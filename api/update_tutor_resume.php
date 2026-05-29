<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $subject = $_POST['subject'] ?? '';
    $region = $_POST['region'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $tutor_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. 更新 users 表的簡介 (bio)
        $stmt1 = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt1->execute([$bio, $tutor_id]);

        // 2. 更新 posts 表該老師的第一篇初始貼文（科目、地區、時薪、內文）
        $stmt2 = $pdo->prepare("
            UPDATE posts 
            SET subject = ?, region = ?, budget = ?, content = ? 
            WHERE user_id = ? 
            ORDER BY id ASC 
            LIMIT 1
        ");
        $stmt2->execute([$subject, $region, $budget, $bio, $tutor_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success']);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
echo json_encode(['status' => 'error']);
?>