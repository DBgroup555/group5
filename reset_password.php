<?php
require_once 'config/db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "<script>alert('不合法的請求！'); location.href='login.php';</script>";
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW() AND status = 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    echo "<script>alert('此連結已失效或已過期，請重新申請！'); location.href='forgot_password.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>家教媒合平台</title>
  <style>
    :root{--bg:#f6efe7;--card:#fffaf4;--line:#e5d7c8;--text:#5b4636;--primary:#c9a27e}
    body{font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); display:flex; justify-content:center; align-items:center; height:100vh; margin:0;}
    .box{background:var(--card); padding:30px; border-radius:20px; border:1px solid var(--line); width:90%; max-width:400px; box-shadow:0 10px 30px rgba(90,60,30,.06)}
    h2{margin-top:0; color:var(--text)}
    input{width:100%; padding:11px; margin:10px 0 20px 0; border:1px solid var(--line); border-radius:12px; box-sizing:border-box}
    button{width:100%; padding:12px; background:var(--primary); color:#fff; border:none; border-radius:12px; font-weight:bold; cursor:pointer}
  </style>
</head>
<body>

  <div class="box">
    <h2>重設密碼</h2>
    
    <form action="api/auth_process.php?action=reset_submit" method="POST" onsubmit="return checkPassword()">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      
      <label>輸入新密碼</label>
      <input type="password" id="pwd" name="password" required placeholder="請輸入新密碼" minlength="6">
      
      <label>再次確認新密碼</label>
      <input type="password" id="pwd_chk" required placeholder="請再次輸入新密碼">
      
      <button type="submit">變更密碼</button>
    </form>
  </div>

  <script>
    function checkPassword() {
        const p1 = document.getElementById('pwd').value;
        const p2 = document.getElementById('pwd_chk').value;
        if (p1 !== p2) {
            alert('兩次輸入的新密碼不一致！');
            return false;
        }
        return true;
    }
  </script>
</body>
</html>