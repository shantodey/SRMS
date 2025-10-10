<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Get search parameter from POST or GET
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = $_POST['search'] ?? '';
} else {
    $search = $_GET['search'] ?? '';
}

if (empty($search)) {
    echo json_encode([
        'success' => false,
        'message' => 'Search parameter is required'
    ]);
    exit;
}

try {
    // Search for student by index_no or board_roll
    $sql = "SELECT s.*, d.name as department_name, d.code as department_code, b.name as batch_name, b.year as batch_year
            FROM students s
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN batches b ON s.batch_id = b.id
            WHERE s.index_no = ? OR s.board_roll = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $student_id = $row['id'];
        $semester = $row['semester'];

        // Get student's results with subject details
        $sql_results = "SELECT r.*, s.subject_code, s.subject_name, s.total_marks as subject_total_marks
                       FROM results r
                       LEFT JOIN subjects s ON r.subject_id = s.id
                       WHERE r.student_id = ? AND r.semester = ?
                       ORDER BY s.subject_code";

        $stmt_results = $conn->prepare($sql_results);
        $stmt_results->bind_param("ii", $student_id, $semester);
        $stmt_results->execute();
        $results_data = $stmt_results->get_result();

        $subjects = [];
        $total_marks_obtained = 0;
        $total_marks_possible = 0;

        while ($result_row = $results_data->fetch_assoc()) {
            // Use total_marks from results table if available, otherwise from subjects table
            $total_marks = $result_row['total_marks'] ?? $result_row['subject_total_marks'] ?? 100;
            $marks_obtained = $result_row['marks_obtained'];

            // Calculate percentage
            $percentage = $total_marks > 0 ? round(($marks_obtained / $total_marks) * 100, 2) : 0;

            // Get grade based on percentage using grade_scale table
            $grade = getGradeFromPercentage($percentage, $conn);

            $subjects[] = [
                'subject_code' => $result_row['subject_code'],
                'subject_name' => $result_row['subject_name'],
                'marks_obtained' => $marks_obtained,
                'total_marks' => $total_marks,
                'percentage' => $percentage,
                'grade' => $grade
            ];

            $total_marks_possible += $total_marks;
            $total_marks_obtained += $marks_obtained;
        }

        // Calculate overall statistics
        $average_percentage = $total_marks_possible > 0 ? round(($total_marks_obtained / $total_marks_possible) * 100, 2) : 0;
        $overall_grade = getGradeFromPercentage($average_percentage, $conn);

        $summary = [
            'total_subjects' => count($subjects),
            'total_marks_obtained' => $total_marks_obtained,
            'total_marks_possible' => $total_marks_possible,
            'average_percentage' => $average_percentage,
            'overall_grade' => $overall_grade
        ];

        echo json_encode([
            'success' => true,
            'student' => [
                'id' => $row['id'],
                'student_name' => $row['student_name'],
                'index_no' => $row['index_no'],
                'board_roll' => $row['board_roll'],
                'roll_no' => $row['roll_no'],
                'department_name' => $row['department_name'],
                'department_code' => $row['department_code'],
                'batch_name' => $row['batch_name'],
                'batch_year' => $row['batch_year'],
                'semester' => $row['semester']
            ],
            'results' => $subjects,
            'summary' => $summary
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No student found with the given Index Number or Board Roll. Please check and try again.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching results. Please try again later.'
    ]);
}

// Function to get grade based on percentage from grade_scale table
function getGradeFromPercentage($percentage, $conn) {
    try {
        $sql = "SELECT grade FROM grade_scale WHERE ? >= min_percentage ORDER BY min_percentage DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("d", $percentage);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['grade'];
        }
        return 'F'; // Default grade if no match found
    } catch (Exception $e) {
        return 'N/A';
    }
}
?>
