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
    customer_tin VARCHAR(50),
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Items Table
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_description VARCHAR(255) NOT NULL,
    item_uom VARCHAR(50) NOT NULL COMMENT 'PCS, PCKS, CS',
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
    customer_terms INT DEFAULT 0 COMMENT 'Payment terms in days',
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

-- Deliveries Table (from Warehouse)
CREATE TABLE IF NOT EXISTS deliveries (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    poi_id INT NULL,
    delivered_by INT NOT NULL,
    delivery_date DATE NOT NULL,
    delivery_quantity INT DEFAULT 0,
    remarks TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    active_status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (poi_id) REFERENCES purchase_order_items(poi_id),
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