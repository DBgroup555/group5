<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

/**
 * @param string $toEmail 接收者的信箱
 * @param string $toName  接收者的名字
 * @param string $subject 信件標題
 * @param string $bodyHtml 信件 HTML 內容
 * @return bool 是否發送成功
 */
function sendWelcomeEmail($toEmail, $toName, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                        // SMTP 伺服器
        $mail->SMTPAuth   = true;                                    // 開啟 SMTP 驗證
        $mail->Username   = 'acs113129@gm.ntcu.edu.tw';                  // 你的 Gmail 信箱
        $mail->Password   = 'dcqf quhs modt ioqp';                // 你的 Gmail「應用程式密碼」(非登入密碼)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;          // 加密方式
        $mail->Port       = 587;                                     // 連接埠
        $mail->CharSet    = 'UTF-8';                                 // 防亂碼

        // 收發件人設定
        $mail->setFrom('acs113129@gm.ntcu.edu.tw', '家教媒合平台');       // 寄件人信箱與名稱
        $mail->addAddress($toEmail, $toName);                        // 收件人

        // 信件內容設定
        $mail->isHTML(true);                          
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // 失敗記錄錯誤訊息到本地日誌
        error_log("郵件發送失敗。Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}