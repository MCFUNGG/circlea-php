<?php
header("Content-Type: application/json");



// 定義支付狀態常量
class PaymentStatus {
    const PENDING = 'pending';
    const NOT_SUBMITTED = 'not_submitted';
    const CONFIRMED = 'confirmed';
    const REJECTED = 'rejected';
}

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

$match_id = $_POST['match_id'] ?? null;
$student_id = $_POST['student_id'] ?? null;

if (!$match_id || !$student_id) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required parameters"
    ]);
    exit;
}

// 使用預處理語句防止 SQL 注入
$query = "SELECT p.*, m.tutor_id, mb.username as tutor_name,
          CASE 
              WHEN p.status = 'not_submitted' THEN 'Not Submitted'
              WHEN p.status = 'pending' THEN 'Pending Verification'
              WHEN p.status = 'confirmed' THEN 'Payment Verified'
              WHEN p.status = 'rejected' THEN 'Payment Rejected'
              ELSE 'Payment Required'
          END as status_text
          FROM payment p 
          JOIN `match` m ON p.match_id = m.match_id 
          JOIN member mb ON m.tutor_id = mb.member_id
          WHERE p.match_id = ? AND p.student_id = ?";

$stmt = mysqli_prepare($connect, $query);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Query preparation failed: " . mysqli_error($connect)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $match_id, $student_id);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => false, 
        "message" => "Query execution failed: " . mysqli_error($connect)
    ]);
    exit;
}

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $payment = mysqli_fetch_assoc($result);
    
    // 格式化時間戳
    $submitted_at = $payment['submitted_at'] ? 
        date('Y-m-d H:i:s', strtotime($payment['submitted_at'])) : null;
    $verified_at = $payment['verified_at'] ? 
        date('Y-m-d H:i:s', strtotime($payment['verified_at'])) : null;

    echo json_encode([
        "success" => true,
        "data" => [
            "payment_id" => $payment['payment_id'],
            "match_id" => $payment['match_id'],
            "student_id" => $payment['student_id'],
            "tutor_id" => $payment['tutor_id'],
            "tutor_name" => $payment['tutor_name'],
            "amount" => $payment['amount'],
            "status" => $payment['status'],
            "status_text" => $payment['status_text'],
            "receipt_path" => $payment['receipt_path'],
            "submitted_at" => $submitted_at,
            "verified_at" => $verified_at
        ]
    ]);
} else {
    // 如果沒有找到記錄，返回默認狀態
    echo json_encode([
        "success" => false,
        "message" => "No payment record found",
        "data" => [
            "status" => "not_submitted",
            "status_text" => "Payment Required"
        ]
    ]);
}

// 添加調試日誌
error_log("Payment check for match_id: $match_id, student_id: $student_id");
if (isset($payment)) {
    error_log("Payment status: " . $payment['status']);
    error_log("Receipt path: " . ($payment['receipt_path'] ?? 'null'));
}

mysqli_stmt_close($stmt);
mysqli_close($connect);
?>