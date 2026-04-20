<?php
$_GET['action'] = 'add_calories';
$payload = json_encode([
    'calories' => 150,
    'protein' => 12,
    'fat' => 8,
    'carbs' => 10,
    'description' => 'test'
]);
file_put_contents('php://stdout', shell_exec('php -r ""')); // no-op
$stdin = fopen('php://memory','rw');
fwrite($stdin, $payload);
rewind($stdin);

$orig = fopen('php://stdin', 'r');
// Since php://input is read-only and not easy to override here, this test won't work as expected.
