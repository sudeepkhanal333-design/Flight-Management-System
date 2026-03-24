-- ============================================
-- FLY WINGS FLIGHT MANAGEMENT SYSTEM
-- Complete Database Schema
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS flywings
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE flywings;

-- ============================================
-- TABLE 1: USERS (Passengers)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 2: ADMINS (Admin Users)
-- ============================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample admin account
INSERT INTO admins (name, email, password) VALUES ('Main Admin', 'admin@flywings.com', 'admin123')
ON DUPLICATE KEY UPDATE email = email;

-- ============================================
-- TABLE 3: FLIGHTS (Flight Routes & Schedules)
-- ============================================
CREATE TABLE IF NOT EXISTS flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_code VARCHAR(20) NOT NULL UNIQUE,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    status ENUM('ON-TIME', 'DELAYED', 'CANCELLED') DEFAULT 'ON-TIME',
    base_fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_seats INT NOT NULL DEFAULT 180,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample flights
INSERT INTO flights (flight_code, origin, destination, departure_time, arrival_time, status, base_fare, total_seats) VALUES 
('FW208', 'Mumbai (BOM)', 'Dubai (DXB)', '2025-12-14 22:35:00', '2025-12-15 00:45:00', 'ON-TIME', 18500.00, 180),
('FW542', 'Delhi (DEL)', 'Singapore (SIN)', '2025-12-15 01:15:00', '2025-12-15 07:10:00', 'DELAYED', 23500.00, 180)
ON DUPLICATE KEY UPDATE flight_code = flight_code;

-- ============================================
-- TABLE 4: BOOKINGS (Flight Reservations)
-- ============================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    pnr VARCHAR(20) NOT NULL UNIQUE,
    seats INT NOT NULL DEFAULT 1,
    status ENUM('CONFIRMED', 'PENDING', 'CANCELLED') DEFAULT 'PENDING',
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status ENUM('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED') DEFAULT 'PENDING',
    payment_transaction_id VARCHAR(100) DEFAULT NULL,
    payment_amount DECIMAL(10,2) DEFAULT NULL,
    payment_date TIMESTAMP NULL DEFAULT NULL,
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE 5: CONTACT_MESSAGES (Contact Form)
-- ============================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;