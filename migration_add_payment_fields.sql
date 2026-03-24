-- Migration: Add payment fields to bookings table
-- Run this in phpMyAdmin

USE flywings;

-- Add payment_method column
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL AFTER status;

-- Add payment_status column
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_status ENUM('PENDING', 'COMPLETED', 'FAILED', 'REFUNDED') DEFAULT 'PENDING' AFTER payment_method;

-- Add payment_transaction_id for tracking
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_transaction_id VARCHAR(100) DEFAULT NULL AFTER payment_status;

-- Add payment_amount (stored amount at time of payment)
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_amount DECIMAL(10,2) DEFAULT NULL AFTER payment_transaction_id;

-- Add payment_date
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_date TIMESTAMP NULL DEFAULT NULL AFTER payment_amount;
