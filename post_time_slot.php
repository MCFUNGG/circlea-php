<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

header("Content-Type: application/json");

try {
    if (!isset($_POST['match_id']) || !isset($_POST['tutor_id']) || !isset($_POST['slots'])) {
        throw new Exception("Missing required parameters");
    }

    $matchId = (int)trim($_POST['match_id']);
    $tutorId = (int)trim($_POST['tutor_id']);
    $slots = json_decode($_POST['slots'], true);

    error_log("Received data: " . print_r($_POST, true));
    error_log("Decoded slots: " . print_r($slots, true));

    require_once 'db_config.php';

    // 创建数据库连接
    $connect = getDbConnection();
    if (!$connect) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    mysqli_begin_transaction($connect);
    
    try {
        $successCount = 0;
        foreach ($slots as $slot) {
            // Parse datetime strings into date and time components
            $startDateTime = new DateTime($slot['start_time']);
            $endDateTime = new DateTime($slot['end_time']);
            
            // Format components
            $date = $startDateTime->format('Y-m-d');
            $startTime = $startDateTime->format('H:i:s');
            $endTime = $endDateTime->format('H:i:s');
            $action = $slot['action'];

            if ($action === 'update' && isset($slot['slot_id'])) {
                // Update existing slot
                $query = "UPDATE booking 
                        SET date = ?,
                            start_time = ?,
                            end_time = ?
                        WHERE booking_id = ? 
                        AND tutor_id = ? 
                        AND status = 'available'";
    
                $stmt = mysqli_prepare($connect, $query);
                mysqli_stmt_bind_param($stmt, "sssis", 
                    $date,
                    $startTime,
                    $endTime,
                    $slot['slot_id'],
                    $tutorId
                );
            } else {
                // Insert new slot
                $query = "INSERT INTO booking (
                            match_id,
                            tutor_id,
                            date,
                            start_time,
                            end_time,
                            status
                        ) VALUES (?, ?, ?, ?, ?, 'available')";
    
                $stmt = mysqli_prepare($connect, $query);
                mysqli_stmt_bind_param($stmt, "iisss", 
                    $matchId,
                    $tutorId, 
                    $date,
                    $startTime,
                    $endTime
                );
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            }
            
            $successCount++;
            mysqli_stmt_close($stmt);
        }

        if ($successCount == count($slots)) {
            mysqli_commit($connect);
            echo json_encode([
                "success" => true,
                "message" => "Successfully saved " . $successCount . " time slots",
                "debug" => [
                    "match_id" => $matchId,
                    "tutor_id" => $tutorId,
                    "slots_processed" => $successCount
                ]
            ]);
        } else {
            throw new Exception("Only saved $successCount out of " . count($slots) . " slots");
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        mysqli_rollback($connect);
        throw $e;
    }

    mysqli_close($connect);

} catch (Exception $e) {
    error_log("Error in post_time_slot.php: " . $e->getMessage());
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