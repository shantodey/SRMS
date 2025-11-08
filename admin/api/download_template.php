<?php
/**
 * API Endpoint: Download Excel Template
 *
 * GET /admin/api/download_template.php?exam_id=1
 *
 * Generates Excel template pre-filled with exam metadata
 */

session_start();

require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Check authentication
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['teacher_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Validate exam_id
    if (!isset($_GET['exam_id']) || empty($_GET['exam_id'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'exam_id is required'
        ]);
        exit;
    }

    $examId = (int)$_GET['exam_id'];

    // Get exam info
    $sql = "SELECT
                e.*,
                d.name as department_name,
                d.code as department_code,
                s.subject_name,
                s.subject_code
            FROM exams e
            INNER JOIN departments d ON e.department_id = d.id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Exam not found'
        ]);
        exit;
    }

    $exam = $result->fetch_assoc();

    // Create spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set sheet name
    $sheet->setTitle('Result Upload');

    // ========================================
    // METADATA SECTION (Hidden rows)
    // ========================================

    $sheet->setCellValue('A1', 'EXAM_METADATA');
    $sheet->setCellValue('B1', 'exam_id');
    $sheet->setCellValue('C1', $exam['id']);

    $sheet->setCellValue('B2', 'exam_type');
    $sheet->setCellValue('C2', $exam['exam_type']);

    $sheet->setCellValue('B3', 'semester');
    $sheet->setCellValue('C3', $exam['semester']);

    $sheet->setCellValue('B4', 'department_id');
    $sheet->setCellValue('C4', $exam['department_id']);

    $sheet->setCellValue('B5', 'subject_id');
    $sheet->setCellValue('C5', $exam['subject_id'] ?? '');

    $sheet->setCellValue('B6', 'total_marks');
    $sheet->setCellValue('C6', $exam['total_marks'] ?? 100);

    $sheet->setCellValue('B7', 'exam_date');
    $sheet->setCellValue('C7', $exam['exam_date'] ?? '');

    $sheet->setCellValue('B8', 'title');
    $sheet->setCellValue('C8', $exam['title']);

    // Hide metadata rows
    for ($i = 1; $i <= 8; $i++) {
        $sheet->getRowDimension($i)->setVisible(false);
    }

    // ========================================
    // INSTRUCTION SECTION
    // ========================================

    $instructionRow = 10;

    $sheet->setCellValue("A{$instructionRow}", 'INSTRUCTIONS:');
    $sheet->getStyle("A{$instructionRow}")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A{$instructionRow}")->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFE7E6E6');

    $instructionRow++;
    $sheet->setCellValue("A{$instructionRow}", "Exam: {$exam['title']}");

    $instructionRow++;
    $sheet->setCellValue("A{$instructionRow}", "Department: {$exam['department_name']} ({$exam['department_code']})");

    $instructionRow++;
    $sheet->setCellValue("A{$instructionRow}", "Semester: {$exam['semester']}");

    if ($exam['subject_id']) {
        $instructionRow++;
        $sheet->setCellValue("A{$instructionRow}", "Subject: {$exam['subject_name']} ({$exam['subject_code']})");
    }

    $instructionRow++;
    $sheet->setCellValue("A{$instructionRow}", "Total Marks: " . ($exam['total_marks'] ?? 100));

    $instructionRow += 2;
    $sheet->setCellValue("A{$instructionRow}", "Fill in student marks below. Required columns are marked with *");
    $sheet->getStyle("A{$instructionRow}")->getFont()->setItalic(true);

    // ========================================
    // HEADER ROW
    // ========================================

    $headerRow = $instructionRow + 2;

    $headers = ['Index No *', 'Board Roll *', 'Marks Obtained *'];

    // If exam is semester-wide (no subject), add subject_code column
    if (!$exam['subject_id']) {
        array_splice($headers, 2, 0, ['Subject Code *']);
    }

    // Optional columns
    $headers[] = 'Total Marks';
    $headers[] = 'Remarks';

    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue("{$col}{$headerRow}", $header);

        // Style header
        $sheet->getStyle("{$col}{$headerRow}")
            ->getFont()->setBold(true)->getSize(12);
        $sheet->getStyle("{$col}{$headerRow}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle("{$col}{$headerRow}")
            ->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("{$col}{$headerRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$col}{$headerRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto-size column
        $sheet->getColumnDimension($col)->setAutoSize(true);

        $col++;
    }

    // ========================================
    // SAMPLE DATA ROWS (optional)
    // ========================================

    $dataRow = $headerRow + 1;

    // Add 3 sample rows with placeholders
    for ($i = 1; $i <= 3; $i++) {
        $col = 'A';
        $sheet->setCellValue("{$col}{$dataRow}", "INDEX{$i}");  // Index No
        $col++;
        $sheet->setCellValue("{$col}{$dataRow}", "BOARD{$i}");  // Board Roll
        $col++;

        if (!$exam['subject_id']) {
            $sheet->setCellValue("{$col}{$dataRow}", "SUBJ101");  // Subject Code
            $col++;
        }

        $sheet->setCellValue("{$col}{$dataRow}", "0");  // Marks Obtained
        $col++;
        $sheet->setCellValue("{$col}{$dataRow}", $exam['total_marks'] ?? 100);  // Total Marks
        $col++;
        $sheet->setCellValue("{$col}{$dataRow}", "");  // Remarks

        // Light gray background for sample rows
        $sheet->getStyle("A{$dataRow}:{$col}{$dataRow}")
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF2F2F2');

        $dataRow++;
    }

    // ========================================
    // GENERATE DOWNLOAD
    // ========================================

    $filename = 'Result_Upload_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $exam['title']) . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
