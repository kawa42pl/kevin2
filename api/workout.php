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

    default:
        echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
}
?>