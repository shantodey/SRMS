-- ==========================================
-- FIX: Check if marks_obtained and total_marks are swapped
-- ==========================================

-- First, check current data to see if values are swapped
SELECT
    id,
    marks_obtained,
    total_marks,
    CASE
        WHEN marks_obtained > total_marks THEN '❌ SWAPPED! (marks > total)'
        WHEN marks_obtained = 0 AND total_marks > 0 THEN '❌ marks_obtained is 0'
        ELSE '✓ OK'
    END as status
FROM results
ORDER BY id DESC
LIMIT 10;

-- If you see SWAPPED, run this to fix existing data:
-- UPDATE results
-- SET
--     marks_obtained = total_marks,
--     total_marks = marks_obtained
-- WHERE marks_obtained > total_marks OR (marks_obtained = 0 AND total_marks > 0);

-- Note: The above won't work due to simultaneous update
-- Use this instead:
UPDATE results
SET
    marks_obtained = (@temp := marks_obtained),
    marks_obtained = total_marks,
    total_marks = @temp
WHERE marks_obtained > total_marks OR (marks_obtained = 0 AND total_marks > 0 AND total_marks < 100);
