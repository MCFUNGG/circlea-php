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
$match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;
$student_id = isset($_POST['student_id']) ? $_POST['student_id'] : null;
$booking_id = isset($_POST['booking_id']) ? $_POST['booking_id'] : null;

if ($match_id === null || $student_id === null || $booking_id === null) {
    echo json_encode([
        "success" => false, 
        "message" => "Match ID, Student ID and Booking ID are required"
    ]);
    exit;
}

// Query to check if both parties marked the lesson as completed
$query = "SELECT b.booking_id, flr_student.status AS student_status, flr_tutor.status AS tutor_status, 
          tr.rate_id AS has_feedback
          FROM booking b
          LEFT JOIN first_lesson_responses flr_student ON b.booking_id = flr_student.booking_id AND flr_student.user_type = 'student'
          LEFT JOIN first_lesson_responses flr_tutor ON b.booking_id = flr_tutor.booking_id AND flr_tutor.user_type = 'tutor'
          LEFT JOIN tutor_rating tr ON (b.booking_id = tr.application_id AND tr.role = 'parent' AND tr.member_id = ?)
          WHERE b.booking_id = ?";

$stmt = $connect->prepare($query);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Query preparation failed: " . mysqli_error($connect)
    ]);
    exit;
}

$stmt->bind_param("ii", $student_id, $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "No booking found for this match"
    ]);
    exit;
}

$row = $result->fetch_assoc();

// Check if both parties marked the lesson as completed
$bothCompleted = ($row['student_status'] === 'completed' && $row['tutor_status'] === 'completed') ? "true" : "false";
$feedbackSubmitted = $row['has_feedback'] ? "true" : "false";

echo json_encode([
    "success" => true,
    "data" => [
        "booking_id" => $row['booking_id'],
        "student_status" => $row['student_status'],
        "tutor_status" => $row['tutor_status'],
        "both_completed" => $bothCompleted,
        "feedback_submitted" => $feedbackSubmitted
    ]
]);

// Close connections
$stmt->close();
mysqli_close($connect);
?>