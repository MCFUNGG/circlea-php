<?php
// Code Comment: Upload new certificate files without deleting old ones.
// This endpoint expects a multipart request with key "file[]" and a POST field "memberId" (plus optional "description" etc.)

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();
$connect->set_charset("utf8mb4");
if ($connect->connect_error) {
    die(json_encode(array(
        'status' => 'error',
        'message' => "Connection failed: " . $connect->connect_error
    )));
}

$connect->begin_transaction();
try {
    error_log("【Debug】Received POST: " . print_r($_POST, true));
    error_log("【Debug】Received FILES: " . print_r($_FILES, true));
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['memberId']) && isset($_FILES['file'])) {
        $memberId    = $_POST['memberId'];
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $mId = intval($memberId);
        
        // For CV section (optional)
        $cvPath = '';
        $contact   = isset($_POST['contact'])   ? $_POST['contact']   : '';
        $skills    = isset($_POST['skills'])    ? $_POST['skills']    : '';
        $education = isset($_POST['education']) ? $_POST['education'] : '';
        $language  = isset($_POST['language'])  ? $_POST['language']  : '';
        $other     = isset($_POST['other'])     ? $_POST['other']     : '';
        
        // Insert or update CV data (if applicable)
        $sqlCV = "INSERT INTO member_cv (member_id, contact, skills, education, language, other, cv_path) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                    contact = VALUES(contact),
                    skills = VALUES(skills),
                    education = VALUES(education),
                    language = VALUES(language),
                    other = VALUES(other),
                    cv_path = VALUES(cv_path)";
        $stmtCV = $connect->prepare($sqlCV);
        if ($stmtCV === false) {
            throw new Exception("Preparing CV SQL statement failed: " . $connect->error);
        }
        $stmtCV->bind_param("issssss", $mId, $contact, $skills, $education, $language, $other, $cvPath);
        if (!$stmtCV->execute()) {
            throw new Exception("Executing CV data save failed: " . $stmtCV->error);
        }
        error_log("【Debug】CV data inserted or updated");
        $stmtCV->close();
        
        // Ensure FILES are treated as array for multi-files
        if (!is_array($_FILES['file']['name'])) {
            $_FILES['file']['name'] = array($_FILES['file']['name']);
            $_FILES['file']['tmp_name'] = array($_FILES['file']['tmp_name']);
            $_FILES['file']['error'] = array($_FILES['file']['error']);
            $_FILES['file']['size'] = array($_FILES['file']['size']);
            $_FILES['file']['type'] = array($_FILES['file']['type']);
        }
        $certUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/FYP/upload_cert/';
        if (!file_exists($certUploadDir)) {
            if (!mkdir($certUploadDir, 0777, true)) {
                throw new Exception("Unable to create certificate upload directory");
            }
        }
        
        $newFileNames = array();
        for($i=0; $i < count($_FILES['file']['name']); $i++){
            error_log("【Debug】Processing file index: " . $i);
            if ($_FILES['file']['error'][$i] === 0) {
                $originalName = $_FILES['file']['name'][$i];
                $tmpPath = $_FILES['file']['tmp_name'][$i];
                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                $newFileName = 'CERT_' . $memberId . '_' . date('YmdHis') . '_' . ($i + 1) . '.' . $fileExtension;
                $certDestination = $certUploadDir . $newFileName;
    
                if (!move_uploaded_file($tmpPath, $certDestination)) {
                    throw new Exception("Moving certificate file failed at index: " . ($i + 1));
                }
                chmod($certDestination, 0644);
                error_log("【Debug】Certificate moved to: " . $certDestination);
    
                $sqlCertInsert = "INSERT INTO member_cert (member_id, cert_file, description, created_time, status) 
                                  VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'P')";
                $stmtCertInsert = $connect->prepare($sqlCertInsert);
                if (!$stmtCertInsert) {
                    throw new Exception("Preparing certificate INSERT statement failed: " . $connect->error);
                }
                $stmtCertInsert->bind_param("iss", $mId, $newFileName, $description);
                if (!$stmtCertInsert->execute()) {
                    throw new Exception("Inserting new certificate record failed: " . $stmtCertInsert->error);
                }
                $stmtCertInsert->close();
                $newFileNames[] = $newFileName;
            } else {
                throw new Exception("Certificate file upload error at index: " . ($i + 1));
            }
        }
        $connect->commit();
        echo json_encode(array(
            'status'     => 'success',
            'message'    => 'Data uploaded successfully',
            'cert_path'  => '/FYP/upload_cert/',
            'cert_files' => $newFileNames,
            'cv_path'    => $cvPath
        ));
    } else {
        throw new Exception("Missing required parameters or file upload");
    }
} catch (Exception $e) {
    $connect->rollback();
    error_log("【Debug】Upload error: " . $e->getMessage());
    echo json_encode(array(
        'status'  => 'error',
        'message' => $e->getMessage()
    ));
}
$connect->close();
?>