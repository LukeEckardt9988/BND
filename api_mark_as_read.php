<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$email_id = $_POST['id'];

$stmt = $pdo->prepare("UPDATE emails SET is_read = 1 WHERE id = :email_id AND recipient_id = :user_id");
$success = $stmt->execute([':email_id' => $email_id, ':user_id' => $user_id]);

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
?>