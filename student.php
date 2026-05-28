<?php 
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$keyword = $_GET['keyword'] ?? '';
$subject = $_GET['subject'] ?? '全部';
$region = $_GET['region'] ?? '全部';

$sql = "SELECT posts.*, users.name, users.gender, users.avatar_url 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE users.role = 'tutor' AND users.status = 1";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (posts.title LIKE ? OR posts.content LIKE ?)";
    $params[] = "%$keyword%"; 
    $params[] = "%$keyword%"; 
}

if ($subject !== '全部') { 
    $sql .= " AND posts.subject = ?"; 
    $params[] = $subject; 
}
if ($region !== '全部') { 
    $sql .= " AND posts.region = ?"; 
    $params[] = $region; 
}

$sql .= " ORDER BY posts.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params); 
$posts = $stmt->fetchAll();

$my_posts_stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY id DESC");
$my_posts_stmt->execute([$_SESSION['user_id']]);
$my_posts = $my_posts_stmt->fetchAll();

$apps_stmt = $pdo->prepare("
    SELECT 
        app.id AS app_id, app.status, app.created_at,
        p.title AS post_title,
        u.id AS tutor_id, u.name AS tutor_name, u.gender AS tutor_gender, u.bio AS tutor_bio
    FROM applications app
    JOIN posts p ON app.post_id = p.id
    JOIN users u ON app.tutor_id = u.id
    WHERE p.user_id = ?
    ORDER BY app.id DESC
");
$apps_stmt->execute([$_SESSION['user_id']]);
$applications = $apps_stmt->fetchAll();
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
    .avatar{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#d9b99b,#f2ddd0);cursor:pointer}
    .meta{display:flex;justify-content:space-between}
    button{border:1px solid var(--line);background:#fff;padding:10px 14px;border-radius:12px;cursor:pointer;color:var(--text)}
    .primary{background:var(--primary);color:#fff;border-color:transparent}
    .ghost{background:var(--soft)}
    .panel{padding:16px}
    .post{display:grid;gap:12px}
    
    /* 彈窗 (Modal) 樣式 */
    .modal {display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:100; justify-content:center; align-items:center;}
    .modal-content {background:var(--card); padding:24px; border-radius:20px; width:90%; max-width:550px; border:1px solid var(--line); position:relative;}
    .close-btn {position:absolute; top:16px; right:16px; cursor:pointer; font-size:20px; font-weight:bold;}
    .chat-box {height:200px; overflow-y:auto; border:1px solid var(--line); background:#fff; padding:10px; border-radius:12px; margin-bottom:10px;}
    
    /* 案件管理清單項目樣式 */
    .manage-post-item {background:#fff; border:1px solid var(--line); padding:12px; border-radius:12px; display:flex; justify-content:between; align-items:center; margin-bottom:10px; gap:10px;}

    @media (max-width: 1100px){.layout{grid-template-columns:1fr}.filters,.side{position:static}.cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width: 700px){.topbar{flex-direction:column;align-items:stretch}.cards{grid-template-columns:1fr}.searchbar{max-width:none}}
  </style>
</head>
<body>

  <header class="topbar">
    <div class="brand" onclick="location.href='student.php'">家教媒合平台</div>
    
    <form class="searchbar" method="GET" action="student.php">
      <input name="keyword" placeholder="搜尋關鍵字..." value="<?php echo htmlspecialchars($keyword); ?>" />
      <button type="submit" class="primary">搜尋</button>
    </form>
    
    <div class="top-actions" style="align-items: center;">
      <span id="topNavWelcome">你好，<?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
      <div class="avatar" onclick="openModal('profileModal')" title="帳號設定"></div>
    </div>
  </header>

  <main class="layout">
    <form class="filters card" method="GET" action="student.php">
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
        <h2>家教老師列表</h2>
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
                  <h3 style="margin:0;"><?php echo htmlspecialchars($post['name']); ?></h3>
                  <p style="font-size: 13px; color: var(--primary); margin-top:2px;">
                    <?php echo htmlspecialchars($post['subject']); ?> · <?php echo htmlspecialchars($post['region']); ?> · <?php echo $post['gender']=='M'?'男':'女'; ?>
                  </p>
                </div>
              </div>
              <p class="desc" style="font-size:14px; font-weight:bold; margin-top:6px;"><?php echo htmlspecialchars($post['title']); ?></p>
              <div class="meta" style="margin-top: auto; padding-top: 8px;">
                <strong style="color: var(--text);"><?php echo htmlspecialchars($post['budget']); ?></strong>
              </div>
              <button class="primary" style="margin-top: 4px;" onclick="openDetail(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['name'] . ' 老師 - ' . $post['title']); ?>', '<?php echo htmlspecialchars($post['content']); ?>', <?php echo $post['user_id']; ?>)">查看詳細履歷</button>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </section>

    <aside class="side card">
      <button style="background: var(--primary); color: #fff; border-color: transparent; font-weight: bold;" onclick="openModal('publishPostModal')">➕ 發布貼文</button>
      
      <button onclick="openModal('managePostModal')">貼文管理</button>
      
      <button onclick="openModal('manageAppsModal')">應徵清單</button>
      <button onclick="openChat(2, '客服與回饋中心')">聊天室</button>
    </aside>
  </main>

  <div id="profileModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
      <span class="close-btn" onclick="closeModal('profileModal')">&times;</span>
      <h2>帳號管理</h2>
      <div style="margin-top:16px; display:grid; gap:12px;">
        <div class="field">
          <label>使用者姓名</label>
          <input type="text" id="editUserName" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" placeholder="請輸入新姓名" />
        </div>
        <div class="field"><label>身分</label><input type="text" disabled value="學生 / 家長" /></div>
        
        <button type="button" class="primary" onclick="updateName()">儲存</button>
        
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line);"></div>
        <button type="button" style="background: #d9534f; color: #fff; border-color: transparent; font-weight: bold;" onclick="alert('已登出'); location.href='login.php';">登出</button>
      </div>
    </div>
  </div>

  <div id="managePostModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('managePostModal')">&times;</span>
      <h2>我的貼文</h2>
      <p class="desc" style="margin-bottom:12px;">以下是您曾經發布過的家教需求貼文：</p>
      
      <div style="max-height: 350px; overflow-y: auto; padding-right:5px;">
        <?php if(empty($my_posts)): ?>
          <p style="text-align:center; padding:20px; color:var(--muted);">您目前尚未發布過任何需求貼文。</p>
        <?php else: ?>
          <?php foreach($my_posts as $mp): ?>
            <div class="manage-post-item" id="myPostRow_<?php echo $mp['id']; ?>">
              <div style="flex: 1;">
                <strong style="font-size:15px; color:var(--text);"><?php echo htmlspecialchars($mp['title']); ?></strong>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">
                  科目: <?php echo htmlspecialchars($mp['subject']); ?> | 地區: <?php echo htmlspecialchars($mp['region']); ?> | 時薪: <?php echo htmlspecialchars($mp['budget']); ?>
                </div>
              </div>
              <button style="background:#d9534f; color:#fff; border:none; padding:6px 12px; font-size:12px;" onclick="deletePost(<?php echo $mp['id']; ?>)">刪除</button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div id="publishPostModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('publishPostModal')">&times;</span>
      <h2>填寫家教需求</h2>
      <form class="post" style="margin-top:16px;" method="POST" action="api/post_process.php">
        <div class="row">
          <div class="field" style="flex:1;"><label>標題</label><input name="title" required placeholder="例如：尋找高一數學段考複習老師" /></div>
          <div class="field" style="flex:1;"><label>科目</label><input name="subject" required placeholder="例如：數學" /></div>
        </div>
        <div class="row">
          <div class="field" style="flex:1;"><label>上課地區</label><input name="region" required placeholder="例如：台北" /></div>
          <div class="field" style="flex:1;"><label>時薪</label><input name="budget" required placeholder="例如：NT$600-700/hr" /></div>
        </div>
        <div class="field"><label>詳細狀況與條件描述</label><textarea name="content" rows="4" required placeholder="請簡單敘述需求..."></textarea></div>
        <button type="submit" class="primary" style="width: 100%; padding: 12px;">發布</button>
      </form>
    </div>
  </div>

  <div id="detailModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('detailModal')">&times;</span>
      <h2 id="detailTitle">老師履歷</h2>
      <p id="detailContent" style="margin:18px 0; color:var(--text); line-height:1.6; background:#fff; padding:15px; border-radius:12px; border:1px solid var(--line);">內文加載中...</p>
      <hr style="border:0; border-top:1px solid var(--line); margin-bottom:16px;">
      <button class="primary" id="contactBtn" style="width: 100%; padding: 12px;">聊聊</button>
    </div>
  </div>

  <div id="manageAppsModal" class="modal">
  <div class="modal-content" style="max-width: 600px;">
    <span class="close-btn" onclick="closeModal('manageAppsModal')">&times;</span>
    <h2>應徵清單</h2>
    <p class="desc" style="margin-bottom:12px;">以下是應徵您家教需求的老師名單：</p>
    
    <div style="max-height: 400px; overflow-y: auto; padding-right:5px;">
      <?php if(empty($applications)): ?>
        <p style="text-align:center; padding:20px; color:var(--muted);">目前尚未有老師應徵您的案件。</p>
      <?php else: ?>
        <?php foreach($applications as $app): ?>
          <div class="manage-post-item" id="appRow_<?php echo $app['app_id']; ?>" style="flex-direction: column; align-items: stretch; background:#fff;">
            <div style="display: flex; justify-content: space-between; align-items: start; border-bottom: 1px solid var(--line); padding-bottom: 8px;">
              <div>
                <span class="chip" style="background:var(--primary); color:#fff; margin-bottom:4px;">應徵案件</span>
                <strong style="color:var(--text); display:block;"><?php echo htmlspecialchars($app['post_title']); ?></strong>
              </div>
              <!-- 動態顯示目前審核狀態 -->
              <span class="chip" id="appBadge_<?php echo $app['app_id']; ?>">
                <?php 
                  if($app['status'] == 'pending') echo '待審核';
                  if($app['status'] == 'accepted') echo '✅ 已錄用';
                  if($app['status'] == 'rejected') echo '❌ 已拒絕';
                ?>
              </span>
            </div>
            
            <div style="margin-top: 8px; display: flex; gap: 12px; align-items: center;">
              <div class="avatar" style="width:40px; height:40px;" onclick="closeModal('manageAppsModal'); openChat(<?php echo $app['tutor_id']; ?>, '<?php echo htmlspecialchars($app['tutor_name']); ?>')"></div>
              <div style="flex: 1;">
                <strong style="color:var(--text);"><?php echo htmlspecialchars($app['tutor_name']); ?> 老師 (<?php echo $app['tutor_gender']=='M'?'男':'女'; ?>)</strong>
                <p style="font-size:12px; color:var(--muted); margin: 2px 0 0 0;"><?php echo htmlspecialchars($app['tutor_bio']); ?></p>
              </div>
            </div>

            <div style="margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end;" id="appBtnGroup_<?php echo $app['app_id']; ?>">
              <?php if($app['status'] == 'pending'): ?>
                <button style="background:#28a745; color:#fff; border:none; padding:6px 12px; font-size:12px;" onclick="handleApplication(<?php echo $app['app_id']; ?>, 'accepted')">錄用</button>
                <button style="background:#d9534f; color:#fff; border:none; padding:6px 12px; font-size:12px;" onclick="handleApplication(<?php echo $app['app_id']; ?>, 'rejected')">婉拒</button>
              <?php endif; ?>
              <button style="padding:6px 12px; font-size:12px;" onclick="closeModal('manageAppsModal'); openChat(<?php echo $app['tutor_id']; ?>, '<?php echo htmlspecialchars($app['tutor_name']); ?>')">聊聊</button>
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
        <div style="color:var(--muted); font-size:13px;">[系統提示] 安全加密對話通道已建立。</div>
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

    function updateName() {
        const nameInput = document.getElementById('editUserName').value;
        if(!nameInput.trim()) { alert('姓名欄位不可留空！'); return; }

        const formData = new FormData();
        formData.append('name', nameInput);

        fetch('api/update_profile.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('您的姓名已成功變更！');
                document.getElementById('topNavWelcome').innerText = '你好，' + data.name;
                closeModal('profileModal');
            } else {
                alert('修改失敗，請稍後再試。');
            }
        });
    }
    function deletePost(postId) {
        if(!confirm('您確定要刪除這則貼文嗎？')) return;

        const formData = new FormData();
        formData.append('post_id', postId);

        fetch('api/delete_post.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('貼文已刪除！');
                // 動態將該筆案件列從列表中移除消失
                const targetRow = document.getElementById('myPostRow_' + postId);
                if(targetRow) targetRow.remove();
            } else {
                alert('刪除失敗，請稍後再試。');
            }
        });
    }

    function openDetail(id, title, content, userId) {
        document.getElementById('detailTitle').innerText = title;
        document.getElementById('detailContent').innerText = content;
        document.getElementById('contactBtn').onclick = function() {
            closeModal('detailModal'); openChat(userId, title.split(' - ')[0]);
        };
        openModal('detailModal');
    }

    function openChat(receiverId, name) {
        document.getElementById('chatTargetName').innerText = '與 ' + name + ' 對話中';
        document.getElementById('receiverId').value = receiverId;
        document.getElementById('chatBox').innerHTML = '<div style="color:var(--muted); font-size:13px;">[系統提示] 歷史紀錄載入成功...</div>';
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

    function handleApplication(appId, action) {
        const confirmMsg = action === 'accepted' ? '確定要錄用這位老師嗎？' : '確定要婉拒這位老師的應徵嗎？';
        if (!confirm(confirmMsg)) return;

        const formData = new FormData();
        formData.append('app_id', appId);
        formData.append('action', action);

        fetch('api/handle_application.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(action === 'accepted' ? '已成功錄用該師資！' : '已婉拒該師資。');
                
                const badge = document.getElementById('appBadge_' + appId);
                if (badge) {
                    badge.innerText = action === 'accepted' ? '✅ 已錄用' : '❌ 已拒絕';
                }
                
                const btnGroup = document.getElementById('appBtnGroup_' + appId);
                if (btnGroup) {
                    btnGroup.innerHTML = `<span style="font-size:12px; color:var(--muted);">審核完畢</span>`;
                }
            } else {
                alert('操作失敗，請稍後再試。');
            }
        });
    }
  </script>
</body>
</html>