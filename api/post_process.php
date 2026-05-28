<?php
// require_once '../config/db.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $title = $_POST['title'] ?? '無標題需求';
    $subject = $_POST['subject'] ?? '';
    $region = $_POST['region'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $content = $_POST['content'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, subject, region, budget, content) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $title, $subject, $region, $budget, $content])) {
        echo "<script>alert('發布成功！'); location.href='../student.php';</script>";
    }
}
?>