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

// Get POST data
$memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;
$amount = isset($_POST['amount']) ? $_POST['amount'] : null;
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;
$paymentStatus = isset($_POST['payment_status']) ? $_POST['payment_status'] : null;
$transactionId = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : null;

// Validate required fields
if (!$memberId || !$amount || !$paymentMethod || !$paymentStatus || !$transactionId) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

// Start transaction
mysqli_begin_transaction($connect);

try {
    // Insert payment record
    $query = "INSERT INTO payment (member_id, amount, payment_method, payment_status, transaction_id, payment_date) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "sdsss", $memberId, $amount, $paymentMethod, $paymentStatus, $transactionId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to insert payment record: " . mysqli_error($connect));
    }
    
    $paymentId = mysqli_insert_id($connect);
    
    // Update member status if payment successful
    if ($paymentStatus === 'completed') {
        $updateQuery = "UPDATE member SET status = 'A' WHERE member_id = ?";
        $updateStmt = mysqli_prepare($connect, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "s", $memberId);
        
        if (!mysqli_stmt_execute($updateStmt)) {
            throw new Exception("Failed to update member status: " . mysqli_error($connect));
        }
    }
    
    // Commit transaction
    mysqli_commit($connect);
    
    echo json_encode([
        "success" => true,
        "message" => "Payment processed successfully",
        "payment_id" => $paymentId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($connect);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

// Close the database connection
mysqli_close($connect);
?>