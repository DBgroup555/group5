<?php
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

// 處理註冊
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_captcha = isset($_POST['captcha']) ? strtolower(trim($_POST['captcha'])) : '';
    $correct_captcha = $_SESSION['captcha_auth'] ?? '';

    if (empty($user_captcha) || $user_captcha !== $correct_captcha) {
        unset($_SESSION['captcha_auth']);
        echo json_encode(['status' => 'error', 'message' => '驗證碼輸入錯誤...']);
        exit;
    }
    unset($_SESSION['captcha_auth']); 

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $phone = $_POST['phone'] ?? '';
    $gender = $_POST['gender'] ?? 'Other';
    $bio = $_POST['bio'] ?? ''; 

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "<script>alert('該 Email 已被註冊！'); history.back();</script>";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role, phone, gender, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$email, $hashed_password, $name, $role, $phone, $gender, $bio])) {
        
        $new_user_id = $pdo->lastInsertId();

        if ($role === 'tutor') {
            $default_title = "大家好，我是 " . $name;
            $default_subject = "其他";
            $default_region = "台北";
            $default_budget = "NT$600+/hr";
            $default_content = !empty($bio) ? $bio : "這位老師很懶，還沒有填寫詳細教學風格。";

            $post_stmt = $pdo->prepare("INSERT INTO posts (user_id, title, subject, region, budget, content) VALUES (?, ?, ?, ?, ?, ?)");
            $post_stmt->execute([$new_user_id, $default_title, $default_subject, $default_region, $default_budget, $default_content]);
        }

        try {
            require_once '../config/mail.php';
            
            $mailSubject = "歡迎加入家教媒合平台！";
            $roleText = ($role === 'tutor') ? '家教老師 (Tutor)' : '學生 / 家長 (Student)';
            
            $mailBody = "
            <div style='background: #f6efe7; padding: 30px; font-family: system-ui, -apple-system, sans-serif; color: #5b4636; line-height: 1.6;'>
                <div style='background: #fffaf4; max-width: 500px; margin: 0 auto; padding: 28px; border-radius: 20px; border: 1px solid #e5d7c8; box-shadow: 0 10px 30px rgba(90,60,30,0.04);'>
                    <h2 style='color: #c9a27e; margin-top: 0; font-size: 22px; border-bottom: 2px solid #f1e5d8; padding-bottom: 12px;'>🎉 恭喜您註冊成功！</h2>
                    <p>親愛的 <strong>{$name}</strong> 您好：</p>
                    <p>感謝您加入家教媒合平台！您的帳號已經成功建立並正式啟用。</p>
                    
                    <div style='background: #f1e5d8; padding: 14px 18px; border-radius: 12px; margin: 20px 0; font-size: 14px; border-left: 4px solid #c9a27e;'>
                        <strong style='color: #5b4636;'>登入帳號：</strong> {$email}<br>
                        <strong style='color: #5b4636;'>註冊身分：</strong> {$roleText}
                    </div>
                    
                    <p>現在您可以立刻登入平台完善您的個人檔案、上傳頭像，並開始體驗平台功能囉！</p>
                    <hr style='border: 0; border-top: 1px dashed #e5d7c8; margin: 24px 0;'>
                    <p style='font-size: 12px; color: #8b6f58; text-align: center; margin: 0;'>💡 本信件為系統自動發送，請勿直接回覆。</p>
                </div>
            </div>
            ";
            
            sendWelcomeEmail($email, $name, $mailSubject, $mailBody);
            
        } catch (\Exception $mailEx) {
            error_log("註冊發信失敗: " . $mailEx->getMessage());
        }

        echo "<script>alert('註冊成功！'); location.href='../login.php';</script>";
    } else {
        echo "<script>alert('註冊失敗，請重試。'); history.back();</script>";
    }
}

