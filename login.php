<?php
// 添加错误显示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 记录请求
file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - 收到请求\n", FILE_APPEND);

header('Content-Type: application/json');
require_once 'db_config.php';

// 获取并记录POST数据
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';

file_put_contents('login_log.txt', "尝试登录: Email=$email, Password=$password\n", FILE_APPEND);

// 数据库连接
$connect = getDbConnection();
if (!$connect) {
    echo json_encode(["success" => false, "message" => "数据库连接失败"]);
    exit;
}

// 使用参数化查询避免SQL注入
$stmt = mysqli_prepare($connect, "SELECT member_id, password FROM member WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 检查查询结果
if (!$result) {
    file_put_contents('login_log.txt', "查询失败: " . mysqli_error($connect) . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "查询失败"]);
    exit;
}

// 检查是否找到用户
if (mysqli_num_rows($result) === 0) {
    file_put_contents('login_log.txt', "未找到用户: $email\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "未找到此邮箱对应的用户"]);
    exit;
}

// 获取存储的密码
$row = mysqli_fetch_assoc($result);
$storedPassword = $row['password'];
$member_id = $row['member_id'];

file_put_contents('login_log.txt', "找到用户: member_id=$member_id, 存储的密码前10位=" . substr($storedPassword, 0, 10) . "...\n", FILE_APPEND);

// 直接比较密码(临时测试，不安全)
if ($password == $storedPassword) {
    file_put_contents('login_log.txt', "登录成功: 直接密码匹配\n", FILE_APPEND);
    echo json_encode(["success" => true, "message" => "登录成功", "member_id" => $member_id]);
}
// 尝试使用password_verify
else if (password_verify($password, $storedPassword)) {
    file_put_contents('login_log.txt', "登录成功: password_verify匹配\n", FILE_APPEND);
    echo json_encode(["success" => true, "message" => "登录成功", "member_id" => $member_id]);
}
else {
    file_put_contents('login_log.txt', "密码错误\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "邮箱或密码不正确"]);
}

mysqli_close($connect);
?>
