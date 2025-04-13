<?php
header("Content-Type: application/json");

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

    // Get parameters from POST request
    $match_id = $_POST['case_id'] ?? null;
    $tutor_id = $_POST['tutor_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $next_action = $_POST['next_action'] ?? null;

    // Validate required parameters
    if (!$match_id || !$tutor_id || !$status) {
        throw new Exception("Required parameters are missing.");
    }

    // Validate status
    $valid_statuses = ['completed', 'incomplete'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception("Invalid status value");
    }

    // Get the confirmed booking_id
    $booking_query = "SELECT booking_id FROM booking 
                     WHERE match_id = ? 
                     AND tutor_id = ? 
                     AND status = 'confirmed'";
    
    $booking_stmt = mysqli_prepare($connect, $booking_query);
    if (!$booking_stmt) {
        throw new Exception("Booking query preparation failed: " . mysqli_error($connect));
    }

    mysqli_stmt_bind_param($booking_stmt, "ss", $match_id, $tutor_id);
    if (!mysqli_stmt_execute($booking_stmt)) {
        throw new Exception("Booking query execution failed: " . mysqli_stmt_error($booking_stmt));
    }

    $booking_result = mysqli_stmt_get_result($booking_stmt);
    $booking_row = mysqli_fetch_assoc($booking_result);

    if (!$booking_row) {
        throw new Exception("No confirmed booking found for this case and tutor");
    }

    $booking_id = $booking_row['booking_id'];

    // Check if record exists in first_lesson table
    $check_exists = "SELECT * FROM first_lesson WHERE booking_id = ?";
    $check_stmt = mysqli_prepare($connect, $check_exists);
    if (!$check_stmt) {
        throw new Exception("Check query preparation failed: " . mysqli_error($connect));
    }

    mysqli_stmt_bind_param($check_stmt, "i", $booking_id);
    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception("Check query execution failed: " . mysqli_stmt_error($check_stmt));
    }

    $exists_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($exists_result) == 0) {
        // Insert new record
        $insert_query = "INSERT INTO first_lesson (booking_id, t_response, t_reason, t_response_time, next_action) 
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)";
        $stmt = mysqli_prepare($connect, $insert_query);
        if (!$stmt) {
            throw new Exception("Insert statement preparation failed: " . mysqli_error($connect));
        }
        mysqli_stmt_bind_param($stmt, "isss", $booking_id, $status, $reason, $next_action);
    } else {
        // Update existing record
        $update_query = "UPDATE first_lesson 
                        SET t_response = ?, 
                            t_reason = ?,
                            t_response_time = CURRENT_TIMESTAMP,
                            next_action = ?
                        WHERE booking_id = ?";
        $stmt = mysqli_prepare($connect, $update_query);
        if (!$stmt) {
            throw new Exception("Update statement preparation failed: " . mysqli_error($connect));
        }
        mysqli_stmt_bind_param($stmt, "sssi", $status, $reason, $next_action, $booking_id);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Statement execution failed: " . mysqli_stmt_error($stmt));
    }

    // Check both responses
    $check_responses = "SELECT ps_response, t_response, ps_reason, t_reason FROM first_lesson WHERE booking_id = ?";
    $response_stmt = mysqli_prepare($connect, $check_responses);
    if (!$response_stmt) {
        throw new Exception("Response check preparation failed: " . mysqli_error($connect));
    }

    mysqli_stmt_bind_param($response_stmt, "i", $booking_id);
    if (!mysqli_stmt_execute($response_stmt)) {
        throw new Exception("Response check execution failed: " . mysqli_stmt_error($response_stmt));
    }

    $response_result = mysqli_stmt_get_result($response_stmt);
    $response_row = mysqli_fetch_assoc($response_result);

    $response = ["success" => true];

    // Handle all possible response scenarios
    if ($response_row['ps_response'] && $response_row['t_response']) {
        if ($response_row['ps_response'] === 'completed' && $response_row['t_response'] === 'completed') {
            $response["status"] = "completed";
            $response["message"] = "Lesson completed successfully. The fee will be sent to you within 3 days";
            
            // Update booking status
            $update_booking = "UPDATE booking SET status = 'completed' WHERE booking_id = ?";
            $update_stmt = mysqli_prepare($connect, $update_booking);
            if (!$update_stmt) {
                throw new Exception("Update booking statement preparation failed: " . mysqli_error($connect));
            }
            mysqli_stmt_bind_param($update_stmt, "i", $booking_id);
            mysqli_stmt_execute($update_stmt);
            
        } else if ($response_row['ps_response'] === 'incomplete' && $response_row['t_response'] === 'incomplete') {
            $response["status"] = "both_incomplete";
            $response["message"] = "Both parties marked lesson as incomplete";
            $response["student_reason"] = $response_row['ps_reason'];
            $response["tutor_reason"] = $response_row['t_reason'];
            
        } else {
            $response["status"] = "conflict";
            if ($response_row['t_response'] === 'completed') {
                $response["message"] = "Status conflict: Student marked lesson as incomplete. Admin review required.";
                $response["student_reason"] = $response_row['ps_reason'];
            } else {
                $response["message"] = "Status conflict: You marked lesson as incomplete but student marked as complete. Admin review required.";
                $response["tutor_reason"] = $response_row['t_reason'];
            }
        }
    } else {
        $response["status"] = "waiting";
        $response["message"] = "Status updated. Waiting for student's response.";
    }

    mysqli_commit($connect);
    echo json_encode($response);

} catch (Exception $e) {
    mysqli_rollback($connect);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
} finally {
    // Clean up - only close statements if they were successfully created
    if (isset($stmt) && $stmt instanceof mysqli_stmt) mysqli_stmt_close($stmt);
    if (isset($check_stmt) && $check_stmt instanceof mysqli_stmt) mysqli_stmt_close($check_stmt);
    if (isset($response_stmt) && $response_stmt instanceof mysqli_stmt) mysqli_stmt_close($response_stmt);
    if (isset($booking_stmt) && $booking_stmt instanceof mysqli_stmt) mysqli_stmt_close($booking_stmt);
    if (isset($update_stmt) && $update_stmt instanceof mysqli_stmt) mysqli_stmt_close($update_stmt);
    mysqli_close($connect);
}
?>  