// 處理登入
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email === 'admin@tutor.com' && $password === 'admin1234') {
        $_SESSION['user_id'] = 999; 
        $_SESSION['user_name'] = '管理員';
        $_SESSION['user_role'] = 'admin';
        
        echo "<script>alert('管理員登入成功！'); location.href='../admin.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role']; 
        $_SESSION['user_avatar'] = $user['avatar_url'];
        
        if ($_SESSION['user_role'] === 'admin') {
            echo "<script>location.href='../admin.php';</script>";
        } elseif ($_SESSION['user_role'] === 'tutor') {
            echo "<script>location.href='../teacher.php';</script>";
        } else {
            echo "<script>location.href='../student.php';</script>";
        }
        exit;
    } else {
        echo "<script>alert('帳號或密碼錯誤！'); history.back();</script>";
    }
}

// 忘記密碼
if ($action === 'forgot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo "<script>alert('請輸入 Email！'); history.back();</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<script>alert('如果該帳號存在，重設連結已發送至您的信箱！'); location.href='../login.php';</script>";
        exit;
    }

    $token = bin2hex(random_bytes(32)); // 產生隨機 64 字元安全的 Token
    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
    $update->execute([$token, $expires, $user['id']]);

    try {
        require_once '../config/mail.php';
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $currentDir = dirname($protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $resetLink = str_replace('/api', '/reset_password.php?token=' . $token, $currentDir);

        $mailSubject = "🔑 密碼重設";
        $mailBody = "
        <div style='background: #f6efe7; padding: 30px; font-family: system-ui, sans-serif; color: #5b4636; line-height: 1.6;'>
            <div style='background: #fffaf4; max-width: 500px; margin: 0 auto; padding: 28px; border-radius: 20px; border: 1px solid #e5d7c8; box-shadow: 0 10px 30px rgba(90,60,30,0.04);'>
                <h2 style='color: #c9a27e; margin-top: 0; font-size: 20px; border-bottom: 2px solid #f1e5d8; padding-bottom: 12px;'>🔑 密碼重設申請</h2>
                <p>親愛的 <strong>{$user['name']}</strong> 您好：</p>
                <p>平台收到了您重設密碼的申請。請點擊下方的按鈕進入重設密碼頁面：</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' style='background: #c9a27e; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 12px; font-weight: bold; display: inline-block; box-shadow: 0 4px 10px rgba(201,162,126,0.2);'>重設密碼</a>
                </div>
                
                <p style='font-size: 13px; color: #8b6f58;'>⚠️ 注意：此連結會在 <strong>30 分鐘後</strong> 失效。如果您沒有申請重設密碼，請忽略此信件，您的原密碼不會受到任何變更。</p>
                <hr style='border: 0; border-top: 1px dashed #e5d7c8; margin: 24px 0;'>
                <p style='font-size: 12px; color: #8b6f58; text-align: center; margin: 0;'>💡 本信件為系統自動發送，請勿直接回覆。</p>
            </div>
        </div>
        ";

        sendWelcomeEmail($email, $user['name'], $mailSubject, $mailBody);

    } catch (Exception $mailEx) {
        error_log("重設密碼發信失敗: " . $mailEx->getMessage());
    }

    echo "<script>alert('重設密碼連結已發送，請至您的信箱收取！'); location.href='../login.php';</script>";
    exit;
}

// 重設密碼
if ($action === 'reset_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';

    if (empty($token) || empty($new_password)) {
        echo "<script>alert('欄位不可留空！'); history.back();</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW() AND status = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<script>alert('重設連結已失效或過期，請重新申請！'); location.href='../forgot_password.php';</script>";
        exit;
    }

    $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    
    if ($update->execute([$new_hashed, $user['id']])) {
        echo "<script>alert('密碼重設成功！請使用新密碼登入。'); location.href='../login.php';</script>";
    } else {
        echo "<script>alert('密碼重設失敗，請重試。'); history.back();</script>";
    }
    exit;
}
?>