<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    $post_id = $_POST['post_id'] ?? null;

    if ($post_id) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        if ($stmt->execute([$post_id])) {
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>