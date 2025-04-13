<?php
// Database connection
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $stmt = $connect->prepare("SELECT DISTINCT su.app_id, a.member_id, a.district_id, a.class_level_id, su.subject_id, d.district_name, d.Latitude, d.Longitude, s.subject_name, cl.class_level_name, a.app_creator FROM application_subject su LEFT JOIN application_district ad ON su.app_id = ad.app_id LEFT JOIN SUBJECT s ON su.subject_id = s.subject_id LEFT JOIN application a ON su.app_id = a.app_id LEFT JOIN class_level cl ON a.class_level_id = cl.class_level_id LEFT JOIN district d ON a.district_id = d.district_id WHERE a.member_id = ? AND a.app_creator = 'T' ");
    $stmt->bind_param("s", $member_id); // 假设 app_id 是字符串类型
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all results
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Set the content type to application/json
    header('Content-Type: application/json');

    // Output the JSON
    echo json_encode($data);

    // Close the statement and connection
    $stmt->close();
    $connect->close();
}