<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => '不合法的請求方法']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '登入狀態已過期，請重新登入']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? null;
$message = $_POST['message'] ?? ''; 

if (empty($receiver_id)) {
    echo json_encode(['status' => 'error', 'msg' => '缺少接收者 ID (receiver_id)']);
    exit;
}

if (trim($message) === '') {
    echo json_encode(['status' => 'error', 'msg' => '訊息內容不可為空']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$sender_id, $receiver_id, $message])) {
        echo json_encode(['status' => 'success']);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'msg' => '資料庫寫入失敗']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => 'SQL 錯誤: ' . $e->getMessage()]);
    exit;
}