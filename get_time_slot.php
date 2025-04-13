<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

try {
    error_log("Received POST data: " . print_r($_POST, true));

    if (!isset($_POST['match_id'])) {
        throw new Exception("Missing match_id parameter");
    }
    
    $isTutor = isset($_POST['is_tutor']) ? filter_var($_POST['is_tutor'], FILTER_VALIDATE_BOOLEAN) : false;
    $matchId = (int)trim($_POST['match_id']);

    error_log("Processing request with match_id: {$matchId}, is_tutor: " . ($isTutor ? 'true' : 'false'));

    require_once 'db_config.php';

    // 创建数据库连接
    $connect = getDbConnection();
    if (!$connect) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get available time slots
    $availableQuery = "SELECT b.* 
                      FROM booking b 
                      WHERE b.match_id = ? AND b.status = 'available'";

    $stmt = mysqli_prepare($connect, $availableQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed for available slots: " . mysqli_error($connect));
    }

    mysqli_stmt_bind_param($stmt, "i", $matchId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed for available slots: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $availableSlots = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $startDateTime = $row['date'] . ' ' . $row['start_time'];
        $endDateTime = $row['date'] . ' ' . $row['end_time'];
        
        $availableSlots[] = array(
            'slot_id' => $row['booking_id'],
            'tutor_id' => $row['tutor_id'],
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'status' => $row['status'],
            'student_id' => $row['student_id'] ?? '',
            'created_at' => $row['created_at']
        );
    }
    mysqli_stmt_close($stmt);

    // Get pending requests with student names
    if ($isTutor) {
        $pendingQuery = "SELECT b.*, m.username as student_name 
                        FROM booking b 
                        LEFT JOIN member m ON b.student_id = m.member_id
                        WHERE b.match_id = ? AND b.status = 'pending'";

        $stmt = mysqli_prepare($connect, $pendingQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed for pending requests: " . mysqli_error($connect));
        }

        mysqli_stmt_bind_param($stmt, "i", $matchId);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute failed for pending requests: " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $pendingRequests = array();
        
        while ($row = mysqli_fetch_assoc($result)) {
            $startDateTime = $row['date'] . ' ' . $row['start_time'];
            $endDateTime = $row['date'] . ' ' . $row['end_time'];
            
            $pendingRequests[] = array(
                'slot_id' => $row['booking_id'],
                'student_id' => $row['student_id'],
                'student_name' => $row['student_name'],
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'status' => $row['status'],
                'created_at' => $row['created_at']
            );
        }
        mysqli_stmt_close($stmt);
    }

    $response = [
        "success" => true,
        "message" => "Data retrieved successfully",
        "slots" => $availableSlots
    ];

    if ($isTutor) {
        $response["requests"] = $pendingRequests;
    }

    error_log("Sending response: " . print_r($response, true));
    echo json_encode($response);

    mysqli_close($connect);

} catch (Exception $e) {
    error_log("Error in get_time_slot.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "debug" => [
            "match_id" => $matchId ?? 'not set',
            "error" => $e->getMessage(),
            "post_data" => $_POST
        ]
    ]);
}
?>