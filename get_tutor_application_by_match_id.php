<?php
header("Content-Type: application/json");

require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Get tutor_id from POST request
$matchId = $_POST['match_id'];

if ($matchId === null) {
    echo json_encode(["success" => false, "message" => "Tutor ID not provided"]);
    exit;
}

// First query: Get match information
$query1 = mysqli_query($connect, "SELECT tutor_app_id FROM `match` WHERE match_id = '$matchId'");
$row = mysqli_fetch_assoc($query1);
if (!$row) {
    echo json_encode(["success" => false, "message" => "Match not found"]);
    exit;
}
$tutorAppId = $row['tutor_app_id'];

// Second query: Get application details
$query2 = "SELECT app_id, member_id, class_level_id, feePerHr 
           FROM application 
           WHERE app_creator = 'T' AND app_id = '$tutorAppId'";
$result = mysqli_query($connect, $query2);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Database query failed"]);
    exit;
}   


if (mysqli_num_rows($result) > 0) {
    $applicationData = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $applicationData[] = $row;
    }

    foreach ($applicationData as &$application) {
        // Get username
        $memberId = $application['member_id'];
        $usernameQuery = "SELECT username FROM member WHERE member_id = '$memberId'";
        $usernameResult = mysqli_query($connect, $usernameQuery);
        if ($usernameRow = mysqli_fetch_assoc($usernameResult)) {
            $application['username'] = $usernameRow['username'];
        } else {
            $application['username'] = 'N/A';
        }

        // Get class level name
        $classLevelId = $application['class_level_id'];
        $classLevelQuery = "SELECT class_level_name FROM class_level WHERE class_level_id = '$classLevelId'";
        $classLevelResult = mysqli_query($connect, $classLevelQuery);
        if ($classRow = mysqli_fetch_assoc($classLevelResult)) {
            $application['class_level_name'] = $classRow['class_level_name'];
        } else {
            $application['class_level_name'] = 'N/A';
        }

        // Get subjects
        $application['subject_names'] = [];
        $subjectQuery = "SELECT subject_id FROM application_subject WHERE app_id = '{$application['app_id']}'";
        $subjectResult = mysqli_query($connect, $subjectQuery);
        while ($subjectRow = mysqli_fetch_assoc($subjectResult)) {
            $subjectId = $subjectRow['subject_id'];
            $subjectNameQuery = "SELECT subject_name FROM subject WHERE subject_id = '$subjectId'";
            $subjectNameResult = mysqli_query($connect, $subjectNameQuery);
            if ($subjectNameRow = mysqli_fetch_assoc($subjectNameResult)) {
                $application['subject_names'][] = $subjectNameRow['subject_name'];
            }
        }

        // Get districts
        $application['district_names'] = [];
        $districtQuery = "SELECT district_id FROM application_district WHERE app_id = '{$application['app_id']}'";
        $districtResult = mysqli_query($connect, $districtQuery);
        while ($districtRow = mysqli_fetch_assoc($districtResult)) {
            $districtId = $districtRow['district_id'];
            $districtNameQuery = "SELECT district_name FROM district WHERE district_id = '$districtId'";
            $districtNameResult = mysqli_query($connect, $districtNameQuery);
            if ($districtNameRow = mysqli_fetch_assoc($districtNameResult)) {
                $application['district_names'][] = $districtNameRow['district_name'];
            }
        }

        // Get profile icon
        $profileQuery = "SELECT profile FROM member_detail WHERE member_id = '$memberId' ORDER BY version DESC LIMIT 1";
        $profileResult = mysqli_query($connect, $profileQuery);
        if ($profileRow = mysqli_fetch_assoc($profileResult)) {
            $application['profile_icon'] = $profileRow['profile'];
        } else {
            $application['profile_icon'] = 'N/A';
        }
    }

    // Format final output
    $finalData = array_map(function($application) {
        return [
            'app_id' => $application['app_id'],
            'member_id' => $application['member_id'],
            'username' => $application['username'],
            'class_level_name' => $application['class_level_name'],
            'subject_names' => $application['subject_names'],
            'district_names' => $application['district_names'],
            'feePerHr' => $application['feePerHr'],
            'profile_icon' => $application['profile_icon']
        ];
    }, $applicationData);

    echo json_encode([
        "success" => true,
        "data" => $finalData
    ]);
} else {
    echo json_encode([
        "success" => false, 
        "message" => "No tutor applications found"
    ]);
}

mysqli_close($connect);
?>