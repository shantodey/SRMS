-- ==========================================
-- STEP 1: Check current database structure
-- ==========================================
SELECT 'Current results table structure:' as '';
DESCRIBE results;

SELECT '' as '';
SELECT 'Sample of current data (showing the problem):' as '';
SELECT id, exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester
FROM results
ORDER BY id DESC
LIMIT 5;

-- ==========================================
-- STEP 2: Fix marks_obtained column size
-- ==========================================
SELECT '' as '';
SELECT 'Fixing marks_obtained column size from decimal(5,2) to decimal(10,2)...' as '';

ALTER TABLE results
MODIFY COLUMN marks_obtained DECIMAL(10,2) NOT NULL;

SELECT 'Done! Column size fixed.' as '';

-- ==========================================
-- STEP 3: Verify the fix
-- ==========================================
SELECT '' as '';
SELECT 'Verifying column type after fix:' as '';
SHOW COLUMNS FROM results LIKE 'marks_obtained';

-- ==========================================
-- STEP 4: Clear bad data (optional - run if you want clean slate)
-- ==========================================
-- UNCOMMENT BELOW TO DELETE ALL BAD DATA:
-- DELETE FROM results WHERE marks_obtained = 999.99;
-- SELECT 'Bad data deleted. You can now re-upload Excel files.' as '';

-- ==========================================
-- INSTRUCTIONS:
-- 1. Run this in phpMyAdmin or MySQL command line
-- 2. After running, DELETE all bad results from database
-- 3. Re-upload your Excel files
-- ==========================================
