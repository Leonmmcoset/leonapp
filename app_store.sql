-- 创建数据库
-- CREATE DATABASE IF NOT EXISTS app_store;
USE awa;

-- 创建APP表
CREATE TABLE IF NOT EXISTS apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    age_rating ENUM('3+', '7+', '12+', '17+') NOT NULL,
    age_rating_description TEXT,
    platforms JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    version VARCHAR(20) NOT NULL,
    changelog TEXT NOT NULL,
    file_path VARCHAR(255) NOT NULL
);

-- 创建APP版本表
CREATE TABLE IF NOT EXISTS app_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    version VARCHAR(50) NOT NULL,
    changelog TEXT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
);

-- 创建APP预览图片表
CREATE TABLE IF NOT EXISTS app_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
);

-- 创建评价表
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (app_id, ip_address),
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
);

-- 创建管理员表
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- 插入默认管理员
INSERT IGNORE INTO admins (username, password) VALUES ("admin", "your_admin_password_hash");

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- 创建标签表
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 创建应用标签关联表
CREATE TABLE IF NOT EXISTS app_tags (
    app_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (app_id, tag_id),
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- 创建应用分类表
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- 应用与分类的多对多关系表
CREATE TABLE IF NOT EXISTS app_categories (
    app_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (app_id, category_id),
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- 修改评价表，支持文字评论并关联用户
SET @exist_ip_address = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'ip_address');
SET @sql = IF(@exist_ip_address > 0, 'ALTER TABLE reviews DROP COLUMN ip_address', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并删除unique_review索引（如果存在）
SET @exist_unique_review = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'reviews' AND INDEX_NAME = 'unique_review');
SET @sql = IF(@exist_unique_review > 0, 'ALTER TABLE reviews DROP INDEX unique_review', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加user_id列（如果不存在）
SET @exist_user_id = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@exist_user_id = 0, 'ALTER TABLE reviews ADD COLUMN user_id INT', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加comment列（如果不存在）
SET @exist_comment = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'comment');
SET @sql = IF(@exist_comment = 0, 'ALTER TABLE reviews ADD COLUMN comment TEXT', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加外键约束（如果不存在）
SET @exist_fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'user_id' AND CONSTRAINT_NAME = 'fk_reviews_users');
SET @sql = IF(@exist_fk = 0, 'ALTER TABLE reviews ADD CONSTRAINT fk_reviews_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
-- 检查并添加唯一索引（如果不存在）
SET @exist_unique_index = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'reviews' AND INDEX_NAME = 'unique_user_app_review');
SET @sql = IF(@exist_unique_index = 0, 'ALTER TABLE reviews ADD UNIQUE KEY unique_user_app_review (user_id, app_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加应用下载统计
SET @exist_download_count = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'awa' AND TABLE_NAME = 'app_versions' AND COLUMN_NAME = 'download_count');
SET @sql = IF(@exist_download_count = 0, 'ALTER TABLE app_versions ADD COLUMN download_count INT DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 创建下载历史表
CREATE TABLE IF NOT EXISTS download_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    version_id INT NOT NULL,
    download_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (version_id) REFERENCES app_versions(id) ON DELETE CASCADE
);

-- 创建用户收藏表
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    app_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, app_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
);

-- 创建开发者表
CREATE TABLE IF NOT EXISTS developers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 修改 apps 表，添加 developer_id 和 status 字段
SET @exist_developer_id = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apps' AND COLUMN_NAME = 'developer_id');
SET @sql = IF(@exist_developer_id = 0, 'ALTER TABLE apps ADD COLUMN developer_id INT', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_status = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apps' AND COLUMN_NAME = 'status');
SET @sql = IF(@exist_status = 0, 'ALTER TABLE apps ADD COLUMN status ENUM(''pending'', ''approved'', ''rejected'') DEFAULT ''pending''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加应用拒绝原因字段
SET @exist_rejection_reason = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apps' AND COLUMN_NAME = 'rejection_reason');
SET @sql = IF(@exist_rejection_reason = 0, 'ALTER TABLE apps ADD COLUMN rejection_reason TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_fk = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apps' AND COLUMN_NAME = 'developer_id' AND CONSTRAINT_NAME = 'fk_apps_developers');

-- 添加 social_links 字段到 developers 表
ALTER TABLE developers
ADD COLUMN social_links VARCHAR(255) DEFAULT '' AFTER password;
SET @sql = IF(@exist_fk = 0, 'ALTER TABLE apps ADD CONSTRAINT fk_apps_developers FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 创建应用推荐表
CREATE TABLE IF NOT EXISTS app_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
);

-- 创建应用更新通知表
CREATE TABLE IF NOT EXISTS app_update_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    app_id INT NOT NULL,
    version_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    FOREIGN KEY (version_id) REFERENCES app_versions(id) ON DELETE CASCADE
);

-- 创建用户反馈表
CREATE TABLE IF NOT EXISTS user_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    app_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE SET NULL
);

-- 修改app_versions表，添加最后更新时间戳用于热门排行
SET @exist_last_updated = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'app_versions' AND COLUMN_NAME = 'last_updated');
SET @sql = IF(@exist_last_updated = 0, 'ALTER TABLE app_versions ADD COLUMN last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

