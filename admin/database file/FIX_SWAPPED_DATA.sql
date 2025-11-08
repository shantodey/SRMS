-- ==========================================
-- FIX: Swap student_id and exam_id that got mixed up
-- ==========================================

-- The old INSERT was putting values in wrong order:
-- It sent: (student_id=1, exam_id=2) but database got: (exam_id=1, student_id=2)

-- This query fixes the swapped data:
-- Look for results where student_id is very small (like < 50) which are actually exam_ids
-- And marks_obtained is 0

-- BEFORE RUNNING: Check what will be affected
SELECT
    id,
    exam_id,
    student_id,
    marks_obtained,
    total_marks,
    'Will be fixed' as status
FROM results
WHERE marks_obtained = 0.00 AND total_marks > 0;

-- If the above looks right, uncomment and run this:
-- DELETE FROM results WHERE marks_obtained = 0.00 AND total_marks > 0;

-- Then re-upload your Excel files with the fixed code!
