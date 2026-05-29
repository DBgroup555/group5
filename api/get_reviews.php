<?php
require_once '../config/db.php';

$tutor_id = $_GET['tutor_id'] ?? 0;

if ($tutor_id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name AS student_name 
        FROM reviews r
        JOIN users u ON r.student_id = u.id
        WHERE r.tutor_id = ?
        ORDER BY r.id DESC
    ");
    $stmt->execute([$tutor_id]);
    $reviews = $stmt->fetchAll();
    
    echo json_encode($reviews);
    exit;
}
echo json_encode([]);
exit;