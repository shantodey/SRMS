<?php
require_once '../config/database.php';
header('Content-Type: text/plain');

echo "=== RESULTS TABLE STRUCTURE ===\n\n";

$result = $conn->query("DESCRIBE results");
$columnOrder = 1;
while ($row = $result->fetch_assoc()) {
    echo "{$columnOrder}. {$row['Field']}\n";
    echo "   Type: {$row['Type']}\n";
    echo "   Null: {$row['Null']}\n";
    echo "   Default: " . ($row['Default'] ?? 'NULL') . "\n";
    echo "\n";
    $columnOrder++;
}

echo "=== CHECKING IF COLUMNS ARE IN CORRECT ORDER ===\n\n";

// The expected order based on INSERT statement
$expectedOrder = [
    'id',
    'student_id',
    'exam_id',
    'subject_id',
    'marks_obtained',  // <-- Should be BEFORE total_marks
    'total_marks',     // <-- Should be AFTER marks_obtained
    'grade',
    'semester',
    'upload_id',
    'created_at',
    'updated_at'
];

echo "Expected column order:\n";
foreach ($expectedOrder as $i => $col) {
    echo ($i + 1) . ". $col\n";
}

echo "\n=== ACTUAL DATA SAMPLE ===\n\n";
$result = $conn->query("SELECT id, student_id, marks_obtained, total_marks, grade FROM results ORDER BY id DESC LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Student: {$row['student_id']}, Marks: {$row['marks_obtained']}, Total: {$row['total_marks']}, Grade: {$row['grade']}\n";
}

echo "\n=== CHECKING IF VALUES ARE SWAPPED ===\n\n";
echo "If marks_obtained shows large numbers (like 100) and total_marks shows small numbers (like 28),\n";
echo "then the columns might be SWAPPED in the INSERT statement!\n";
?>
