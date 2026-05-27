<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// 徹底清除所有 Session 變數
$_SESSION = array();
session_destroy();

echo "<script>alert('已強制清空登入狀態！'); location.href='login.php';</script>";
?>