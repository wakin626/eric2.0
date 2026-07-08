-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2026 at 05:09 AM
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
(26, 'MSYBSY', 'MESSY BESSY CLEANERS INC.', 'Natividad Building #2308 Chino Roces Avenue, Ext. 1232 Magallanes Village Makati City\r\n', 'vat', '006-935-228-000', '30', '2026-07-04 09:31:40', 1, 0, '2026-07-04 01:31:40');

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
(26, 'FG0672-BLKERTRCON180', 'BareLab Keratin Treatment Conditioner 180g', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 14:28:38', 1, 0, '2026-07-06 07:29:22'),
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
(39, 'FG0633-EMPSHAM11+1', 'Empress Shampoo Long & Healthy 21mlx24pck (11+1)', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 15:11:48', 1, 0, '2026-07-03 07:11:48'),
(40, 'FG0664-EMPSHAx2', 'Empress Shampoo Long n Healthy x2', 19, 'PCS', 500, NULL, 0.00, '2026-07-03 15:12:44', 1, 0, '2026-07-03 07:12:44'),
(43, 'FG0190-EDTACT60', 'Pure Basic EDT Active 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:19:04', 1, 0, '2026-07-03 07:19:04'),
(44, 'FG0191-EDTCOOL60', 'Pure Basic EDT Cool 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:19:53', 1, 0, '2026-07-03 07:19:53'),
(45, 'FG0192-EDTAQUA60', 'Pure Basic EDT Aqua 60ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:21:50', 1, 0, '2026-07-03 07:21:50'),
(52, 'FG0235-EDTBLOOM100', 'Pure Basic EDT Bloom 100ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:34:15', 0, 0, '2026-07-06 05:37:18'),
(53, 'FG0236-EDTINTENSE100', 'Pure Bacic EDT Intense 100ml', 19, 'PCS', 24, NULL, 0.00, '2026-07-03 15:35:07', 0, 0, '2026-07-06 05:37:25'),
(54, 'FG0263-KPSS22x11+1', 'Keratin Plus Shampoo Soft Smooth 22mlx11+1 Promo', 19, 'PCS', 288, NULL, 0.00, '2026-07-03 15:35:54', 1, 0, '2026-07-03 07:35:54'),
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
(368, 'FG0642-RSGPSBLUSH5', 'True Ready Scent, Go! Blush Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:58:03', 1, 0, '2026-07-06 06:58:03');
INSERT INTO `items` (`item_id`, `item_code`, `item_description`, `customer_id`, `item_uom`, `uom_conversion`, `item_size`, `item_amount`, `date_created`, `status`, `remove`, `last_update`) VALUES
(369, 'FG0643-RSGPSGLAM5', 'True Ready Scent, Go! Glam Perfume Stick 5g', 11, 'PCS', 84, NULL, 0.00, '2026-07-06 14:58:24', 1, 0, '2026-07-06 06:58:24'),
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
(404, 'FG0688-GCNSTNGSAN250', 'Green Cross Gentle Protect No-Sting Sanitizer 250mL', 15, 'PCS', 48, NULL, 0.00, '2026-07-06 15:42:00', 1, 0, '2026-07-06 07:42:00');

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
(112, 221, 'hand Cream Kiwi 50g', '50g', 0.00, 0.00, 68.88, 'vat', 0, 0, '2026-07-04 14:10:35', '2026-07-06 08:30:29');

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
(10, 'mfrubio', 'disbursements@cianancorp.com', 'mfrubio', 'Maria Francia Rubio', 'finance', 1, 0, '2026-07-07 11:01:33', '2026-07-07 03:01:33');

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
-- Indexes for table `delivery_reports`
--
ALTER TABLE `delivery_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_delivery_id` (`delivery_id`),
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=405;

--
-- AUTO_INCREMENT for table `price_list`
--
ALTER TABLE `price_list`
  MODIFY `price_list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `production_history`
--
ALTER TABLE `production_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `production_lots`
--
ALTER TABLE `production_lots`
  MODIFY `lot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `production_reports`
--
ALTER TABLE `production_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `poi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `so_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
