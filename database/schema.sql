-- =============================================================
-- Vitage Vintage Clothing Store - Database Schema
-- Run this in phpMyAdmin (XAMPP) or via mysql CLI
-- =============================================================

DROP DATABASE IF EXISTS vitage_store;
CREATE DATABASE vitage_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vitage_store;

-- -------------------------------------------------------------
-- Users
-- -------------------------------------------------------------
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('user','admin') NOT NULL DEFAULT 'user',
    address         VARCHAR(255) DEFAULT NULL,
    phone           VARCHAR(40)  DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Categories
-- -------------------------------------------------------------
CREATE TABLE categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80) NOT NULL UNIQUE,
    slug        VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Products
-- -------------------------------------------------------------
CREATE TABLE products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    category_id     INT NOT NULL,
    name            VARCHAR(150) NOT NULL,
    short_desc      VARCHAR(255) DEFAULT NULL,
    description     TEXT,
    price           DECIMAL(10,2) NOT NULL,
    stock           INT NOT NULL DEFAULT 0,
    sizes           VARCHAR(120) DEFAULT 'S,M,L',
    image_1         VARCHAR(255) DEFAULT NULL,
    image_2         VARCHAR(255) DEFAULT NULL,
    image_3         VARCHAR(255) DEFAULT NULL,
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    views           INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Cart (one cart per user, items stored separately)
-- -------------------------------------------------------------
CREATE TABLE cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    cart_id     INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    size        VARCHAR(20) DEFAULT NULL,
    CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id)
        REFERENCES cart(id) ON DELETE CASCADE,
    CONSTRAINT fk_ci_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_item (cart_id, product_id, size)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Orders
-- -------------------------------------------------------------
CREATE TABLE orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    total           DECIMAL(10,2) NOT NULL,
    status          ENUM('pending','paid','shipped','delivered','cancelled')
                    NOT NULL DEFAULT 'pending',
    shipping_addr   VARCHAR(255) NOT NULL,
    phone           VARCHAR(40)  NOT NULL,
    notes           VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    product_id  INT NOT NULL,
    name        VARCHAR(150) NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    quantity    INT NOT NULL,
    size        VARCHAR(20) DEFAULT NULL,
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
        REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
        REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Password reset tokens
-- -------------------------------------------------------------
CREATE TABLE password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Activity logs (analytics)
-- -------------------------------------------------------------
CREATE TABLE activity_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT NULL,
    action      VARCHAR(80) NOT NULL,
    detail      VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- Seed data (users are seeded by /install.php with proper bcrypt hashes)
-- =============================================================

INSERT INTO categories (name, slug, description) VALUES
('Pants',       'pants',       'High-waisted denim, corduroy & wide-leg trousers'),
('Shirts',      'shirts',      'Button-down, oxford and retro printed shirts'),
('T-Shirts',    'tshirts',     'Soft-washed tees with timeless graphics'),
('Shoes',       'shoes',       'Loafers, leather boots and retro sneakers'),
('Belts',       'belts',       'Tooled leather and woven vintage belts'),
('Caps',        'caps',        'Newsboy caps, berets and trucker hats'),
('Accessories', 'accessories', 'Sunglasses, scarves, pins and more');

INSERT INTO products
(category_id, name, short_desc, description, price, stock, sizes, image_1, is_featured) VALUES
(1, '70s High-Waist Denim',  'Indigo wash, wide leg',     'Authentic 1970s cut high-waist jeans in deep indigo wash. Heavyweight cotton denim, button fly, wide leg silhouette. Pre-loved & restored.', 64.00, 12, 'S,M,L,XL', 'assets/images/p1.svg', 1),
(2, 'Linen Camp Shirt',      'Cream linen, vintage cut',  'Soft cream linen camp shirt. Boxy mid-century fit with notched lapel and pearl-tone buttons.', 42.00, 18, 'S,M,L,XL', 'assets/images/p2.svg', 1),
(3, 'Retro Sunset Tee',      'Sunset graphic print',      '100% combed cotton, washed for a worn-in feel. Hand-screened 80s sunset graphic on the front.', 24.00, 30, 'S,M,L,XL,XXL', 'assets/images/p3.svg', 1),
(4, 'Leather Penny Loafers', 'Hand-stitched cognac',      'Cognac leather penny loafers, hand-stitched and resoled. A timeless classic from the 60s.', 88.00, 8,  '39,40,41,42,43,44', 'assets/images/p4.svg', 1),
(5, 'Tooled Western Belt',   'Hand-tooled leather',       'Genuine tooled leather western belt with floral motif and antique brass buckle.', 32.00, 20, 'S,M,L', 'assets/images/p5.svg', 0),
(6, 'Wool Newsboy Cap',      'Charcoal herringbone',      'Eight-panel newsboy cap in charcoal herringbone wool. Satin lined.', 28.00, 15, 'S,M,L', 'assets/images/p6.svg', 1),
(7, 'Round Tortoise Shades', 'Acetate, UV400',            'Classic round acetate frames in tortoise shell, UV400 lenses. Inspired by 70s icons.', 36.00, 25, 'One Size', 'assets/images/p7.svg', 1),
(2, 'Pin-Stripe Oxford',     'Navy pinstripe',            'Crisp cotton oxford with subtle navy pinstripes. Single needle stitched.', 48.00, 14, 'S,M,L,XL', 'assets/images/p8.svg', 0),
(3, 'Vinyl Records Tee',     'Black, screen-printed',     'Black ringspun cotton tee with hand-printed vinyl record graphic.', 22.00, 26, 'S,M,L,XL', 'assets/images/p9.svg', 0),
(4, 'Combat Boots',          'Polished black leather',    'Polished black leather lace-up combat boots. Resoled & ready for another decade.', 110.00, 6, '40,41,42,43,44,45', 'assets/images/p10.svg', 0),
(1, 'Brown Corduroy Pants',  'Wide-wale corduroy',        'Wide-wale corduroy trousers in chestnut brown. Straight leg.', 54.00, 11, 'S,M,L,XL', 'assets/images/p11.svg', 0),
(7, 'Silk Paisley Scarf',    'Burgundy paisley',          'Hand-rolled silk scarf in burgundy paisley print. Lightweight and luxurious.', 30.00, 22, 'One Size', 'assets/images/p12.svg', 0);
