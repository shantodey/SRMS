<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin only.']);
    exit();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => []];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // GET SUBJECTS LIST
            $department_id = isset($_GET['department']) && $_GET['department'] !== '' ? intval($_GET['department']) : null;
            $semester = isset($_GET['semester']) && $_GET['semester'] !== '' ? intval($_GET['semester']) : null;
            
            $sql = "SELECT s.*, d.name as department_name, d.code as department_code
                    FROM subjects s
                    LEFT JOIN departments d ON s.department_id = d.id
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if ($department_id !== null) {
                $sql .= " AND s.department_id = ?";
                $params[] = $department_id;
                $types .= "i";
            }
            
            if ($semester !== null) {
                $sql .= " AND s.semester = ?";
                $params[] = $semester;
                $types .= "i";
            }
            
            $sql .= " ORDER BY d.name, s.semester, s.subject_code";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $serial_no = 1;
            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = [
                    's_no' => $serial_no++,
                    'id' => $row['id'],
                    'subject_code' => $row['subject_code'],
                    'subject_name' => $row['subject_name'],
                    'department_id' => $row['department_id'],
                    'department_name' => $row['department_name'],
                    'department_code' => $row['department_code'],
                    'semester' => $row['semester'],
                    'total_marks' => $row['total_marks'],
                    'created_at' => $row['created_at']
                ];
            }
            
            $response['success'] = true;
            $response['data'] = $subjects;
            $response['total'] = count($subjects);
            $stmt->close();
            break;

        case 'create':
            // CREATE NEW SUBJECT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
            $subject_name = trim($_POST['subject_name'] ?? '');
            $department_id = intval($_POST['department_id'] ?? 0);
            $semester = intval($_POST['semester'] ?? 0);
            $total_marks = intval($_POST['total_marks'] ?? 100);
            
            if (empty($subject_code) || empty($subject_name) || $department_id <= 0 || $semester <= 0) {
                throw new Exception('All fields are required');
            }
            
            if ($semester < 1 || $semester > 8) {
                throw new Exception('Semester must be between 1 and 8');
            }
            
            // Check if subject code already exists
            $checkSql = "SELECT id FROM subjects WHERE subject_code = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $subject_code);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                throw new Exception('Subject code already exists. Please use a unique code.');
            }
            $checkStmt->close();
            
            // Insert new subject
            $insertSql = "INSERT INTO subjects (subject_code, subject_name, department_id, semester, total_marks) 
                         VALUES (?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("ssiii", $subject_code, $subject_name, $department_id, $semester, $total_marks);
            
            if ($insertStmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Subject added successfully';
                $response['data'] = ['id' => $conn->insert_id];
            } else {
                throw new Exception('Failed to add subject');
            }
            
            $insertStmt->close();
            break;

        case 'update':
            // UPDATE SUBJECT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $subject_id = intval($_POST['subject_id'] ?? 0);
            $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
            $subject_name = trim($_POST['subject_name'] ?? '');
            $department_id = intval($_POST['department_id'] ?? 0);
            $semester = intval($_POST['semester'] ?? 0);
            $total_marks = intval($_POST['total_marks'] ?? 100);
            
            if ($subject_id <= 0 || empty($subject_code) || empty($subject_name) || $department_id <= 0 || $semester <= 0) {
                throw new Exception('All fields are required');
            }
            
            if ($semester < 1 || $semester > 8) {
                throw new Exception('Semester must be between 1 and 8');
            }
            
            // Check if subject code already exists (excluding current subject)
            $checkSql = "SELECT id FROM subjects WHERE subject_code = ? AND id != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("si", $subject_code, $subject_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                throw new Exception('Subject code already exists. Please use a unique code.');
            }
            $checkStmt->close();
            
            // Update subject
            $updateSql = "UPDATE subjects 
                         SET subject_code = ?, subject_name = ?, department_id = ?, semester = ?, total_marks = ? 
                         WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssiiii", $subject_code, $subject_name, $department_id, $semester, $total_marks, $subject_id);
            
            if ($updateStmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Subject updated successfully';
            } else {
                throw new Exception('Failed to update subject');
            }
            
            $updateStmt->close();
            break;

        case 'delete':
            // DELETE SUBJECT
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $subject_id = intval($_POST['subject_id'] ?? 0);
            
            if ($subject_id <= 0) {
                throw new Exception('Invalid subject ID');
            }
            
            // Check if subject has results
            $checkSql = "SELECT COUNT(*) as count FROM results WHERE subject_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $subject_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $row = $checkResult->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception('Cannot delete subject. It has ' . $row['count'] . ' result(s) associated with it.');
            }
            $checkStmt->close();
            
            // Delete subject
            $deleteSql = "DELETE FROM subjects WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $subject_id);
            
            if ($deleteStmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Subject deleted successfully';
            } else {
                throw new Exception('Failed to delete subject');
            }
            
            $deleteStmt->close();
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>