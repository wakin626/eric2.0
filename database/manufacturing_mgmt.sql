-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 11:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `manufacturing_mgmt`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_type` enum('vat','non_vat') DEFAULT 'vat' COMMENT 'vat=VAT registered, non_vat=Non-VAT',
  `customer_tin` varchar(50) DEFAULT NULL,
  `customer_terms` int(11) DEFAULT 0 COMMENT 'Payment terms in days',
  `date_created` datetime DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `remove` tinyint(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_code`, `customer_name`, `customer_address`, `customer_type`, `customer_tin`, `customer_terms`, `date_created`, `status`, `remove`, `last_update`) VALUES
(4, 'SKI-001', 'SKINTEC ADVANCE INCORPORATED', 'THIS IS THE DELIVERY ADDRESS', 'vat', '000-000-000', 30, '2026-06-29 09:11:09', 1, 0, '2026-06-29 01:11:09'),
(5, 'DWE-002', 'DWELLBEING', 'THI IS THE DELIVERY ADDRESS', 'vat', '111-111-111', 30, '2026-06-29 09:12:28', 1, 0, '2026-06-29 01:23:33'),
(6, 'LAY-003', 'LAY BARE', 'THIS IS THE DELIVERY ADDRESS', 'vat', '222-222-222', 60, '2026-06-29 09:13:23', 1, 0, '2026-06-29 01:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `poi_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `delivered_by` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `remarks_type` varchar(20) DEFAULT NULL,
  `active_status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `date_created` datetime DEFAULT current_timestamp(),
  `delivery_quantity` int(11) DEFAULT 0,
  `dr_number` varchar(50) DEFAULT NULL COMMENT 'Delivery Receipt number',
  `lot_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of lot details [{lot_id, poi_id, qty}]' CHECK (json_valid(`lot_items`)),
  `remove` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`delivery_id`, `po_id`, `poi_id`, `lot_id`, `delivered_by`, `delivery_date`, `remarks`, `remarks_type`, `active_status`, `date_created`, `delivery_quantity`, `dr_number`, `lot_items`, `remove`) VALUES
(16, 19, 22, NULL, 4, '2026-06-29', 'partial', NULL, 1, '2026-06-29 09:48:03', 1500, 'DR-001', '[{\"lot_id\":16,\"poi_id\":22,\"lot_number\":\"000-001\",\"item_code\":\"GRP-002\",\"item_description\":\"Grips Hair Clay 25g\",\"qty\":1500,\"item_uom\":\"PCS\",\"uom_conversion\":50}]', 0),
(17, 19, 23, NULL, 4, '2026-06-29', 'partial', NULL, 1, '2026-06-29 09:50:35', 1500, 'DR-002', '[{\"lot_id\":17,\"poi_id\":23,\"lot_number\":\"000-011\",\"item_code\":\"GRP-001\",\"item_description\":\"Grips Hair Gel Assorted12gx12\",\"qty\":1500,\"item_uom\":\"PCS\",\"uom_conversion\":50}]', 0);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_receipts`
--

CREATE TABLE `delivery_receipts` (
  `receipt_id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `remove` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_receipts`
--

INSERT INTO `delivery_receipts` (`receipt_id`, `delivery_id`, `po_id`, `file_name`, `file_path`, `file_type`, `file_size`, `uploaded_by`, `remove`, `date_created`) VALUES
(3, 17, 19, 'OCEAN RAIN 1GAL LABEL.png', 'uploads/receipts/dr_photo_17_1782725574.png', 'image/png', 257896, 4, 0, '2026-06-29 17:32:54'),
(4, 17, 19, 'doc00617620260629154515.pdf', 'uploads/receipts/receipt_17_1782725630.pdf', 'application/pdf', 234281, 7, 0, '2026-06-29 17:33:50');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `item_uom` varchar(50) NOT NULL COMMENT 'Unit of Measurement',
  `uom_conversion` int(11) DEFAULT NULL COMMENT 'Units per case, e.g. 10 means 10 PCS = 1 CS. NULL when UOM is CS',
  `item_size` varchar(50) DEFAULT NULL,
  `item_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `date_created` datetime DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `remove` tinyint(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `item_code`, `item_description`, `item_uom`, `uom_conversion`, `item_size`, `item_amount`, `date_created`, `status`, `remove`, `last_update`) VALUES
