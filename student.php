<?php 
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$keyword = $_GET['keyword'] ?? '';
$chosen_subjects = $_GET['subjects'] ?? [];
$chosen_cities = $_GET['cities'] ?? [];
$chosen_genders = $_GET['genders'] ?? [];

$standard_subjects = ['國文', '數學', '英文', '自然', '社會'];
$standard_cities = [
    '基隆市', '台北市', '新北市', '桃園市', '新竹市', '新竹縣', '宜蘭縣',
    '苗栗縣', '台中市', '彰化縣', '南投縣', '雲林縣',
    '嘉義市', '嘉義縣', '台南市', '高雄市', '屏東縣',
    '花蓮縣', '台東縣', '澎湖縣', '金門縣', '連江縣'
];

$sql = "SELECT posts.*, users.name, users.gender FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE users.role = 'tutor' AND users.status = 1";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (posts.title LIKE ? OR posts.content LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if (!empty($chosen_subjects)) {
    $sub_conditions = [];
    $has_other_sub = in_array('其他', $chosen_subjects);
    $pure_subjects = array_diff($chosen_subjects, ['其他']);
    
    if (!empty($pure_subjects)) {
        $placeholders = implode(',', array_fill(0, count($pure_subjects), '?'));
        $sub_conditions[] = "posts.subject IN ($placeholders)";
        foreach ($pure_subjects as $s) { $params[] = $s; }
    }
    if ($has_other_sub) {
        $not_in_placeholders = implode(',', array_fill(0, count($standard_subjects), '?'));
        $sub_conditions[] = "posts.subject NOT IN ($not_in_placeholders) OR posts.subject IS NULL";
        foreach ($standard_subjects as $s) { $params[] = $s; }
    }
    
    if (!empty($sub_conditions)) {
        $sql .= " AND (" . implode(' OR ', $sub_conditions) . ")";
    }
}

if (!empty($chosen_cities)) {
    $city_conditions = [];
    $has_other_city = in_array('其他', $chosen_cities);
    
    $pure_cities = array_diff($chosen_cities, ['其他']);
    
    if (!empty($pure_cities)) {
        $placeholders = implode(',', array_fill(0, count($pure_cities), '?'));
        $city_conditions[] = "posts.region IN ($placeholders)";
        foreach ($pure_cities as $c) { $params[] = $c; }
    }
    if ($has_other_city) {
        $not_in_placeholders = implode(',', array_fill(0, count($standard_cities), '?'));
        $city_conditions[] = "posts.region NOT IN ($not_in_placeholders) OR posts.region IS NULL";
        foreach ($standard_cities as $c) { $params[] = $c; }
    }
    if (!empty($city_conditions)) {
        $sql .= " AND (" . implode(' OR ', $city_conditions) . ")";
    }
}

if (!empty($chosen_genders)) {
    $placeholders = implode(',', array_fill(0, count($chosen_genders), '?'));
    $sql .= " AND users.gender IN ($placeholders)";
    foreach ($chosen_genders as $gen) { $params[] = $gen; }
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
    #reviewModal, #viewReviewsModal {z-index: 200 !important;}
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
    <form class="filters card" method="GET" action="<?php echo basename($_SERVER['PHP_SELF']); ?>" style="padding: 20px; display: grid; gap: 16px;">
        <h2>多條件搜尋</h2>
        
        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword ?? ''); ?>">

        <div class="filter-group">
          <strong style="display:block; margin-bottom: 8px; font-size: 14px;">科目</strong>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; padding-left: 4px;">
            <?php 
            $main_subjects = ['國文', '數學', '英文', '自然', '社會'];
            $chosen_subjects = $_GET['subjects'] ?? [];
            foreach ($main_subjects as $sub): 
              $checked = in_array($sub, $chosen_subjects) ? 'checked' : '';
            ?>
              <label style="display: flex; align-items: center; gap: 4px; font-size: 13px; cursor: pointer;">
                <input type="checkbox" name="subjects[]" value="<?php echo $sub; ?>" <?php echo $checked; ?>> <?php echo $sub; ?>
              </label>
            <?php endforeach; ?>
            <label style="display: flex; align-items: center; gap: 4px; font-size: 13px; cursor: pointer;">
              <input type="checkbox" name="subjects[]" value="其他" <?php echo in_array('其他', $chosen_subjects) ? 'checked' : ''; ?>> 其他
            </label>
          </div>
        </div>

        <div style="border-top: 1px dashed var(--line);"></div>

        <div class="filter-group">
          <strong style="display:block; margin-bottom: 8px; font-size: 14px;">地區</strong>
          
          <?php
          // 定義四大區域與縣市對照表
          $regions_map = [
              '北部' => ['基隆市', '台北市', '新北市', '桃園市', '新竹市', '新竹縣', '宜蘭縣'],
              '中部' => ['苗栗縣', '台中市', '彰化縣', '南投縣', '雲林縣'],
              '南部' => ['嘉義市', '嘉義縣', '台南市', '高雄市', '屏東縣'],
              '東部和離島' => ['花蓮縣', '台東縣', '澎湖縣', '金門縣', '連江縣'] // 離島併入東部與外島管理
          ];
          $chosen_cities = $_GET['cities'] ?? [];
          $chosen_other_region = in_array('其他', $_GET['regions'] ?? []) || in_array('其他', $chosen_cities);

          foreach ($regions_map as $area => $cities):
          ?>
            <details style="margin-bottom: 6px; background: #faf6f0; border-radius: 8px; padding: 4px 8px; border: 1px solid #f1e5d8;">
              <summary style="font-size: 13px; font-weight: bold; cursor: pointer; color: var(--text); padding: 4px 0;">
                <?php echo $area; ?>地區
              </summary>
              <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; padding: 8px 4px 4px 12px; border-top: 1px solid #f1e5d8; margin-top: 4px;">
                <?php foreach ($cities as $city): 
                  $checked = in_array($city, $chosen_cities) ? 'checked' : '';
                ?>
                  <label style="display: flex; align-items: center; gap: 4px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="cities[]" value="<?php echo $city; ?>" <?php echo $checked; ?>> <?php echo $city; ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </details>
          <?php endforeach; ?>

          <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; margin-top: 8px; padding-left: 8px;">
            <input type="checkbox" name="cities[]" value="其他" <?php echo in_array('...其他', $chosen_cities) || $chosen_other_region ? 'checked' : ''; ?>>其他
          </label>
        </div>

        <div style="border-top: 1px dashed var(--line);"></div>

        <div class="filter-group">
          <strong style="display:block; margin-bottom: 8px; font-size: 14px;">性別</strong>
          <div style="display: flex; gap: 16px; padding-left: 4px;">
            <?php $chosen_genders = $_GET['genders'] ?? []; ?>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 14px; cursor: pointer;">
              <input type="checkbox" name="genders[]" value="M" <?php echo in_array('M', $chosen_genders) ? 'checked' : ''; ?>> 男
            </label>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 14px; cursor: pointer;">
              <input type="checkbox" name="genders[]" value="F" <?php echo in_array('F', $chosen_genders) ? 'checked' : ''; ?>> 女
            </label>
          </div>
        </div>

        <button type="submit" class="primary" style="margin-top: 10px; padding: 12px; font-weight: bold;">套用</button>
      </form>
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
            <article class="tutor-card card" style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
    
              <div style="display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 10px;">
                <div class="avatar" style="width: 44px; height: 44px; flex-shrink: 0;"></div>
                <strong style="font-size: 16px; color: var(--text);"><?php echo htmlspecialchars($post['name']); ?> 老師</strong>
              </div>

              <div style="display: flex; flex-direction: column; gap: 6px; background: #fff; padding: 10px; border-radius: 10px; border: 1px solid #f1e5d8; font-size: 13px;">
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--muted);">專長科目：</span>
                  <strong style="color: var(--text);"><?php echo htmlspecialchars($post['subject']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--muted);">上課地區：</span>
                  <strong style="color: var(--text);"><?php echo htmlspecialchars($post['region']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                  <span style="color: var(--muted);">期望時薪：</span>
                  <strong style="color: var(--primary);"><?php echo htmlspecialchars($post['budget']); ?></strong>
                </div>
              </div>

              <button class="primary" style="width: 100%; margin-top: auto; padding: 8px;" 
                      onclick="openDetail(
                        <?php echo $post['id']; ?>, 
                        '<?php echo htmlspecialchars($post['title']); ?>', 
                        '<?php echo htmlspecialchars($post['subject']); ?>', 
                        '<?php echo htmlspecialchars($post['region']); ?>', 
                        '<?php echo htmlspecialchars($post['budget']); ?>', 
                        '<?php echo htmlspecialchars($post['content']); ?>', 
                        <?php echo $post['user_id']; ?>
                      )">
                查看詳情
              </button>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </section>

    <div id="reviewModal" class="modal">
      <div class="modal-content" style="max-width: 400px;">
        <span class="close-btn" onclick="closeModal('reviewModal')">&times;</span>
        <h2>給予老師回饋</h2>
        <form id="reviewForm" style="margin-top:16px; display:grid; gap:12px;">
          <input type="hidden" id="reviewAppId" />
          <input type="hidden" id="reviewTutorId" />
          
          <div class="field">
            <label>評語</label>
            <textarea id="reviewComment" rows="5" required placeholder="請輸入您對老師的教學回饋與心得..."></textarea>
          </div>
          <button type="button" class="primary" onclick="submitReview()">提交</button>
        </form>
      </div>
    </div>
    <div id="viewReviewsModal" class="modal">
      <div class="modal-content" style="max-width: 500px;">
        <span class="close-btn" onclick="closeModal('viewReviewsModal')">&times;</span>
        <h2 id="viewReviewsTitle">老師的歷史評價</h2>
        <div id="reviewsList" style="max-height: 350px; overflow-y: auto; margin-top: 16px; display: grid; gap: 10px;">
          </div>
      </div>
    </div>

    <aside class="side card">
      <button style="background: var(--primary); color: #fff; border-color: transparent; font-weight: bold;" onclick="openModal('publishPostModal')">➕ 發布貼文</button>
      
      <button onclick="openModal('managePostModal')">貼文管理</button>
      
      <button onclick="openModal('manageAppsModal')">應徵清單</button>
      <button onclick="openChat()">聊天室</button>
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
    <div class="modal-content" style="max-width: 500px;">
      <span class="close-btn" onclick="closeModal('detailModal')">&times;</span>
      
      <h2 id="detailTitle" style="font-size: 20px; line-height: 1.4; margin-bottom: 16px; color: var(--text);">老師履歷標題</h2>
      
      <div style="background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 14px; display: grid; gap: 8px; font-size: 14px; margin-bottom: 16px;">
        <div><strong>專長科目：</strong> <span id="detailSubject"></span></div>
        <div><strong>上課地區：</strong> <span id="detailRegion"></span></div>
        <div><strong>期望時薪：</strong> <span id="detailBudget" style="color: var(--primary); font-weight: bold;"></span></div>
      </div>

      <div style="font-size: 14px; color: var(--text); margin-bottom: 6px;"><strong>簡介：</strong></div>
      <p id="detailContent" style="margin: 6px 0 16px 0; color: var(--text); line-height: 1.6; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--line); white-space: pre-line;">內文加載中...</p>
      
      <input type="hidden" id="detailTutorId" />
      <hr style="border:0; border-top:1px solid var(--line); margin-bottom:16px;">
      <button type="button" style="width: 100%; padding: 10px; margin-bottom: 10px; background: #fff; border: 1px solid var(--primary); color: var(--primary); border-radius: 10px;" onclick="showTutorReviews()">評價</button>
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
              <?php elseif($app['status'] == 'accepted'): ?>
                <button style="background:#ffc107; color:#212529; border:none; padding:6px 12px; font-size:12px; font-weight:bold;" onclick="closeModal('manageAppsModal'); openReviewModal(<?php echo $app['app_id']; ?>, <?php echo $app['tutor_id']; ?>)">給予回饋</button>
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
    <div class="modal-content" style="max-width: 750px; padding: 0; overflow: hidden; display: flex; flex-direction: column; height: 500px;">
      
      <div style="padding: 16px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: var(--card);">
        <h2 id="chatTargetName" style="font-size: 18px;">即時互動聊天室</h2>
        <span class="close-btn" style="position: static;" onclick="closeModal('chatModal')">&times;</span>
      </div>

      <div style="display: flex; flex: 1; min-height: 0;">
        
        <div id="contactList" style="width: 220px; border-right: 1px solid var(--line); background: #fff; overflow-y: auto; padding: 10px 0;">
          <p style="text-align: center; color: var(--muted); font-size: 13px; padding: 10px;">載入聯絡人中...</p>
        </div>

        <div style="flex: 1; display: flex; flex-direction: column; background: var(--card);">
          <div class="chat-box" id="chatBox" style="flex: 1; height: auto; margin: 0; border: none; border-radius: 0; padding: 16px; overflow-y: auto; background: #faf5f0;">
            <div style="color:var(--muted); text-align: center; font-size:13px; margin-top: 60px;">請從左側選擇一位聯絡人開始對話</div>
          </div>
          
          <div style="padding: 12px; border-top: 1px solid var(--line); display:flex; gap:8px; background: #fff; align-items: center;">
            <input type="hidden" id="receiverId" />
            <input type="text" id="msgInput" style="flex:1; padding:10px; border-radius:10px; border:1px solid var(--line);" placeholder="請選擇聯絡人後輸入訊息..." disabled />
            <button class="primary" id="sendMsgBtn" onclick="sendMessage()" style="padding: 10px 20px;" disabled>發送</button>
          </div>
        </div>

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

    function openDetail(id, title, subject, region, budget, content, userId) {
      document.getElementById('detailTitle').innerText = title;
      document.getElementById('detailSubject').innerText = subject;
      document.getElementById('detailRegion').innerText = region;
      document.getElementById('detailBudget').innerText = budget;
      document.getElementById('detailContent').innerText = content;
      document.getElementById('detailTutorId').value = userId; 
      
      document.getElementById('contactBtn').onclick = function() {
          closeModal('detailModal'); openChat(userId, title.split(' - ')[0]);
      };
      openModal('detailModal');
    }

    function openChat(targetUserId = null, targetUserName = '') {
      openModal('chatModal');

      fetch('api/get_contacts.php')
      .then(res => res.json())
      .then(contacts => {
          const contactListDiv = document.getElementById('contactList');
          contactListDiv.innerHTML = ''; 

          if (contacts.length === 0 && !targetUserId) {
              contactListDiv.innerHTML = '<p style="text-align: center; color: var(--muted); font-size: 13px; padding: 10px;">目前尚無對話紀錄</p>';
              return;
          }
          const exists = contacts.some(c => c.id == targetUserId);
          if (targetUserId && !exists) {
              contacts.unshift({ id: targetUserId, name: targetUserName, role: '' });
          }

          contacts.forEach(c => {
              const roleBadge = c.role === 'tutor' ? ' (老師)' : (c.role === 'student' ? ' (學生)' : '');
              const item = document.createElement('div');
              item.style.padding = '12px 16px';
              item.style.cursor = 'pointer';
              item.style.borderBottom = '1px solid #f1e5d8';
              item.style.fontSize = '14px';
              item.style.fontWeight = 'bold';
              item.innerText = c.name + roleBadge;
              item.setAttribute('id', 'contactItem_' + c.id);
              
              // 點擊左側聯絡人觸發載入右側歷史對話
              item.onclick = function() {
                  // 先把所有聯絡人的背景色還原
                  contacts.forEach(co => {
                      const row = document.getElementById('contactItem_' + co.id);
                      if(row) row.style.background = 'transparent';
                  });

                  item.style.background = 'var(--soft)';
                  loadChatHistory(c.id, c.name);
              };

              contactListDiv.appendChild(item);
          });

          if (targetUserId) {
              const targetRow = document.getElementById('contactItem_' + targetUserId);
              if (targetRow) targetRow.click();
          }
      });
  }

  function loadChatHistory(withUserId, name) {
      document.getElementById('chatTargetName').innerText = '與 ' + name + ' 對話中';
      document.getElementById('receiverId').value = withUserId;
      
      document.getElementById('msgInput').disabled = false;
      document.getElementById('msgInput').placeholder = '請輸入訊息...';
      document.getElementById('sendMsgBtn').disabled = false;

      fetch('api/get_chat_history.php?with_id=' + withUserId)
      .then(res => res.json())
      .then(history => {
          const box = document.getElementById('chatBox');
          box.innerHTML = ''; 
          
          if (history.length === 0) {
              box.innerHTML = '<div style="color:var(--muted); text-align: center; font-size:13px; margin-top:20px;">暫無對話紀錄，傳送訊息開始聊聊吧！</div>';
              return;
          }

          history.forEach(m => {
            const isMe = (m.sender_id == <?php echo $_SESSION['user_id']; ?>);
            
            const align = isMe ? 'right' : 'left';
            const bg = isMe ? 'var(--primary)' : '#fff';
            const color = isMe ? '#fff' : 'var(--text)';
            const senderName = isMe ? '你' : name;

            box.innerHTML += `
                <div style="margin: 10px 0; text-align: ${align};">
                  <span style="font-size: 11px; color: var(--muted); display:block; margin-bottom:2px;">${senderName}</span>
                  <span style="display: inline-block; padding: 10px 14px; border-radius: 12px; background: ${bg}; color: ${color}; max-width: 70%; text-align: left; box-shadow: 0 2px 5px rgba(0,0,0,0.03); word-break: break-all;">
                    ${m.message}
                  </span>
                </div>
            `;
          });
          box.scrollTop = box.scrollHeight; 
      });
  }

  function sendMessage() {
      const receiverId = document.getElementById('receiverId').value;
      const msg = document.getElementById('msgInput').value;
      if(!msg.trim() || !receiverId) return;

      const formData = new FormData();
      formData.append('receiver_id', receiverId);
      formData.append('message', msg);

      fetch('api/send_message.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
          if(data.status === 'success') {
              const box = document.getElementById('chatBox');
              if (box.innerText.includes('暫無對話紀錄')) box.innerHTML = '';

              box.innerHTML += `
                  <div style="margin: 10px 0; text-align: right;">
                    <span style="font-size: 11px; color: var(--muted); display:block; margin-bottom:2px;">你</span>
                    <span style="display: inline-block; padding: 10px 14px; border-radius: 12px; background: var(--primary); color: #fff; max-width: 70%; text-align: left; box-shadow: 0 2px 5px rgba(0,0,0,0.03); word-break: break-all;">
                      ${msg}
                    </span>
                  </div>
              `;
              document.getElementById('msgInput').value = '';
              box.scrollTop = box.scrollHeight;
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

    function openReviewModal(appId, tutorId) {
      document.getElementById('reviewAppId').value = appId;
      document.getElementById('reviewTutorId').value = tutorId;
      document.getElementById('reviewComment').value = '';
      openModal('reviewModal');
    }

    function submitReview() {
        const appId = document.getElementById('reviewAppId').value;
        const tutorId = document.getElementById('reviewTutorId').value;
        const comment = document.getElementById('reviewComment').value; 

        if (!comment.trim()) { alert('請填寫評語'); return; }

        const formData = new FormData();
        formData.append('app_id', appId);
        formData.append('tutor_id', tutorId);
        formData.append('comment', comment);

        fetch('api/submit_review.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('感謝您的寶貴回饋！評價已成功提交。');
                closeModal('reviewModal');
            } else {
                alert(data.message || '提交失敗，請稍後再試。');
            }
        });
    }

    // 學生查看老師列表的歷史評價 
    function showTutorReviews() {
        const tutorId = document.getElementById('detailTutorId').value;
        const listDiv = document.getElementById('reviewsList');
        listDiv.innerHTML = '<p style="text-align:center; color:var(--muted);">載入評價中...</p>';
        
        openModal('viewReviewsModal');

        fetch('api/get_reviews.php?tutor_id=' + tutorId)
        .then(res => res.json())
        .then(reviews => {
            listDiv.innerHTML = '';
            if (reviews.length === 0) {
                listDiv.innerHTML = '<p style="text-align:center; padding:20px; color:var(--muted);">目前該位老師暫無學生評價。</p>';
                return;
            }
            reviews.forEach(r => {
                listDiv.innerHTML += `
                    <div style="background:#fff; border:1px solid var(--line); padding:14px; border-radius:12px;">
                      <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--muted);">
                        <strong>${r.student_name} 同學</strong>
                        <span>${r.created_at.split(' ')[0]}</span>
                      </div>
                      <p style="margin:8px 0 0 0; font-size:14px; color:var(--text); line-height:1.5; white-space: pre-line;">${r.comment}</p>
                    </div>
                `;
            });
        });
    }
  </script>
</body>
</html>