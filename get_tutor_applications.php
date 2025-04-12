<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

// Check connection
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get member_id from POST request
$memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;

if (!$memberId) {
    echo json_encode(["success" => false, "message" => "Member ID is required"]);
    exit;
}

// Query to get tutor applications
$query = "SELECT * FROM application WHERE member_id = '$memberId' AND app_creator = 'T'";
$result = mysqli_query($connect, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

$applications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $applications
]);

// Close the database connection
mysqli_close($connect);
?> 