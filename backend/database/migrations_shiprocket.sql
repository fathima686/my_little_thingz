-- Migration to add Shiprocket fields to orders table
-- Run this migration to enable full Shiprocket courier functionality

-- Add Shiprocket-related columns to orders table
ALTER TABLE `orders` 
ADD COLUMN `shiprocket_order_id` INT NULL COMMENT 'Shiprocket order ID' AFTER `tracking_number`,
ADD COLUMN `shiprocket_shipment_id` INT NULL COMMENT 'Shiprocket shipment ID' AFTER `shiprocket_order_id`,
ADD COLUMN `courier_id` INT NULL COMMENT 'Courier company ID' AFTER `shiprocket_shipment_id`,
ADD COLUMN `courier_name` VARCHAR(100) NULL COMMENT 'Courier company name' AFTER `courier_id`,
ADD COLUMN `awb_code` VARCHAR(100) NULL COMMENT 'AWB tracking code' AFTER `courier_name`,
ADD COLUMN `pickup_scheduled_date` DATETIME NULL COMMENT 'Scheduled pickup date' AFTER `awb_code`,
ADD COLUMN `pickup_token_number` VARCHAR(100) NULL COMMENT 'Pickup token number' AFTER `pickup_scheduled_date`,
ADD COLUMN `label_url` VARCHAR(500) NULL COMMENT 'Shipping label URL' AFTER `pickup_token_number`,
ADD COLUMN `manifest_url` VARCHAR(500) NULL COMMENT 'Manifest URL' AFTER `label_url`,
ADD COLUMN `shipping_charges` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Actual shipping charges' AFTER `manifest_url`,
ADD COLUMN `weight` DECIMAL(10,2) NULL DEFAULT 0.50 COMMENT 'Package weight in kg' AFTER `shipping_charges`,
ADD COLUMN `length` DECIMAL(10,2) NULL DEFAULT 10.00 COMMENT 'Package length in cm' AFTER `weight`,
ADD COLUMN `breadth` DECIMAL(10,2) NULL DEFAULT 10.00 COMMENT 'Package breadth in cm' AFTER `length`,
ADD COLUMN `height` DECIMAL(10,2) NULL DEFAULT 10.00 COMMENT 'Package height in cm' AFTER `breadth`;

-- Add indexes for better query performance
ALTER TABLE `orders` 
ADD INDEX `idx_shiprocket_order_id` (`shiprocket_order_id`),
ADD INDEX `idx_shiprocket_shipment_id` (`shiprocket_shipment_id`),
ADD INDEX `idx_awb_code` (`awb_code`),
ADD INDEX `idx_courier_id` (`courier_id`);

-- Update existing tracking_number column to allow NULL and increase length
ALTER TABLE `orders` 
MODIFY COLUMN `tracking_number` VARCHAR(100) NULL;

-- Create a table to store courier serviceability cache (optional, for performance)
CREATE TABLE IF NOT EXISTS `courier_serviceability_cache` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pickup_pincode` VARCHAR(10) NOT NULL,
  `delivery_pincode` VARCHAR(10) NOT NULL,
  `weight` DECIMAL(10,2) NOT NULL,
  `cod` TINYINT(1) NOT NULL DEFAULT 0,
  `courier_data` TEXT NOT NULL COMMENT 'JSON data of available couriers',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_pincodes` (`pickup_pincode`, `delivery_pincode`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a table to store shipment tracking history
CREATE TABLE IF NOT EXISTS `shipment_tracking_history` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `awb_code` VARCHAR(100) NOT NULL,
  `status` VARCHAR(100) NOT NULL,
  `status_code` VARCHAR(50) NULL,
  `location` VARCHAR(255) NULL,
  `remarks` TEXT NULL,
  `tracking_date` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_awb_code` (`awb_code`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comments to the orders table
ALTER TABLE `orders` 
COMMENT = 'Customer orders with Shiprocket integration';