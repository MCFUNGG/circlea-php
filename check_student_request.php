<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['match_id']) || !isset($_POST['student_id'])) {
        throw new Exception("Missing required parameters");
    }

    $matchId = $_POST['match_id'];
    $studentId = $_POST['student_id'];
   // 引入数据库配置文件
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // Check for any existing request by the student
    $query = "SELECT * FROM booking 
              WHERE match_id = ? 
              AND student_id = ?
              AND status IN ('pending', 'confirmed','completed')
              ORDER BY created_at DESC
              LIMIT 1";

    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "ii", $matchId, $studentId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Query execution failed");
    }

    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Found an existing request
        echo json_encode([
            "has_request" => true,
            "request_data" => [
                "slot_id" => $row['booking_id'],
                "start_time" => $row['date'] . ' ' . $row['start_time'],
                "end_time" => $row['date'] . ' ' . $row['end_time'],
                "status" => $row['status']
            ]
        ]);
    } else {
        // No existing request
        echo json_encode([
            "has_request" => false
        ]);
    }

    mysqli_close($connect);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>