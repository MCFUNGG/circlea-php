<?php
// 設定資料庫連接參數

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();


// 檢查連接是否成功
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// 檢查請求方法和檔案是否存在
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // 從 POST 請求獲取 memberId
    $memberId = $_POST['memberId']; 
    $description = isset($_POST['description']) ? $_POST['description'] : "n"; // 默認描述

    // 檢查檔案是否有錯誤
    if ($fileError === 0) {
        // 檔案儲存的路徑
        $uploadDir = '../upload_cert/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // 創建上傳目錄
        }
        $fileDestination = $uploadDir . basename($fileName);

        // 嘗試移動檔案
        if (move_uploaded_file($fileTmpName, $fileDestination)) {
            echo "檔案已成功移動到 $fileDestination\n"; // 日誌輸出

            // 準備 SQL 插入語句，省略 member_cert_id
            $sql = "INSERT INTO member_cert (member_id, cert_file, description, status) VALUES (?, ?, ?, 'W')";
            $stmt = $connect->prepare($sql);
            if ($stmt === false) {
                die("SQL prepare failed: " . $connect->error);
            }

            // 綁定參數
            $stmt->bind_param("iss", $memberId, $fileName, $description);

            // 執行語句並檢查結果
            if ($stmt->execute()) {
                echo "檔案上傳成功，並已儲存到資料庫。";
            } else {
                echo "資料庫儲存錯誤: " . $stmt->error; // 獲取錯誤資訊
            }

            $stmt->close();
        } else {
            echo "檔案移動失敗。";
        }
    } else {
        echo "檔案上傳錯誤代碼: " . $fileError;
    }
} else {
    echo "未接收到檔案或請求方法不正確。";
}

$connect->close();
?>