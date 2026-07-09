<?php
require_once '../config/database.php';
header('Content-Type: text/plain');

echo "=== TESTING INSERT STATEMENT ===\n\n";

// Test values
$testStudentId = 1;
$testExamId = 1;
$testSubjectId = 1;
$testMarksObtained = 28.5;
$testTotalMarks = 30.0;
$testGrade = 'A';
$testSemester = 6;
$testUploadId = 'test_' . time();

echo "Test values we're inserting:\n";
echo "  marks_obtained: $testMarksObtained\n";
echo "  total_marks: $testTotalMarks\n\n";

// Do the insert exactly like the real code
$insertSql = "INSERT INTO results (student_id, exam_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertSql);
$insertStmt->bind_param("iiiddsis", $testStudentId, $testExamId, $testSubjectId, $testMarksObtained, $testTotalMarks, $testGrade, $testSemester, $testUploadId);

if ($insertStmt->execute()) {
    $newId = $conn->insert_id;
    echo "✓ Insert successful! ID: $newId\n\n";

    // Now read it back
    $result = $conn->query("SELECT id, marks_obtained, total_marks FROM results WHERE id = $newId");
    $row = $result->fetch_assoc();

    echo "What got saved in database:\n";
    echo "  marks_obtained: {$row['marks_obtained']}\n";
    echo "  total_marks: {$row['total_marks']}\n\n";

    if ($row['marks_obtained'] == $testMarksObtained && $row['total_marks'] == $testTotalMarks) {
        echo "✓✓✓ VALUES ARE CORRECT! The INSERT is working fine.\n";
        echo "The problem might be in the DISPLAY code (get_results.php)\n";
    } else {
        echo "❌❌❌ VALUES ARE WRONG! The database columns are in wrong order!\n";
        echo "\nExpected:\n";
        echo "  marks_obtained: $testMarksObtained\n";
        echo "  total_marks: $testTotalMarks\n";
        echo "\nActual:\n";
        echo "  marks_obtained: {$row['marks_obtained']}\n";
        echo "  total_marks: {$row['total_marks']}\n";
    }

    // Clean up test data
    $conn->query("DELETE FROM results WHERE id = $newId");
    echo "\n(Test row deleted)\n";
} else {
    echo "❌ Insert failed: " . $insertStmt->error . "\n";
}

echo "\n=== END TEST ===\n";
?>
