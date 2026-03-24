-- Migration: Add total_seats column to flights table
-- Run this in phpMyAdmin or MySQL command line

USE flywings;

-- Add total_seats column if it doesn't exist
ALTER TABLE flights 
ADD COLUMN IF NOT EXISTS total_seats INT NOT NULL DEFAULT 180 AFTER base_fare;

-- Update existing flights with default seat capacity (180 seats)
UPDATE flights SET total_seats = 180 WHERE total_seats IS NULL OR total_seats = 0;
