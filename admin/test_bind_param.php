<?php
require_once '../config/database.php';
header('Content-Type: text/plain');

echo "=== TESTING BIND_PARAM ORDER ===\n\n";

// Test values (these should be CLEARLY identifiable)
$examId = 99;           // Should go to exam_id column
$studentId = 88888;     // Should go to student_id column
$currentSubjectId = 77; // Should go to subject_id column
$marksObtained = 25.5;  // Should go to marks_obtained column
$totalMarks = 30.0;     // Should go to total_marks column
$grade = 'B+';          // Should go to grade column
$semester = 6;          // Should go to semester column
$uploadId = 'TEST123';  // Should go to upload_id column

echo "Values we're inserting:\n";
echo "  examId = $examId (should be in exam_id column)\n";
echo "  studentId = $studentId (should be in student_id column)\n";
echo "  currentSubjectId = $currentSubjectId (should be in subject_id column)\n";
echo "  marksObtained = $marksObtained (should be in marks_obtained column)\n";
echo "  totalMarks = $totalMarks (should be in total_marks column)\n";
echo "  grade = $grade (should be in grade column)\n";
echo "  semester = $semester (should be in semester column)\n";
echo "  uploadId = $uploadId (should be in upload_id column)\n\n";

// Do the insert exactly like the real code (line 339-343)
$insertSql = "INSERT INTO results (exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertSql);
$insertStmt->bind_param("iiiddsis", $examId, $studentId, $currentSubjectId, $marksObtained, $totalMarks, $grade, $semester, $uploadId);

if ($insertStmt->execute()) {
    $newId = $conn->insert_id;
    echo "✓ Insert successful! ID: $newId\n\n";

    // Now read it back and see what actually got saved
    $result = $conn->query("SELECT * FROM results WHERE id = $newId");
    $row = $result->fetch_assoc();

    echo "What got saved in database:\n";
    echo "  exam_id = {$row['exam_id']}\n";
    echo "  student_id = {$row['student_id']}\n";
    echo "  subject_id = {$row['subject_id']}\n";
    echo "  marks_obtained = {$row['marks_obtained']}\n";
    echo "  total_marks = {$row['total_marks']}\n";
    echo "  grade = {$row['grade']}\n";
    echo "  semester = {$row['semester']}\n";
    echo "  upload_id = {$row['upload_id']}\n\n";

    echo "=== ANALYSIS ===\n";
    $problems = [];

    if ($row['exam_id'] != $examId) {
        $problems[] = "exam_id is {$row['exam_id']}, expected $examId";
    }
    if ($row['student_id'] != $studentId) {
        $problems[] = "student_id is {$row['student_id']}, expected $studentId";
    }
    if ($row['subject_id'] != $currentSubjectId) {
        $problems[] = "subject_id is {$row['subject_id']}, expected $currentSubjectId";
    }
    if (abs($row['marks_obtained'] - $marksObtained) > 0.01) {
        $problems[] = "marks_obtained is {$row['marks_obtained']}, expected $marksObtained";
    }
    if (abs($row['total_marks'] - $totalMarks) > 0.01) {
        $problems[] = "total_marks is {$row['total_marks']}, expected $totalMarks";
    }
    if ($row['grade'] != $grade) {
        $problems[] = "grade is {$row['grade']}, expected $grade";
    }
    if ($row['semester'] != $semester) {
        $problems[] = "semester is {$row['semester']}, expected $semester";
    }
    if ($row['upload_id'] != $uploadId) {
        $problems[] = "upload_id is {$row['upload_id']}, expected $uploadId";
    }

    if (empty($problems)) {
        echo "✓✓✓ ALL VALUES CORRECT! bind_param is working fine.\n";
    } else {
        echo "❌ PROBLEMS FOUND:\n";
        foreach ($problems as $problem) {
            echo "  - $problem\n";
        }
    }

    // Clean up test data
    $conn->query("DELETE FROM results WHERE id = $newId");
    echo "\n(Test row deleted)\n";
} else {
    echo "❌ Insert failed: " . $insertStmt->error . "\n";
}

echo "\n=== END TEST ===\n";
?>
