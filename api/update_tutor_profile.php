<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {

    $new_name = $_POST['name'] ?? '';
    $new_subject = $_POST['subject'] ?? '其他';
    $new_region = $_POST['region'] ?? '台北';
    $new_budget = $_POST['budget'] ?? 'NT$600+/hr';
    $new_bio = $_POST['bio'] ?? '';
    $tutor_id = $_SESSION['user_id'];

    if (!empty($new_name)) {
        try {

            $pdo->beginTransaction();

            $stmt1 = $pdo->prepare("UPDATE users SET name = ?, bio = ? WHERE id = ?");
            $stmt1->execute([$new_name, $new_bio, $tutor_id]);

            $new_title = "大家好，我是 " . $new_name . " 老師，歡迎找我接案！";
            
            $stmt2 = $pdo->prepare("
                UPDATE posts 
                SET title = ?, subject = ?, region = ?, budget = ?, content = ? 
                WHERE user_id = ? 
                ORDER BY id ASC 
                LIMIT 1
            ");
            $stmt2->execute([$new_title, $new_subject, $new_region, $new_budget, $new_bio, $tutor_id]);

            $pdo->commit();

            $_SESSION['user_name'] = $new_name; 

            echo json_encode([
                'status' => 'success', 
                'name' => $new_name,
                'subject' => $new_subject,
                'region' => $new_region,
                'budget' => $new_budget,
                'bio' => $new_bio
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>