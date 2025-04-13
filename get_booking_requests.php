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

// Get parameters from POST request
$match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;
$tutor_id = isset($_POST['tutor_id']) ? $_POST['tutor_id'] : null;

if (!$match_id || !$tutor_id) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required parameters"
    ]);
    exit;
}

// Query to get booking requests
$query = "SELECT b.*, m.username as student_name
          FROM booking b
          JOIN member m ON b.student_id = m.member_id
          WHERE b.match_id = ? 
          AND b.tutor_id = ?
          AND (b.status = 'pending' OR b.status = 'confirmed')
          ORDER BY b.status = 'confirmed' DESC, 
                   b.created_at DESC";

$stmt = $connect->prepare($query);
if (!$stmt) {
    echo json_encode([
        "success" => false, 
        "message" => "Query preparation failed: " . mysqli_error($connect)
    ]);
    exit;
}

$stmt->bind_param("ss", $match_id, $tutor_id);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false, 
        "message" => "Query execution failed: " . mysqli_error($connect)
    ]);
    exit;
}

$result = $stmt->get_result();
$requests = [];
$hasConfirmed = false;

while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'confirmed') {
        $hasConfirmed = true;
    }
    
    // Format the dates to match the Android expected format (yyyy-MM-dd HH:mm)
    $start_time = date('Y-m-d H:i', strtotime($row['start_time']));
    $end_time = date('Y-m-d H:i', strtotime($row['end_time']));
    
    $requests[] = [
        'booking_id' => $row['booking_id'],
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'start_time' => $start_time,
        'end_time' => $end_time,
        'status' => $row['status']
    ];
}

if (empty($requests)) {
    echo json_encode([
        "success" => true,
        "message" => "No booking requests found",
        "requests" => []
    ]);
} else {
    echo json_encode([
        "success" => true,
        "has_confirmed" => $hasConfirmed,
        "requests" => $requests
    ]);
}

// Close connections
$stmt->close();
$connect->close();
?>