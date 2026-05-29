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

        $stmt1 = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt1->execute([$bio, $tutor_id]);

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