<?php
session_start();
require_once 'db_connect.php';

// Diese Funktion ist eine Kopie der bewährten Logik aus der mission_console.php
if (!function_exists('send_ingame_email')) {
    function send_ingame_email($template_id, $user_id, $pdo)
    {
        $stmt_get_template = $pdo->prepare("SELECT * FROM email_templates WHERE id = :template_id");
        $stmt_get_template->execute([':template_id' => $template_id]);
        $template_data = $stmt_get_template->fetch(PDO::FETCH_ASSOC);
        if ($template_data) {
            $stmt_insert_email = $pdo->prepare(
                "INSERT INTO emails (template_id, recipient_id, sent_at, is_read, is_phishing_copy, phishing_analysis_data_copy)
                 VALUES (:template_id, :user_id, NOW(), 0, :is_phishing, :phishing_data)"
            );
            $stmt_insert_email->execute([
                ':template_id' => $template_data['id'],
                ':user_id' => $user_id,
                ':is_phishing' => $template_data['is_phishing'],
                ':phishing_data' => $template_data['phishing_analysis_data']
            ]);
            return true;
        }
        return false;
    }
}

if (!isset($_SESSION['user_id']) || !isset($_POST['template_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert oder keine Template-ID.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$template_id = $_POST['template_id'];

$success = send_ingame_email($template_id, $user_id, $pdo);

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
