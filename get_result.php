<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Get search parameter from POST or GET
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = $_POST['search'] ?? '';
} else {
    $search = $_GET['search'] ?? '';
}

$search = trim($search);

if (empty($search)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a search term (Name, Index Number, Board Roll, Department, or Batch)'
    ]);
    exit;
}

try {
    // ===============================================
    // UNIVERSAL SEARCH - Search by ANYTHING
    // ===============================================
    $sql = "SELECT DISTINCT
                s.id,
                s.student_name,
                s.index_no,
                s.board_roll,
                s.roll_no,
                s.semester as current_semester,
                s.status,
                s.email,
                s.phone,
                s.photo,
                d.name as department_name,
                d.code as department_code,
                b.name as batch_name,
                b.year as batch_year
            FROM students s
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN batches b ON s.batch_id = b.id
            WHERE
                s.student_name LIKE ? OR
                s.index_no = ? OR
                s.board_roll = ? OR
                s.roll_no = ? OR
                d.code = ? OR
                d.name LIKE ? OR
                b.year = ? OR
                b.name LIKE ?
            ORDER BY
                CASE
                    WHEN s.index_no = ? THEN 1
                    WHEN s.board_roll = ? THEN 2
                    WHEN s.roll_no = ? THEN 3
                    ELSE 4
                END,
                s.student_name
            LIMIT 20";

    $stmt = $conn->prepare($sql);
    $searchLike = '%' . $search . '%';

    $stmt->bind_param(
        "sssssssssss",
        $searchLike,  // name LIKE
        $search,      // index_no =
        $search,      // board_roll =
        $search,      // roll_no =
        $search,      // dept code =
        $searchLike,  // dept name LIKE
        $search,      // batch year =
        $searchLike,  // batch name LIKE
        $search,      // ORDER BY index_no =
        $search,      // ORDER BY board_roll =
        $search       // ORDER BY roll_no =
    );

    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any students found
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No student found matching "' . htmlspecialchars($search) . '". Please check and try again.'
        ]);
        exit;
    }

    // If multiple students found, return list for selection
    if ($result->num_rows > 1) {
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'id' => $row['id'],
                'student_name' => $row['student_name'],
                'index_no' => $row['index_no'],
                'board_roll' => $row['board_roll'],
                'department_code' => $row['department_code'],
                'batch_name' => $row['batch_name'],
                'batch_year' => $row['batch_year']
            ];
        }

        echo json_encode([
            'success' => true,
            'multiple_results' => true,
            'count' => count($students),
            'students' => $students,
            'message' => 'Found ' . count($students) . ' students matching your search. Please select one.'
        ]);
        exit;
    }

    // Single student found - Get full details with ALL SEMESTER RESULTS
    $student = $result->fetch_assoc();
    $student_id = $student['id'];

    // ===============================================
    // GET ALL RESULTS FOR ALL SEMESTERS
    // ===============================================
    $sql_results = "SELECT
                        r.id,
                        r.semester,
                        r.marks_obtained,
                        r.percentage,
                        r.grade,
                        r.exam_type,
                        r.exam_date,
                        r.total_marks,
                        sub.subject_code,
                        sub.subject_name,
                        sub.total_marks as subject_total_marks
                    FROM results r
                    LEFT JOIN subjects sub ON r.subject_id = sub.id
                    WHERE r.student_id = ?
                    ORDER BY r.semester ASC, sub.subject_code ASC";

    $stmt_results = $conn->prepare($sql_results);
    $stmt_results->bind_param("i", $student_id);
    $stmt_results->execute();
    $results_data = $stmt_results->get_result();

    // Group results by semester
    $semesters = [];
    $all_subjects = [];

    while ($result_row = $results_data->fetch_assoc()) {
        $semester_num = $result_row['semester'];

        // Use total_marks from results table, fallback to subjects table
        $total_marks = $result_row['total_marks'] ?? $result_row['subject_total_marks'] ?? 100;
        $marks_obtained = $result_row['marks_obtained'];

        // Calculate percentage (use stored value if available)
        $percentage = $result_row['percentage'] ?? ($total_marks > 0 ? round(($marks_obtained / $total_marks) * 100, 2) : 0);

        // Get grade (use stored value if available)
        $grade = $result_row['grade'] ?? getGradeFromPercentage($percentage, $conn);

        $subject_data = [
            'subject_code' => $result_row['subject_code'],
            'subject_name' => $result_row['subject_name'],
            'marks_obtained' => $marks_obtained,
            'total_marks' => $total_marks,
            'percentage' => $percentage,
            'grade' => $grade,
            'exam_type' => $result_row['exam_type'] ?? 'Final',
            'exam_date' => $result_row['exam_date']
        ];

        // Add to all subjects array
        $all_subjects[] = $subject_data;

        // Group by semester
        if (!isset($semesters[$semester_num])) {
            $semesters[$semester_num] = [
                'semester_number' => $semester_num,
                'subjects' => [],
                'total_marks_obtained' => 0,
                'total_marks_possible' => 0
            ];
        }

        $semesters[$semester_num]['subjects'][] = $subject_data;
        $semesters[$semester_num]['total_marks_obtained'] += $marks_obtained;
        $semesters[$semester_num]['total_marks_possible'] += $total_marks;
    }

    // Calculate statistics for each semester
    foreach ($semesters as $sem_num => &$sem_data) {
        $sem_data['total_subjects'] = count($sem_data['subjects']);
        $sem_data['percentage'] = $sem_data['total_marks_possible'] > 0
            ? round(($sem_data['total_marks_obtained'] / $sem_data['total_marks_possible']) * 100, 2)
            : 0;
        $sem_data['grade'] = getGradeFromPercentage($sem_data['percentage'], $conn);
        $sem_data['gpa'] = getGPAFromGrade($sem_data['grade']);
    }

    // Convert to indexed array and sort by semester
    $semesters_array = array_values($semesters);
    usort($semesters_array, function($a, $b) {
        return $a['semester_number'] - $b['semester_number'];
    });

    // ===============================================
    // CALCULATE OVERALL STATISTICS
    // ===============================================
    $total_subjects = count($all_subjects);
    $total_marks_obtained = 0;
    $total_marks_possible = 0;
    $total_gpa_points = 0;

    foreach ($semesters_array as $sem) {
        $total_marks_obtained += $sem['total_marks_obtained'];
        $total_marks_possible += $sem['total_marks_possible'];
        $total_gpa_points += $sem['gpa'];
    }

    $average_percentage = $total_marks_possible > 0
        ? round(($total_marks_obtained / $total_marks_possible) * 100, 2)
        : 0;

    $cumulative_gpa = count($semesters_array) > 0
        ? round($total_gpa_points / count($semesters_array), 2)
        : 0;

    $overall_grade = getGradeFromPercentage($average_percentage, $conn);

    $summary = [
        'total_semesters' => count($semesters_array),
        'total_subjects' => $total_subjects,
        'total_marks_obtained' => $total_marks_obtained,
        'total_marks_possible' => $total_marks_possible,
        'average_percentage' => $average_percentage,
        'overall_grade' => $overall_grade,
        'cumulative_gpa' => $cumulative_gpa
    ];

    // ===============================================
    // RETURN COMPLETE DATA
    // ===============================================
    echo json_encode([
        'success' => true,
        'student' => [
            'id' => $student['id'],
            'student_name' => $student['student_name'],
            'index_no' => $student['index_no'],
            'board_roll' => $student['board_roll'],
            'roll_no' => $student['roll_no'],
            'department_name' => $student['department_name'],
            'department_code' => $student['department_code'],
            'batch_name' => $student['batch_name'],
            'batch_year' => $student['batch_year'],
            'current_semester' => $student['current_semester'],
            'status' => $student['status'] ?? 'active',
            'email' => $student['email'],
            'phone' => $student['phone'],
            'photo' => $student['photo']
        ],
        'semesters' => $semesters_array,  // Grouped by semester
        'all_subjects' => $all_subjects,  // Flat list (for backward compatibility)
        'summary' => $summary
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching results. Please try again later.',
        'error' => $e->getMessage() // Remove in production
    ]);
}

// ===============================================
// HELPER FUNCTIONS
// ===============================================

// Function to get grade based on percentage from grade_scale table
function getGradeFromPercentage($percentage, $conn) {
    try {
        $sql = "SELECT grade FROM grade_scale WHERE ? >= min_percentage ORDER BY min_percentage DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("d", $percentage);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['grade'];
        }
        return 'F'; // Default grade if no match found
    } catch (Exception $e) {
        return 'N/A';
    }
}

// Function to convert grade to GPA (for cumulative calculation)
function getGPAFromGrade($grade) {
    $gpa_map = [
        'A+' => 4.0,
        'A' => 3.75,
        'A-' => 3.5,
        'B+' => 3.25,
        'B' => 3.0,
        'B-' => 2.75,
        'C+' => 2.5,
        'C' => 2.25,
        'C-' => 2.0,
        'D' => 1.0,
        'F' => 0.0
    ];

    return $gpa_map[$grade] ?? 0.0;
}
?>
