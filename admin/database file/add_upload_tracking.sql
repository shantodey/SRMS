-- Migration: Add upload tracking for undo feature
-- Run this SQL script once to add upload tracking functionality

-- Add upload_id column to results table
ALTER TABLE results
ADD COLUMN upload_id VARCHAR(50) NULL AFTER semester,
ADD INDEX idx_upload_id (upload_id);

-- Create upload_history table
CREATE TABLE IF NOT EXISTS upload_history (
    id VARCHAR(50) PRIMARY KEY,
    exam_type VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    department_id INT NOT NULL,
    subject_id INT NULL,
    records_count INT NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
);

-- Add updated_at column to results table if doesn't exist
ALTER TABLE results
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
