-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS parking_system_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE parking_system_db;

-- Drop tables in reverse order of dependency to avoid foreign key errors
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS slots;
DROP TABLE IF EXISTS parking_lots;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Parking lots table
CREATE TABLE parking_lots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Slots table
CREATE TABLE slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lot_id INT NOT NULL,
    slot_number VARCHAR(50) NOT NULL,
    status ENUM('available','booked') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lot_id) REFERENCES parking_lots(id) ON DELETE CASCADE,
    UNIQUE KEY lot_slot_unique (lot_id, slot_number) -- Ensure unique slot numbers within a lot
) ENGINE=InnoDB;

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('booked','completed','cancelled') NOT NULL DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    INDEX idx_booking_times (slot_id, start_time, end_time, status) -- Index for faster conflict checks
) ENGINE=InnoDB;

-- Add a default admin user (change password in a real application)
-- Password is 'adminpass'
INSERT INTO users (name, email, password, role) VALUES ('Admin User', 'admin@parksys.com', '$2y$10$9.B2yJ6kZ4L4K4O4I4E4fOu5n5o5k5A5d5m5i5n5P5a5s5s', 'admin');