<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $new_name = $_POST['name'] ?? '';
    $tutor_id = $_SESSION['user_id'];

    if (!empty($name_input = trim($new_name))) {

        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        if ($stmt->execute([$name_input, $tutor_id])) {
            $_SESSION['user_name'] = $name_input; 
            echo json_encode(['status' => 'success', 'name' => $name_input]);
            exit;
        }
    }
}
echo json_encode(['status' => 'error']);
?>