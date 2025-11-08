<?php
session_start();
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    try {
        // Get form data with trim to remove extra spaces
        $index_no = isset($_POST['index_no']) ? trim($_POST['index_no']) : '';
        $board_roll = isset($_POST['board_roll']) ? trim($_POST['board_roll']) : '';
        $student_name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
        
        // Optional fields - accept whatever is provided (even if wrong)
        $roll_no = isset($_POST['roll_no']) ? trim($_POST['roll_no']) : '';
        $department_id = isset($_POST['department_id']) && !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $batch_id = isset($_POST['batch_id']) && !empty($_POST['batch_id']) ? intval($_POST['batch_id']) : null;
        $semester = isset($_POST['semester']) && !empty($_POST['semester']) ? intval($_POST['semester']) : null;

        // =====================================================
        // VALIDATION - ONLY CHECK 3 ESSENTIAL FIELDS
        // =====================================================
        
        // Check Index Number
        if (empty($index_no)) {
            $response['message'] = 'Index Number is required. Please enter the student\'s index number.';
            echo json_encode($response);
            exit;
        }

        // Check Board Roll
        if (empty($board_roll)) {
            $response['message'] = 'Board Roll is required. Please enter the student\'s board roll number.';
            echo json_encode($response);
            exit;
        }

        // Check Student Name
        if (empty($student_name)) {
            $response['message'] = 'Student Name is required. Please enter the student\'s full name.';
            echo json_encode($response);
            exit;
        }

        // Validate name length (reasonable check)
        if (strlen($student_name) < 3) {
            $response['message'] = 'Student Name is too short. Please enter the full name (at least 3 characters).';
            echo json_encode($response);
            exit;
        }

        // =====================================================
        // CHECK FOR DUPLICATE STUDENT
        // =====================================================
        
        $check_sql = "SELECT id, student_name FROM students WHERE index_no = ? OR board_roll = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            $response['message'] = 'System error. Please try again or contact administrator.';
            error_log('Prepare failed: ' . $conn->error);
            echo json_encode($response);
            exit;
        }

        $check_stmt->bind_param("ss", $index_no, $board_roll);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            $response['message'] = 'This student already exists in the system. Student Name: ' . $existing['student_name'] . '. Please check the Index Number and Board Roll.';
            echo json_encode($response);
            exit;
        }
        $check_stmt->close();

        // =====================================================
        // INSERT STUDENT - ACCEPT ALL DATA (EVEN IF WRONG)
        // =====================================================
        
        $sql = "INSERT INTO students (index_no, board_roll, roll_no, student_name, department_id, batch_id, semester, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $response['message'] = 'System error while preparing data. Please try again.';
            error_log('Insert prepare failed: ' . $conn->error);
            echo json_encode($response);
            exit;
        }

        $stmt->bind_param("sssssii", 
            $index_no, 
            $board_roll, 
            $roll_no, 
            $student_name, 
            $department_id, 
            $batch_id, 
            $semester
        );

        if ($stmt->execute()) {
            $new_student_id = $conn->insert_id;
            
            $response['success'] = true;
            $response['message'] = 'Student added successfully! ' . 
                                   'Student Name: ' . $student_name . ' | ' .
                                   'Index No: ' . $index_no . ' | ' .
                                   'Board Roll: ' . $board_roll;
            $response['student_id'] = $new_student_id;
            
            // Add helpful note if optional fields are missing
            $missing_fields = [];
            if (empty($department_id)) $missing_fields[] = 'Department';
            if (empty($batch_id)) $missing_fields[] = 'Batch';
            if (empty($semester)) $missing_fields[] = 'Semester';
            if (empty($roll_no)) $missing_fields[] = 'Roll Number';
            
            if (!empty($missing_fields)) {
                $response['message'] .= ' Note: ' . implode(', ', $missing_fields) . ' not provided. You can edit the student later to add these details.';
            }
            
        } else {
            // Handle specific database errors with user-friendly messages
            $error = $stmt->error;
            
            if (strpos($error, 'Duplicate entry') !== false) {
                if (strpos($error, 'index_no') !== false) {
                    $response['message'] = 'This Index Number already exists. Please check and try again.';
                } elseif (strpos($error, 'board_roll') !== false) {
                    $response['message'] = 'This Board Roll already exists. Please check and try again.';
                } else {
                    $response['message'] = 'This student already exists in the system.';
                }
            } elseif (strpos($error, 'foreign key') !== false || strpos($error, 'Cannot add or update') !== false) {
                $response['message'] = 'Invalid Department or Batch selected. Please select valid options from the dropdown.';
            } else {
                // Generic error for anything else
                $response['message'] = 'Unable to add student. Please check all information and try again.';
                error_log('Insert execution failed: ' . $error);
            }
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = 'An unexpected error occurred. Please try again or contact support.';
        error_log('Exception in process_student.php: ' . $e->getMessage());
    }

    echo json_encode($response);
    exit;

} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method. Please use the form to submit data.'
    ];
    echo json_encode($response);
    exit;
}
?>