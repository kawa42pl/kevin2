<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'calculate_onerm':
        $input = json_decode(file_get_contents('php://input'), true);
        $weight = validateInput($input['weight'] ?? 0, 'float');
        $reps = validateInput($input['reps'] ?? 0, 'int');

        if ($weight <= 0 || $reps <= 0) {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowe dane']);
            exit;
        }

        $onerm = calculateOneRM($weight, $reps);
        $percentages = calculatePercentages($onerm['epley']); // Używamy Epley jako bazowego

        echo json_encode(['success' => true, 'data' => array_merge($onerm, ['percentages' => $percentages])]);
        break;

    case 'save_onerm_record':
        $input = json_decode(file_get_contents('php://input'), true);
        $exercise = validateInput($input['exercise'] ?? '');
        $weight = validateInput($input['weight'] ?? 0, 'float');

        if (!$exercise || $weight <= 0) {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowe dane']);
            exit;
        }

        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $workouts[] = [
            'id' => generateId(),
            'type' => 'onerm_record',
            'exercise' => $exercise,
            'weight' => $weight,
            'date' => date('Y-m-d')
        ];

        if (saveJsonData('../data/workouts.json', $workouts)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Błąd zapisu']);
        }
        break;

    case 'get_onerm_records':
        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $records = [];

        foreach ($workouts as $workout) {
            if ($workout['type'] === 'onerm_record') {
                $exercise = $workout['exercise'];
                if (!isset($records[$exercise])) {
                    $records[$exercise] = [];
                }
                $records[$exercise][] = [
                    'date' => formatDate($workout['date']),
                    'weight' => $workout['weight']
                ];
            }
        }

        // Sortuj po dacie
        foreach ($records as $exercise => &$recs) {
            usort($recs, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));
        }

        echo json_encode(['success' => true, 'data' => $records]);
        break;

    case 'save_session':
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['date'], $input['exercises'])) {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowe dane']);
            exit;
        }

        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $workouts[] = [
            'id' => generateId(),
            'type' => 'session',
            'date' => validateInput($input['date']),
            'plan' => validateInput($input['plan'] ?? ''),
            'note' => validateInput($input['note'] ?? ''),
            'exercises' => array_map(function($ex) {
                return [
                    'name' => validateInput($ex['name']),
                    'sets' => validateInput($ex['sets'], 'int'),
                    'reps' => validateInput($ex['reps'], 'int'),
                    'weight' => validateInput($ex['weight'], 'float'),
                    'rpe' => $ex['rpe'] ? validateInput($ex['rpe'], 'int') : null
                ];
            }, $input['exercises'])
        ];

        if (saveJsonData('../data/workouts.json', $workouts)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Błąd zapisu']);
        }
        break;

    case 'get_sessions':
        $month = validateInput($_GET['month'] ?? '', 'int');
        $plan = validateInput($_GET['plan'] ?? '');

        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $sessions = [];
        $stats = ['totalSessions' => 0, 'totalVolume' => 0, 'favoriteExercise' => ''];
        $exerciseCount = [];
        $chartData = ['weeks' => [], 'counts' => []];

        foreach ($workouts as $workout) {
            if ($workout['type'] === 'session') {
                // Filtry
                if ($month && date('m', strtotime($workout['date'])) != $month) continue;
                if ($plan && stripos($workout['plan'], $plan) === false) continue;

                $sessions[] = [
                    'date' => formatDate($workout['date']),
                    'plan' => $workout['plan'],
                    'note' => $workout['note'],
                    'exercises' => $workout['exercises']
                ];

                $stats['totalSessions']++;

                // Oblicz volume
                foreach ($workout['exercises'] as $ex) {
                    $stats['totalVolume'] += $ex['sets'] * $ex['reps'] * $ex['weight'];
                    $exerciseCount[$ex['name']] = ($exerciseCount[$ex['name']] ?? 0) + 1;
                }

                // Dane do wykresu
                $week = date('W', strtotime($workout['date']));
                $year = date('Y', strtotime($workout['date']));
                $weekKey = $year . '-W' . $week;
                if (!isset($chartData['weeks'][$weekKey])) {
                    $chartData['weeks'][$weekKey] = 0;
                }
                $chartData['weeks'][$weekKey]++;
            }
        }

        // Sortuj sesje malejąco po dacie
        usort($sessions, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

        // Znajdź ulubione ćwiczenie
        if ($exerciseCount) {
            arsort($exerciseCount);
            $stats['favoriteExercise'] = array_key_first($exerciseCount);
        }

        // Przygotuj dane wykresu
        $weeksData = $chartData['weeks'];
        ksort($weeksData);
        $chartData['weeks'] = array_keys($weeksData);
        $chartData['counts'] = array_values($weeksData);
        $chartData['counts'] = array_map(fn($week) => $chartData['weeks'][$week] ?? 0, array_keys($chartData['weeks'])); // Fix

        echo json_encode(['success' => true, 'data' => ['sessions' => $sessions, 'stats' => $stats, 'chartData' => $chartData]]);
        break;

    case 'get_monthly_stats':
        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $nutrition = loadJsonData('../data/nutrition.json') ?: [];
        $thisMonth = date('Y-m');
        $thisWeek = date('W');
        $thisYear = date('Y');
        
        $monthlyCount = 0;
        $avgCalories = 0;
        $streak = 0;
        $calorieDates = [];
        
        // Treningi w tym miesiącu
        foreach ($workouts as $w) {
            if ($w['type'] === 'session' && strpos($w['date'], $thisMonth) === 0) {
                $monthlyCount++;
            }
        }
        
        // Średnie kalorie
        $totalCalories = 0;
        $calorieCount = 0;
        foreach ($nutrition as $n) {
            if (is_array($n) && strpos($n['date'] ?? '', $thisMonth) === 0) {
                $totalCalories += $n['consumed'] ?? 0;
                $calorieCount++;
                $calorieDates[$n['date']] = true;
            }
        }
        $avgCalories = $calorieCount > 0 ? intval($totalCalories / $calorieCount) : 0;
        
        // Seria treningów
        $today = new DateTime();
        for ($i = 0; $i < 100; $i++) {
            $checkDate = $today->format('Y-m-d');
            $dayHasWorkout = false;
            
            foreach ($workouts as $w) {
                if ($w['type'] === 'session' && $w['date'] === $checkDate) {
                    $dayHasWorkout = true;
                    break;
                }
            }
            
            if ($dayHasWorkout) {
                $streak++;
                $today->modify('-1 day');
            } else {
                break;
            }
        }
        
        echo json_encode(['success' => true, 'data' => [
            'monthly_count' => $monthlyCount,
            'avg_calories' => $avgCalories,
            'streak' => $streak
        ]]);
        break;

    case 'get_recent':
        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $sessions = [];
        
        foreach ($workouts as $w) {
            if ($w['type'] === 'session') {
                $sessions[] = $w;
            }
        }
        
        usort($sessions, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
        $recent = array_slice($sessions, 0, 5);
        
        echo json_encode(['success' => true, 'data' => $recent]);
        break;

    case 'get_weekly_activity':
        $workouts = loadJsonData('../data/workouts.json') ?: [];
        $days = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'Nd'];
        $counts = [0, 0, 0, 0, 0, 0, 0];
        
        $today = new DateTime();
        $monday = clone $today;
        $monday->modify('monday this week');
        
        for ($i = 0; $i < 7; $i++) {
            $checkDate = $monday->format('Y-m-d');
            $dayOfWeek = $monday->format('N') - 1; // 0-6
            
            foreach ($workouts as $w) {
                if ($w['type'] === 'session' && $w['date'] === $checkDate) {
                    $counts[$dayOfWeek]++;
                }
            }
            
            $monday->modify('+1 day');
        }
        
        echo json_encode(['success' => true, 'data' => [
            'days' => $days,
            'counts' => $counts
        ]]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
}
?>