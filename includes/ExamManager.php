<?php
/**
 * ExamManager Class
 *
 * Handles all exam-related operations:
 * - Creating exams
 * - Fetching exams by various filters
 * - Managing exam metadata
 * - Validating exam data
 */

class ExamManager {
    private $conn;

    // Valid exam types
    const EXAM_TYPES = ['Final', 'Midterm', 'ClassTest', 'Assignment', 'Quiz'];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Create a new exam
     *
     * @param array $data Exam data
     * @return array ['success' => bool, 'exam_id' => int|null, 'message' => string]
     */
    public function createExam($data) {
        // Validate required fields
        $required = ['exam_type', 'semester', 'department_id', 'title'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'success' => false,
                    'exam_id' => null,
                    'message' => "Missing required field: $field"
                ];
            }
        }

        // Validate exam_type
        if (!in_array($data['exam_type'], self::EXAM_TYPES)) {
            return [
                'success' => false,
                'exam_id' => null,
                'message' => "Invalid exam_type. Must be one of: " . implode(', ', self::EXAM_TYPES)
            ];
        }

        // Validate business rules
        if ($data['exam_type'] === 'ClassTest' && empty($data['subject_id'])) {
            return [
                'success' => false,
                'exam_id' => null,
                'message' => "ClassTest requires a subject_id"
            ];
        }

        // Auto-increment exam_number for ClassTests
        if ($data['exam_type'] === 'ClassTest' && empty($data['exam_number'])) {
            $data['exam_number'] = $this->getNextExamNumber(
                $data['exam_type'],
                $data['semester'],
                $data['department_id'],
                $data['subject_id']
            );
        }

        // Set defaults
        $data['exam_number'] = $data['exam_number'] ?? 1;
        $data['subject_id'] = $data['subject_id'] ?? null;
        $data['total_marks'] = $data['total_marks'] ?? null;
        $data['exam_date'] = $data['exam_date'] ?? null;
        $data['created_by'] = $data['created_by'] ?? null;

        // Insert exam
        $sql = "INSERT INTO exams (
            exam_type, exam_number, title, semester, department_id,
            subject_id, total_marks, exam_date, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sisiiissi",
            $data['exam_type'],
            $data['exam_number'],
            $data['title'],
            $data['semester'],
            $data['department_id'],
            $data['subject_id'],
            $data['total_marks'],
            $data['exam_date'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            return [
                'success' => true,
                'exam_id' => $this->conn->insert_id,
                'message' => 'Exam created successfully'
            ];
        } else {
            return [
                'success' => false,
                'exam_id' => null,
                'message' => 'Database error: ' . $stmt->error
            ];
        }
    }

    /**
     * Get next exam number for a given exam type and subject
     */
    private function getNextExamNumber($examType, $semester, $departmentId, $subjectId) {
        $sql = "SELECT COALESCE(MAX(exam_number), 0) + 1 as next_number
                FROM exams
                WHERE exam_type = ?
                AND semester = ?
                AND department_id = ?
                AND subject_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siii", $examType, $semester, $departmentId, $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['next_number'];
        }

        return 1;
    }

    /**
     * Get exam by ID
     *
     * @param int $examId
     * @return array|null
     */
    public function getExamById($examId) {
        $sql = "SELECT
                    e.*,
                    d.name as department_name,
                    d.code as department_code,
                    s.subject_name,
                    s.subject_code
                FROM exams e
                INNER JOIN departments d ON e.department_id = d.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                WHERE e.id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Get exams by filters
     *
     * @param array $filters ['exam_type', 'semester', 'department_id', 'subject_id']
     * @param array $options ['order_by', 'limit', 'offset']
     * @return array
     */
    public function getExams($filters = [], $options = []) {
        $where = [];
        $params = [];
        $types = '';

        // Build WHERE clause
        if (!empty($filters['exam_type'])) {
            $where[] = "e.exam_type = ?";
            $params[] = $filters['exam_type'];
            $types .= 's';
        }

        if (!empty($filters['semester'])) {
            $where[] = "e.semester = ?";
            $params[] = $filters['semester'];
            $types .= 'i';
        }

        if (!empty($filters['department_id'])) {
            $where[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 'i';
        }

        if (isset($filters['subject_id'])) {
            if ($filters['subject_id'] === null) {
                $where[] = "e.subject_id IS NULL";
            } else {
                $where[] = "e.subject_id = ?";
                $params[] = $filters['subject_id'];
                $types .= 'i';
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Build ORDER BY
        $orderBy = $options['order_by'] ?? 'e.exam_date DESC, e.exam_number ASC';

        // Build LIMIT
        $limit = '';
        if (isset($options['limit'])) {
            $limit = "LIMIT " . (int)$options['limit'];
            if (isset($options['offset'])) {
                $limit .= " OFFSET " . (int)$options['offset'];
            }
        }

        $sql = "SELECT
                    e.*,
                    d.name as department_name,
                    d.code as department_code,
                    s.subject_name,
                    s.subject_code
                FROM exams e
                INNER JOIN departments d ON e.department_id = d.id
                LEFT JOIN subjects s ON e.subject_id = s.id
                $whereClause
                ORDER BY $orderBy
                $limit";

        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $exams = [];
        while ($row = $result->fetch_assoc()) {
            $exams[] = $row;
        }

        return $exams;
    }

    /**
     * Get class tests for a subject
     *
     * @param int $subjectId
     * @param int $semester
     * @return array
     */
    public function getClassTestsBySubject($subjectId, $semester) {
        return $this->getExams([
            'exam_type' => 'ClassTest',
            'subject_id' => $subjectId,
            'semester' => $semester
        ], [
            'order_by' => 'e.exam_number ASC, e.exam_date DESC'
        ]);
    }

    /**
     * Get semester-wide exams (Final/Midterm)
     *
     * @param string $examType 'Final' or 'Midterm'
     * @param int $semester
     * @param int $departmentId
     * @return array
     */
    public function getSemesterExams($examType, $semester, $departmentId) {
        return $this->getExams([
            'exam_type' => $examType,
            'semester' => $semester,
            'department_id' => $departmentId,
            'subject_id' => null
        ], [
            'order_by' => 'e.exam_date DESC'
        ]);
    }

    /**
     * Update exam
     *
     * @param int $examId
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateExam($examId, $data) {
        $updates = [];
        $params = [];
        $types = '';

        $allowedFields = ['exam_type', 'exam_number', 'title', 'semester',
                         'subject_id', 'total_marks', 'exam_date'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];

                // Determine type
                if (in_array($field, ['exam_number', 'semester', 'subject_id', 'total_marks'])) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
            }
        }

        if (empty($updates)) {
            return [
                'success' => false,
                'message' => 'No fields to update'
            ];
        }

        $params[] = $examId;
        $types .= 'i';

        $sql = "UPDATE exams SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Exam updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Database error: ' . $stmt->error
            ];
        }
    }

    /**
     * Delete exam (CASCADE will delete associated results)
     *
     * @param int $examId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteExam($examId) {
        // Check if exam has results
        $sql = "SELECT COUNT(*) as count FROM results WHERE exam_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];

        if ($count > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete exam: $count result(s) are linked to this exam. Delete results first."
            ];
        }

        $sql = "DELETE FROM exams WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $examId);

        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Exam deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Database error: ' . $stmt->error
            ];
        }
    }

    /**
     * Generate auto title for exam
     *
     * @param array $examData
     * @return string
     */
    public function generateTitle($examData) {
        $parts = [];

        // Exam type prefix
        if ($examData['exam_type'] === 'ClassTest') {
            $parts[] = "CT-" . ($examData['exam_number'] ?? '1');
        } else {
            $parts[] = $examData['exam_type'];
        }

        // Subject or semester
        if (!empty($examData['subject_name'])) {
            $parts[] = $examData['subject_name'];
        } elseif (!empty($examData['semester'])) {
            $parts[] = "Semester " . $examData['semester'];
        }

        // Semester info
        if (!empty($examData['semester'])) {
            $parts[] = "Sem " . $examData['semester'];
        }

        // Date
        if (!empty($examData['exam_date'])) {
            $parts[] = date('M Y', strtotime($examData['exam_date']));
        }

        return implode(' - ', $parts);
    }

    /**
     * Get exams for a student (based on their semester and department)
     *
     * @param int $studentId
     * @param string $examType Optional filter by exam type
     * @return array
     */
    public function getExamsForStudent($studentId, $examType = null) {
        // Get student info
        $sql = "SELECT department_id, semester FROM students WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return [];
        }

        $student = $result->fetch_assoc();

        // Get exams
        $filters = [
            'department_id' => $student['department_id'],
            'semester' => $student['semester']
        ];

        if ($examType) {
            $filters['exam_type'] = $examType;
        }

        return $this->getExams($filters, [
            'order_by' => 'e.exam_type, e.exam_number ASC, e.exam_date DESC'
        ]);
    }
}
