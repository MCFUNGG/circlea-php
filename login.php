<?php
// 添加错误显示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 记录请求
file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

header('Content-Type: application/json');
require_once 'db_config.php';

// 获取并记录POST数据
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';

file_put_contents('login_log.txt', "Login attempt: Email=$email, Password length=" . strlen($password) . "\n", FILE_APPEND);

// 数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// 使用参数化查询避免SQL注入
$stmt = mysqli_prepare($connect, "SELECT member_id, password FROM member WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 检查查询结果
if (!$result) {
    file_put_contents('login_log.txt', "Query failed: " . mysqli_error($connect) . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Query failed"]);
    exit;
}

// 检查是否找到用户
if (mysqli_num_rows($result) === 0) {
    file_put_contents('login_log.txt', "No user found: $email\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "No user found with that email"]);
    exit;
}

// 获取存储的密码
$row = mysqli_fetch_assoc($result);
$storedPassword = $row['password'];
$member_id = $row['member_id'];

file_put_contents('login_log.txt', "User found: member_id=$member_id, Stored password first 10 chars=" . substr($storedPassword, 0, 10) . "...\n", FILE_APPEND);

// 直接比较密码(临时测试)
if ($password == $storedPassword) {
    file_put_contents('login_log.txt', "Login successful: Direct password match\n", FILE_APPEND);
    echo json_encode(["success" => true, "message" => "Login successful", "member_id" => $member_id]);
}
// 尝试使用password_verify
else if (password_verify($password, $storedPassword)) {
    file_put_contents('login_log.txt', "Login successful: password_verify match\n", FILE_APPEND);
    echo json_encode(["success" => true, "message" => "Login successful", "member_id" => $member_id]);
}
else {
    file_put_contents('login_log.txt', "Password incorrect\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
}

mysqli_close($connect);
?>
