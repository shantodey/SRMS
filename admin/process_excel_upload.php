<?php
// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Start output buffering
ob_start();

session_start();
require_once '../config/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Helper function for upload errors
function upload_error_message($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}

// Clear any previous output and set JSON header
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Initialize response array
$response = ['success' => false, 'message' => '', 'data' => [], 'stats' => ['success' => 0, 'failed' => 0, 'errors' => []]];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Verify file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = isset($_FILES['file']) ? upload_error_message($_FILES['file']['error']) : 'No file uploaded';
        throw new Exception($error);
    }

    if (!isset($_POST['type'])) {
        throw new Exception('Upload type not specified');
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
    
    try {
        $spreadsheet = IOFactory::load($inputFileName);
    } catch (Exception $e) {
        throw new Exception('Error reading file: ' . $e->getMessage());
    }
    
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    if (count($rows) <= 1) {
        throw new Exception('File appears to be empty or contains only headers');
    }

    // Remove header row
    $headers = array_shift($rows);

    // Start transaction
    $conn->begin_transaction();

    if ($_POST['type'] === 'students') {
        processStudents($conn, $rows, $response);
    } else if ($_POST['type'] === 'results') {
        // Get exam layer parameters
        $examType = $_POST['exam_type'] ?? 'Final';
        $semester = $_POST['semester'] ?? null;
        $departmentId = $_POST['department_id'] ?? null;
        $subjectId = $_POST['subject_id'] ?? null; // For ClassTest
        $testNumber = $_POST['test_number'] ?? 1;

        if (!$semester || !$departmentId) {
            throw new Exception('Semester and Department are required for result upload');
        }

        // Generate unique upload ID
        $uploadId = uniqid('upload_', true);
        $response['upload_info'] = [
            'upload_id' => $uploadId,
            'timestamp' => date('Y-m-d H:i:s'),
            'exam_type' => $examType,
            'semester' => $semester
        ];

        processResults($conn, $rows, $response, $examType, $semester, $departmentId, $subjectId, $testNumber, $uploadId);

        // Save upload history (create table if doesn't exist)
        createUploadHistoryTable($conn);
        saveUploadHistory($conn, $uploadId, $examType, $semester, $departmentId, $subjectId, $response['stats']['success']);
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
    error_log('Excel upload error: ' . $e->getMessage());
}

// Ensure proper JSON encoding
try {
    $jsonResponse = json_encode($response, JSON_THROW_ON_ERROR);
    if ($jsonResponse === false) {
        throw new Exception(json_last_error_msg());
    }
    echo $jsonResponse;
} catch (Exception $e) {
    // If JSON encoding fails, send a basic error response
    echo json_encode([
        'success' => false,
        'message' => 'Error encoding response: ' . $e->getMessage()
    ]);
}

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
            // Validate required fields (roll number is optional)
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) ||
                empty($row[5]) || empty($row[6])) {
                throw new Exception("Missing required fields in row $rowNumber. Required: Batch Year, Semester, Department Code, Student Name, Index No, Board Roll");
            }

            $batchYear = trim($row[0]);
            $semester = trim($row[1]);
            $departmentCode = strtoupper(trim($row[2]));
            $studentName = trim($row[3]);
            $rollNo = !empty($row[4]) ? trim($row[4]) : ''; // Roll number is optional
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
                $updateStmt->bind_param("isisssis", $batchId, $semester, $departmentId,
                                       $studentName, $rollNo, $indexNo, $boardRoll, $existingStudent['id']);
                $updateStmt->execute();
            } else {
                // Insert new student
                $insertSql = "INSERT INTO students (batch_id, semester, department_id, student_name, roll_no, index_no, board_roll)
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iiissss", $batchId, $semester, $departmentId,
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

// Function to process results data with exam layer support
function processResults($conn, $rows, &$response, $examType, $semester, $departmentId, $subjectId = null, $testNumber = 1, $uploadId = null) {
    $rowNumber = 1;
    $examId = null; // Will be set after creating/finding the exam

    foreach ($rows as $row) {
        $rowNumber++;

        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }

        try {
            // UNIFIED EXCEL FORMAT FOR ALL EXAM TYPES:
            // Column 0: Index No
            // Column 1: Board Roll
            // Column 2: Subject Code (REQUIRED for all exam types)
            // Column 3: Subject Name
            // Column 4: Marks Obtained
            // Column 5: Total Marks

            // Read all columns the same way
            $indexNo = !empty($row[0]) ? trim($row[0]) : '';
            $boardRoll = !empty($row[1]) ? trim($row[1]) : '';
            $subjectCode = !empty($row[2]) ? trim($row[2]) : '';
            $subjectName = !empty($row[3]) ? trim($row[3]) : '';
            $marksObtained = !empty($row[4]) ? floatval($row[4]) : 0;
            $totalMarks = !empty($row[5]) ? floatval($row[5]) : 0;

            // Validate required fields
            if (empty($indexNo) && empty($boardRoll)) {
                throw new Exception("Missing Index No or Board Roll in row $rowNumber");
            }

            if (empty($subjectCode)) {
                throw new Exception("Missing Subject Code in row $rowNumber");
            }

            if ($totalMarks <= 0) {
                throw new Exception("Invalid or missing Total Marks in row $rowNumber");
            }

            // All exam types now use subject code from Excel file
            $currentSubjectId = getSubjectIdByCode($conn, $subjectCode, $departmentId, $semester);
            if (!$currentSubjectId) {
                throw new Exception("Subject not found with code '$subjectCode' for department/semester in row $rowNumber");
            }

            // Get student ID
            $studentId = getStudentId($conn, $indexNo, $boardRoll);
            if (!$studentId) {
                throw new Exception("Student not found with index_no '$indexNo' or board_roll '$boardRoll' in row $rowNumber");
            }

            // Create or get exam record
            // For ClassTest/Assignment: each subject has its own exam
            // For Final/Midterm: one exam per semester (subject_id is null in exam record)
            if ($examType === 'ClassTest' || $examType === 'Assignment') {
                // Get exam for this specific subject
                $examId = getOrCreateExam($conn, $examType, $semester, $departmentId, $currentSubjectId, $testNumber, $totalMarks);
            } else {
                // For Final/Midterm, create exam once per upload (semester-wide)
                if ($examId === null) {
                    $examId = getOrCreateExam($conn, $examType, $semester, $departmentId, null, $testNumber, $totalMarks);
                }
            }

            // Ensure totalMarks is not zero to prevent division by zero
            if ($totalMarks <= 0) {
                throw new Exception("Invalid total marks (must be greater than 0) in row $rowNumber");
            }

            // Calculate percentage and grade
            $percentage = ($marksObtained / $totalMarks) * 100;
            $grade = calculateGrade($conn, $percentage);

            // Check for duplicate result
            $checkSql = "SELECT id FROM results WHERE student_id = ? AND exam_id = ? AND subject_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("iii", $studentId, $examId, $currentSubjectId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Update existing result
                $existingResult = $checkResult->fetch_assoc();
                $updateSql = "UPDATE results SET marks_obtained = ?, total_marks = ?, grade = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ddsi", $marksObtained, $totalMarks, $grade, $existingResult['id']);
                $updateStmt->execute();
            } else {
                // Insert new result
                $insertSql = "INSERT INTO results (exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iiiddsis", $examId, $studentId, $currentSubjectId, $marksObtained, $totalMarks, $grade, $semester, $uploadId);
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
    // Build dynamic query based on which identifier is provided
    if (!empty($indexNo) && !empty($boardRoll)) {
        $sql = "SELECT id FROM students WHERE index_no = ? OR board_roll = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $indexNo, $boardRoll);
    } elseif (!empty($indexNo)) {
        $sql = "SELECT id FROM students WHERE index_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $indexNo);
    } elseif (!empty($boardRoll)) {
        $sql = "SELECT id FROM students WHERE board_roll = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $boardRoll);
    } else {
        return null;
    }
    
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

// Helper function to get subject ID by code
function getSubjectIdByCode($conn, $subjectCode, $departmentId, $semester) {
    $sql = "SELECT id FROM subjects WHERE subject_code = ? AND department_id = ? AND semester = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $subjectCode, $departmentId, $semester);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }

    return null;
}

// Helper function to get or create exam record
function getOrCreateExam($conn, $examType, $semester, $departmentId, $subjectId, $testNumber, $totalMarks) {
    // For ClassTest/Assignment, check by subject_id and exam_number
    // For Final/Midterm, check by semester and department (subject_id is NULL)

    if (($examType === 'ClassTest' || $examType === 'Assignment') && $subjectId) {
        // Check for existing class test or assignment
        $sql = "SELECT id FROM exams
                WHERE exam_type = ?
                AND semester = ?
                AND department_id = ?
                AND subject_id = ?
                AND exam_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiii", $examType, $semester, $departmentId, $subjectId, $testNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }

        // Create new exam
        $subjectInfo = getSubjectInfo($conn, $subjectId);
        $prefix = $examType === 'ClassTest' ? 'CT' : 'ASN';
        $title = "{$prefix}-{$testNumber} - {$subjectInfo['subject_name']} - Sem {$semester}";

        $insertSql = "INSERT INTO exams (exam_type, exam_number, title, semester, department_id, subject_id, total_marks, exam_date)
                      VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("sisiiid", $examType, $testNumber, $title, $semester, $departmentId, $subjectId, $totalMarks);
        $insertStmt->execute();

        return $conn->insert_id;

    } else {
        // For Final/Midterm - one exam per semester/department
        $sql = "SELECT id FROM exams
                WHERE exam_type = ?
                AND semester = ?
                AND department_id = ?
                AND subject_id IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $examType, $semester, $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }

        // Create new semester-wide exam
        $title = "{$examType} - Semester {$semester}";

        $insertSql = "INSERT INTO exams (exam_type, exam_number, title, semester, department_id, subject_id, total_marks, exam_date)
                      VALUES (?, NULL, ?, ?, ?, NULL, ?, CURDATE())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ssiid", $examType, $title, $semester, $departmentId, $totalMarks);
        $insertStmt->execute();

        return $conn->insert_id;
    }
}

// Helper function to get subject info
function getSubjectInfo($conn, $subjectId) {
    $sql = "SELECT subject_code, subject_name, total_marks FROM subjects WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return ['subject_code' => '', 'subject_name' => '', 'total_marks' => 100];
}

// Create upload_history table if it doesn't exist
function createUploadHistoryTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS upload_history (
        id VARCHAR(50) PRIMARY KEY,
        exam_type VARCHAR(50) NOT NULL,
        semester INT NOT NULL,
        department_id INT NOT NULL,
        subject_id INT NULL,
        records_count INT NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_status (status)
    )";
    $conn->query($sql);
}

// Save upload history
function saveUploadHistory($conn, $uploadId, $examType, $semester, $departmentId, $subjectId, $recordsCount) {
    $sql = "INSERT INTO upload_history (id, exam_type, semester, department_id, subject_id, records_count)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiiii", $uploadId, $examType, $semester, $departmentId, $subjectId, $recordsCount);
    $stmt->execute();
    $stmt->close();
}