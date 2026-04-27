<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/functions.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_profiles':
        $users = loadJsonData('../data/users.json') ?: [];
        echo json_encode(['success' => true, 'data' => array_values($users)]);
        break;

    case 'get_profile':
        $id = validateInput($_GET['id'] ?? '', 'string');
        $users = loadJsonData('../data/users.json') ?: [];
        $profile = $users[$id] ?? null;
        if ($profile) {
            echo json_encode(['success' => true, 'data' => $profile]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Profil nie znaleziony']);
        }
        break;

    case 'save_profile':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowe dane']);
            exit;
        }

        $users = loadJsonData('../data/users.json') ?: [];
        $id = $input['id'];
        $users[$id] = [
            'id' => $id,
            'name' => validateInput($input['name']),
            'gender' => validateInput($input['gender']),
            'birthdate' => validateInput($input['birthdate']),
            'weight' => validateInput($input['weight'], 'float'),
            'height' => validateInput($input['height'], 'int'),
            'avatar' => validateInput($input['avatar'])
        ];

        if (saveJsonData('../data/users.json', $users)) {
            // Ustaw profil jako aktywny, jeśli nie ma jeszcze aktywnego profilu lub edytowany profil jest aktualny
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === $id) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_data'] = $users[$id];
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Błąd zapisu']);
        }
        break;

    case 'switch_profile':
        $id = validateInput($_GET['id'] ?? '', 'string');
        $users = loadJsonData('../data/users.json') ?: [];
        if (isset($users[$id])) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_data'] = $users[$id];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Profil nie znaleziony']);
        }
        break;

    case 'delete_profile':
        $id = validateInput($_GET['id'] ?? '', 'string');
        $users = loadJsonData('../data/users.json') ?: [];
        if (isset($users[$id])) {
            unset($users[$id]);
            if (saveJsonData('../data/users.json', $users)) {
                // Jeśli usuwany jest aktywny profil, wyloguj
                if (($_SESSION['user_id'] ?? '') === $id) {
                    session_destroy();
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Błąd zapisu']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Profil nie znaleziony']);
        }
        break;

    case 'upload_avatar':
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Błąd wgrywania pliku']);
            exit;
        }

        $file = $_FILES['file'];
        $profileId = validateInput($_POST['id'] ?? '', 'string');
        
        if (!$profileId) {
            echo json_encode(['success' => false, 'error' => 'Brak ID profilu']);
            exit;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedMimes)) {
            echo json_encode(['success' => false, 'error' => 'Nieobsługiwany format pliku']);
            exit;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'Plik jest za duży (max 5MB)']);
            exit;
        }

        $uploadDir = '../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $profileId . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        $relativeUrl = 'uploads/avatars/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => true, 'data' => ['avatar' => $relativeUrl]]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Błąd zapisania pliku']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
}
?>