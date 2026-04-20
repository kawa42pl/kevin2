<?php
// Funkcje pomocnicze dla aplikacji fitness

/**
 * Oblicza BMR (Basal Metabolic Rate) wg wzoru Mifflin-St Jeor
 * @param float $weight Masa ciała w kg
 * @param float $height Wzrost w cm
 * @param int $age Wiek w latach
 * @param string $gender 'male' lub 'female'
 * @return float BMR w kcal
 */
function calculateBMR($weight, $height, $age, $gender) {
    if ($gender === 'male') {
        return 10 * $weight + 6.25 * $height - 5 * $age + 5;
    } else {
        return 10 * $weight + 6.25 * $height - 5 * $age - 161;
    }
}

/**
 * Oblicza TDEE (Total Daily Energy Expenditure) na podstawie BMR i poziomu aktywności
 * @param float $bmr BMR w kcal
 * @param string $activity Poziom aktywności: 'sedentary', 'light', 'moderate', 'active', 'very_active'
 * @return float TDEE w kcal
 */
function calculateTDEE($bmr, $activity) {
    $multipliers = [
        'sedentary' => 1.2,
        'light' => 1.375,
        'moderate' => 1.55,
        'active' => 1.725,
        'very_active' => 1.9
    ];
    return $bmr * ($multipliers[$activity] ?? 1.2);
}

/**
 * Oblicza cel kaloryczny na podstawie TDEE i celu
 * @param float $tdee TDEE w kcal
 * @param string $goal 'maintenance', 'reduction', 'mass'
 * @return float Cel kaloryczny w kcal
 */
function calculateCalorieGoal($tdee, $goal) {
    switch ($goal) {
        case 'reduction':
            return $tdee - 500;
        case 'mass':
            return $tdee + 300;
        default:
            return $tdee;
    }
}

/**
 * Oblicza makroskładniki na podstawie celu kalorycznego i masy ciała
 * @param float $calorieGoal Cel kaloryczny w kcal
 * @param float $weight Masa ciała w kg
 * @return array ['protein' => g, 'fat' => g, 'carbs' => g, 'protein_pct' => %, 'fat_pct' => %, 'carbs_pct' => %]
 */
function calculateMacros($calorieGoal, $weight) {
    $proteinG = 2 * $weight; // 2g na kg masy ciała
    $proteinKcal = $proteinG * 4;
    $fatPct = 0.25; // 25% kalorii z tłuszczów
    $fatKcal = $calorieGoal * $fatPct;
    $fatG = $fatKcal / 9;
    $carbsKcal = $calorieGoal - $proteinKcal - $fatKcal;
    $carbsG = $carbsKcal / 4;
    $carbsPct = $carbsKcal / $calorieGoal;

    return [
        'protein' => round($proteinG, 1),
        'fat' => round($fatG, 1),
        'carbs' => round($carbsG, 1),
        'protein_pct' => round(($proteinKcal / $calorieGoal) * 100, 1),
        'fat_pct' => round($fatPct * 100, 1),
        'carbs_pct' => round($carbsPct * 100, 1)
    ];
}

/**
 * Oblicza 1RM wg różnych wzorów
 * @param float $weight Ciężar w kg
 * @param int $reps Liczba powtórzeń
 * @return array ['epley' => 1RM, 'brzycki' => 1RM, 'lander' => 1RM]
 */
function calculateOneRM($weight, $reps) {
    $epley = $weight * (1 + $reps / 30);
    $brzycki = $weight * 36 / (37 - $reps);
    $lander = (100 * $weight) / (101.3 - 2.67123 * $reps);
    return [
        'epley' => round($epley, 1),
        'brzycki' => round($brzycki, 1),
        'lander' => round($lander, 1)
    ];
}

/**
 * Oblicza obciążenia procentowe dla danego 1RM
 * @param float $oneRM 1RM w kg
 * @return array ['100' => kg, '95' => kg, ...]
 */
function calculatePercentages($oneRM) {
    $percentages = [100, 95, 90, 85, 80, 75, 70, 65, 60];
    $result = [];
    foreach ($percentages as $pct) {
        $result[$pct] = round($oneRM * ($pct / 100), 1);
    }
    return $result;
}

/**
 * Waliduje dane wejściowe
 * @param mixed $input Dane do walidacji
 * @param string $type Typ: 'string', 'int', 'float', 'email'
 * @return mixed Przefiltrowane dane lub false jeśli niepoprawne
 */
function validateInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Zapisuje dane do pliku JSON z blokadą
 * @param string $filePath Ścieżka do pliku
 * @param mixed $data Dane do zapisania
 * @return bool Sukces
 */
function saveJsonData($filePath, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filePath, $json, LOCK_EX) !== false;
}

/**
 * Odczytuje dane z pliku JSON
 * @param string $filePath Ścieżka do pliku
 * @return mixed Dane lub false jeśli błąd
 */
function loadJsonData($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    $content = file_get_contents($filePath);
    return json_decode($content, true);
}

/**
 * Generuje unikalne ID
 * @return string ID
 */
function generateId() {
    return uniqid('', true);
}

/**
 * Formatuje datę na DD.MM.YYYY
 * @param string $date Data w formacie Y-m-d lub podobnym
 * @return string Sformatowana data
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Parsuje datę z DD.MM.YYYY na Y-m-d
 * @param string $date Data w formacie DD.MM.YYYY
 * @return string Data w Y-m-d
 */
function parseDate($date) {
    $parts = explode('.', $date);
    if (count($parts) === 3) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $date;
}
?>