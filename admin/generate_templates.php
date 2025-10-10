<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create Students Template
function createStudentTemplate() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    $headers = ['Batch Year', 'Semester', 'Department', 'Student Name', 'Roll No', 'Index No', 'Board Roll'];
    $sheet->fromArray([$headers], NULL, 'A1');
    
    // Add sample data
    $sampleData = [
        ['2025', '1', 'CSE', 'John Doe', 'CSE-2025-001', 'IDX2025001', 'BR20250001'],
        ['2025', '1', 'CSE', 'Jane Smith', 'CSE-2025-002', 'IDX2025002', 'BR20250002'],
        ['2025', '1', 'EEE', 'Mike Johnson', 'EEE-2025-001', 'IDX2025003', 'BR20250003'],
        ['2025', '1', 'ME', 'Sarah Williams', 'ME-2025-001', 'IDX2025004', 'BR20250004'],
        ['2025', '1', 'CSE', 'David Brown', 'CSE-2025-003', 'IDX2025005', 'BR20250005']
    ];
    
    $sheet->fromArray($sampleData, NULL, 'A2');
    
    // Style the headers
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);
    foreach(range('A','G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('../templates/student_template.xlsx');
}

// Create Results Template
function createResultTemplate() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    $headers = ['Index No', 'Board Roll', 'Subject Code', 'Subject Name', 'Marks Obtained', 'Total Marks'];
    $sheet->fromArray([$headers], NULL, 'A1');
    
    // Add sample data
    $sampleData = [
        ['IDX2025001', 'BR20250001', 'CSE101', 'Introduction to Programming', '85', '100'],
        ['IDX2025001', 'BR20250001', 'CSE102', 'Computer Fundamentals', '78', '100'],
        ['IDX2025002', 'BR20250002', 'CSE101', 'Introduction to Programming', '92', '100'],
        ['IDX2025002', 'BR20250002', 'CSE102', 'Computer Fundamentals', '88', '100'],
        ['IDX2025003', 'BR20250003', 'EEE101', 'Basic Electrical', '90', '100']
    ];
    
    $sheet->fromArray($sampleData, NULL, 'A2');
    
    // Style the headers
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    foreach(range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('../templates/results_template.xlsx');
}

// Create templates directory if it doesn't exist
if (!file_exists('../templates')) {
    mkdir('../templates', 0777, true);
}

// Generate both templates
createStudentTemplate();
createResultTemplate();

echo "Templates created successfully!";
?>
