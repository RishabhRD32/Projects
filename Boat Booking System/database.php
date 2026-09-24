CREATE DATABASE boat_booking_system;
USE boat_booking_system;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Boats Table
CREATE TABLE boats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Speedboat', 'Yacht', 'Fishing Boat', 'Pontoon') NOT NULL,
    capacity INT NOT NULL,
    price_per_hour DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    available TINYINT(1) DEFAULT 1
);

-- Bookings Table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    boat_id INT,
    booking_date DATE,
    start_time TIME,
    end_time TIME,
    total_price DECIMAL(10,2),
    status ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (boat_id) REFERENCES boats(id)
);