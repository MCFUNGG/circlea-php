<?php
header("Content-Type: application/json");

// Database connection parameters
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

try {
    mysqli_begin_transaction($connect);

    // 获取 POST 参数
    $case_id = $_POST['case_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $reason = $_POST['reason'] ?? null;

    // 验证必要参数
    if (!$case_id || !$student_id) {
        throw new Exception("Required parameters are missing.");
    }

    // 获取需要重置的 booking 记录
    $booking_query = "SELECT b.booking_id 
                     FROM booking b
                     JOIN first_lesson fl ON b.booking_id = fl.booking_id
                     WHERE b.match_id = ? 
                     AND b.student_id = ? 
                     AND b.status = 'confirmed'
                     AND (fl.ps_response = 'incomplete' AND fl.t_response = 'incomplete')
                     ORDER BY b.booking_id ASC 
                     LIMIT 1";
    
    $booking_stmt = mysqli_prepare($connect, $booking_query);
    mysqli_stmt_bind_param($booking_stmt, "ss", $case_id, $student_id);
    mysqli_stmt_execute($booking_stmt);
    $booking_result = mysqli_stmt_get_result($booking_stmt);
    $booking_row = mysqli_fetch_assoc($booking_result);

    if (!$booking_row) {
        throw new Exception("No booking found that needs to be reset");
    }

    $booking_id = $booking_row['booking_id'];

    // 删除 first_lesson 记录
    //$delete_lesson_query = "DELETE FROM first_lesson WHERE booking_id = ?";
    //$delete_lesson_stmt = mysqli_prepare($connect, $delete_lesson_query);
    //mysqli_stmt_bind_param($delete_lesson_stmt, "i", $booking_id);
    
    //if (!mysqli_stmt_execute($delete_lesson_stmt)) {
    //    throw new Exception("Failed to delete first lesson record");
    //}

    // 更新 booking 状态为 available 并清除 student_id
    $reset_booking_query = "UPDATE booking 
                           SET status = 'available', 
                               student_id = NULL 
                           WHERE booking_id = ?";
    $reset_booking_stmt = mysqli_prepare($connect, $reset_booking_query);
    mysqli_stmt_bind_param($reset_booking_stmt, "i", $booking_id);
    
    if (!mysqli_stmt_execute($reset_booking_stmt)) {
        throw new Exception("Failed to reset booking status");
    }

    mysqli_commit($connect);
    
    echo json_encode([
        "success" => true,
        "message" => "Booking status reset successfully",
        "booking_id" => $booking_id
    ]);

} catch (Exception $e) {
    mysqli_rollback($connect);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($booking_stmt)) mysqli_stmt_close($booking_stmt);
    //if (isset($delete_lesson_stmt)) mysqli_stmt_close($delete_lesson_stmt);
    if (isset($reset_booking_stmt)) mysqli_stmt_close($reset_booking_stmt);
    mysqli_close($connect);
}
?>