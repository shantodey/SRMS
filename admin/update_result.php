<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultId = $_POST['result_id'] ?? '';
    $marksObtained = $_POST['marks_obtained'] ?? '';

    if (empty($resultId) || $marksObtained === '') {
        $response['message'] = 'Result ID and marks are required';
        echo json_encode($response);
        exit;
    }

    try {
        // Get current result to calculate new grade
        $sql = "SELECT * FROM results WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'Result not found';
            echo json_encode($response);
            exit;
        }

        $currentResult = $result->fetch_assoc();
        $stmt->close();

        // Get total marks from exam or calculate percentage
        $sql = "SELECT total_marks FROM exams WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $examId = $currentResult['exam_id'];
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        $examResult = $stmt->get_result();
        $exam = $examResult->fetch_assoc();
        $stmt->close();

        $totalMarks = $exam['total_marks'] ?? 100;

        // Calculate percentage and grade
        $percentage = ($marksObtained / $totalMarks) * 100;

        // Get grade from grade_scale table
        $sql = "SELECT grade FROM grade_scale WHERE min_percentage <= ? ORDER BY min_percentage DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("d", $percentage);
        $stmt->execute();
        $gradeResult = $stmt->get_result();
        $grade = 'F';
        if ($gradeResult->num_rows > 0) {
            $gradeRow = $gradeResult->fetch_assoc();
            $grade = $gradeRow['grade'];
        }
        $stmt->close();

        // Update the result
        $sql = "UPDATE results SET marks_obtained = ?, grade = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsi", $marksObtained, $grade, $resultId);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Result updated successfully!';
            $response['new_grade'] = $grade;
            $response['percentage'] = round($percentage, 2);
        } else {
            $response['message'] = 'Failed to update result';
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
