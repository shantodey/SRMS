<?php
require_once '../config/database.php';
header('Content-Type: text/plain');

echo "=== TESTING ACTUAL INSERT ===\n\n";

// Clean test
$conn->query("DELETE FROM results WHERE student_id = 9999");

// Test insert with known values
$testExamId = 1;
$testStudentId = 9999;
$testSubjectId = 1;
$testMarksObtained = 26.00;
$testTotalMarks = 30.00;
$testGrade = 'A';
$testSemester = 6;
$testUploadId = 'test123';

echo "VALUES WE'RE SENDING:\n";
echo "  examId: $testExamId\n";
echo "  studentId: $testStudentId\n";
echo "  subjectId: $testSubjectId\n";
echo "  marksObtained: $testMarksObtained\n";
echo "  totalMarks: $testTotalMarks\n\n";

// Use EXACT same INSERT as real code
$insertSql = "INSERT INTO results (exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("iiiddsis", $testExamId, $testStudentId, $testSubjectId, $testMarksObtained, $testTotalMarks, $testGrade, $testSemester, $testUploadId);
$stmt->execute();

// Read it back
$result = $conn->query("SELECT * FROM results WHERE student_id = 9999");
$row = $result->fetch_assoc();

echo "WHAT GOT SAVED IN DATABASE:\n";
echo "  exam_id: {$row['exam_id']}\n";
echo "  student_id: {$row['student_id']}\n";
echo "  subject_id: {$row['subject_id']}\n";
echo "  marks_obtained: {$row['marks_obtained']}\n";
echo "  total_marks: {$row['total_marks']}\n";
echo "  grade: {$row['grade']}\n";
echo "  semester: {$row['semester']}\n\n";

if ($row['marks_obtained'] == 26.00 && $row['total_marks'] == 30.00) {
    echo "✓✓✓ SUCCESS! Values saved correctly!\n";
} else {
    echo "❌❌❌ FAILED! Values are WRONG!\n";
    echo "\nThis means either:\n";
    echo "1. Database column types are wrong\n";
    echo "2. Column positions don't match\n";
    echo "3. bind_param types are wrong\n";
}

// Cleanup
$conn->query("DELETE FROM results WHERE student_id = 9999");
?>
