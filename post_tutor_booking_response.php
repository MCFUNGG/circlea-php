<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

try {
    // Log received POST data
    error_log("Received POST data: " . print_r($_POST, true));

    // Validate required parameters
    if (!isset($_POST['booking_id']) || !isset($_POST['action'])) {
        throw new Exception("Missing required parameters");
    }

    $bookingId = trim($_POST['booking_id']);
    $action = trim($_POST['action']);

    // Validate action
    if (!in_array($action, ['accept', 'reject'])) {
        throw new Exception("Invalid action parameter");
    }

    error_log("Processing booking response: booking_id={$bookingId}, action={$action}");

    // Database connection
    require_once 'db_config.php';

    // 创建数据库连接
    $connect = getDbConnection();
    if (!$connect) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Start transaction
    mysqli_begin_transaction($connect);

    try {
        // First, verify the booking exists and is in pending status
        $verifyQuery = "SELECT status FROM booking WHERE booking_id = ? AND status = 'pending'";
        $stmt = mysqli_prepare($connect, $verifyQuery);
        if (!$stmt) {
            throw new Exception("Prepare verify query failed: " . mysqli_error($connect));
        }

        mysqli_stmt_bind_param($stmt, "s", $bookingId);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute verify query failed: " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) === 0) {
            throw new Exception("Booking not found or not in pending status");
        }
        mysqli_stmt_close($stmt);

        if ($action === 'accept') {
            // For accept action: set status to confirmed
            $newStatus = 'confirmed';
            $updateQuery = "UPDATE booking 
                           SET status = ?,
                               updated_at = NOW()
                           WHERE booking_id = ?";
            $stmt = mysqli_prepare($connect, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ss", $newStatus, $bookingId);
        } else {
            // For reject action: set status to available and clear student_id
            $newStatus = 'available';
            $updateQuery = "UPDATE booking 
                           SET status = ?,
                               student_id = NULL,
                               updated_at = NOW()
                           WHERE booking_id = ?";
            $stmt = mysqli_prepare($connect, $updateQuery);
            mysqli_stmt_bind_param($stmt, "ss", $newStatus, $bookingId);
        }

        if (!$stmt) {
            throw new Exception("Prepare update query failed: " . mysqli_error($connect));
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute update query failed: " . mysqli_stmt_error($stmt));
        }

        if (mysqli_affected_rows($connect) === 0) {
            throw new Exception("No booking was updated");
        }

        // Commit transaction
        mysqli_commit($connect);

        $response = [
            "success" => true,
            "message" => "Booking " . ($action === 'accept' ? "accepted" : "rejected") . " successfully",
            "status" => $newStatus
        ];

    } catch (Exception $e) {
        mysqli_rollback($connect);
        throw $e;
    }

    error_log("Sending response: " . print_r($response, true));
    echo json_encode($response);

    mysqli_close($connect);

} catch (Exception $e) {
    error_log("Error in post_tutor_booking_response.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "debug" => [
            "error" => $e->getMessage(),
            "post_data" => $_POST
        ]
    ]);
}
?>