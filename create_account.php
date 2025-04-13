<?php
header("Content-Type: application/json"); // Set response type

// 引入数据库配置文件
require_once 'db_config.php';

// Get POST data
$username = trim($_POST["username"]);
$email = trim($_POST["email"]);
$phoneNumber = trim($_POST["phone"]); 
$accountPassword = trim($_POST["password"]); 

// Validate input
if (empty($username) || empty($email) || empty($phoneNumber) || empty($accountPassword)) {
    die(json_encode(["success" => false, "message" => "All fields are required."]));
}

// Encrypt the password
$hashedPassword = password_hash($accountPassword, PASSWORD_DEFAULT);

// 创建数据库连接
$connect = getDbConnection();
if (!$connect) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]));
}

// Check for existing email
$checkEmailQuery = "SELECT * FROM member WHERE email = '$email'";
$checkEmailResult = mysqli_query($connect, $checkEmailQuery);
if (mysqli_num_rows($checkEmailResult) > 0) {
    die(json_encode(["success" => false, "message" => "Email already exists."]));
}

// Insert new member
$queryDetails = "INSERT INTO member (isAdmin, status, email, phone, password, username) 
            VALUES ('N', 'I', '$email', '$phoneNumber', '$hashedPassword', '$username')";
$resultDetails = mysqli_query($connect, $queryDetails);

// Check if the query was successful
if ($resultDetails) {
    echo json_encode(["success" => true, "message" => "Account created successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to create account! SQL Error: " . mysqli_error($connect)]);
}

// Close connection
mysqli_close($connect);
?>