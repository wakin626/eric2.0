-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2026 at 02:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

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
-- Table structure for table `advance_production_consumption`
--

CREATE TABLE `advance_production_consumption` (
  `id` int(11) NOT NULL,
  `advance_poi_id` int(11) NOT NULL,
  `advance_po_id` int(11) NOT NULL,
  `normal_poi_id` int(11) NOT NULL,
  `normal_po_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date_allocated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advance_production_consumption`
--

INSERT INTO `advance_production_consumption` (`id`, `advance_poi_id`, `advance_po_id`, `normal_poi_id`, `normal_po_id`, `quantity`, `date_allocated`) VALUES
(1, 66, 33, 68, 35, 100680, '2026-07-10 23:42:06');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `action` varchar(20) NOT NULL COMMENT 'LOGIN, LOGOUT, CREATE, UPDATE, DELETE',
  `module` varchar(50) NOT NULL COMMENT 'auth, admin, warehouse, production, finance',
  `target_type` varchar(50) NOT NULL COMMENT 'user, customer, item, po, delivery, production, excess, receipt, price_list',
  `target_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `username`, `department`, `action`, `module`, `target_type`, `target_id`, `description`, `old_values`, `new_values`, `created_at`) VALUES
(1, 4, 'testwh', 'warehouse', 'UPDATE', 'warehouse', 'purchase_order', 25, 'Edited PO PO-00000014', '{\"customer_po_number\":\"PO-00000014\",\"customer_po_date\":\"2026-07-01\",\"production_type\":\"normal\",\"items\":[{\"poi_id\":null,\"item_id\":24,\"quantity\":584352},{\"poi_id\":null,\"item_id\":232,\"quantity\":352800},{\"poi_id\":null,\"item_id\":25,\"quantity\":581472},{\"poi_id\":null,\"item_id\":230,\"quantity\":134784},{\"poi_id\":null,\"item_id\":22,\"quantity\":219456},{\"poi_id\":null,\"item_id\":181,\"quantity\":6359040},{\"poi_id\":null,\"item_id\":78,\"quantity\":4447872},{\"poi_id\":null,\"item_id\":18,\"quantity\":526464},{\"poi_id\":null,\"item_id\":79,\"quantity\":21960},{\"poi_id\":null,\"item_id\":74,\"quantity\":5136},{\"poi_id\":null,\"item_id\":70,\"quantity\":1620},{\"poi_id\":null,\"item_id\":62,\"quantity\":2087856},{\"poi_id\":null,\"item_id\":72,\"quantity\":3744},{\"poi_id\":null,\"item_id\":71,\"quantity\":3816},{\"poi_id\":null,\"item_id\":93,\"quantity\":7056},{\"poi_id\":null,\"item_id\":96,\"quantity\":10512},{\"poi_id\":null,\"item_id\":141,\"quantity\":9456},{\"poi_id\":null,\"item_id\":143,\"quantity\":28128},{\"poi_id\":null,\"item_id\":145,\"quantity\":11784},{\"poi_id\":null,\"item_id\":34,\"quantity\":404352},{\"poi_id\":null,\"item_id\":231,\"quantity\":220032},{\"poi_id\":null,\"item_id\":26,\"quantity\":48000}]}', '{\"customer_po_number\":\"PO-00000014\",\"customer_po_date\":\"2026-07-01\",\"production_type\":\"normal\",\"items\":[{\"poi_id\":null,\"item_id\":24,\"quantity\":584352},{\"poi_id\":null,\"item_id\":232,\"quantity\":352800},{\"poi_id\":null,\"item_id\":25,\"quantity\":581472},{\"poi_id\":null,\"item_id\":230,\"quantity\":134784},{\"poi_id\":null,\"item_id\":22,\"quantity\":219456},{\"poi_id\":null,\"item_id\":181,\"quantity\":6359040},{\"poi_id\":null,\"item_id\":78,\"quantity\":4447872},{\"poi_id\":null,\"item_id\":18,\"quantity\":526464},{\"poi_id\":null,\"item_id\":79,\"quantity\":21960},{\"poi_id\":null,\"item_id\":74,\"quantity\":5136},{\"poi_id\":null,\"item_id\":70,\"quantity\":1620},{\"poi_id\":null,\"item_id\":62,\"quantity\":2087856},{\"poi_id\":null,\"item_id\":72,\"quantity\":3744},{\"poi_id\":null,\"item_id\":71,\"quantity\":3816},{\"poi_id\":null,\"item_id\":93,\"quantity\":7056},{\"poi_id\":null,\"item_id\":96,\"quantity\":10512},{\"poi_id\":null,\"item_id\":141,\"quantity\":9456},{\"poi_id\":null,\"item_id\":143,\"quantity\":28128},{\"poi_id\":null,\"item_id\":145,\"quantity\":11784},{\"poi_id\":null,\"item_id\":34,\"quantity\":404352},{\"poi_id\":null,\"item_id\":231,\"quantity\":220032},{\"poi_id\":null,\"item_id\":26,\"quantity\":48000}]}', '2026-07-10 04:05:53'),
(2, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 31, 'Updated production quantity for PO-00000014', NULL, '{\"produced_quantity\":\"12672\"}', '2026-07-10 04:52:27'),
(3, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 31, 'Updated production quantity for PO-00000014', NULL, '{\"produced_quantity\":\"5760\"}', '2026-07-10 04:53:58'),
(4, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 31, 'Updated production quantity for PO-00000014', NULL, '{\"produced_quantity\":\"12672\"}', '2026-07-10 04:55:17'),
(5, 11, 'Cath', 'production', 'CREATE', 'production', 'production_history', NULL, 'Reported production history', NULL, '{\"history_id\":\"73\",\"report_type\":\"lot_number\",\"reason\":\"pacancel po ako nito mali po ang input ko \\r\\n\\r\\nThanks po\"}', '2026-07-10 04:55:57'),
(6, 11, 'Cath', 'production', 'CREATE', 'production', 'production_history', NULL, 'Reported production history', NULL, '{\"history_id\":\"56\",\"report_type\":\"lot_number\",\"reason\":\"Good Day po Makikisuyo po ako papalitan po ang Item Code Imbis na Barelab Sleek dapat Barelab Anti Hairfall QTY 12962pcs\\r\\n\\r\\nThank you \\r\\n\"}', '2026-07-10 05:01:39'),
(7, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014', NULL, '{\"produced_quantity\":\"7200\"}', '2026-07-10 05:07:05'),
(8, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 66, 'Updated production quantity for ADV253', NULL, '{\"produced_quantity\":\"12672\"}', '2026-07-10 05:15:34'),
(9, 11, 'Cath', 'production', 'CREATE', 'production', 'production_history', NULL, 'Reported production history', NULL, '{\"history_id\":\"76\",\"report_type\":\"lot_number\",\"reason\":\"Double input \\r\\n\\r\\nThank you\\r\\n\"}', '2026-07-10 05:18:15'),
(10, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-10 05:19:51'),
(11, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-10 05:19:55'),
(12, 1, 'admin', 'admin', 'UPDATE', 'admin', 'production_history', 76, 'Edited production history #76', NULL, '{\"added_quantity\":7200,\"lot_number\":\"151-306\"}', '2026-07-10 05:21:53'),
(13, 6, 'testprod', 'production', 'LOGOUT', 'auth', 'user', 6, 'User logged out: testprod', NULL, NULL, '2026-07-10 05:26:01'),
(14, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-10 05:26:14'),
(15, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-10 05:32:13'),
(16, 6, 'testprod', 'production', 'LOGOUT', 'auth', 'user', 6, 'User logged out: testprod', NULL, NULL, '2026-07-10 05:32:26'),
(17, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-10 05:32:30'),
(18, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-10 05:43:41'),
(19, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-10 05:43:45'),
(20, 1, 'admin', 'admin', 'UPDATE', 'admin', 'production_history', 56, 'Edited production history #56', NULL, '{\"added_quantity\":12672,\"lot_number\":\"151-313\"}', '2026-07-10 06:06:14'),
(21, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-10 06:47:02'),
(22, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-10 06:47:09'),
(23, 14, 'QUEENSEE', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created deliveries for PO-00000014 (DR: DR17259)', NULL, '{\"lot_ids\":\"25:32544,37:12672,32:31104\"}', '2026-07-10 07:19:54'),
(24, 14, 'QUEENSEE', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created deliveries for PO-00000014 (DR: DR17262)', NULL, '{\"lot_ids\":\"40:5760,29:19296,34:25344\"}', '2026-07-10 07:31:03'),
(25, 6, 'testprod', 'production', 'DELETE', 'admin', 'production_history', 73, 'Deleted production history #73', NULL, '{\"history_id\":\"73\"}', '2026-07-10 07:43:32'),
(26, 6, 'testprod', 'production', 'DELETE', 'admin', 'production_history', 56, 'Deleted production history #56', NULL, '{\"history_id\":\"56\"}', '2026-07-10 07:44:23'),
(27, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 32, 'Updated production quantity for PO-00000014: added 12962 pcs for lot 151-313 (previous 38016 → new 50978)', '{\"previous_quantity\":38016,\"added_quantity\":0,\"new_quantity\":38016,\"lot_number\":\"151-313\"}', '{\"previous_quantity\":38016,\"added_quantity\":12962,\"new_quantity\":50978,\"lot_number\":\"151-313\"}', '2026-07-10 09:06:17'),
(28, 11, 'Cath', 'production', 'LOGIN', 'auth', 'user', 11, 'User logged in: Cath', NULL, '{\"username\":\"Cath\"}', '2026-07-10 22:22:57'),
(29, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 31, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 159-112 (previous 69120 → new 81792)', '{\"previous_quantity\":69120,\"added_quantity\":0,\"new_quantity\":69120,\"lot_number\":\"159-112\"}', '{\"previous_quantity\":69120,\"added_quantity\":12672,\"new_quantity\":81792,\"lot_number\":\"159-112\"}', '2026-07-10 22:24:01'),
(30, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 6336 pcs for lot 151-308 (previous 63648 → new 69984)', '{\"previous_quantity\":63648,\"added_quantity\":0,\"new_quantity\":63648,\"lot_number\":\"151-308\"}', '{\"previous_quantity\":63648,\"added_quantity\":6336,\"new_quantity\":69984,\"lot_number\":\"151-308\"}', '2026-07-10 22:25:09'),
(31, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 151-309 (previous 69984 → new 82656)', '{\"previous_quantity\":69984,\"added_quantity\":0,\"new_quantity\":69984,\"lot_number\":\"151-309\"}', '{\"previous_quantity\":69984,\"added_quantity\":12672,\"new_quantity\":82656,\"lot_number\":\"151-309\"}', '2026-07-10 22:26:36'),
(32, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 37, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 124-550 (previous 0 → new 12672)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"124-550\"}', '{\"previous_quantity\":0,\"added_quantity\":12672,\"new_quantity\":12672,\"lot_number\":\"124-550\"}', '2026-07-10 22:27:27'),
(33, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 151-308 (previous 82656 → new 95328)', '{\"previous_quantity\":82656,\"added_quantity\":0,\"new_quantity\":82656,\"lot_number\":\"151-308\"}', '{\"previous_quantity\":82656,\"added_quantity\":12672,\"new_quantity\":95328,\"lot_number\":\"151-308\"}', '2026-07-10 22:30:58'),
(34, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 28, 'Updated production quantity for PO-00000062: added 12672 pcs for lot 152-204 (previous 145728 → new 158400)', '{\"previous_quantity\":145728,\"added_quantity\":0,\"new_quantity\":145728,\"lot_number\":\"152-204\"}', '{\"previous_quantity\":145728,\"added_quantity\":12672,\"new_quantity\":158400,\"lot_number\":\"152-204\"}', '2026-07-10 22:31:34'),
(35, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 6912 pcs for lot 151-307 (previous 95328 → new 102240)', '{\"previous_quantity\":95328,\"added_quantity\":0,\"new_quantity\":95328,\"lot_number\":\"151-307\"}', '{\"previous_quantity\":95328,\"added_quantity\":6912,\"new_quantity\":102240,\"lot_number\":\"151-307\"}', '2026-07-10 22:33:08'),
(36, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 37, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 124-549 (previous 12672 → new 25344)', '{\"previous_quantity\":12672,\"added_quantity\":0,\"new_quantity\":12672,\"lot_number\":\"124-549\"}', '{\"previous_quantity\":12672,\"added_quantity\":12672,\"new_quantity\":25344,\"lot_number\":\"124-549\"}', '2026-07-10 22:33:47'),
(37, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 151-307 (previous 102240 → new 114912)', '{\"previous_quantity\":102240,\"added_quantity\":0,\"new_quantity\":102240,\"lot_number\":\"151-307\"}', '{\"previous_quantity\":102240,\"added_quantity\":12672,\"new_quantity\":114912,\"lot_number\":\"151-307\"}', '2026-07-10 22:34:28'),
(38, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 151-308 (previous 114912 → new 127584)', '{\"previous_quantity\":114912,\"added_quantity\":0,\"new_quantity\":114912,\"lot_number\":\"151-308\"}', '{\"previous_quantity\":114912,\"added_quantity\":12672,\"new_quantity\":127584,\"lot_number\":\"151-308\"}', '2026-07-10 22:34:59'),
(39, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 31, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 15229 (previous 81792 → new 94464)', '{\"previous_quantity\":81792,\"added_quantity\":0,\"new_quantity\":81792,\"lot_number\":\"15229\"}', '{\"previous_quantity\":81792,\"added_quantity\":12672,\"new_quantity\":94464,\"lot_number\":\"15229\"}', '2026-07-10 22:35:47'),
(40, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 28, 'Updated production quantity for PO-00000062: added 12672 pcs for lot 152-204 (previous 158400 → new 171072)', '{\"previous_quantity\":158400,\"added_quantity\":0,\"new_quantity\":158400,\"lot_number\":\"152-204\"}', '{\"previous_quantity\":158400,\"added_quantity\":12672,\"new_quantity\":171072,\"lot_number\":\"152-204\"}', '2026-07-10 22:36:09'),
(41, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 32, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 157-159 (previous 50978 → new 63650)', '{\"previous_quantity\":50978,\"added_quantity\":0,\"new_quantity\":50978,\"lot_number\":\"157-159\"}', '{\"previous_quantity\":50978,\"added_quantity\":12672,\"new_quantity\":63650,\"lot_number\":\"157-159\"}', '2026-07-10 22:36:51'),
(42, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 28, 'Updated production quantity for PO-00000062: added 12672 pcs for lot 152-204 (previous 171072 → new 183744)', '{\"previous_quantity\":171072,\"added_quantity\":0,\"new_quantity\":171072,\"lot_number\":\"152-204\"}', '{\"previous_quantity\":171072,\"added_quantity\":12672,\"new_quantity\":183744,\"lot_number\":\"152-204\"}', '2026-07-10 22:37:14'),
(43, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 66, 'Updated production quantity for ADV253: added 12672 pcs for lot 118-856 (previous 82944 → new 95616)', '{\"previous_quantity\":82944,\"added_quantity\":0,\"new_quantity\":82944,\"lot_number\":\"118-856\"}', '{\"previous_quantity\":82944,\"added_quantity\":12672,\"new_quantity\":95616,\"lot_number\":\"118-856\"}', '2026-07-10 22:38:39'),
(44, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 67, 'Updated production quantity for ADV252: added 12672 pcs for lot 133-854 (previous 32832 → new 45504)', '{\"previous_quantity\":32832,\"added_quantity\":0,\"new_quantity\":32832,\"lot_number\":\"133-854\"}', '{\"previous_quantity\":32832,\"added_quantity\":12672,\"new_quantity\":45504,\"lot_number\":\"133-854\"}', '2026-07-10 22:41:46'),
(45, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 66, 'Updated production quantity for ADV253: added 4032 pcs for lot 118-856 (previous 95616 → new 99648)', '{\"previous_quantity\":95616,\"added_quantity\":0,\"new_quantity\":95616,\"lot_number\":\"118-856\"}', '{\"previous_quantity\":95616,\"added_quantity\":4032,\"new_quantity\":99648,\"lot_number\":\"118-856\"}', '2026-07-10 22:45:18'),
(46, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 66, 'Updated production quantity for ADV253: added 1032 pcs for lot 118-856 (previous 99648 → new 100680)', '{\"previous_quantity\":99648,\"added_quantity\":0,\"new_quantity\":99648,\"lot_number\":\"118-856\"}', '{\"previous_quantity\":99648,\"added_quantity\":1032,\"new_quantity\":100680,\"lot_number\":\"118-856\"}', '2026-07-10 22:46:00'),
(47, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 28, 'Updated production quantity for PO-00000062: added 12672 pcs for lot 152-204 (previous 183744 → new 196416)', '{\"previous_quantity\":183744,\"added_quantity\":0,\"new_quantity\":183744,\"lot_number\":\"152-204\"}', '{\"previous_quantity\":183744,\"added_quantity\":12672,\"new_quantity\":196416,\"lot_number\":\"152-204\"}', '2026-07-10 22:58:52'),
(48, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 32, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 157-160 (previous 63650 → new 76322)', '{\"previous_quantity\":63650,\"added_quantity\":0,\"new_quantity\":63650,\"lot_number\":\"157-160\"}', '{\"previous_quantity\":63650,\"added_quantity\":12672,\"new_quantity\":76322,\"lot_number\":\"157-160\"}', '2026-07-10 23:05:59'),
(49, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 28, 'Updated production quantity for PO-00000062: added 12384 pcs for lot 152-204 (previous 196416 → new 208800)', '{\"previous_quantity\":196416,\"added_quantity\":0,\"new_quantity\":196416,\"lot_number\":\"152-204\"}', '{\"previous_quantity\":196416,\"added_quantity\":12384,\"new_quantity\":208800,\"lot_number\":\"152-204\"}', '2026-07-10 23:26:09'),
(50, 12, 'ELMEI', 'warehouse', 'LOGIN', 'auth', 'user', 12, 'User logged in: ELMEI', NULL, '{\"username\":\"ELMEI\"}', '2026-07-10 23:33:42'),
(51, 12, 'ELMEI', 'warehouse', 'CREATE', 'warehouse', 'purchase_order', 35, 'Created PO PO-00000111 for S BRANDS CONSUMER CARE INC.', NULL, '{\"customer_po_number\":\"PO-00000111\",\"customer_po_date\":\"2026-07-08\",\"production_type\":\"normal\"}', '2026-07-10 23:42:06'),
(52, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-10 23:51:57'),
(53, 1, 'admin', 'admin', 'UPDATE', 'admin', 'item', 26, 'Updated item (inline): ', '{\"item_id\":26,\"item_code\":\"FG0672-BLKERTRCON180\",\"item_description\":\"BareLab Keratin Treatment Conditioner 180g\",\"customer_id\":19,\"item_uom\":\"PCS\",\"uom_conversion\":24,\"item_size\":null,\"item_amount\":\"0.00\",\"date_created\":\"2026-07-03 14:28:38\",\"status\":1,\"remove\":0,\"last_update\":\"2026-07-06 15:29:22\"}', '{\"item_name\":\"\",\"item_code\":\"FG0672-BLKERTRCON180\",\"description\":\"\"}', '2026-07-11 00:27:34'),
(54, 14, 'QUEENSEE', 'warehouse', 'LOGIN', 'auth', 'user', 14, 'User logged in: QUEENSEE', NULL, '{\"username\":\"QUEENSEE\"}', '2026-07-11 00:43:25'),
(55, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 51, 'Updated production quantity for PO-00000014: added 1140 pcs for lot 163-001 (previous 0 → new 1140)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"163-001\"}', '{\"previous_quantity\":0,\"added_quantity\":1140,\"new_quantity\":1140,\"lot_number\":\"163-001\"}', '2026-07-11 00:46:02'),
(56, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 37, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 124-550 (previous 25344 → new 38016)', '{\"previous_quantity\":25344,\"added_quantity\":0,\"new_quantity\":25344,\"lot_number\":\"124-550\"}', '{\"previous_quantity\":25344,\"added_quantity\":12672,\"new_quantity\":38016,\"lot_number\":\"124-550\"}', '2026-07-11 00:46:56'),
(57, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 30, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 151-310 (previous 127584 → new 140256)', '{\"previous_quantity\":127584,\"added_quantity\":0,\"new_quantity\":127584,\"lot_number\":\"151-310\"}', '{\"previous_quantity\":127584,\"added_quantity\":12672,\"new_quantity\":140256,\"lot_number\":\"151-310\"}', '2026-07-11 00:47:20'),
(58, 12, 'ELMEI', 'warehouse', 'UPDATE', 'warehouse', 'purchase_order', 23, 'Edited PO PO-00000062', '{\"customer_po_number\":\"PO-00000062\",\"customer_po_date\":\"2026-07-01\",\"production_type\":\"normal\",\"items\":[{\"poi_id\":null,\"item_id\":39,\"quantity\":672768}]}', '{\"customer_po_number\":\"PO-00000062\",\"customer_po_date\":\"2026-07-01\",\"production_type\":\"normal\",\"items\":[{\"action\":\"quantity_changed\",\"poi_id\":\"28\",\"item_id\":39,\"old_quantity\":672768,\"new_quantity\":1762560}]}', '2026-07-11 00:51:32'),
(59, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-11 00:54:21'),
(60, 12, 'ELMEI', 'warehouse', 'UPDATE', 'warehouse', 'purchase_order', 23, 'Edited PO PO-00000062', '{\"customer_po_number\":\"PO-00000062\",\"customer_po_date\":\"2026-07-01\",\"production_type\":\"normal\",\"items\":[{\"poi_id\":null,\"item_id\":39,\"quantity\":1762560},{\"poi_id\":null,\"item_id\":30,\"quantity\":146880}]}', '{\"customer_po_number\":\"PO-00000062\",\"customer_po_date\":\"2026-07-01\",\"production_type\":\"normal\",\"items\":[{\"action\":\"quantity_changed\",\"poi_id\":\"28\",\"item_id\":39,\"old_quantity\":1762560,\"new_quantity\":1615680}]}', '2026-07-11 00:57:58'),
(61, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 68, 'Updated production quantity for PO-00000111: added 12672 pcs for lot 118-854 (previous 100680 → new 113352)', '{\"previous_quantity\":100680,\"added_quantity\":0,\"new_quantity\":100680,\"lot_number\":\"118-854\"}', '{\"previous_quantity\":100680,\"added_quantity\":12672,\"new_quantity\":113352,\"lot_number\":\"118-854\"}', '2026-07-11 01:12:26'),
(62, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 69, 'Updated production quantity for PO-00000062: added 5256 pcs for lot 152-204 (previous 0 → new 5256)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"152-204\"}', '{\"previous_quantity\":0,\"added_quantity\":5256,\"new_quantity\":5256,\"lot_number\":\"152-204\"}', '2026-07-11 01:39:02'),
(63, 1, 'admin', 'admin', 'UPDATE', 'admin', 'item', 54, 'Updated item (inline): ', '{\"item_id\":54,\"item_code\":\"FG0263-KPSS22x11+1\",\"item_description\":\"Keratin Plus Shampoo Soft Smooth 22mlx11+1 Promo\",\"customer_id\":19,\"item_uom\":\"PCS\",\"uom_conversion\":288,\"item_size\":null,\"item_amount\":\"0.00\",\"date_created\":\"2026-07-03 15:35:54\",\"status\":1,\"remove\":0,\"last_update\":\"2026-07-03 15:35:54\"}', '{\"item_name\":\"\",\"item_code\":\"FG0263-KPSS22x11+1\",\"description\":\"\"}', '2026-07-11 01:39:13'),
(64, 1, 'admin', 'admin', 'UPDATE', 'admin', 'item', 39, 'Updated item (inline): ', '{\"item_id\":39,\"item_code\":\"FG0633-EMPSHAM11+1\",\"item_description\":\"Empress Shampoo Long & Healthy 21mlx24pck (11+1)\",\"customer_id\":19,\"item_uom\":\"PCS\",\"uom_conversion\":288,\"item_size\":null,\"item_amount\":\"0.00\",\"date_created\":\"2026-07-03 15:11:48\",\"status\":1,\"remove\":0,\"last_update\":\"2026-07-03 15:11:48\"}', '{\"item_name\":\"\",\"item_code\":\"FG0633-EMPSHAM11+1\",\"description\":\"\"}', '2026-07-11 01:39:47'),
(65, 14, 'QUEENSEE', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created deliveries for PO-00000014 (DR: DR17263)', NULL, '{\"lot_ids\":\"37:19584,44:31680,45:12672,53:12672\"}', '2026-07-11 01:59:05'),
(66, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 37, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 124-550 (previous 38016 → new 50688)', '{\"previous_quantity\":38016,\"added_quantity\":0,\"new_quantity\":38016,\"lot_number\":\"124-550\"}', '{\"previous_quantity\":38016,\"added_quantity\":12672,\"new_quantity\":50688,\"lot_number\":\"124-550\"}', '2026-07-11 02:28:14'),
(67, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-11 02:31:12'),
(68, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-11 02:31:21'),
(69, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-11 02:31:49'),
(70, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 37, 'Updated production quantity for PO-00000014: added 29664 pcs for lot 549 (previous 50688 → new 80352)', '{\"previous_quantity\":50688,\"added_quantity\":0,\"new_quantity\":50688,\"lot_number\":\"549\"}', '{\"previous_quantity\":50688,\"added_quantity\":29664,\"new_quantity\":80352,\"lot_number\":\"549\"}', '2026-07-11 02:34:55'),
(71, 6, 'testprod', 'production', 'LOGOUT', 'auth', 'user', 6, 'User logged out: testprod', NULL, NULL, '2026-07-11 02:40:17'),
(72, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-11 02:40:30'),
(73, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-11 02:42:52'),
(74, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-11 02:43:00'),
(75, 6, 'testprod', 'production', 'LOGOUT', 'auth', 'user', 6, 'User logged out: testprod', NULL, NULL, '2026-07-11 02:44:08'),
(76, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-11 02:44:58'),
(77, 1, 'admin', 'admin', 'UPDATE', 'admin', 'production_history', 106, 'Updated production quantity and lot for history #106', '{\"previous_quantity\":50688,\"added_quantity\":29664,\"new_quantity\":80352,\"lot_number\":\"549\",\"old_added_quantity\":null,\"old_lot_number\":null}', '{\"previous_quantity\":50688,\"added_quantity\":29664,\"new_quantity\":80352,\"lot_number\":\"124-549\",\"old_added_quantity\":29664,\"old_lot_number\":\"549\"}', '2026-07-11 02:45:16'),
(78, 11, 'Cath', 'production', 'LOGIN', 'auth', 'user', 11, 'User logged in: Cath', NULL, '{\"username\":\"cath\"}', '2026-07-11 02:49:51'),
(79, 11, 'Cath', 'production', 'UPDATE', 'production', 'purchase_order_item', 37, 'Updated production quantity for PO-00000014: added 12672 pcs for lot 124-550 (previous 80352 → new 93024)', '{\"previous_quantity\":80352,\"added_quantity\":0,\"new_quantity\":80352,\"lot_number\":\"124-550\"}', '{\"previous_quantity\":80352,\"added_quantity\":12672,\"new_quantity\":93024,\"lot_number\":\"124-550\"}', '2026-07-11 02:51:53'),
(80, 14, 'QUEENSEE', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created deliveries for PO-00000014 (DR: DR17264)', NULL, '{\"lot_ids\":\"48:29664,46:50688\"}', '2026-07-11 02:54:18'),
(81, 1, 'admin', 'admin', 'DELETE', 'admin', 'delivery', 23, 'Deleted delivery #23', NULL, '{\"delivery_id\":\"23\"}', '2026-07-11 03:00:12'),
(82, 14, 'QUEENSEE', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created deliveries for PO-00000014 (DR: DR17265)', NULL, '{\"lot_ids\":\"48:29664,46:50688\"}', '2026-07-11 03:02:36'),
(83, 11, 'Cath', 'production', 'LOGOUT', 'auth', 'user', 11, 'User logged out: Cath', NULL, NULL, '2026-07-11 03:03:51'),
(84, 11, 'Cath', 'production', 'LOGIN', 'auth', 'user', 11, 'User logged in: Cath', NULL, '{\"username\":\"cath\"}', '2026-07-11 03:10:10'),
(85, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-11 03:24:37'),
(86, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-11 03:48:29'),
(87, 1, 'admin', 'admin', 'DELETE', 'admin', 'delivery', 24, 'Deleted delivery #24', NULL, '{\"delivery_id\":\"24\"}', '2026-07-11 03:49:02'),
(88, 4, 'testwh', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created delivery records for PO-00000014 with DR 11111', NULL, '{\"lot_ids\":\"48:29664,46:50688\"}', '2026-07-11 03:50:40'),
(89, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-11 03:52:51'),
(90, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-11 05:06:42'),
(91, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-11 05:18:53'),
(92, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-11 05:19:06'),
(93, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-11 05:33:30'),
(94, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-11 05:33:39'),
(95, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-11 05:46:50'),
(96, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-11 05:46:54'),
(97, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-13 01:41:34'),
(98, 4, 'testwh', 'warehouse', 'CREATE', 'warehouse', 'purchase_order', 36, 'Created purchase order TEST-0000001 for CALM SANDS INC. (normal production)', NULL, '{\"customer_po_number\":\"TEST-0000001\",\"customer_po_date\":\"2026-07-13\",\"production_type\":\"normal\"}', '2026-07-13 01:59:15'),
(99, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-13 02:02:31'),
(100, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 70, 'Updated production quantity for TEST-0000001: added 1999 pcs for lot 123-456 (previous 0 → new 1999) (lot quantity 0 → 1999)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"123-456\"}', '{\"previous_quantity\":0,\"added_quantity\":1999,\"new_quantity\":1999,\"lot_number\":\"123-456\"}', '2026-07-13 02:06:04'),
(101, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 71, 'Updated production quantity for TEST-0000001: added 1899 pcs for lot 123-789 (previous 0 → new 1899) (lot quantity 0 → 1899)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"123-789\"}', '{\"previous_quantity\":0,\"added_quantity\":1899,\"new_quantity\":1899,\"lot_number\":\"123-789\"}', '2026-07-13 02:06:05'),
(102, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 72, 'Updated production quantity for TEST-0000001: added 1799 pcs for lot 123-101 (previous 0 → new 1799) (lot quantity 0 → 1799)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"123-101\"}', '{\"previous_quantity\":0,\"added_quantity\":1799,\"new_quantity\":1799,\"lot_number\":\"123-101\"}', '2026-07-13 02:06:05'),
(103, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 70, 'Updated production quantity for TEST-0000001: added 101 pcs for lot 123-456 (previous 1999 → new 2100) (lot quantity 1999 → 2100)', '{\"previous_quantity\":1999,\"added_quantity\":0,\"new_quantity\":1999,\"lot_number\":\"123-456\"}', '{\"previous_quantity\":1999,\"added_quantity\":101,\"new_quantity\":2100,\"lot_number\":\"123-456\"}', '2026-07-13 02:12:32'),
(104, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 71, 'Updated production quantity for TEST-0000001: added 101 pcs for lot 123-789 (previous 1899 → new 2000) (lot quantity 1899 → 2000)', '{\"previous_quantity\":1899,\"added_quantity\":0,\"new_quantity\":1899,\"lot_number\":\"123-789\"}', '{\"previous_quantity\":1899,\"added_quantity\":101,\"new_quantity\":2000,\"lot_number\":\"123-789\"}', '2026-07-13 02:12:33'),
(105, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 71, 'Updated production quantity for TEST-0000001: added 101 pcs for lot 123-789 (previous 2000 → new 2101) (lot quantity 2000 → 2101)', '{\"previous_quantity\":2000,\"added_quantity\":0,\"new_quantity\":2000,\"lot_number\":\"123-789\"}', '{\"previous_quantity\":2000,\"added_quantity\":101,\"new_quantity\":2101,\"lot_number\":\"123-789\"}', '2026-07-13 02:12:33'),
(106, 4, 'testwh', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created delivery records for TEST-0000001 with DR DR-003', NULL, '{\"lot_ids\":\"57:2100,58:2101,59:1799\"}', '2026-07-13 02:15:57'),
(107, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-14 00:08:00'),
(108, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-14 00:08:32'),
(109, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-14 00:08:41'),
(110, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-14 00:15:46'),
(111, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 52, 'Updated production quantity for PO-00000096: added 2000 pcs for lot 194-168 (previous 0 → new 2000) (lot quantity 0 → 2000)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"194-168\"}', '{\"previous_quantity\":0,\"added_quantity\":2000,\"new_quantity\":2000,\"lot_number\":\"194-168\"}', '2026-07-14 00:17:13'),
(112, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 53, 'Updated production quantity for PO-00000096: added 2000 pcs for lot 168-194 (previous 0 → new 2000) (lot quantity 0 → 2000)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"168-194\"}', '{\"previous_quantity\":0,\"added_quantity\":2000,\"new_quantity\":2000,\"lot_number\":\"168-194\"}', '2026-07-14 00:17:13'),
(113, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-14 00:18:22'),
(114, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 52, 'Updated production quantity for PO-00000096: added 2000 pcs for lot 168 (previous 2000 → new 4000) (lot quantity 0 → 2000)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"168\"}', '{\"previous_quantity\":0,\"added_quantity\":2000,\"new_quantity\":2000,\"lot_number\":\"168\"}', '2026-07-14 00:19:43'),
(115, 1, 'admin', 'admin', 'UPDATE', 'admin', 'production_history', 116, 'Updated production quantity and lot for history #116', '{\"previous_quantity\":2000,\"added_quantity\":2000,\"new_quantity\":4000,\"lot_number\":\"168\",\"old_added_quantity\":null,\"old_lot_number\":null}', '{\"previous_quantity\":2000,\"added_quantity\":2000,\"new_quantity\":4000,\"lot_number\":\"194-168\",\"old_added_quantity\":2000,\"old_lot_number\":\"168\"}', '2026-07-14 00:22:29'),
(116, 4, 'testwh', 'warehouse', 'CREATE', 'warehouse', 'delivery', NULL, 'Created delivery records for PO-00000096 with DR 2424', NULL, '{\"lot_ids\":\"60:4000,61:2000\"}', '2026-07-14 00:23:21'),
(117, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-15 05:48:22'),
(118, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-15 05:54:08'),
(119, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-15 06:09:56'),
(120, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-15 06:42:14'),
(121, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-15 06:42:23'),
(122, 6, 'testprod', 'production', 'LOGOUT', 'auth', 'user', 6, 'User logged out: testprod', NULL, NULL, '2026-07-15 07:02:57'),
(123, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-15 07:03:04'),
(124, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-16 00:01:11'),
(125, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-16 05:30:12'),
(126, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-16 05:30:32'),
(127, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-16 05:30:45'),
(128, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 70, 'Updated production quantity for TEST-0000001: added 1000 pcs for lot lotnegative (previous 2100 → new 3100) (lot quantity 0 → 1000)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"lotnegative\"}', '{\"previous_quantity\":0,\"added_quantity\":1000,\"new_quantity\":1000,\"lot_number\":\"lotnegative\"}', '2026-07-16 05:31:59'),
(129, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-16 06:56:46'),
(130, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-16 06:56:51'),
(131, 6, 'testprod', 'production', 'LOGOUT', 'auth', 'user', 6, 'User logged out: testprod', NULL, NULL, '2026-07-16 07:01:39'),
(132, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-16 07:01:45'),
(133, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-16 07:02:11'),
(134, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-16 07:02:31'),
(135, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-16 23:41:28'),
(136, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-17 03:10:22'),
(137, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-17 03:20:05'),
(138, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-17 03:20:23'),
(139, 6, 'testprod', 'production', 'CREATE', 'production', 'production_history', NULL, 'Reported production history entry #117 with reason: wrong qty', NULL, '{\"history_id\":\"117\",\"report_type\":\"lot_number\",\"reason\":\"wrong qty\\r\\n\"}', '2026-07-17 03:20:51'),
(140, 1, 'admin', 'admin', 'DELETE', 'admin', 'production_history', 117, 'Deleted production history #117', NULL, '{\"history_id\":\"117\"}', '2026-07-17 03:21:12'),
(141, 6, 'testprod', 'production', 'UPDATE', 'production', 'purchase_order_item', 70, 'Updated production quantity for TEST-0000001: added 1000 pcs for lot 10 (previous 2100 → new 3100) (lot quantity 0 → 1000)', '{\"previous_quantity\":0,\"added_quantity\":0,\"new_quantity\":0,\"lot_number\":\"10\"}', '{\"previous_quantity\":0,\"added_quantity\":1000,\"new_quantity\":1000,\"lot_number\":\"10\"}', '2026-07-17 03:55:36'),
(142, 1, 'admin', 'admin', 'DELETE', 'admin', 'delivery', 27, 'Deleted delivery #27', NULL, '{\"delivery_id\":\"27\"}', '2026-07-17 03:56:15'),
(143, 1, 'admin', 'admin', 'DELETE', 'admin', 'production_history', 118, 'Deleted production history #118', NULL, '{\"history_id\":\"118\"}', '2026-07-17 03:56:43'),
(144, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-17 05:24:24'),
(145, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-17 05:24:50'),
(146, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-17 05:25:14'),
(147, 1, 'admin', 'admin', 'UPDATE', 'admin', 'item', 540, 'Updated item (inline): ', '{\"item_id\":540,\"item_code\":\"FG0402-ADV500+2KGCON\",\"item_description\":\"AP Advance 500mL + 2 K Gold Promo\",\"customer_id\":27,\"item_uom\":\"PCS\",\"uom_conversion\":24,\"item_size\":null,\"item_amount\":\"0.00\",\"date_created\":\"2026-07-08 15:36:25\",\"status\":1,\"remove\":0,\"last_update\":\"2026-07-08 15:36:25\"}', '{\"item_name\":\"\",\"item_code\":\"FG0402-ADV500+2KGCON\",\"description\":\"\"}', '2026-07-17 05:56:15'),
(148, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-17 05:56:17'),
(149, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-17 23:40:21'),
(150, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-17 23:45:31'),
(151, 1, 'admin', 'admin', 'UPDATE', 'admin', 'delivery', 26, 'Updated delivery #26', NULL, '{\"dr_number\":\"DR-003\",\"delivery_date\":\"2026-07-13\"}', '2026-07-18 00:25:01'),
(152, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-18 01:18:02'),
(153, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-18 01:18:08'),
(154, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-18 02:31:29'),
(155, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-18 02:31:37'),
(156, 4, 'testwh', 'warehouse', 'LOGOUT', 'auth', 'user', 4, 'User logged out: testwh', NULL, NULL, '2026-07-18 05:16:33'),
(157, 1, 'admin', 'admin', 'LOGIN', 'auth', 'user', 1, 'User logged in: admin', NULL, '{\"username\":\"admin\"}', '2026-07-18 05:16:38'),
(158, 1, 'admin', 'admin', 'LOGOUT', 'auth', 'user', 1, 'User logged out: admin', NULL, NULL, '2026-07-18 06:00:36'),
(159, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-18 06:00:44'),
(160, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-20 00:12:44'),
(161, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-20 00:15:39'),
(162, 6, 'testprod', 'production', 'LOGIN', 'auth', 'user', 6, 'User logged in: testprod', NULL, '{\"username\":\"testprod\"}', '2026-07-20 00:21:30'),
(163, 4, 'testwh', 'warehouse', 'LOGIN', 'auth', 'user', 4, 'User logged in: testwh', NULL, '{\"username\":\"testwh\"}', '2026-07-20 00:24:27');

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
  `customer_terms` varchar(50) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `remove` tinyint(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_code`, `customer_name`, `customer_address`, `customer_type`, `customer_tin`, `customer_terms`, `date_created`, `status`, `remove`, `last_update`) VALUES
