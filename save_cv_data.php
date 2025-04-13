<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 引入数据库配置文件
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();
if ($connect->connect_error) {
    die(json_encode(array(
        'status' => 'error',
        'message' => "Connection failed: " . $connect->connect_error
    )));
}

// Check if request was made via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get POST data
    $memberId = isset($_POST['member_id']) ? $_POST['member_id'] : null;
    $cvData = isset($_POST['cv_data']) ? $_POST['cv_data'] : null;
    
    if (!$memberId || !$cvData) {
        die(json_encode(array(
            'status' => 'error',
            'message' => 'Missing required fields: member_id or cv_data'
        )));
    }
    
    try {
        // Process CV data
        $cvDataObject = json_decode($cvData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in cv_data: " . json_last_error_msg());
        }
        
        // Extract data
        $name = $cvDataObject['name'] ?? '';
        $phone = $cvDataObject['phone'] ?? '';
        $email = $cvDataObject['email'] ?? '';
        $education = $cvDataObject['education'] ?? '';
        $experience = $cvDataObject['experience'] ?? '';
        $skills = $cvDataObject['skills'] ?? '';
        $version = isset($cvDataObject['version']) ? (int)$cvDataObject['version'] : 1;
        
        // Prepare SQL query
        $sql = "INSERT INTO tutor_cv (member_id, name, phone, email, education, experience, skills, version) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $connect->error);
        }
        
        $stmt->bind_param("issssssi", 
            $memberId, 
            $name, 
            $phone,
            $email,
            $education,
            $experience,
            $skills,
            $version
        );
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(array(
                'status' => 'success',
                'message' => 'CV data saved successfully',
                'cv_id' => $stmt->insert_id
            ));
        } else {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(array(
            'status' => 'error',
            'message' => $e->getMessage()
        ));
    }
} else {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Invalid request method. Only POST is supported.'
    ));
}

$connect->close();
?>