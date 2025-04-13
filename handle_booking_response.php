<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_POST['booking_id']) || !isset($_POST['action'])) {
        throw new Exception("Missing required parameters");
    }

    $bookingId = $_POST['booking_id'];
    $action = $_POST['action']; // 'accept' or 'reject'
    
    require_once 'db_config.php';

    // 创建数据库连接
    $connect = getDbConnection();
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    mysqli_begin_transaction($connect);

    try {
        if ($action === 'accept') {
            // Update booking status to 'confirmed'
            $query = "UPDATE booking 
                     SET status = 'confirmed'
                     WHERE booking_id = ?";
        } else {
            // Update booking status back to 'available' and clear student_id
            $query = "UPDATE booking 
                     SET status = 'available',
                         student_id = NULL 
                     WHERE booking_id = ?";
        }

        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param($stmt, "i", $bookingId);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update booking status");
        }

        mysqli_commit($connect);
        
        echo json_encode([
            "success" => true,
            "message" => "Booking " . ($action === 'accept' ? "accepted" : "rejected") . " successfully"
        ]);

    } catch (Exception $e) {
        mysqli_rollback($connect);
        throw $e;
    }

    mysqli_close($connect);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>