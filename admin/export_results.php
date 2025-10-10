<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

try {
    $batch_id = $_GET['batch_id'] ?? '';

    // Build query
    $sql = "SELECT s.index_no, s.student_name, s.board_roll, r.subject_code, r.subject_name,
            r.marks_obtained, r.total_marks, r.percentage, r.grade,
            d.code as department_code, b.name as batch_name
            FROM results r
            JOIN students s ON r.student_id = s.id
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN batches b ON s.batch_id = b.id";

    if (!empty($batch_id)) {
        $sql .= " WHERE s.batch_id = ?";
    }

    $sql .= " ORDER BY s.index_no ASC, r.subject_code ASC";

    if (!empty($batch_id)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Results');

    // Set headers
    $headers = ['Index No', 'Name', 'Board Roll', 'Department', 'Batch', 'Subject Code', 'Subject Name', 'Marks', 'Total', 'Percentage', 'Grade'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF70AD47');
        $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $col++;
    }

    // Add data
    $row = 2;
    while ($result_data = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $result_data['index_no']);
        $sheet->setCellValue('B' . $row, $result_data['student_name']);
        $sheet->setCellValue('C' . $row, $result_data['board_roll']);
        $sheet->setCellValue('D' . $row, $result_data['department_code'] ?? 'N/A');
        $sheet->setCellValue('E' . $row, $result_data['batch_name'] ?? 'N/A');
        $sheet->setCellValue('F' . $row, $result_data['subject_code']);
        $sheet->setCellValue('G' . $row, $result_data['subject_name']);
        $sheet->setCellValue('H' . $row, $result_data['marks_obtained']);
        $sheet->setCellValue('I' . $row, $result_data['total_marks']);
        $sheet->setCellValue('J' . $row, number_format($result_data['percentage'], 2) . '%');
        $sheet->setCellValue('K' . $row, $result_data['grade']);
        $row++;
    }

    // Auto-size columns
    foreach (range('A', 'K') as $col) {
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
    $sheet->getStyle('A1:K' . ($row - 1))->applyFromArray($styleArray);

    // Output file
    $filename = 'results_export_' . date('Y-m-d_His') . '.xlsx';

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