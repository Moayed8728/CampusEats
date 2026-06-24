CREATE DATABASE IF NOT EXISTS campuseats
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE campuseats;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'vendor', 'admin') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vendors (
    id CHAR(36) PRIMARY KEY,
    owner_id CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vendors_owner FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS menu_items (
    id CHAR(36) PRIMARY KEY,
    vendor_id CHAR(36) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    INDEX idx_menu_vendor (vendor_id)
);

CREATE TABLE IF NOT EXISTS orders (
    id CHAR(36) PRIMARY KEY,
    customer_id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    pickup_at DATETIME NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('placed', 'preparing', 'ready', 'collected') NOT NULL DEFAULT 'placed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_orders_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    INDEX idx_orders_vendor (vendor_id),
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_created (created_at)
);

CREATE TABLE IF NOT EXISTS order_items (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) NOT NULL,
    menu_item_id CHAR(36) NOT NULL,
    qty INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    INDEX idx_order_items_order (order_id)
);
