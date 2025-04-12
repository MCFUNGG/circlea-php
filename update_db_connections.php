<?php
// 此脚本用于批量更新PHP文件中的数据库连接代码
// 请在运行之前备份所有PHP文件！

// 获取所有PHP文件
$directory = __DIR__; // 当前目录
$phpFiles = glob($directory . '/*.php');

// 需要排除的文件
$excludeFiles = [
    __FILE__, // 当前脚本
    $directory . '/db_config.php', // 配置文件
    $directory . '/test_connection.php' // 测试文件
];

foreach ($phpFiles as $file) {
    // 跳过排除的文件
    if (in_array($file, $excludeFiles)) {
        continue;
    }
    
    // 读取文件内容
    $content = file_get_contents($file);
    
    if (!$content) {
        echo "无法读取文件: " . basename($file) . "\n";
        continue;
    }
    
    // 检查是否需要更新
    $needsUpdate = false;
    
    // 检查是否包含旧的数据库配置
    if (strpos($content, '$host = "127.0.0.1"') !== false ||
        strpos($content, '$username = "root"') !== false ||
        strpos($content, 'mysqli_connect($host') !== false) {
        $needsUpdate = true;
    }
    
    if ($needsUpdate) {
        // 创建新内容
        $newContent = $content;
        
        // 移除旧的数据库配置
        $patterns = [
            '/\$host\s*=\s*["\'].*?["\']\s*;\s*\n/',
            '/\$username\s*=\s*["\'].*?["\']\s*;\s*\n/',
            '/\$password(?:_db)?\s*=\s*["\'].*?["\']\s*;\s*\n/',
            '/\$dbname\s*=\s*["\'].*?["\']\s*;\s*\n/'
        ];
        
        foreach ($patterns as $pattern) {
            $newContent = preg_replace($pattern, '', $newContent);
        }
        
        // 替换数据库连接代码
        $newContent = preg_replace(
            '/\$connect\s*=\s*mysqli_connect\([^;]+;/',
            '$connect = getDbConnection();',
            $newContent
        );
        
        // 添加配置文件引入
        if (strpos($newContent, "require_once 'db_config.php'") === false) {
            $newContent = preg_replace(
                '/<\?php\s+/',
                "<?php\n// 引入数据库配置文件\nrequire_once 'db_config.php';\n\n",
                $newContent
            );
        }
        
        // 写回文件
        if (file_put_contents($file, $newContent)) {
            echo "已更新文件: " . basename($file) . "\n";
        } else {
            echo "更新文件失败: " . basename($file) . "\n";
        }
    } else {
        echo "文件无需更新: " . basename($file) . "\n";
    }
}

echo "\n更新完成！\n";
?> 