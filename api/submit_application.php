<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'] ?? null;
    $tutor_id = $_SESSION['user_id'];

    if ($post_id) {
        // 1. 預防機制：檢查該名老師是否「已經應徵過」這個案件，避免洗版
        $check = $pdo->prepare("SELECT id FROM applications WHERE post_id = ? AND tutor_id = ?");
        $check->execute([$post_id, $tutor_id]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'exists']);
            exit;
        }

        // 2. 寫入應徵紀錄（狀態預設為 pending 待審核）
        $stmt = $pdo->prepare("INSERT INTO applications (post_id, tutor_id, status) VALUES (?, ?, 'pending')");
        if ($stmt->execute([$post_id, $tutor_id])) {
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>