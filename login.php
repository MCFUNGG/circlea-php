<?php
header('Content-Type: application/json');

// 引入数据库配置文件
require_once 'db_config.php';

// Get data from POST request
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';

// 使用配置的连接函数获取数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["error" => "Sorry, unable to connect to database server"]);
    exit;
}

// Run the query to get the password hash based on the email
$query = "SELECT member_id, password FROM member WHERE email = '$email'";
$result = mysqli_query($connect, $query);

// Check if the query was successful
if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed"]);
    exit;
}

// Check if a result was returned
if (mysqli_num_rows($result) === 0) {
    echo json_encode(["success" => false, "message" => "No user found with that email"]);
    exit;
}

// Fetch the stored password hash
$row = mysqli_fetch_assoc($result);
$storedPassword = $row['password'];

// Verify the provided password against the stored hash
if (password_verify($password, $storedPassword)) {
    $member_id = $row['member_id'];
    echo json_encode(["success" => true, "message" => "Login successful", "member_id" => $member_id]);
} else {
    echo json_encode(["success" => false,   "message" => "Invalid email or password"]);
}

// Close the connection
mysqli_close($connect);
?>