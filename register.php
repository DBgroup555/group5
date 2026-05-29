<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>帳號註冊</title>
  <style>
    :root{--bg:#f6efe7;--card:#fffaf4;--line:#e5d7c8;--text:#5b4636;--primary:#c9a27e;--soft:#f1e5d8}
    *{box-sizing:border-box}
    body{margin:0; font-family:system-ui,-apple-system,sans-serif; background:var(--bg); display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px;}
    .auth-card{background:var(--card); border:1px solid var(--line); border-radius:20px; padding:30px; width:100%; max-width:450px; box-shadow:0 10px 30px rgba(90,60,30,.06)}
    h2{margin:0 0 20px; text-align:center; color:var(--text)}
    .field{display:grid; gap:6px; margin-bottom:16px; font-size:14px; color:var(--text)}
    input, select, textarea{width:100%; padding:11px 12px; border:1px solid var(--line); border-radius:12px; background:#fff; font:inherit}
    button{width:100%; background:var(--primary); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:bold; cursor:pointer; font-size:16px; margin-top:10px}
    button:hover{opacity:0.9}
    .tutor-only{display:none;} 
    .switch-link{text-align:center; margin-top:16px; font-size:14px; color:#8b6f58}
    .switch-link a{color:var(--primary); text-decoration:none; font-weight:bold}
  </style>
</head>
<body>

  <div class="auth-card">
    <h2>註冊</h2>
    <form action="api/auth_process.php?action=register" method="POST">
      
      <div class="field">
        <label>我想成為...</label>
        <select name="role" id="roleSelect" onchange="toggleRoleFields()" required>
          <option value="student">學生 / 家長（尋找老師）</option>
          <option value="tutor">家教老師（尋找學生）</option>
        </select>
      </div>

      <div class="field">
        <label>電子信箱 (登入帳號)</label>
        <input type="email" name="email" required placeholder="example@mail.com">
      </div>

      <div class="field">
        <label>密碼</label>
        <input type="password" name="password" required placeholder="請輸入密碼">
      </div>

      <div class="field">
        <label>姓名</label>
        <input type="text" name="name" required placeholder="請輸入姓名">
      </div>

      <div class="field">
        <label>電話號碼</label>
        <input type="tel" name="phone" placeholder="0912345678">
      </div>

      <div class="field">
        <label>性別</label>
        <select name="gender">
          <option value="M">男</option>
          <option value="F">女</option>
          <option value="Other">其他 / 不公開</option>
        </select>
      </div>

      <div class="field tutor-only" id="tutorFields">
        <label>教學履歷 / 自我介紹</label>
        <textarea name="bio" rows="4" placeholder="請填寫您的學經歷、擅長科目、教學理念等，這會公開在您的師資履歷上。"></textarea>
      </div>

      <div class="field" style="display: grid; gap: 6px; font-size: 14px; margin-bottom: 12px;">
        <label>驗證碼</label>
        
        <div style="display: flex; gap: 10px; align-items: center;">
          <input type="text" name="captcha" id="registerCaptcha" placeholder="請輸入圖中英數" required 
                style="flex: 1; padding: 11px 12px; border: 1px solid var(--line); border-radius: 12px;" />
          
          <img src="api/captcha.php" id="captchaImg" alt="驗證碼" title="點擊更換一張" 
              style="cursor: pointer; border-radius: 8px; border: 1px solid var(--line); height: 40px;" 
              onclick="refreshCaptcha()" />
        </div>
        <small style="color: var(--muted); font-size: 12px;">看不清楚？點擊圖片可更換一張新驗證碼。</small>
      </div>

      <button type="submit">註冊</button>
      
      <div class="switch-link">
        已經有帳號了？ <a href="login.php">立即登入</a>
      </div>
    </form>
  </div>

  <script>
    function refreshCaptcha() {
        document.getElementById('captchaImg').src = 'api/captcha.php?v=' + Date.now();
    }

    function toggleRoleFields() {
        const role = document.getElementById('roleSelect').value;
        const tutorFields = document.getElementById('tutorFields');
        
        if (role === 'tutor') {
            tutorFields.style.display = 'grid'; 
            tutorFields.querySelector('textarea').required = true;
        } else {
            tutorFields.style.display = 'none'; 
            tutorFields.querySelector('textarea').required = false;
        }
    }
  </script>
</body>
</html>