<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $grades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];
        $updated = 0;

        foreach ($grades as $grade) {
            $field_name = 'grade_' . $grade;
            if (isset($_POST[$field_name])) {
                $min_percentage = floatval($_POST[$field_name]);

                // Update grade scale
                $sql = "UPDATE grade_scale SET min_percentage = ? WHERE grade = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ds", $min_percentage, $grade);

                if ($stmt->execute()) {
                    $updated++;
                }
                $stmt->close();
            }
        }

        $conn->commit();

        $response['success'] = true;
        $response['message'] = "Successfully updated $updated grade thresholds";

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>