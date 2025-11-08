-- =====================================================
-- EXAM LAYER MIGRATION - STEP 3
-- ADD CONSTRAINTS AND INDEXES
-- DO NOT RUN THIS DIRECTLY - Use migration_runner.php
-- =====================================================
-- This adds FK constraints and unique constraints after data migration
-- Run this ONLY after verifying Step 2 completed successfully
-- =====================================================

USE mawts;

-- Update migration status
INSERT INTO `migration_status` (`migration_name`, `status`, `started_at`)
VALUES ('exam_layer_step3', 'running', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE status = 'running', started_at = CURRENT_TIMESTAMP;

-- =====================================================
-- PRE-CHECK: Verify Step 2 completed
-- =====================================================

SET @step2_status = (
    SELECT status
    FROM migration_status
    WHERE migration_name = 'exam_layer_step2'
);

-- If step 2 is not completed, abort
SELECT
    CASE
        WHEN @step2_status != 'completed' THEN
            'ERROR: Cannot run Step 3 - Step 2 is not completed'
        ELSE
            'OK: Step 2 completed, proceeding with constraints'
    END AS preflight_check;

-- =====================================================
-- STEP 1: ADD FOREIGN KEY CONSTRAINT
-- =====================================================

-- Add FK constraint from results.exam_id to exams.id
-- This ensures referential integrity
ALTER TABLE `results`
ADD CONSTRAINT `fk_results_exam`
FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`)
ON DELETE CASCADE;

-- =====================================================
-- STEP 2: ADD UNIQUE CONSTRAINT
-- =====================================================

-- Prevent duplicate results for same student, exam, and subject
-- This is a critical business rule: one result per student per exam per subject

-- First, check if there are any duplicates
CREATE TEMPORARY TABLE IF NOT EXISTS temp_duplicates AS
SELECT
    student_id,
    exam_id,
    subject_id,
    COUNT(*) as duplicate_count
FROM results
WHERE exam_id IS NOT NULL
GROUP BY student_id, exam_id, subject_id
HAVING COUNT(*) > 1;

-- Count duplicates
SET @duplicate_count = (SELECT COUNT(*) FROM temp_duplicates);

-- Show duplicates if any exist
SELECT
    @duplicate_count AS total_duplicate_groups,
    CASE
        WHEN @duplicate_count > 0 THEN 'WARNING: Duplicates found - must be resolved before adding unique constraint'
        ELSE 'OK: No duplicates found'
    END AS duplicate_check_status;

-- If duplicates exist, show them
SELECT
    td.student_id,
    s.student_name,
    s.index_no,
    e.title AS exam_title,
    sub.subject_name,
    td.duplicate_count,
    GROUP_CONCAT(r.id ORDER BY r.created_at DESC SEPARATOR ', ') AS result_ids
FROM temp_duplicates td
INNER JOIN students s ON td.student_id = s.id
INNER JOIN exams e ON td.exam_id = e.id
INNER JOIN subjects sub ON td.subject_id = sub.id
INNER JOIN results r ON
    r.student_id = td.student_id
    AND r.exam_id = td.exam_id
    AND r.subject_id = td.subject_id
GROUP BY td.student_id, td.exam_id, td.subject_id, s.student_name, s.index_no, e.title, sub.subject_name, td.duplicate_count
LIMIT 20;

-- Only add unique constraint if no duplicates exist
SET @add_constraint = (@duplicate_count = 0);

-- Conditionally add unique constraint
SET @sql = IF(@add_constraint,
    'ALTER TABLE `results` ADD UNIQUE KEY `uk_student_exam_subject` (`student_id`, `exam_id`, `subject_id`)',
    'SELECT "SKIPPED: Unique constraint not added due to duplicates" AS constraint_status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- STEP 3: ADD ADDITIONAL INDEXES FOR PERFORMANCE
-- =====================================================

-- Composite index for student + exam lookups (common query pattern)
ALTER TABLE `results`
ADD INDEX IF NOT EXISTS `idx_student_exam` (`student_id`, `exam_id`);

-- Index for subject + semester + exam_type (for filtering class tests by subject)
ALTER TABLE `exams`
ADD INDEX IF NOT EXISTS `idx_subject_semester_type` (`subject_id`, `semester`, `exam_type`);

-- Index for department + semester lookups
ALTER TABLE `exams`
ADD INDEX IF NOT EXISTS `idx_dept_sem_type` (`department_id`, `semester`, `exam_type`);

-- =====================================================
-- STEP 4: UPDATE MIGRATION STATUS
-- =====================================================

UPDATE `migration_status`
SET
    status = CASE
        WHEN @duplicate_count > 0 THEN 'failed'
        ELSE 'completed'
    END,
    completed_at = CURRENT_TIMESTAMP,
    error_message = CASE
        WHEN @duplicate_count > 0
        THEN CONCAT(@duplicate_count, ' duplicate result groups found - unique constraint not added')
        ELSE NULL
    END
WHERE migration_name = 'exam_layer_step3';

-- =====================================================
-- FINAL STATUS
-- =====================================================

SELECT
    'Step 3 Completed' AS status,
    @duplicate_count AS duplicates_found,
    @add_constraint AS unique_constraint_added;

-- Show all migration statuses
SELECT * FROM migration_status WHERE migration_name LIKE 'exam_layer%' ORDER BY id;

-- Cleanup temp table
DROP TEMPORARY TABLE IF EXISTS temp_duplicates;
