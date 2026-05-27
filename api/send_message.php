<?php
require_once './config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $receiver_id = $_POST['receiver_id'] ?? null;
    $message = $_POST['message'] ?? '';

    if ($receiver_id && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
        echo json_encode(['status' => 'success']);
        exit;
    }
}
echo json_encode(['status' => 'error']);
?>