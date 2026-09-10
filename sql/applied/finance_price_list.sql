-- Price List table for Finance module

CREATE TABLE IF NOT EXISTS price_list (
    price_list_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    net_size VARCHAR(100) NULL,
    price_per_pack DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    price_per_case DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    price_per_piece DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    vat_type ENUM('vat','non_vat') DEFAULT 'vat' COMMENT 'vat=VAT registered, non_vat=Non-VAT',
    status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
    `remove` TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
