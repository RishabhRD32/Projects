-- ============================================================
--  foodie_db  –  Full clean schema
--  Run: mysql -u root < foodie_db.sql   OR import via phpMyAdmin
-- ============================================================

DROP DATABASE IF EXISTS `foodie_db`;
CREATE DATABASE `foodie_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodie_db`;

-- ------------------------------------------------------------------
-- users
-- ------------------------------------------------------------------
CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100)  NOT NULL,
  `email`      VARCHAR(150)  NOT NULL UNIQUE,
  `phone`      VARCHAR(15)   DEFAULT NULL,
  `password`   VARCHAR(255)  NOT NULL,
  `address`    VARCHAR(300)  DEFAULT NULL,
  `role`       ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ONLY admin seed user. Customers register themselves.
INSERT INTO `users` (`name`,`email`,`phone`,`password`,`role`) VALUES
('Admin', 'admin@foodie.com', '9000000000', 'admin123', 'admin');

-- ------------------------------------------------------------------
-- products
-- ------------------------------------------------------------------
CREATE TABLE `products` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price`       DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `image`       VARCHAR(255) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`name`,`description`,`price`,`image`) VALUES
('Double Beef Burger','Juicy double patty with cheese and fresh veggies',12.50,'images/burger.png'),
('Cheese Pizza',      'Classic margherita with extra cheese',             15.00,'images/pizza.png'),
('Fried Chicken',     'Crispy golden fried chicken pieces',               10.00,'images/fried-chicken.png'),
('Grilled Sandwich',  'Toasted sandwich with grilled veggies and sauce',   8.50,'images/sandwich.png'),
('Chicken Roll',      'Soft roll stuffed with spicy chicken filling',      9.00,'images/chicken-roll.png'),
('Italian Spaghetti', 'Classic spaghetti in rich tomato basil sauce',     14.00,'images/spaghetti.png'),
('Spring Roll',       'Crispy rolls filled with mixed vegetables',         7.00,'images/spring-roll.png'),
('Lasagna',           'Layered pasta with rich meat and bechamel sauce',  11.50,'images/lasagna.png');

-- ------------------------------------------------------------------
-- orders
-- ------------------------------------------------------------------
CREATE TABLE `orders` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT NOT NULL,
  `total`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`           ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled')
                     NOT NULL DEFAULT 'pending',
  `payment_method`   ENUM('cod','online') NOT NULL DEFAULT 'cod',
  `payment_status`   ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  `customer_name`    VARCHAR(150) NOT NULL,
  `customer_phone`   VARCHAR(20)  NOT NULL,
  `customer_address` VARCHAR(300) NOT NULL,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------------
-- order_items
-- ------------------------------------------------------------------
CREATE TABLE `order_items` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`   INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity`   INT NOT NULL DEFAULT 1,
  `price`      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;