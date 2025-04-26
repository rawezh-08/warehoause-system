<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?> 