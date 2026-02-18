-- Database schema for the rental system
-- Run this in phpMyAdmin to set everything up

CREATE DATABASE IF NOT EXISTS rento_db;
USE rento_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    max_rentals INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Equipment table
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    `condition` ENUM('new', 'good', 'fair', 'poor') DEFAULT 'good', -- backticks cos condition is a reserved word
    total_quantity INT NOT NULL DEFAULT 1,
    available_quantity INT NOT NULL DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rentals table - return_date is NULL until the item comes back
CREATE TABLE IF NOT EXISTS rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    quantity_rented INT NOT NULL DEFAULT 1,
    rental_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    status ENUM('rented', 'returned', 'overdue') DEFAULT 'rented',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data

-- Admin account (password: admin123)
INSERT INTO users (name, email, username, password, role, max_rentals) VALUES
('System Admin', 'admin@rento.local', 'admin', '$2y$12$O0Jb.zyehwtVOLIGZmvDjO7CLdsTTJT4exvMFsvIORbBH1UYBq/Ie', 'admin', 99);

-- Test users (passwords: password123, password456)
INSERT INTO users (name, email, username, password, role) VALUES
('Alice Johnson', 'alice@student.ac.uk', 'alice', '$2y$12$c12ApQgpUHk0h9zpnh9DzuJRfC1TMuSx92fzk7pes4yAFKAVU2qLa', 'user'),
('Bob Smith', 'bob@student.ac.uk', 'bob', '$2y$12$uuJV2y1Jhd09Be4EbuD2DO71QYEynjSvOdmYro3C213yuipu.1X9S', 'user');

-- Some equipment to test with
INSERT INTO equipment (name, category, serial_number, `condition`, total_quantity, available_quantity, description) VALUES
('Canon EOS R50', 'Cameras', 'CAM-001', 'new', 3, 3, 'Mirrorless digital camera with 24.2MP sensor. Ideal for photography projects.'),
('Rode VideoMic Pro', 'Audio', 'AUD-001', 'good', 5, 5, 'Shotgun microphone for on-camera use. Great for video production.'),
('Manfrotto Tripod 290', 'Accessories', 'ACC-001', 'good', 4, 4, 'Lightweight aluminium tripod. Supports cameras up to 5kg.'),
('MacBook Pro 14"', 'Laptops', 'LAP-001', 'fair', 2, 2, '14-inch MacBook Pro for video editing and design work.'),
('Sony A7 III', 'Cameras', 'CAM-002', 'good', 2, 2, 'Full-frame mirrorless camera. Excellent for low-light shooting.'),
('Aputure 120D II', 'Lighting', 'LGT-001', 'new', 3, 3, 'LED studio light with Bowens mount. Daylight balanced at 5500K.');
