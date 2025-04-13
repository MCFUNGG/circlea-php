<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 使用配置的连接函数获取数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// 获取POST参数
$match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;

if (!$match_id || !$status) {
    echo json_encode(["success" => false, "message" => "Missing required parameters"]);
    exit;
}

try {
    // 更新学生第一课状态
    $query = "UPDATE match_case SET student_first_lesson_status = ? WHERE match_id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("si", $status, $match_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Student first lesson status updated successfully"
        ]);
    } else {
        throw new Exception("Failed to update status: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

// 关闭数据库连接
mysqli_close($connect);
?>