(7, 'GRP-001', 'Grips Hair Gel Assorted12gx12', 'PCS', 50, NULL, 0.00, '2026-06-29 09:20:02', 1, 0, '2026-06-29 01:20:02'),
(8, 'GRP-002', 'Grips Hair Clay 25g', 'PCS', 50, NULL, 0.00, '2026-06-29 09:20:32', 1, 0, '2026-06-29 01:20:32'),
(9, 'SOO-001', 'Soothing Cream 30mL', 'PCS', 10, NULL, 0.00, '2026-06-29 09:22:02', 1, 0, '2026-06-29 01:22:02'),
(10, 'SOO-002', 'Soothing Cream 100mL', 'PCS', 10, NULL, 0.00, '2026-06-29 09:22:52', 1, 0, '2026-06-29 01:22:52'),
(11, 'FLO-001', 'FLora Filipinas Hand Soap 375mL', 'PCS', 15, NULL, 0.00, '2026-06-29 09:29:46', 1, 0, '2026-06-29 01:29:46'),
(12, 'FLO-002', 'FLora Filipinas Hand Soap 750mL', 'PCS', 15, NULL, 0.00, '2026-06-29 09:32:05', 1, 0, '2026-06-29 01:32:05');

-- --------------------------------------------------------

--
-- Table structure for table `price_list`
--

