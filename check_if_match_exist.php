<?php
header("Content-Type: application/json");
// 引入数据库配置文件
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get and validate input parameters
$tutorAppId = isset($_POST["tutor_app_id"]) ? trim($_POST["tutor_app_id"]) : null;
$psAppId = isset($_POST["ps_app_id"]) ? trim($_POST["ps_app_id"]) : null;

// Log received parameters (for debugging)
error_log("Received parameters - Tutor App ID: $tutorAppId, PS App ID: $psAppId");

if ($tutorAppId === null || $psAppId === null) {
    echo json_encode(["success" => false, "message" => "Missing required parameters"]);
    exit;
}

// Query to check if a match exists
$stmt = $connect->prepare("SELECT match_id FROM `match` WHERE tutor_app_id = ? AND ps_app_id = ?");
$stmt->bind_param("ss", $tutorAppId, $psAppId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

// Check if any match_id was found
if ($result->num_rows > 0) {
    echo json_encode(["success" => true, "message" => "This application has already been submitted."]);
} else {    
    echo json_encode(["success" => false, "message" => "No existing match found"]);
}

// Close database connection
$stmt->close();
mysqli_close($connect);
?>