<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'students' => [], 'message' => '', 'total' => 0];

try {
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $department_id = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
    $batch_id = isset($_GET['batch']) && $_GET['batch'] !== '' ? intval($_GET['batch']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20; // Default show 20-30 recent students

    // Build the SQL query with filters
    $sql = "SELECT s.*, d.name as department_name, d.code as department_code, b.name as batch_name, b.year as batch_year
            FROM students s
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN batches b ON s.batch_id = b.id
            WHERE 1=1";

    $params = [];
    $types = "";

    // Add search filter (search by name OR board roll)
    if (!empty($search)) {
        $sql .= " AND (s.student_name LIKE ? OR s.board_roll LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    // Add department filter
    if ($department_id !== null) {
        $sql .= " AND s.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    // Add batch filter
    if ($batch_id !== null) {
        $sql .= " AND s.batch_id = ?";
        $params[] = $batch_id;
        $types .= "i";
    }

    // Order by ID descending (most recent first) and limit
    $sql .= " ORDER BY s.id DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";

    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $serial_no = 1; // Start serial number from 1
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['students'][] = [
                's_no' => $serial_no++, // Add serial number
                'id' => $row['id'],
                'index_no' => $row['index_no'],
                'student_name' => $row['student_name'],
                'roll_no' => $row['roll_no'],
                'board_roll' => $row['board_roll'] ?? '',
                'department_id' => $row['department_id'],
                'department_name' => $row['department_name'],
                'department_code' => $row['department_code'],
                'batch_id' => $row['batch_id'],
                'batch_name' => $row['batch_name'],
                'batch_year' => $row['batch_year'],
                'semester' => $row['semester'] ?? ''
            ];
        }
        $response['success'] = true;
        $response['total'] = $result->num_rows;
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
