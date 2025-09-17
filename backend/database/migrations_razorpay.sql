-- Razorpay payment integration migration
-- Adds payment-related columns to orders and optional table for payment logs

ALTER TABLE orders
  ADD COLUMN payment_method VARCHAR(50) NULL AFTER status,
  ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER payment_method,
  ADD COLUMN razorpay_order_id VARCHAR(100) NULL AFTER payment_status,
  ADD COLUMN razorpay_payment_id VARCHAR(100) NULL AFTER razorpay_order_id,
  ADD COLUMN razorpay_signature VARCHAR(191) NULL AFTER razorpay_payment_id;

-- Optional payments log table
CREATE TABLE IF NOT EXISTS order_payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'razorpay',
  provider_order_id VARCHAR(100) NULL,
  provider_payment_id VARCHAR(100) NULL,
  provider_signature VARCHAR(191) NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  status VARCHAR(40) NOT NULL,
  payload JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);