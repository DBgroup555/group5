<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '尚未登入']); exit;
}
$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($message) || empty($receiver_id)) {
    echo json_encode(['status' => 'error', 'message' => '欄位不可為空']); exit;
}

$apiKey = "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"; //6/28到期

$url = "https://api.groq.com/openai/v1/chat/completions";

$prompt = "
你是一個聊天室審查 AI。

如果訊息包含：
- 加 LINE
- 電話
- 匯款
- 私下交易
- 色情
- 辱罵

請回：
BLOCK

正常訊息回：
SAFE

只能回 SAFE 或 BLOCK。

訊息：
{$message}
";

$data = [
    "model" => "llama-3.3-70b-versatile",
    "messages" => [
        [
            "role" => "user",
            "content" => $prompt
        ]
    ],
    "temperature" => 0
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);

if ($response === false) {
    die(curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$result = json_decode($response, true);

if ($httpCode != 200) {

    echo json_encode([
        "status" => "api_error",
        "response" => $result
    ]);

    exit;
}

$reply = $result['choices'][0]['message']['content'] ?? '';

$is_safe = trim($reply) === 'SAFE';

if (!$is_safe) {

    echo json_encode([
        'status' => 'blocked',
        'message' => '⚠️ 系統偵測違規訊息，已拒絕發送'
    ]);

    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
$success = $stmt->execute([$sender_id, $receiver_id, $message]);

if (ob_get_length()) ob_clean();

if ($success) {
    echo json_encode([
        'status' => 'success',
        'message' => '訊息已送出'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => '資料庫寫入失敗'
    ]);
}
exit; 