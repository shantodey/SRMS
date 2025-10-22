<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'stats' => [], 'message' => ''];

try {
    // Get total students count
    $sql = "SELECT COUNT(*) as total_students FROM students";
    $result = $conn->query($sql);
    $response['stats']['total_students'] = $result->fetch_assoc()['total_students'];

    // Get students with published results (distinct students)
    $sql = "SELECT COUNT(DISTINCT student_id) as students_with_results FROM results";
    $result = $conn->query($sql);
    $response['stats']['students_with_results'] = $result->fetch_assoc()['students_with_results'];

    // Get total departments
    $sql = "SELECT COUNT(*) as total_departments FROM departments";
    $result = $conn->query($sql);
    $response['stats']['total_departments'] = $result->fetch_assoc()['total_departments'];

    // Get total active notices
    $sql = "SELECT COUNT(*) as active_notices FROM notices WHERE status = 'published'";
    $result = $conn->query($sql);
    $response['stats']['active_notices'] = $result->fetch_assoc()['active_notices'];

    // Get recent results summary (last 5 students with results added)
    $sql = "SELECT
                s.student_name,
                s.index_no,
                d.name as department_name,
                d.code as department_code,
                s.semester,
                SUM(r.marks_obtained) as total_marks,
                SUM(r.total_marks) as total_possible,
                ROUND((SUM(r.marks_obtained) / SUM(r.total_marks)) * 100, 2) as percentage,
                MAX(r.created_at) as result_date
            FROM results r
            INNER JOIN students s ON r.student_id = s.id
            LEFT JOIN departments d ON s.department_id = d.id
            GROUP BY r.student_id, s.semester
            ORDER BY MAX(r.created_at) DESC
            LIMIT 5";
    $result = $conn->query($sql);
    $response['stats']['recent_results'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['stats']['recent_results'][] = $row;
    }

    // Get grade distribution
    $sql = "SELECT
                SUM(r.marks_obtained) as total_marks,
                SUM(r.total_marks) as total_possible,
                s.id as student_id,
                s.semester
            FROM results r
            INNER JOIN students s ON r.student_id = s.id
            GROUP BY r.student_id, s.semester";
    $result = $conn->query($sql);

    $grade_distribution = [
        'A+' => 0, 'A' => 0, 'A-' => 0,
        'B+' => 0, 'B' => 0, 'B-' => 0,
        'C+' => 0, 'C' => 0, 'C-' => 0,
        'D' => 0, 'F' => 0
    ];
    $total_students_with_results = 0;
    $total_percentage = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['total_possible'] > 0) {
            $percentage = ($row['total_marks'] / $row['total_possible']) * 100;
            $total_percentage += $percentage;
            $total_students_with_results++;

            // Determine grade based on percentage
            if ($percentage >= 80) $grade = 'A+';
            elseif ($percentage >= 75) $grade = 'A';
            elseif ($percentage >= 70) $grade = 'A-';
            elseif ($percentage >= 65) $grade = 'B+';
            elseif ($percentage >= 60) $grade = 'B';
            elseif ($percentage >= 55) $grade = 'B-';
            elseif ($percentage >= 50) $grade = 'C+';
            elseif ($percentage >= 45) $grade = 'C';
            elseif ($percentage >= 40) $grade = 'C-';
            elseif ($percentage >= 33) $grade = 'D';
            else $grade = 'F';

            $grade_distribution[$grade]++;
        }
    }

    $response['stats']['grade_distribution'] = $grade_distribution;
    $response['stats']['total_students_with_results'] = $total_students_with_results;
    $response['stats']['average_percentage'] = $total_students_with_results > 0
        ? round($total_percentage / $total_students_with_results, 2)
        : 0;

    // Calculate pass rate (assuming 33% is passing)
    $passing_students = 0;
    foreach ($grade_distribution as $grade => $count) {
        if ($grade !== 'F') {
            $passing_students += $count;
        }
    }
    $response['stats']['pass_rate'] = $total_students_with_results > 0
        ? round(($passing_students / $total_students_with_results) * 100, 2)
        : 0;

    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
