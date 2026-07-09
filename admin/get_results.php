<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'results' => [], 'message' => '', 'total' => 0];

try {
    // Get filter parameters
    $examType = isset($_GET['exam_type']) && $_GET['exam_type'] !== '' ? $_GET['exam_type'] : null;
    $semester = isset($_GET['semester']) && $_GET['semester'] !== '' ? intval($_GET['semester']) : null;
    $departmentId = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
    $subjectId = isset($_GET['subject']) && $_GET['subject'] !== '' ? intval($_GET['subject']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100; // Default 100 results

    // Build the SQL query with filters
    $sql = "SELECT r.*,
            s.student_name, s.index_no, s.board_roll,
            e.exam_type, e.title as exam_title,
            subj.subject_code, subj.subject_name,
            d.name as department_name
            FROM results r
            INNER JOIN students s ON r.student_id = s.id
            INNER JOIN exams e ON r.exam_id = e.id
            INNER JOIN subjects subj ON r.subject_id = subj.id
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE 1=1";

    $params = [];
    $types = "";

    // Add exam type filter
    if ($examType !== null) {
        $sql .= " AND e.exam_type = ?";
        $params[] = $examType;
        $types .= "s";
    }

    // Add semester filter
    if ($semester !== null) {
        $sql .= " AND r.semester = ?";
        $params[] = $semester;
        $types .= "i";
    }

    // Add department filter
    if ($departmentId !== null) {
        $sql .= " AND s.department_id = ?";
        $params[] = $departmentId;
        $types .= "i";
    }

    // Add subject filter
    if ($subjectId !== null) {
        $sql .= " AND r.subject_id = ?";
        $params[] = $subjectId;
        $types .= "i";
    }

    // Add search filter (search by student name OR index no)
    if (!empty($search)) {
        $sql .= " AND (s.student_name LIKE ? OR s.index_no LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    // Order by most recent first and limit
    $sql .= " ORDER BY r.created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['results'][] = [
                'id' => $row['id'],
                'student_id' => $row['student_id'],
                'student_name' => $row['student_name'],
                'index_no' => $row['index_no'],
                'board_roll' => $row['board_roll'],
                'exam_id' => $row['exam_id'],
                'exam_type' => $row['exam_type'],
                'exam_title' => $row['exam_title'],
                'subject_id' => $row['subject_id'],
                'subject_code' => $row['subject_code'],
                'subject_name' => $row['subject_name'],
                'marks_obtained' => $row['marks_obtained'],
                'total_marks' => $row['total_marks'],
                'grade' => $row['grade'],
                'semester' => $row['semester'],
                'department_name' => $row['department_name'],
                'created_at' => $row['created_at']
            ];
        }
        $response['success'] = true;
        $response['total'] = $result->num_rows;
    } else {
        $response['success'] = true;
        $response['message'] = 'No results found';
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
