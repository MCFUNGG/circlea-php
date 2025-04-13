<?php
header("Content-Type: application/json");
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get member_id from POST request
$memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;

if ($memberId === null) {
    echo json_encode(["success" => false, "message" => "Member ID not provided"]);
    exit;
}

// Query for received requests
$stmt1 = $connect->prepare("SELECT 
    m.match_id, 
    m.tutor_id,
    m.tutor_app_id, 
    m.ps_id, 
    m.ps_app_id, 
    m.match_mark,
    m.status,
    m.match_creator,
    ps.username as ps_username,
    t.username as tutor_username
FROM `match` m
JOIN member ps ON m.ps_id = ps.member_id
JOIN member t ON m.tutor_id = t.member_id
WHERE ((m.tutor_id = ? AND m.match_creator = 'PS' AND m.status = 'WT')
   OR (m.ps_id = ? AND m.match_creator = 'T' AND m.status = 'WPS'))
   AND m.match_creator != ?");

$stmt1->bind_param("sss", $memberId, $memberId, $memberId);
$stmt1->execute();
$result1 = $stmt1->get_result();

if (!$result1) {
    echo json_encode(["success" => false, "message" => "Match query failed: " . mysqli_error($connect)]);
    exit;
}

$matchData = [];
while ($row = $result1->fetch_assoc()) {
    // Get profile icon based on creator
    $profileMemberId = ($row['match_creator'] == 'PS') ? $row['ps_id'] : $row['tutor_id'];
    
    $stmt2 = $connect->prepare("SELECT profile 
        FROM member_detail 
        WHERE member_id = ? 
        AND version = (
            SELECT MAX(version) 
            FROM member_detail 
            WHERE member_id = ?
        )");
    $stmt2->bind_param("ss", $profileMemberId, $profileMemberId);
    $stmt2->execute();
    $profileResult = $stmt2->get_result();
    $row['profile_icon'] = ($profileResult && $profileResult->num_rows > 0) 
        ? $profileResult->fetch_assoc()['profile'] 
        : 'N/A';
    
    // Get application details based on creator
    $appId = ($row['match_creator'] == 'PS') ? $row['ps_app_id'] : $row['tutor_app_id'];
    
    $stmt3 = $connect->prepare("SELECT * FROM application WHERE app_id = ?");
    $stmt3->bind_param("s", $appId);
    $stmt3->execute();
    $appResult = $stmt3->get_result();
    
    if ($appResult && $appResult->num_rows > 0) {
        $appData = $appResult->fetch_assoc();
        
        // Get class level name
        $stmt4 = $connect->prepare("SELECT class_level_name FROM class_level WHERE class_level_id = ?");
        $stmt4->bind_param("s", $appData['class_level_id']);
        $stmt4->execute();
        $classLevelResult = $stmt4->get_result();
        $classLevelName = ($classLevelResult && $classLevelResult->num_rows > 0) 
            ? $classLevelResult->fetch_assoc()['class_level_name'] 
            : 'N/A';
        
        // Get subjects
        $subjectNames = [];
        $stmt5 = $connect->prepare("SELECT s.subject_name 
            FROM application_subject as_rel
            JOIN subject s ON as_rel.subject_id = s.subject_id
            WHERE as_rel.app_id = ?");
        $stmt5->bind_param("s", $appId);
        $stmt5->execute();
        $subjectResult = $stmt5->get_result();
        while ($subjectRow = $subjectResult->fetch_assoc()) {
            $subjectNames[] = $subjectRow['subject_name'];
        }
        
        // Get districts
        $districtNames = [];
        $stmt6 = $connect->prepare("SELECT d.district_name 
            FROM application_district ad
            JOIN district d ON ad.district_id = d.district_id
            WHERE ad.app_id = ?");
        $stmt6->bind_param("s", $appId);
        $stmt6->execute();
        $districtResult = $stmt6->get_result();
        while ($districtRow = $districtResult->fetch_assoc()) {
            $districtNames[] = $districtRow['district_name'];
        }
        
        // Combine all application details
        $row['application_details'] = [
            'app_id' => $appData['app_id'],
            'class_level_name' => $classLevelName,
            'subject_names' => $subjectNames,
            'district_names' => $districtNames,
            'feePerHr' => $appData['feePerHr'],
            'description' => $appData['description']
        ];
        
        $matchData[] = $row;
        
        // Close statements
        $stmt4->close();
        $stmt5->close();
        $stmt6->close();
    }
    
    $stmt2->close();
    $stmt3->close();
}

if (count($matchData) > 0) {
    echo json_encode([
        "success" => true,
        "data" => $matchData
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No matching requests found"
    ]);
}

$stmt1->close();
mysqli_close($connect);
?>