<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

$plan = $_GET['plan'] ?? '';

$plans = [
    'powerlifting' => [
        'type' => 'powerlifting',
        'name' => 'Trójbój Siłowy',
        'description' => 'Program skoncentrowany na rozwoju siły w trzech podstawowych ćwiczeniach: przysiad, martwy ciąg i wyciskanie sztangi.',
        'days' => [
            [
                'name' => 'Przysiad',
                'exercises' => [
                    ['name' => 'Przysiad', 'sets' => 5, 'reps' => 5, 'notes' => 'Praca nad techniką'],
                    ['name' => 'Wyciskanie żołnierskie', 'sets' => 4, 'reps' => 6, 'notes' => 'Rozgrzewka'],
                    ['name' => 'Martwy ciąg rumuński', 'sets' => 3, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Core', 'sets' => 3, 'reps' => 12, 'notes' => 'Plank lub Russian twist']
                ]
            ],
            [
                'name' => 'Wyciskanie',
                'exercises' => [
                    ['name' => 'Wyciskanie sztangi', 'sets' => 5, 'reps' => 5, 'notes' => ''],
                    ['name' => 'Przysiad bułgarski', 'sets' => 4, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Wiosłowanie', 'sets' => 4, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Triceps', 'sets' => 3, 'reps' => 12, 'notes' => 'Wyciągi']
                ]
            ],
            [
                'name' => 'Martwy ciąg',
                'exercises' => [
                    ['name' => 'Martwy ciąg', 'sets' => 5, 'reps' => 3, 'notes' => 'Maksymalne obciążenia'],
                    ['name' => 'Wyciskanie żołnierskie', 'sets' => 4, 'reps' => 5, 'notes' => ''],
                    ['name' => 'Przysiad przedni', 'sets' => 3, 'reps' => 6, 'notes' => ''],
                    ['name' => 'Biceps', 'sets' => 3, 'reps' => 12, 'notes' => '']
                ]
            ],
            [
                'name' => 'Siła pomocnicza',
                'exercises' => [
                    ['name' => 'Wyciskanie skośne', 'sets' => 4, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Wiosłowanie T-bar', 'sets' => 4, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Leg press', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Core', 'sets' => 4, 'reps' => 15, 'notes' => '']
                ]
            ]
        ]
    ],
    'physique_male' => [
        'type' => 'physique_male',
        'name' => 'Sylwetka — Mężczyzna',
        'description' => 'Program Push/Pull/Legs skupiający się na rozwoju mięśni i poprawie kompozycji ciała.',
        'days' => [
            [
                'name' => 'Push',
                'exercises' => [
                    ['name' => 'Wyciskanie', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Wyciskanie skośne hantlami', 'sets' => 3, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Rozpiętki', 'sets' => 3, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Triceps wyciąg', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Boczne unoszenie', 'sets' => 3, 'reps' => 15, 'notes' => '']
                ]
            ],
            [
                'name' => 'Pull',
                'exercises' => [
                    ['name' => 'Podciąganie', 'sets' => 4, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Wiosłowanie sztangą', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Wiosłowanie hantlem', 'sets' => 3, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Biceps sztanga', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Młotki', 'sets' => 3, 'reps' => 15, 'notes' => '']
                ]
            ],
            [
                'name' => 'Legs',
                'exercises' => [
                    ['name' => 'Przysiad', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Leg press', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Wykroki', 'sets' => 3, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Uginanie nóg', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Łydki', 'sets' => 5, 'reps' => 15, 'notes' => '']
                ]
            ],
            [
                'name' => 'Full body siłowy',
                'exercises' => [
                    ['name' => 'Martwy ciąg', 'sets' => 4, 'reps' => 6, 'notes' => ''],
                    ['name' => 'Wyciskanie żołnierskie', 'sets' => 4, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Podciąganie obciążone', 'sets' => 4, 'reps' => 6, 'notes' => ''],
                    ['name' => 'Core', 'sets' => 4, 'reps' => 15, 'notes' => '']
                ]
            ]
        ]
    ],
    'physique_female' => [
        'type' => 'physique_female',
        'name' => 'Sylwetka — Kobieta',
        'description' => 'Program dostosowany do potrzeb kobiet, skupiający się na kształtowaniu sylwetki i wzmocnieniu.',
        'days' => [
            [
                'name' => 'Dolna partia (glute focus)',
                'exercises' => [
                    ['name' => 'Przysiad sumo', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Hip thrust', 'sets' => 4, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Wykroki', 'sets' => 3, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Abdukcja', 'sets' => 4, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Łydki', 'sets' => 4, 'reps' => 15, 'notes' => '']
                ]
            ],
            [
                'name' => 'Górna partia',
                'exercises' => [
                    ['name' => 'Wyciskanie hantlami', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Wiosłowanie hantlem', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Unoszenie boczne', 'sets' => 3, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Biceps', 'sets' => 3, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Triceps', 'sets' => 3, 'reps' => 15, 'notes' => '']
                ]
            ],
            [
                'name' => 'Dolna partia (quad focus)',
                'exercises' => [
                    ['name' => 'Przysiad bułgarski', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Leg press', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Wyprosty nóg', 'sets' => 3, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Uginanie nóg', 'sets' => 3, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Glute bridge', 'sets' => 4, 'reps' => 20, 'notes' => '']
                ]
            ],
            [
                'name' => 'Full body + core',
                'exercises' => [
                    ['name' => 'Martwy ciąg rumuński', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Podciąganie (asystowane)', 'sets' => 3, 'reps' => 8, 'notes' => ''],
                    ['name' => 'Wyciskanie żołnierskie hantlami', 'sets' => 3, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Plank', 'sets' => 4, 'reps' => 45, 'notes' => 'sekundy'],
                    ['name' => 'Russian twist', 'sets' => 4, 'reps' => 20, 'notes' => '']
                ]
            ]
        ]
    ],
    'calisthenics' => [
        'type' => 'calisthenics',
        'name' => 'Kalistenika',
        'description' => 'Program wykorzystujący ciężar własnego ciała do budowania siły i masy mięśniowej.',
        'days' => [
            [
                'name' => 'Push',
                'exercises' => [
                    ['name' => 'Pompki', 'sets' => 5, 'reps' => 'max', 'notes' => ''],
                    ['name' => 'Dipy', 'sets' => 4, 'reps' => 'max', 'notes' => ''],
                    ['name' => 'Pompki diamentowe', 'sets' => 3, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Pike push-up', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Pompki z elewacją', 'sets' => 3, 'reps' => 15, 'notes' => '']
                ]
            ],
            [
                'name' => 'Pull',
                'exercises' => [
                    ['name' => 'Podciąganie nachwytem', 'sets' => 5, 'reps' => 'max', 'notes' => ''],
                    ['name' => 'Podciąganie podchwytem', 'sets' => 4, 'reps' => 'max', 'notes' => ''],
                    ['name' => 'Australian pull-up', 'sets' => 4, 'reps' => 12, 'notes' => ''],
                    ['name' => 'Face pull z gumy', 'sets' => 3, 'reps' => 15, 'notes' => ''],
                    ['name' => 'Zwisy', 'sets' => 3, 'reps' => 30, 'notes' => 'sekundy']
                ]
            ],
            [
                'name' => 'Legs + Core',
                'exercises' => [
                    ['name' => 'Pistol squat', 'sets' => 4, 'reps' => 6, 'notes' => 'progresja'],
                    ['name' => 'Wykroki bułgarskie', 'sets' => 4, 'reps' => 10, 'notes' => ''],
                    ['name' => 'Nordic curl', 'sets' => 3, 'reps' => 5, 'notes' => ''],
                    ['name' => 'L-sit', 'sets' => 4, 'reps' => 20, 'notes' => 'sekundy'],
                    ['name' => 'Dragon flag', 'sets' => 3, 'reps' => 5, 'notes' => '']
                ]
            ],
            [
                'name' => 'Umiejętnościowy',
                'exercises' => [
                    ['name' => 'Muscle-up progresja', 'sets' => 5, 'reps' => 3, 'notes' => ''],
                    ['name' => 'Handstand (przy ścianie)', 'sets' => 5, 'reps' => 20, 'notes' => 'sekundy'],
                    ['name' => 'Planche lean', 'sets' => 4, 'reps' => 15, 'notes' => 'sekundy'],
                    ['name' => 'Front lever tuck', 'sets' => 4, 'reps' => 10, 'notes' => 'sekundy'],
                    ['name' => 'Core', 'sets' => 4, 'reps' => 15, 'notes' => '']
                ]
            ]
        ]
    ]
];

if (isset($plans[$plan])) {
    echo json_encode(['success' => true, 'data' => $plans[$plan]]);
} else {
    echo json_encode(['success' => false, 'error' => 'Plan nie znaleziony']);
}
?>