<?php
session_start();
require_once '../config/database.php';
require 'vendor/autoload.php'; // Make sure you have PHPSpreadsheet installed

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['file'])) {
            throw new Exception('No file uploaded');
        }

        $inputFileName = $_FILES['file']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Remove header row
        $headers = array_shift($rows);
        
        if ($_POST['type'] === 'students') {
            // Process student data
            foreach ($rows as $row) {
                $sql = "INSERT INTO students (batch_year, semester, department_id, student_name, roll_no, index_no, board_roll) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssissss", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
                $stmt->execute();
            }
        } else if ($_POST['type'] === 'results') {
            // Process results data
            foreach ($rows as $row) {
                $sql = "INSERT INTO results (index_no, board_roll, subject_code, subject_name, marks_obtained, total_marks) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssdd", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
                $stmt->execute();
            }
        }

        $response['success'] = true;
        $response['message'] = 'Data imported successfully';
        $response['data'] = array_slice($rows, 0, 5); // Return first 5 rows as preview
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
