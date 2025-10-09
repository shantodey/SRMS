<?php
// First, download PhpSpreadsheet if not already installed
if (!file_exists('vendor/autoload.php')) {
    die("Please install PhpSpreadsheet first by running: composer require phpoffice/phpspreadsheet");
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create directory for templates if it doesn't exist
if (!is_dir('templates')) {
    mkdir('templates', 0777, true);
}

// Create Student Excel Template
function createStudentTemplate() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = ['Batch Year', 'Semester', 'Department', 'Student Name', 'Roll No', 'Index No', 'Board Roll'];
    $sheet->fromArray([$headers], NULL, 'A1');

    // Add sample data
    $sampleData = [
        ['2025', '1', 'CSE', 'John Doe', 'CSE-2025-001', 'IDX2025001', 'BR20250001'],
        ['2025', '1', 'CSE', 'Jane Smith', 'CSE-2025-002', 'IDX2025002', 'BR20250002'],
        ['2025', '1', 'EEE', 'Mike Wilson', 'EEE-2025-001', 'IDX2025003', 'BR20250003'],
        ['2025', '1', 'ME', 'Sarah Brown', 'ME-2025-001', 'IDX2025004', 'BR20250004'],
        ['2025', '1', 'CSE', 'Alex Johnson', 'CSE-2025-003', 'IDX2025005', 'BR20250005']
    ];

    // Add data starting from row 2
    $sheet->fromArray($sampleData, NULL, 'A2');

    // Auto-size columns
    foreach(range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Style headers
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('templates/students_sample.xlsx');
}

// Create Results Excel Template
function createResultTemplate() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = ['Index No', 'Board Roll', 'Subject Code', 'Subject Name', 'Marks Obtained', 'Total Marks'];
    $sheet->fromArray([$headers], NULL, 'A1');

    // Add sample data (using the same student IDs from student template)
    $sampleData = [
        ['IDX2025001', 'BR20250001', 'CSE101', 'Introduction to Programming', '85', '100'],
        ['IDX2025001', 'BR20250001', 'CSE102', 'Digital Logic Design', '92', '100'],
        ['IDX2025002', 'BR20250002', 'CSE101', 'Introduction to Programming', '88', '100'],
        ['IDX2025002', 'BR20250002', 'CSE102', 'Digital Logic Design', '90', '100'],
        ['IDX2025003', 'BR20250003', 'EEE101', 'Basic Electrical Engineering', '87', '100'],
        ['IDX2025003', 'BR20250003', 'EEE102', 'Electronics Fundamentals', '82', '100'],
        ['IDX2025004', 'BR20250004', 'ME101', 'Engineering Mechanics', '88', '100'],
        ['IDX2025005', 'BR20250005', 'CSE101', 'Introduction to Programming', '95', '100']
    ];

    // Add data starting from row 2
    $sheet->fromArray($sampleData, NULL, 'A2');

    // Auto-size columns
    foreach(range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Style headers
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('templates/results_sample.xlsx');
}

try {
    createStudentTemplate();
    createResultTemplate();
    echo "Sample Excel files created successfully!\n";
    echo "1. Student template: templates/students_sample.xlsx\n";
    echo "2. Result template: templates/results_sample.xlsx\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
