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
    location VARCHAR(160) NULL,
    opening_hours VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vendors_owner FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS vendor_applications (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    vendor_name VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(160) NOT NULL,
    opening_hours VARCHAR(120) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vendor_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_vendor_applications_user (user_id),
    INDEX idx_vendor_applications_status (status, created_at)
);

SET @add_location = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE vendors ADD COLUMN location VARCHAR(160) NULL AFTER description',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vendors'
      AND COLUMN_NAME = 'location'
);
PREPARE add_location_stmt FROM @add_location;
EXECUTE add_location_stmt;
DEALLOCATE PREPARE add_location_stmt;

SET @add_opening_hours = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE vendors ADD COLUMN opening_hours VARCHAR(120) NULL AFTER location',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'vendors'
      AND COLUMN_NAME = 'opening_hours'
);
PREPARE add_opening_hours_stmt FROM @add_opening_hours;
EXECUTE add_opening_hours_stmt;
DEALLOCATE PREPARE add_opening_hours_stmt;

CREATE TABLE IF NOT EXISTS menu_items (
    id CHAR(36) PRIMARY KEY,
    vendor_id CHAR(36) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(80) NULL,
    is_halal TINYINT(1) NOT NULL DEFAULT 1,
    is_vegetarian TINYINT(1) NOT NULL DEFAULT 0,
    in_stock TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    INDEX idx_menu_vendor (vendor_id)
);

SET @add_menu_category = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE menu_items ADD COLUMN category VARCHAR(80) NULL AFTER price',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'menu_items'
      AND COLUMN_NAME = 'category'
);
PREPARE add_menu_category_stmt FROM @add_menu_category;
EXECUTE add_menu_category_stmt;
DEALLOCATE PREPARE add_menu_category_stmt;

SET @add_menu_is_halal = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE menu_items ADD COLUMN is_halal TINYINT(1) NOT NULL DEFAULT 1 AFTER category',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'menu_items'
      AND COLUMN_NAME = 'is_halal'
);
PREPARE add_menu_is_halal_stmt FROM @add_menu_is_halal;
EXECUTE add_menu_is_halal_stmt;
DEALLOCATE PREPARE add_menu_is_halal_stmt;

SET @add_menu_is_vegetarian = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE menu_items ADD COLUMN is_vegetarian TINYINT(1) NOT NULL DEFAULT 0 AFTER is_halal',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'menu_items'
      AND COLUMN_NAME = 'is_vegetarian'
);
PREPARE add_menu_is_vegetarian_stmt FROM @add_menu_is_vegetarian;
EXECUTE add_menu_is_vegetarian_stmt;
DEALLOCATE PREPARE add_menu_is_vegetarian_stmt;

CREATE TABLE IF NOT EXISTS orders (
    id CHAR(36) PRIMARY KEY,
    customer_id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    pickup_at DATETIME NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending','paid','failed','mock_paid') NOT NULL DEFAULT 'pending',
    payment_reference VARCHAR(255) NULL,
    payment_method ENUM('mock','stripe') NOT NULL DEFAULT 'mock',
    status ENUM('placed', 'preparing', 'ready', 'collected') NOT NULL DEFAULT 'placed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_orders_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    INDEX idx_orders_vendor (vendor_id),
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_created (created_at)
);

SET @add_order_payment_status = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD COLUMN payment_status ENUM(''pending'',''paid'',''failed'',''mock_paid'') NOT NULL DEFAULT ''pending'' AFTER total',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_status'
);
PREPARE add_order_payment_status_stmt FROM @add_order_payment_status;
EXECUTE add_order_payment_status_stmt;
DEALLOCATE PREPARE add_order_payment_status_stmt;

SET @add_order_payment_reference = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(255) NULL AFTER payment_status',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_reference'
);
PREPARE add_order_payment_reference_stmt FROM @add_order_payment_reference;
EXECUTE add_order_payment_reference_stmt;
DEALLOCATE PREPARE add_order_payment_reference_stmt;

SET @add_order_payment_method = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD COLUMN payment_method ENUM(''mock'',''stripe'') NOT NULL DEFAULT ''mock'' AFTER payment_reference',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_method'
);
PREPARE add_order_payment_method_stmt FROM @add_order_payment_method;
EXECUTE add_order_payment_method_stmt;
DEALLOCATE PREPARE add_order_payment_method_stmt;

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

CREATE TABLE IF NOT EXISTS reviews (
    id CHAR(36) PRIMARY KEY,
    student_id CHAR(36) NOT NULL,
    vendor_id CHAR(36) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_student FOREIGN KEY (student_id) REFERENCES users(id),
    CONSTRAINT fk_reviews_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_reviews_vendor (vendor_id),
    INDEX idx_reviews_student (student_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id, created_at)
);

CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id CHAR(36) PRIMARY KEY,
    student_id CHAR(36) NOT NULL,
    order_id CHAR(36) NOT NULL UNIQUE,
    points INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loyalty_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_loyalty_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_loyalty_student (student_id, created_at)
);
