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

// Query to get cart items
$query = "SELECT * FROM member_cart WHERE member_id = '$memberId'";
$result = mysqli_query($connect, $query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

$cartItems = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cartItems[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $cartItems
]);

// Close the database connection
mysqli_close($connect);
?>