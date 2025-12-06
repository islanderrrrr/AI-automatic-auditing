-- 更新 project 表结构
-- 添加扫描状态相关字段

-- 方式1: 适用于 MySQL 8.0+
-- ALTER TABLE `project` 
-- ADD COLUMN IF NOT EXISTS `scan_status` VARCHAR(20) DEFAULT 'pending' COMMENT '扫描状态',
-- ADD COLUMN IF NOT EXISTS `code_path` VARCHAR(500) DEFAULT NULL COMMENT 'CodeQL数据库路径',
-- ADD COLUMN IF NOT EXISTS `sarif_path` VARCHAR(500) DEFAULT NULL COMMENT 'SARIF结果文件路径',
-- ADD COLUMN IF NOT EXISTS `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
-- ADD COLUMN IF NOT EXISTS `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间';

-- 方式2: 适用于 MySQL 5.7 及以下版本（逐个添加字段，如果字段存在会报错但不影响）
ALTER TABLE `project` ADD COLUMN `scan_status` VARCHAR(20) DEFAULT 'pending' COMMENT '扫描状态: pending-待扫描, scanning-扫描中, completed-已完成, error-失败';
ALTER TABLE `project` ADD COLUMN `code_path` VARCHAR(500) DEFAULT NULL COMMENT 'CodeQL数据库路径';
ALTER TABLE `project` ADD COLUMN `sarif_path` VARCHAR(500) DEFAULT NULL COMMENT 'SARIF结果文件路径';
ALTER TABLE `project` ADD COLUMN `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间';
ALTER TABLE `project` ADD COLUMN `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间';

-- 更新现有数据的默认值
UPDATE `project` SET `scan_status` = 'pending' WHERE `scan_status` IS NULL OR `scan_status` = '';
UPDATE `project` SET `create_time` = NOW() WHERE `create_time` IS NULL;
