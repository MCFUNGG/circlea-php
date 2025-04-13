<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    mysqli_begin_transaction($connect);

    $match_id = $_POST['case_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $next_action = $_POST['next_action'] ?? null;

    if (!$match_id || !$student_id || !$status) {
        throw new Exception("Required parameters are missing.");
    }

    $valid_statuses = ['completed', 'incomplete'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception("Invalid status value");
    }

    $booking_query = "SELECT b.booking_id 
                     FROM booking b 
                     WHERE b.match_id = ? 
                     AND b.student_id = ? 
                     AND b.status = 'confirmed'";

    $booking_stmt = mysqli_prepare($connect, $booking_query);
    mysqli_stmt_bind_param($booking_stmt, "ss", $match_id, $student_id);
    mysqli_stmt_execute($booking_stmt);
    $booking_result = mysqli_stmt_get_result($booking_stmt);
    $booking_row = mysqli_fetch_assoc($booking_result);

    if (!$booking_row) {
        throw new Exception("No confirmed booking found");
    }

    $booking_id = $booking_row['booking_id'];

    // Check if record exists and get current responses
    $check_query = "SELECT * FROM first_lesson WHERE booking_id = ?";
    $check_stmt = mysqli_prepare($connect, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $booking_id);
    mysqli_stmt_execute($check_stmt);
    $exists_result = mysqli_stmt_get_result($check_stmt);
    $existing_record = mysqli_fetch_assoc($exists_result);

    if (!$existing_record) {
        // First response - Insert with deadline
        $insert_query = "INSERT INTO first_lesson (booking_id, ps_response, ps_reason, ps_response_time, response_deadline) 
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 72 HOUR))";
        $stmt = mysqli_prepare($connect, $insert_query);
        mysqli_stmt_bind_param($stmt, "iss", $booking_id, $status, $reason);
    } else {
        if (!$existing_record['ps_response'] && !$existing_record['t_response']) {
            // First response - Update with deadline
            $update_query = "UPDATE first_lesson 
                           SET ps_response = ?, 
                               ps_reason = ?,
                               ps_response_time = CURRENT_TIMESTAMP,
                               response_deadline = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 72 HOUR)
                           WHERE booking_id = ?";
        } else {
            // Subsequent response - Normal update
            $update_query = "UPDATE first_lesson 
                           SET ps_response = ?, 
                               ps_reason = ?,
                               ps_response_time = CURRENT_TIMESTAMP
                           WHERE booking_id = ?";
        }
        $stmt = mysqli_prepare($connect, $update_query);
        mysqli_stmt_bind_param($stmt, "ssi", $status, $reason, $booking_id);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update lesson status");
    }

    // Get both responses to check status
    $response_query = "SELECT ps_response, t_response, response_deadline FROM first_lesson WHERE booking_id = ?";
    $response_stmt = mysqli_prepare($connect, $response_query);
    mysqli_stmt_bind_param($response_stmt, "i", $booking_id);
    mysqli_stmt_execute($response_stmt);
    $response_result = mysqli_stmt_get_result($response_stmt);
    $response_row = mysqli_fetch_assoc($response_result);

    $response = [
        "success" => true,
        "booking_id" => $booking_id
    ];

    if ($response_row['ps_response'] && $response_row['t_response']) {
        if ($response_row['ps_response'] === 'completed' && $response_row['t_response'] === 'completed') {
            $response["status"] = "completed";
            $response["message"] = "Lesson completed successfully";
        } else if ($response_row['ps_response'] === 'incomplete' && $response_row['t_response'] === 'incomplete') {
            $response["status"] = "both_incomplete";
            $response["message"] = "Both parties marked lesson as incomplete";
        } else {
            $response["status"] = "conflict";
            $response["message"] = "Responses don't match. Admin review required.";
        }
    } else {
        $response["status"] = "waiting";
        $response["message"] = "Status updated. Waiting for other party's response.";
        if ($response_row['response_deadline']) {
            $response["deadline"] = $response_row['response_deadline'];
        }
    }

    if ($status === 'incomplete' && $next_action) {
        $action_query = "UPDATE first_lesson SET next_action = ? WHERE booking_id = ?";
        $action_stmt = mysqli_prepare($connect, $action_query);
        mysqli_stmt_bind_param($action_stmt, "si", $next_action, $booking_id);
        mysqli_stmt_execute($action_stmt);
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
    if (isset($stmt)) mysqli_stmt_close($stmt);
    if (isset($check_stmt)) mysqli_stmt_close($check_stmt);
    if (isset($response_stmt)) mysqli_stmt_close($response_stmt);
    if (isset($booking_stmt)) mysqli_stmt_close($booking_stmt);
    if (isset($action_stmt)) mysqli_stmt_close($action_stmt);
    mysqli_close($connect);
}
?>