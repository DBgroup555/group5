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
    .back{display:block; text-align:center; margin-top:15px; color:var(--primary); text-decoration:none; font-size:14px}
  </style>
</head>
<body>

  <div class="box">
    <h2>忘記密碼</h2>
    <p style="font-size:14px; color:#8b6f58;">請輸入您註冊時使用的 Email，系統將會發送一封密碼重設連結給您。</p>
    
    <form action="api/auth_process.php?action=forgot" method="POST" id="forgotForm">
      <label>Email 信箱</label>
      <input type="email" name="email" required placeholder="example@gmail.com">
      <button type="submit" id="submitBtn">發送重設連結</button>
    </form>
    
    <a href="login.php" class="back">返回登入頁面</a>
  </div>

  <script>
    document.getElementById('forgotForm').onsubmit = function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerText = '信件發送中，請稍候...';
    };
  </script>
</body>
</html>