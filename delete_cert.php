<?php
// Code Comment: Delete existing certificate records and files for a given member
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "system001";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die(json_encode(array(
        'status' => 'error',
        'message' => "Connection failed: " . $conn->connect_error
    )));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['memberId'])) {
    $memberId = $_POST['memberId'];
    $mId = intval($memberId);
    
    // Set certificate upload directory
    $certUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/FYP/upload_cert/';
    
    // Fetch and delete old records and files
    $sqlCertCheck = "SELECT cert_file FROM member_cert WHERE member_id = ?";
    $stmtCertCheck = $conn->prepare($sqlCertCheck);
    if (!$stmtCertCheck) {
        die(json_encode(array('status'=>'error','message'=>'SQL prepare failed: '.$conn->error)));
    }
    $stmtCertCheck->bind_param("i", $mId);
    $stmtCertCheck->execute();
    $result = $stmtCertCheck->get_result();
    while ($row = $result->fetch_assoc()) {
        $oldCertFile = $row['cert_file'];
        $oldCertFullPath = $certUploadDir . $oldCertFile;
        if (file_exists($oldCertFullPath)) {
            unlink($oldCertFullPath);
            error_log("【Debug】Deleted old certificate file: " . $oldCertFullPath);
        }
    }
    $stmtCertCheck->close();
    // Delete old certificate records from the database
    $sqlCertDelete = "DELETE FROM member_cert WHERE member_id = ?";
    $stmtCertDelete = $conn->prepare($sqlCertDelete);
    if (!$stmtCertDelete) {
        die(json_encode(array('status'=>'error','message'=>'SQL prepare failed: '.$conn->error)));
    }
    $stmtCertDelete->bind_param("i", $mId);
    if ($stmtCertDelete->execute()) {
        echo json_encode(array(
            'status'  => 'success',
            'message' => 'Old certificate records deleted successfully'
        ));
    } else {
        echo json_encode(array(
            'status'  => 'error',
            'message' => 'Deleting old certificate records failed'
        ));
    }
    $stmtCertDelete->close();
} else {
    echo json_encode(array(
        'status'  => 'error',
        'message' => 'Missing required parameter: memberId'
    ));
}
$conn->close();
?>