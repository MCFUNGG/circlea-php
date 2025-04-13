# PowerShell脚本：更新PHP文件的数据库连接

# 设置工作目录
$workingDir = $PSScriptRoot
Write-Host "Working directory: $workingDir"

# 获取所有PHP文件
$phpFiles = Get-ChildItem -Path $workingDir -Filter "*.php" -Recurse | 
            Where-Object { $_.Name -ne "db_config.php" -and $_.Name -ne "update_db_connections.php" }

Write-Host "Found $($phpFiles.Count) PHP files to check"

# 旧连接模式
$oldPatterns = @(
    '$host = "127.0.0.1"',
    '$username = "root"',
    '$password = ""',
    '$dbname = "system001"'
)

# 新的连接代码
$newConnectionCode = @"
// 引入数据库配置文件
require_once 'db_config.php';

// 使用配置的连接函数获取数据库连接
`$connect = getDbConnection();
if (!`$connect) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}
"@

$updatedFiles = 0
$failedFiles = @()

# 处理每个文件
foreach ($file in $phpFiles) {
    Write-Host "Checking file: $($file.FullName)"
    
    # 读取文件内容
    $content = Get-Content -Path $file.FullName -Raw
    
    # 检查是否包含旧的连接配置
    $containsOldConfig = $false
    foreach ($pattern in $oldPatterns) {
        if ($content -match [regex]::Escape($pattern)) {
            $containsOldConfig = $true
            Write-Host "Found old connection pattern in file: $($file.Name)"
            break
        }
    }
    
    if ($containsOldConfig) {
        Write-Host "Updating file: $($file.Name)"
        
        # 尝试替换完整的连接块
        $completePattern = '\$host\s*=\s*"127\.0\.0\.1";\s*\$username\s*=\s*"root";\s*\$password\s*=\s*"";\s*\$dbname\s*=\s*"system001";\s*\$connect\s*=\s*mysqli_connect\(\$host,\s*\$username,\s*\$password,\s*\$dbname\);'
        $newContent = $content -replace $completePattern, $newConnectionCode
        
        # 如果没有匹配完整模式，单独替换各个部分
        if ($newContent -eq $content) {
            Write-Host "Using alternative replacement method for: $($file.Name)"
            
            # 移除旧的连接行
            foreach ($pattern in $oldPatterns) {
                $newContent = $newContent.Replace($pattern, "")
            }
            
            # 在文件开头添加新连接代码
            $newContent = $newContent -replace "(<\?php)", "`$1`n$newConnectionCode"
        }
        
        # 确保文件不包含重复的db_config.php引入
        $newContent = $newContent.Replace("require_once 'db_config.php';`nrequire_once 'db_config.php';", "require_once 'db_config.php';")
        
        try {
            # 保存更新后的文件
            Set-Content -Path $file.FullName -Value $newContent -Force
            $updatedFiles++
            Write-Host "Updated successfully: $($file.Name)"
        }
        catch {
            $failedFiles += $file.FullName
            Write-Host "Failed to update: $($file.Name) - $_"
        }
    }
    else {
        Write-Host "No old connection patterns found in: $($file.Name)"
    }
}

Write-Host "`nUpdate completed."
Write-Host "Updated files: $updatedFiles"

if ($failedFiles.Count -gt 0) {
    Write-Host "Failed to update $($failedFiles.Count) files:"
    foreach ($file in $failedFiles) {
        Write-Host "- $file"
    }
} 