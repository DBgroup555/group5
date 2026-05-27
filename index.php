<?php 
require_once 'config/db.php'; 

// 測試用帳密
// if (!isset($_SESSION['user_id'])) {
//     $_SESSION['user_id'] = 1;
//     $_SESSION['user_name'] = '預設使用者';
//     $_SESSION['user_role'] = 'student'; 
// }

// 如果檢查到沒有 user_id ，回登入頁面
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit; // 停止執行後續的 index.php 程式碼
}

$subject = $_GET['subject'] ?? '全部';
$region = $_GET['region'] ?? '全部';

$sql = "SELECT posts.*, users.name, users.gender FROM posts JOIN users ON posts.user_id = users.id WHERE 1=1";
$params = [];

if ($subject !== '全部') { $sql .= " AND posts.subject = ?"; $params[] = $subject; }
if ($region !== '全部') { $sql .= " AND posts.region = ?"; $params[] = $region; }
$sql .= " ORDER BY posts.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>家教媒合平台</title>
  <style>
    :root{--bg:#f6efe7;--card:#fffaf4;--line:#e5d7c8;--text:#5b4636;--muted:#8b6f58;--primary:#c9a27e;--soft:#f1e5d8}
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,"Noto Sans TC",sans-serif;background:linear-gradient(180deg,#fbf6f1,var(--bg));color:var(--text)}
    button,input,select,textarea{font:inherit}
    .topbar{display:flex;gap:16px;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid var(--line);background:rgba(255,250,244,.85);backdrop-filter:blur(8px);position:sticky;top:0;z-index:10}
    .brand{font-weight:800;letter-spacing:1px}
    .searchbar{display:flex;gap:8px;flex:1;max-width:560px}
    .searchbar input,.field input,.field select,.field textarea{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:12px;background:#fff}
    .layout{display:grid;grid-template-columns:260px 1fr 240px;gap:18px;padding:18px;align-items:start}
    .card{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:0 10px 30px rgba(90,60,30,.06)}
    .filters,.side{padding:16px;display:grid;gap:12px;position:sticky;top:88px}
    .filters label,.field{display:grid;gap:6px;font-size:14px}
    .content{display:grid;gap:18px}
    .hero{padding:24px;display:flex;justify-content:space-between;gap:20px;align-items:center}
    .hero h1{margin:0 0 8px;font-size:30px}
    .hero p,.desc,.grid-title span,.meta{color:var(--muted)}
    .hero-actions,.top-actions,.row{display:flex;gap:10px;flex-wrap:wrap}
    .grid-title{display:flex;justify-content:space-between;align-items:end}
    .cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .tutor-card{padding:16px;display:grid;gap:10px}
    .avatar{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#d9b99b,#f2ddd0);cursor:pointer}
    .meta{display:flex;justify-content:space-between}
    button{border:1px solid var(--line);background:#fff;padding:10px 14px;border-radius:12px;cursor:pointer;color:var(--text)}
    .primary{background:var(--primary);color:#fff;border-color:transparent}
    .ghost{background:var(--soft)}
    .panel{padding:16px}
    .post{display:grid;gap:12px}
    .chip{display:inline-flex;align-items:center;justify-content:center;padding:4px 10px;border-radius:999px;background:var(--soft);font-size:12px}

    .modal {display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:100; justify-content:center; align-items:center;}
    .modal-content {background:var(--card); padding:24px; border-radius:20px; width:90%; max-width:500px; border:1px solid var(--line); position:relative;}
    .close-btn {position:absolute; top:16px; right:16px; cursor:pointer; font-size:20px; font-weight:bold;}
    .chat-box {height:200px; overflow-y:auto; border:1px solid var(--line); background:#fff; padding:10px; border-radius:12px; margin-bottom:10px;}
  </style>
</head>
<body>

  <header class="topbar">
    <div class="brand" onclick="location.href='index.php'" style="cursor:pointer;">家教媒合平台</div>
    <form class="searchbar" method="GET" action="index.php">
      <input name="keyword" placeholder="搜尋科目、地區、老師" value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>" />
      <button type="submit" class="primary">搜尋</button>
    </form>
    <div class="top-actions">
      <span>你好，<?php echo $_SESSION['user_name']; ?> (<?php echo $_SESSION['user_role']; ?>)</span>
      <div class="avatar" onclick="openModal('profileModal')" title="修改基本資料/履歷"></div>
    </div>
  </header>

  <main class="layout">
    <form class="filters card" method="GET" action="index.php">
      <h2>多條件搜尋</h2>
      <label>科目
        <select name="subject">
          <option <?php if($subject=='全部') echo 'selected'; ?>>全部</option>
          <option <?php if($subject=='數學') echo 'selected'; ?>>數學</option>
          <option <?php if($subject=='英文') echo 'selected'; ?>>英文</option>
          <option <?php if($subject=='國文') echo 'selected'; ?>>國文</option>
        </select>
      </label>
      <label>地區
        <select name="region">
          <option <?php if($region=='全部') echo 'selected'; ?>>全部</option>
          <option <?php if($region=='台北') echo 'selected'; ?>>台北</option>
          <option <?php if($region=='台中') echo 'selected'; ?>>台中</option>
          <option <?php if($region=='高雄') echo 'selected'; ?>>高雄</option>
        </select>
      </label>
      <button type="submit" class="primary">套用篩選</button>
    </form>

    <section class="content">
      <section class="hero card">
        <div>
          <h1>找到合適的家教，或發布你的需求</h1>
          <p>現正切換至手繪範本流程：支援多條件篩選與即時串接。</p>
        </div>
      </section>

      <div class="grid-title">
        <h2>貼文列表</h2>
        <span>共 <?php echo count($posts); ?> 筆資料</span>
      </div>

      <section class="cards">
        <?php if(empty($posts)): ?>
          <p>目前沒有符合條件的案件貼文。</p>
        <?php else: ?>
          <?php foreach($posts as $post): ?>
            <article class="tutor-card card">
              <div class="avatar"></div>
              <h3><?php echo htmlspecialchars($post['name']); ?> 老師</h3>
              <p><?php echo htmlspecialchars($post['subject']); ?> · <?php echo htmlspecialchars($post['region']); ?> · <?php echo $post['gender']=='M'?'男':'女'; ?></p>
              <p class="desc"><?php echo htmlspecialchars($post['title']); ?></p>
              <div class="meta"><span>⭐ 4.8</span><span><?php echo htmlspecialchars($post['budget']); ?></span></div>
              <button class="primary" onclick="openDetail(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>', '<?php echo htmlspecialchars($post['content']); ?>', <?php echo $post['user_id']; ?>)">查看詳情</button>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <section class="card panel">
        <h2>發布家教需求/履歷貼文</h2>
        <form class="post" style="margin-top:12px;" method="POST" action="api/post_process.php">
          <div class="row">
            <div class="field" style="flex:1;min-width:180px;"><label>需求標題</label><input name="title" required placeholder="例如：尋找國三英文會考衝刺" /></div>
            <div class="field" style="flex:1;min-width:180px;"><label>科目</label><input name="subject" required placeholder="例如：英文" /></div>
          </div>
          <div class="row">
            <div class="field" style="flex:1;min-width:180px;"><label>地區</label><input name="region" required placeholder="例如：台北" /></div>
            <div class="field" style="flex:1;min-width:180px;"><label>時薪預算</label><input name="budget" required placeholder="例如：NT$800/hr" /></div>
          </div>
          <div class="field"><label>詳細內容描述</label><textarea name="content" rows="4" required placeholder="請詳細列出上課時間、期望條件與目前程度..."></textarea></div>
          <div class="row">
            <button type="submit" class="primary">送出需求貼文</button>
          </div>
        </form>
      </section>
    </section>

    <aside class="side card">
  <h2>功能入口</h2>
  
  <button onclick="openModal('profileModal')">帳號管理</button>
  <button onclick="openChat(2, '客服與回饋系統')">文字回饋聊天室</button>
  
  <?php if ($_SESSION['user_role'] === 'student'): ?>
    <button style="border-left: 4px solid #c9a27e;" onclick="document.querySelector('.post').scrollIntoView({behavior: 'smooth'});">➕ 發布新需求</button>
    <button onclick="alert('導向我刊登的案件管理頁面')">案件管理</button>
    <button onclick="alert('導向我收到的老師應徵清單')">應徵清單</button>
  <?php endif; ?>

  <?php if ($_SESSION['user_role'] === 'tutor'): ?>
    <button style="border-left: 4px solid #c9a27e;" onclick="alert('導向全站案件列表，供老師尋找新案件')">🔍 瀏覽最新案件</button>
    <button onclick="alert('導向我主動應徵的進度列表')">我的應徵清單</button>
    <button onclick="alert('導向學生給我的公開評價頁面')">我的評分與回饋</button>
  <?php endif; ?>

  <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line);"></div>
    <button style="background: #d9534f; color: #fff; border-color: transparent;" onclick="alert('進入後台：審查全站老師的身份證、畢業證書與良民證')">⚙️ 老師資格審查</button>
    <button style="background: #d9534f; color: #fff; border-color: transparent;" onclick="alert('進入後台：處理檢舉黑名單、管理全站貼文')">⚙️ 檢舉與貼文管理</button>
  <?php endif; ?>

  <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line);"></div>
  <button style="background: #f1e5d8; border-color: transparent; text-align: center;" onclick="alert('已登出！'); location.href='login.php';">安全登出</button>
  
  <div class="footer-space"></div>
</aside>
  </main>

  <div id="profileModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('profileModal')">&times;</span>
      <h2>基本資料與履歷維護</h2>
      <form style="display:grid; gap:12px; margin-top:12px;">
        <div class="field"><label>姓名/暱稱</label><input type="text" value="<?php echo $_SESSION['user_name']; ?>" /></div>
        <div class="field"><label>自我介紹/履歷簡介</label><textarea rows="3">專職教學，豐富經驗。</textarea></div>
        <button type="button" class="primary" onclick="alert('履歷基本資料已更新！'); closeModal('profileModal');">確認變更</button>
      </form>
    </div>
  </div>

  <div id="detailModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('detailModal')">&times;</span>
      <h2 id="detailTitle">案件標題</h2>
      <p id="detailContent" style="margin:14px 0; color:var(--muted); line-height:1.5;">詳細內容載入中...</p>
      <hr style="border:0; border-top:1px solid var(--line); margin-bottom:12px;">
      <button class="primary" id="contactBtn">聯絡聊聊（文字回饋）</button>
    </div>
  </div>

  <div id="chatModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('chatModal')">&times;</span>
      <h2 id="chatTargetName">正在與...聊天</h2>
      <div class="chat-box" id="chatBox">
        <div style="color:var(--muted); font-size:13px;">[系統提示] 您已進入文字安全對話聊天室</div>
      </div>
      <div style="display:flex; gap:8px;">
        <input type="hidden" id="receiverId" />
        <input type="text" id="msgInput" style="flex:1; padding:10px; border-radius:10px; border:1px solid var(--line);" placeholder="請輸入訊息內容..." />
        <button class="primary" onclick="sendMessage()">發送</button>
      </div>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
     Cambiar
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    // 開啟貼文詳情彈窗
    function openDetail(id, title, content, userId) {
        document.getElementById('detailTitle').innerText = title;
        document.getElementById('detailContent').innerText = content;
        document.getElementById('contactBtn').onclick = function() {
            closeModal('detailModal');
            openChat(userId, '該案件刊登者');
        };
        openModal('detailModal');
    }

    // 開啟聊天室
    function openChat(receiverId, name) {
        document.getElementById('chatTargetName').innerText = '與 ' + name + ' 對話中';
        document.getElementById('receiverId').value = receiverId;
        document.getElementById('chatBox').innerHTML = '<div style="color:var(--muted); font-size:13px;">[系統提示] 歷史文字紀錄載入成功...</div>';
        openModal('chatModal');
    }

    // 發送聊天內容與文字回饋
    function sendMessage() {
        const receiverId = document.getElementById('receiverId').value;
        const msg = document.getElementById('msgInput').value;
        if(!msg.trim()) return;

        const formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', msg);

        fetch('api/send_message.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const box = document.getElementById('chatBox');
                box.innerHTML += `<div style="margin: 6px 0; text-align:right;"><strong>你:</strong> ${msg}</div>`;
                document.getElementById('msgInput').value = '';
                box.scrollTop = box.scrollHeight;
            }
        });
    }
  </script>
</body>
</html>