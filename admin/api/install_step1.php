<?php
/**
 * Installation Step 1: Create Tables
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn->begin_transaction();

    // Create exams table
    $sql = "CREATE TABLE IF NOT EXISTS `exams` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `exam_type` ENUM('Final', 'Midterm', 'ClassTest', 'Assignment', 'Quiz') NOT NULL,
        `exam_number` INT(11) NULL COMMENT 'Sequence number for ClassTests (1,2,3...). NULL or 1 for Final/Midterm',
        `title` VARCHAR(255) NOT NULL COMMENT 'Human-readable title like \"CT-3 - Data Structures - Sem 6\"',
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores exam instances'";

    $conn->query($sql);

    // Add exam_id to results table
    $conn->query("ALTER TABLE `results` ADD COLUMN IF NOT EXISTS `exam_id` INT(11) NULL AFTER `id`");
    $conn->query("ALTER TABLE `results` ADD INDEX IF NOT EXISTS `idx_exam_id` (`exam_id`)");

    // Create upload_logs table
    $sql = "CREATE TABLE IF NOT EXISTS `upload_logs` (
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
        PRIMARY KEY (`id`),
        FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
        INDEX `idx_uploader` (`uploader_id`, `uploader_type`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($sql);

    // Create result_audit_log table
    $sql = "CREATE TABLE IF NOT EXISTS `result_audit_log` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($sql);

    // Create migration_status table
    $sql = "CREATE TABLE IF NOT EXISTS `migration_status` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `migration_name` VARCHAR(100) NOT NULL UNIQUE,
        `status` ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
        `started_at` TIMESTAMP NULL,
        `completed_at` TIMESTAMP NULL,
        `error_message` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `migration_name` (`migration_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($sql);

    // Record migration status
    $conn->query("INSERT INTO `migration_status` (`migration_name`, `status`, `completed_at`)
                  VALUES ('exam_layer_step1', 'completed', CURRENT_TIMESTAMP)
                  ON DUPLICATE KEY UPDATE status = 'completed', completed_at = CURRENT_TIMESTAMP");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tables created successfully',
        'tables_created' => ['exams', 'upload_logs', 'result_audit_log', 'migration_status']
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
