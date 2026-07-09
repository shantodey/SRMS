<?php
require_once '../config/database.php';

header('Content-Type: text/plain');

echo "=== CHECKING RESULTS TABLE DATA ===\n\n";

// Check if total_marks column exists
$result = $conn->query("SHOW COLUMNS FROM results LIKE 'total_marks'");
if ($result->num_rows == 0) {
    echo "❌ ERROR: total_marks column does NOT exist in results table!\n";
    echo "   You need to run the SQL migration first.\n\n";
} else {
    $column = $result->fetch_assoc();
    echo "✓ total_marks column exists\n";
    echo "  Type: {$column['Type']}\n";
    echo "  Null: {$column['Null']}\n";
    echo "  Default: {$column['Default']}\n\n";
}

// Check actual data
echo "=== LAST 10 RESULTS ===\n\n";
$sql = "SELECT
    r.id,
    s.student_name,
    e.title as exam_title,
    r.marks_obtained,
    r.total_marks,
    ROUND((r.marks_obtained / r.total_marks) * 100, 2) as calculated_percentage,
    r.grade
FROM results r
INNER JOIN students s ON r.student_id = s.id
INNER JOIN exams e ON r.exam_id = e.id
ORDER BY r.id DESC
LIMIT 10";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Student: {$row['student_name']}\n";
        echo "Exam: {$row['exam_title']}\n";
        echo "Marks Obtained: {$row['marks_obtained']}\n";
        echo "Total Marks: {$row['total_marks']}\n";
        echo "Calculated %: {$row['calculated_percentage']}%\n";
        echo "Grade: {$row['grade']}\n";
        echo "---\n";
    }
} else {
    echo "No results found in database.\n";
}

// Check for NULL or 0 total_marks
echo "\n=== CHECKING FOR INVALID TOTAL_MARKS ===\n\n";
$result = $conn->query("SELECT COUNT(*) as count FROM results WHERE total_marks IS NULL OR total_marks = 0");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "❌ Found {$row['count']} results with NULL or 0 total_marks\n";
    echo "   These need to be fixed!\n";
} else {
    echo "✓ All results have valid total_marks\n";
}

// Check for values less than 1 (might be decimal issue)
$result = $conn->query("SELECT COUNT(*) as count FROM results WHERE total_marks < 1 AND total_marks > 0");
$row = $result->fetch_assoc();
if ($row['count'] > 0) {
    echo "❌ Found {$row['count']} results with total_marks less than 1\n";
    echo "   This suggests decimal conversion issue!\n";

    // Show examples
    echo "\n   Examples:\n";
    $result = $conn->query("SELECT id, marks_obtained, total_marks FROM results WHERE total_marks < 1 AND total_marks > 0 LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "   ID {$row['id']}: marks={$row['marks_obtained']}, total={$row['total_marks']}\n";
    }
}

echo "\n=== END OF CHECK ===\n";
?>
