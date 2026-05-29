<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
   
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed_exts)) {
            echo json_encode(['status' => 'error', 'message' => '只允許上傳 JPG, PNG, GIF 格式的圖片！']);
            exit;
        }

        if ($file_size > 2097152) {
            echo json_encode(['status' => 'error', 'message' => '圖片檔案過大，請勿超過 2MB！']);
            exit;
        }

        $new_file_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $upload_dir = '../uploads/';
        $dest_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp, $dest_path)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            if ($stmt->execute([$new_file_name, $user_id])) {
            
                $_SESSION['user_avatar'] = $new_file_name;

                echo json_encode([
                    'status' => 'success', 
                    'message' => '頭像上傳成功！',
                    'avatar_url' => 'uploads/' . $new_file_name
                ]);
                exit;
            }
        }
    }
}
echo json_encode(['status' => 'error', 'message' => '上傳失敗，請選擇正確的圖片檔案。']);
exit;