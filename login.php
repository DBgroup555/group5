<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>帳號登入</title>
  <style>
    :root{--bg:#f6efe7;--card:#fffaf4;--line:#e5d7c8;--text:#5b4636;--primary:#c9a27e}
    *{box-sizing:border-box}
    body{margin:0; font-family:system-ui,-apple-system,sans-serif; background:var(--bg); display:flex; justify-content:center; align-items:center; min-height:100vh}
    .auth-card{background:var(--card); border:1px solid var(--line); border-radius:20px; padding:30px; width:100%; max-width:400px; box-shadow:0 10px 30px rgba(90,60,30,.06)}
    h2{margin:0 0 20px; text-align:center; color:var(--text)}
    .field{display:grid; gap:6px; margin-bottom:16px; font-size:14px; color:var(--text)}
    input{width:100%; padding:11px 12px; border:1px solid var(--line); border-radius:12px; background:#fff}
    button{width:100%; background:var(--primary); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:bold; cursor:pointer; font-size:16px; margin-top:10px}
    .switch-link{text-align:center; margin-top:16px; font-size:14px; color:#8b6f58}
    .switch-link a{color:var(--primary); text-decoration:none; font-weight:bold}
    .admin-hint {background:#f1e5d8; padding:10px; border-radius:10px; font-size:12px; color:#8b6f58; margin-top:15px; line-height:1.4;}
  </style>
</head>
<body>

  <div class="auth-card">
    <h2>登入</h2>
    <form action="api/auth_process.php?action=login" method="POST">
      
      <div class="field">
        <label>電子信箱</label>
        <input type="email" name="email" required placeholder="請輸入註冊的電子信箱">
      </div>

      <div class="field">
        <label>密碼</label>
        <input type="password" name="password" required placeholder="請輸入密碼">
      </div>

      <button type="submit">登入</button>

      <!-- <div class="admin-hint">
        🔒 <strong>管理員測試入口：</strong><br>
        帳號：<code>admin@tutor.com</code><br>
        密碼：<code>admin1234</code>
      </div> -->
      
      <div class="switch-link">
        還沒有帳號嗎？ <a href="register.php">立即註冊</a>
      </div>
    </form>
  </div>

</body>
</html>