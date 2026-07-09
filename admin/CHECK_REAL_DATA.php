<?php
require_once '../config/database.php';
header('Content-Type: text/plain');

echo "=== CHECKING REAL UPLOADED DATA ===\n\n";

// Simple query - just results table
$result = $conn->query("SELECT id, exam_id, student_id, subject_id, marks_obtained, total_marks, grade, semester
FROM results
ORDER BY id DESC
LIMIT 10");

if ($result) {
    echo "Found " . $result->num_rows . " results\n\n";

    if ($result->num_rows > 0) {
        echo "ID | ExamID | StudentID | SubjectID | Marks | Total | Grade | Semester\n";
        echo str_repeat("-", 80) . "\n";

        while ($row = $result->fetch_assoc()) {
            printf("%d | %d | %d | %d | %.2f | %.2f | %s | %d\n",
                $row['id'],
                $row['exam_id'],
                $row['student_id'],
                $row['subject_id'],
                $row['marks_obtained'],
                $row['total_marks'],
                $row['grade'],
                $row['semester']
            );

            // Calculate percentage
            $percentage = ($row['total_marks'] > 0)
                ? round(($row['marks_obtained'] / $row['total_marks']) * 100, 2)
                : 0;

            echo "  → Percentage: {$percentage}%\n";

            // Check if data looks correct
            if ($row['marks_obtained'] > $row['total_marks']) {
                echo "  ✗✗✗ ERROR: Marks ({$row['marks_obtained']}) is MORE than Total ({$row['total_marks']})!\n";
            } else if ($row['marks_obtained'] == 0 && $row['total_marks'] > 0) {
                echo "  ✗✗✗ ERROR: Marks is ZERO but Total is {$row['total_marks']}!\n";
            } else if ($percentage > 100) {
                echo "  ✗✗✗ ERROR: Percentage is {$percentage}% (more than 100%)!\n";
            } else if ($row['marks_obtained'] > 0 && $row['total_marks'] > 0) {
                echo "  ✓ Looks OK\n";
            }

            echo "\n";
        }
    } else {
        echo "No results found in database. Upload an Excel file first!\n";
    }
} else {
    echo "ERROR: " . $conn->error . "\n";
}

echo "\n=== END ===\n";
?>