(7, '7BARACOS', '7 BARACOS CORPORATION', 'U128 12th Floor Civic Drv Filinvest Corporate City', 'vat', '009-096-168-000', '30', '2026-07-03 11:04:00', 1, 0, '2026-07-03 03:35:15'),
(8, 'SUPERMAR', 'ALABANG SUPERMARKET CORP', 'COMMERCE AVENUE COR THEATER DR ALABANG 1785 CITY OF MUNTINLUPA NCR NORTH', 'vat', '003-840-587-000', 'COD', '2026-07-03 11:04:54', 1, 0, '2026-07-03 05:38:54'),
(9, 'MR. DIY', 'BRICOLAGE PHILIPPINES INC.', '3A/F XELAND BLDG GUERILLA ST.,COR G. FERNANDO AVENUE STO NIN CITY OF MARIKINA 1820', 'vat', '100-576-170-000', '30', '2026-07-03 11:12:15', 1, 0, '2026-07-03 03:37:06'),
(10, 'CALMSAND', 'CALM SANDS INC.', '66 UNITED STREET HIGHWAY HILLS 1550 CITY OF MANDALUYONG CITY', 'vat', '618-114-179-00000', '30', '2026-07-03 11:13:29', 1, 0, '2026-07-03 03:37:18'),
(11, 'CASH', 'CASH SALES', 'LOT 7, BLK 7 Springbook, ST. Sterling Technopark Maguyam Silang Cavite', 'vat', '000-000-001', 'Undefined Credit Term', '2026-07-03 11:14:02', 1, 0, '2026-07-03 03:48:55'),
(12, 'DWELL', 'DWELLBEING INC.', '20 Daisy St. Don Aguedo Bernabe Subd. San Antonio Valley 5 Paranaque', 'vat', '609-630-642-00000', '30', '2026-07-03 11:14:37', 1, 0, '2026-07-03 03:37:49'),
(15, 'GCI', 'GREEN CROSS INC.', '14 & 15 F COMMON GOAL TOWER FINANCE COR INDUSTRY STS. MADRIGAL BUSINESS PARK AYALA ALABANG 1780', 'vat', '000-416-871-00000', '30', '2026-07-03 11:19:19', 1, 0, '2026-07-03 03:38:02'),
(16, 'LAYBAR', 'LAY BARE SUPPLIES  DISTRIBUTION CORP.', '2/F Unit 10 Citiplace Bldg 8001 Abad Santos St. San Juan', 'vat', '008-686-474-000', '30', '2026-07-03 11:19:49', 1, 0, '2026-07-03 03:39:48'),
(17, 'PEDIA', 'PEDIAPHARMA INC', 'Pediapharma Center  70-A Scout Tuazon St.South Triangle, Quezon City', 'vat', '000-405-671-000', '30', '2026-07-03 11:23:25', 1, 0, '2026-07-03 03:39:54'),
(18, 'REAL', 'REAL FOODS', 'Commissary 2/F 107 Diversified Marcos Alvarez ', 'vat', '009-176-959-0002', '30', '2026-07-03 11:23:59', 1, 0, '2026-07-03 03:40:28'),
(19, 'SBRANDS', 'S BRANDS CONSUMER CARE INC.', '29F JOY NOSTALG CENTER 17 ADB AVENUE ORTIGAS CENTER SAN ANTONIO PASIG CITY', 'vat', '010-962-552-000', '90', '2026-07-03 11:24:53', 1, 0, '2026-07-03 03:24:53'),
(20, 'DREW', 'THE DREW LIFESTYLE TRADE INC.', 'EMAX BLDG 7105 MASTERSON AVE.UPTOWN UPPER BALULANG CDO', 'vat', '604-593-584', 'Undefined Credit Term', '2026-07-03 11:25:32', 1, 0, '2026-07-03 05:39:01'),
(21, 'LANDMARK', 'THE LANDMARK CORP.', 'Ayala Center Makati Avenue', 'vat', '000-148-285-00000', 'Undefined Credit Term', '2026-07-03 11:26:26', 1, 0, '2026-07-03 03:48:10'),
(22, 'UM', 'UM SUPERFOODS CORP.', 'Capitol Commons Shaw Blvd Cor. Meralco Avenue Oranbo', 'vat', '009-801-157-001', '30', '2026-07-03 11:26:56', 1, 0, '2026-07-03 03:41:39'),
(23, 'UNIMART', 'UNIMART INCORPORATED', 'GF Mckinley Arcade Plaza Bldg. Greenhills Shopping Center', 'vat', '000-062-391-00000', '30', '2026-07-03 11:27:29', 1, 0, '2026-07-03 03:41:44'),
(24, 'WILCON', 'WILCON DEPOT, INC.', '#90 E RODRIGUEZ JR. AVE  UGONG NORTE NCR  SECOND DISTRICT', 'vat', '009-192-878-000', 'Undefined Credit Term', '2026-07-03 11:28:06', 1, 0, '2026-07-03 03:48:02'),
(25, 'WATSON', 'WILLIS TOWERS WATSON PHILIPPINES INC', '23F W City Center 7th Ave. Cor 30th St., BGC Taguig City', 'vat', '000-171-259-000', '30', '2026-07-03 11:29:11', 1, 0, '2026-07-03 03:41:52'),
(26, 'MSYBSY', 'MESSY BESSY CLEANERS INC.', 'Natividad Building #2308 Chino Roces Avenue, Ext. 1232 Magallanes Village Makati City\r\n', 'vat', '006-935-228-000', '30', '2026-07-04 09:31:40', 1, 0, '2026-07-04 01:31:40'),
(27, 'SKNTEC', 'SKINTEC ADVANCE INC', 'BYPASS ROAD BULIHAN PLARIDEL BULACAN 3004', 'vat', '008-434-783-000', '90', '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25');

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
  `report_remarks` text DEFAULT NULL,
  `remarks_type` varchar(20) DEFAULT NULL,
  `active_status` tinyint(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
  `date_created` datetime DEFAULT current_timestamp(),
  `delivery_quantity` int(11) DEFAULT 0,
  `old_quantity` text DEFAULT NULL,
  `dr_number` varchar(50) DEFAULT NULL COMMENT 'Delivery Receipt number',
  `old_dr_number` varchar(50) DEFAULT NULL,
  `lot_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of lot details [{lot_id, poi_id, qty}]' CHECK (json_valid(`lot_items`)),
  `remove` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`delivery_id`, `po_id`, `poi_id`, `lot_id`, `delivered_by`, `delivery_date`, `remarks`, `report_remarks`, `remarks_type`, `active_status`, `date_created`, `delivery_quantity`, `old_quantity`, `dr_number`, `old_dr_number`, `lot_items`, `remove`) VALUES
(20, 25, 30, NULL, 14, '2026-07-10', '', NULL, NULL, 1, '2026-07-10 15:19:54', 76320, NULL, 'DR17259', NULL, '[{\"lot_id\":25,\"poi_id\":30,\"lot_number\":\"151-306\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":32544,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":37,\"poi_id\":30,\"lot_number\":\"151-307\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":12672,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":32,\"poi_id\":30,\"lot_number\":\"151-313\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":31104,\"item_uom\":\"PCS\",\"uom_conversion\":288}]', 0),
(21, 25, 31, NULL, 14, '2026-07-10', '', NULL, NULL, 1, '2026-07-10 15:31:03', 50400, NULL, 'DR17262', NULL, '[{\"lot_id\":40,\"poi_id\":31,\"lot_number\":\"159-109\",\"item_code\":\"FG0648-BLANTDNFSHA12\",\"item_description\":\"BareLab Anti-Dandruff Shampoo 12mLx12sx24pck\",\"qty\":5760,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":29,\"poi_id\":31,\"lot_number\":\"159-110\",\"item_code\":\"FG0648-BLANTDNFSHA12\",\"item_description\":\"BareLab Anti-Dandruff Shampoo 12mLx12sx24pck\",\"qty\":19296,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":34,\"poi_id\":31,\"lot_number\":\"159-111\",\"item_code\":\"FG0648-BLANTDNFSHA12\",\"item_description\":\"BareLab Anti-Dandruff Shampoo 12mLx12sx24pck\",\"qty\":25344,\"item_uom\":\"PCS\",\"uom_conversion\":288}]', 0),
(22, 25, 30, NULL, 14, '2026-07-11', '', NULL, NULL, 1, '2026-07-11 09:59:05', 76608, NULL, 'DR17263', NULL, '[{\"lot_id\":37,\"poi_id\":30,\"lot_number\":\"151-307\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":19584,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":44,\"poi_id\":30,\"lot_number\":\"151-308\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":31680,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":45,\"poi_id\":30,\"lot_number\":\"151-309\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":12672,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":53,\"poi_id\":30,\"lot_number\":\"151-310\",\"item_code\":\"FG0670-BLSLEEK13X12\",\"item_description\":\"BareLab Sleek and Straight 13mlx12sx24packs\",\"qty\":12672,\"item_uom\":\"PCS\",\"uom_conversion\":288}]', 0),
(23, 25, 37, NULL, 14, '2026-07-11', '', NULL, NULL, 1, '2026-07-11 10:54:18', 63360, NULL, 'DR17264', NULL, '[{\"lot_id\":48,\"poi_id\":37,\"lot_number\":\"124-549\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":12672,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":46,\"poi_id\":37,\"lot_number\":\"124-550\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":50688,\"item_uom\":\"PCS\",\"uom_conversion\":288}]', 1),
(24, 25, 37, NULL, 14, '2026-07-11', '', NULL, NULL, 1, '2026-07-11 11:02:36', 63360, NULL, 'DR17265', NULL, '[{\"lot_id\":48,\"poi_id\":37,\"lot_number\":\"124-549\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":12672,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":46,\"poi_id\":37,\"lot_number\":\"124-550\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":50688,\"item_uom\":\"PCS\",\"uom_conversion\":288}]', 1),
(25, 25, 37, NULL, 4, '2026-07-11', '', NULL, NULL, 1, '2026-07-11 11:50:40', 80352, NULL, '11111', NULL, '[{\"lot_id\":48,\"poi_id\":37,\"lot_number\":\"124-549\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":8879,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":56,\"poi_id\":37,\"lot_number\":\"124-549\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":20785,\"item_uom\":\"PCS\",\"uom_conversion\":288},{\"lot_id\":46,\"poi_id\":37,\"lot_number\":\"124-550\",\"item_code\":\"FG0311-EMPRSSHAMPx12\",\"item_description\":\"Empress Shampoo x 12\",\"qty\":50688,\"item_uom\":\"PCS\",\"uom_conversion\":288}]', 0),
(26, 36, 70, NULL, 4, '2026-07-13', 'partial', NULL, 'edited', 1, '2026-07-13 10:15:57', 6000, NULL, 'DR-003', NULL, '[{\"lot_id\":57,\"poi_id\":70,\"lot_number\":\"123-456\",\"item_code\":\"FG0650-MBLAGT300mL\",\"item_description\":\"MB Hand & Body Lotion Aloe Green Tea 300mLx12\",\"qty\":2100,\"item_uom\":\"PCS\",\"uom_conversion\":12,\"actual_uom_conversion\":100},{\"lot_id\":58,\"poi_id\":71,\"lot_number\":\"123-789\",\"item_code\":\"FG0565-DWSAMORNHSGAL\",\"item_description\":\"Dwellbeing Sampaguita n Orange Liquid Hand Soap Gal\",\"qty\":2101,\"item_uom\":\"PCS\",\"uom_conversion\":null},{\"lot_id\":59,\"poi_id\":72,\"lot_number\":\"123-101\",\"item_code\":\"FG0605-HBWAGT50\",\"item_description\":\"Messy Bessy Hand & Body Wash Aloe Green Tea 50mL\",\"qty\":1799,\"item_uom\":\"PCS\",\"uom_conversion\":16,\"actual_uom_conversion\":16}]', 0),
(27, 26, 52, NULL, 4, '2026-07-14', '', NULL, NULL, 1, '2026-07-14 08:23:21', 6000, NULL, '2424', NULL, '[{\"lot_id\":60,\"poi_id\":52,\"lot_number\":\"194-168\",\"item_code\":\"FG0267-APISO70CLS150\",\"item_description\":\"AlcoPlus Iso 70 Alcohol Classic 150mL\",\"qty\":2000,\"item_uom\":\"PCS\",\"uom_conversion\":48},{\"lot_id\":62,\"poi_id\":52,\"lot_number\":\"194-168\",\"item_code\":\"FG0267-APISO70CLS150\",\"item_description\":\"AlcoPlus Iso 70 Alcohol Classic 150mL\",\"qty\":2000,\"item_uom\":\"PCS\",\"uom_conversion\":48},{\"lot_id\":61,\"poi_id\":53,\"lot_number\":\"168-194\",\"item_code\":\"FG0268-APISO70CLS250\",\"item_description\":\"AlcoPlus Iso 70 Alcohol Classic 250mL\",\"qty\":2000,\"item_uom\":\"PCS\",\"uom_conversion\":48}]', 1);

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

-- --------------------------------------------------------

--
-- Table structure for table `delivery_reports`
--

CREATE TABLE `delivery_reports` (
  `report_id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `poi_id` int(11) DEFAULT NULL,
  `po_id` int(11) NOT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `old_quantity` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `report_type` enum('dr_number','quantity') DEFAULT 'dr_number',
  `status` enum('pending','resolved') DEFAULT 'pending',
  `resolved_by` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `date_reported` datetime DEFAULT current_timestamp(),
  `date_resolved` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `excess_production`
--

CREATE TABLE `excess_production` (
  `excess_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `source_po_id` int(11) NOT NULL,
  `source_poi_id` int(11) NOT NULL,
  `excess_quantity` int(11) NOT NULL,
  `consumed_quantity` int(11) DEFAULT 0,
  `remaining_quantity` int(11) GENERATED ALWAYS AS (`excess_quantity` - `consumed_quantity`) STORED,
  `status` enum('pending','partial','consumed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
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

INSERT INTO `items` (`item_id`, `item_code`, `item_description`, `customer_id`, `item_uom`, `uom_conversion`, `item_size`, `item_amount`, `date_created`, `status`, `remove`, `last_update`) VALUES
(17, 'FG0186-KPSS22x6', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 13:35:11', 1, 0, '2026-07-04 00:42:11'),
(18, 'FG0311-EMPRSSHAMPx12', 'Empress Shampoo x 12', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:10:16', 1, 0, '2026-07-03 06:10:16'),
(19, 'FG0337-EMPRSSHAMPx6', 'Empress Shampoo Long and Healthy 21ml x6pcs x48', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:10:54', 1, 0, '2026-07-06 06:04:42'),
(20, 'FG0564-BLHCONDI15x6', 'BareLab Hair Conditioner 15mL x 6', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:19:36', 1, 0, '2026-07-03 06:19:36'),
(21, 'FG0573-BLHCONDI15x12', 'BareLab Sleek and Straight 15ml x 12s x 24packs', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:20:31', 1, 0, '2026-07-03 06:20:31'),
(22, 'FG0662-BLSHAMLONG15', 'Barelab Shampoo Long and Nourished 15mL', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:23:31', 1, 0, '2026-07-03 06:23:31'),
(23, 'FG0665-BLSSOFnSHIN15', 'Barelab Shampoo Soft and Shiny 15mL', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:24:21', 1, 0, '2026-07-03 06:24:21'),
(24, 'FG0670-BLSLEEK13X12', 'BareLab Sleek and Straight 13mlx12sx24packs', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:26:44', 1, 0, '2026-07-03 06:26:44'),
(25, 'FG0671-BLAFALCON13', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 14:28:03', 1, 0, '2026-07-03 06:28:03'),
(26, 'FG0672-BLKERTRCON180', 'BareLab Keratin Intense Deluxe 180g', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 14:28:38', 1, 0, '2026-07-11 00:27:34'),
(27, 'FG0187-EDTSUN60', 'Pure Basic EDT Sunny 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 14:56:33', 1, 0, '2026-07-03 06:56:33'),
(28, 'FG0188-EDTROM60', 'Pure Basic EDT Romantic 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 14:57:19', 1, 0, '2026-07-03 06:57:19'),
(29, 'FG0189-EDTFRESH60', 'Pure Basic EDT Fresh 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 14:58:07', 1, 0, '2026-07-03 06:58:07'),
(30, 'FG0338-EMPRSSHAMPx1', 'Empress Shampoo x 1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-03 15:05:08', 1, 0, '2026-07-06 06:04:59'),
(31, 'FG0382-PROKP12+2KGLD', 'Keratin Shampoo 12+2 Keratin Gold Promo', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 15:06:17', 1, 0, '2026-07-03 07:06:17'),
(32, 'FG0383-PROEM12+2KGLD', 'Empress Shampoo 12+2 Keratin Gold Promo', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 15:06:52', 1, 0, '2026-07-03 07:06:52'),
(33, 'FG0553-KSHAM6+1PROMO', 'Keratin Shampoo 22mL 6 + 1 PROMO', 19, 'PCS', 312, NULL, 0.00, '2026-07-03 15:07:36', 1, 0, '2026-07-06 06:38:29'),
(34, 'FG0563-COATCLAS50x12', 'Pure Basics Hair Cuticle Coat Classic 50mL x 12', 19, 'PCS', 144, NULL, 0.00, '2026-07-03 15:08:26', 1, 0, '2026-07-03 07:08:26'),
(35, 'FG0568-EMPSHA12+2ELH', 'Empress Shampoo Long n Healthy 12+2 Empress LH', 19, 'PCS', 312, NULL, 0.00, '2026-07-03 15:08:58', 1, 0, '2026-07-06 06:44:39'),
(36, 'FG0570-KERSHA12+2KDN', 'Keratin Shampoo Daily Nourishing 12+2 Keratin DN', 19, 'PCS', 312, NULL, 0.00, '2026-07-03 15:09:36', 1, 0, '2026-07-06 06:45:14'),
(37, 'FG0575-EMPSHAx4', 'Empress Shampoo Long n Healthy x4', 19, 'PCS', 400, NULL, 0.00, '2026-07-03 15:10:19', 1, 0, '2026-07-03 07:10:19'),
(38, 'FG0589-EMPx6+2KGOLD', 'Empress Shampoo Long n Heathy 6 + 2 Keratin Gold', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 15:11:12', 1, 0, '2026-07-03 07:11:12'),
(39, 'FG0633-EMPSHAM11+1', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', 19, 'PCS', 264, NULL, 0.00, '2026-07-03 15:11:48', 1, 0, '2026-07-11 01:39:47'),
(40, 'FG0664-EMPSHAx2', 'Empress Shampoo Long n Healthy x2', 19, 'PCS', 500, NULL, 0.00, '2026-07-03 15:12:44', 1, 0, '2026-07-03 07:12:44'),
(43, 'FG0190-EDTACT60', 'Pure Basic EDT Active 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:19:04', 1, 0, '2026-07-03 07:19:04'),
(44, 'FG0191-EDTCOOL60', 'Pure Basic EDT Cool 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:19:53', 1, 0, '2026-07-03 07:19:53'),
(45, 'FG0192-EDTAQUA60', 'Pure Basic EDT Aqua 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:21:50', 1, 0, '2026-07-03 07:21:50'),
(52, 'FG0235-EDTBLOOM100', 'Pure Basic EDT Bloom 100ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:34:15', 0, 0, '2026-07-06 05:37:18'),
(53, 'FG0236-EDTINTENSE100', 'Pure Bacic EDT Intense 100ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:35:07', 0, 0, '2026-07-06 05:37:25'),
(54, 'FG0263-KPSS22x11+1', 'Keratin Plus Shampoo Soft Smooth 22mlx11+1 Promo', 19, 'PCS', 264, NULL, 0.00, '2026-07-03 15:35:54', 1, 0, '2026-07-11 01:39:13'),
(55, 'FG0011-GGC14', 'Grips Gel (Clear) 14g', 19, 'PCS', 576, NULL, 0.00, '2026-07-03 15:38:10', 0, 0, '2026-07-06 03:11:17'),
(56, 'FG0012-GGY14', 'Grips Gel (Yellow) 14g', 19, 'PCS', 576, NULL, 0.00, '2026-07-03 15:42:20', 0, 0, '2026-07-06 03:11:07'),
(58, 'FG0013-GGG14', 'Grips Gel (Green) 14g', 19, 'PCS', 576, NULL, 0.00, '2026-07-03 15:52:45', 0, 0, '2026-07-06 03:10:40'),
(60, 'FG0014-GGASOR14', 'Grips Gel Assorted 14gx12x48', 19, 'PCS', 1728, NULL, 0.00, '2026-07-03 15:54:08', 1, 0, '2026-07-06 03:13:46'),
(61, 'FG0021-GWM5x12', 'Grips Wax Hard&Mat 5gx12x36', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 06:52:14', 1, 0, '2026-07-06 03:15:29'),
(62, 'FG0022-GWS5x12', 'Grips Wax Hard&Shiny 5gx12x36', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 06:54:32', 1, 0, '2026-07-06 03:16:03'),
(63, 'FG0023-GWX5x12', 'Grips Wax Xtreme Mat 5Gx12x36', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 06:56:10', 1, 0, '2026-07-06 03:16:37'),
(64, 'FG0024-GWM5x6', 'Grips Wax Hard&Mat 5gx6x72', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 07:02:49', 1, 0, '2026-07-06 03:19:23'),
(65, 'FG0025-GWS5x6', 'Grips Wax Hard&Shiny 5gx6xx72', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 07:03:59', 1, 0, '2026-07-06 03:29:26'),
(66, 'FG0026-GWX5x6', 'Grips Wax Xtreme Hard&Mat 5gx6x72', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 07:05:07', 1, 0, '2026-07-06 03:33:23'),
(67, 'FG0027-GWM5x1', 'Grips Wax Hard&Mat 5gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 07:06:22', 1, 0, '2026-07-03 23:06:29'),
(68, 'FG0028-GWS5x1', 'Grips Wax Hard&Shiny 5gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 07:12:09', 1, 0, '2026-07-03 23:12:09'),
(69, 'FG0029-GWX5x1', 'Grips Wax Xtreme Hard&Mat 5gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 07:14:39', 1, 0, '2026-07-03 23:14:39'),
(70, 'FG0030-GWM75', 'Grips Wax Hard&Mat 75gx36', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 07:15:47', 1, 0, '2026-07-06 03:35:40'),
(71, 'FG0031-GWS75', 'Grips Wax Hard&Shiny 75gx36', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 07:16:53', 1, 0, '2026-07-06 03:36:01'),
(72, 'FG0032-GWX75', 'Grips Wax Xtreme Hard&Mat 75gx36', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 07:58:25', 1, 0, '2026-07-06 03:36:19'),
(73, 'FG0034-GCLAY5x1', 'Grips Clay 5gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 07:59:25', 1, 0, '2026-07-03 23:59:25'),
(74, 'FG0045-GCLAY25x48', 'Grips Clay 25gx48', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 08:00:42', 1, 0, '2026-07-06 03:41:56'),
(75, 'FG0046-GWHM25x48', 'Grips Wax Hard&Mat 25gx48', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 08:02:10', 1, 0, '2026-07-06 03:42:22'),
(76, 'FG0047-GWS25x48', 'Grips Wax Hard&Shiny 25gx48', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 08:03:12', 1, 0, '2026-07-06 03:42:40'),
(77, 'FG0048-GWX25x48', 'Grips Wax Xtreme Hard&Mat 25gx48', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 08:04:11', 1, 0, '2026-07-06 03:42:52'),
(78, 'FG0049-GCLAY5x6x72', 'Grips Clay 5gx6x72', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 08:05:25', 1, 0, '2026-07-06 03:43:16'),
(79, 'FG0050-GCLAY75x36', 'Grips Hair Clay 75gx36', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 08:07:58', 1, 0, '2026-07-06 03:45:12'),
(80, 'FG0052-GWM75stkr', 'Grips Wax Mat(stkr)75gx36', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:11:06', 0, 0, '2026-07-06 03:46:10'),
(81, 'FG0422-DWBLEMLHSGAL', 'Dwellbeing Lemongrass Liquid Hand Soap Gallon', 12, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:11:53', 0, 0, '2026-07-06 06:33:10'),
(82, 'FG0053-GWS75stkr', 'Grips Wax Shiny(Stkr)75gx36', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:12:08', 0, 0, '2026-07-06 03:46:23'),
(83, 'FG0054-GWX75stkr', 'Grips Wax Xtreme(stkr) 75gx36', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:13:03', 0, 0, '2026-07-06 03:46:35'),
(84, 'FG0423-DWBLEMSANGAL', 'Dwellbeing Lemongrass Sanitizer Gallon', 12, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:13:27', 0, 0, '2026-07-06 06:33:19'),
(85, 'FG0055-GWMstkr25x48', 'Grips Wax Mat(stkr) 25gx48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:13:59', 0, 0, '2026-07-06 03:46:45'),
(86, 'FG0056-GWSstkr25x48', 'Grips Wax Shiny(stkr) 25gx48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:14:49', 0, 0, '2026-07-06 03:47:05'),
(87, 'FG0500-DBGRPFRTHSGAL', 'Dwellbeing Grapefruit Liquid Hand Soap Gallon', 12, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:15:03', 1, 0, '2026-07-04 00:15:03'),
(88, 'FG0057-GWXstkr25x48', 'Grips Wax Xtreme(stkr) 25gx48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:15:39', 0, 0, '2026-07-06 03:47:19'),
(89, 'FG0565-DWSAMORNHSGAL', 'Dwellbeing Sampaguita n Orange Liquid Hand Soap Gal', NULL, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:17:01', 1, 0, '2026-07-04 00:17:01'),
(90, 'FG0058-GGAsfly14x48', 'Grips Gel Assorted (flier) 14gx12x48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 08:17:04', 0, 0, '2026-07-06 03:47:31'),
(91, 'FG0067-GWM5x12x2', 'Grips Hair Wax Hard and Mat 5g Promo 12+2', 19, 'PCS', 504, NULL, 0.00, '2026-07-04 08:18:25', 1, 0, '2026-07-06 04:56:21'),
(92, 'FG0613-LEMONSANGAL', 'Everything Clean All-Natural Sanitizer Gallon', 12, 'PCS', 4, NULL, 0.00, '2026-07-04 08:18:26', 1, 0, '2026-07-06 06:50:30'),
(93, 'FG0101-TITANIUM75x36', 'Grips Hair Clay Titanium 75gx36', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 08:19:44', 1, 0, '2026-07-06 05:04:22'),
(94, 'FG0614-LEMONSAN750', 'Everything Clean All-Natural Sanitizer 750ml', 12, 'PCS', 12, NULL, 0.00, '2026-07-04 08:20:12', 1, 0, '2026-07-06 06:50:49'),
(95, 'FG0615-LEMONSAN375', 'Everything Clean All-Natural Sanitizer 375ml', 12, 'PCS', 12, NULL, 0.00, '2026-07-04 08:21:12', 1, 0, '2026-07-06 06:51:13'),
(96, 'FG0120-GPOMADE75', 'Grips Pomade 75gx36', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 08:21:43', 1, 0, '2026-07-06 05:10:40'),
(97, 'FG0616-LEMONHSGAL', 'Hand Soap Lemongrass Gallon', 12, 'PCS', 4, NULL, 0.00, '2026-07-04 08:22:10', 1, 0, '2026-07-06 06:51:28'),
(98, 'FG0617-LEMONHS750', 'Hand Soap Lemongrass 750ml', 12, 'PCS', 12, NULL, 0.00, '2026-07-04 08:23:06', 1, 0, '2026-07-06 06:51:41'),
(99, 'FG0121-GPOMADE5x6', 'Grips Pomade 5gx6x72', 19, 'PCS', 432, NULL, 0.00, '2026-07-04 08:23:48', 1, 0, '2026-07-06 05:10:58'),
(100, 'FG0618-LEMONHS375', 'Hand Soap Lemongrass 375ml', 12, 'PCS', 15, NULL, 0.00, '2026-07-04 08:23:53', 1, 0, '2026-07-06 06:51:55'),
(101, 'FG0619-GRAPEFRHSGAL', 'Hand Soap Grapefruit Gallon', 12, 'PCS', 4, NULL, 0.00, '2026-07-04 08:25:28', 1, 0, '2026-07-06 06:52:09'),
(103, 'FG0620-GRAPEFRHS750', 'Hand Soap Grapefruit 750ml', 12, 'PCS', 12, NULL, 0.00, '2026-07-04 08:26:06', 1, 0, '2026-07-06 06:52:22'),
(104, 'FG0621-GRAPEFRHS375', 'Hand Soap Grapefruit 375ml', 12, 'PCS', 15, NULL, 0.00, '2026-07-04 08:26:47', 1, 0, '2026-07-06 06:52:37'),
(105, 'FG0622-FLORAFILHSGAL', 'Flora Filipinas All-Natural Hand Soap Gallon', 12, 'PCS', 4, NULL, 0.00, '2026-07-04 08:31:10', 1, 0, '2026-07-06 06:52:53'),
(106, 'FG0623-FLORAFILHS750', 'Flora Filipinas All-Natural Hand Soap 750ml', 12, 'PCS', 12, NULL, 0.00, '2026-07-04 08:32:06', 1, 0, '2026-07-06 06:53:05'),
(107, 'FG0624-FLORAFILHS375', 'Flora Filipinas All-Natural Hand Soap 375ml', 12, 'PCS', 12, NULL, 0.00, '2026-07-04 08:33:08', 1, 0, '2026-07-06 06:53:17'),
(108, 'FG0185-KPSS22x12', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', 19, 'PCS', 288, NULL, 0.00, '2026-07-04 08:36:01', 1, 0, '2026-07-06 05:24:06'),
(110, 'FG0041-LBSC300', 'Laybare Soothing Cream 300ml', 16, 'PCS', 35, NULL, 0.00, '2026-07-04 08:40:38', 1, 0, '2026-07-06 03:39:37'),
(112, 'FG0226-GWM5x24+KGOL', 'Promo Grips Wax Hard&Mat 5gx24+KplusGoldx6', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:01:12', 1, 0, '2026-07-04 01:01:12'),
(113, 'FG0043-LBEC30', 'Laybare Exfoliating Cream 30g', 16, 'PCS', 99, NULL, 0.00, '2026-07-04 09:01:36', 1, 0, '2026-07-06 03:40:29'),
(114, 'FG0224-LBSC100', 'Laybare Soothing Cream 100ml', 16, 'PCS', 96, NULL, 0.00, '2026-07-04 09:10:41', 1, 0, '2026-07-06 05:33:41'),
(115, 'FG0239-SANGELRED60', 'AlcoPlus Hand Sanitizer Gel with Moisturizer 60mL', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:11:28', 0, 0, '2026-07-06 05:37:57'),
(116, 'FG0042-LBSC30', 'Laybare Soothing Cream 30ml', 16, 'PCS', 99, NULL, 0.00, '2026-07-04 09:11:49', 1, 0, '2026-07-06 03:40:07'),
(117, 'FG0240-SANGELRED150', 'AlcoPlus Hand Sanitizer Gel with Moisturizer 150mL', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:12:52', 0, 0, '2026-07-06 05:38:08'),
(118, 'PM00067', 'Printed Tube with cap Soothing cream 30ml(w/cost)', 16, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:13:02', 1, 0, '2026-07-04 01:13:02'),
(119, 'PM00069', 'Printed Tube w/cap Soothing cream 100ml (w/cost)', 16, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:14:00', 1, 0, '2026-07-04 01:14:00'),
(120, 'FG0069-DWCLNR50', 'Desk and Workspace Cleaner 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:32:19', 0, 0, '2026-07-06 04:57:25'),
(121, 'FG0072-OASPRY50', 'Odor Absorber Spray 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:33:19', 0, 0, '2026-07-06 04:58:07'),
(122, 'FG0076-TLWBER50', 'The Little Warrior Bergamot 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:34:19', 0, 0, '2026-07-06 04:59:15'),
(123, 'FG0077-TLWCAM50', 'The Little Warrior Chamomile 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:36:29', 0, 0, '2026-07-06 04:59:35'),
(124, 'FG0078-TLWGNT50', 'The Little Warrior Green Tea 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:37:33', 0, 0, '2026-07-06 05:00:05'),
(125, 'FG0241-SANGELBLUE60', 'AlcoPlus Hand Sanitizer Gel with Vit E Beads 60mL', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:38:08', 0, 0, '2026-07-06 05:38:52'),
(126, 'FG0080-BPSN50', 'Messy Bessy Pocket Sanitizer 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:38:31', 0, 0, '2026-07-06 05:00:30'),
(127, 'FG0242-SANGELBLUE150', 'Sanitizer Gel Blue 150mL', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:39:02', 0, 0, '2026-07-06 05:39:01'),
(128, 'FG0088-DSKTPDUO', 'Desktop Duo', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:39:23', 0, 0, '2026-07-06 05:01:58'),
(129, 'FG0094-DSKTP3CAM', 'Desktop Trio Chamomile', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:40:06', 0, 0, '2026-07-06 05:02:56'),
(130, 'FG0095-DSKTPGNT', 'Desktop Trio Green Tea', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:41:06', 0, 0, '2026-07-06 05:03:12'),
(131, 'FG0264-ETYL70CLS150', 'AlcoPlus Ethyl 70 Alcohol Classic 150mL', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 09:41:07', 1, 0, '2026-07-06 05:46:54'),
(132, 'FG0109-PDSY50', 'Potty Disinfectant Spray 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:42:41', 0, 0, '2026-07-06 05:06:36'),
(133, 'FG0111-CS-BPDSY30', 'CS Be Poolite Deodorizer Spray30', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:43:44', 0, 0, '2026-07-06 05:06:59'),
(134, 'FG0112-MBPOTDUO', 'Messy Bessy Potty Duo', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:44:33', 0, 0, '2026-07-06 05:08:36'),
(135, 'FG0124-BDBC500', 'Messy Baby Dish and Bottle Cleaner 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:46:09', 0, 0, '2026-07-06 05:11:35'),
(136, 'FG0265-ETYL70CLS250', 'AlcoPlus Ethyl 70 Alcohol Classic 250mL', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 09:46:40', 1, 0, '2026-07-06 05:47:13'),
(137, 'FG0126-BLLDCL975', 'Messy Baby Liquid Laundry Detergent Cham/Lav 975', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:47:17', 0, 0, '2026-07-06 05:11:54'),
(138, 'FG0266-ETYL70CLS500', 'AlcoPlus Ethyl 70 Alcohol Classic 500mL', 19, 'PCS', 24, NULL, 0.00, '2026-07-04 09:47:33', 1, 0, '2026-07-06 05:47:51'),
(139, 'FG0127-NDCAGT500', 'Natural Dish Cleaner Aloe Green Tea 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:48:20', 0, 0, '2026-07-06 05:12:09'),
(140, 'FG0128-NDCAGT2000', 'Natural Dish Cleaner Aloe Green Tea 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:49:12', 0, 0, '2026-07-06 05:12:20'),
(141, 'FG0267-APISO70CLS150', 'AlcoPlus Iso 70 Alcohol Classic 150mL', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 09:49:23', 1, 0, '2026-07-06 05:48:12'),
(142, 'FG0129-NDCKWL500', 'Natural Dish Cleaner Kiwi Lemon 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:50:03', 0, 0, '2026-07-06 05:12:35'),
(143, 'FG0268-APISO70CLS250', 'AlcoPlus Iso 70 Alcohol Classic 250mL', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 09:50:23', 1, 0, '2026-07-06 05:48:29'),
(144, 'FG0130-NDCKWL2000', 'Natural Dish Cleaner Kiwi Lemon 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:51:06', 0, 0, '2026-07-06 05:12:46'),
(145, 'FG0269-APISO70CLS500', 'AlcoPlus Iso 70 Alcohol Classic 500mL', 19, 'PCS', 24, NULL, 0.00, '2026-07-04 09:51:54', 1, 0, '2026-07-06 05:48:57'),
(146, 'FG0270-APADVANCE40', 'AlcoPlus Advance Antibacterial Sanitizer 40mL', 19, 'PCS', 36, NULL, 0.00, '2026-07-04 09:53:41', 1, 0, '2026-07-06 05:49:16'),
(147, 'FG0131-NLLDGRF975', 'Natural Liquid Laundry Detergent Grapefruit 975ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:54:02', 0, 0, '2026-07-06 05:13:01'),
(148, 'FG0271-APADVANCE250', 'AlcoPlus Advance Antibacterial Sanitizer 250mL', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 09:54:53', 1, 0, '2026-07-06 05:49:43'),
(149, 'FG0132-NLLDLAV975', 'Natural Liquid Laundry Detergent Lavender 975ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:55:12', 0, 0, '2026-07-06 05:13:12'),
(150, 'FG0272-APADVANCE500', 'AlcoPlus Advance Antibacterial Sanitizer 500mL', 19, 'PCS', 24, NULL, 0.00, '2026-07-04 09:56:16', 1, 0, '2026-07-06 05:50:05'),
(151, 'FG0135-SCGC250', 'Squeaky Clean Glass Cleaner 250ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:57:33', 0, 0, '2026-07-06 05:14:01'),
(152, 'FG0136-TLWBER500', 'The Little Warrior Bergamot 505ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 09:59:20', 0, 0, '2026-07-06 05:14:15'),
(153, 'FG0137-TLWCAM500', 'The Little Warrior Chamomile 505ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:02:29', 0, 0, '2026-07-06 05:14:37'),
(154, 'FG0273-KPSS22x1', 'Keratin Plus Shampoo Daily Nourishing 22mlx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:03:10', 1, 0, '2026-07-04 02:03:10'),
(155, 'FG0138-TLWGNT500', 'The Little Warrior Green Tea 505ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:04:54', 0, 0, '2026-07-06 05:15:32'),
(156, 'FG0139-MOSCLNR500', 'Minty Orange Surface Cleaner 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:06:35', 0, 0, '2026-07-06 05:15:47'),
(157, 'FG0275-APADVANCEGAL', 'AlcoPlus Advance Antibacterial Sanitizer 3785mL', 19, 'PCS', 4, NULL, 0.00, '2026-07-04 10:07:13', 1, 0, '2026-07-06 05:51:02'),
(158, 'FG0140-MOSCLNR2000', 'Minty Orange Surface Cleaner 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:07:26', 0, 0, '2026-07-06 05:15:57'),
(159, 'FG0141-DASPRY500', 'Disinfectant Aroma Spray 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:08:29', 0, 0, '2026-07-06 05:16:08'),
(160, 'FG0142-DASPRY2000', 'Disinfectant Aroma Spray 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:09:28', 0, 0, '2026-07-06 05:16:19'),
(161, 'FG0143-MBFF200', 'Messy Bessy Fabric Freshener 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:10:29', 0, 0, '2026-07-06 05:16:33'),
(162, 'FG0147-HBWAGT500', 'Hand and Body Wash Aloe Green Tea 540ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:12:07', 0, 0, '2026-07-06 05:17:10'),
(163, 'FG0362-ADVANCPROMO50', 'AP Advance 500mL+40mL Promo', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:13:11', 1, 0, '2026-07-04 02:13:11'),
(164, 'FG0148-HBWAGT2000', 'Hand and Body Wash Aloe Green Tea 2000 ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:13:12', 0, 0, '2026-07-06 05:17:24'),
(165, 'FG0150-HBWKWL500', 'Hand and Body Wash Kiwi Lemon 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:14:51', 0, 0, '2026-07-06 05:17:41'),
(166, 'FG0151-HBWKWL2000', 'Hand and Body Wash Kiwi Lemon 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:15:38', 0, 0, '2026-07-06 05:18:03'),
(167, 'FG0152-HBWOR200', 'Hand and Body Wash Ocean Rain 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:20:54', 0, 0, '2026-07-06 05:18:15'),
(168, 'FG0368-KSHAMP200', 'Keratin Plus Shampoo Daily Nourishing 200mlx24', 19, 'PCS', 24, NULL, 0.00, '2026-07-04 10:21:25', 1, 0, '2026-07-06 06:12:42'),
(169, 'FG0153-HBWOR500', 'Hand and Body Wash Ocean Rain 500 ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:21:37', 0, 0, '2026-07-06 05:18:26'),
(170, 'FG0154-HBWOR2000', 'Hand and Body Wash Ocean Rain 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:22:25', 0, 0, '2026-07-06 05:18:37'),
(171, 'FG0155-BBRC200', 'Messy Baby Bug Repellent Cologne 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:23:34', 0, 0, '2026-07-06 05:18:47'),
(172, 'FG157-BHTW500', 'Messy Baby Head to Toe wash 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:24:22', 1, 0, '2026-07-04 02:24:22'),
(173, 'FG0385-ETYL250+2KGLD', 'AP Ethyl 70 Alcohol Classic 250mL+1K Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 10:24:42', 1, 0, '2026-07-06 06:19:22'),
(174, 'FG0159-BTSC200', 'Messy Baby Toy and Surface Cleaner 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:25:32', 0, 0, '2026-07-06 05:19:31'),
(175, 'FG0386-ISO250+2KGLD', 'AP Isopropyl 70 Alcohol Classic 250mL+1K Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 10:25:46', 1, 0, '2026-07-06 06:19:46'),
(176, 'FG0161-MHFBW500', 'Messy Man Hair Face and Body Wash 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:26:36', 0, 0, '2026-07-06 05:19:55'),
(177, 'FG0388-ETYL150+1KGLD', 'AP Ethyl 70 Alcohol Classic 150mL+1K Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 10:27:56', 1, 0, '2026-07-06 06:20:53'),
(178, 'FG0389-ISO150+1KGLD', 'AP Isopropyl 70 Alcohol Classic 150mL+1K Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 10:28:53', 1, 0, '2026-07-06 06:21:08'),
(179, 'FG0390-ETYL500+2KGLD', 'AP Ethyl 70 Alcohol Classic 500mL+2K Gold', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:30:13', 1, 0, '2026-07-04 02:30:13'),
(180, 'FG0391-ISO500+2KGLD', 'AP Isopropyl 70 Alcohol Classic 500mL+2K Gold', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:31:15', 1, 0, '2026-07-04 02:31:15'),
(181, 'FG0395-GGASOR12', 'Grips Gel Assorted 12gx12x48', 19, 'PCS', 576, NULL, 0.00, '2026-07-04 10:32:22', 1, 0, '2026-07-06 06:24:38'),
(182, 'FG0162-MSPRTZR200', 'Messy Man Sports Spritzer 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:33:44', 0, 0, '2026-07-06 05:20:22'),
(183, 'FG0163-OASPRY200', 'Odor Absorber Spray 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:34:54', 0, 0, '2026-07-06 05:20:34'),
(184, 'FG0167-TTMM250', 'Tea Tree Mold and Mildew 250ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:36:08', 0, 0, '2026-07-06 05:21:07'),
(185, 'FG0424-KSHAM200+2GLD', 'Keratin Shampoo 200mL+2 Keratin Gold Promo', 19, 'PCS', 24, NULL, 0.00, '2026-07-04 10:36:20', 1, 0, '2026-07-06 06:33:36'),
(186, 'FG0174-BPDSY50', 'Be Poolite Deodorizer Spray 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:37:12', 0, 0, '2026-07-06 05:22:04'),
(187, 'FG0425-EMPSHAx6+3GLD', 'Empress Shampoo 21mL x 6+3 Empress Gold Promo', 19, 'PCS', 288, NULL, 0.00, '2026-07-04 10:38:00', 1, 0, '2026-07-06 06:33:51'),
(188, 'FG0175-BPDSY250', 'Be Poolite Deodorizer Spray 250ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:39:08', 0, 0, '2026-07-06 05:22:16'),
(189, 'FG0177-MHFBW2000', 'Messy Man Hair Face and Body Wash 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:40:01', 0, 0, '2026-07-06 05:22:33'),
(190, 'FG0179-HANDCREDUO', 'Messy Bessy Hand Care Duo', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:41:00', 0, 0, '2026-07-06 05:22:56'),
(191, 'FG0181-WWCX250', 'Woody Wood Cleaner and Conditioner Spray 250ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:41:57', 0, 0, '2026-07-06 05:23:15'),
(192, 'FG0182-DBCX2000', 'Messy Baby Dish and Bottle Cleaner 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:42:36', 0, 0, '2026-07-06 05:23:25'),
(193, 'FG0540-GGEL12FLYER', 'Grips Gel Assorted 12gx1 Flyer', 19, 'PCS', 576, NULL, 0.00, '2026-07-04 10:43:17', 1, 0, '2026-07-06 06:35:52'),
(194, 'FG0183-SSPUNX200', 'Messy Bessy Sport Spritzer Her 200ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:43:19', 0, 0, '2026-07-06 05:23:37'),
(195, 'FG0541-GCLAY5FLYER', 'Grips Clay 5gx1 Flyer', 19, 'PCS', 400, NULL, 0.00, '2026-07-04 10:44:53', 1, 0, '2026-07-06 06:36:20'),
(196, 'FG184-BHTW2000', 'Messy Baby Head to Toe Wash 2000ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:46:15', 1, 0, '2026-07-04 02:46:15'),
(197, 'FG0542-GWSHINY5FLYER', 'Grips Wax Shiny 5gx1 Flyer', 19, 'PCS', 400, NULL, 0.00, '2026-07-04 10:46:23', 1, 0, '2026-07-06 06:36:33'),
(198, 'FG0193-CS-DASPRY50', 'CS Disinfectant Aroma Spray 50', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:47:02', 0, 0, '2026-07-06 05:25:21'),
(199, 'FG0194-CS-DWCLNR50', 'CS Desk Workspace Cleaner 50', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:48:00', 0, 0, '2026-07-06 05:25:33'),
(200, 'FG0543-GWMAT5FLYER', 'Grips Wax Mat 5gx1 Flyer', 19, 'PCS', 400, NULL, 0.00, '2026-07-04 10:48:07', 1, 0, '2026-07-06 06:36:46'),
(201, 'FG0195-CS-PDSY50', 'CS Potty Disinfectant Spray 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:48:38', 0, 0, '2026-07-06 05:25:42'),
(202, 'FG0544-GWMAT5FLYER', 'Grips Wax Mat 5gx1 Flyer', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:49:03', 1, 0, '2026-07-04 02:49:03'),
(203, 'FG0196-CS-HCKIWI10', 'CS Hand Cream Kiwi 10g', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:49:50', 0, 0, '2026-07-06 05:25:51'),
(204, 'FG0197-CS-TLWCAM50', 'Little Warrior Chamomile 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:50:24', 0, 0, '2026-07-06 05:25:58'),
(205, 'FG0198-CS-TLWGNT50', 'Little Warrior Green Tea 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:51:09', 0, 0, '2026-07-06 05:26:07'),
(206, 'FG0551-MAT5X2SAMPLER', 'Sampler- Wax Hard Mat x2(for Tie up)', 19, 'PCS', 1280, NULL, 0.00, '2026-07-04 10:51:22', 1, 0, '2026-07-06 06:38:06'),
(207, 'FG0199-CS-HBWLAV50', 'CS Hand Body Wash Lavender 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:51:50', 0, 0, '2026-07-06 05:26:17'),
(208, 'FG0552-CLAY6+1PROMO', 'Promo- Grips Hair Clay 5gx72 packs (6+1)', 19, 'PCS', 504, NULL, 0.00, '2026-07-04 10:53:03', 1, 0, '2026-07-06 06:38:18'),
(209, 'FG0200-CS-BSLAV50', 'CS Body Spray Lavender 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:54:04', 0, 0, '2026-07-06 05:26:26'),
(210, 'FG0201-CS-HCLAV50', 'CS Hand Cream Lavender Dream 50g', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:55:01', 0, 0, '2026-07-06 05:26:36'),
(211, 'FG0554-KSHAM200+2LUX', 'Keratin Shampoo 200mL + 2 Lux PROMO', 19, 'PCS', 24, NULL, 0.00, '2026-07-04 10:55:13', 1, 0, '2026-07-06 06:38:44'),
(212, 'FG0202-CS-HBWBAM50', 'CS Hand Body Wash Bamboo Fresh 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:56:40', 0, 0, '2026-07-06 05:26:46'),
(213, 'FG0555-KSHAMPOO22x2', 'Sampler Keratin Daily Nourishing 22ml x 2 (Tie Up)', 19, 'PCS', 500, NULL, 0.00, '2026-07-04 10:58:15', 1, 0, '2026-07-06 06:39:44'),
(214, 'FG203-CS-BSBAM50', 'CS Body Spray Bamboo 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 10:58:22', 1, 0, '2026-07-04 02:58:22'),
(215, 'FG0558-SHINY5x2SMPLR', 'Sampler- Wax Hard and Shiny x 2 (Tie up)', 19, 'PCS', 1280, NULL, 0.00, '2026-07-04 10:59:49', 1, 0, '2026-07-06 06:40:46'),
(216, 'FG0204-CS-HCBAM50', 'CS Hand Cream Bamboo Fresh 50g', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:00:59', 0, 0, '2026-07-06 05:27:32'),
(217, 'FG0559-ADV250+2KGOLD', 'Alcoplus Advance 250mL+2 Keratin Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-04 11:01:02', 1, 0, '2026-07-06 06:40:59'),
(218, 'FG0207-RLSPRAYBAM50', 'CS Room and Linen Spray Bamboo Fresh 50', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:01:59', 0, 0, '2026-07-06 05:27:59'),
(219, 'FG0208-RLSPRAYLAV50', 'CS Room and Linen Spray Lavander Dream 50', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:04:24', 0, 0, '2026-07-06 05:28:10'),
(220, 'FG0560-WAXMAT5g12+2', 'Promo- Grips Wax Hard n Mat 5g 12+2 Grips HM', 19, 'PCS', 504, NULL, 0.00, '2026-07-04 11:04:26', 1, 0, '2026-07-06 06:41:10'),
(221, 'FG0244-HFCREMKIWI50', 'hand Cream Kiwi 50g', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:05:08', 1, 0, '2026-07-04 03:05:08'),
(222, 'FG0561-WAXEHM5g12+2', 'Promo- Grips Wax Extreme HM5g 12+2 Grips Extreme', 19, 'PCS', 504, NULL, 0.00, '2026-07-04 11:06:32', 1, 0, '2026-07-06 06:41:28'),
(223, 'FG0562-KERSHAMPFLYER', 'Sampler -Keratin Shampoo Daily Nourishing Flyer', 19, 'PCS', 250, NULL, 0.00, '2026-07-04 11:08:41', 1, 0, '2026-07-06 06:41:47'),
(224, 'FG0569-GCLAY5x2', 'Sampler- Grips Hair Clay 5gx2', 19, 'PCS', 500, NULL, 0.00, '2026-07-04 11:10:43', 1, 0, '2026-07-06 06:44:55'),
(225, 'FG0571-EMPSHAFLYERx1', 'Sampler- Empress Shampoo Long n Healthy x1 Flyer', 19, 'PCS', 400, NULL, 0.00, '2026-07-04 11:12:40', 1, 0, '2026-07-06 06:45:29'),
(226, 'FG0572-POMADE5FLYRx1', 'Sampler- Grips Pomade x1 Flyer', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:14:30', 1, 0, '2026-07-04 03:14:30'),
(227, 'FG0574-POMADE5x1', 'Sampler - Grips Pomade x1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:16:16', 0, 0, '2026-07-06 06:46:01'),
(228, 'FG0609-BLAFALCON15', 'BareLab Hair Conditioner 15mL x12s x24', 19, 'PCS', 288, NULL, 0.00, '2026-07-04 11:18:19', 1, 0, '2026-07-06 06:49:18'),
(229, 'FG0611-BLKERTRCON20', 'BareLab Keratin Intense Deluxe 20gx12sx24', 19, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:21:20', 1, 0, '2026-07-04 03:21:20'),
(230, 'FG0646-BLKERTRCON20', 'BareLab Keratin Intense Deluxe 20gx12sx24', 19, 'PCS', 288, NULL, 0.00, '2026-07-04 11:24:13', 1, 0, '2026-07-06 06:59:57'),
(231, 'FG0647-BLSHAMnCON15', 'Barelab Shampoo n Conditioner 15mLx12sx24pck', 19, 'PCS', 288, NULL, 0.00, '2026-07-04 11:26:05', 1, 0, '2026-07-06 07:00:12'),
(232, 'FG0648-BLANTDNFSHA12', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', 19, 'PCS', 288, NULL, 0.00, '2026-07-04 11:29:30', 1, 0, '2026-07-04 08:16:46'),
(233, 'FG0001-XYGL25', 'Xylogel Oral Gel Bubblegum 25gx60', 17, 'PCS', 60, NULL, 0.00, '2026-07-04 11:34:38', 1, 0, '2026-07-06 03:07:17'),
(234, 'FG0002-XYLBS30', 'Xylorinse Bspray FrshMint 30mLx60', 17, 'PCS', 60, NULL, 0.00, '2026-07-04 11:35:48', 1, 0, '2026-07-06 03:07:39'),
(235, 'FG0003-XYLMW300', ' Xylorinse Mwash FrshMint 300mLx36', 17, 'PCS', 36, NULL, 0.00, '2026-07-04 11:37:04', 0, 0, '2026-07-06 03:32:34'),
(236, 'FG0351-GCHSFLOCRE500', 'GC Antibacterial Hand Soap Floral Care 500mL x 6', 15, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:37:23', 0, 0, '2026-07-06 06:06:50'),
(237, 'FG0004-XYCLNS50', 'Xylodens Tpaste Bblgum 50mLx108', 17, 'PCS', 108, NULL, 0.00, '2026-07-04 11:38:04', 1, 0, '2026-07-06 03:09:01'),
(238, 'FG0044-XYGL5', 'Xylogel Oral Teething Gel 5g', 17, 'PCS', 500, NULL, 0.00, '2026-07-04 11:38:55', 1, 0, '2026-07-06 03:41:03'),
(239, 'FG0352-GCHSCITCLN500', 'GC Antibacterial Hand Soap Citrus Clean 500mL x 6', 15, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:39:04', 0, 0, '2026-07-06 06:07:04'),
(240, 'FG0062-PRBDM40', 'Probioderm Cream 40g', 17, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:39:36', 0, 0, '2026-07-06 03:55:05'),
(241, 'FG0353-GCHSFLOCRE450', 'GC Antibacterial Hand Soap Floral Care 450mL x 12', 15, 'PCS', 12, NULL, 0.00, '2026-07-04 11:40:34', 1, 0, '2026-07-06 06:08:57'),
(242, 'FG0354-GCHSCITCLN450', 'GC Antibacterial Hand Soap Citrus Clean 450ml x 12', 15, 'PCS', 12, NULL, 0.00, '2026-07-04 11:42:05', 1, 0, '2026-07-06 06:09:20'),
(243, 'FG0375-GCHSCITCLN225', 'GC Antibacterial Hand Soap Citrus Clean 225mL x 12', 15, 'PCS', NULL, NULL, 0.00, '2026-07-04 11:43:47', 1, 0, '2026-07-04 03:43:47'),
(244, 'FG0211-TRUBHTTW250', 'True Baby 100 Natural Head to Toe Wash 250ml', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:16:10', 0, 0, '2026-07-06 05:29:45'),
(245, 'FG0212-TRUKCSBW250', 'True Kinder Nat Conditioning Shampoo+Body Wash 250', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:17:39', 0, 0, '2026-07-06 05:30:02'),
(246, 'FG0213-TRUKLOTION250', 'True Kinder 100 Natural Lotion 250', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:18:36', 0, 0, '2026-07-06 05:30:33'),
(247, 'FG0214-TRUKDSHBW250', 'True Kids 100 Nat Shampoo + Body Wash 250', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:19:37', 0, 0, '2026-07-06 05:30:50'),
(248, 'FG0215-TRUKDCOND250', 'True Kids 100 Natural Conditioner 250', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:20:35', 0, 0, '2026-07-06 05:31:22'),
(249, 'FG0216-TRUSHAMP250', 'True 100 Natural Shampoo 250x14', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:21:28', 0, 0, '2026-07-06 05:31:53'),
(250, 'FG0217-TRUCOND250', 'True 100 Women Natural Conditioner 230', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:22:23', 0, 0, '2026-07-06 05:32:03'),
(251, 'FG0220-TRUFACWSH250', 'True 100 Natural Facial Wash 250', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:23:11', 0, 0, '2026-07-06 05:32:31'),
(252, 'FG0221-TRUFEMWSH250', 'True 100 Natural Feminine Wash 250', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:24:11', 0, 0, '2026-07-06 05:32:47'),
(253, 'FG0232-TPSANWIPES30', 'True Protect Sanitizing Wipes 30s ', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:26:07', 0, 0, '2026-07-06 05:34:40'),
(254, 'FG0233-TPSANWIPES60', 'True Protect Sanitizing Wipes 60s', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:27:02', 0, 0, '2026-07-06 05:37:01'),
(255, 'FG0246-TRUSANLUXE50', 'True Protect Hand Sanitizer Spray Luxe 50ml', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:28:11', 0, 0, '2026-07-06 05:39:34'),
(256, 'FG0247-TRUSANAQUA50', 'True Protect Hand Sanitizer Spray Aqua 50ml', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:29:22', 0, 0, '2026-07-06 05:39:47'),
(257, 'FG0248-TRUSANGUMBR50', 'True Protect Hand Sanitizer Spray Gummy Bear 50ml', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:31:40', 0, 0, '2026-07-06 05:40:02'),
(258, 'FG0249-TRURSGOLUXE60', 'True Ready Soap Go Luxe 60ml', 23, 'PCS', 12, NULL, 0.00, '2026-07-04 13:33:46', 1, 0, '2026-07-06 05:40:30'),
(259, 'FG0250-TRURSGOAQUA60', 'True Ready Soap Go Aqua 60ml', 23, 'PCS', 12, NULL, 0.00, '2026-07-04 13:34:58', 1, 0, '2026-07-06 05:40:49'),
(260, 'FG0251-TRURSGOFMEL60', 'True Ready Soap Go Fruity Melon 60ml', 23, 'PCS', 12, NULL, 0.00, '2026-07-04 13:35:49', 1, 0, '2026-07-06 05:41:11'),
(261, 'FG0254-TPORVNLH500', 'True Orange Vanilla Liquid Hand Soap 500ml', 23, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:37:03', 1, 0, '2026-07-04 05:37:03'),
(262, 'FG0255-TPGTCAMLHS500', 'True Green Tea Chamomile Liquid Hand Soap 500ml', 23, 'PCS', 12, NULL, 0.00, '2026-07-04 13:38:36', 1, 0, '2026-07-06 05:41:59'),
(263, 'FG0157-BHTW500', 'Messy Baby Head to Toe Wash 500ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:41:23', 0, 0, '2026-07-06 05:19:06'),
(264, 'FG0213-TRUKLOTION250', 'True Kinder 100 Natural Lotion 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:42:05', 0, 0, '2026-07-06 05:30:29'),
(265, 'FG0214-TRUKDSHBW250', 'True Kids 100 Nat Shampoo + Body Wash 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:43:25', 0, 0, '2026-07-06 05:30:48'),
(266, 'FG0215-TRUKDCOND250', 'True Kids 100 Natural Conditioner 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:44:03', 0, 0, '2026-07-06 05:31:19'),
(267, 'FG0216-TRUSHAMP250', 'True 100 Natural Shampoo 250x14', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:44:48', 0, 0, '2026-07-06 05:31:50'),
(268, 'FG0217-TRUCOND250', 'True 100 Women Natural Conditioner 230', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:45:25', 0, 0, '2026-07-06 05:32:06'),
(269, 'FG0218-TRUBWASH250', 'True 100 Natural Body Wash 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:47:27', 1, 0, '2026-07-04 05:47:27'),
(270, 'FG0219-TRUBLOTION250', 'True 100 Natural Body Lotion 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:49:10', 1, 0, '2026-07-04 05:49:10'),
(271, 'FG0220-TRUFACWSH250', 'True 100 Natural Facial Wash 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:50:04', 0, 0, '2026-07-06 05:32:29'),
(272, 'FG0221-TRUFEMWSH250', 'True 100 Natural Feminine Wash 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:50:29', 0, 0, '2026-07-06 05:32:45'),
(273, 'FG0232-TPSANWIPES30', 'True Protect Sanitizing Wipes 30s ', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:51:07', 0, 0, '2026-07-06 05:34:37'),
(274, 'FG0233-TPSANWIPES60', 'True Protect Sanitizing Wipes 60s', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:51:46', 0, 0, '2026-07-06 05:36:59'),
(275, 'FG0237-CIAPDWLGAL', 'Premium Dishwashing Liquid GreenTea 3.7L', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:52:59', 0, 0, '2026-07-06 05:37:40'),
(276, 'FG0246-TRUSANLUXE50', 'True Protect Hand Sanitizer Spray Luxe 50ml', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:53:32', 0, 0, '2026-07-06 05:39:31'),
(277, 'FG0247-TRUSANAQUA50', 'True Protect Hand Sanitizer Spray Aqua 50ml', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:53:55', 0, 0, '2026-07-06 05:39:44'),
(278, 'FG0248-TRUSANGUMBR50', 'True Protect Hand Sanitizer Spray Gummy Bear 50ml', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:54:30', 0, 0, '2026-07-06 05:39:57'),
(279, 'FG0249-TRURSGOLUXE60', 'True Ready Soap Go Luxe 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-04 13:54:57', 1, 0, '2026-07-06 05:40:23'),
(280, 'FG0250-TRURSGOAQUA60', 'True Ready Soap Go Aqua 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-04 13:56:03', 1, 0, '2026-07-06 05:40:43'),
(281, 'FG0251-TRURSGOFMEL60', 'True Ready Soap Go Fruity Melon 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-04 13:56:29', 1, 0, '2026-07-06 05:41:02'),
(282, 'FG0254-TPORVNLH500', 'True Orange Vanilla Liquid Hand Soap 500ml', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:57:02', 1, 0, '2026-07-04 05:57:02'),
(283, 'FG0255-TPGTCAMLHS500', 'True Green Tea Chamomile Liquid Hand Soap 500ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-04 13:58:10', 1, 0, '2026-07-06 05:41:54'),
(284, 'FG0258-TRUHCBUNDLE', 'True Hair Care Bundle', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 13:59:10', 0, 0, '2026-07-06 05:45:48'),
(285, 'FG0260-TPBUNDLEKIDS', 'True Protect Bundle for Kids', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:00:06', 0, 0, '2026-07-06 05:46:02'),
(286, 'FG0261-TPBUNDLEHER', 'True Protect Bundle for Her', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:02:53', 0, 0, '2026-07-06 05:46:14'),
(287, 'FG0262-TPBUNDLEHIM', 'True Protect Bundle for Him', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:03:53', 0, 0, '2026-07-06 05:46:22'),
(288, 'FG0278-TRUBNCHTTW200', 'True Baby Natural Clean Head to Toe Wash 200', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:05:54', 0, 0, '2026-07-06 05:52:58'),
(289, 'FG0203-CS-BSBAM50', 'CS Body Spray Bamboo 50ml', 26, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:06:42', 0, 0, '2026-07-06 05:27:21'),
(290, 'FG0307-TPDWLORNG250', 'True Protect Dishwashing Liquid Orange  250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:07:16', 1, 0, '2026-07-04 06:07:16'),
(291, 'FG0308-TPDWLGRNT250', 'True Protect Dishwashing Liquid Green Tea 250', 24, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:08:11', 1, 0, '2026-07-04 06:08:11'),
(292, 'FG0304-PORIGWHLCOF250', 'Pouch Original Whole Coffee 250', 25, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:09:44', 1, 0, '2026-07-04 06:09:44'),
(293, 'FG0307-TPGTCAMHS500P', 'TP Green Tea Chamomile Liquid Hand Soap 500 Pouch', 9, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:14:00', 1, 0, '2026-07-04 06:14:00'),
(294, 'FG0313-TPCHBLMLH500', 'TP Cherry Blossom Plant-based Hand Soap 500ml', 9, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:15:32', 1, 0, '2026-07-04 06:15:32'),
(295, 'FG0211-TRUBHTTW250', 'True Baby 100 Natural Head to Toe Wash 250ml', 21, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:17:11', 0, 0, '2026-07-06 05:29:42'),
(296, 'FG0212-TRUKCSBW250', 'True Kinder Nat Conditioning Shampoo+Body Wash 250', 21, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:17:41', 0, 0, '2026-07-06 05:30:05'),
(297, 'FG0213-TRUKLOTION250', 'True Kinder 100 Natural Lotion 250', 21, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:18:21', 0, 0, '2026-07-06 05:30:26'),
(298, 'FG0214-TRUKDSHBW250', 'True Kids 100 Nat Shampoo + Body Wash 250', 21, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:18:50', 0, 0, '2026-07-06 05:30:45'),
(299, 'FG0215-TRUKDCOND250', 'True Kids 100 Natural Conditioner 250', 21, 'PCS', NULL, NULL, 0.00, '2026-07-04 14:19:22', 0, 0, '2026-07-06 05:31:17'),
(300, 'FG0415-RSGCRMLUXE30', 'True Protect Ready Smooth Go! Hand Cream Luxe 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:22:51', 1, 0, '2026-07-06 06:29:07'),
(301, 'FG0416-RSGCRMAQUA30', 'True Protect Ready Smooth Go! Hand Cream Aqua 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:27:13', 1, 0, '2026-07-06 06:32:02'),
(302, 'FG0417-RSGCRMFRUM30', 'TP Ready Smooth Go! Hand Cream Fruity Melon 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:28:39', 1, 0, '2026-07-06 06:32:13'),
(303, 'FG0418-RSGCRMPEPOR30', 'Ready Smooth Go! Hand Cream Peppermint Orange 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:30:06', 1, 0, '2026-07-06 06:32:23'),
(304, 'FG0419-RSGCRMCHERB30', 'TP Ready Smooth Go! Hand Cream Cherry Blossom 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:31:16', 1, 0, '2026-07-06 06:32:34'),
(305, 'FG0420-RSGCRMSTRAW30', 'Ready Smooth Go Hand Cream Strawberries N Cream 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:33:16', 1, 0, '2026-07-06 06:32:44'),
(306, 'FG0421-RSGCRMSWETP30', 'TP Ready Smooth Go Hand Cream Sweet Pea Bliss 30ml', 21, 'PCS', 25, NULL, 0.00, '2026-07-04 14:34:32', 1, 0, '2026-07-06 06:32:54'),
(307, 'FG0015-GGC50', 'Grips Gel (Clear) 60gx72', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 10:58:47', 0, 0, '2026-07-06 03:31:31'),
(308, 'FG0016-GGY50', 'Grips Gel (Yellow) 60gx72', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 10:59:14', 0, 0, '2026-07-06 03:31:18'),
(309, 'FG0017-GGG50', 'Grips Gel (Green) 60gx72', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 10:59:37', 0, 0, '2026-07-06 03:31:01'),
(310, 'FG0018-GGC130', 'Grips Gel (Clear) 130gx48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 11:00:13', 0, 0, '2026-07-06 03:30:48'),
(311, 'FG0019-GGY130', 'Grips Gel (Yellow) 130gx48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 11:00:37', 0, 0, '2026-07-06 03:30:22'),
(312, 'FG0020-GGG130', 'Grips Gel (Green) 130gx48', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 11:01:04', 0, 0, '2026-07-06 03:30:12'),
(313, 'FG0033-GCLAY5x6', 'Grips Clay 5gx6x36', 19, 'PCS', 432, NULL, 0.00, '2026-07-06 11:37:16', 1, 0, '2026-07-06 03:37:16'),
(314, 'FG0051-GWM75x1', 'Grips Wax Hard&Mat 75gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 11:45:48', 1, 0, '2026-07-06 03:45:48'),
(315, 'FG0060-GWS75x1', 'Grips Wax Hard&Shiny 75gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 11:54:27', 1, 0, '2026-07-06 03:54:27'),
(316, 'FG0061 -GWX75x1', 'Grips Wax Xtreme Hard & mat 75gx1', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 11:54:52', 1, 0, '2026-07-06 03:54:52'),
(317, 'FG0254-TPORVNLHS500', 'True Protect Orange Vanilla Liquid Hand Soap 500', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 13:43:29', 1, 0, '2026-07-06 05:43:29'),
(318, 'FG0254-TPORVNLHS500', 'True Protect Orange Vanilla Liquid Hand Soap 500', 23, 'PCS', 12, NULL, 0.00, '2026-07-06 13:43:45', 1, 0, '2026-07-06 05:43:45'),
(319, 'FG0277-ADVNCPROMO500', 'AP Advance 500mL+40mL refill Promo', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 13:52:22', 1, 0, '2026-07-06 05:52:22'),
(320, 'FG0279-TRUKNCCSBW200', 'True Kinder Natural Clean CS+Body Wash 200', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 13:53:59', 1, 0, '2026-07-06 05:53:59'),
(321, 'FG0280-TRUKNCSBW200', 'True Kids Natural & Clean Shampoo + Body Wash 200', 25, 'PCS', 12, NULL, 0.00, '2026-07-06 13:54:36', 1, 0, '2026-07-06 05:54:36'),
(322, 'FG0281-TRUKNCCOND200', 'True Kids Natural & Clean Conditioner 200', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 13:55:13', 1, 0, '2026-07-06 05:55:13'),
(323, 'FG0309-TRSGAPLCINN60', 'True Protect Ready Soap Go! Apple Cinnamon 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 13:59:18', 1, 0, '2026-07-06 05:59:18'),
(324, 'FG0310-TRSGPPRORNG60', 'True Protect Ready Soap Go! Peppermint Orange 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 13:59:39', 1, 0, '2026-07-06 05:59:39'),
(325, 'FG0312-TRSGCHBLSM60', 'True Protect Ready Soap Go! Cherry Blossom 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:00:20', 1, 0, '2026-07-06 06:00:20'),
(326, 'FG0313-TPCHBLMLHS500', 'True Protect Cherry Blossom Liquid Hand Soap 500ml', 23, 'PCS', 12, NULL, 0.00, '2026-07-06 14:00:41', 1, 0, '2026-07-06 06:30:12'),
(327, 'FG0333-TRSGSTRWCRM60', 'TP Ready Soap Go! Strawberries n Cream 60ml', 21, 'PCS', 12, NULL, 0.00, '2026-07-06 14:03:29', 1, 0, '2026-07-06 06:29:58'),
(328, 'FG0334-TRSGTROPUN60', 'True Protect Ready Soap Go! Tropical Punch 60ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:03:49', 1, 0, '2026-07-06 06:03:49'),
(329, 'FG0335-THSSTRWCRM500', 'TP Strawberry n Cream Liquid Hand Soap 500ml', 21, 'PCS', 12, NULL, 0.00, '2026-07-06 14:04:06', 1, 0, '2026-07-06 06:29:48'),
(330, 'FG0336-THSTROPUN500', 'True Protect Tropical Punch Liquid Hand Soap 500ml', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:04:25', 1, 0, '2026-07-06 06:04:25'),
(331, 'FG0362-ADVNCPROMO500', 'AP Advance 500mL+ 40mL Promo', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:10:21', 1, 0, '2026-07-06 06:10:21'),
(332, 'FG0366-ISOPRO500+KGC', 'AP Iso Classic 500ml with Free KGold Conditioner', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:11:43', 1, 0, '2026-07-06 06:11:43'),
(333, 'FG0367-ETHPRO500+KRC', 'AP Ethyl Classic 500ml with Free KRed Conditioner ', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:12:03', 1, 0, '2026-07-06 06:12:03'),
(334, 'FG0369-TRSGVANBN60', 'True Protect Ready Soap, Go! Vanilla Bean 60mL', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:13:55', 1, 0, '2026-07-06 06:13:55'),
(335, 'FG0372-TOYSLION', 'True Plush Toys Lion 8inch', 24, 'PCS', NULL, NULL, 0.00, '2026-07-06 14:16:48', 1, 0, '2026-07-06 06:16:48'),
(336, 'FG0373-TOYSELEPHANT', 'True Plush Toys Elephant 8inch', 24, 'PCS', NULL, NULL, 0.00, '2026-07-06 14:17:04', 1, 0, '2026-07-06 06:17:04'),
(337, 'FG0374-TOYSWHALE', 'True Plush Toys Whale 8inch', 24, 'PCS', NULL, NULL, 0.00, '2026-07-06 14:17:18', 1, 0, '2026-07-06 06:17:18'),
(338, 'FG0384-RSGSWEETPEA60', 'Ready Soap, Go! Sweet Pea Bliss No Rinse Foaming Soap 60mL x 12', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:18:34', 1, 0, '2026-07-06 06:18:34'),
(339, 'FG0387-ADV250+2KGLD', 'AP 70% Alcohol Classic 250mL + 1 K Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-06 14:20:14', 1, 0, '2026-07-06 06:20:14'),
(340, 'FG0390-ETYL500+1KGLD', 'AP Ethyl 70% Alcohol Classic 500mL + 2 K Gold', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:21:40', 1, 0, '2026-07-06 06:21:40'),
(341, 'FG0391-ISO500+1KGLD', 'AP Isopropyl 70% Alcohol Classic 500mL + 2 K Gold', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:22:00', 1, 0, '2026-07-06 06:22:00'),
(342, 'FG0393-RSGCHOMART60', 'RSG Chocolate Martini No Rinse Foam Soap 60mlx12', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:23:56', 1, 0, '2026-07-06 06:23:56'),
(343, 'FG0394-RSGBLUEBELI60', 'RSG Blueberry Bellini No Rinse Foam Soap 60mLx12', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:24:15', 1, 0, '2026-07-06 06:24:15'),
(344, 'FG0396-RSGRELAX60', 'RSG Relax No Rinse Foam Soap 60mlx12', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:25:15', 1, 0, '2026-07-06 06:25:15'),
(345, 'FG0397-RSGREFRESH60', 'RSG Refresh No Rinse Foam Soap 60mlx12', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:25:45', 1, 0, '2026-07-06 06:25:45'),
(346, 'FG0398-RSGRENEW60', 'RSG Renew No Rinse Foam Soap 60mlx12', 24, 'PCS', 12, NULL, 0.00, '2026-07-06 14:26:07', 1, 0, '2026-07-06 06:26:07'),
(347, 'FG0399-GGC12', 'Grips Gel Clear 12g', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 14:27:08', 1, 0, '2026-07-06 06:27:08'),
(348, 'FG0400-GGY12', 'Grips Gel Yellow 12g', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 14:27:25', 1, 0, '2026-07-06 06:27:25'),
(349, 'FG0401-GGG12', 'Grips Gel Green 12g', 19, 'PCS', NULL, NULL, 0.00, '2026-07-06 14:27:45', 1, 0, '2026-07-06 06:27:45'),
(350, 'FG0402-ADV500+2KGCON', 'AP Advance 500mL + 2 K Gold Promo', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:28:06', 1, 0, '2026-07-06 06:28:06'),
(351, 'FG0544-GWXTREM5FLYER', 'Grips Wax Xtreme 5g x 1 Flyer', 19, 'PCS', 400, NULL, 0.00, '2026-07-06 14:37:20', 1, 0, '2026-07-06 06:37:20'),
(352, 'FG0556-KSHA+KGDB12F2', 'Keratin Shampoo + Keratin Gold Promo Buy 12 Free 2', 19, 'PCS', 288, NULL, 0.00, '2026-07-06 14:40:12', 1, 0, '2026-07-06 06:40:12'),
(353, 'FG0566-TRSGAMETH60', 'True Ready Soap Go! Amethyst 60mL', 21, 'PCS', 12, NULL, 0.00, '2026-07-06 14:43:34', 1, 0, '2026-07-06 06:43:34'),
(354, 'FG0567-TRSGVERDE60', 'True Ready Soap Go! Verde 60mL', 21, 'PCS', 12, NULL, 0.00, '2026-07-06 14:43:56', 1, 0, '2026-07-06 06:43:56'),
(355, 'FG0605-HBWAGT50', 'Messy Bessy Hand & Body Wash Aloe Green Tea 50mL', 10, 'PCS', 16, NULL, 0.00, '2026-07-06 14:47:26', 1, 0, '2026-07-06 06:47:26'),
(356, 'FG0606-HBWAGT500', 'Messy Bessy Hand & Body Wash Aloe Green Tea 500mL', 10, 'PCS', 12, NULL, 0.00, '2026-07-06 14:47:46', 1, 0, '2026-07-06 06:47:46'),
(357, 'FG0607-HBWOR50', 'Messy Bessy Hand & Body Wash Ocean Rain 50mL', 10, 'PCS', 16, NULL, 0.00, '2026-07-06 14:48:38', 1, 0, '2026-07-06 06:48:38'),
(358, 'FG0608-HBWOR500', 'Messy Bessy Hand & Body Wash Ocean Rain 500mL', 10, 'PCS', 12, NULL, 0.00, '2026-07-06 14:48:55', 1, 0, '2026-07-06 06:48:55'),
(359, 'FG0610-BLADANSHAM12', 'BareLab Anti-dandruff Shampoo 12mL x 12s x 24 packs', 19, 'PCS', 288, NULL, 0.00, '2026-07-06 14:49:47', 1, 0, '2026-07-06 06:49:47'),
(360, 'FG0634-RSGPSVERDE5', 'True Ready Scent, Go! Verde Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:54:09', 1, 0, '2026-07-06 06:57:27'),
(361, 'FG0635-RSGPSAME5', 'True Ready Scent, Go! Amethyst Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:54:32', 1, 0, '2026-07-06 06:57:33'),
(362, 'FG0636-RSGPSSPARK5', 'True Ready Scent, Go! Sparkle Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:54:52', 1, 0, '2026-07-06 06:57:39'),
(363, 'FG0637-RSGPSCOAST5', 'True Ready Scent, Go! Coastal Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:55:14', 1, 0, '2026-07-06 06:57:21'),
(364, 'FG0638-RSGPSAURA5', 'True Ready Scent, Go! Aura Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:55:38', 1, 0, '2026-07-06 06:57:13'),
(365, 'FG0639-RSGPSCHERP5', 'True Ready Scent, Go! Cherry Pop Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:55:57', 1, 0, '2026-07-06 06:57:06'),
(366, 'FG0640-RSGPSPHAN5', 'True Ready Scent, Go! Phantom Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:56:21', 1, 0, '2026-07-06 06:57:01'),
(367, 'FG0641-RSGPSHORIZ5', 'True Ready Scent, Go! Horizon Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:56:53', 1, 0, '2026-07-06 06:56:53'),
(368, 'FG0642-RSGPSBLUSH5', 'True Ready Scent, Go! Blush Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:58:03', 1, 0, '2026-07-06 06:58:03'),
(369, 'FG0643-RSGPSGLAM5', 'True Ready Scent, Go! Glam Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:58:24', 1, 0, '2026-07-06 06:58:24');
INSERT INTO `items` (`item_id`, `item_code`, `item_description`, `customer_id`, `item_uom`, `uom_conversion`, `item_size`, `item_amount`, `date_created`, `status`, `remove`, `last_update`) VALUES
(370, 'FG0644-ETYL500+2KER', 'Promo Ethyl Classic 500mL + 2 Keratin Shampoo 22mL', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:58:46', 1, 0, '2026-07-06 06:58:46'),
(371, 'FG0645-ISO500+2KER', 'Promo Isopropyl Classic 500mL + 2 Keratin Shampoo 22mL', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 14:59:11', 1, 0, '2026-07-06 06:59:11'),
(372, 'FG0649-MBLAGT50mL', 'MB Hand & Body Lotion Aloe Green Tea 50mLx36', 10, 'PCS', 36, NULL, 0.00, '2026-07-06 15:15:05', 1, 0, '2026-07-06 07:15:05'),
(373, 'FG0650-MBLAGT300mL', 'MB Hand & Body Lotion Aloe Green Tea 300mLx12', 10, 'PCS', 12, NULL, 0.00, '2026-07-06 15:15:29', 1, 0, '2026-07-06 07:15:29'),
(374, 'FG0651-MBLAGT1L', 'MB Hand & Body Lotion Aloe Green Tea 1Lx6', 10, 'PCS', 6, NULL, 0.00, '2026-07-06 15:15:54', 1, 0, '2026-07-06 07:15:54'),
(375, 'FG0652-MBLOR50mL', 'MB Hand & Body Lotion Ocean Rain 50mLx36', 10, 'PCS', 36, NULL, 0.00, '2026-07-06 15:16:18', 1, 0, '2026-07-06 07:16:18'),
(376, 'FG0653-MBLOR300mL', 'MB Hand & Body Lotion Ocean Rain 300mLx12', 10, 'PCS', 12, NULL, 0.00, '2026-07-06 15:16:41', 1, 0, '2026-07-06 07:16:41'),
(377, 'FG0654-MBLOR1L', 'MB Hand & Body Lotion Ocean Rain 1Lx6', 10, 'PCS', 6, NULL, 0.00, '2026-07-06 15:16:59', 1, 0, '2026-07-06 07:16:59'),
(378, 'FG0655-MBLOR4L', 'MB Hand & Body Lotion Ocean Rain 4Lx4', 10, 'PCS', 4, NULL, 0.00, '2026-07-06 15:17:25', 1, 0, '2026-07-06 07:17:25'),
(379, 'FG0656-MBLLAV50mL', 'MB Hand & Body Lotion Lavender Dream 50mLx36', 10, 'PCS', 36, NULL, 0.00, '2026-07-06 15:17:56', 1, 0, '2026-07-06 07:17:56'),
(380, 'FG0657-MBLLAV300mL', 'MB Hand & Body Lotion Lavender Dream 300mLx12', 10, 'PCS', 12, NULL, 0.00, '2026-07-06 15:18:14', 1, 0, '2026-07-06 07:18:14'),
(381, 'FG0658-MBLLAV1L', 'MB Hand & Body Lotion Lavender Dream 1Lx6', 10, 'PCS', 6, NULL, 0.00, '2026-07-06 15:18:35', 1, 0, '2026-07-06 07:18:35'),
(382, 'FG0659-MBHBWLAV50', 'Messy Bessy Hand n Body Wash Lavender Dream 50mL', 10, 'PCS', 36, NULL, 0.00, '2026-07-06 15:19:04', 1, 0, '2026-07-06 07:19:04'),
(383, 'FG0660-MBHBWLAV500', 'Messy Bessy Hand n Body Wash Lavender Dream 500mL', 10, 'PCS', 12, NULL, 0.00, '2026-07-06 15:19:49', 1, 0, '2026-07-06 07:19:49'),
(384, 'FG0661-MBHBWLAV1L', 'Messy Bessy Hand n Body Wash Lavender Dream 1L', 10, 'PCS', 6, NULL, 0.00, '2026-07-06 15:20:16', 1, 0, '2026-07-06 07:20:16'),
(385, 'FG0663-EMPSINGX1FLY', 'Sampler - Empress Shampoo LongnHealthy Single x1 FLY', 19, 'PCS', 500, NULL, 0.00, '2026-07-06 15:21:29', 1, 0, '2026-07-06 07:21:29'),
(386, 'FG0666-MBHBWOR1L', 'Messy Bessy Hand n Body Wash Ocean Rain 1Lx6', 10, 'PCS', 6, NULL, 0.00, '2026-07-06 15:22:46', 1, 0, '2026-07-06 07:22:46'),
(387, 'FG0667-HBWAGT1L', 'Messy Bessy Hand & Body Wash Aloe Green Tea 1L', 10, 'PCS', 6, NULL, 0.00, '2026-07-06 15:26:38', 1, 0, '2026-07-06 07:26:38'),
(388, 'FG0668-ADV250+1KGOLD', 'Promo - Alcoplus Advance 250ml + 1Keratin Gold', 19, 'PCS', 48, NULL, 0.00, '2026-07-06 15:27:18', 1, 0, '2026-07-06 07:27:18'),
(389, 'FG0669-KDNBOT1GET50', 'Promo - Keratin DN 200mL Buy 1 Get 2nd at 50%', 19, 'PCS', 24, NULL, 0.00, '2026-07-06 15:27:39', 1, 0, '2026-07-06 07:27:39'),
(390, 'FG0673-TRSGLUXE120', 'True Ready Soap, Go! Luxe 120ml Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:33:58', 1, 0, '2026-07-06 07:33:58'),
(391, 'FG0674-TRSGAQUA120', 'True Ready Soap, Go! Aqua 120ml Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:34:17', 1, 0, '2026-07-06 07:34:17'),
(392, 'FG0675-TRSGFMEL120', 'True Ready Soap, Go! Fruity Melon 120ml Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:34:38', 1, 0, '2026-07-06 07:34:38'),
(393, 'FG0676-TRSGAPLCIN120', 'True Ready Soap, Go! Apple Cinnamon 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:34:54', 1, 0, '2026-07-06 07:34:54'),
(394, 'FG0677-TRSGPRORNG120', 'True Ready Soap, Go! Peppermint Orange 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:35:13', 1, 0, '2026-07-06 07:35:13'),
(395, 'FG0678-TRSGCHBLSM120', 'True Ready Soap, Go! Cherry Blossom 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:35:32', 1, 0, '2026-07-06 07:35:32'),
(396, 'FG0679-TRSGSTRWCR120', 'True RSoapGo! Strawberries & Cream 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:35:59', 1, 0, '2026-07-06 07:35:59'),
(397, 'FG0680-TRSGTROPUN120', 'True Ready Soap, Go! Tropical Punch 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:36:15', 1, 0, '2026-07-06 07:36:15'),
(398, 'FG0682-RSGSWTPEAB120', 'True Ready Soap, Go! Sweet Pea Bliss 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:38:33', 1, 0, '2026-07-06 07:38:33'),
(399, 'FG0683-RSGBLUBELI120', 'True Ready Soap, Go! Blueberry Bellini 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:38:53', 1, 0, '2026-07-06 07:38:53'),
(400, 'FG0684-RSGCHOMAR120', 'True Ready Soap, Go! Chocolate Martini 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:39:09', 1, 0, '2026-07-06 07:39:09'),
(401, 'FG0685-TRSGAMETH120', 'True Ready Soap, Go! Amethyst 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:39:30', 1, 0, '2026-07-06 07:39:30'),
(402, 'FG0686-TRSGVERDE120', 'True Ready Soap, Go! Verde 120mL Refill', 11, 'PCS', 9, NULL, 0.00, '2026-07-06 15:39:53', 1, 0, '2026-07-06 07:39:53'),
(403, 'FG0687-GCNSTNGSAN40', 'Green Cross Gentle Protect No-Sting Sanitizer 40mL', 15, 'PCS', 24, NULL, 0.00, '2026-07-06 15:41:43', 1, 0, '2026-07-06 07:41:43'),
(404, 'FG0688-GCNSTNGSAN250', 'Green Cross Gentle Protect No-Sting Sanitizer 250mL', 15, 'PCS', 48, NULL, 0.00, '2026-07-06 15:42:00', 1, 0, '2026-07-06 07:42:00'),
(405, 'FG0186-KPSS22x6', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(406, 'FG0311-EMPRSSHAMPx12', 'Empress Shampoo x 12', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(407, 'FG0337-EMPRSSHAMPx6', 'Empress Shampoo Long and Healthy 21ml x6pcs x48', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(408, 'FG0564-BLHCONDI15x6', 'BareLab Hair Conditioner 15mL x 6', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(409, 'FG0573-BLHCONDI15x12', 'BareLab Sleek and Straight 15ml x 12s x 24packs', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(410, 'FG0662-BLSHAMLONG15', 'Barelab Shampoo Long and Nourished 15mL', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(411, 'FG0665-BLSSOFnSHIN15', 'Barelab Shampoo Soft and Shiny 15mL', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(412, 'FG0670-BLSLEEK13X12', 'BareLab Sleek and Straight 13mlx12sx24packs', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(413, 'FG0671-BLAFALCON13', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(414, 'FG0672-BLKERTRCON180', 'BareLab Keratin Treatment Conditioner 180g', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(415, 'FG0187-EDTSUN60', 'Pure Basic EDT Sunny 60ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(416, 'FG0188-EDTROM60', 'Pure Basic EDT Romantic 60ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(417, 'FG0189-EDTFRESH60', 'Pure Basic EDT Fresh 60ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(418, 'FG0338-EMPRSSHAMPx1', 'Empress Shampoo x 1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(419, 'FG0382-PROKP12+2KGLD', 'Keratin Shampoo 12+2 Keratin Gold Promo', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(420, 'FG0383-PROEM12+2KGLD', 'Empress Shampoo 12+2 Keratin Gold Promo', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(421, 'FG0553-KSHAM6+1PROMO', 'Keratin Shampoo 22mL 6 + 1 PROMO', 27, 'PCS', 312, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(422, 'FG0563-COATCLAS50x12', 'Pure Basics Hair Cuticle Coat Classic 50mL x 12', 27, 'PCS', 144, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(423, 'FG0568-EMPSHA12+2ELH', 'Empress Shampoo Long n Healthy 12+2 Empress LH', 27, 'PCS', 312, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(424, 'FG0570-KERSHA12+2KDN', 'Keratin Shampoo Daily Nourishing 12+2 Keratin DN', 27, 'PCS', 312, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(425, 'FG0575-EMPSHAx4', 'Empress Shampoo Long n Healthy x4', 27, 'PCS', 400, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(426, 'FG0589-EMPx6+2KGOLD', 'Empress Shampoo Long n Heathy 6 + 2 Keratin Gold', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(427, 'FG0633-EMPSHAM11+1', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(428, 'FG0664-EMPSHAx2', 'Empress Shampoo Long n Healthy x2', 27, 'PCS', 500, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(429, 'FG0190-EDTACT60', 'Pure Basic EDT Active 60ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(430, 'FG0191-EDTCOOL60', 'Pure Basic EDT Cool 60ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(431, 'FG0192-EDTAQUA60', 'Pure Basic EDT Aqua 60ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(432, 'FG0235-EDTBLOOM100', 'Pure Basic EDT Bloom 100ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(433, 'FG0236-EDTINTENSE100', 'Pure Bacic EDT Intense 100ml', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(434, 'FG0263-KPSS22x11+1', 'Keratin Plus Shampoo Soft Smooth 22mlx11+1 Promo', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(435, 'FG0011-GGC14', 'Grips Gel (Clear) 14g', 27, 'PCS', 576, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(436, 'FG0012-GGY14', 'Grips Gel (Yellow) 14g', 27, 'PCS', 576, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(437, 'FG0013-GGG14', 'Grips Gel (Green) 14g', 27, 'PCS', 576, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(438, 'FG0014-GGASOR14', 'Grips Gel Assorted 14gx12x48', 27, 'PCS', 1728, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(439, 'FG0021-GWM5x12', 'Grips Wax Hard&Mat 5gx12x36', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(440, 'FG0022-GWS5x12', 'Grips Wax Hard&Shiny 5gx12x36', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(441, 'FG0023-GWX5x12', 'Grips Wax Xtreme Mat 5Gx12x36', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(442, 'FG0024-GWM5x6', 'Grips Wax Hard&Mat 5gx6x72', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(443, 'FG0025-GWS5x6', 'Grips Wax Hard&Shiny 5gx6xx72', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(444, 'FG0026-GWX5x6', 'Grips Wax Xtreme Hard&Mat 5gx6x72', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(445, 'FG0027-GWM5x1', 'Grips Wax Hard&Mat 5gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(446, 'FG0028-GWS5x1', 'Grips Wax Hard&Shiny 5gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(447, 'FG0029-GWX5x1', 'Grips Wax Xtreme Hard&Mat 5gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(448, 'FG0030-GWM75', 'Grips Wax Hard&Mat 75gx36', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(449, 'FG0031-GWS75', 'Grips Wax Hard&Shiny 75gx36', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(450, 'FG0032-GWX75', 'Grips Wax Xtreme Hard&Mat 75gx36', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(451, 'FG0034-GCLAY5x1', 'Grips Clay 5gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(452, 'FG0045-GCLAY25x48', 'Grips Clay 25gx48', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(453, 'FG0046-GWHM25x48', 'Grips Wax Hard&Mat 25gx48', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(454, 'FG0047-GWS25x48', 'Grips Wax Hard&Shiny 25gx48', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(455, 'FG0048-GWX25x48', 'Grips Wax Xtreme Hard&Mat 25gx48', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(456, 'FG0049-GCLAY5x6x72', 'Grips Clay 5gx6x72', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(457, 'FG0050-GCLAY75x36', 'Grips Hair Clay 75gx36', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(458, 'FG0052-GWM75stkr', 'Grips Wax Mat(stkr)75gx36', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(459, 'FG0053-GWS75stkr', 'Grips Wax Shiny(Stkr)75gx36', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(460, 'FG0054-GWX75stkr', 'Grips Wax Xtreme(stkr) 75gx36', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(461, 'FG0055-GWMstkr25x48', 'Grips Wax Mat(stkr) 25gx48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(462, 'FG0056-GWSstkr25x48', 'Grips Wax Shiny(stkr) 25gx48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(463, 'FG0057-GWXstkr25x48', 'Grips Wax Xtreme(stkr) 25gx48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(464, 'FG0058-GGAsfly14x48', 'Grips Gel Assorted (flier) 14gx12x48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(465, 'FG0067-GWM5x12x2', 'Grips Hair Wax Hard and Mat 5g Promo 12+2', 27, 'PCS', 504, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(466, 'FG0101-TITANIUM75x36', 'Grips Hair Clay Titanium 75gx36', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(467, 'FG0120-GPOMADE75', 'Grips Pomade 75gx36', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(468, 'FG0121-GPOMADE5x6', 'Grips Pomade 5gx6x72', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(469, 'FG0185-KPSS22x12', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(470, 'FG0226-GWM5x24+KGOL', 'Promo Grips Wax Hard&Mat 5gx24+KplusGoldx6', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(471, 'FG0239-SANGELRED60', 'AlcoPlus Hand Sanitizer Gel with Moisturizer 60mL', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(472, 'FG0240-SANGELRED150', 'AlcoPlus Hand Sanitizer Gel with Moisturizer 150mL', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(473, 'FG0241-SANGELBLUE60', 'AlcoPlus Hand Sanitizer Gel with Vit E Beads 60mL', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(474, 'FG0242-SANGELBLUE150', 'Sanitizer Gel Blue 150mL', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(475, 'FG0264-ETYL70CLS150', 'AlcoPlus Ethyl 70 Alcohol Classic 150mL', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(476, 'FG0265-ETYL70CLS250', 'AlcoPlus Ethyl 70 Alcohol Classic 250mL', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(477, 'FG0266-ETYL70CLS500', 'AlcoPlus Ethyl 70 Alcohol Classic 500mL', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(478, 'FG0267-APISO70CLS150', 'AlcoPlus Iso 70 Alcohol Classic 150mL', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(479, 'FG0268-APISO70CLS250', 'AlcoPlus Iso 70 Alcohol Classic 250mL', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(480, 'FG0269-APISO70CLS500', 'AlcoPlus Iso 70 Alcohol Classic 500mL', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(481, 'FG0270-APADVANCE40', 'AlcoPlus Advance Antibacterial Sanitizer 40mL', 27, 'PCS', 36, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(482, 'FG0271-APADVANCE250', 'AlcoPlus Advance Antibacterial Sanitizer 250mL', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(483, 'FG0272-APADVANCE500', 'AlcoPlus Advance Antibacterial Sanitizer 500mL', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(484, 'FG0273-KPSS22x1', 'Keratin Plus Shampoo Daily Nourishing 22mlx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(485, 'FG0275-APADVANCEGAL', 'AlcoPlus Advance Antibacterial Sanitizer 3785mL', 27, 'PCS', 4, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(486, 'FG0362-ADVANCPROMO50', 'AP Advance 500mL+40mL Promo', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(487, 'FG0368-KSHAMP200', 'Keratin Plus Shampoo Daily Nourishing 200mlx24', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(488, 'FG0385-ETYL250+2KGLD', 'AP Ethyl 70 Alcohol Classic 250mL+1K Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(489, 'FG0386-ISO250+2KGLD', 'AP Isopropyl 70 Alcohol Classic 250mL+1K Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(490, 'FG0388-ETYL150+1KGLD', 'AP Ethyl 70 Alcohol Classic 150mL+1K Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(491, 'FG0389-ISO150+1KGLD', 'AP Isopropyl 70 Alcohol Classic 150mL+1K Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(492, 'FG0390-ETYL500+2KGLD', 'AP Ethyl 70 Alcohol Classic 500mL+2K Gold', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(493, 'FG0391-ISO500+2KGLD', 'AP Isopropyl 70 Alcohol Classic 500mL+2K Gold', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(494, 'FG0395-GGASOR12', 'Grips Gel Assorted 12gx12x48', 27, 'PCS', 576, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(495, 'FG0424-KSHAM200+2GLD', 'Keratin Shampoo 200mL+2 Keratin Gold Promo', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(496, 'FG0425-EMPSHAx6+3GLD', 'Empress Shampoo 21mL x 6+3 Empress Gold Promo', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(497, 'FG0540-GGEL12FLYER', 'Grips Gel Assorted 12gx1 Flyer', 27, 'PCS', 576, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(498, 'FG0541-GCLAY5FLYER', 'Grips Clay 5gx1 Flyer', 27, 'PCS', 400, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(499, 'FG0542-GWSHINY5FLYER', 'Grips Wax Shiny 5gx1 Flyer', 27, 'PCS', 400, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(500, 'FG0543-GWMAT5FLYER', 'Grips Wax Mat 5gx1 Flyer', 27, 'PCS', 400, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(501, 'FG0544-GWMAT5FLYER', 'Grips Wax Mat 5gx1 Flyer', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(502, 'FG0551-MAT5X2SAMPLER', 'Sampler- Wax Hard Mat x2(for Tie up)', 27, 'PCS', 1280, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(503, 'FG0552-CLAY6+1PROMO', 'Promo- Grips Hair Clay 5gx72 packs (6+1)', 27, 'PCS', 504, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(504, 'FG0554-KSHAM200+2LUX', 'Keratin Shampoo 200mL + 2 Lux PROMO', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(505, 'FG0555-KSHAMPOO22x2', 'Sampler Keratin Daily Nourishing 22ml x 2 (Tie Up)', 27, 'PCS', 500, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(506, 'FG0558-SHINY5x2SMPLR', 'Sampler- Wax Hard and Shiny x 2 (Tie up)', 27, 'PCS', 1280, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(507, 'FG0559-ADV250+2KGOLD', 'Alcoplus Advance 250mL+2 Keratin Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(508, 'FG0560-WAXMAT5g12+2', 'Promo- Grips Wax Hard n Mat 5g 12+2 Grips HM', 27, 'PCS', 504, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(509, 'FG0561-WAXEHM5g12+2', 'Promo- Grips Wax Extreme HM5g 12+2 Grips Extreme', 27, 'PCS', 504, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(510, 'FG0562-KERSHAMPFLYER', 'Sampler -Keratin Shampoo Daily Nourishing Flyer', 27, 'PCS', 250, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(511, 'FG0569-GCLAY5x2', 'Sampler- Grips Hair Clay 5gx2', 27, 'PCS', 500, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(512, 'FG0571-EMPSHAFLYERx1', 'Sampler- Empress Shampoo Long n Healthy x1 Flyer', 27, 'PCS', 400, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(513, 'FG0572-POMADE5FLYRx1', 'Sampler- Grips Pomade x1 Flyer', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(514, 'FG0574-POMADE5x1', 'Sampler - Grips Pomade x1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(515, 'FG0609-BLAFALCON15', 'BareLab Hair Conditioner 15mL x12s x24', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(516, 'FG0611-BLKERTRCON20', 'BareLab Keratin Intense Deluxe 20gx12sx24', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(517, 'FG0646-BLKERTRCON20', 'BareLab Keratin Intense Deluxe 20gx12sx24', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(518, 'FG0647-BLSHAMnCON15', 'Barelab Shampoo n Conditioner 15mLx12sx24pck', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(519, 'FG0648-BLANTDNFSHA12', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(520, 'FG0015-GGC50', 'Grips Gel (Clear) 60gx72', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(521, 'FG0016-GGY50', 'Grips Gel (Yellow) 60gx72', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(522, 'FG0017-GGG50', 'Grips Gel (Green) 60gx72', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(523, 'FG0018-GGC130', 'Grips Gel (Clear) 130gx48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(524, 'FG0019-GGY130', 'Grips Gel (Yellow) 130gx48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(525, 'FG0020-GGG130', 'Grips Gel (Green) 130gx48', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 0, 0, '2026-07-08 07:36:25'),
(526, 'FG0033-GCLAY5x6', 'Grips Clay 5gx6x36', 27, 'PCS', 432, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(527, 'FG0051-GWM75x1', 'Grips Wax Hard&Mat 75gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(528, 'FG0060-GWS75x1', 'Grips Wax Hard&Shiny 75gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(529, 'FG0061 -GWX75x1', 'Grips Wax Xtreme Hard & mat 75gx1', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(530, 'FG0277-ADVNCPROMO500', 'AP Advance 500mL+40mL refill Promo', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(531, 'FG0362-ADVNCPROMO500', 'AP Advance 500mL+ 40mL Promo', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(532, 'FG0366-ISOPRO500+KGC', 'AP Iso Classic 500ml with Free KGold Conditioner', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(533, 'FG0367-ETHPRO500+KRC', 'AP Ethyl Classic 500ml with Free KRed Conditioner ', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(534, 'FG0387-ADV250+2KGLD', 'AP 70% Alcohol Classic 250mL + 1 K Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(535, 'FG0390-ETYL500+1KGLD', 'AP Ethyl 70% Alcohol Classic 500mL + 2 K Gold', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(536, 'FG0391-ISO500+1KGLD', 'AP Isopropyl 70% Alcohol Classic 500mL + 2 K Gold', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(537, 'FG0399-GGC12', 'Grips Gel Clear 12g', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(538, 'FG0400-GGY12', 'Grips Gel Yellow 12g', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(539, 'FG0401-GGG12', 'Grips Gel Green 12g', 27, 'PCS', NULL, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(540, 'FG0402-ADV500+2KGCON', 'AP Advance 500mL + 2 K Gold Promo', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(541, 'FG0544-GWXTREM5FLYER', 'Grips Wax Xtreme 5g x 1 Flyer', 27, 'PCS', 400, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(542, 'FG0556-KSHA+KGDB12F2', 'Keratin Shampoo + Keratin Gold Promo Buy 12 Free 2', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(543, 'FG0610-BLADANSHAM12', 'BareLab Anti-dandruff Shampoo 12mL x 12s x 24 packs', 27, 'PCS', 288, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(544, 'FG0644-ETYL500+2KER', 'Promo Ethyl Classic 500mL + 2 Keratin Shampoo 22mL', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(545, 'FG0645-ISO500+2KER', 'Promo Isopropyl Classic 500mL + 2 Keratin Shampoo 22mL', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(546, 'FG0663-EMPSINGX1FLY', 'Sampler - Empress Shampoo LongnHealthy Single x1 FLY', 27, 'PCS', 500, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(547, 'FG0668-ADV250+1KGOLD', 'Promo - Alcoplus Advance 250ml + 1Keratin Gold', 27, 'PCS', 48, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25'),
(548, 'FG0669-KDNBOT1GET50', 'Promo - Keratin DN 200mL Buy 1 Get 2nd at 50%', 27, 'PCS', 24, NULL, 0.00, '2026-07-08 15:36:25', 1, 0, '2026-07-08 07:36:25');

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
(5, 55, 'Grips Gel (Clear) 12g', '12g', 0.00, 0.00, 1.05, 'vat', 0, 0, '2026-07-03 16:15:56', '2026-07-06 07:44:19'),
(6, 56, 'Grips Gel (Yellow) 12g', '12g', 0.00, 0.00, 1.05, 'vat', 0, 0, '2026-07-03 16:25:44', '2026-07-06 07:44:30'),
(7, 58, 'Grips Gel (Green) 12g', '12g', 0.00, 0.00, 1.05, 'vat', 0, 0, '2026-07-03 16:28:57', '2026-07-06 07:44:55'),
(8, 60, 'Grips Gel Assorted 12gx12x48', '12g', 13.20, 0.00, 1.10, 'vat', 0, 0, '2026-07-03 16:35:29', '2026-07-06 07:45:03'),
(9, 28, 'Pure Basic EDT Romantic 60ml', '60ml', 0.00, 0.00, 27.84, 'vat', 1, 0, '2026-07-03 17:11:13', '2026-07-03 09:11:13'),
(11, 17, 'Keratin Plus Shampoo Daily Nourishing 22mlx6', '22mL', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-03 17:13:45', '2026-07-03 09:13:45'),
(12, 27, 'Pure Basic EDT Sunny 60ml', '60mL', 0.00, 0.00, 12.60, 'vat', 1, 0, '2026-07-03 17:16:33', '2026-07-03 09:16:33'),
(13, 29, 'Pure Basic EDT Fresh 60ml', '60mL', 0.00, 0.00, 29.90, 'vat', 1, 0, '2026-07-03 17:18:08', '2026-07-03 09:18:08'),
(14, 43, 'Pure Basic EDT Active 60ml', '60mL', 0.00, 0.00, 12.60, 'vat', 1, 0, '2026-07-03 17:18:53', '2026-07-03 09:18:53'),
(15, 44, 'Pure Basic EDT Cool 60ml', '60mL', 0.00, 0.00, 27.65, 'vat', 1, 0, '2026-07-03 17:19:44', '2026-07-03 09:19:44'),
(16, 45, 'Pure Basic EDT Aqua 60ml', '60mL', 0.00, 0.00, 29.90, 'vat', 1, 0, '2026-07-03 17:20:37', '2026-07-03 09:20:37'),
(17, 54, 'Keratin Plus Shampoo Soft Smooth 22mlx11+1 Promo', '22mL', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-03 17:28:49', '2026-07-03 09:28:49'),
(18, 18, 'Empress Shampoo x 12', '21mL', 0.00, 0.00, 2.50, 'vat', 1, 0, '2026-07-03 17:30:19', '2026-07-03 09:30:19'),
(19, 19, 'Empress Shampoo x 6', '21mL', 0.00, 0.00, 2.50, 'vat', 1, 0, '2026-07-03 17:31:12', '2026-07-03 09:31:12'),
(20, 30, 'Empress Shampoo x 1', '21mL', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-03 17:32:03', '2026-07-03 09:32:03'),
(21, 31, 'Keratin Shampoo 12+2 Keratin Gold Promo', '21mL', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-03 17:35:08', '2026-07-03 09:35:08'),
(22, 32, 'Empress Shampoo 12+2 Keratin Gold Promo', '21mL', 0.00, 0.00, 2.50, 'vat', 1, 0, '2026-07-03 17:36:02', '2026-07-03 09:36:02'),
(23, 36, 'Keratin Shampoo Daily Nourishing 12+2 Keratin DN', '', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-03 17:41:03', '2026-07-03 09:41:03'),
(24, 37, 'Empress Shampoo Long n Healthy x4', '21mL', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-03 17:41:54', '2026-07-03 09:41:54'),
(25, 236, 'GC Antibacterial Hand Soap Floral Care 500mL x 6', '500mL', 0.00, 0.00, 5.49, 'vat', 1, 0, '2026-07-04 12:51:52', '2026-07-04 04:51:52'),
(26, 239, 'GC Antibacterial Hand Soap Citrus Clean 500mL x 6', '500mL', 0.00, 0.00, 5.49, 'vat', 1, 0, '2026-07-04 12:52:33', '2026-07-04 04:52:33'),
(27, 241, 'GC Antibacterial Hand Soap Floral Care 450mL x 12', '450mL', 0.00, 0.00, 6.38, 'vat', 1, 0, '2026-07-04 12:53:05', '2026-07-04 04:53:05'),
(28, 242, 'GC Antibacterial Hand Soap Citrus Clean 450ml x 12', '450mL', 0.00, 0.00, 6.38, 'vat', 1, 0, '2026-07-04 12:53:34', '2026-07-04 04:53:34'),
(29, 243, 'GC Antibacterial Hand Soap Citrus Clean 225mL x 12', '225mL', 0.00, 0.00, 5.24, 'vat', 0, 0, '2026-07-04 12:54:00', '2026-07-06 08:28:36'),
(30, 110, 'Laybare Soothing Cream 300ml', '300mL', 0.00, 0.00, 169.00, 'vat', 1, 0, '2026-07-04 12:55:21', '2026-07-04 04:55:21'),
(31, 116, 'Laybare Soothing Cream 30ml', '30mL', 0.00, 0.00, 44.50, 'vat', 1, 0, '2026-07-04 12:55:49', '2026-07-04 04:55:49'),
(32, 113, 'Laybare Exfoliating Cream 30g', '30g', 0.00, 0.00, 66.20, 'vat', 1, 0, '2026-07-04 12:56:25', '2026-07-04 04:56:25'),
(33, 114, 'Laybare Soothing Cream 100ml', '100ml', 0.00, 0.00, 76.70, 'vat', 1, 0, '2026-07-04 12:57:21', '2026-07-04 04:57:21'),
(34, 118, 'Printed Tube with cap Soothing cream 30ml(w/cost)', '30mL', 0.00, 0.00, 14.50, 'vat', 1, 0, '2026-07-04 12:57:51', '2026-07-04 04:57:51'),
(35, 119, 'Printed Tube w/cap Soothing cream 100ml (w/cost)', '100mL', 0.00, 0.00, 19.60, 'vat', 1, 0, '2026-07-04 12:58:14', '2026-07-04 04:58:14'),
(36, 81, 'Dwellbeing Lemongrass Liquid Hand Soap Gallon', '', 0.00, 0.00, 290.00, 'vat', 0, 0, '2026-07-04 13:00:19', '2026-07-06 08:23:52'),
(37, 84, 'Dwellbeing Lemongrass Sanitizer Gallon', '', 0.00, 0.00, 485.00, 'vat', 0, 0, '2026-07-04 13:00:41', '2026-07-06 08:25:42'),
(38, 87, 'Dwellbeing Grapefruit Liquid Hand Soap Gallon', '', 0.00, 0.00, 295.00, 'vat', 0, 0, '2026-07-04 13:01:44', '2026-07-06 08:23:36'),
(39, 89, 'Dwellbeing Sampaguita n Orange Liquid Hand Soap Gal', '', 0.00, 0.00, 350.00, 'vat', 0, 0, '2026-07-04 13:02:30', '2026-07-06 08:25:59'),
(40, 92, 'Everything Clean All-Natural Sanitizer Gallon', '', 0.00, 0.00, 491.00, 'vat', 1, 0, '2026-07-04 13:02:58', '2026-07-04 05:02:58'),
(41, 94, 'Everything Clean All-Natural Sanitizer 750ml', '750mL', 0.00, 0.00, 102.00, 'vat', 1, 0, '2026-07-04 13:03:59', '2026-07-04 05:03:59'),
(42, 95, 'Everything Clean All-Natural Sanitizer 375ml', '375mL', 0.00, 0.00, 54.00, 'vat', 1, 0, '2026-07-04 13:04:56', '2026-07-04 05:04:56'),
(43, 97, 'Hand Soap Lemongrass Gallon', '', 0.00, 0.00, 290.00, 'vat', 1, 0, '2026-07-04 13:05:20', '2026-07-04 05:05:20'),
(44, 98, 'Hand Soap Lemongrass 750ml', '750ml', 0.00, 0.00, 64.00, 'vat', 1, 0, '2026-07-04 13:06:07', '2026-07-04 05:06:07'),
(45, 100, 'Hand Soap Lemongrass 375ml', '375ml', 0.00, 0.00, 34.00, 'vat', 1, 0, '2026-07-04 13:06:33', '2026-07-04 05:06:33'),
(46, 101, 'Hand Soap Grapefruit Gallon', '', 0.00, 0.00, 310.00, 'vat', 1, 0, '2026-07-04 13:07:06', '2026-07-04 05:07:06'),
(47, 103, 'Hand Soap Grapefruit 750ml', '750ml', 0.00, 0.00, 65.00, 'vat', 1, 0, '2026-07-04 13:09:37', '2026-07-04 05:09:37'),
(48, 104, 'Hand Soap Grapefruit 375ml', '375ml', 0.00, 0.00, 36.00, 'vat', 1, 0, '2026-07-04 13:11:52', '2026-07-04 05:11:52'),
(49, 105, 'Flora Filipinas All-Natural Hand Soap Gallon', '', 0.00, 0.00, 350.00, 'vat', 1, 0, '2026-07-04 13:12:13', '2026-07-04 05:12:13'),
(50, 106, 'Flora Filipinas All-Natural Hand Soap 750ml', '750ml', 0.00, 0.00, 70.00, 'vat', 1, 0, '2026-07-04 13:12:41', '2026-07-04 05:12:41'),
(51, 107, 'Flora Filipinas All-Natural Hand Soap 375ml', '375ml', 0.00, 0.00, 44.00, 'vat', 1, 0, '2026-07-04 13:13:03', '2026-07-04 05:13:03'),
(52, 120, 'Desk and Workspace Cleaner 50ml', '50ml', 0.00, 0.00, 13.44, 'vat', 0, 0, '2026-07-04 13:14:58', '2026-07-06 08:23:18'),
(53, 121, 'Odor Absorber Spray 50ml', '50ml', 0.00, 0.00, 12.21, 'vat', 0, 0, '2026-07-04 13:15:25', '2026-07-06 08:35:23'),
(54, 122, 'The Little Warrior Bergamot 50ml', '50ml', 0.00, 0.00, 45.92, 'vat', 0, 0, '2026-07-04 13:15:48', '2026-07-06 07:53:52'),
(55, 123, 'The Little Warrior Chamomile 50ml', '50ml', 0.00, 0.00, 46.93, 'vat', 0, 0, '2026-07-04 13:16:12', '2026-07-06 07:54:17'),
(56, 124, 'The Little Warrior Green Tea 50ml', '50ml', 0.00, 0.00, 46.48, 'vat', 0, 0, '2026-07-04 13:16:45', '2026-07-06 07:54:28'),
(57, 126, 'Messy Bessy Pocket Sanitizer 50ml', '50ml', 0.00, 0.00, 19.49, 'vat', 0, 0, '2026-07-04 13:17:52', '2026-07-06 07:54:44'),
(58, 132, 'Potty Disinfectant Spray 50ml', '50ml', 0.00, 0.00, 20.72, 'vat', 0, 0, '2026-07-04 13:18:45', '2026-07-06 08:35:30'),
(59, 133, 'CS Be Poolite Deodorizer Spray30', '', 0.00, 0.00, 15.20, 'vat', 0, 0, '2026-07-04 13:19:44', '2026-07-06 08:21:04'),
(60, 135, 'Messy Baby Dish and Bottle Cleaner 500ml', '500ml', 0.00, 0.00, 49.28, 'vat', 0, 0, '2026-07-04 13:20:17', '2026-07-06 08:00:13'),
(61, 137, 'Messy Baby Liquid Laundry Detergent Cham/Lav 975', '975ml', 0.00, 0.00, 111.44, 'vat', 0, 0, '2026-07-04 13:21:27', '2026-07-06 08:00:33'),
(62, 139, 'Natural Dish Cleaner Aloe Green Tea 500ml', '500ml', 0.00, 0.00, 66.64, 'vat', 0, 0, '2026-07-04 13:22:26', '2026-07-06 08:00:45'),
(63, 140, 'Natural Dish Cleaner Aloe Green Tea 2000ml', '2000ml', 0.00, 0.00, 212.80, 'vat', 0, 0, '2026-07-04 13:22:59', '2026-07-06 08:00:55'),
(64, 142, 'Natural Dish Cleaner Kiwi Lemon 500ml', '500ml', 0.00, 0.00, 81.76, 'vat', 0, 0, '2026-07-04 13:23:28', '2026-07-06 08:01:05'),
(65, 144, 'Natural Dish Cleaner Kiwi Lemon 2000ml', '2000ml', 0.00, 0.00, 250.88, 'vat', 0, 0, '2026-07-04 13:25:05', '2026-07-06 08:01:17'),
(66, 147, 'Natural Liquid Laundry Detergent Grapefruit 975ml', '975ml', 0.00, 0.00, 109.76, 'vat', 0, 0, '2026-07-04 13:25:46', '2026-07-06 08:01:27'),
(67, 149, 'Natural Liquid Laundry Detergent Lavender 975ml', '975ml', 0.00, 0.00, 109.76, 'vat', 0, 0, '2026-07-04 13:26:25', '2026-07-06 08:02:22'),
(68, 151, 'Squeaky Clean Glass Cleaner 250ml', '250ml', 0.00, 0.00, 53.76, 'vat', 0, 0, '2026-07-04 13:26:50', '2026-07-06 08:02:42'),
(69, 152, 'The Little Warrior Bergamot 505ml', '505ml', 0.00, 0.00, 213.36, 'vat', 0, 0, '2026-07-04 13:27:38', '2026-07-06 08:02:53'),
(70, 153, 'The Little Warrior Chamomile 505ml', '505ml', 0.00, 0.00, 218.96, 'vat', 0, 0, '2026-07-04 13:28:00', '2026-07-06 08:03:01'),
(71, 155, 'The Little Warrior Green Tea 505ml', '505ml', 0.00, 0.00, 217.00, 'vat', 0, 0, '2026-07-04 13:28:29', '2026-07-06 08:03:10'),
(72, 156, 'Minty Orange Surface Cleaner 500ml', '500ml', 0.00, 0.00, 41.44, 'vat', 0, 0, '2026-07-04 13:29:46', '2026-07-06 08:03:19'),
(73, 158, 'Minty Orange Surface Cleaner 2000ml', '2000ml', 0.00, 0.00, 134.40, 'vat', 0, 0, '2026-07-04 13:30:39', '2026-07-06 08:03:28'),
(74, 159, 'Disinfectant Aroma Spray 500ml', '500ml', 0.00, 0.00, 49.84, 'vat', 0, 0, '2026-07-04 13:31:02', '2026-07-06 08:03:39'),
(75, 160, 'Disinfectant Aroma Spray 2000ml', '2000ml', 0.00, 0.00, 157.92, 'vat', 0, 0, '2026-07-04 13:31:36', '2026-07-06 08:07:48'),
(76, 161, 'Messy Bessy Fabric Freshener 200ml', '200ml', 0.00, 0.00, 37.52, 'vat', 0, 0, '2026-07-04 13:32:20', '2026-07-06 08:08:02'),
(77, 162, 'Hand and Body Wash Aloe Green Tea 540ml', '540ml', 0.00, 0.00, 168.00, 'vat', 0, 0, '2026-07-04 13:32:42', '2026-07-06 08:08:41'),
(78, 164, 'Hand and Body Wash Aloe Green Tea 2000 ml', '2000ml', 0.00, 0.00, 369.60, 'vat', 0, 0, '2026-07-04 13:33:15', '2026-07-06 08:09:02'),
(79, 165, 'Hand and Body Wash Kiwi Lemon 500ml', '500ml', 0.00, 0.00, 87.36, 'vat', 0, 0, '2026-07-04 13:33:52', '2026-07-06 08:09:19'),
(80, 166, 'Hand and Body Wash Kiwi Lemon 2000ml', '2000ml', 0.00, 0.00, 280.00, 'vat', 0, 0, '2026-07-04 13:34:18', '2026-07-06 08:09:30'),
(81, 167, 'Hand and Body Wash Ocean Rain 200ml', '200ml', 0.00, 0.00, 15.18, 'vat', 0, 0, '2026-07-04 13:34:49', '2026-07-06 08:09:39'),
(82, 169, 'Hand and Body Wash Ocean Rain 500 ml', '500ml', 0.00, 0.00, 173.04, 'vat', 0, 0, '2026-07-04 13:35:21', '2026-07-06 08:09:49'),
(83, 170, 'Hand and Body Wash Ocean Rain 2000ml', '2000ml', 0.00, 0.00, 394.80, 'vat', 0, 0, '2026-07-04 13:39:15', '2026-07-06 08:09:58'),
(84, 171, 'Messy Baby Bug Repellent Cologne 200ml', '200ml', 0.00, 0.00, 10.30, 'vat', 0, 0, '2026-07-04 13:39:38', '2026-07-06 08:10:08'),
(85, 263, 'Messy Baby Head to Toe Wash 500ml', '500ml', 0.00, 0.00, 179.76, 'vat', 0, 0, '2026-07-04 13:42:00', '2026-07-06 08:10:33'),
(86, 174, 'Messy Baby Toy and Surface Cleaner 200ml', '200ml', 0.00, 0.00, 23.52, 'vat', 0, 0, '2026-07-04 13:42:39', '2026-07-06 08:13:06'),
(87, 176, 'Messy Man Hair Face and Body Wash 500ml', '500ml', 0.00, 0.00, 92.96, 'vat', 0, 0, '2026-07-04 13:44:02', '2026-07-06 08:14:35'),
(88, 182, 'Messy Man Sports Spritzer 200ml', '200ml', 0.00, 0.00, 110.54, 'vat', 0, 0, '2026-07-04 13:45:39', '2026-07-06 08:15:19'),
(89, 183, 'Odor Absorber Spray 200ml', '200ml', 0.00, 0.00, 38.08, 'vat', 0, 0, '2026-07-04 13:46:03', '2026-07-06 08:15:28'),
(90, 184, 'Tea Tree Mold and Mildew 250ml', '250ml', 0.00, 0.00, 35.84, 'vat', 0, 0, '2026-07-04 13:46:39', '2026-07-06 08:16:00'),
(91, 186, 'Be Poolite Deodorizer Spray 50ml', '50ml', 0.00, 0.00, 21.84, 'vat', 0, 0, '2026-07-04 13:47:11', '2026-07-06 08:16:42'),
(92, 188, 'Be Poolite Deodorizer Spray 250ml', '250ml', 0.00, 0.00, 77.28, 'vat', 0, 0, '2026-07-04 13:48:36', '2026-07-06 08:16:55'),
(93, 189, 'Messy Man Hair Face and Body Wash 2000ml', '2000ml', 0.00, 0.00, 308.00, 'vat', 0, 0, '2026-07-04 13:49:00', '2026-07-06 08:17:12'),
(94, 190, 'Messy Bessy Hand Care Duo', '', 0.00, 0.00, 0.00, 'vat', 0, 0, '2026-07-04 13:49:32', '2026-07-06 08:17:30'),
(95, 191, 'Woody Wood Cleaner and Conditioner Spray 250ml', '250ml', 0.00, 0.00, 54.88, 'vat', 0, 0, '2026-07-04 13:50:08', '2026-07-06 08:17:50'),
(96, 192, 'Messy Baby Dish and Bottle Cleaner 2000ml', '2000ml', 0.00, 0.00, 151.20, 'vat', 0, 0, '2026-07-04 13:52:09', '2026-07-06 08:18:00'),
(97, 194, 'Messy Bessy Sport Spritzer Her 200ml', '200ml', 0.00, 0.00, 95.98, 'vat', 0, 0, '2026-07-04 13:53:12', '2026-07-06 08:18:12'),
(98, 198, 'CS Disinfectant Aroma Spray 50', '50ml', 0.00, 0.00, 14.34, 'vat', 0, 0, '2026-07-04 13:54:24', '2026-07-06 08:19:45'),
(99, 199, 'CS Desk Workspace Cleaner 50', '50ml', 0.00, 0.00, 8.90, 'vat', 0, 0, '2026-07-04 13:55:00', '2026-07-06 08:19:57'),
(100, 201, 'CS Potty Disinfectant Spray 50ml', '50ml', 0.00, 0.00, 16.46, 'vat', 0, 0, '2026-07-04 14:02:38', '2026-07-06 08:20:05'),
(101, 203, 'CS Hand Cream Kiwi 10g', '10g', 0.00, 0.00, 10.53, 'vat', 0, 0, '2026-07-04 14:03:08', '2026-07-06 08:20:16'),
(102, 204, 'Little Warrior Chamomile 50ml', '50ml', 0.00, 0.00, 15.01, 'vat', 0, 0, '2026-07-04 14:03:28', '2026-07-06 08:20:30'),
(103, 205, 'Little Warrior Green Tea 50ml', '50ml', 0.00, 0.00, 15.23, 'vat', 0, 0, '2026-07-04 14:03:44', '2026-07-06 08:34:43'),
(104, 207, 'CS Hand Body Wash Lavender 50ml', '50ml', 0.00, 0.00, 11.87, 'vat', 0, 0, '2026-07-04 14:04:00', '2026-07-06 08:22:01'),
(105, 209, 'CS Body Spray Lavender 50ml', '50ml', 0.00, 0.00, 7.78, 'vat', 0, 0, '2026-07-04 14:04:31', '2026-07-06 08:21:34'),
(106, 210, 'CS Hand Cream Lavender Dream 50g', '50g', 0.00, 0.00, 23.52, 'vat', 0, 0, '2026-07-04 14:04:51', '2026-07-06 08:22:43'),
(107, 212, 'CS Hand Body Wash Bamboo Fresh 50ml', '50ml', 0.00, 0.00, 14.00, 'vat', 0, 0, '2026-07-04 14:05:11', '2026-07-06 08:21:48'),
(108, 289, 'CS Body Spray Bamboo 50ml', '50ml', 0.00, 0.00, 9.52, 'vat', 0, 0, '2026-07-04 14:08:17', '2026-07-06 08:21:19'),
(109, 216, 'CS Hand Cream Bamboo Fresh 50g', '50g', 0.00, 0.00, 27.78, 'vat', 0, 0, '2026-07-04 14:08:58', '2026-07-06 08:22:14'),
(110, 218, 'CS Room and Linen Spray Bamboo Fresh 50', '50ml', 0.00, 0.00, 9.52, 'vat', 0, 0, '2026-07-04 14:09:45', '2026-07-06 08:23:00'),
(111, 219, 'CS Room and Linen Spray Lavander Dream 50', '50ml', 0.00, 0.00, 7.78, 'vat', 0, 0, '2026-07-04 14:10:08', '2026-07-06 08:23:03'),
(112, 221, 'hand Cream Kiwi 50g', '50g', 0.00, 0.00, 68.88, 'vat', 0, 0, '2026-07-04 14:10:35', '2026-07-06 08:30:29'),
(113, 233, 'Xylogel Oral Gel Bubblegum 25gx60', '25', 0.00, 0.00, 9.50, 'vat', 1, 0, '2026-07-07 13:52:16', '2026-07-07 05:52:16'),
(114, 294, 'TP Cherry Blossom Plant-based Hand Soap 500ml', '500', 0.00, 0.00, 70.81, 'vat', 1, 0, '2026-07-07 16:22:24', '2026-07-07 08:22:24'),
(115, 293, 'TP Green Tea Chamomile Liquid Hand Soap 500 Pouch', '500', 0.00, 0.00, 70.81, 'vat', 1, 0, '2026-07-07 16:24:07', '2026-07-07 08:24:07'),
(116, 60, 'Grips Gel Assorted 14gx12x48', '14', 0.00, 0.00, 13.20, 'vat', 1, 0, '2026-07-07 16:26:11', '2026-07-07 08:26:11'),
(117, 435, 'Grips Gel (Clear) 12g', '12g', 0.00, 0.00, 1.05, 'vat', 0, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(118, 436, 'Grips Gel (Yellow) 12g', '12g', 0.00, 0.00, 1.05, 'vat', 0, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(119, 437, 'Grips Gel (Green) 12g', '12g', 0.00, 0.00, 1.05, 'vat', 0, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(120, 438, 'Grips Gel Assorted 12gx12x48', '12g', 13.20, 0.00, 1.10, 'vat', 0, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(121, 416, 'Pure Basic EDT Romantic 60ml', '60ml', 0.00, 0.00, 27.84, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(122, 405, 'Keratin Plus Shampoo Daily Nourishing 22mlx6', '22mL', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(123, 415, 'Pure Basic EDT Sunny 60ml', '60mL', 0.00, 0.00, 12.60, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(124, 417, 'Pure Basic EDT Fresh 60ml', '60mL', 0.00, 0.00, 29.90, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(125, 429, 'Pure Basic EDT Active 60ml', '60mL', 0.00, 0.00, 12.60, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(126, 430, 'Pure Basic EDT Cool 60ml', '60mL', 0.00, 0.00, 27.65, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(127, 431, 'Pure Basic EDT Aqua 60ml', '60mL', 0.00, 0.00, 29.90, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(128, 434, 'Keratin Plus Shampoo Soft Smooth 22mlx11+1 Promo', '22mL', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(129, 406, 'Empress Shampoo x 12', '21mL', 0.00, 0.00, 2.50, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(130, 407, 'Empress Shampoo x 6', '21mL', 0.00, 0.00, 2.50, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(131, 418, 'Empress Shampoo x 1', '21mL', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(132, 419, 'Keratin Shampoo 12+2 Keratin Gold Promo', '21mL', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(133, 420, 'Empress Shampoo 12+2 Keratin Gold Promo', '21mL', 0.00, 0.00, 2.50, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(134, 424, 'Keratin Shampoo Daily Nourishing 12+2 Keratin DN', '', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(135, 425, 'Empress Shampoo Long n Healthy x4', '21mL', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(136, 438, 'Grips Gel Assorted 14gx12x48', '14', 0.00, 0.00, 13.20, 'vat', 1, 0, '2026-07-08 15:36:25', '2026-07-08 07:36:25'),
(148, 409, 'BareLab Sleek and Straight 15ml x 12s x 24packs', '15ml', 0.00, 0.00, 2.15, 'vat', 1, 0, '2026-07-10 09:20:39', '2026-07-10 01:20:39'),
(149, 515, 'BareLab Hair Conditioner 15mL x12s x24', '15ml', 0.00, 0.00, 2.15, 'vat', 1, 0, '2026-07-10 09:22:16', '2026-07-10 01:22:16'),
(150, 61, 'Grips Wax Hard&Mat 5gx12x36', '5g', 0.00, 0.00, 2.20, 'vat', 1, 0, '2026-07-10 09:25:34', '2026-07-10 01:25:34'),
(151, 62, 'Grips Wax Hard&Shiny 5gx12x36', '5g', 0.00, 0.00, 2.07, 'vat', 1, 0, '2026-07-10 09:27:17', '2026-07-10 01:27:17'),
(152, 63, 'Grips Wax Xtreme Mat 5Gx12x36', '5g', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-10 09:27:56', '2026-07-10 01:27:56'),
(153, 64, 'Grips Wax Hard&Mat 5gx6x72', '5g', 0.00, 0.00, 2.20, 'vat', 1, 0, '2026-07-10 09:28:29', '2026-07-10 01:28:29'),
(154, 65, 'Grips Wax Hard&Shiny 5gx6xx72', '5g', 0.00, 0.00, 2.07, 'vat', 1, 0, '2026-07-10 09:29:33', '2026-07-10 01:29:33'),
(155, 66, 'Grips Wax Xtreme Hard&Mat 5gx6x72', '5g', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-10 09:30:15', '2026-07-10 01:30:15'),
(156, 67, 'Grips Wax Hard&Mat 5gx1', '5g', 0.00, 0.00, 2.00, 'vat', 1, 0, '2026-07-10 09:33:19', '2026-07-10 01:33:19'),
(157, 68, 'Grips Wax Hard&Shiny 5gx1', '5g', 0.00, 0.00, 1.80, 'vat', 1, 0, '2026-07-10 09:33:41', '2026-07-10 01:33:41'),
(158, 69, 'Grips Wax Xtreme Hard&Mat 5gx1', '5g', 0.00, 0.00, 2.00, 'vat', 1, 0, '2026-07-10 09:34:06', '2026-07-10 01:34:06'),
(159, 70, 'Grips Wax Hard&Mat 75gx36', '75g', 0.00, 0.00, 33.00, 'vat', 1, 0, '2026-07-10 09:36:31', '2026-07-10 01:36:31'),
(160, 71, 'Grips Wax Hard&Shiny 75gx36', '75g', 0.00, 0.00, 27.55, 'vat', 1, 0, '2026-07-10 09:36:47', '2026-07-10 01:36:47'),
(161, 72, 'Grips Wax Xtreme Hard&Mat 75gx36', '75g', 0.00, 0.00, 33.50, 'vat', 1, 0, '2026-07-10 09:41:17', '2026-07-10 01:41:17'),
(162, 313, 'Grips Clay 5gx6x36', '5g', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-10 09:47:31', '2026-07-10 01:47:31'),
(163, 73, 'Grips Clay 5gx1', '', 0.00, 0.00, 2.25, 'vat', 1, 0, '2026-07-10 09:48:57', '2026-07-10 01:48:57'),
(164, 74, 'Grips Clay 25gx48', '', 0.00, 0.00, 16.25, 'vat', 1, 0, '2026-07-10 09:49:19', '2026-07-10 01:49:19'),
(165, 75, 'Grips Wax Hard&Mat 25gx48', '', 0.00, 0.00, 14.90, 'vat', 1, 0, '2026-07-10 09:49:39', '2026-07-10 01:49:39'),
(166, 76, 'Grips Wax Hard&Shiny 25gx48', '', 0.00, 0.00, 13.40, 'vat', 1, 0, '2026-07-10 09:49:55', '2026-07-10 01:49:55'),
(167, 77, 'Grips Wax Xtreme Hard&Mat 25gx48', '', 0.00, 0.00, 15.20, 'vat', 1, 0, '2026-07-10 09:50:44', '2026-07-10 01:50:44'),
(168, 78, 'Grips Clay 5gx6x72', '', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-10 09:51:01', '2026-07-10 01:51:01'),
(169, 79, 'Grips Hair Clay 75gx36', '', 0.00, 0.00, 35.90, 'vat', 1, 0, '2026-07-10 09:51:15', '2026-07-10 01:51:15'),
(170, 93, 'Grips Hair Clay Titanium 75gx36', '', 0.00, 0.00, 67.00, 'vat', 1, 0, '2026-07-10 09:52:49', '2026-07-10 01:52:49'),
(171, 96, 'Grips Pomade 75gx36', '', 0.00, 0.00, 50.00, 'vat', 1, 0, '2026-07-10 09:54:59', '2026-07-10 01:54:59'),
(172, 99, 'Grips Pomade 5gx6x72', '', 0.00, 0.00, 3.20, 'vat', 1, 0, '2026-07-10 09:55:15', '2026-07-10 01:55:15'),
(173, 108, 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '', 0.00, 0.00, 2.40, 'vat', 1, 0, '2026-07-10 09:55:39', '2026-07-10 01:55:39'),
(174, 131, 'AlcoPlus Ethyl 70 Alcohol Classic 150mL', '', 0.00, 0.00, 4.10, 'vat', 1, 0, '2026-07-10 10:13:15', '2026-07-10 02:13:15'),
(175, 136, 'AlcoPlus Ethyl 70 Alcohol Classic 250mL', '', 0.00, 0.00, 4.75, 'vat', 1, 0, '2026-07-10 10:13:55', '2026-07-10 02:13:55'),
(176, 138, 'AlcoPlus Ethyl 70 Alcohol Classic 500mL', '', 0.00, 0.00, 7.75, 'vat', 1, 0, '2026-07-10 10:14:11', '2026-07-10 02:14:11'),
(177, 141, 'AlcoPlus Iso 70 Alcohol Classic 150mL', '', 0.00, 0.00, 4.10, 'vat', 1, 0, '2026-07-10 10:14:28', '2026-07-10 02:14:28'),
(178, 143, 'AlcoPlus Iso 70 Alcohol Classic 250mL', '', 0.00, 0.00, 4.75, 'vat', 1, 0, '2026-07-10 10:17:22', '2026-07-10 02:17:22'),
(179, 145, 'AlcoPlus Iso 70 Alcohol Classic 500mL', '', 0.00, 0.00, 7.75, 'vat', 1, 0, '2026-07-10 10:21:01', '2026-07-10 02:21:01'),
(180, 146, 'AlcoPlus Advance Antibacterial Sanitizer 40mL', '', 0.00, 0.00, 4.40, 'vat', 1, 0, '2026-07-10 10:25:49', '2026-07-10 02:25:49'),
(181, 148, 'AlcoPlus Advance Antibacterial Sanitizer 250mL', '', 0.00, 0.00, 6.00, 'vat', 1, 0, '2026-07-10 10:27:12', '2026-07-10 02:27:12'),
(182, 150, 'AlcoPlus Advance Antibacterial Sanitizer 500mL', '', 0.00, 0.00, 9.30, 'vat', 1, 0, '2026-07-10 10:27:26', '2026-07-10 02:27:26'),
(183, 154, 'Keratin Plus Shampoo Daily Nourishing 22mlx1', '', 0.00, 0.00, 2.10, 'vat', 1, 0, '2026-07-10 10:27:48', '2026-07-10 02:27:48'),
(184, 157, 'AlcoPlus Advance Antibacterial Sanitizer 3785mL', '', 0.00, 0.00, 104.25, 'vat', 1, 0, '2026-07-10 10:40:11', '2026-07-10 02:40:11'),
(185, 319, 'AP Advance 500mL+40mL refill Promo', '', 0.00, 0.00, 9.30, 'vat', 1, 0, '2026-07-10 10:45:30', '2026-07-10 02:45:30'),
(186, 163, 'AP Advance 500mL+40mL Promo', '', 0.00, 0.00, 9.30, 'vat', 1, 0, '2026-07-10 10:46:12', '2026-07-10 02:46:12'),
(187, 331, 'AP Advance 500mL+ 40mL Promo', '', 0.00, 0.00, 9.30, 'vat', 1, 0, '2026-07-10 10:46:25', '2026-07-10 02:46:25'),
(188, 332, 'AP Iso Classic 500ml with Free KGold Conditioner', '', 0.00, 0.00, 7.75, 'vat', 1, 0, '2026-07-10 10:48:41', '2026-07-10 02:48:41'),
(189, 333, 'AP Ethyl Classic 500ml with Free KRed Conditioner ', '', 0.00, 0.00, 7.75, 'vat', 1, 0, '2026-07-10 10:49:04', '2026-07-10 02:49:04'),
(190, 168, 'Keratin Plus Shampoo Daily Nourishing 200mlx24', '', 0.00, 0.00, 25.80, 'vat', 1, 0, '2026-07-10 10:53:06', '2026-07-10 02:53:06');

-- --------------------------------------------------------

--
-- Table structure for table `production_history`
--

CREATE TABLE `production_history` (
  `history_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `poi_id` int(11) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `sts_ref` varchar(255) DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `mo_no` varchar(100) DEFAULT NULL,
  `material_type` varchar(100) DEFAULT NULL,
  `reject_status` varchar(100) DEFAULT NULL,
  `sts_remarks` text DEFAULT NULL,
  `pcs_per_case` int(11) DEFAULT NULL,
  `prepared_by_name` varchar(255) DEFAULT NULL,
  `checked_by_name` varchar(255) DEFAULT NULL,
  `received_by_name` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `edited_by` int(11) DEFAULT NULL,
  `previous_quantity` int(11) DEFAULT 0,
  `added_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_edited` datetime DEFAULT NULL,
  `old_lot_number` varchar(100) DEFAULT NULL,
  `old_added_quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_history`
--

INSERT INTO `production_history` (`history_id`, `po_id`, `poi_id`, `lot_number`, `item_description`, `sts_ref`, `shift`, `mo_no`, `material_type`, `reject_status`, `sts_remarks`, `pcs_per_case`, `prepared_by_name`, `checked_by_name`, `received_by_name`, `user_id`, `edited_by`, `previous_quantity`, `added_quantity`, `new_quantity`, `date_created`, `date_edited`, `old_lot_number`, `old_added_quantity`) VALUES
(35, 23, 28, '152-202', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 5472, 5472, '2026-07-09 13:30:15', NULL, NULL, NULL),
(36, 25, 30, '151-306', 'BareLab Sleek and Straight 13mlx12sx24packs', '15115', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 12672, 12672, '2026-07-09 13:31:53', NULL, NULL, NULL),
(37, 25, 32, '157-158', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', '15114', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 12672, 12672, '2026-07-09 13:32:53', NULL, NULL, NULL),
(38, 23, 28, '152-202', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 5472, 12672, 18144, '2026-07-09 13:33:51', NULL, NULL, NULL),
(39, 34, 67, '133-853', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 1440, 1440, '2026-07-09 13:52:05', NULL, NULL, NULL),
(40, 33, 66, '118-850', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 9216, 9216, '2026-07-09 13:52:33', NULL, NULL, NULL),
(41, 25, 31, '159-110', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15118', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 12672, 12672, '2026-07-09 13:56:37', NULL, NULL, NULL),
(42, 34, 67, '133-853', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 1440, 12672, 14112, '2026-07-09 15:28:25', NULL, NULL, NULL),
(43, 33, 66, '118-855', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 9216, 11808, 21024, '2026-07-09 15:49:19', NULL, NULL, NULL),
(44, 23, 28, '152-202', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 18144, 4608, 22752, '2026-07-09 16:00:05', NULL, NULL, NULL),
(45, 23, 28, '152-201', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 22752, 12672, 35424, '2026-07-09 16:00:28', NULL, NULL, NULL),
(46, 25, 30, '151-313', 'BareLab Sleek and Straight 13mlx12sx24packs', '15141', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 12672, 12672, 25344, '2026-07-09 16:20:24', NULL, NULL, NULL),
(47, 23, 28, '152-201', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15175', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 35424, 12672, 48096, '2026-07-10 06:50:48', NULL, NULL, NULL),
(48, 23, 28, '152-203', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15177', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 48096, 12672, 60768, '2026-07-10 06:52:32', NULL, NULL, NULL),
(49, 23, 28, '152-203', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15176', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 60768, 10944, 71712, '2026-07-10 06:53:06', NULL, NULL, NULL),
(50, 23, 28, '152-203', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15162', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 71712, 12672, 84384, '2026-07-10 06:53:51', NULL, NULL, NULL),
(51, 23, 28, '152-201', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15160', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 84384, 12672, 97056, '2026-07-10 06:54:22', NULL, NULL, NULL),
(52, 23, 28, '152-203', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15158', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 97056, 12672, 109728, '2026-07-10 06:54:50', NULL, NULL, NULL),
(53, 25, 31, '159-111', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15181', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 12672, 12672, 25344, '2026-07-10 06:55:39', NULL, NULL, NULL),
(54, 23, 28, '152-203', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15164', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 109728, 12672, 122400, '2026-07-10 06:56:19', NULL, NULL, NULL),
(55, 33, 66, '118-855', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15165', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 21024, 11232, 32256, '2026-07-10 06:56:59', NULL, NULL, NULL),
(57, 34, 67, '133-853', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', '15163', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 14112, 12672, 26784, '2026-07-10 06:58:18', NULL, NULL, NULL),
(58, 25, 31, '159-110', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15161', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 25344, 6624, 31968, '2026-07-10 06:58:51', NULL, NULL, NULL),
(59, 25, 30, '151-306', 'BareLab Sleek and Straight 13mlx12sx24packs', '15159', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 38016, 12672, 50688, '2026-07-10 06:59:24', NULL, NULL, NULL),
(60, 25, 31, '159-111', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15158', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 31968, 12672, 44640, '2026-07-10 06:59:54', NULL, NULL, NULL),
(61, 25, 50, '160-058', 'Barelab Shampoo n Conditioner 15mLx12sx24pck', '15154', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 12672, 12672, '2026-07-10 07:02:08', NULL, NULL, NULL),
(62, 25, 32, '157-158', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', '15153', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 12672, 12672, 25344, '2026-07-10 07:02:54', NULL, NULL, NULL),
(63, 25, 30, '151-313', 'BareLab Sleek and Straight 13mlx12sx24packs', '15179', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 50688, 5760, 56448, '2026-07-10 07:03:48', NULL, NULL, NULL),
(64, 25, 32, '157-159', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', '15191', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 25344, 12672, 38016, '2026-07-10 07:58:46', NULL, NULL, NULL),
(65, 25, 30, '151-307', 'BareLab Sleek and Straight 13mlx12sx24packs', '15192', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 56448, 12672, 69120, '2026-07-10 07:59:47', NULL, NULL, NULL),
(66, 33, 66, '118-850', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15193', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 32256, 12672, 44928, '2026-07-10 09:03:32', NULL, NULL, NULL),
(67, 23, 28, '152-201', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15195', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 122400, 10656, 133056, '2026-07-10 09:04:48', NULL, NULL, NULL),
(68, 23, 28, '152-203', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15196', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 133056, 12672, 145728, '2026-07-10 09:43:51', NULL, NULL, NULL),
(69, 33, 66, '188-856', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15199', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 44928, 12672, 57600, '2026-07-10 09:44:24', NULL, NULL, NULL),
(70, 34, 67, '133-853', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', '15203', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 26784, 6048, 32832, '2026-07-10 11:12:27', NULL, NULL, NULL),
(71, 33, 66, '118-856', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15200', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 57600, 12672, 70272, '2026-07-10 11:13:23', NULL, NULL, NULL),
(72, 25, 31, '159-111', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15204', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 44640, 6048, 50688, '2026-07-10 11:20:12', NULL, NULL, NULL),
(74, 25, 31, '159-109', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15042', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 63360, 5760, 69120, '2026-07-10 12:53:58', NULL, NULL, NULL),
(75, 25, 31, '159-110', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15118', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 69120, 12672, 81792, '2026-07-10 12:55:17', NULL, NULL, NULL),
(76, 25, 30, '151-306', 'BareLab Sleek and Straight 13mlx12sx24packs', '15169', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, 1, 69120, 7200, 76320, '2026-07-10 13:07:05', '2026-07-10 13:21:53', '151-306', 7200),
(77, 33, 66, '118-854', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15209', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 70272, 12672, 82944, '2026-07-10 13:15:33', NULL, NULL, NULL),
(78, 25, 32, '151-313', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', '15168', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 38016, 12962, 50978, '2026-07-10 17:06:17', NULL, NULL, NULL),
(79, 25, 31, '159-112', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15288', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 69120, 12672, 81792, '2026-07-11 06:24:01', NULL, NULL, NULL),
(80, 25, 30, '151-308', 'BareLab Sleek and Straight 13mlx12sx24packs', '15278', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 63648, 6336, 69984, '2026-07-11 06:25:08', NULL, NULL, NULL),
(81, 25, 30, '151-309', 'BareLab Sleek and Straight 13mlx12sx24packs', '15281', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 69984, 12672, 82656, '2026-07-11 06:26:36', NULL, NULL, NULL),
(82, 25, 37, '124-550', 'Empress Shampoo x 12', '15259', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 12672, 12672, '2026-07-11 06:27:27', NULL, NULL, NULL),
(83, 25, 30, '151-308', 'BareLab Sleek and Straight 13mlx12sx24packs', '15231', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 82656, 12672, 95328, '2026-07-11 06:30:58', NULL, NULL, NULL),
(84, 23, 28, '152-204', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15257', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 145728, 12672, 158400, '2026-07-11 06:31:34', NULL, NULL, NULL),
(85, 25, 30, '151-307', 'BareLab Sleek and Straight 13mlx12sx24packs', '15247', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 95328, 6912, 102240, '2026-07-11 06:33:08', NULL, NULL, NULL),
(86, 25, 37, '124-549', 'Empress Shampoo x 12', '15233', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 12672, 12672, 25344, '2026-07-11 06:33:46', NULL, NULL, NULL),
(87, 25, 30, '151-307', 'BareLab Sleek and Straight 13mlx12sx24packs', '15232', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 102240, 12672, 114912, '2026-07-11 06:34:28', NULL, NULL, NULL),
(88, 25, 30, '151-308', 'BareLab Sleek and Straight 13mlx12sx24packs', '15230', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 114912, 12672, 127584, '2026-07-11 06:34:58', NULL, NULL, NULL),
(89, 25, 31, '15229', 'BareLab Anti-Dandruff Shampoo 12mLx12sx24pck', '15229', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 81792, 12672, 94464, '2026-07-11 06:35:47', NULL, NULL, NULL),
(90, 23, 28, '152-204', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15225', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 158400, 12672, 171072, '2026-07-11 06:36:09', NULL, NULL, NULL),
(91, 25, 32, '157-159', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', '15223', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 50978, 12672, 63650, '2026-07-11 06:36:51', NULL, NULL, NULL),
(92, 23, 28, '152-204', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15224', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 171072, 12672, 183744, '2026-07-11 06:37:14', NULL, NULL, NULL),
(93, 33, 66, '118-856', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15235', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 82944, 12672, 95616, '2026-07-11 06:38:39', NULL, NULL, NULL),
(94, 34, 67, '133-854', 'Keratin Plus Shampoo Daily Nourishing 22mlx6', '15246', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 32832, 12672, 45504, '2026-07-11 06:41:46', NULL, NULL, NULL),
(95, 33, 66, '118-856', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15277', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 95616, 4032, 99648, '2026-07-11 06:45:18', NULL, NULL, NULL),
(96, 33, 66, '118-856', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15282', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 99648, 1032, 100680, '2026-07-11 06:46:00', NULL, NULL, NULL),
(97, 23, 28, '152-204', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15224', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 183744, 12672, 196416, '2026-07-11 06:58:52', NULL, NULL, NULL),
(98, 25, 32, '157-160', 'BareLab Anti-Hairfall Conditioner 13mLx12sx24', '15292', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 63650, 12672, 76322, '2026-07-11 07:05:59', NULL, NULL, NULL),
(99, 23, 28, '152-204', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', '15293', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 196416, 12384, 208800, '2026-07-11 07:26:09', NULL, NULL, NULL),
(100, 25, 51, '163-001', 'BareLab Keratin Intense Deluxe 180g', '15297', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 1140, 1140, '2026-07-11 08:46:02', NULL, NULL, NULL),
(101, 25, 37, '124-550', 'Empress Shampoo x 12', '15299', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 25344, 12672, 38016, '2026-07-11 08:46:55', NULL, NULL, NULL),
(102, 25, 30, '151-310', 'BareLab Sleek and Straight 13mlx12sx24packs', '15300', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 127584, 12672, 140256, '2026-07-11 08:47:20', NULL, NULL, NULL),
(103, 35, 68, '118-854', 'Keratin Plus Shampoo Daily Nourishing 22mlx12', '15302', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 100680, 12672, 113352, '2026-07-11 09:12:26', NULL, NULL, NULL),
(104, 23, 69, '152-204', 'Empress Shampoo x 1', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 0, 5256, 5256, '2026-07-11 09:39:01', NULL, NULL, NULL),
(105, 25, 37, '124-550', 'Empress Shampoo x 12', '15291', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 38016, 12672, 50688, '2026-07-11 10:28:14', NULL, NULL, NULL),
(106, 25, 37, '124-549', 'Empress Shampoo x 12', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, 1, 50688, 29664, 80352, '2026-07-11 10:34:55', '2026-07-11 10:45:16', '549', 29664),
(107, 25, 37, '124-550', 'Empress Shampoo x 12', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, 80352, 12672, 93024, '2026-07-11 10:51:53', NULL, NULL, NULL),
(108, 36, 70, '123-456', 'MB Hand & Body Lotion Aloe Green Tea 300mLx12', '123', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 0, 1999, 1999, '2026-07-13 10:06:04', NULL, NULL, NULL),
(109, 36, 71, '123-789', 'Dwellbeing Sampaguita n Orange Liquid Hand Soap Gal', '124', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 0, 1899, 1899, '2026-07-13 10:06:04', NULL, NULL, NULL),
(110, 36, 72, '123-101', 'Messy Bessy Hand & Body Wash Aloe Green Tea 50mL', '125', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 0, 1799, 1799, '2026-07-13 10:06:05', NULL, NULL, NULL),
(111, 36, 70, '123-456', 'MB Hand & Body Lotion Aloe Green Tea 300mLx12', '987', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 1999, 101, 2100, '2026-07-13 10:12:32', NULL, NULL, NULL),
(112, 36, 71, '123-789', 'Dwellbeing Sampaguita n Orange Liquid Hand Soap Gal', '654', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 1899, 101, 2000, '2026-07-13 10:12:33', NULL, NULL, NULL),
(113, 36, 71, '123-789', 'Dwellbeing Sampaguita n Orange Liquid Hand Soap Gal', '321', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 2000, 101, 2101, '2026-07-13 10:12:33', NULL, NULL, NULL),
(114, 26, 52, '194-168', 'AlcoPlus Iso 70 Alcohol Classic 150mL', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 0, 2000, 2000, '2026-07-14 08:17:13', NULL, NULL, NULL),
(115, 26, 53, '168-194', 'AlcoPlus Iso 70 Alcohol Classic 250mL', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, 0, 2000, 2000, '2026-07-14 08:17:13', NULL, NULL, NULL),
(116, 26, 52, '194-168', 'AlcoPlus Iso 70 Alcohol Classic 150mL', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 1, 2000, 2000, 4000, '2026-07-14 08:19:43', '2026-07-14 08:22:29', '168', 2000);

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
  `pcs_per_case` int(11) DEFAULT NULL,
  `lot_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `is_removed` tinyint(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_lots`
--

INSERT INTO `production_lots` (`lot_id`, `po_id`, `poi_id`, `lot_number`, `quantity_produced`, `pcs_per_case`, `lot_date`, `created_by`, `date_created`, `is_removed`, `last_update`) VALUES
(24, 23, 28, '152-202', 22752, NULL, '2026-07-09', 11, '2026-07-09 13:30:15', 0, '2026-07-09 08:00:05'),
(25, 25, 30, '151-306', 32544, NULL, '2026-07-09', 11, '2026-07-09 13:31:53', 0, '2026-07-10 05:07:05'),
(26, 25, 32, '157-158', 25344, NULL, '2026-07-09', 11, '2026-07-09 13:32:53', 0, '2026-07-09 23:02:54'),
(27, 34, 67, '133-853', 32832, NULL, '2026-07-09', 11, '2026-07-09 13:52:05', 0, '2026-07-10 03:12:27'),
(28, 33, 66, '118-850', 21888, NULL, '2026-07-09', 11, '2026-07-09 13:52:33', 0, '2026-07-10 01:03:32'),
(29, 25, 31, '159-110', 31968, NULL, '2026-07-09', 11, '2026-07-09 13:56:37', 0, '2026-07-10 04:55:17'),
(30, 33, 66, '118-855', 23040, NULL, '2026-07-09', 11, '2026-07-09 15:49:19', 0, '2026-07-09 22:56:59'),
(31, 23, 28, '152-201', 48672, NULL, '2026-07-09', 11, '2026-07-09 16:00:28', 0, '2026-07-10 01:04:48'),
(32, 25, 30, '151-313', 18432, NULL, '2026-07-09', 11, '2026-07-09 16:20:24', 0, '2026-07-10 07:44:23'),
(33, 23, 28, '152-203', 74304, NULL, '2026-07-10', 11, '2026-07-10 06:52:32', 0, '2026-07-10 01:43:51'),
(34, 25, 31, '159-111', 31392, NULL, '2026-07-10', 11, '2026-07-10 06:55:39', 0, '2026-07-10 03:20:12'),
(35, 25, 50, '160-058', 12672, NULL, '2026-07-10', 11, '2026-07-10 07:02:08', 0, '2026-07-09 23:02:08'),
(36, 25, 32, '157-159', 25344, NULL, '2026-07-10', 11, '2026-07-10 07:58:46', 0, '2026-07-10 22:36:51'),
(37, 25, 30, '151-307', 32256, NULL, '2026-07-10', 11, '2026-07-10 07:59:47', 0, '2026-07-10 22:34:28'),
(38, 33, 66, '188-856', 12672, NULL, '2026-07-10', 11, '2026-07-10 09:44:24', 0, '2026-07-10 01:44:24'),
(39, 33, 66, '118-856', 30408, NULL, '2026-07-10', 11, '2026-07-10 11:13:23', 0, '2026-07-10 22:46:00'),
(40, 25, 31, '159-109', 5760, NULL, '2026-07-10', 11, '2026-07-10 12:52:27', 0, '2026-07-10 07:43:32'),
(41, 33, 66, '118-854', 12672, NULL, '2026-07-10', 11, '2026-07-10 13:15:34', 0, '2026-07-10 05:15:34'),
(42, 25, 32, '151-313', 12962, NULL, '2026-07-10', 11, '2026-07-10 17:06:17', 0, '2026-07-10 09:06:17'),
(43, 25, 31, '159-112', 12672, NULL, '2026-07-11', 11, '2026-07-11 06:24:01', 0, '2026-07-10 22:24:01'),
(44, 25, 30, '151-308', 31680, NULL, '2026-07-11', 11, '2026-07-11 06:25:09', 0, '2026-07-10 22:34:58'),
(45, 25, 30, '151-309', 12672, NULL, '2026-07-11', 11, '2026-07-11 06:26:36', 0, '2026-07-10 22:26:36'),
(46, 25, 37, '124-550', 50688, NULL, '2026-07-11', 11, '2026-07-11 06:27:27', 0, '2026-07-11 02:51:53'),
(47, 23, 28, '152-204', 63072, NULL, '2026-07-11', 11, '2026-07-11 06:31:34', 0, '2026-07-10 23:26:09'),
(48, 25, 37, '124-549', 42336, NULL, '2026-07-11', 11, '2026-07-11 06:33:47', 0, '2026-07-14 00:33:09'),
(49, 25, 31, '15229', 12672, NULL, '2026-07-11', 11, '2026-07-11 06:35:47', 0, '2026-07-10 22:35:47'),
(50, 34, 67, '133-854', 12672, NULL, '2026-07-11', 11, '2026-07-11 06:41:46', 0, '2026-07-10 22:41:46'),
(51, 25, 32, '157-160', 12672, NULL, '2026-07-11', 11, '2026-07-11 07:05:59', 0, '2026-07-10 23:05:59'),
(52, 25, 51, '163-001', 1140, NULL, '2026-07-11', 11, '2026-07-11 08:46:02', 0, '2026-07-11 00:46:02'),
(53, 25, 30, '151-310', 12672, NULL, '2026-07-11', 11, '2026-07-11 08:47:20', 0, '2026-07-11 00:47:20'),
(54, 35, 68, '118-854', 12672, NULL, '2026-07-11', 11, '2026-07-11 09:12:26', 0, '2026-07-11 01:12:26'),
(55, 23, 69, '152-204', 5256, NULL, '2026-07-11', 11, '2026-07-11 09:39:01', 0, '2026-07-11 01:39:01'),
(56, 25, 37, '124-549', 29664, NULL, '2026-07-11', 11, '2026-07-11 10:34:55', 1, '2026-07-14 00:33:09'),
(57, 36, 70, '123-456', 2100, NULL, '2026-07-13', 6, '2026-07-13 10:06:04', 0, '2026-07-13 02:12:32'),
(58, 36, 71, '123-789', 2101, NULL, '2026-07-13', 6, '2026-07-13 10:06:04', 0, '2026-07-13 02:12:33'),
(59, 36, 72, '123-101', 1799, NULL, '2026-07-13', 6, '2026-07-13 10:06:05', 0, '2026-07-13 02:06:05'),
(60, 26, 52, '194-168', 2000, NULL, '2026-07-14', 6, '2026-07-14 08:17:13', 0, '2026-07-14 00:17:13'),
(61, 26, 53, '168-194', 2000, NULL, '2026-07-14', 6, '2026-07-14 08:17:13', 0, '2026-07-14 00:17:13'),
(62, 26, 52, '194-168', 2000, NULL, '2026-07-14', 6, '2026-07-14 08:19:43', 0, '2026-07-14 00:22:29'),
(63, 36, 70, 'lotnegative', 0, NULL, '2026-07-16', 6, '2026-07-16 13:31:58', 1, '2026-07-17 03:21:12'),
(64, 36, 70, '10', 0, NULL, '2026-07-17', 6, '2026-07-17 11:55:36', 1, '2026-07-17 03:56:43');

-- --------------------------------------------------------

--
-- Table structure for table `production_reports`
--

CREATE TABLE `production_reports` (
  `report_id` int(11) NOT NULL,
  `history_id` int(11) NOT NULL,
  `poi_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `old_lot_number` varchar(100) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `report_type` enum('lot_number','quantity') DEFAULT 'lot_number',
  `status` enum('pending','resolved') DEFAULT 'pending',
  `resolved_by` int(11) DEFAULT NULL,
  `new_lot_number` varchar(100) DEFAULT NULL,
  `date_reported` datetime DEFAULT current_timestamp(),
  `date_resolved` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_reports`
--

INSERT INTO `production_reports` (`report_id`, `history_id`, `poi_id`, `po_id`, `old_lot_number`, `reported_by`, `reason`, `report_type`, `status`, `resolved_by`, `new_lot_number`, `date_reported`, `date_resolved`) VALUES
(5, 76, 30, 25, '151-306', 11, 'Double input \r\n\r\nThank you', 'lot_number', 'resolved', NULL, '151-306', '2026-07-10 13:18:15', '2026-07-10 13:21:53');

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
(21, '1400027200', '2025-11-19', '', 15, 13, 'pending', '2026-07-07 12:01:19', '2026-07-07 06:56:26', 202473, 30, 'normal', 0, 0, 0),
(22, 'PO-00000063', '2026-07-01', '', 19, 13, 'pending', '2026-07-07 15:01:41', '2026-07-07 08:43:27', 1734000, 90, 'normal', 0, 0, 0),
(23, 'PO-00000062', '2026-07-01', '', 19, 13, 'pending', '2026-07-07 15:03:50', '2026-07-11 01:39:01', 1762560, 90, 'normal', 214056, 0, 0),
(25, 'PO-00000014', '2026-07-01', '', 19, 13, 'pending', '2026-07-07 16:36:26', '2026-07-11 03:50:40', 16069692, 90, 'normal', 417878, 283680, 0),
(26, 'PO-00000096', '2026-07-08', '', 19, 13, 'pending', '2026-07-09 08:11:51', '2026-07-17 03:56:15', 62400, 90, 'normal', 6000, 0, 0),
(27, 'SKI-CC-02743', '2026-07-08', '', 27, 13, 'pending', '2026-07-09 08:29:53', '2026-07-09 00:29:53', 168000, 90, 'normal', 0, 0, 0),
(28, '32626', '2026-05-06', '', 12, 13, 'pending', '2026-07-09 08:59:36', '2026-07-09 00:59:36', 1090, 30, 'normal', 0, 0, 0),
(29, '32627', '2026-05-06', '', 12, 13, 'pending', '2026-07-09 09:19:48', '2026-07-09 01:19:48', 1090, 30, 'normal', 0, 0, 0),
(30, '32628', '2026-05-06', '', 12, 13, 'pending', '2026-07-09 09:22:12', '2026-07-09 01:22:12', 1090, 30, 'normal', 0, 0, 0),
(31, '32629', '2026-05-06', '', 12, 13, 'pending', '2026-07-09 09:26:45', '2026-07-09 01:26:45', 1090, 30, 'normal', 0, 0, 0),
(32, '32630', '2026-05-06', '', 12, 13, 'pending', '2026-07-09 09:28:59', '2026-07-09 01:28:59', 108, 30, 'normal', 0, 0, 0),
(33, 'ADV253', '2026-07-01', '', 19, 12, 'pending', '2026-07-09 13:43:30', '2026-07-10 22:46:00', 576000, 90, 'advance', 100680, 0, 0),
(34, 'ADV252', '2026-07-01', '', 19, 12, 'pending', '2026-07-09 13:45:19', '2026-07-10 22:41:46', 432000, 90, 'advance', 45504, 0, 0),
(35, 'PO-00000111', '2026-07-08', '', 19, 12, 'pending', '2026-07-11 07:42:06', '2026-07-11 01:12:26', 1728000, 90, 'normal', 113352, 0, 0),
(36, 'TEST-0000001', '2026-07-13', '', 10, 4, 'pending', '2026-07-13 09:59:15', '2026-07-17 03:56:43', 7680, 30, 'normal', 6000, 6000, 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `poi_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_uom` varchar(50) NOT NULL DEFAULT 'PCS',
  `quantity` int(11) NOT NULL,
  `produced_quantity` int(11) DEFAULT 0 COMMENT 'Produced quantity per item',
  `delivered_quantity` int(11) DEFAULT 0 COMMENT 'Delivered quantity per item',
  `unit_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`poi_id`, `po_id`, `item_id`, `item_uom`, `quantity`, `produced_quantity`, `delivered_quantity`, `unit_price`) VALUES
(25, 21, 241, 'PCS', 133361, 0, 0, 0.00),
(26, 21, 242, 'PCS', 69112, 0, 0, 0.00),
(27, 22, 37, 'PCS', 1734000, 0, 0, 0.00),
(28, 23, 39, 'PCS', 1615680, 208800, 0, 0.00),
(30, 25, 24, 'PCS', 584352, 140256, 152928, 0.00),
(31, 25, 232, 'PCS', 352800, 94464, 50400, 0.00),
(32, 25, 25, 'PCS', 581472, 76322, 0, 0.00),
(33, 25, 230, 'PCS', 134784, 0, 0, 0.00),
(34, 25, 22, 'PCS', 219456, 0, 0, 0.00),
(35, 25, 181, 'PCS', 6359040, 0, 0, 0.00),
(36, 25, 78, 'PCS', 4447872, 0, 0, 0.00),
(37, 25, 18, 'PCS', 526464, 93024, 80352, 0.00),
(38, 25, 79, 'PCS', 21960, 0, 0, 0.00),
(39, 25, 74, 'PCS', 5136, 0, 0, 0.00),
(40, 25, 70, 'PCS', 1620, 0, 0, 0.00),
(41, 25, 62, 'PCS', 2087856, 0, 0, 0.00),
(42, 25, 72, 'PCS', 3744, 0, 0, 0.00),
(43, 25, 71, 'PCS', 3816, 0, 0, 0.00),
(44, 25, 93, 'PCS', 7056, 0, 0, 0.00),
(45, 25, 96, 'PCS', 10512, 0, 0, 0.00),
(46, 25, 141, 'PCS', 9456, 0, 0, 0.00),
(47, 25, 143, 'PCS', 28128, 0, 0, 0.00),
(48, 25, 145, 'PCS', 11784, 0, 0, 0.00),
(49, 25, 34, 'PCS', 404352, 0, 0, 0.00),
(50, 25, 231, 'PCS', 220032, 12672, 0, 0.00),
(51, 25, 26, 'PCS', 48000, 1140, 0, 0.00),
(52, 26, 141, 'PCS', 12000, 4000, 0, 0.00),
(53, 26, 143, 'PCS', 28800, 2000, 2000, 0.00),
(54, 26, 145, 'PCS', 21600, 0, 0, 0.00),
(55, 27, 481, 'PCS', 36000, 0, 0, 0.00),
(56, 27, 482, 'PCS', 28800, 0, 0, 0.00),
(57, 27, 483, 'PCS', 21600, 0, 0, 0.00),
(58, 27, 475, 'PCS', 24000, 0, 0, 0.00),
(59, 27, 476, 'PCS', 28800, 0, 0, 0.00),
(60, 27, 477, 'PCS', 28800, 0, 0, 0.00),
(61, 28, 95, 'PCS', 1090, 0, 0, 0.00),
(62, 29, 107, 'PCS', 1090, 0, 0, 0.00),
(63, 30, 104, 'PCS', 1090, 0, 0, 0.00),
(64, 31, 100, 'PCS', 1090, 0, 0, 0.00),
(65, 32, 89, 'PCS', 108, 0, 0, 0.00),
(66, 33, 108, 'PCS', 576000, 100680, 0, 0.00),
(67, 34, 17, 'PCS', 432000, 45504, 0, 0.00),
(68, 35, 108, 'PCS', 1728000, 113352, 0, 0.00),
(69, 23, 30, 'PCS', 146880, 5256, 0, 0.00),
(70, 36, 373, 'PCS', 2500, 2100, 6000, 0.00),
(71, 36, 89, 'PCS', 2300, 2101, 2101, 0.00),
(72, 36, 355, 'PCS', 2880, 1799, 1799, 0.00);

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
(7, 'finance', 'finance@email.com', 'finance', 'Test Finance', 'finance', 1, 0, '2026-06-02 10:41:41', '2026-06-02 02:41:41'),
(8, 'wakin', 'wakin@gmail.com', 'qwerty', 'Luis Guevara', 'warehouse', 1, 0, '2026-07-03 08:46:43', '2026-07-03 00:46:43'),
(9, 'sescultura', 'billings@cianancorp.com', 'sescultura', 'Shyrene Escultura', 'finance', 1, 0, '2026-07-07 10:59:45', '2026-07-07 02:59:45'),
(10, 'mfrubio', 'disbursements@cianancorp.com', 'mfrubio', 'Maria Francia Rubio', 'finance', 1, 0, '2026-07-07 11:01:33', '2026-07-07 03:01:33'),
(11, 'Cath', 'productionencoder09@gmail.com', 'carol0124', 'Cathlyn Libres', 'production', 1, 0, '2026-07-07 11:43:38', '2026-07-07 03:43:38'),
(12, 'ELMEI', 'supplychainofficercianan@gmail.com', 'Password1234', 'ELMEI JOY HAGOS', 'warehouse', 1, 0, '2026-07-07 11:44:33', '2026-07-07 03:44:33'),
(13, 'alezzaperdigosa', 'supplychainofficercianan1@gmail.com', 'Cianan123', 'Alezzagrace', 'warehouse', 1, 0, '2026-07-07 11:46:01', '2026-07-07 03:46:01'),
(14, 'QUEENSEE', 'warehouse@cianancorp.com', 'queensee', 'QUEENSEE NAVAREZ', 'warehouse', 1, 0, '2026-07-07 15:30:00', '2026-07-07 07:30:00'),
(15, 'GEMELYN', 'grrecodig@gmail.com', 'gemelyn2026!', 'Gemelyn R. Recodig', 'warehouse', 1, 0, '2026-07-10 09:03:53', '2026-07-10 01:03:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advance_production_consumption`
--
ALTER TABLE `advance_production_consumption`
  ADD PRIMARY KEY (`id`),
  ADD KEY `advance_poi_id` (`advance_poi_id`),
  ADD KEY `advance_po_id` (`advance_po_id`),
  ADD KEY `normal_poi_id` (`normal_poi_id`),
  ADD KEY `normal_po_id` (`normal_po_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created` (`created_at`);

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
-- Indexes for table `delivery_reports`
--
ALTER TABLE `delivery_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_delivery_id` (`delivery_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `excess_production`
--
ALTER TABLE `excess_production`
  ADD PRIMARY KEY (`excess_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `source_po_id` (`source_po_id`),
  ADD KEY `source_poi_id` (`source_poi_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
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
-- Indexes for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_history_id` (`history_id`),
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `advance_production_consumption`
--
ALTER TABLE `advance_production_consumption`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `delivery_receipts`
--
ALTER TABLE `delivery_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery_reports`
--
ALTER TABLE `delivery_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `excess_production`
--
ALTER TABLE `excess_production`
  MODIFY `excess_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=660;

--
-- AUTO_INCREMENT for table `price_list`
--
ALTER TABLE `price_list`
  MODIFY `price_list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `production_history`
--
ALTER TABLE `production_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `production_lots`
--
ALTER TABLE `production_lots`
  MODIFY `lot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `production_reports`
--
ALTER TABLE `production_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `poi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `so_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advance_production_consumption`
--
ALTER TABLE `advance_production_consumption`
  ADD CONSTRAINT `advance_production_consumption_ibfk_1` FOREIGN KEY (`advance_poi_id`) REFERENCES `purchase_order_items` (`poi_id`),
  ADD CONSTRAINT `advance_production_consumption_ibfk_2` FOREIGN KEY (`advance_po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `advance_production_consumption_ibfk_3` FOREIGN KEY (`normal_poi_id`) REFERENCES `purchase_order_items` (`poi_id`),
  ADD CONSTRAINT `advance_production_consumption_ibfk_4` FOREIGN KEY (`normal_po_id`) REFERENCES `purchase_orders` (`po_id`);

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `deliveries_ibfk_3` FOREIGN KEY (`lot_id`) REFERENCES `production_lots` (`lot_id`);

--
-- Constraints for table `excess_production`
--
ALTER TABLE `excess_production`
  ADD CONSTRAINT `excess_production_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `excess_production_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  ADD CONSTRAINT `excess_production_ibfk_3` FOREIGN KEY (`source_po_id`) REFERENCES `purchase_orders` (`po_id`),
  ADD CONSTRAINT `excess_production_ibfk_4` FOREIGN KEY (`source_poi_id`) REFERENCES `purchase_order_items` (`poi_id`);

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
