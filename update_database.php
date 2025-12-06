<?php
/**
 * 数据库更新脚本
 * 用于添加 project 表的扫描状态相关字段
 */

// 直接使用 PDO 连接
$host = '127.0.0.1';
$port = '3306';
$dbname = 'ai-code';
$username = 'root';
$password = 'yu.157024';  // 使用你提供的密码

echo "开始更新数据库...\n\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ 数据库连接成功\n\n";
    
    // 获取表的所有字段
    $stmt = $pdo->query("SHOW COLUMNS FROM `project`");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "当前 project 表已有字段: " .implode(', ', $existingColumns) ."\n\n";
    
    // 需要添加的字段
    $columns = [
        [
            'name' => 'scan_status',
            'sql' => "ALTER TABLE `project` ADD COLUMN `scan_status` VARCHAR(20) DEFAULT 'pending' COMMENT '扫描状态'"
        ],
        [
            'name' => 'code_path',
            'sql' => "ALTER TABLE `project` ADD COLUMN `code_path` VARCHAR(500) DEFAULT NULL COMMENT 'CodeQL数据库路径'"
        ],
        [
            'name' => 'sarif_path',
            'sql' => "ALTER TABLE `project` ADD COLUMN `sarif_path` VARCHAR(500) DEFAULT NULL COMMENT 'SARIF结果文件路径'"
        ],
        [
            'name' => 'update_time',
            'sql' => "ALTER TABLE `project` ADD COLUMN `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'"
        ],
        [
            'name' => 'create_time',
            'sql' => "ALTER TABLE `project` ADD COLUMN `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'"
        ]
    ];
    
    // 逐个添加字段
    foreach ($columns as $column) {
        if (in_array($column['name'], $existingColumns)) {
            echo "✓ 字段 {$column['name']} 已存在，跳过\n";
        } else {
            echo "→ 正在添加字段 {$column['name']}...\n";
            try {
                $pdo->exec($column['sql']);
                echo "✓ 字段 {$column['name']} 添加成功\n";
            } catch (PDOException $e) {
                echo "✗ 字段 {$column['name']} 添加失败: " .$e->getMessage() ."\n";
            }
        }
    }
    
    echo "\n更新现有数据的默认值...\n";
    
    // 更新默认值
    $pdo->exec("UPDATE `project` SET `scan_status` = 'pending' WHERE `scan_status` IS NULL OR `scan_status` = ''");
    echo "✓ 更新 scan_status 默认值\n";
    
    $pdo->exec("UPDATE `project` SET `create_time` = NOW() WHERE `create_time` IS NULL");
    echo "✓ 更新 create_time 默认值\n";
    
    echo "\n==========================================\n";
    echo "数据库更新完成！\n";
    echo "==========================================\n\n";
    
    // 显示最终的表结构
    echo "最终表结构:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `project`");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($finalColumns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) Default: {$col['Default']}\n";
    }
    
    echo "\n现在可以访问项目列表了: http://localhost:8899/index.php/project\n";
    
} catch (PDOException $e) {
    echo "\n❌ 错误: " .$e->getMessage() ."\n";
    echo "\n请检查数据库连接配置是否正确\n";
    exit(1);
}

