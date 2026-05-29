<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $app_id = $_POST['app_id'] ?? 0;
    $tutor_id = $_POST['tutor_id'] ?? 0;
    $comment = $_POST['comment'] ?? ''; 
    $student_id = $_SESSION['user_id'];

    if ($app_id > 0 && $tutor_id > 0 && !empty($comment)) {
        // 檢查是否已經評價過
        $check = $pdo->prepare("SELECT id FROM reviews WHERE application_id = ?");
        $check->execute([$app_id]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => '您已經給過這未老師評價囉！']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO reviews (application_id, student_id, tutor_id, comment) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$app_id, $student_id, $tutor_id, $comment])) {
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}
echo json_encode(['status' => 'error', 'message' => '欄位填寫不完整']);
exit;