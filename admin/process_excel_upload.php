<?php
// Start output buffering to prevent any output before JSON
ob_start();

// Suppress all errors from being displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Clear any output that might have been generated
ob_end_clean();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => [], 'stats' => ['success' => 0, 'failed' => 0, 'errors' => []]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['file'])) {
            throw new Exception('No file uploaded');
        }

        // Validate file type
        $allowedExtensions = ['xlsx', 'xls'];
        $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only .xlsx and .xls files are allowed.');
        }

        // Validate file size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES['file']['size'] > $maxFileSize) {
            throw new Exception('File size exceeds 5MB limit.');
        }

        $inputFileName = $_FILES['file']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header row
        $headers = array_shift($rows);

        // Start transaction
        $conn->begin_transaction();

        if ($_POST['type'] === 'students') {
            processStudents($conn, $rows, $response);
        } else if ($_POST['type'] === 'results') {
            processResults($conn, $rows, $response);
        } else {
            throw new Exception('Invalid upload type');
        }

        // Commit transaction
        $conn->commit();

        $response['success'] = true;
        $response['message'] = "Import completed. Success: {$response['stats']['success']}, Failed: {$response['stats']['failed']}";

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->connect_errno === 0) {
            $conn->rollback();
        }
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);

// Function to process student data
function processStudents($conn, $rows, &$response) {
    $rowNumber = 1; // Start from 1 (after header)

    foreach ($rows as $row) {
        $rowNumber++;

        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        try {
            // Validate required fields
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) ||
                empty($row[4]) || empty($row[5]) || empty($row[6])) {
                throw new Exception("Missing required fields in row $rowNumber");
            }

            $batchYear = trim($row[0]);
            $semester = trim($row[1]);
            $departmentCode = strtoupper(trim($row[2]));
            $studentName = trim($row[3]);
            $rollNo = trim($row[4]);
            $indexNo = trim($row[5]);
            $boardRoll = trim($row[6]);

            // Lookup or create batch
            $batchId = getBatchId($conn, $batchYear);
            if (!$batchId) {
                throw new Exception("Invalid batch year '$batchYear' in row $rowNumber");
            }

            // Lookup department
            $departmentId = getDepartmentId($conn, $departmentCode);
            if (!$departmentId) {
                throw new Exception("Invalid department code '$departmentCode' in row $rowNumber");
            }

            // Check for duplicate index_no or board_roll
            $checkSql = "SELECT id FROM students WHERE index_no = ? OR board_roll = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ss", $indexNo, $boardRoll);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Update existing student
                $existingStudent = $checkResult->fetch_assoc();
                $updateSql = "UPDATE students SET batch_id = ?, semester = ?, department_id = ?,
                             student_name = ?, roll_no = ?, index_no = ?, board_roll = ?
                             WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("isisssi", $batchId, $semester, $departmentId,
                                       $studentName, $rollNo, $indexNo, $boardRoll, $existingStudent['id']);
                $updateStmt->execute();
            } else {
                // Insert new student
                $insertSql = "INSERT INTO students (batch_id, semester, department_id, student_name, roll_no, index_no, board_roll)
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iisssss", $batchId, $semester, $departmentId,
                                       $studentName, $rollNo, $indexNo, $boardRoll);
                $insertStmt->execute();
            }

            $response['stats']['success']++;

            // Add to preview (first 5 rows only)
            if (count($response['data']) < 5) {
                $response['data'][] = $row;
            }

        } catch (Exception $e) {
            $response['stats']['failed']++;
            $response['stats']['errors'][] = "Row $rowNumber: " . $e->getMessage();
        }
    }
}

// Function to process results data
function processResults($conn, $rows, &$response) {
    $rowNumber = 1;

    foreach ($rows as $row) {
        $rowNumber++;

        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        try {
            // Validate required fields
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) ||
                empty($row[3]) || !isset($row[4]) || empty($row[5])) {
                throw new Exception("Missing required fields in row $rowNumber");
            }

            $indexNo = trim($row[0]);
            $boardRoll = trim($row[1]);
            $subjectCode = trim($row[2]);
            $subjectName = trim($row[3]);
            $marksObtained = floatval($row[4]);
            $totalMarks = floatval($row[5]);

            // Get student ID
            $studentId = getStudentId($conn, $indexNo, $boardRoll);
            if (!$studentId) {
                throw new Exception("Student not found with index_no '$indexNo' or board_roll '$boardRoll' in row $rowNumber");
            }

            // Get student's department and semester
            $studentInfo = getStudentInfo($conn, $studentId);

            // Get or create subject
            $subjectId = getOrCreateSubject($conn, $subjectCode, $subjectName, $studentInfo['department_id'], $studentInfo['semester'], $totalMarks);

            // Calculate grade
            $percentage = ($marksObtained / $totalMarks) * 100;
            $grade = calculateGrade($conn, $percentage);

            // Current date for exam_date
            $examDate = date('Y-m-d');

            // Check for duplicate result
            $checkSql = "SELECT id FROM results WHERE student_id = ? AND subject_id = ? AND semester = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("iii", $studentId, $subjectId, $studentInfo['semester']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Update existing result
                $existingResult = $checkResult->fetch_assoc();
                $updateSql = "UPDATE results SET marks_obtained = ?, grade = ?, exam_date = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("dssi", $marksObtained, $grade, $examDate, $existingResult['id']);
                $updateStmt->execute();
            } else {
                // Insert new result
                $insertSql = "INSERT INTO results (student_id, subject_id, marks_obtained, grade, semester, exam_date)
                             VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iidsis", $studentId, $subjectId, $marksObtained, $grade,
                                       $studentInfo['semester'], $examDate);
                $insertStmt->execute();
            }

            $response['stats']['success']++;

            // Add to preview (first 5 rows only)
            if (count($response['data']) < 5) {
                $response['data'][] = $row;
            }

        } catch (Exception $e) {
            $response['stats']['failed']++;
            $response['stats']['errors'][] = "Row $rowNumber: " . $e->getMessage();
        }
    }
}

// Helper function to get batch ID
function getBatchId($conn, $year) {
    $sql = "SELECT id FROM batches WHERE year = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }

    // Create batch if it doesn't exist
    $insertSql = "INSERT INTO batches (name, year) VALUES (?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $batchName = "Batch " . $year;
    $insertStmt->bind_param("si", $batchName, $year);
    $insertStmt->execute();

    return $conn->insert_id;
}

// Helper function to get department ID
function getDepartmentId($conn, $code) {
    $sql = "SELECT id FROM departments WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }

    return null;
}

// Helper function to get student ID
function getStudentId($conn, $indexNo, $boardRoll) {
    $sql = "SELECT id FROM students WHERE index_no = ? OR board_roll = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $indexNo, $boardRoll);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }

    return null;
}

// Helper function to get student info
function getStudentInfo($conn, $studentId) {
    $sql = "SELECT department_id, semester FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Helper function to get or create subject
function getOrCreateSubject($conn, $code, $name, $departmentId, $semester, $totalMarks) {
    $sql = "SELECT id FROM subjects WHERE subject_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }

    // Create subject if it doesn't exist
    $insertSql = "INSERT INTO subjects (subject_code, subject_name, department_id, semester, total_marks)
                  VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ssiii", $code, $name, $departmentId, $semester, $totalMarks);
    $insertStmt->execute();

    return $conn->insert_id;
}

// Helper function to calculate grade
function calculateGrade($conn, $percentage) {
    $sql = "SELECT grade FROM grade_scale WHERE min_percentage <= ? ORDER BY min_percentage DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("d", $percentage);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['grade'];
    }

    return 'F';
}