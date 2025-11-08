<?php
/**
 * Complete Installation - All Steps Combined
 * Runs all installation steps in one go
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn->begin_transaction();

    // Step 1: Create Tables
    $conn->query("CREATE TABLE IF NOT EXISTS `exams` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `exam_type` ENUM('Final', 'Midterm', 'ClassTest', 'Assignment', 'Quiz') NOT NULL,
        `exam_number` INT(11) NULL,
        `title` VARCHAR(255) NOT NULL,
        `semester` INT(11) NOT NULL,
        `department_id` INT(11) NOT NULL,
        `subject_id` INT(11) NULL,
        `total_marks` INT(11) NULL,
        `exam_date` DATE NULL,
        `created_by` INT(11) NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("ALTER TABLE `results` ADD COLUMN IF NOT EXISTS `exam_id` INT(11) NULL AFTER `id`");

    $conn->query("CREATE TABLE IF NOT EXISTS `upload_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `exam_id` INT(11) NULL,
        `filename` VARCHAR(255) NOT NULL,
        `uploader_id` INT(11) NULL,
        `uploader_type` ENUM('admin', 'teacher') NULL,
        `rows_total` INT(11) NOT NULL DEFAULT 0,
        `rows_success` INT(11) NOT NULL DEFAULT 0,
        `rows_failed` INT(11) NOT NULL DEFAULT 0,
        `error_log` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS `result_audit_log` (
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
        `reason` VARCHAR(255) NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Step 2: Migrate Data
    $conn->query("INSERT INTO `exams` (
        `exam_type`, `exam_number`, `title`, `semester`, `department_id`, `subject_id`, `total_marks`, `exam_date`, `created_at`
    )
    SELECT DISTINCT
        COALESCE(r.exam_type, 'Final') AS exam_type,
        1 AS exam_number,
        CONCAT(
            COALESCE(r.exam_type, 'Final'), ' - ',
            COALESCE(sub.subject_name, CONCAT('Semester ', r.semester)), ' - Sem ', r.semester
        ) AS title,
        r.semester,
        s.department_id,
        r.subject_id,
        COALESCE(r.total_marks, sub.total_marks, 100) AS total_marks,
        r.exam_date,
        MIN(r.created_at) AS created_at
    FROM `results` r
    INNER JOIN `students` s ON r.student_id = s.id
    LEFT JOIN `subjects` sub ON r.subject_id = sub.id
    WHERE r.exam_id IS NULL
    GROUP BY
        COALESCE(r.exam_type, 'Final'), r.semester, s.department_id, r.subject_id, r.exam_date,
        COALESCE(r.total_marks, sub.total_marks, 100)");

    $examsCreated = $conn->affected_rows;

    $conn->query("UPDATE `results` r
    INNER JOIN `students` s ON r.student_id = s.id
    LEFT JOIN `subjects` sub ON r.subject_id = sub.id
    INNER JOIN `exams` e ON
        e.exam_type = COALESCE(r.exam_type, 'Final')
        AND e.semester = r.semester
        AND e.department_id = s.department_id
        AND ((e.subject_id = r.subject_id) OR (e.subject_id IS NULL AND r.subject_id IS NULL))
        AND e.total_marks = COALESCE(r.total_marks, sub.total_marks, 100)
    SET r.exam_id = e.id
    WHERE r.exam_id IS NULL");

    $resultsMapped = $conn->affected_rows;

    // Step 3: Add Constraints (check first to avoid errors)
    $result = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results'
                           AND CONSTRAINT_NAME = 'fk_results_exam'");

    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE `results`
                     ADD CONSTRAINT `fk_results_exam`
                     FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE");
    }

    $result = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results'
                           AND CONSTRAINT_NAME = 'uk_student_exam_subject'");

    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE `results`
                     ADD UNIQUE KEY `uk_student_exam_subject` (`student_id`, `exam_id`, `subject_id`)");
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Installation completed successfully',
        'exams_created' => $examsCreated,
        'results_mapped' => $resultsMapped
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
