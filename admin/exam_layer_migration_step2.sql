-- =====================================================
-- EXAM LAYER MIGRATION - STEP 2
-- POPULATE EXAMS FROM EXISTING RESULTS
-- DO NOT RUN THIS DIRECTLY - Use migration_runner.php
-- =====================================================
-- This migration creates exam records from existing result data
-- Groups results by (exam_type, semester, subject_id, exam_date)
-- =====================================================

USE mawts;

-- Update migration status
INSERT INTO `migration_status` (`migration_name`, `status`, `started_at`)
VALUES ('exam_layer_step2', 'running', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE status = 'running', started_at = CURRENT_TIMESTAMP;

-- =====================================================
-- STEP 1: CREATE EXAMS FROM EXISTING RESULTS
-- =====================================================

-- This query groups existing results and creates one exam per unique combination
-- of (exam_type, semester, subject_id, exam_date, department_id)

INSERT INTO `exams` (
    `exam_type`,
    `exam_number`,
    `title`,
    `semester`,
    `department_id`,
    `subject_id`,
    `total_marks`,
    `exam_date`,
    `created_by`,
    `created_at`
)
SELECT DISTINCT
    -- exam_type: Use existing exam_type from results, default to 'Final' if NULL
    COALESCE(r.exam_type, 'Final') AS exam_type,

    -- exam_number: Set to 1 for migrated data (we don't have sequence info)
    1 AS exam_number,

    -- title: Generate descriptive title
    CONCAT(
        COALESCE(r.exam_type, 'Final'),
        ' - ',
        COALESCE(sub.subject_name, CONCAT('Semester ', r.semester)),
        ' - Sem ',
        r.semester,
        IFNULL(CONCAT(' - ', DATE_FORMAT(r.exam_date, '%b %Y')), '')
    ) AS title,

    -- semester: From results
    r.semester,

    -- department_id: Get from student's department
    s.department_id,

    -- subject_id: Keep subject reference
    r.subject_id,

    -- total_marks: Use from results or subject
    COALESCE(r.total_marks, sub.total_marks, 100) AS total_marks,

    -- exam_date: From results
    r.exam_date,

    -- created_by: NULL for migrated data (no creator info available)
    NULL AS created_by,

    -- created_at: Use earliest result creation date for this exam group
    MIN(r.created_at) AS created_at

FROM `results` r
INNER JOIN `students` s ON r.student_id = s.id
LEFT JOIN `subjects` sub ON r.subject_id = sub.id

-- Only migrate results that don't have exam_id yet
WHERE r.exam_id IS NULL

-- Group by the exam-defining attributes
GROUP BY
    COALESCE(r.exam_type, 'Final'),
    r.semester,
    s.department_id,
    r.subject_id,
    r.exam_date,
    COALESCE(r.total_marks, sub.total_marks, 100)

ORDER BY r.semester, r.exam_date, COALESCE(r.exam_type, 'Final');

-- =====================================================
-- STEP 2: LINK RESULTS TO EXAMS
-- =====================================================

-- Update results to link them to the newly created exams
-- Match by: exam_type, semester, department (via student), subject_id, exam_date, total_marks

UPDATE `results` r
INNER JOIN `students` s ON r.student_id = s.id
LEFT JOIN `subjects` sub ON r.subject_id = sub.id
INNER JOIN `exams` e ON
    e.exam_type = COALESCE(r.exam_type, 'Final')
    AND e.semester = r.semester
    AND e.department_id = s.department_id
    AND (
        (e.subject_id = r.subject_id) OR
        (e.subject_id IS NULL AND r.subject_id IS NULL)
    )
    AND (
        (e.exam_date = r.exam_date) OR
        (e.exam_date IS NULL AND r.exam_date IS NULL)
    )
    AND e.total_marks = COALESCE(r.total_marks, sub.total_marks, 100)
SET r.exam_id = e.id
WHERE r.exam_id IS NULL;

-- =====================================================
-- STEP 3: VERIFICATION
-- =====================================================

-- Check if all results now have exam_id
SET @unmapped_results = (
    SELECT COUNT(*)
    FROM results
    WHERE exam_id IS NULL
);

-- Check exam creation count
SET @exam_count = (SELECT COUNT(*) FROM exams);

-- Check result count
SET @result_count = (SELECT COUNT(*) FROM results);

-- Log verification results
SELECT
    @exam_count AS exams_created,
    @result_count AS total_results,
    @unmapped_results AS unmapped_results,
    CASE
        WHEN @unmapped_results = 0 THEN 'SUCCESS: All results mapped to exams'
        ELSE CONCAT('WARNING: ', @unmapped_results, ' results not mapped to exams')
    END AS migration_status;

-- =====================================================
-- STEP 4: UPDATE MIGRATION STATUS
-- =====================================================

UPDATE `migration_status`
SET
    status = CASE WHEN @unmapped_results = 0 THEN 'completed' ELSE 'failed' END,
    completed_at = CURRENT_TIMESTAMP,
    error_message = CASE
        WHEN @unmapped_results > 0
        THEN CONCAT(@unmapped_results, ' results could not be mapped to exams')
        ELSE NULL
    END
WHERE migration_name = 'exam_layer_step2';

-- Show final status
SELECT * FROM migration_status WHERE migration_name LIKE 'exam_layer%' ORDER BY id;
