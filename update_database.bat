@echo off
chcp 65001 >nul
echo ==========================================
echo 数据库更新脚本
echo ==========================================
echo.

set /p password=yu.157024:

echo.
echo 正在连接数据库并更新...
echo.

php -r "
$host = '127.0.0.1';
$port = '3306';
$dbname = 'ai-code';
$username = 'root';
$password = '%password%';

try {
    $pdo = new PDO(\"mysql:host=$host;port=$port;dbname=$dbname;charset=utf8\", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo \"✓ 数据库连接成功\n\n\";
    
    $stmt = $pdo->query(\"SHOW COLUMNS FROM project\");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo \"当前字段: \" .implode(', ', $existingColumns) .\"\n\n\";
    
    $columns = [
        ['name' => 'scan_status', 'sql' => \"ALTER TABLE project ADD COLUMN scan_status VARCHAR(20) DEFAULT 'pending' COMMENT '扫描状态'\"],
        ['name' => 'code_path', 'sql' => \"ALTER TABLE project ADD COLUMN code_path VARCHAR(500) DEFAULT NULL COMMENT 'CodeQL数据库路径'\"],
        ['name' => 'sarif_path', 'sql' => \"ALTER TABLE project ADD COLUMN sarif_path VARCHAR(500) DEFAULT NULL COMMENT 'SARIF结果文件路径'\"],
        ['name' => 'update_time', 'sql' => \"ALTER TABLE project ADD COLUMN update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'\"],
        ['name' => 'create_time', 'sql' => \"ALTER TABLE project ADD COLUMN create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'\"]
    ];
    
    foreach ($columns as $column) {
        if (in_array($column['name'], $existingColumns)) {
            echo \"✓ 字段 {$column['name']} 已存在\n\";
        } else {
            echo \"→ 添加字段 {$column['name']}...\n\";
            try {
                $pdo->exec($column['sql']);
                echo \"✓ 字段 {$column['name']} 添加成功\n\";
            } catch (PDOException \$e) {
                echo \"✗ 失败: \" .\$e->getMessage() .\"\n\";
            }
        }
    }
    
    echo \"\n更新默认值...\n\";
    $pdo->exec(\"UPDATE project SET scan_status = 'pending' WHERE scan_status IS NULL OR scan_status = ''\");
    $pdo->exec(\"UPDATE project SET create_time = NOW() WHERE create_time IS NULL\");
    echo \"✓ 完成\n\n\";
    
    echo \"==========================================\n\";
    echo \"数据库更新成功！\n\";
    echo \"==========================================\n\";
    
} catch (PDOException \$e) {
    echo \"\n❌ 错误: \" .\$e->getMessage() .\"\n\";
    exit(1);
}
"

echo.
echo 按任意键退出...
pause >nul
