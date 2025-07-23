<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['program_id'])) {
    echo json_encode([]);
    exit;
}

require 'db_connect.php';
$program_id = $_GET['program_id'];

try {
    // Holt jetzt auch die neue 'explanation' Spalte
    $sql = "SELECT id, keyword, description, explanation FROM commands WHERE program_id = :program_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':program_id', $program_id, PDO::PARAM_INT);
    $stmt->execute();
    $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($commands);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>