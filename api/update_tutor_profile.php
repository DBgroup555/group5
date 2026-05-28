<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $new_name = $_POST['name'] ?? '';
    $new_bio = $_POST['bio'] ?? '';
    $tutor_id = $_SESSION['user_id'];

    if (!empty($new_name)) {
        try {
            $pdo->beginTransaction();

            $stmt1 = $pdo->prepare("UPDATE users SET name = ?, bio = ? WHERE id = ?");
            $stmt1->execute([$new_name, $new_bio, $tutor_id]);

            $new_title = "大家好，我是 " . $new_name;
            
            $stmt2 = $pdo->prepare("
                UPDATE posts 
                SET title = ?, content = ? 
                WHERE user_id = ? 
                ORDER BY id ASC 
                LIMIT 1
            ");
            $stmt2->execute([$new_title, $new_bio, $tutor_id]);

            $pdo->commit();
            $_SESSION['user_name'] = $new_name; 

            echo json_encode(['status' => 'success', 'name' => $new_name]);
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