<?php 
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$users_stmt = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");
$all_users = $users_stmt->fetchAll();

$view_type = $_GET['view_type'] ?? 'tutor'; 

$posts_stmt = $pdo->prepare("
    SELECT posts.*, users.name, users.role 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE users.role = ? 
    ORDER BY posts.id DESC
");
$posts_stmt->execute([$view_type]);
$filtered_posts = $posts_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>家教媒合平台</title>
  <style>
    :root{--bg:#f6efe7;--card:#fffaf4;--line:#e5d7c8;--text:#5b4636;--muted:#8b6f58;--primary:#c9a27e;--soft:#f1e5d8;--danger:#d9534f;--success:#28a745}
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,"Noto Sans TC",sans-serif;background:linear-gradient(180deg,#fbf6f1,var(--bg));color:var(--text)}
    button,input,select,textarea{font:inherit}
    .topbar{display:flex;align-items:center;justify-content:space-between;padding:18px 28px;border-bottom:1px solid var(--line);background:rgba(255,250,244,.85);backdrop-filter:blur(8px);position:sticky;top:0;z-index:10}
    .brand{font-weight:800;letter-spacing:1px;color:var(--danger)}
    .layout{display:grid;grid-template-columns:1fr;gap:24px;padding:24px;max-width:1200px;margin:0 auto;}
    .card{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:0 10px 30px rgba(90,60,30,.06);padding:20px;}
    
    /* 表格樣式 */
    table{width:100%;border-collapse:collapse;margin-top:12px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--line)}
    th,td{padding:14px;text-align:left;border-bottom:1px solid var(--line)}
    th{background:var(--soft);color:var(--text);font-weight:bold}
    
    /* 切換標籤樣式 */
    .tab-container{display:flex;gap:10px;margin-bottom:16px;border-bottom:2px solid var(--line);padding-bottom:10px;}
    .tab-btn{padding:10px 20px;border-radius:10px;border:1px solid var(--line);background:#fff;font-weight:bold;cursor:pointer;}
    .tab-btn.active{background:var(--primary);color:#fff;border-color:transparent;}
    
    button{border:1px solid var(--line);background:#fff;padding:8px 14px;border-radius:10px;cursor:pointer;color:var(--text);font-weight:500;}
    button.btn-danger{background:var(--danger);color:#fff;border-color:transparent;}
    button.btn-success{background:var(--success);color:#fff;border-color:transparent;}
    
    .chip{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:bold;}
    .chip-tutor{background:#e3f2fd;color:#0d47a1}
    .chip-student{background:#f3e5f5;color:#4a148c}
    
    .top-actions{display:flex;align-items:center;gap:14px;}
    .avatar{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#5b4636,#8b6f58);cursor:pointer}

    .modal {display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:100; justify-content:center; align-items:center;}
    .modal-content {background:var(--card); padding:24px; border-radius:20px; width:90%; max-width:400px; border:1px solid var(--line); position:relative;}
    .close-btn {position:absolute; top:16px; right:16px; cursor:pointer; font-size:20px; font-weight:bold;}
  </style>
</head>
<body>

  <header class="topbar">
    <div class="brand" onclick="location.href='admin.php'">家教媒合平台</div>
    <div class="top-actions">
      <span style="font-weight:bold;">您好，<?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
      <div class="avatar" onclick="openModal('logoutModal')" title="點擊登出"></div>
    </div>
  </header>

  <main class="layout">
    
    <section class="card">
      <h2 style="margin:0 0 4px 0;">帳號權限管理</h2>
      
      <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>姓名</th>
              <th>電子信箱</th>
              <th>身分</th>
              <th>電話</th>
              <th>目前狀態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($all_users as $u): ?>
              <tr id="userRow_<?php echo $u['id']; ?>">
                <td><?php echo $u['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                  <span class="chip <?php echo $u['role']=='tutor'?'chip-tutor':'chip-student'; ?>">
                    <?php echo $u['role']=='tutor'?'家教老師':'學生家長'; ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($u['phone'] ?? '未填寫'); ?></td>
                <td id="statusText_<?php echo $u['id']; ?>">
                  <?php echo $u['status'] == 1 ? '<span style="color:var(--success); font-weight:bold;">● 正常啟用</span>' : '<span style="color:var(--danger); font-weight:bold;">🛑 已停權</span>'; ?>
                </td>
                <td>
                  <button id="statusBtn_<?php echo $u['id']; ?>" 
                          class="<?php echo $u['status'] == 1 ? 'btn-danger' : 'btn-success'; ?>" 
                          onclick="toggleUserStatus(<?php echo $u['id']; ?>, <?php echo $u['status']; ?>)">
                    <?php echo $u['status'] == 1 ? '停權' : '啟用'; ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card" style="margin-top:10px;">
      <h2 style="margin:0 0 4px 0;">貼文管理</h2>
      
      <div class="tab-container">
        <button class="tab-btn <?php echo $view_type === 'tutor' ? 'active' : ''; ?>" onclick="location.href='admin.php?view_type=tutor'">老師貼文</button>
        <button class="tab-btn <?php echo $view_type === 'student' ? 'active' : ''; ?>" onclick="location.href='admin.php?view_type=student'">學生貼文</button>
      </div>

      <div style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>刊登發布者</th>
              <th>科目分類</th>
              <th>上課地區</th>
              <th>預算/時薪</th>
              <th>標題</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($filtered_posts)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:var(--muted);">目前該分類下沒有任何刊登貼文。</td>
              </tr>
            <?php else: ?>
              <?php foreach($filtered_posts as $p): ?>
                <tr id="postRow_<?php echo $p['id']; ?>">
                  <td><?php echo $p['id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                  <td><span class="chip" style="background:var(--soft);"><?php echo htmlspecialchars($p['subject']); ?></span></td>
                  <td><?php echo htmlspecialchars($p['region']); ?></td>
                  <td style="color:var(--primary); font-weight:bold;"><?php echo htmlspecialchars($p['budget']); ?></td>
                  <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-transform:ellipsis;" title="<?php echo htmlspecialchars($p['content']); ?>">
                    <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                  </td>
                  <td>
                    <button class="btn-danger" style="padding:6px 12px; font-size:13px;" onclick="adminDeletePost(<?php echo $p['id']; ?>)">刪除</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('logoutModal')">&times;</span>
      <h3>帳號管理</h3>
      <button style="width:100%; background:#d9534f; color:#fff; padding:12px; border:none; border-radius:10px; font-weight:bold;" onclick="alert('已登出'); location.href='login.php';">登出</button>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function toggleUserStatus(userId, currentStatus) {
        const actionText = currentStatus === 1 ? '封鎖停權該會員，使其無法正常登入平台嗎？' : '解除封鎖該會員帳號嗎？';
        if(!confirm('確定要' + actionText)) return;

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('current_status', currentStatus);

        fetch('api/admin_toggle_user.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('帳號權限狀態已成功變更！');
                
                // 即時刷新變更畫面的狀態文字與按鈕顏色，完全不用重整網頁
                const textTd = document.getElementById('statusText_' + userId);
                const actionBtn = document.getElementById('statusBtn_' + userId);
                
                if(data.new_status === 0) {
                    textTd.innerHTML = '<span style="color:var(--danger); font-weight:bold;">🛑 已停權</span>';
                    actionBtn.innerText = '解除';
                    actionBtn.className = 'btn-success';
                    actionBtn.setAttribute('onclick', `toggleUserStatus(${userId}, 0)`);
                } else {
                    textTd.innerHTML = '<span style="color:var(--success); font-weight:bold;">● 正常啟用</span>';
                    actionBtn.innerText = '停權';
                    actionBtn.className = 'btn-danger';
                    actionBtn.setAttribute('onclick', `toggleUserStatus(${userId}, 1)`);
                }
            } else {
                alert('權限變更失敗，請確認管理員身分。');
            }
        });
    }

    function adminDeletePost(postId) {
        if(!confirm('該貼文將會永久消失。確定執行嗎？')) return;

        const formData = new FormData();
        formData.append('post_id', postId);

        fetch('api/admin_delete_post.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert('貼文已被刪除！');
                const row = document.getElementById('postRow_' + postId);
                if(row) row.remove();
            } else {
                alert('操作失敗，請重試。');
            }
        });
    }
  </script>
</body>
</html>