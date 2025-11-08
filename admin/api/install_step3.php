<?php
/**
 * Installation Step 3: Add Constraints
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

    // Update migration status
    $conn->query("INSERT INTO `migration_status` (`migration_name`, `status`, `started_at`)
                  VALUES ('exam_layer_step3', 'running', CURRENT_TIMESTAMP)
                  ON DUPLICATE KEY UPDATE status = 'running', started_at = CURRENT_TIMESTAMP");

    // Check for duplicates before adding unique constraint
    $result = $conn->query("SELECT COUNT(*) as count FROM (
        SELECT student_id, exam_id, subject_id, COUNT(*) as c
        FROM results
        WHERE exam_id IS NOT NULL
        GROUP BY student_id, exam_id, subject_id
        HAVING c > 1
    ) t");
    $duplicateCount = $result->fetch_assoc()['count'];

    if ($duplicateCount > 0) {
        throw new Exception("$duplicateCount duplicate result groups found. Cannot add unique constraint.");
    }

    // Add foreign key constraint
    $result = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                           WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME = 'results'
                           AND CONSTRAINT_NAME = 'fk_results_exam'");

    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE `results`
                     ADD CONSTRAINT `fk_results_exam`
                     FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`)
                     ON DELETE CASCADE");
    }

    // Add unique constraint
    $result = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                           WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME = 'results'
                           AND CONSTRAINT_NAME = 'uk_student_exam_subject'");

    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE `results`
                     ADD UNIQUE KEY `uk_student_exam_subject` (`student_id`, `exam_id`, `subject_id`)");
    }

    // Add additional indexes
    $conn->query("ALTER TABLE `results`
                 ADD INDEX IF NOT EXISTS `idx_student_exam` (`student_id`, `exam_id`)");

    $conn->query("ALTER TABLE `exams`
                 ADD INDEX IF NOT EXISTS `idx_subject_semester_type` (`subject_id`, `semester`, `exam_type`)");

    $conn->query("ALTER TABLE `exams`
                 ADD INDEX IF NOT EXISTS `idx_dept_sem_type` (`department_id`, `semester`, `exam_type`)");

    // Update migration status
    $conn->query("UPDATE `migration_status`
                  SET status = 'completed', completed_at = CURRENT_TIMESTAMP
                  WHERE migration_name = 'exam_layer_step3'");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Constraints added successfully',
        'duplicates_found' => $duplicateCount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
