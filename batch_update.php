<?php
// 批量更新PHP文件的数据库连接配置

// 要排除的文件
$excludeFiles = [
    'db_config.php',
    'test_connection.php',
    'batch_update.php',
    'update_db_connections.php',
    'README.md',
    'php_error.log',
    'match_svm.py',
    'high_matches.json',
    'update_files.ps1'
];

// 新的数据库配置代码
$newConfig = <<<'EOD'
<?php
header("Content-Type: application/json");

// 引入数据库配置文件
require_once 'db_config.php';

// 创建数据库连接
$connect = getDbConnection();

// Check connection
if (!$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

EOD;

// 遍历目录中的所有PHP文件
$files = glob("*.php");
$updatedCount = 0;

foreach ($files as $file) {
    // 跳过排除的文件
    if (in_array($file, $excludeFiles)) {
        echo "跳过文件: $file\n";
        continue;
    }
    
    // 读取文件内容
    $content = file_get_contents($file);
    if ($content === false) {
        echo "无法读取文件: $file\n";
        continue;
    }
    
    // 检查是否需要更新
    if (strpos($content, 'require_once \'db_config.php\'') !== false) {
        echo "文件已更新，跳过: $file\n";
        continue;
    }
    
    // 备份原始文件
    copy($file, $file . '.bak');
    
    // 移除旧的数据库配置
    $patterns = [
        '/\$host\s*=\s*["\'].*?["\']\s*;\s*\n/',
        '/\$username\s*=\s*["\'].*?["\']\s*;\s*\n/',
        '/\$password(?:_db)?\s*=\s*["\'].*?["\']\s*;\s*\n/',
        '/\$dbname\s*=\s*["\'].*?["\']\s*;\s*\n/',
        '/\$connect\s*=\s*mysqli_connect\([^;]+;\s*\n/',
        '/\$connect\s*=\s*new\s+mysqli\([^;]+;\s*\n/',
        '/mysqli_select_db\([^;]+;\s*\n/'
    ];
    
    // 替换旧的配置
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // 替换文件头部
    $content = preg_replace(
        '/^<\?php\s*\n\s*header\([^;]+;\s*\n/s',
        $newConfig,
        $content
    );
    
    // 如果没有header声明，在文件开头添加新配置
    if (strpos($content, 'header("Content-Type: application/json")') === false) {
        $content = $newConfig . substr($content, strpos($content, '<?php') + 5);
    }
    
    // 保存更新后的文件
    if (file_put_contents($file, $content)) {
        echo "已更新文件: $file\n";
        $updatedCount++;
    } else {
        echo "更新失败: $file\n";
        // 如果更新失败，恢复备份
        copy($file . '.bak', $file);
    }
    
    // 删除备份文件
    unlink($file . '.bak');
}

echo "\n更新完成！共更新 $updatedCount 个文件。\n";

// 显示更新的文件列表
echo "\n已更新的文件：\n";
foreach ($files as $file) {
    if (!in_array($file, $excludeFiles)) {
        echo "- $file\n";
    }
}
?> 