<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

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

// Get parameters from POST
$booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : null;
$tutor_id = isset($_POST['tutor_id']) ? $_POST['tutor_id'] : null;
$student_id = isset($_POST['student_id']) ? $_POST['student_id'] : null;
$rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? $_POST['comment'] : '';
$role = isset($_POST['role']) ? $_POST['role'] : 'parent'; // Default is parent/student

if ($booking_id === null || $tutor_id === null || $student_id === null || $rating <= 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required parameters or invalid rating"
    ]);
    exit;
}

// Start transaction
mysqli_begin_transaction($connect);

try {
    // Check if rating already exists
    $checkQuery = "SELECT rate_id FROM tutor_rating 
                  WHERE application_id = ? AND member_id = ? AND role = ?";
    
    $stmt = $connect->prepare($checkQuery);
    $stmt->bind_param("sis", $booking_id, $student_id, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Rating already exists, update it
        $updateQuery = "UPDATE tutor_rating 
                       SET rate_score = ?, comment = ?, rate_times = NOW() 
                       WHERE application_id = ? AND member_id = ? AND role = ?";
        
        $stmt = $connect->prepare($updateQuery);
        $stmt->bind_param("dssis", $rating, $comment, $booking_id, $student_id, $role);
        $stmt->execute();
    } else {
        // Insert new rating
        $insertQuery = "INSERT INTO tutor_rating 
                       (member_id, application_id, role, rate_times, rate_score, comment) 
                       VALUES (?, ?, ?, NOW(), ?, ?)";
        
        $stmt = $connect->prepare($insertQuery);
        $stmt->bind_param("iisds", $student_id, $booking_id, $role, $rating, $comment);
        $stmt->execute();
    }
    
    // Commit transaction
    mysqli_commit($connect);
    
    echo json_encode([
        "success" => true,
        "message" => "Rating submitted successfully"
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($connect);
    
    echo json_encode([
        "success" => false,
        "message" => "Error submitting rating: " . $e->getMessage()
    ]);
}

// Close connection
mysqli_close($connect);
?> 