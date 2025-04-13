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

// 验证match_id参数
$match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;
$member_id = isset($_POST['member_id']) ? $_POST['member_id'] : null;

if (!$match_id || !$member_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// 查询数据库获取tutor联系信息
$query = "SELECT m.username, m.phone, m.email
          FROM match_case mc
          JOIN member m ON mc.tutor_id = m.member_id
          WHERE mc.match_id = ? AND mc.ps_id = ? AND mc.status = 'A'";

$stmt = mysqli_prepare($connect, $query);
mysqli_stmt_bind_param($stmt, "ss", $match_id, $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $contact_info = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'data' => $contact_info
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No contact information found or not authorized to view'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($connect);
?>