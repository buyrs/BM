-- Create database and user for BM application
CREATE DATABASE IF NOT EXISTS bm_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'bm_user'@'%' IDENTIFIED BY 'bm_password';
GRANT ALL PRIVILEGES ON bm_app.* TO 'bm_user'@'%';
FLUSH PRIVILEGES;