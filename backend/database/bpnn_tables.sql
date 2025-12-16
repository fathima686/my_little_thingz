-- BPNN Neural Network Recommendation System Tables
-- This file creates tables for tracking user behavior and training the neural network

-- User behavior tracking table
CREATE TABLE `user_behavior` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `artwork_id` int(10) UNSIGNED NOT NULL,
  `behavior_type` enum('view', 'add_to_cart', 'add_to_wishlist', 'purchase', 'rating', 'remove_from_wishlist') NOT NULL,
  `rating_value` decimal(2,1) DEFAULT NULL COMMENT 'Rating from 1.0 to 5.0',
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_behavior_user` (`user_id`),
  KEY `idx_user_behavior_artwork` (`artwork_id`),
  KEY `idx_user_behavior_type` (`behavior_type`),
  KEY `idx_user_behavior_created` (`created_at`),
  CONSTRAINT `fk_user_behavior_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_behavior_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BPNN model configuration and training history
CREATE TABLE `bpnn_models` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_name` varchar(100) NOT NULL,
  `model_version` varchar(20) NOT NULL DEFAULT '1.0',
  `architecture` text NOT NULL COMMENT 'JSON configuration of network layers',
  `weights` longtext NOT NULL COMMENT 'Serialized neural network weights',
  `training_data_size` int(11) NOT NULL DEFAULT 0,
  `training_accuracy` decimal(5,4) DEFAULT NULL,
  `validation_accuracy` decimal(5,4) DEFAULT NULL,
  `training_loss` decimal(8,6) DEFAULT NULL,
  `validation_loss` decimal(8,6) DEFAULT NULL,
  `training_epochs` int(11) NOT NULL DEFAULT 0,
  `learning_rate` decimal(8,6) NOT NULL DEFAULT 0.01,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_model_name_version` (`model_name`, `model_version`),
  KEY `idx_bpnn_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Training data for BPNN
CREATE TABLE `bpnn_training_data` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `artwork_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `cart_count` int(11) NOT NULL DEFAULT 0,
  `wishlist_count` int(11) NOT NULL DEFAULT 0,
  `purchase_count` int(11) NOT NULL DEFAULT 0,
  `avg_rating` decimal(2,1) DEFAULT NULL,
  `days_since_created` int(11) NOT NULL DEFAULT 0,
  `is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `has_offer` tinyint(1) NOT NULL DEFAULT 0,
  `target_preference` decimal(3,2) NOT NULL COMMENT 'Target value for training (0.0 to 1.0)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_training_user` (`user_id`),
  KEY `idx_training_artwork` (`artwork_id`),
  KEY `idx_training_category` (`category_id`),
  CONSTRAINT `fk_training_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_training_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BPNN predictions cache
CREATE TABLE `bpnn_predictions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `artwork_id` int(10) UNSIGNED NOT NULL,
  `prediction_score` decimal(5,4) NOT NULL COMMENT 'Predicted preference score (0.0 to 1.0)',
  `model_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_artwork_prediction` (`user_id`, `artwork_id`),
  KEY `idx_predictions_user` (`user_id`),
  KEY `idx_predictions_score` (`prediction_score`),
  KEY `idx_predictions_expires` (`expires_at`),
  CONSTRAINT `fk_predictions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_predictions_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_predictions_model` FOREIGN KEY (`model_id`) REFERENCES `bpnn_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User preference profiles (derived from behavior)
CREATE TABLE `user_preference_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `preferred_categories` text DEFAULT NULL COMMENT 'JSON array of preferred category IDs',
  `price_range_min` decimal(10,2) DEFAULT NULL,
  `price_range_max` decimal(10,2) DEFAULT NULL,
  `avg_rating_preference` decimal(2,1) DEFAULT NULL,
  `prefers_trending` tinyint(1) NOT NULL DEFAULT 0,
  `prefers_offers` tinyint(1) NOT NULL DEFAULT 0,
  `activity_score` decimal(5,4) NOT NULL DEFAULT 0.0 COMMENT 'Overall user activity score',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_preference` (`user_id`),
  CONSTRAINT `fk_preference_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default BPNN model configuration
INSERT INTO `bpnn_models` (
  `model_name`, 
  `model_version`, 
  `architecture`, 
  `weights`, 
  `is_active`
) VALUES (
  'gift_preference_predictor',
  '1.0',
  '{"input_size": 10, "hidden_layers": [8, 6], "output_size": 1, "activation": "sigmoid", "learning_rate": 0.01}',
  '',
  1
);
















