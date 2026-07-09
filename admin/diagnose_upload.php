<?php
// Temporary debug script - DELETE after fixing
require_once '../config/database.php';
header('Content-Type: text/plain');

// Simulate the upload process with test values
$testData = [
    'marks_obtained' => 28,
    'total_marks' => 30
];

echo "=== TESTING INSERT VALUES ===\n\n";

// Show what PHP has
echo "PHP Values:\n";
echo "  marks_obtained: " . $testData['marks_obtained'] . " (type: " . gettype($testData['marks_obtained']) . ")\n";
echo "  total_marks: " . $testData['total_marks'] . " (type: " . gettype($testData['total_marks']) . ")\n";
echo "\n";

// Convert using floatval like the real code does
$marksObtained = floatval($testData['marks_obtained']);
$totalMarks = floatval($testData['total_marks']);

echo "After floatval():\n";
echo "  marks_obtained: $marksObtained\n";
echo "  total_marks: $totalMarks\n";
echo "\n";

// Check database column type
$result = $conn->query("SHOW COLUMNS FROM results WHERE Field = 'total_marks'");
if ($result->num_rows > 0) {
    $column = $result->fetch_assoc();
    echo "Database Column 'total_marks':\n";
    echo "  Type: " . $column['Type'] . "\n";
    echo "  Null: " . $column['Null'] . "\n";
    echo "  Default: " . $column['Default'] . "\n";
    echo "\n";
} else {
    echo "❌ ERROR: total_marks column does NOT exist!\n\n";
}

// Check what's actually in the database
echo "=== LAST 3 RESULTS IN DATABASE ===\n\n";
$result = $conn->query("SELECT id, marks_obtained, total_marks,
    ROUND((marks_obtained / total_marks) * 100, 2) as percentage
    FROM results ORDER BY id DESC LIMIT 3");

while ($row = $result->fetch_assoc()) {
    echo "ID {$row['id']}:\n";
    echo "  marks_obtained: {$row['marks_obtained']}\n";
    echo "  total_marks: {$row['total_marks']}\n";
    echo "  calculated %: {$row['percentage']}%\n";
    echo "\n";
}

echo "=== END DIAGNOSTIC ===\n";
?>
