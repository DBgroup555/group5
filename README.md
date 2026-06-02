## 平台

管理員預設帳密:
帳-admin@tutor.com
密-admin1234

## 開發

1. 開啟 XAMPP Control Panel
2. 啟動 Apache 和 MySQL
3. 點 MySQL 的 admin 進入 phpmyadmin
4. 點新增 tutor_platform 資料庫，並匯入 tutor_platform.sql
5. 開啟 http://localhost/...(專案放置路徑)

## 除錯

1. 請先自行在根目錄建立 .env 檔案，將 .env.example 內容複製過去並更改 <br>

2. 如果驗證碼沒顯示出來: <br>
開啟 XAMPP Control Panel -> 點擊 Apache 旁的 config -> 選擇 PHP(php.ini) -> 將 ;extension=gd 這行的分號刪掉 -> 存檔 -> 重啟  Apache

