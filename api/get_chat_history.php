<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['with_id'])) {
    echo json_encode([]);
    exit;
}

$my_id = $_SESSION['user_id'];
$with_id = $_GET['with_id'];

// 撈出雙方往來的完整對話，並按時間排序
$sql = "
    SELECT * FROM messages 
    WHERE (sender_id = :my_id AND receiver_id = :with_id)
       OR (sender_id = :with_id AND receiver_id = :my_id)
    ORDER BY id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['my_id' => $my_id, 'with_id' => $with_id]);
$history = $stmt->fetchAll();

echo json_encode($history);
exit;