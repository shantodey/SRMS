-- =====================================================
-- MAWTS SRMS Database Migration V2
-- Enhancements for Universal Search & Better Performance
-- =====================================================
-- Run this script to upgrade your existing database
-- This is SAFE - No data will be lost!
-- =====================================================

USE mawts;

-- =====================================================
-- 1. ADD NEW COLUMNS TO STUDENTS TABLE
-- =====================================================

-- Add status column to track student lifecycle
ALTER TABLE students
ADD COLUMN IF NOT EXISTS status ENUM('active', 'graduated', 'dropped')
DEFAULT 'active'
AFTER semester;

-- Add email and phone for future features (optional)
ALTER TABLE students
ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL
AFTER board_roll;

ALTER TABLE students
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL
AFTER email;

-- Add photo path for student images
ALTER TABLE students
ADD COLUMN IF NOT EXISTS photo VARCHAR(255) NULL
AFTER phone;

-- Add updated_at timestamp
ALTER TABLE students
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
AFTER created_at;

-- =====================================================
-- 2. ENHANCE RESULTS TABLE
-- =====================================================

-- Add percentage column (pre-calculated for faster display)
ALTER TABLE results
ADD COLUMN IF NOT EXISTS percentage DECIMAL(5,2) NULL
AFTER marks_obtained;

-- Add total_marks to results (backup if subject is deleted)
ALTER TABLE results
ADD COLUMN IF NOT EXISTS total_marks INT DEFAULT 100
AFTER percentage;

-- Add exam_type for categorizing results
ALTER TABLE results
MODIFY COLUMN exam_date DATE NULL;

ALTER TABLE results
ADD COLUMN IF NOT EXISTS exam_type ENUM('Final', 'Midterm', 'Assignment', 'Quiz')
DEFAULT 'Final'
AFTER semester;

-- =====================================================
-- 3. FIX NOTICES TABLE
-- =====================================================

-- Make created_by nullable (notices don't need admin link)
ALTER TABLE notices
MODIFY COLUMN created_by INT(11) NULL;

-- Add priority for important notices
ALTER TABLE notices
ADD COLUMN IF NOT EXISTS priority ENUM('normal', 'important', 'urgent')
DEFAULT 'normal'
AFTER status;

-- Add expiry date for notices
ALTER TABLE notices
ADD COLUMN IF NOT EXISTS expiry_date DATE NULL
AFTER publish_date;

-- =====================================================
-- 4. ADD SEARCH INDEXES FOR FAST QUERIES
-- =====================================================

-- Composite index for universal search
ALTER TABLE students
ADD INDEX IF NOT EXISTS idx_universal_search (student_name, index_no, board_roll, roll_no);

-- Individual index for name search (for LIKE queries)
ALTER TABLE students
ADD INDEX IF NOT EXISTS idx_student_name (student_name);

-- Index for status filtering
ALTER TABLE students
ADD INDEX IF NOT EXISTS idx_status (status);

-- Composite index for batch and department queries
ALTER TABLE students
ADD INDEX IF NOT EXISTS idx_batch_dept (batch_id, department_id);

-- Index for results percentage (for toppers, reports)
ALTER TABLE results
ADD INDEX IF NOT EXISTS idx_percentage (percentage DESC);

-- Index for semester-wise queries
ALTER TABLE results
ADD INDEX IF NOT EXISTS idx_semester_student (semester, student_id);

-- =====================================================
-- 5. UPDATE EXISTING DATA
-- =====================================================

-- Calculate and update percentage for existing results
UPDATE results r
JOIN subjects s ON r.subject_id = s.id
SET
    r.percentage = ROUND((r.marks_obtained / s.total_marks) * 100, 2),
    r.total_marks = s.total_marks
WHERE r.percentage IS NULL OR r.total_marks IS NULL;

-- Set all existing students as 'active'
UPDATE students
SET status = 'active'
WHERE status IS NULL;

-- =====================================================
-- 6. CREATE VIEW FOR EASY QUERYING
-- =====================================================

-- Drop view if exists
DROP VIEW IF EXISTS v_student_results;

-- Create comprehensive view for student results
CREATE VIEW v_student_results AS
SELECT
    s.id as student_id,
    s.student_name,
    s.index_no,
    s.board_roll,
    s.roll_no,
    s.semester as current_semester,
    s.status,
    d.name as department_name,
    d.code as department_code,
    b.name as batch_name,
    b.year as batch_year,
    r.id as result_id,
    r.semester as result_semester,
    r.marks_obtained,
    r.total_marks,
    r.percentage,
    r.grade,
    r.exam_type,
    r.exam_date,
    sub.subject_code,
    sub.subject_name
FROM students s
LEFT JOIN departments d ON s.department_id = d.id
LEFT JOIN batches b ON s.batch_id = b.id
LEFT JOIN results r ON s.id = r.student_id
LEFT JOIN subjects sub ON r.subject_id = sub.id
ORDER BY s.id, r.semester, sub.subject_code;

-- =====================================================
-- 7. CREATE STORED PROCEDURE FOR UNIVERSAL SEARCH
-- =====================================================

DROP PROCEDURE IF EXISTS sp_search_students;

DELIMITER //
CREATE PROCEDURE sp_search_students(IN search_term VARCHAR(100))
BEGIN
    SELECT DISTINCT
        s.id,
        s.student_name,
        s.index_no,
        s.board_roll,
        s.roll_no,
        s.semester,
        s.status,
        d.name as department_name,
        d.code as department_code,
        b.name as batch_name,
        b.year as batch_year
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN batches b ON s.batch_id = b.id
    WHERE
        s.student_name LIKE CONCAT('%', search_term, '%')
        OR s.index_no = search_term
        OR s.board_roll = search_term
        OR s.roll_no = search_term
        OR d.code = search_term
        OR d.name LIKE CONCAT('%', search_term, '%')
        OR b.year = search_term
        OR b.name LIKE CONCAT('%', search_term, '%')
    ORDER BY
        CASE
            WHEN s.index_no = search_term THEN 1
            WHEN s.board_roll = search_term THEN 2
            WHEN s.roll_no = search_term THEN 3
            ELSE 4
        END,
        s.student_name;
END//
DELIMITER ;


OPTIMIZE TABLE students;
OPTIMIZE TABLE results;
OPTIMIZE TABLE subjects;
OPTIMIZE TABLE batches;
OPTIMIZE TABLE departments;
OPTIMIZE TABLE notices;
OPTIMIZE TABLE grade_scale;



SELECT 'Database migration completed successfully!' as STATUS;
SELECT COUNT(*) as total_students FROM students;
SELECT COUNT(*) as total_results FROM results;
SELECT COUNT(*) as total_subjects FROM subjects;
