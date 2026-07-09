<?php
require_once '../config/database.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Diagnostic</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 2px solid #333; }
        .good { color: green; font-weight: bold; }
        .bad { color: red; font-weight: bold; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #666; padding: 8px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>

<h1>🔍 COMPLETE DIAGNOSTIC - TRACE EVERYTHING</h1>

<?php
// ========================================
// STEP 1: CHECK DATABASE STRUCTURE
// ========================================
echo "<div class='section'>";
echo "<h2>STEP 1: Database Table Structure</h2>";

$result = $conn->query("DESCRIBE results");
echo "<table><tr><th>Position</th><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th></tr>";
$pos = 1;
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>$pos</td>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
    $pos++;
}
echo "</table>";

// Check if columns are in correct position
echo "<p><strong>Expected position for INSERT:</strong></p>";
echo "<p>INSERT INTO results (exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id)</p>";
echo "<p>This expects: exam_id at position 2, student_id at position 3</p>";

$result = $conn->query("SHOW COLUMNS FROM results");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$examIdPos = array_search('exam_id', $columns) + 1;
$studentIdPos = array_search('student_id', $columns) + 1;
$marksPos = array_search('marks_obtained', $columns) + 1;
$totalPos = array_search('total_marks', $columns) + 1;

echo "<p>Actual positions: exam_id=$examIdPos, student_id=$studentIdPos, marks_obtained=$marksPos, total_marks=$totalPos</p>";

if ($examIdPos == 2 && $studentIdPos == 3) {
    echo "<p class='good'>✓ Column order matches INSERT statement!</p>";
} else {
    echo "<p class='bad'>✗ Column order DOES NOT match INSERT statement!</p>";
}

echo "</div>";

// ========================================
// STEP 2: TEST INSERT
// ========================================
echo "<div class='section'>";
echo "<h2>STEP 2: Test INSERT with Known Values</h2>";

// Clean up test
$conn->query("DELETE FROM results WHERE student_id = 99999");

// Test values
$testExamId = 1;
$testStudentId = 99999;
$testSubjectId = 1;
$testMarks = 26.5;
$testTotal = 30.0;
$testGrade = 'A';
$testSemester = 6;
$testUploadId = 'test_diagnostic';

echo "<p><strong>Values we're inserting:</strong></p>";
echo "<ul>";
echo "<li>exam_id: $testExamId</li>";
echo "<li>student_id: $testStudentId</li>";
echo "<li>subject_id: $testSubjectId</li>";
echo "<li>marks_obtained: $testMarks</li>";
echo "<li>total_marks: $testTotal</li>";
echo "<li>grade: $testGrade</li>";
echo "<li>semester: $testSemester</li>";
echo "<li>upload_id: $testUploadId</li>";
echo "</ul>";

// Use EXACT same INSERT as process_excel_upload.php
$insertSql = "INSERT INTO results (exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("iiiddsis", $testExamId, $testStudentId, $testSubjectId, $testMarks, $testTotal, $testGrade, $testSemester, $testUploadId);

if ($stmt->execute()) {
    echo "<p class='good'>✓ INSERT executed successfully</p>";

    // Read it back
    $result = $conn->query("SELECT * FROM results WHERE student_id = 99999");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        echo "<p><strong>What got saved in database:</strong></p>";
        echo "<table>";
        echo "<tr><th>Column</th><th>Expected</th><th>Actual</th><th>Match?</th></tr>";

        $checks = [
            ['exam_id', $testExamId, $row['exam_id']],
            ['student_id', $testStudentId, $row['student_id']],
            ['subject_id', $testSubjectId, $row['subject_id']],
            ['marks_obtained', $testMarks, $row['marks_obtained']],
            ['total_marks', $testTotal, $row['total_marks']],
            ['grade', $testGrade, $row['grade']],
            ['semester', $testSemester, $row['semester']],
        ];

        $allCorrect = true;
        foreach ($checks as $check) {
            echo "<tr>";
            echo "<td><strong>{$check[0]}</strong></td>";
            echo "<td>{$check[1]}</td>";
            echo "<td>{$check[2]}</td>";

            if ($check[1] == $check[2]) {
                echo "<td class='good'>✓ YES</td>";
            } else {
                echo "<td class='bad'>✗ NO</td>";
                $allCorrect = false;
            }
            echo "</tr>";
        }
        echo "</table>";

        if ($allCorrect) {
            echo "<p class='good'>✓✓✓ ALL VALUES SAVED CORRECTLY!</p>";
        } else {
            echo "<p class='bad'>✗✗✗ VALUES ARE WRONG! Column positions are mixed up!</p>";
        }
    }

    // Clean up
    $conn->query("DELETE FROM results WHERE student_id = 99999");
} else {
    echo "<p class='bad'>✗ INSERT failed: " . $stmt->error . "</p>";
}

echo "</div>";

