<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    $user_id = $_POST['user_id'] ?? null;
    $current_status = intval($_POST['current_status'] ?? 1);
    
    $new_status = ($current_status === 1) ? 0 : 1;

    if ($user_id) {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
        if ($stmt->execute([$new_status, $user_id])) {
            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>