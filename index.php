<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>家教媒合平台</title>
  <style>
    :root {
      --bg: #f6efe7;
      --card: #fffaf4;
      --line: #e5d7c8;
      --text: #5b4636;
      --muted: #8b6f58;
      --primary: #c9a27e;
      --soft: #f1e5d8;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 0;
      font-family: system-ui, -apple-system, "Noto Sans TC", sans-serif;
      background: linear-gradient(135deg, #fbf6f1, var(--bg));
      color: var(--text);
      min-height: 100vh;
      display: flex; flex-direction: column; justify-content: center; align-items: center;
    }
    .welcome-container {
      background: var(--card); border: 1px solid var(--line); border-radius: 28px;
      padding: 50px 40px; width: 90%; max-width: 650px; text-align: center;
      box-shadow: 0 15px 35px rgba(90, 60, 30, 0.08); position: relative; overflow: hidden;
    }
    .welcome-container::before {
      content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px;
      border-radius: 50%; background: var(--soft); opacity: 0.5; z-index: 1;
    }
    .brand-logo { font-size: 18px; font-weight: 800; letter-spacing: 2px; color: var(--primary); margin-bottom: 16px; }
    h1 { font-size: 36px; margin: 0 0 12px 0; font-weight: 800; line-height: 1.3; }
    .subtitle { font-size: 16px; color: var(--muted); margin-bottom: 40px; line-height: 1.6; }
    .cta-actions { display: flex; gap: 16px; justify-content: center; margin-bottom: 40px; }
    .btn { flex: 1; max-width: 180px; padding: 14px 28px; font-size: 16px; font-weight: 700; border-radius: 14px; cursor: pointer; text-decoration: none; transition: all 0.2s ease; text-align: center; }
    .btn-register { background: var(--primary); color: #fff; border: 1px solid transparent; box-shadow: 0 4px 12px rgba(201, 162, 126, 0.3); }
    .btn-register:hover { opacity: 0.9; transform: translateY(-2px); }
    .btn-login { background: #fff; color: var(--text); border: 1px solid var(--line); }
    .btn-login:hover { background: var(--soft); transform: translateY(-2px); }
    .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; border-top: 1px solid var(--line); padding-top: 30px; }
    .feature-item { font-size: 13px; color: var(--muted); }
    .feature-item strong { display: block; color: var(--text); margin-bottom: 4px; font-size: 14px; }
    @media (max-width: 500px) {
      h1 { font-size: 28px; }
      .cta-actions { flex-direction: column; align-items: center; gap: 12px; }
      .btn { width: 100%; max-width: none; }
      .features-grid { grid-template-columns: 1fr; gap: 16px; }
    }
  </style>
</head>
<body>

  <div class="welcome-container">
    <div class="brand-logo">Tutor Match</div>
    <h1>家教媒合平台</h1>
    <p class="subtitle">學你想學，教你想教，找家教就該這麼簡單</p>

    <div class="cta-actions">
      <a href="login.php" class="btn btn-login">登入</a>
      <a href="register.php" class="btn btn-register">註冊</a>
    </div>
  </div>

</body>
</html>