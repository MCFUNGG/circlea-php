<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

$memberId = $_POST['member_id'] ?? null;
    
if ($memberId === null) {
    echo json_encode(["success" => false, "message" => "Member ID is required."]);
    exit;
}

// Main consolidated query using subqueries
$query = "
    SELECT 
        ab.app_id,
        ab.class_level_name,
        ab.description,
        ab.feePerHr,
        ab.status, 
        COALESCE(sd.subject_names, 'N/A') as subject_names,
        COALESCE(dd.district_names, 'N/A') as district_names
    FROM (
        SELECT 
            a.app_id,
            a.app_creator,
            a.member_id,
            a.description,
            a.feePerHr,
            a.status, 
            cl.class_level_name
        FROM application a
        LEFT JOIN class_level cl ON a.class_level_id = cl.class_level_id
        WHERE a.member_id = '$memberId'
        AND a.app_creator = 'PS'
    ) ab
    LEFT JOIN (
        SELECT 
            as_table.app_id,
            GROUP_CONCAT(s.subject_name) as subject_names
        FROM application_subject as_table
        JOIN subject s ON as_table.subject_id = s.subject_id
        GROUP BY as_table.app_id
    ) sd ON ab.app_id = sd.app_id
    LEFT JOIN (
        SELECT 
            ad.app_id,
            GROUP_CONCAT(d.district_name) as district_names
        FROM application_district ad
        JOIN district d ON ad.district_id = d.district_id
        GROUP BY ad.app_id
    ) dd ON ab.app_id = dd.app_id";

$result = mysqli_query($connect, $query);

if (!$result) {
    echo json_encode([
        "success" => false, 
        "message" => "Query failed: " . mysqli_error($connect),
        "query" => $query
    ]);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    $applicationData = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Convert comma-separated strings to arrays
        $row['subject_names'] = $row['subject_names'] !== 'N/A' ? 
            explode(',', $row['subject_names']) : [];
        $row['district_names'] = $row['district_names'] !== 'N/A' ? 
            explode(',', $row['district_names']) : [];
            
        $applicationData[] = [
            'app_id' => $row['app_id'],
            'subject_names' => $row['subject_names'],
            'class_level_name' => $row['class_level_name'],
            'district_names' => $row['district_names'],
            'feePerHr' => $row['feePerHr'],
            'description' => $row['description'],
            'status' => $row['status'] 
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $applicationData
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "No application found for this member ID"
    ]);
}

mysqli_close($connect);
?>