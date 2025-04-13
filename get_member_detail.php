<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 获取member_id参数
$member_id = isset($_GET['member_id']) ? $_GET['member_id'] : '';

if (empty($member_id)) {
    echo json_encode(["success" => false, "message" => "Member ID is required"]);
    exit;
}

// 使用配置的连接函数获取数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// 准备SQL查询
$query = "SELECT * FROM member WHERE member_id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $member = $result->fetch_assoc();
    echo json_encode(["success" => true, "data" => $member]);
} else {
    echo json_encode(["success" => false, "message" => "Member not found"]);
}

// 关闭数据库连接
mysqli_close($connect);
?>