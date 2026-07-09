<?php
/**
 * Generate Excel Template Based on Exam Type
 * Integrated with existing SRMS system
 */

require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Get parameters
$examType = $_GET['exam_type'] ?? '';
$semester = $_GET['semester'] ?? '';
$departmentId = $_GET['department_id'] ?? '';
$subjectId = $_GET['subject_id'] ?? null;
$testNumber = $_GET['test_number'] ?? 1;

if (empty($examType) || empty($semester) || empty($departmentId)) {
    die('Missing required parameters');
}

// Get department info
$stmt = $conn->prepare("SELECT name, code FROM departments WHERE id = ?");
$stmt->bind_param("i", $departmentId);
$stmt->execute();
$dept = $stmt->get_result()->fetch_assoc();

// Get subject info if provided
$subject = null;
if ($subjectId) {
    $stmt = $conn->prepare("SELECT subject_name, subject_code, total_marks FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Result Upload');

// ==========================
// HEADER SECTION
// ==========================
$row = 1;

// Title
$sheet->setCellValue("A{$row}", "RESULT UPLOAD TEMPLATE");
$sheet->mergeCells("A{$row}:E{$row}");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
$sheet->getStyle("A{$row}")->getFont()->getColor()->setARGB('FFFFFFFF');
$row += 2;

// Exam Details
$sheet->setCellValue("A{$row}", "Exam Type:");
$sheet->setCellValue("B{$row}", $examType);
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;

$sheet->setCellValue("A{$row}", "Semester:");
$sheet->setCellValue("B{$row}", $semester);
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;

$sheet->setCellValue("A{$row}", "Department:");
$sheet->setCellValue("B{$row}", "{$dept['name']} ({$dept['code']})");
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;

if ($subject) {
    $sheet->setCellValue("A{$row}", "Subject:");
    $sheet->setCellValue("B{$row}", "{$subject['subject_name']} ({$subject['subject_code']})");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    if ($examType === 'ClassTest') {
        $sheet->setCellValue("A{$row}", "Test Number:");
        $sheet->setCellValue("B{$row}", "CT-{$testNumber}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
    } elseif ($examType === 'Assignment') {
        $sheet->setCellValue("A{$row}", "Assignment Number:");
        $sheet->setCellValue("B{$row}", "ASN-{$testNumber}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
    }
}

$row += 2;

// Instructions
$sheet->setCellValue("A{$row}", "INSTRUCTIONS:");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
$row++;

$sheet->setCellValue("A{$row}", "1. Fill in the student marks below");
$row++;
$sheet->setCellValue("A{$row}", "2. Required columns are marked with *");
$row++;
$sheet->setCellValue("A{$row}", "3. Do not modify the header row");
$row++;
$sheet->setCellValue("A{$row}", "4. Save and upload this file to the admin panel");
$row++;

$row += 2;

// ==========================
// DATA HEADER ROW
// ==========================
$headerRow = $row;

// All exam types use the same format - subject code is always required
$headers = ['Index No *', 'Board Roll *', 'Subject Code *', 'Subject Name', 'Marks Obtained *', 'Total Marks *'];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue("{$col}{$headerRow}", $header);

    // Style header
    $sheet->getStyle("{$col}{$headerRow}")
        ->getFont()->setBold(true)->setSize(11);
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

// ==========================
// SAMPLE DATA ROWS
// ==========================
$dataRow = $headerRow + 1;

// Add 3 sample rows
for ($i = 1; $i <= 3; $i++) {
    $col = 'A';

    // Index No
    $sheet->setCellValue("{$col}{$dataRow}", "INDEX00{$i}");
    $col++;

    // Board Roll
    $sheet->setCellValue("{$col}{$dataRow}", "BOARD00{$i}");
    $col++;

    // Subject Code (required for all exam types)
    $sheet->setCellValue("{$col}{$dataRow}", "SUBJ101");
    $col++;

    // Subject Name
    $sheet->setCellValue("{$col}{$dataRow}", "Subject Name");
    $col++;

    // Marks Obtained
    $sheet->setCellValue("{$col}{$dataRow}", "0");
    $col++;

    // Total Marks
    $sheet->setCellValue("{$col}{$dataRow}", "100");
    $col++;

    // Light gray background for sample rows
    $sheet->getStyle("A{$dataRow}:{$col}{$dataRow}")
        ->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFF2F2F2');

    $dataRow++;
}

// ==========================
// GENERATE DOWNLOAD
// ==========================
$filename = "{$examType}_";
if ($subject) {
    $filename .= preg_replace('/[^A-Za-z0-9_-]/', '_', $subject['subject_name']) . "_";
}
$filename .= "Sem{$semester}_Template.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
