<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'] ?? null;
    $tutor_id = $_SESSION['user_id'];

    if ($post_id) {
        $check = $pdo->prepare("SELECT id FROM applications WHERE post_id = ? AND tutor_id = ?");
        $check->execute([$post_id, $tutor_id]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'exists']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO applications (post_id, tutor_id, status) VALUES (?, ?, 'pending')");
        if ($stmt->execute([$post_id, $tutor_id])) {
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>