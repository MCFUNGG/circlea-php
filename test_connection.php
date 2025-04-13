<?php
header('Content-Type: application/json');

// 引入数据库配置文件
require_once 'db_config.php';

// 测试连接
try {
    // 获取数据库连接
    $connect = getDbConnection();
    
    if (!$connect) {
        throw new Exception("无法连接到数据库: " . mysqli_connect_error());
    }
    
    // 尝试执行一个简单的查询
    $query = "SELECT 'Connection successful' AS message, @@version AS version";
    $result = mysqli_query($connect, $query);
    
    if (!$result) {
        throw new Exception("查询执行失败: " . mysqli_error($connect));
    }
    
    // 获取结果
    $row = mysqli_fetch_assoc($result);
    
    // 获取一些表信息
    $tables_query = "SHOW TABLES";
    $tables_result = mysqli_query($connect, $tables_query);
    
    $tables = [];
    if ($tables_result) {
        while ($table = mysqli_fetch_array($tables_result)[0]) {
            $tables[] = $table;
        }
    }
    
    // 关闭连接
    mysqli_close($connect);
    
    // 返回成功结果
    echo json_encode([
        'success' => true,
        'message' => $row['message'],
        'mysql_version' => $row['version'],
        'tables' => $tables,
        'connection_info' => [
            'host' => $host, // 来自db_config.php
            'database' => $dbname,
            'ssl_enabled' => true
        ]
    ]);
    
} catch (Exception $e) {
    // 返回错误
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'connection_info' => [
            'host' => $host ?? 'unknown',
            'database' => $dbname ?? 'unknown'
        ]
    ]);
}
?> 