<?php
// 设置内容类型为JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 引入数据库配置
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

// 检查是否接收到POST请求
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取证书ID和会员ID
    $cert_id = isset($_POST['cert_id']) ? intval($_POST['cert_id']) : 0;
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    
    if ($cert_id <= 0 || $member_id <= 0) {
        die(json_encode(array('status'=>'error','message'=>'Invalid certificate ID or member ID')));
    }
    
    // 首先检查证书是否存在且属于该会员
    $sqlCertCheck = "SELECT cert_file FROM member_cert WHERE member_cert_id = ? AND member_id = ?";
    $stmtCertCheck = $connect->prepare($sqlCertCheck);
    if ($stmtCertCheck === false) {
        die(json_encode(array('status'=>'error','message'=>'SQL prepare failed: '.$connect->error)));
    }
    
    $stmtCertCheck->bind_param("ii", $cert_id, $member_id);
    $stmtCertCheck->execute();
    $stmtCertCheck->store_result();
    
    if ($stmtCertCheck->num_rows == 0) {
        $stmtCertCheck->close();
        die(json_encode(array('status'=>'error','message'=>'Certificate not found or not owned by this member')));
    }
    
    // 获取文件名以便删除文件
    $stmtCertCheck->bind_result($cert_file);
    $stmtCertCheck->fetch();
    $stmtCertCheck->close();
    
    // 删除数据库记录
    $sqlCertDelete = "DELETE FROM member_cert WHERE member_cert_id = ? AND member_id = ?";
    $stmtCertDelete = $connect->prepare($sqlCertDelete);
    if ($stmtCertDelete === false) {
        die(json_encode(array('status'=>'error','message'=>'SQL prepare failed: '.$connect->error)));
    }
    
    $stmtCertDelete->bind_param("ii", $cert_id, $member_id);
    $result = $stmtCertDelete->execute();
    $stmtCertDelete->close();
    
    if ($result) {
        // 尝试删除文件
        $file_path = '../upload_cert/' . $cert_file;
        $file_deleted = false;
        
        if (file_exists($file_path)) {
            $file_deleted = unlink($file_path);
        }
        
        echo json_encode(array(
            'status' => 'success',
            'message' => 'Certificate deleted successfully',
            'file_deleted' => $file_deleted
        ));
    } else {
        echo json_encode(array('status'=>'error','message'=>'Failed to delete certificate'));
    }
} else {
    echo json_encode(array('status'=>'error','message'=>'Invalid request method'));
}

$connect->close();
?>