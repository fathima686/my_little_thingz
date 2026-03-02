-- MySQL Configuration to handle large packets
-- Run this in your MySQL/phpMyAdmin to increase limits

-- Increase max_allowed_packet to 64MB
SET GLOBAL max_allowed_packet = 67108864;

-- Increase net_buffer_length
SET GLOBAL net_buffer_length = 32768;

-- Show current settings
SHOW VARIABLES LIKE 'max_allowed_packet';
SHOW VARIABLES LIKE 'net_buffer_length';

-- Note: These settings will reset on MySQL restart
-- To make permanent, add to my.cnf or my.ini:
-- [mysqld]
-- max_allowed_packet = 64M
-- net_buffer_length = 32K