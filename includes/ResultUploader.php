<?php
/**
 * ResultUploader Class
 *
 * Handles exam result uploads via Excel/CSV:
 * - Parsing uploaded files
 * - Validating data
 * - Preview with error reporting
 * - Batch commit with conflict handling
 * - Audit logging
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class ResultUploader {
    private $conn;
    private $examId;
    private $uploaderId;
    private $uploaderType; // 'admin' or 'teacher'

    // Validation results
    private $validRows = [];
    private $invalidRows = [];
    private $duplicateRows = [];

    public function __construct($conn, $examId, $uploaderId, $uploaderType = 'admin') {
        $this->conn = $conn;
        $this->examId = $examId;
        $this->uploaderId = $uploaderId;
        $this->uploaderType = $uploaderType;
    }

    /**
     * Parse and validate uploaded file
     *
     * @param string $filePath Path to uploaded file
     * @param array $examMetadata Expected exam metadata for validation
     * @return array ['success' => bool, 'valid' => array, 'invalid' => array, 'duplicates' => array, 'stats' => array]
     */
    public function parseAndValidate($filePath, $examMetadata = []) {
        try {
            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) <= 1) {
                return [
                    'success' => false,
                    'message' => 'File appears to be empty or contains only headers',
                    'valid' => [],
                    'invalid' => [],
                    'duplicates' => [],
                    'stats' => ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0]
                ];
            }

            // Extract header
            $headers = array_shift($rows);
            $headerMap = $this->mapHeaders($headers);

            if (!$headerMap['success']) {
                return [
                    'success' => false,
                    'message' => $headerMap['message'],
                    'valid' => [],
                    'invalid' => [],
                    'duplicates' => [],
                    'stats' => ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0]
                ];
            }

            // Get exam info
            $exam = $this->getExamInfo($this->examId);

            if (!$exam) {
                return [
                    'success' => false,
                    'message' => 'Exam not found',
                    'valid' => [],
                    'invalid' => [],
                    'duplicates' => [],
                    'stats' => ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0]
                ];
            }

            // Validate each row
            $rowNumber = 1; // Excel row number (after header)
            foreach ($rows as $row) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $validatedRow = $this->validateRow($row, $headerMap['map'], $exam, $rowNumber);

                if ($validatedRow['valid']) {
                    // Check for duplicates
                    if ($this->isDuplicate($validatedRow['data'])) {
                        $validatedRow['duplicate'] = true;
                        $this->duplicateRows[] = $validatedRow;
                    } else {
                        $this->validRows[] = $validatedRow;
                    }
                } else {
                    $this->invalidRows[] = $validatedRow;
                }
            }

            return [
                'success' => true,
                'valid' => $this->validRows,
                'invalid' => $this->invalidRows,
                'duplicates' => $this->duplicateRows,
                'stats' => [
                    'total' => count($rows),
                    'valid' => count($this->validRows),
                    'invalid' => count($this->invalidRows),
                    'duplicates' => count($this->duplicateRows)
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error parsing file: ' . $e->getMessage(),
                'valid' => [],
                'invalid' => [],
                'duplicates' => [],
                'stats' => ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0]
            ];
        }
    }

    /**
     * Map Excel headers to database columns
     */
    private function mapHeaders($headers) {
        $required = ['index_no', 'board_roll', 'marks_obtained'];
        $headerMap = [];

        // Normalize and map headers
        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim(str_replace(' ', '_', $header)));
            $headerMap[$normalized] = $index;
        }

        // Check if at least one student identifier exists
        if (!isset($headerMap['index_no']) && !isset($headerMap['board_roll'])) {
            return [
                'success' => false,
                'message' => 'Required column missing: Either "index_no" or "board_roll" must be present',
                'map' => []
            ];
        }

        // Check for marks_obtained
        if (!isset($headerMap['marks_obtained'])) {
            return [
                'success' => false,
                'message' => 'Required column missing: "marks_obtained"',
                'map' => []
            ];
        }

        return [
            'success' => true,
            'map' => $headerMap,
            'message' => 'Headers mapped successfully'
        ];
    }

    /**
     * Validate a single row
     */
    private function validateRow($row, $headerMap, $exam, $rowNumber) {
        $errors = [];
        $data = [
            'row_number' => $rowNumber,
            'exam_id' => $this->examId
        ];

        // Extract student identifier
        $indexNo = isset($headerMap['index_no']) ? trim($row[$headerMap['index_no']] ?? '') : '';
        $boardRoll = isset($headerMap['board_roll']) ? trim($row[$headerMap['board_roll']] ?? '') : '';

        if (empty($indexNo) && empty($boardRoll)) {
            $errors[] = "Missing student identifier (index_no or board_roll)";
        }

        // Find student
        $student = $this->findStudent($indexNo, $boardRoll);

        if (!$student) {
            $errors[] = "Student not found with index_no '$indexNo' or board_roll '$boardRoll'";
        } else {
            $data['student_id'] = $student['id'];
            $data['student_name'] = $student['student_name'];
            $data['index_no'] = $student['index_no'];
            $data['board_roll'] = $student['board_roll'];
        }

        // Extract subject (if exam is subject-specific)
        if ($exam['subject_id']) {
            // Exam is subject-specific, use exam's subject
            $data['subject_id'] = $exam['subject_id'];
        } else {
            // Semester-wide exam, need subject_code from file
            if (!isset($headerMap['subject_code'])) {
                $errors[] = "Missing subject_code column (required for semester-wide exams)";
            } else {
                $subjectCode = trim($row[$headerMap['subject_code']] ?? '');
                if (empty($subjectCode)) {
                    $errors[] = "Missing subject_code value";
                } else {
                    $subject = $this->findSubject($subjectCode, $exam['semester'], $exam['department_id']);
                    if (!$subject) {
                        $errors[] = "Subject not found: $subjectCode";
                    } else {
                        $data['subject_id'] = $subject['id'];
                    }
                }
            }
        }

        // Extract marks
        $marksObtained = trim($row[$headerMap['marks_obtained']] ?? '');

        if ($marksObtained === '' || !is_numeric($marksObtained)) {
            $errors[] = "Invalid marks_obtained: must be numeric";
        } else {
            $marksObtained = floatval($marksObtained);
            $data['marks_obtained'] = $marksObtained;
        }

        // Total marks (from Excel or exam default)
        if (isset($headerMap['total_marks']) && !empty($row[$headerMap['total_marks']])) {
            $totalMarks = floatval($row[$headerMap['total_marks']]);
        } else {
            $totalMarks = $exam['total_marks'] ?? 100;
        }
        $data['total_marks'] = $totalMarks;

        // Validate marks range
        if (isset($marksObtained) && ($marksObtained < 0 || $marksObtained > $totalMarks)) {
            $errors[] = "Marks out of range: must be between 0 and $totalMarks";
        }

        // Calculate percentage and grade
        if (isset($marksObtained)) {
            $data['percentage'] = ($marksObtained / $totalMarks) * 100;
            $data['grade'] = $this->calculateGrade($data['percentage']);
        }

        // Optional: remarks
        if (isset($headerMap['remarks'])) {
            $data['remarks'] = trim($row[$headerMap['remarks']] ?? '');
        }

        // Semester from exam
        $data['semester'] = $exam['semester'];

        return [
            'valid' => empty($errors),
            'data' => $data,
            'errors' => $errors,
            'raw_row' => $row
        ];
    }

    /**
     * Find student by index_no or board_roll
     */
    private function findStudent($indexNo, $boardRoll) {
        if (!empty($indexNo) && !empty($boardRoll)) {
            $sql = "SELECT id, student_name, index_no, board_roll, department_id, semester
                    FROM students WHERE index_no = ? OR board_roll = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $indexNo, $boardRoll);
        } elseif (!empty($indexNo)) {
            $sql = "SELECT id, student_name, index_no, board_roll, department_id, semester
                    FROM students WHERE index_no = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $indexNo);
        } elseif (!empty($boardRoll)) {
            $sql = "SELECT id, student_name, index_no, board_roll, department_id, semester
                    FROM students WHERE board_roll = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $boardRoll);
        } else {
            return null;
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Find subject by code
     */
    private function findSubject($subjectCode, $semester, $departmentId) {
        $subjectCode = strtoupper(trim($subjectCode));

        $sql = "SELECT id, subject_name, total_marks
                FROM subjects
                WHERE subject_code = ? AND semester = ? AND department_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $subjectCode, $semester, $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    /**
     * Calculate grade from percentage
     */
    private function calculateGrade($percentage) {
        $sql = "SELECT grade FROM grade_scale
                WHERE min_percentage <= ?
                ORDER BY min_percentage DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("d", $percentage);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['grade'];
        }

        return 'F';
    }

    /**
     * Check if result already exists (duplicate)
     */
    private function isDuplicate($data) {
        if (!isset($data['student_id']) || !isset($data['subject_id'])) {
            return false;
        }

        $sql = "SELECT id FROM results
                WHERE student_id = ? AND exam_id = ? AND subject_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $data['student_id'], $this->examId, $data['subject_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    /**
     * Get exam info
     */
    private function getExamInfo($examId) {
        $sql = "SELECT * FROM exams WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Commit validated results to database
     *
     * @param array $validRows Valid rows from parseAndValidate
     * @param string $conflictPolicy 'overwrite', 'skip', or 'create_revision'
     * @param string $filename Original filename
     * @return array ['success' => bool, 'inserted' => int, 'updated' => int, 'skipped' => int, 'errors' => array]
     */
    public function commit($validRows, $conflictPolicy = 'overwrite', $filename = '') {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $this->conn->begin_transaction();

        try {
            foreach ($validRows as $row) {
                $data = $row['data'];

                // Check if result exists
                $existingId = $this->getExistingResultId($data['student_id'], $this->examId, $data['subject_id']);

                if ($existingId) {
                    // Handle conflict
                    if ($conflictPolicy === 'skip') {
                        $skipped++;
                        continue;
                    } elseif ($conflictPolicy === 'overwrite') {
                        // Log audit trail before update
                        $this->logAudit($existingId, 'update', $data);

                        // Update existing result
                        if ($this->updateResult($existingId, $data)) {
                            $updated++;
                        } else {
                            $errors[] = "Row {$data['row_number']}: Failed to update";
                        }
                    } elseif ($conflictPolicy === 'create_revision') {
                        // Not implemented in this version
                        $errors[] = "Row {$data['row_number']}: Revision not implemented";
                    }
                } else {
                    // Insert new result
                    $resultId = $this->insertResult($data);
                    if ($resultId) {
                        $this->logAudit($resultId, 'insert', $data);
                        $inserted++;
                    } else {
                        $errors[] = "Row {$data['row_number']}: Failed to insert";
                    }
                }
            }

            // Log upload
            $this->logUpload($filename, count($validRows), $inserted + $updated, count($errors), $errors);

            $this->conn->commit();

            return [
                'success' => true,
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['Transaction failed: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Get existing result ID
     */
    private function getExistingResultId($studentId, $examId, $subjectId) {
        $sql = "SELECT id FROM results WHERE student_id = ? AND exam_id = ? AND subject_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $studentId, $examId, $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row['id'];
        }

        return null;
    }

    /**
     * Insert new result
     */
    private function insertResult($data) {
        $sql = "INSERT INTO results (student_id, exam_id, subject_id, marks_obtained, percentage, total_marks, grade, semester, exam_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iiiddiis",
            $data['student_id'],
            $data['exam_id'],
            $data['subject_id'],
            $data['marks_obtained'],
            $data['percentage'],
            $data['total_marks'],
            $data['grade'],
            $data['semester']
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return null;
    }

    /**
     * Update existing result
     */
    private function updateResult($resultId, $data) {
        $sql = "UPDATE results
                SET marks_obtained = ?, percentage = ?, total_marks = ?, grade = ?, exam_date = CURDATE()
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ddisi",
            $data['marks_obtained'],
            $data['percentage'],
            $data['total_marks'],
            $data['grade'],
            $resultId
        );

        return $stmt->execute();
    }

    /**
     * Log audit trail
     */
    private function logAudit($resultId, $action, $newData) {
        // Get previous data if updating
        $previousMarks = null;
        $previousGrade = null;

        if ($action === 'update') {
            $sql = "SELECT marks_obtained, grade FROM results WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $resultId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $previousMarks = $row['marks_obtained'];
                $previousGrade = $row['grade'];
            }
        }

        $sql = "INSERT INTO result_audit_log (
                    result_id, student_id, exam_id, subject_id, action,
                    previous_marks, new_marks, previous_grade, new_grade,
                    changed_by, changed_by_type, reason
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Excel upload')";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iiiisddssss",
            $resultId,
            $newData['student_id'],
            $this->examId,
            $newData['subject_id'],
            $action,
            $previousMarks,
            $newData['marks_obtained'],
            $previousGrade,
            $newData['grade'],
            $this->uploaderId,
            $this->uploaderType
        );

        $stmt->execute();
    }

    /**
     * Log upload operation
     */
    private function logUpload($filename, $rowsTotal, $rowsSuccess, $rowsFailed, $errors) {
        $errorJson = json_encode($errors);

        $sql = "INSERT INTO upload_logs (
                    exam_id, filename, uploader_id, uploader_type,
                    rows_total, rows_success, rows_failed, error_log
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ississis",
            $this->examId,
            $filename,
            $this->uploaderId,
            $this->uploaderType,
            $rowsTotal,
            $rowsSuccess,
            $rowsFailed,
            $errorJson
        );

        $stmt->execute();
    }
}
