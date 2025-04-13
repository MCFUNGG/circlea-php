<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed: " . mysqli_connect_error()
    ]);
    exit;
}

// Get tutor_id from POST
$tutorId = isset($_POST['tutor_id']) ? $_POST['tutor_id'] : null;

if ($tutorId === null) {
    echo json_encode([
        "success" => false, 
        "message" => "Tutor ID not provided"
    ]);
    exit;
}

// Query to get tutor information
$query = "SELECT m.username as name, m.phone, m.email 
          FROM member m 
          WHERE m.member_id = ?";

$stmt = $connect->prepare($query);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Query preparation failed: " . mysqli_error($connect)
    ]);
    exit;
}

$stmt->bind_param("s", $tutorId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode([
        "success" => false, 
        "message" => "Query failed: " . mysqli_error($connect)
    ]);
    exit;
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "success" => true,
        "tutor" => [
            "name" => $row['name'],
            "phone" => $row['phone'],
            "email" => $row['email']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "No tutor found with this ID"
    ]);
}

// Close connections
$stmt->close();
mysqli_close($connect);
?>