<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (isset($_GET['search'])) {
    $search = $_GET['search'];
    
    // Prepare SQL to search by either index_no or board_roll
    $sql = "SELECT s.*, d.name as department_name, d.code as department_code, b.name as batch_name 
            FROM students s 
            JOIN departments d ON s.department_id = d.id 
            JOIN batches b ON s.batch_id = b.id 
            WHERE s.index_no = ? OR s.board_roll = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Get student's results
        $sql_results = "SELECT r.*, s.subject_code, s.subject_name, s.total_marks 
                       FROM results r 
                       JOIN subjects s ON r.subject_id = s.id 
                       WHERE r.student_id = ? AND r.semester = ?
                       ORDER BY s.subject_code";
        
        $stmt_results = $conn->prepare($sql_results);
        $stmt_results->bind_param("ii", $row['id'], $row['semester']);
        $stmt_results->execute();
        $results = $stmt_results->get_result();
        
        $subjects = [];
        $total_marks = 0;
        $obtained_marks = 0;
        
        while ($result_row = $results->fetch_assoc()) {
            $subjects[] = [
                'subject_code' => $result_row['subject_code'],
                'subject_name' => $result_row['subject_name'],
                'marks_obtained' => $result_row['marks_obtained'],
                'total_marks' => $result_row['total_marks'],
                'percentage' => ($result_row['marks_obtained'] / $result_row['total_marks']) * 100,
                'grade' => $result_row['grade']
            ];
            
            $total_marks += $result_row['total_marks'];
            $obtained_marks += $result_row['marks_obtained'];
        }
        
        // Calculate overall statistics
        $stats = [
            'total_subjects' => count($subjects),
            'total_marks' => $obtained_marks . '/' . $total_marks,
            'average_percentage' => $total_marks > 0 ? round(($obtained_marks / $total_marks) * 100, 2) : 0
        ];
        
        // Get overall grade
        $overall_percentage = $stats['average_percentage'];
        $sql_grade = "SELECT grade FROM grade_scale WHERE ? >= min_percentage ORDER BY min_percentage DESC LIMIT 1";
        $stmt_grade = $conn->prepare($sql_grade);
        $stmt_grade->bind_param("d", $overall_percentage);
        $stmt_grade->execute();
        $grade_result = $stmt_grade->get_result();
        $grade_row = $grade_result->fetch_assoc();
        $stats['overall_grade'] = $grade_row['grade'];
        
        echo json_encode([
            'success' => true,
            'student' => $row,
            'results' => $subjects,
            'stats' => $stats
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No student found with the given Index No or Board Roll'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Search parameter is required'
    ]);
}
?>
