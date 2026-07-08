-- Manufacturing Management System Database Schema
-- Updated with Warehouse and additional tables

CREATE DATABASE IF NOT EXISTS manufacturing_mgmt;
USE manufacturing_mgmt;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    department ENUM('admin', 'warehouse', 'production', 'finance') NOT NULL,
    status TINYINT(1) DEFAULT 1,
    `remove` TINYINT(1) DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_address TEXT,
    customer_type ENUM('vat','non_vat') DEFAULT 'vat' COMMENT 'vat=VAT registered, non_vat=Non-VAT',
    customer_tin VARCHAR(50),
    customer_terms VARCHAR(50) DEFAULT NULL COMMENT 'Payment terms: 15/30/60/90/120 days, COD, or Undefined Credit Term',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Items Table
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL,
    item_description VARCHAR(255) NOT NULL,
    customer_id INT(11) DEFAULT NULL,
    item_uom VARCHAR(50) NOT NULL COMMENT 'PCS, PCKS, CS',
    uom_conversion INT NULL DEFAULT NULL COMMENT 'Units per case, e.g. 10 means 10 PCS = 1 CS. NULL when UOM is CS',
    item_size VARCHAR(50),
    item_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Orders Table (from Warehouse)
CREATE TABLE IF NOT EXISTS purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    requested_by INT NOT NULL,
    total_quantity INT DEFAULT 0 COMMENT 'Total quantity for production tracking',
    customer_terms VARCHAR(50) DEFAULT NULL COMMENT 'Payment terms: 15/30/60/90/120 days, COD, or Undefined Credit Term',
    production_type ENUM('normal','advance') DEFAULT 'normal' COMMENT 'normal=regular, advance=advance production',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    active_status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (requested_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Order Items Table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    poi_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    item_id INT NOT NULL,
    item_uom VARCHAR(50) NOT NULL DEFAULT 'PCS' COMMENT 'Stored UOM for the PO item, defaults to PCS',
    quantity INT NOT NULL,
    produced_quantity INT DEFAULT 0 COMMENT 'Produced quantity per item',
    delivered_quantity INT DEFAULT 0 COMMENT 'Delivered quantity per item',
    unit_price DECIMAL(15,2) NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    active_status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Production Lots Table
CREATE TABLE IF NOT EXISTS production_lots (
    lot_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    poi_id INT NOT NULL,
    lot_number VARCHAR(100) NOT NULL,
    quantity_produced INT NOT NULL DEFAULT 0,
    lot_date DATE NULL,
    created_by INT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    `is_removed` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deliveries Table (from Warehouse)
CREATE TABLE IF NOT EXISTS deliveries (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    poi_id INT NULL,
    lot_id INT NULL,
    delivered_by INT NOT NULL,
    delivery_date DATE NOT NULL,
    delivery_quantity INT DEFAULT 0,
    dr_number VARCHAR(50) NULL COMMENT 'Delivery Receipt number',
    lot_items JSON NULL COMMENT 'JSON array of lot details [{lot_id, poi_id, lot_number, item_code, item_description, qty}]',
    remarks TEXT,
    report_remarks TEXT NULL COMMENT 'Report/edit concerns from warehouse or admin',
    remarks_type VARCHAR(20) NULL DEFAULT NULL COMMENT 'NULL or ''normal'' = normal remarks, ''report'' = warehouse-reported concern',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    active_status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (lot_id) REFERENCES production_lots(lot_id),
    FOREIGN KEY (delivered_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Manufacturing Requests Table (for Production)
CREATE TABLE IF NOT EXISTS manufacturing_requests (
    mr_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    instructions TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales Orders Table (for Finance)
CREATE TABLE IF NOT EXISTS sales_orders (
    so_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    customer_id INT NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Excess Production Table
CREATE TABLE IF NOT EXISTS excess_production (
    excess_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    item_id INT NOT NULL,
    source_po_id INT NOT NULL,
    source_poi_id INT NOT NULL,
    excess_quantity INT NOT NULL,
    consumed_quantity INT DEFAULT 0,
    remaining_quantity INT GENERATED ALWAYS AS (excess_quantity - consumed_quantity) STORED,
    status ENUM('pending','partial','consumed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    FOREIGN KEY (source_po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (source_poi_id) REFERENCES purchase_order_items(poi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Advance Production Consumption Table
CREATE TABLE IF NOT EXISTS advance_production_consumption (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advance_poi_id INT NOT NULL COMMENT 'Which advance PO item was consumed',
    advance_po_id INT NOT NULL,
    normal_poi_id INT NOT NULL COMMENT 'Which normal PO item received it',
    normal_po_id INT NOT NULL,
    quantity INT NOT NULL COMMENT 'How much was allocated',
    date_allocated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (advance_poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (advance_po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (normal_poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (normal_po_id) REFERENCES purchase_orders(po_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;