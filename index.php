<?php
session_start();
require_once 'includes/functions.php';

// Jeśli nie ma aktywnego użytkownika, przekieruj do profili
if (!isset($_SESSION['user_id'])) {
    $page = 'profile';
} else {
    $page = $_GET['page'] ?? 'dashboard';
}

// Obsługa akcji (np. logout)
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'logout':
            session_destroy();
            header('Location: ?page=dashboard');
            exit;
    }
}

// Routing stron
$allowedPages = ['dashboard', 'calculator', 'onerm', 'workouts', 'plans', 'profile'];

if (!in_array($page, $allowedPages)) {
    $page = isset($_SESSION['user_id']) ? 'dashboard' : 'profile';
}

$pageFile = "pages/{$page}.php";

include 'includes/header.php';

if (file_exists($pageFile)) {
    include $pageFile;
} else {
    echo '<div class="container"><h2>Strona nie znaleziona</h2></div>';
}

include 'includes/footer.php';
?>