-- 添加 social_links 字段到 developers 表
ALTER TABLE developers
ADD COLUMN social_links VARCHAR(255) DEFAULT '' AFTER password;