<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get member_id from POST
$memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;

if ($memberId === null) {
    echo json_encode(["success" => false, "message" => "Member ID not provided"]);
    exit;
}

// Query to get member info, profile, and CV status using LEFT JOIN
$query = "SELECT m.email, m.phone, m.username, 
          md.profile,
          mc.status as cv_status,  
          CASE 
              WHEN mc.member_id IS NOT NULL THEN 1
              ELSE 0
          END as has_cv
          FROM member m
          LEFT JOIN member_detail md ON m.member_id = md.member_id
          LEFT JOIN member_cv mc ON m.member_id = mc.member_id
          WHERE m.member_id = ?
          ORDER BY md.version DESC
          LIMIT 1";

$stmt = $connect->prepare($query);
$stmt->bind_param("s", $memberId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed: " . mysqli_error($connect)]);
    exit;
}

if ($result->num_rows > 0) {
    $applicationData = [];
    while ($row = $result->fetch_assoc()) {
        // Convert CV status to readable format
        if ($row['has_cv']) {
            if ($row['cv_status'] === 'P') {
                $row['cv_status'] = 'Pending';
            } else if ($row['cv_status'] === 'A') {
                $row['cv_status'] = 'Approved';
            }
        }
        $applicationData[] = $row;
    }
    echo json_encode([
        "success" => true,
        "data" => $applicationData
    ]);
} else {
    echo json_encode(["success" => false, "message" => "No member found with this ID"]);
}

// Close connections
$stmt->close();
mysqli_close($connect);
?>