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