// ========================================
// STEP 3: CHECK ACTUAL UPLOADED DATA
// ========================================
echo "<div class='section'>";
echo "<h2>STEP 3: Real Data in Database (Last 5 uploads)</h2>";

$result = $conn->query("SELECT
    r.id,
    r.exam_id,
    r.student_id,
    s.student_name,
    s.index_no,
    r.subject_id,
    subj.subject_code,
    r.marks_obtained,
    r.total_marks,
    ROUND((r.marks_obtained / r.total_marks) * 100, 2) as percentage,
    r.grade
FROM results r
LEFT JOIN students s ON r.student_id = s.id
LEFT JOIN subjects subj ON r.subject_id = subj.id
ORDER BY r.id DESC
LIMIT 5");

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th><th>Student</th><th>Index</th><th>Subject</th>";
    echo "<th>Marks</th><th>Total</th><th>%</th><th>Grade</th><th>Status</th>";
    echo "</tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['student_name']}</td>";
        echo "<td>{$row['index_no']}</td>";
        echo "<td>{$row['subject_code']}</td>";
        echo "<td>{$row['marks_obtained']}</td>";
        echo "<td>{$row['total_marks']}</td>";
        echo "<td>{$row['percentage']}%</td>";
        echo "<td>{$row['grade']}</td>";

        // Check if data looks correct
        if ($row['marks_obtained'] > 0 && $row['total_marks'] > 0 &&
            $row['marks_obtained'] <= $row['total_marks'] &&
            $row['percentage'] <= 100) {
            echo "<td class='good'>✓ OK</td>";
        } else {
            echo "<td class='bad'>✗ WRONG</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No data found. Upload an Excel file first!</p>";
}

echo "</div>";

// ========================================
// STEP 4: CHECK API RESPONSE
// ========================================
echo "<div class='section'>";
echo "<h2>STEP 4: What API Returns (get_results.php)</h2>";

$result = $conn->query("SELECT r.*,
    s.student_name, s.index_no, s.board_roll,
    e.exam_type, e.title as exam_title,
    subj.subject_code, subj.subject_name,
    d.name as department_name
FROM results r
INNER JOIN students s ON r.student_id = s.id
INNER JOIN exams e ON r.exam_id = e.id
INNER JOIN subjects subj ON r.subject_id = subj.id
LEFT JOIN departments d ON s.department_id = d.id
ORDER BY r.id DESC
LIMIT 3");

if ($result->num_rows > 0) {
    echo "<p><strong>Sample API response (what JavaScript receives):</strong></p>";

    while ($row = $result->fetch_assoc()) {
        echo "<pre>";
        echo "{\n";
        echo "  id: {$row['id']}\n";
        echo "  student_name: {$row['student_name']}\n";
        echo "  index_no: {$row['index_no']}\n";
        echo "  marks_obtained: {$row['marks_obtained']}\n";
        echo "  total_marks: {$row['total_marks']}\n";

        $percentage = ($row['total_marks'] > 0)
            ? round(($row['marks_obtained'] / $row['total_marks']) * 100, 2)
            : 0;
        echo "  calculated_percentage: {$percentage}%\n";
        echo "  grade: {$row['grade']}\n";
        echo "}\n";
        echo "</pre>";

        if ($percentage > 100 || $percentage < 0) {
            echo "<p class='bad'>✗ Percentage is WRONG! {$row['marks_obtained']} / {$row['total_marks']} = {$percentage}%</p>";
        } else {
            echo "<p class='good'>✓ Percentage looks correct</p>";
        }
        echo "<hr>";
    }
}

echo "</div>";

// ========================================
// STEP 5: RECOMMENDATIONS
// ========================================
echo "<div class='section'>";
echo "<h2>STEP 5: Diagnosis Summary</h2>";

echo "<p><strong>If STEP 2 shows values are CORRECT but STEP 3 shows WRONG data:</strong></p>";
echo "<ul>";
echo "<li>The INSERT code is working fine</li>";
echo "<li>The problem is OLD data uploaded with broken code</li>";
echo "<li><strong>Solution:</strong> DELETE FROM results; then re-upload Excel files</li>";
echo "</ul>";

echo "<p><strong>If STEP 2 shows values are WRONG:</strong></p>";
echo "<ul>";
echo "<li>Database column positions don't match INSERT statement</li>";
echo "<li><strong>Solution:</strong> Need to fix INSERT column order in process_excel_upload.php</li>";
echo "</ul>";

echo "<p><strong>If STEP 3 shows correct data but STEP 4 shows wrong percentage:</strong></p>";
echo "<ul>";
echo "<li>The calculation in JavaScript (manage_results.js) is wrong</li>";
echo "<li><strong>Solution:</strong> Fix the percentage calculation in JavaScript</li>";
echo "</ul>";

echo "</div>";

?>

</body>
</html>