CREATE TABLE `price_list` (
  `price_list_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `net_size` varchar(100) DEFAULT NULL,
  `price_per_pack` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_per_case` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_per_piece` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_type` enum('vat','non_vat') DEFAULT 'vat',
  `status` tinyint(1) DEFAULT 1,
  `remove` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp(),
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `price_list`
--

INSERT INTO `price_list` (`price_list_id`, `item_id`, `product_name`, `net_size`, `price_per_pack`, `price_per_case`, `price_per_piece`, `vat_type`, `status`, `remove`, `date_created`, `last_update`) VALUES
(3, 7, 'Grips Hair Gel Assorted12gx12', '12g', 13.20, 633.60, 1.10, 'vat', 1, 0, '2026-06-29 11:17:26', '2026-06-29 03:18:20'),
(4, 8, 'Grips Hair Clay 25g', '25g', 0.00, 780.00, 16.25, 'vat', 1, 0, '2026-06-29 11:18:06', '2026-06-29 03:18:06');

-- --------------------------------------------------------

--
-- Table structure for table `production_history`
--

CREATE TABLE `production_history` (
  `history_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `previous_quantity` int(11) DEFAULT 0,
  `added_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_history`
--

INSERT INTO `production_history` (`history_id`, `po_id`, `user_id`, `previous_quantity`, `added_quantity`, `new_quantity`, `date_created`) VALUES
(25, 19, 6, 0, 1500, 1500, '2026-06-29 09:41:42'),
(26, 19, 6, 0, 1500, 1500, '2026-06-29 09:41:42');

-- --------------------------------------------------------

--
-- Table structure for table `production_lots`
--

CREATE TABLE `production_lots` (
  `lot_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `poi_id` int(11) NOT NULL,
  `lot_number` varchar(100) NOT NULL,
  `quantity_produced` int(11) NOT NULL DEFAULT 0,
  `lot_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `is_removed` tinyint(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_lots`
--

INSERT INTO `production_lots` (`lot_id`, `po_id`, `poi_id`, `lot_number`, `quantity_produced`, `lot_date`, `created_by`, `date_created`, `is_removed`, `last_update`) VALUES
(16, 19, 22, '000-001', 1500, '2026-06-29', 6, '2026-06-29 09:41:42', 0, '2026-06-29 01:41:42'),
(17, 19, 23, '000-011', 1500, '2026-06-29', 6, '2026-06-29 09:41:42', 0, '2026-06-29 01:41:42');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `customer_po_number` varchar(100) DEFAULT NULL,
  `customer_po_date` date DEFAULT NULL,
  `po_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','delivered') DEFAULT 'pending',
  `date_created` datetime DEFAULT current_timestamp(),
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_quantity` int(11) DEFAULT 0,
  `customer_terms` int(11) DEFAULT 0 COMMENT 'Payment terms in days',
  `production_type` enum('normal','advance') DEFAULT 'normal',
  `produced_quantity` int(11) DEFAULT 0,
  `delivered_quantity` int(11) DEFAULT 0,
  `remove` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `customer_po_number`, `customer_po_date`, `po_number`, `customer_id`, `requested_by`, `status`, `date_created`, `last_update`, `total_quantity`, `customer_terms`, `production_type`, `produced_quantity`, `delivered_quantity`, `remove`) VALUES
(19, 'PO1234', '2026-06-29', '', 4, 4, 'pending', '2026-06-29 09:40:05', '2026-06-29 01:50:35', 6000, 30, 'normal', 3000, 3000, 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `poi_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `produced_quantity` int(11) DEFAULT 0 COMMENT 'Produced quantity per item',
  `delivered_quantity` int(11) DEFAULT 0 COMMENT 'Delivered quantity per item',
  `unit_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`poi_id`, `po_id`, `item_id`, `quantity`, `produced_quantity`, `delivered_quantity`, `unit_price`) VALUES
(22, 19, 8, 3000, 1500, 1500, 0.00),
(23, 19, 7, 3000, 1500, 1500, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `so_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `date_created` datetime DEFAULT current_timestamp(),
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `department` enum('admin','warehouse','production','finance') NOT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `remove` tinyint(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
  `date_created` datetime DEFAULT current_timestamp(),
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `department`, `status`, `remove`, `date_created`, `last_update`) VALUES
(1, 'admin', 'admin@system.com', 'admin', 'System Admin', 'admin', 1, 0, '2026-05-12 11:25:48', '2026-05-12 03:25:48'),
(4, 'testwh', 'test@gmail.com', '$2y$10$z6TzWhhpzvImWluEkSgeAeyNvaJplJSj2Gh2p/FLrpfaE/tPLJcem', 'test', 'warehouse', 1, 0, '2026-05-12 13:34:04', '2026-05-12 05:34:04'),
(6, 'testprod', 'production@gmail.com', 'qwerty', 'production', 'production', 1, 0, '2026-05-13 10:13:52', '2026-05-13 02:13:52'),
(7, 'finance', 'finance@email.com', 'finance', 'Test Finance', 'finance', 1, 0, '2026-06-02 10:41:41', '2026-06-02 02:41:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `idx_customer_code` (`customer_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_remove` (`remove`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `delivered_by` (`delivered_by`),
  ADD KEY `lot_id` (`lot_id`);

--
-- Indexes for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `delivery_id` (`delivery_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `idx_item_code` (`item_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_remove` (`remove`);

--
-- Indexes for table `price_list`
--
ALTER TABLE `price_list`
  ADD PRIMARY KEY (`price_list_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `production_history`
--
ALTER TABLE `production_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Indexes for table `production_lots`
--
ALTER TABLE `production_lots`
  ADD PRIMARY KEY (`lot_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `poi_id` (`poi_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`poi_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`so_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `price_list`
--
ALTER TABLE `price_list`
  MODIFY `price_list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `production_history`
--
ALTER TABLE `production_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `production_lots`
--
ALTER TABLE `production_lots`
  MODIFY `lot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `poi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `so_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `deliveries_ibfk_3` FOREIGN KEY (`lot_id`) REFERENCES `production_lots` (`lot_id`);

--
-- Constraints for table `price_list`
--
ALTER TABLE `price_list`
  ADD CONSTRAINT `price_list_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`);

--
-- Constraints for table `production_lots`
--
ALTER TABLE `production_lots`
  ADD CONSTRAINT `production_lots_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `production_lots_ibfk_2` FOREIGN KEY (`poi_id`) REFERENCES `purchase_order_items` (`poi_id`),
  ADD CONSTRAINT `production_lots_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`);

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
