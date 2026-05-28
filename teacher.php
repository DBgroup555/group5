<?php 
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'tutor') {
    header("Location: login.php");
    exit;
}

// 接收搜尋與篩選條件
$keyword = $_GET['keyword'] ?? '';
$subject = $_GET['subject'] ?? '全部';
$region = $_GET['region'] ?? '全部';

$sql = "SELECT posts.*, users.name, users.gender, users.avatar_url 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE users.role = 'student' AND users.status = 1";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (posts.title LIKE ? OR posts.content LIKE ?)";
    $params[] = "%$keyword%"; $params[] = "%$keyword%";
}
if ($subject !== '全部') { $sql .= " AND posts.subject = ?"; $params[] = $subject; }
if ($region !== '全部') { $sql .= " AND posts.region = ?"; $params[] = $region; }

$sql .= " ORDER BY posts.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// 撈取目前老師本人的履歷簡介資料
$profile_stmt = $pdo->prepare("SELECT name, bio FROM users WHERE id = ?");
$profile_stmt->execute([$_SESSION['user_id']]);
$my_profile = $profile_stmt->fetch();

$my_apps_stmt = $pdo->prepare("
    SELECT 
        app.id AS app_id, app.status, app.created_at,
        p.title AS post_title, p.budget, p.subject, p.region,
        u.id AS student_id, u.name AS student_name
    FROM applications app
    JOIN posts p ON app.post_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE app.tutor_id = ?
    ORDER BY app.id DESC
");
$my_apps_stmt->execute([$_SESSION['user_id']]);
$my_applications = $my_apps_stmt->fetchAll();
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
    .brand{font-weight:800;letter-spacing:1px;cursor:pointer;}
    .searchbar{display:flex;gap:8px;flex:1;max-width:560px}
    .searchbar input,.field input,.field select,.field textarea{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:12px;background:#fff}
    .layout{display:grid;grid-template-columns:260px 1fr 240px;gap:18px;padding:18px;align-items:start}
    .card{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:0 10px 30px rgba(90,60,30,.06)}
    .filters,.side{padding:16px;display:grid;gap:12px;position:sticky;top:88px}
    .filters label,.field{display:grid;gap:6px;font-size:14px}
    .content{display:grid;gap:18px}
    .desc,.grid-title span,.meta{color:var(--muted)}
    .top-actions,.row{display:flex;gap:10px;flex-wrap:wrap}
    .grid-title{display:flex;justify-content:space-between;align-items:end}
    .cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .tutor-card{padding:16px;display:grid;gap:10px}
    .avatar{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#9bc1d9,#d0e3f2);cursor:pointer} /* 老師看學生用粉藍色調區分 */
    .meta{display:flex;justify-content:space-between}
    button{border:1px solid var(--line);background:#fff;padding:10px 14px;border-radius:12px;cursor:pointer;color:var(--text)}
    .primary{background:var(--primary);color:#fff;border-color:transparent}
    .ghost{background:var(--soft)}
    .panel{padding:16px}
    
    .modal {display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:100; justify-content:center; align-items:center;}
    .modal-content {background:var(--card); padding:24px; border-radius:20px; width:90%; max-width:550px; border:1px solid var(--line); position:relative;}
    .close-btn {position:absolute; top:16px; right:16px; cursor:pointer; font-size:20px; font-weight:bold;}
    .chat-box {height:200px; overflow-y:auto; border:1px solid var(--line); background:#fff; padding:10px; border-radius:12px; margin-bottom:10px;}
    
    @media (max-width: 1100px){.layout{grid-template-columns:1fr}.filters,.side{position:static}.cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width: 700px){.topbar{flex-direction:column;align-items:stretch}.cards{grid-template-columns:1fr}.searchbar{max-width:none}}
  </style>
</head>
<body>

  <header class="topbar">
    <div class="brand" onclick="location.href='teacher.php'">家教媒合平台</div>
    
    <form class="searchbar" method="GET" action="teacher.php">
      <input name="keyword" placeholder="搜尋關鍵字..." value="<?php echo htmlspecialchars($keyword); ?>" />
      <button type="submit" class="primary">搜尋</button>
    </form>
    
    <div class="top-actions" style="align-items: center;">
      <span id="topNavWelcome">你好，<?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
      <div class="avatar" onclick="openModal('profileModal')" title="帳號設定"></div>
    </div>
  </header>

  <main class="layout">
    <form class="filters card" method="GET" action="teacher.php">
      <h2>多條件搜尋</h2>
      <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
      <label>科目
        <select name="subject">
          <option <?php if($subject=='全部') echo 'selected'; ?>>全部</option>
          <option <?php if($subject=='國文') echo 'selected'; ?>>國文</option>
          <option <?php if($subject=='數學') echo 'selected'; ?>>數學</option>
          <option <?php if($subject=='英文') echo 'selected'; ?>>英文</option>
          <option <?php if($subject=='自然') echo 'selected'; ?>>自然</option>
          <option <?php if($subject=='社會') echo 'selected'; ?>>社會</option>
          <option <?php if($subject=='其他') echo 'selected'; ?>>其他</option>
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
      <div class="grid-title">
        <h2>學生列表</h2>
        <span>共 <?php echo count($posts); ?> 筆符合條件</span>
      </div>

      <section class="cards">
        <?php if(empty($posts)): ?>
          <p style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--muted);">目前沒有符合條件的貼文。</p>
        <?php else: ?>
          <?php foreach($posts as $post): ?>
            <article class="tutor-card card">
              <div class="row" style="align-items: center;">
                <div class="avatar"></div>
                <div>
                  <h3 style="margin:0; font-size:16px;"><?php echo htmlspecialchars($post['name']); ?></h3>
                  <p style="font-size: 13px; color: var(--primary); margin-top:2px;">
                    尋找：<?php echo htmlspecialchars($post['subject']); ?> · <?php echo htmlspecialchars($post['region']); ?>
                  </p>
                </div>
              </div>
              <p class="desc" style="font-size:14px; font-weight:bold; margin-top:6px;"><?php echo htmlspecialchars($post['title']); ?></p>
              <div class="meta" style="margin-top: auto; padding-top: 8px;">
                <span>⏳ 招募中</span>
                <strong style="color: var(--text);"><?php echo htmlspecialchars($post['budget']); ?></strong>
              </div>
              <button class="primary" style="margin-top: 4px;" onclick="openDetail(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['name'] . ' - ' . $post['title']); ?>', '<?php echo htmlspecialchars($post['content']); ?>', <?php echo $post['user_id']; ?>)">查看詳情</button>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </section>

    <aside class="side card">
      <button style="background: var(--primary); color: #fff; border-color: transparent; font-weight: bold;" onclick="openModal('profileModal')">📝 編輯履歷</button>
      <button onclick="openModal('myAppsStatusModal')">應徵清單</button>
      <button onclick="alert('功能導向：家長/學生給我的公開星等與評價內容')">我的回饋</button>
      <button onclick="openChat(2, '客服與回饋中心')">聊天室</button>
    </aside>
  </main>

  <div id="profileModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('profileModal')">&times;</span>
      <h2>帳號管理</h2>
      <div style="margin-top:16px; display:grid; gap:12px;">
        <div class="field">
          <label>使用者姓名</label>
          <input type="text" id="editTutorName" value="<?php echo htmlspecialchars($my_profile['name']); ?>" placeholder="請輸入新姓名" />
        </div>
        <div class="field"><label>身分</label><input type="text" disabled value="家教老師 (Tutor)" /></div>
        
        <button type="button" class="primary" onclick="updateTutorProfile()">儲存</button>
        
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line);"></div>
        <button type="button" style="background: #d9534f; color: #fff; border-color: transparent; font-weight: bold;" onclick="alert('已登出'); location.href='login.php';">登出</button>
      </div>
    </div>
  </div>

  <div id="detailModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('detailModal')">&times;</span>
      <h2 id="detailTitle">貼文詳情</h2>
      <p id="detailContent" style="margin:18px 0; color:var(--text); line-height:1.6; background:#fff; padding:15px; border-radius:12px; border:1px solid var(--line);">內文加載中...</p>
      <hr style="border:0; border-top:1px solid var(--line); margin-bottom:16px;">
      
      <div style="display:flex; gap:10px;">
        <button class="primary" id="applyBtn" style="flex:1; padding: 12px; font-weight:bold; background:#28a745;">我要應徵</button>
        <button class="ghost" id="contactBtn" style="padding: 12px;">💬 聊聊</button>
      </div>
    </div>
  </div>

  <div id="myAppsStatusModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close-btn" onclick="closeModal('myAppsStatusModal')">&times;</span>
        <h2>應徵清單</h2>
        <p class="desc" style="margin-bottom:12px;">以下是您應徵的名單：</p>
        
        <div style="max-height: 400px; overflow-y: auto; padding-right:5px;">
        <?php if(empty($my_applications)): ?>
            <p style="text-align:center; padding:20px; color:var(--muted);">您目前尚未應徵過任何家教案件。</p>
        <?php else: ?>
            <?php foreach($my_applications as $ma): ?>
            <div class="manage-post-item" style="flex-direction: column; align-items: stretch; background:#fff; padding:14px; border:1px solid var(--line); border-radius:12px; margin-bottom:10px;">
                
                <div style="display: flex; justify-content: space-between; align-items: start; border-bottom: 1px solid var(--line); padding-bottom: 8px;">
                <div>
                    <span class="chip" style="background:var(--soft); color:var(--text); margin-bottom:4px; font-weight:bold;">
                    <?php echo htmlspecialchars($ma['subject']); ?> · <?php echo htmlspecialchars($ma['region']); ?>
                    </span>
                    <strong style="color:var(--text); display:block; font-size:15px; margin-top:2px;"><?php echo htmlspecialchars($ma['post_title']); ?></strong>
                </div>
                
                <div>
                    <?php if($ma['status'] == 'pending'): ?>
                    <span class="chip" style="background: #fcf8e3; color: #8a6d3b; border: 1px solid #faebcc;">學生考慮中...</span>
                    <?php elseif($ma['status'] == 'accepted'): ?>
                    <span class="chip" style="background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; font-weight:bold;">✅ 恭喜錄用！</span>
                    <?php else: ?>
                    <span class="chip" style="background: #f2dede; color: #a94442; border: 1px solid #ebccd1;">❌ 未錄取</span>
                    <?php endif; ?>
                </div>
                </div>
                
                <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 13px; color: var(--muted);">
                    學生姓名：<strong style="color:var(--text);"><?php echo htmlspecialchars($ma['student_name']); ?></strong><br>
                    時薪：<span style="color:var(--primary); font-weight:bold;"><?php echo htmlspecialchars($ma['budget']); ?></span>
                </div>
                
                <button style="padding: 6px 12px; font-size: 12px; display: flex; align-items: center; gap: 4px;" 
                        onclick="closeModal('myAppsStatusModal'); openChat(<?php echo $ma['student_id']; ?>, '<?php echo htmlspecialchars($ma['student_name']); ?>')">
                    💬 聊聊
                </button>
                </div>

            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
    </div>

  <div id="chatModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('chatModal')">&times;</span>
      <h2 id="chatTargetName">正在進行對話</h2>
      <div class="chat-box" id="chatBox">
        <div style="color:var(--muted); font-size:13px;">[系統提示] 安全文字對話通道已建立。</div>
      </div>
      <div style="display:flex; gap:8px;">
        <input type="hidden" id="receiverId" />
        <input type="text" id="msgInput" style="flex:1; padding:10px; border-radius:10px; border:1px solid var(--line);" placeholder="請輸入訊息..." />
        <button class="primary" onclick="sendMessage()">發送</button>
      </div>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function updateTutorProfile() {
        const nameInput = document.getElementById('editTutorName').value;
        const bioInput = document.getElementById('editTutorBio').value;
        if(!nameInput.trim()) { alert('姓名不可為空！'); return; }

        const formData = new FormData();
        formData.append('name', nameInput);
        formData.append('bio', bioInput); 

        fetch('api/update_tutor_profile.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('已成功更新！');
                document.getElementById('topNavWelcome').innerText = '你好，' + data.name;
                closeModal('profileModal');
            } else {
                alert('修改失敗，請稍後再試。');
            }
        });
    }

    // 開啟案件需求詳細內容
    function openDetail(postId, title, content, studentId) {
        document.getElementById('detailTitle').innerText = title;
        document.getElementById('detailContent').innerText = content;
        
        document.getElementById('applyBtn').onclick = function() {
            if(!confirm('確定要應徵嗎？')) return;
            
            const formData = new FormData();
            formData.append('post_id', postId);

            fetch('api/submit_application.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('履歷投遞成功！請耐心靜候學生的聯絡錄用。');
                    closeModal('detailModal');
                } else if(data.status === 'exists') {
                    alert('您先前已經應徵過這個案件囉，請勿重複投遞。');
                } else {
                    alert('應徵失敗，請稍後再試。');
                }
            });
        };

        document.getElementById('contactBtn').onclick = function() {
            closeModal('detailModal'); 
            openChat(studentId, title.split(' 的需求')[0]);
        };
        openModal('detailModal');
    }

    // 聊天室邏輯
    function openChat(receiverId, name) {
        document.getElementById('chatTargetName').innerText = '與 ' + name + ' 對話中';
        document.getElementById('receiverId').value = receiverId;
        document.getElementById('chatBox').innerHTML = '<div style="color:var(--muted); font-size:13px;">[系統提示] 歷史文字紀錄載入成功...</div>';
        openModal('chatModal');
    }

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
                box.innerHTML += `<div style="margin: 8px 0; text-align:right;"><strong>你:</strong> ${msg}</div>`;
                document.getElementById('msgInput').value = ''; box.scrollTop = box.scrollHeight;
            }
        });
    }
  </script>
</body>
</html>