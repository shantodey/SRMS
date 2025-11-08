<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

try {
    $department_id = $_GET['department_id'] ?? '';

    // Build query
    $sql = "SELECT s.*, b.name as batch_name, b.year as batch_year, d.name as department_name, d.code as department_code
            FROM students s
            LEFT JOIN batches b ON s.batch_id = b.id
            LEFT JOIN departments d ON s.department_id = d.id";

    if (!empty($department_id)) {
        $sql .= " WHERE s.department_id = ?";
    }

    $sql .= " ORDER BY s.batch_id DESC, s.index_no ASC";

    if (!empty($department_id)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Students');

    // Set headers
    $headers = ['Index No', 'Name', 'Roll No', 'Board Roll', 'Department', 'Batch', 'Semester'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $col++;
    }

    // Add data
    $row = 2;
    while ($student = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $student['index_no']);
        $sheet->setCellValue('B' . $row, $student['student_name']);
        $sheet->setCellValue('C' . $row, $student['roll_no']);
        $sheet->setCellValue('D' . $row, $student['board_roll']);
        $sheet->setCellValue('E' . $row, $student['department_code'] ?? 'N/A');
        $sheet->setCellValue('F' . $row, $student['batch_name'] ?? 'N/A');
        $sheet->setCellValue('G' . $row, $student['semester']);
        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set borders
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];
    $sheet->getStyle('A1:G' . ($row - 1))->applyFromArray($styleArray);

    // Output file
    $filename = 'students_export_' . date('Y-m-d_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die('Error generating export: ' . $e->getMessage());
}
?>