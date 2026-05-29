<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $application_id = $_POST['app_id'] ?? null;
    $action = $_POST['action'] ?? ''; 

    if ($application_id && in_array($action, ['accepted', 'rejected'])) {
        $stmt = $pdo->prepare("
            SELECT app.id FROM applications app
            JOIN posts p ON app.post_id = p.id
            WHERE app.id = ? AND p.user_id = ?
        ");
        $stmt->execute([$application_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
            if ($update->execute([$action, $application_id])) {
                echo json_encode(['status' => 'success', 'new_status' => $action]);
                exit;
            }
        }
    }
}
echo json_encode(['status' => 'error']);
?>