<?php
require_once 'config/database.php';
header('Content-Type: text/plain');

echo "=== RESULTS TABLE STRUCTURE ===\n\n";

$result = $conn->query("DESCRIBE results");

echo "Position | Column Name      | Type           | Null | Default\n";
echo "---------|------------------|----------------|------|--------\n";

$pos = 1;
while ($row = $result->fetch_assoc()) {
    printf("%-8d | %-16s | %-14s | %-4s | %s\n",
        $pos++,
        $row['Field'],
        $row['Type'],
        $row['Null'],
        $row['Default'] ?? 'NULL'
    );
}

echo "\n=== SAMPLE OF ACTUAL DATA (last 3 rows) ===\n\n";
$result = $conn->query("SELECT id, exam_id, student_id, subject_id, marks_obtained, total_marks, percentage, grade, semester FROM results ORDER BY id DESC LIMIT 3");

echo "ID   | ExamID | StudentID | SubjID | Marks    | Total    | Percent  | Grade | Sem\n";
echo "-----|--------|-----------|--------|----------|----------|----------|-------|----\n";

while ($row = $result->fetch_assoc()) {
    printf("%-4d | %-6d | %-9d | %-6d | %-8s | %-8s | %-8s | %-5s | %d\n",
        $row['id'],
        $row['exam_id'],
        $row['student_id'],
        $row['subject_id'],
        $row['marks_obtained'],
        $row['total_marks'],
        $row['percentage'] ?? 'NULL',
        $row['grade'],
        $row['semester']
    );
}
?>
