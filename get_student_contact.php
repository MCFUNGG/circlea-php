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
$tutor_id = isset($_POST['tutor_id']) ? $_POST['tutor_id'] : null;

if ($match_id === null || $tutor_id === null) {
    echo json_encode([
        "success" => false, 
        "message" => "Match ID and Tutor ID are required"
    ]);
    exit;
}

// First, check if payment is confirmed for this match
$payment_query = "SELECT p.status, p.student_id 
                  FROM payment p
                  JOIN `match` m ON p.match_id = m.match_id
                  WHERE p.match_id = ? AND m.tutor_id = ?";

$stmt = $connect->prepare($payment_query);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Payment query preparation failed: " . mysqli_error($connect)
    ]);
    exit;
}

$stmt->bind_param("ss", $match_id, $tutor_id);
$stmt->execute();
$payment_result = $stmt->get_result();
$stmt->close();

if ($payment_result->num_rows === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "No payment record found for this match"
    ]);
    exit;
}

$payment_data = $payment_result->fetch_assoc();
if ($payment_data['status'] !== 'confirmed') {
    echo json_encode([
        "success" => false, 
        "message" => "Student payment is not confirmed yet",
        "status" => $payment_data['status']
    ]);
    exit;
}

// Payment is confirmed, get student contact information
$student_id = $payment_data['student_id'];
$contact_query = "SELECT m.username as name, m.phone, m.email 
                  FROM member m 
                  WHERE m.member_id = ?";

$stmt = $connect->prepare($contact_query);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Contact query preparation failed: " . mysqli_error($connect)
    ]);
    exit;
}

$stmt->bind_param("s", $student_id);
$stmt->execute();
$contact_result = $stmt->get_result();
$stmt->close();

if ($contact_result->num_rows === 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Student information not found"
    ]);
    exit;
}

$student_data = $contact_result->fetch_assoc();
echo json_encode([
    "success" => true,
    "message" => "Student contact information retrieved successfully",
    "student" => [
        "name" => $student_data['name'],
        "phone" => $student_data['phone'],
        "email" => $student_data['email']
    ]
]);

// Close connection
mysqli_close($connect);
?> 