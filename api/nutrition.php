<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'calculate':
        $input = json_decode(file_get_contents('php://input'), true);

        $weight = validateInput($input['weight'] ?? 0, 'float');
        $height = validateInput($input['height'] ?? 0, 'int');
        $age = validateInput($input['age'] ?? 0, 'int');
        $gender = validateInput($input['gender'] ?? '');
        $activity = validateInput($input['activity'] ?? '');
        $goal = validateInput($input['goal'] ?? '');

        if ($weight <= 0 || $height <= 0 || $age <= 0 || !in_array($gender, ['male', 'female']) || !in_array($activity, ['sedentary', 'light', 'moderate', 'active', 'very_active']) || !in_array($goal, ['maintenance', 'reduction', 'mass'])) {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowe dane wejściowe']);
            exit;
        }

        $bmr = calculateBMR($weight, $height, $age, $gender);
        $tdee = calculateTDEE($bmr, $activity);
        $calorieGoal = calculateCalorieGoal($tdee, $goal);
        $macros = calculateMacros($calorieGoal, $weight);

        echo json_encode(['success' => true, 'data' => [
            'bmr' => round($bmr, 1),
            'tdee' => round($tdee, 1),
            'calorieGoal' => round($calorieGoal, 1),
            'macros' => $macros
        ]]);
        break;

    case 'save_goal':
        $input = json_decode(file_get_contents('php://input'), true);
        $goal = validateInput($input['goal'] ?? 0, 'int');

        if ($goal <= 0) {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowy cel']);
            exit;
        }

        $nutrition = loadJsonData('../data/nutrition.json') ?: [];
        $today = date('Y-m-d');

        // Znajdź lub utwórz wpis na dzisiaj
        $found = false;
        foreach ($nutrition as &$entry) {
            if ($entry['date'] === $today) {
                $entry['goal'] = $goal;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $nutrition[] = [
                'id' => generateId(),
                'date' => $today,
                'goal' => $goal,
                'consumed' => 0
            ];
        }

        if (saveJsonData('../data/nutrition.json', $nutrition)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Błąd zapisu']);
        }
        break;

    case 'add_calories':
        $input = json_decode(file_get_contents('php://input'), true);
        $calories = validateInput($input['calories'] ?? 0, 'int');
        $protein = validateInput($input['protein'] ?? 0, 'float');
        $fat = validateInput($input['fat'] ?? 0, 'float');
        $carbs = validateInput($input['carbs'] ?? 0, 'float');
        $description = validateInput($input['description'] ?? '');
        $date = validateInput($input['date'] ?? date('Y-m-d'));

        if ($calories <= 0 || $protein < 0 || $fat < 0 || $carbs < 0) {
            echo json_encode(['success' => false, 'error' => 'Podaj poprawne wartości makroskładników']);
            exit;
        }

        $nutrition = loadJsonData('../data/nutrition.json') ?: [];
        $today = $date;
        $found = false;
        foreach ($nutrition as &$entry) {
            if ($entry['date'] === $today) {
                $found = true;
                $entry['entries'] = $entry['entries'] ?? [];
                $entry['entries'][] = [
                    'id' => generateId(),
                    'time' => date('H:i'),
                    'calories' => $calories,
                    'protein' => $protein,
                    'fat' => $fat,
                    'carbs' => $carbs,
                    'description' => $description
                ];
                $entry['consumed'] = ($entry['consumed'] ?? 0) + $calories;
                $entry['protein'] = ($entry['protein'] ?? 0) + $protein;
                $entry['fat'] = ($entry['fat'] ?? 0) + $fat;
                $entry['carbs'] = ($entry['carbs'] ?? 0) + $carbs;
                break;
            }
        }

        if (!$found) {
            $nutrition[] = [
                'id' => generateId(),
                'date' => $today,
                'goal' => 2000,
                'consumed' => $calories,
                'protein' => $protein,
                'fat' => $fat,
                'carbs' => $carbs,
                'entries' => [[
                    'id' => generateId(),
                    'time' => date('H:i'),
                    'calories' => $calories,
                    'protein' => $protein,
                    'fat' => $fat,
                    'carbs' => $carbs,
                    'description' => $description
                ]]
            ];
        }

        if (saveJsonData('../data/nutrition.json', $nutrition)) {
            // Odczytaj ponownie dzisiejszy wpis, aby zwrócić zaktualizowane dane
            $responseEntry = [
                'date' => $today,
                'goal' => 2000,
                'consumed' => 0,
                'protein' => 0,
                'fat' => 0,
                'carbs' => 0,
                'entries' => []
            ];
            foreach ($nutrition as $item) {
                if ($item['date'] === $today) {
                    $responseEntry = $item;
                    break;
                }
            }
            echo json_encode(['success' => true, 'data' => $responseEntry]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Błąd zapisu']);
        }
        break;

    case 'get_today_nutrition':
        $nutrition = loadJsonData('../data/nutrition.json') ?: [];
        $today = date('Y-m-d');
        $result = [
            'date' => $today,
            'goal' => 2000,
            'consumed' => 0,
            'protein' => 0,
            'fat' => 0,
            'carbs' => 0,
            'entries' => []
        ];

        foreach ($nutrition as $entry) {
            if ($entry['date'] === $today) {
                $result = array_merge($result, $entry);
                break;
            }
        }

        echo json_encode(['success' => true, 'data' => $result]);
        break;

    case 'get_weight_data':
        $nutrition = loadJsonData('../data/nutrition.json') ?: [];
        $weightData = [];

        // Pobierz dane wagi z ostatnich 30 dni
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $weight = null;
            foreach ($nutrition as $entry) {
                if ($entry['date'] === $date && isset($entry['weight'])) {
                    $weight = $entry['weight'];
                    break;
                }
            }
            $weightData[] = [
                'date' => formatDate($date),
                'weight' => $weight
            ];
        }

        $dates = array_column($weightData, 'date');
        $weights = array_column($weightData, 'weight');

        echo json_encode(['success' => true, 'data' => ['dates' => $dates, 'weights' => $weights]]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
}
?>