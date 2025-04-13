<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

header("Content-Type: application/json");

try {
    // Database connection
    require_once 'db_config.php';

    // 创建数据库连接
    $connect = getDbConnection();
    if (!$connect) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Validate required parameters
    if (!isset($_POST['match_id']) || !isset($_POST['slot_id']) || !isset($_POST['student_id'])) {
        throw new Exception("Missing required parameters");
    }

    $matchId = mysqli_real_escape_string($connect, $_POST['match_id']);
    $slotId = mysqli_real_escape_string($connect, $_POST['slot_id']);
    $studentId = mysqli_real_escape_string($connect, $_POST['student_id']);

    mysqli_begin_transaction($connect);

    // First check if the slot is still available
    $checkQuery = "SELECT status FROM booking 
                  WHERE booking_id = ? 
                  AND match_id = ? 
                  AND status = 'available'";

    $stmt = mysqli_prepare($connect, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ss", $slotId, $matchId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error checking slot availability");
    }

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        throw new Exception("This time slot is no longer available");
    }

    // Check if student already has a pending or confirmed booking for this match
    $checkExistingQuery = "SELECT status FROM booking 
                          WHERE match_id = ? 
                          AND student_id = ? 
                          AND status IN ('pending', 'confirmed')";

    $stmt = mysqli_prepare($connect, $checkExistingQuery);
    mysqli_stmt_bind_param($stmt, "ss", $matchId, $studentId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error checking existing bookings");
    }

    $existingResult = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($existingResult) > 0) {
        throw new Exception("You already have a pending or confirmed booking for this case");
    }

    // Update the slot with student's booking request
    $updateQuery = "UPDATE booking 
                   SET status = 'pending',
                       student_id = ?,
                       updated_at = NOW()
                   WHERE booking_id = ? 
                   AND match_id = ? 
                   AND status = 'available'";

    $stmt = mysqli_prepare($connect, $updateQuery);
    mysqli_stmt_bind_param($stmt, "sss", $studentId, $slotId, $matchId);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update booking status");
    }

    if (mysqli_affected_rows($connect) === 0) {
        throw new Exception("Failed to book the time slot. Please try again.");
    }

    // If everything is successful, commit the transaction
    mysqli_commit($connect);

    // Send success response
    echo json_encode([
        "success" => true,
        "message" => "Booking request sent successfully"
    ]);

} catch (Exception $e) {
    // If there's an error, rollback the transaction
    if (isset($connect)) {
        mysqli_rollback($connect);
    }
    
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($connect)) {
        mysqli_close($connect);
    }
}
?>