<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(id) FROM emails WHERE recipient_id = :user_id AND is_read = 0");
$stmt->execute([':user_id' => $user_id]);
$count = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'count' => $count]);
?>