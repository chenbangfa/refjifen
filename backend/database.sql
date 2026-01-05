-- Database Schema for RefJifen (H5 Commercial Points System)
-- Version: 1.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(20) NOT NULL COMMENT 'Registration mobile',
  `password` varchar(255) NOT NULL COMMENT 'Login password hash',
  `pay_password` varchar(255) DEFAULT NULL COMMENT '6-digit payment pin hash',
  `nickname` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT 0 COMMENT 'Placement Parent ID (Upline)',
  `position` enum('L','R') DEFAULT NULL COMMENT 'Position under parent: L=Left, R=Right',
  `level` tinyint(2) DEFAULT 0 COMMENT '0=None, 1=Gold, 2=Diamond',
  `is_sub_account` tinyint(1) DEFAULT 0 COMMENT '0=Master, 1=Sub',
  `linked_mobile` varchar(20) DEFAULT NULL COMMENT 'Links Master and Sub accounts',
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Banned',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Accounts (Dual Structure)';

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `user_id` int(11) NOT NULL,
  `balance` decimal(20,4) DEFAULT 0.0000 COMMENT 'Withdrawable Balance',
  `traffic_points` decimal(20,4) DEFAULT 0.0000 COMMENT 'Traffic Points (Core Asset)',
  `vouchers` decimal(20,4) DEFAULT 0.0000 COMMENT 'Shopping Vouchers',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Assets Wallet';

-- --------------------------------------------------------

--
-- Table structure for table `performance`
--

CREATE TABLE `performance` (
  `user_id` int(11) NOT NULL,
  `left_total` decimal(20,4) DEFAULT 0.0000 COMMENT 'Total Accumulated Left Region Sales',
  `right_total` decimal(20,4) DEFAULT 0.0000 COMMENT 'Total Accumulated Right Region Sales',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Performance Tracking (Infinite Levels)';

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `zone` enum('A','B') NOT NULL COMMENT 'A=Balance Zone (Points), B=Voucher Zone',
  `stock` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mall Products';

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `zone` enum('A','B') NOT NULL,
  `status` tinyint(2) DEFAULT 0 COMMENT '0=Unpaid, 1=Paid/Pending Ship, 2=Shipped, 3=Completed',
  `pay_time` timestamp NULL DEFAULT NULL,
  `address_info` text COMMENT 'JSON Snapshot of address',
  `tracking_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_sn` (`order_sn`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mall Orders';

-- --------------------------------------------------------

--
-- Table structure for table `logs_finance`
--

CREATE TABLE `logs_finance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'recharge, release, buy, transfer_in, transfer_out',
  `asset_type` varchar(20) NOT NULL COMMENT 'balance, points, vouchers',
  `amount` decimal(20,4) NOT NULL,
  `before_val` decimal(20,4) NOT NULL,
  `after_val` decimal(20,4) NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_type` (`user_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Financial Audit Logs';

-- --------------------------------------------------------

--
-- Table structure for table `daily_checkin_logs`
--

CREATE TABLE `daily_checkin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `checkin_date` date NOT NULL,
  `release_amount` decimal(20,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_date` (`user_id`, `checkin_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Prevent duplicate checkins';

COMMIT;
