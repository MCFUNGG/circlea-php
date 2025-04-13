<?php
// 引入数据库配置文件
require_once 'db_config.php';

// 使用配置的连接函数获取数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// 获取上传的文件和其他参数
$file = $_FILES['cert_file'];
$member_id = $_POST['member_id'];
$description = $_POST['description'];

// 检查文件是否成功上传
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "success" => false,
        "message" => "File upload failed with error code: " . $file['error']
    ]);
    exit;
}

// 创建上传目录（如果不存在）
$uploadDir = './uploads/certs/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 生成唯一的文件名
$timestamp = time();
$originalName = $file['name'];
$newFileName = $timestamp . '_' . $originalName;
$targetPath = $uploadDir . $newFileName;

// 移动文件到目标位置
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // 更新数据库
    $query = "INSERT INTO member_cert (member_id, cert_file, description, created_time, status) 
              VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'P')";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("iss", $member_id, $newFileName, $description);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Certificate uploaded successfully",
            "file_path" => $targetPath
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database update failed: " . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to move uploaded file"
    ]);
}

// 关闭数据库连接
mysqli_close($connect);
?>