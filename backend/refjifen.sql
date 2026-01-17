-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-01-08 15:09:45
-- 服务器版本： 8.4.5
-- PHP 版本： 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `refjifen`
--

-- --------------------------------------------------------

--
-- 表的结构 `acceleration_rules`
--

CREATE TABLE `acceleration_rules` (
  `id` int NOT NULL,
  `min_performance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '双区最小业绩阈值',
  `daily_bonus` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '日释放额度',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `addresses`
--

CREATE TABLE `addresses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Receiver Name',
  `mobile` varchar(20) NOT NULL COMMENT 'Receiver Mobile',
  `province` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `detail` varchar(255) NOT NULL COMMENT 'Detailed Address',
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `assets`
--

CREATE TABLE `assets` (
  `user_id` int NOT NULL,
  `balance` decimal(20,4) DEFAULT '0.0000' COMMENT 'Withdrawable Balance',
  `traffic_points` decimal(20,4) DEFAULT '0.0000' COMMENT 'Traffic Points (Core Asset)',
  `vouchers` decimal(20,4) DEFAULT '0.0000' COMMENT 'Shopping Vouchers',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User Assets Wallet';

-- --------------------------------------------------------

--
-- 表的结构 `banners`
--

CREATE TABLE `banners` (
  `id` int NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` tinyint DEFAULT '1',
  `sort` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `sort` int DEFAULT '0' COMMENT 'Order, smaller first',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Product Categories';

-- --------------------------------------------------------

--
-- 表的结构 `daily_checkin_logs`
--

CREATE TABLE `daily_checkin_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `checkin_date` date NOT NULL,
  `release_amount` decimal(20,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Prevent duplicate checkins';

-- --------------------------------------------------------

--
-- 表的结构 `logs_finance`
--

CREATE TABLE `logs_finance` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'recharge, release, buy, transfer_in, transfer_out',
  `asset_type` varchar(20) NOT NULL COMMENT 'balance, points, vouchers',
  `amount` decimal(20,4) NOT NULL,
  `before_val` decimal(20,4) NOT NULL,
  `after_val` decimal(20,4) NOT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Financial Audit Logs';

-- --------------------------------------------------------

--
-- 表的结构 `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `order_sn` varchar(32) NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `amount` decimal(10,2) NOT NULL,
  `receiver_info` text COMMENT 'Snapshot of receiver address',
  `remark` varchar(255) DEFAULT NULL COMMENT 'Order Remark',
  `admin_remark` text COMMENT 'Admin internal remarks',
  `zone` enum('A','B') NOT NULL,
  `status` tinyint DEFAULT '0' COMMENT '0=Unpaid, 1=Paid/Pending Ship, 2=Shipped, 3=Completed',
  `pay_time` timestamp NULL DEFAULT NULL,
  `address_info` text COMMENT 'JSON Snapshot of address',
  `tracking_number` varchar(50) DEFAULT NULL,
  `express_company` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Mall Orders';

-- --------------------------------------------------------

--
-- 表的结构 `performance`
--

CREATE TABLE `performance` (
  `user_id` int NOT NULL,
  `left_total` decimal(20,4) DEFAULT '0.0000' COMMENT 'Total Accumulated Left Region Sales',
  `right_total` decimal(20,4) DEFAULT '0.0000' COMMENT 'Total Accumulated Right Region Sales',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Performance Tracking (Infinite Levels)';

-- --------------------------------------------------------

--
-- 表的结构 `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `category_id` int DEFAULT '0' COMMENT 'Category ID, 0=Uncategorized',
  `title` varchar(255) NOT NULL,
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT '件' COMMENT 'Product Unit',
  `traffic_ratio` decimal(5,2) DEFAULT '1.00' COMMENT 'Traffic points multiplier (e.g., 2.00 means 2x price)',
  `zone` enum('A','B') NOT NULL COMMENT 'A=Balance Zone (Points), B=Voucher Zone',
  `stock` int DEFAULT '0',
  `sales` int DEFAULT '0' COMMENT 'Sales Count',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Mall Products';

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `mobile` varchar(20) NOT NULL COMMENT 'Registration mobile',
  `password` varchar(255) NOT NULL COMMENT 'Login password hash',
  `pay_password` varchar(255) DEFAULT NULL COMMENT '6-digit payment pin hash',
  `nickname` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `parent_id` int DEFAULT '0' COMMENT 'Placement Parent ID (Upline)',
  `position` enum('L','R') DEFAULT NULL COMMENT 'Position under parent: L=Left, R=Right',
  `level` tinyint DEFAULT '0' COMMENT '0=None, 1=Gold, 2=Diamond',
  `is_sub_account` tinyint(1) DEFAULT '0' COMMENT '0=Master, 1=Sub',
  `linked_mobile` varchar(20) DEFAULT NULL COMMENT 'Links Master and Sub accounts',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Banned',
  `is_frozen` tinyint(1) DEFAULT '0' COMMENT '1=Frozen, 0=Normal',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User Accounts (Dual Structure)';

ALTER TABLE `users` ADD `is_frozen` tinyint(1) DEFAULT '0' COMMENT '1=Frozen, 0=Normal';
-- --------------------------------------------------------

--
-- 表的结构 `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL,
  `details` text NOT NULL,
  `status` tinyint(1) DEFAULT '0',
  `admin_note` varchar(255) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL COMMENT 'Payment proof or reject reason image',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `acceleration_rules`
--
ALTER TABLE `acceleration_rules`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 表的索引 `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `daily_checkin_logs`
--
ALTER TABLE `daily_checkin_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_date` (`user_id`,`checkin_date`);

--
-- 表的索引 `logs_finance`
--
ALTER TABLE `logs_finance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type` (`user_id`,`type`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_sn` (`order_sn`),
  ADD KEY `idx_user` (`user_id`);

--
-- 表的索引 `performance`
--
ALTER TABLE `performance`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- 表的索引 `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `acceleration_rules`
--
ALTER TABLE `acceleration_rules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `daily_checkin_logs`
--
ALTER TABLE `daily_checkin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `logs_finance`
--
ALTER TABLE `logs_finance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- 2026-01-09 Traffic Points Reward Rules
CREATE TABLE IF NOT EXISTS `traffic_reward_rules` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `min_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Minimum Order Amount',
    `ratio` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT 'Reward Ratio',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `traffic_reward_rules` (min_amount, ratio) VALUES (0, 1.00);
INSERT INTO `traffic_reward_rules` (min_amount, ratio) VALUES (4000, 3.00);
INSERT INTO `traffic_reward_rules` (min_amount, ratio) VALUES (20000, 4.00);

-- 2026-01-09 After Sales / Refund Table
CREATE TABLE IF NOT EXISTS `after_sales` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `order_sn` varchar(32) NOT NULL,
    `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=Refund Only, 2=Return & Refund',
    `reason` varchar(255) NOT NULL,
    `images` text COMMENT 'Proof images',
    `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=Pending, 1=Approved, 2=Rejected',
    `admin_reply` varchar(255) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_order_sn` (`order_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2026-01-14 Navigation Items
CREATE TABLE IF NOT EXISTS `nav_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Item Name',
  `icon` varchar(255) NOT NULL COMMENT 'Icon Image URL',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Link, 2=Popup',
  `content` text COMMENT 'URL to jump or Content to show',
  `sort` int DEFAULT '0' COMMENT 'Order',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Show, 0=Hide',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Home Navigation Grid';
