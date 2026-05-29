<?php
// 確保啟動 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$code_pool = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$captcha_code = '';
for ($i = 0; $i < 4; $i++) {
    $captcha_code .= $code_pool[rand(0, strlen($code_pool) - 1)];
}

$_SESSION['captcha_auth'] = strtolower($captcha_code);

if (ob_get_length()) ob_clean();

header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');

$image = imagecreatetruecolor(120, 40);

// 4. 設定顏色
$bg_color = imagecolorallocate($image, 250, 245, 240);
$text_color = imagecolorallocate($image, 91, 70, 54);  
$noise_color = imagecolorallocate($image, 220, 210, 200);


imagefill($image, 0, 0, $bg_color);


for ($i = 0; $i < 4; $i++) {
    imageline($image, rand(0, 120), rand(0, 40), rand(0, 120), rand(0, 40), $noise_color);
}

for ($i = 0; $i < strlen($captcha_code); $i++) {
    $x = 20 + ($i * 22); 
    $y = rand(8, 15);  
    imagechar($image, 5, $x, $y, $captcha_code[$i], $text_color);
}

// 7. 輸出圖片並釋放
imagepng($image);
imagedestroy($image);
exit;
?>