<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$my_id = $_SESSION['user_id'];

$sql = "
    SELECT DISTINCT u.id, u.name, u.role 
    FROM users u
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = :my_id OR m.receiver_id = :my_id) 
      AND u.id != :my_id 
      AND u.id != 2
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['my_id' => $my_id]);
$contacts = $stmt->fetchAll();

echo json_encode($contacts);
exit;