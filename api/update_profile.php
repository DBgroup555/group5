<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $new_name = $_POST['name'] ?? '';

    if (!empty($new_name)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        if ($stmt->execute([$new_name, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $new_name; 
            echo json_encode(['status' => 'success', 'name' => $new_name]);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>