<?php
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

// 處理註冊
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $phone = $_POST['phone'] ?? '';
    $gender = $_POST['gender'] ?? 'Other';
    $bio = $_POST['bio'] ?? ''; // 老師才有的欄位

    // 檢查 Email 是否重複
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "<script>alert('該 Email 已被註冊！'); history.back();</script>";
        exit;
    }

    // 密碼雜湊加密
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 寫入資料庫
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, phone, gender, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$email, $hashed_password, $name, $role, $phone, $gender, $bio])) {
        echo "<script>alert('註冊成功！請重新登入。'); location.href='../login.php';</script>";
    } else {
        echo "<script>alert('註冊失敗，請重試。'); history.back();</script>";
    }
}

// 處理登入

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 檢查是否為管理員特殊帳密
    // 管理員帳號 admin@tutor.com / 密碼 admin1234
    if ($email === 'admin@tutor.com' && $password === 'admin1234') {
        $_SESSION['user_id'] = 999; // 假定一個管理員專用 ID
        $_SESSION['user_name'] = '超級管理員';
        $_SESSION['user_role'] = 'admin';
        echo "<script>alert('管理員登入成功！'); location.href='../index.php';</script>";
        exit;
    }

    // 學生或老師登入
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // 登入成功，將資訊寫入 Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        echo "<script>alert('登入成功！'); location.href='../index.php';</script>";
    } else {
        echo "<script>alert('帳號或密碼錯誤！'); history.back();</script>";
    }
}
?>