-- =====================================================
-- EXAM LAYER MIGRATION - STEP 1
-- DO NOT RUN THIS DIRECTLY - Use migration_runner.php
-- =====================================================
-- This migration adds the exam layer to enable:
-- - Multiple class test instances per subject
-- - Separate Final/Midterm/ClassTest exam types
-- - Better organization and tracking of exam results
-- =====================================================

USE mawts;

-- =====================================================
-- STEP 1: CREATE EXAMS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `exams` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `exam_type` ENUM('Final', 'Midterm', 'ClassTest', 'Assignment', 'Quiz') NOT NULL,
  `exam_number` INT(11) NULL COMMENT 'Sequence number for ClassTests (1,2,3...). NULL or 1 for Final/Midterm',
  `title` VARCHAR(255) NOT NULL COMMENT 'Human-readable title like "CT-3 - Data Structures - Sem 6"',
  `semester` INT(11) NOT NULL,
  `department_id` INT(11) NOT NULL,
  `subject_id` INT(11) NULL COMMENT 'NULL for semester-wide exams (Final/Midterm)',
  `total_marks` INT(11) NULL COMMENT 'Default total marks for this exam',
  `exam_date` DATE NULL,
  `created_by` INT(11) NULL COMMENT 'Teacher or admin ID who created this exam',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL,
  INDEX `idx_exam_type` (`exam_type`),
  INDEX `idx_semester` (`semester`),
  INDEX `idx_dept_semester` (`department_id`, `semester`),
  INDEX `idx_subject_type` (`subject_id`, `exam_type`),
  INDEX `idx_exam_date` (`exam_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores exam instances (Final, Midterm, ClassTest, etc.)';

-- =====================================================
-- STEP 2: ADD exam_id TO results TABLE (nullable initially)
-- =====================================================

ALTER TABLE `results`
ADD COLUMN IF NOT EXISTS `exam_id` INT(11) NULL
AFTER `id`,
ADD INDEX `idx_exam_id` (`exam_id`);

-- Keep exam_type for backward compatibility during migration
-- We'll deprecate it later after verifying migration success
ALTER TABLE `results`
MODIFY COLUMN `exam_type` ENUM('Final', 'Midterm', 'Assignment', 'Quiz', 'ClassTest') NULL;

-- =====================================================
-- STEP 3: CREATE AUDIT AND LOGGING TABLES
-- =====================================================

-- Upload logs - tracks all file uploads
CREATE TABLE IF NOT EXISTS `upload_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `exam_id` INT(11) NULL,
  `filename` VARCHAR(255) NOT NULL,
  `uploader_id` INT(11) NULL COMMENT 'Teacher or admin who uploaded',
  `uploader_type` ENUM('admin', 'teacher') NULL,
  `rows_total` INT(11) NOT NULL DEFAULT 0,
  `rows_success` INT(11) NOT NULL DEFAULT 0,
  `rows_failed` INT(11) NOT NULL DEFAULT 0,
  `error_log` TEXT NULL COMMENT 'JSON array of error messages',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
  INDEX `idx_uploader` (`uploader_id`, `uploader_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks all result upload operations';

-- Result audit log - tracks changes to result records
CREATE TABLE IF NOT EXISTS `result_audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `result_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `exam_id` INT(11) NULL,
  `subject_id` INT(11) NOT NULL,
  `action` ENUM('insert', 'update', 'delete') NOT NULL,
  `previous_marks` DECIMAL(5,2) NULL,
  `new_marks` DECIMAL(5,2) NULL,
  `previous_grade` VARCHAR(2) NULL,
  `new_grade` VARCHAR(2) NULL,
  `changed_by` INT(11) NULL,
  `changed_by_type` ENUM('admin', 'teacher') NULL,
  `reason` VARCHAR(255) NULL COMMENT 'Reason for change (e.g., "Excel upload", "Manual correction")',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`result_id`) REFERENCES `results`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  INDEX `idx_result` (`result_id`),
  INDEX `idx_student` (`student_id`),
  INDEX `idx_exam` (`exam_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit trail for all result modifications';

-- =====================================================
-- MIGRATION STATUS TRACKING
-- =====================================================

CREATE TABLE IF NOT EXISTS `migration_status` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `migration_name` VARCHAR(100) NOT NULL UNIQUE,
  `status` ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert migration tracking record
INSERT INTO `migration_status` (`migration_name`, `status`)
VALUES ('exam_layer_step1', 'completed')
ON DUPLICATE KEY UPDATE status = 'completed', completed_at = CURRENT_TIMESTAMP;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

SELECT 'Step 1 completed: Tables created' AS status;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'mawts'
AND TABLE_NAME IN ('exams', 'upload_logs', 'result_audit_log', 'migration_status');
