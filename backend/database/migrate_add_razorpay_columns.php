<?php
// Run once to add Razorpay columns to orders and create order_payments table if missing.
// Usage: php backend/database/migrate_add_razorpay_columns.php

require_once __DIR__ . '/../config/database.php';

function columnExists(PDO $db, $table, $column) {
  // Use information_schema for portability and to avoid SHOW with placeholders
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
  $stmt->execute([$table, $column]);
  return (int)$stmt->fetchColumn() > 0;
}

function tableExists(PDO $db, $table) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
  $stmt->execute([$table]);
  return (int)$stmt->fetchColumn() > 0;
}

try {
  $db = (new Database())->getConnection();
  $db->beginTransaction();

  // Ensure orders table has the required columns
  $added = [];
  if (!columnExists($db, 'orders', 'payment_method')) {
    $db->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER status");
    $added[] = 'payment_method';
  }
  if (!columnExists($db, 'orders', 'payment_status')) {
    $db->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER payment_method");
    $added[] = 'payment_status';
  }
  if (!columnExists($db, 'orders', 'razorpay_order_id')) {
    $db->exec("ALTER TABLE orders ADD COLUMN razorpay_order_id VARCHAR(100) NULL AFTER payment_status");
    $added[] = 'razorpay_order_id';
  }
  if (!columnExists($db, 'orders', 'razorpay_payment_id')) {
    $db->exec("ALTER TABLE orders ADD COLUMN razorpay_payment_id VARCHAR(100) NULL AFTER razorpay_order_id");
    $added[] = 'razorpay_payment_id';
  }
  if (!columnExists($db, 'orders', 'razorpay_signature')) {
    $db->exec("ALTER TABLE orders ADD COLUMN razorpay_signature VARCHAR(191) NULL AFTER razorpay_payment_id");
    $added[] = 'razorpay_signature';
  }

  // Create order_payments table if missing
  if (!tableExists($db, 'order_payments')) {
    $db->exec("CREATE TABLE order_payments (
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
    )");
    $added[] = 'order_payments(table)';
  }

  $db->commit();

  echo json_encode(['status' => 'success', 'message' => 'Migration completed', 'added' => $added]);
} catch (Exception $e) {
  if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}