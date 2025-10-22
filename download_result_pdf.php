<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session not used in this application

require_once 'config/database.php';

// Check database connection
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Check if search parameter is provided
if (!isset($_GET['search']) || empty($_GET['search'])) {
    die('Error: No search parameter provided. Please search for a student first.');
}

$search = $_GET['search'];

// Search for student by index number or board roll
$sql = "SELECT s.*, d.name as department_name, d.code as department_code
        FROM students s
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.index_no = ? OR s.board_roll = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Database Error: ' . $conn->error);
}

$stmt->bind_param("ss", $search, $search);
if (!$stmt->execute()) {
    die('Query Execution Error: ' . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('Error: Student not found with Index Number or Board Roll: ' . htmlspecialchars($search) . '<br><br><a href="index.php">Go back to search</a>');
}

$student = $result->fetch_assoc();

if (!$student) {
    die('Error: Unable to fetch student data.');
}

// Get student results
$sql_results = "SELECT r.*, s.subject_name, s.subject_code, s.total_marks as subject_total_marks
                FROM results r
                LEFT JOIN subjects s ON r.subject_id = s.id
                WHERE r.student_id = ? AND r.semester = ?
                ORDER BY s.subject_code";

$stmt_results = $conn->prepare($sql_results);
if (!$stmt_results) {
    die('Database Error (Results): ' . $conn->error);
}

$stmt_results->bind_param("ii", $student['id'], $student['semester']);
if (!$stmt_results->execute()) {
    die('Query Execution Error (Results): ' . $stmt_results->error);
}

$results = $stmt_results->get_result();

// Check if student has any results
if ($results->num_rows == 0) {
    die('Error: No results found for student <strong>' . htmlspecialchars($student['student_name']) . '</strong> in Semester ' . $student['semester'] . '<br><br>Please make sure results have been published for this student.<br><br><a href="index.php">Go back to search</a>');
}

// Calculate totals
$totalMarks = 0;
$totalPossible = 0;
$subjectCount = 0;
$gradePoints = 0;

$subjects = [];
while ($row = $results->fetch_assoc()) {
    $totalMarks += $row['marks_obtained'];
    $totalPossible += $row['total_marks'];
    $subjectCount++;

    // Calculate grade points based on percentage
    $percentage = ($row['marks_obtained'] / $row['total_marks']) * 100;
    if ($percentage >= 80) { $gp = 4.0; $grade = 'A+'; }
    elseif ($percentage >= 75) { $gp = 3.75; $grade = 'A'; }
    elseif ($percentage >= 70) { $gp = 3.5; $grade = 'A-'; }
    elseif ($percentage >= 65) { $gp = 3.25; $grade = 'B+'; }
    elseif ($percentage >= 60) { $gp = 3.0; $grade = 'B'; }
    elseif ($percentage >= 55) { $gp = 2.75; $grade = 'B-'; }
    elseif ($percentage >= 50) { $gp = 2.5; $grade = 'C+'; }
    elseif ($percentage >= 45) { $gp = 2.25; $grade = 'C'; }
    elseif ($percentage >= 40) { $gp = 2.0; $grade = 'C-'; }
    elseif ($percentage >= 33) { $gp = 1.0; $grade = 'D'; }
    else { $gp = 0.0; $grade = 'F'; }

    $gradePoints += $gp;

    $row['percentage'] = $percentage;
    $row['grade'] = $grade;
    $row['grade_point'] = $gp;
    $subjects[] = $row;
}

$percentage = $totalPossible > 0 ? ($totalMarks / $totalPossible) * 100 : 0;
$cgpa = $subjectCount > 0 ? $gradePoints / $subjectCount : 0;

// Determine overall grade
if ($percentage >= 80) $finalGrade = 'A+';
elseif ($percentage >= 75) $finalGrade = 'A';
elseif ($percentage >= 70) $finalGrade = 'A-';
elseif ($percentage >= 65) $finalGrade = 'B+';
elseif ($percentage >= 60) $finalGrade = 'B';
elseif ($percentage >= 55) $finalGrade = 'B-';
elseif ($percentage >= 50) $finalGrade = 'C+';
elseif ($percentage >= 45) $finalGrade = 'C';
elseif ($percentage >= 40) $finalGrade = 'C-';
elseif ($percentage >= 33) $finalGrade = 'D';
else $finalGrade = 'F';

// Check if TCPDF library is available
$tcpdfAvailable = false;
if (file_exists('vendor/autoload.php')) {
    require_once('vendor/autoload.php');
    $tcpdfAvailable = class_exists('TCPDF');
} elseif (file_exists('tcpdf/tcpdf.php')) {
    require_once('tcpdf/tcpdf.php');
    $tcpdfAvailable = class_exists('TCPDF');
}

// Use HTML print approach (works in all browsers)
if (!$tcpdfAvailable) {
    // Simple HTML to PDF approach using print CSS - No special headers needed
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Result - <?php echo htmlspecialchars($student['student_name']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #5eb3f6;
        }

        .header h1 {
            font-size: 24pt;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 16pt;
            color: #5eb3f6;
            margin-bottom: 10px;
        }

        .info-section {
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 35%;
            color: #2c3e50;
        }

        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #34495e;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .results-table th {
            background: #5eb3f6;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #5eb3f6;
        }

        .results-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }

        .results-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .results-table tfoot td {
            font-weight: bold;
            background: #e8f4f8;
            padding: 10px;
            border: 1px solid #5eb3f6;
        }

        .summary-box {
            margin: 20px 0;
            padding: 15px;
            background: #f0f8ff;
            border: 2px solid #5eb3f6;
            border-radius: 5px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            font-size: 10pt;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 18pt;
            font-weight: bold;
            color: #2c3e50;
        }

        .grade-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14pt;
        }

        .grade-A-plus, .grade-A { background: #d4edda; color: #155724; }
        .grade-B-plus, .grade-B { background: #d1ecf1; color: #0c5460; }
        .grade-C-plus, .grade-C { background: #fff3cd; color: #856404; }
        .grade-D { background: #f8d7da; color: #721c24; }
        .grade-F { background: #f8d7da; color: #721c24; font-weight: bold; }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 10pt;
            color: #7f8c8d;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
    <script>
        // Automatically trigger print dialog
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Student Result Management System</h1>
        <h2>Academic Result Sheet</h2>
        <p>Semester <?php echo $student['semester']; ?> - Academic Year <?php echo date('Y'); ?></p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Student Name:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['student_name']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Index Number:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['index_no']); ?></div>
        </div>
        <?php if (!empty($student['board_roll'])): ?>
        <div class="info-row">
            <div class="info-label">Board Roll:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['board_roll']); ?></div>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <div class="info-label">Department:</div>
            <div class="info-value"><?php echo htmlspecialchars($student['department_name']); ?> (<?php echo htmlspecialchars($student['department_code']); ?>)</div>
        </div>
        <div class="info-row">
            <div class="info-label">Semester:</div>
            <div class="info-value"><?php echo $student['semester']; ?></div>
        </div>
    </div>

    <table class="results-table">
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th style="text-align: center;">Marks</th>
                <th style="text-align: center;">Percentage</th>
                <th style="text-align: center;">Grade</th>
                <th style="text-align: center;">GP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subjects as $subject): ?>
            <tr>
                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                <td style="text-align: center;"><?php echo $subject['marks_obtained'] . ' / ' . $subject['total_marks']; ?></td>
                <td style="text-align: center;"><?php echo number_format($subject['percentage'], 2); ?>%</td>
                <td style="text-align: center;"><strong><?php echo $subject['grade']; ?></strong></td>
                <td style="text-align: center;"><?php echo number_format($subject['grade_point'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;"><strong>Total / Average</strong></td>
                <td style="text-align: center;"><strong><?php echo $totalMarks . ' / ' . $totalPossible; ?></strong></td>
                <td style="text-align: center;"><strong><?php echo number_format($percentage, 2); ?>%</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-box">
        <h3 style="text-align: center; margin-bottom: 15px; color: #2c3e50;">Performance Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Marks</div>
                <div class="summary-value"><?php echo $totalMarks; ?>/<?php echo $totalPossible; ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Percentage</div>
                <div class="summary-value"><?php echo number_format($percentage, 2); ?>%</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">CGPA</div>
                <div class="summary-value"><?php echo number_format($cgpa, 2); ?></div>
            </div>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <span class="summary-label">Final Grade:</span>
            <span class="grade-badge grade-<?php echo str_replace('+', '-plus', str_replace('-', '-minus', $finalGrade)); ?>">
                <?php echo $finalGrade; ?>
            </span>
        </div>
    </div>

    <div class="footer">
        <p><strong>Generated on:</strong> <?php echo date('F d, Y \a\t h:i A'); ?></p>
        <p style="margin-top: 5px;">This is a computer-generated result sheet and does not require a signature.</p>
        <p style="margin-top: 5px; font-size: 9pt;">Student Result Management System &copy; <?php echo date('Y'); ?></p>
    </div>
</body>
</html>
    <?php
    exit;
}

// If TCPDF is available, use it for better PDF generation
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SRMS');
$pdf->SetTitle('Student Result - ' . $student['student_name']);
$pdf->SetSubject('Academic Result');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Generate HTML content
$html = '
<style>
    h1 { color: #2c3e50; text-align: center; font-size: 20pt; }
    h2 { color: #5eb3f6; text-align: center; font-size: 14pt; }
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #5eb3f6; color: white; padding: 8px; text-align: left; }
    td { padding: 6px; border: 1px solid #ddd; }
    .info-table td { border: none; padding: 4px; }
    .summary { background-color: #f0f8ff; padding: 15px; border: 2px solid #5eb3f6; margin-top: 10px; }
</style>

<h1>Student Result Management System</h1>
<h2>Academic Result Sheet - Semester ' . $student['semester'] . '</h2>
<br>

<table class="info-table">
    <tr><td width="30%"><strong>Student Name:</strong></td><td>' . htmlspecialchars($student['student_name']) . '</td></tr>
    <tr><td><strong>Index Number:</strong></td><td>' . htmlspecialchars($student['index_no']) . '</td></tr>
    ' . (!empty($student['board_roll']) ? '<tr><td><strong>Board Roll:</strong></td><td>' . htmlspecialchars($student['board_roll']) . '</td></tr>' : '') . '
    <tr><td><strong>Department:</strong></td><td>' . htmlspecialchars($student['department_name']) . ' (' . htmlspecialchars($student['department_code']) . ')</td></tr>
</table>
<br>

<table border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Subject Code</th>
            <th>Subject Name</th>
            <th align="center">Marks</th>
            <th align="center">Percentage</th>
            <th align="center">Grade</th>
            <th align="center">GP</th>
        </tr>
    </thead>
    <tbody>';

foreach ($subjects as $subject) {
    $html .= '<tr>
        <td>' . htmlspecialchars($subject['subject_code']) . '</td>
        <td>' . htmlspecialchars($subject['subject_name']) . '</td>
        <td align="center">' . $subject['marks_obtained'] . ' / ' . $subject['total_marks'] . '</td>
        <td align="center">' . number_format($subject['percentage'], 2) . '%</td>
        <td align="center"><strong>' . $subject['grade'] . '</strong></td>
        <td align="center">' . number_format($subject['grade_point'], 2) . '</td>
    </tr>';
}

$html .= '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" align="right"><strong>Total / Average</strong></td>
            <td align="center"><strong>' . $totalMarks . ' / ' . $totalPossible . '</strong></td>
            <td align="center"><strong>' . number_format($percentage, 2) . '%</strong></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="summary">
    <h3 align="center">Performance Summary</h3>
    <table width="100%">
        <tr>
            <td align="center"><strong>Total Marks:</strong><br>' . $totalMarks . '/' . $totalPossible . '</td>
            <td align="center"><strong>Percentage:</strong><br>' . number_format($percentage, 2) . '%</td>
            <td align="center"><strong>CGPA:</strong><br>' . number_format($cgpa, 2) . '</td>
        </tr>
        <tr>
            <td colspan="3" align="center"><br><strong>Final Grade: ' . $finalGrade . '</strong></td>
        </tr>
    </table>
</div>

<br><br>
<p align="center" style="font-size: 10pt; color: #7f8c8d;">
    <strong>Generated on:</strong> ' . date('F d, Y \a\t h:i A') . '<br>
    This is a computer-generated result sheet.<br>
    Student Result Management System &copy; ' . date('Y') . '
</p>
';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('Result_' . $student['index_no'] . '_Semester_' . $student['semester'] . '.pdf', 'D');
?>
