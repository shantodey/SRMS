<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['teacher_logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - SRMS</title>
    <link rel="stylesheet" href="../css/admin-style.css">
    <style>
        .exam-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .exam-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .exam-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }
        .exam-meta-item {
            display: flex;
            flex-direction: column;
        }
        .exam-meta-label {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .exam-meta-value {
            font-weight: 600;
            color: #2c3e50;
        }
        .exam-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .exam-type-final {
            background: #3498db;
            color: white;
        }
        .exam-type-midterm {
            background: #9b59b6;
            color: white;
        }
        .exam-type-classtest {
            background: #2ecc71;
            color: white;
        }
        .exam-type-assignment {
            background: #f39c12;
            color: white;
        }
        .exam-type-quiz {
            background: #e74c3c;
            color: white;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        .btn-success:hover {
            background: #27ae60;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background: #e67e22;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group select,
        .form-group input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        .close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Exams</h1>

        <div class="btn-group">
            <button class="btn btn-primary" onclick="openCreateExamModal()">+ Create New Exam</button>
        </div>

        <div class="filters">
            <h3>Filter Exams</h3>
            <div class="filter-row">
                <div class="form-group">
                    <label>Exam Type</label>
                    <select id="filter-exam-type">
                        <option value="">All Types</option>
                        <option value="Final">Final</option>
                        <option value="Midterm">Midterm</option>
                        <option value="ClassTest">Class Test</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Quiz">Quiz</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select id="filter-semester">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select id="filter-department">
                        <option value="">All Departments</option>
                        <?php
                        $deptResult = $conn->query("SELECT id, name, code FROM departments ORDER BY name");
                        while ($dept = $deptResult->fetch_assoc()):
                        ?>
                            <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?> (<?= $dept['code'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary" onclick="loadExams()" style="margin-top: 28px;">Apply Filters</button>
                </div>
            </div>
        </div>

        <div id="exam-list"></div>
    </div>

    <!-- Create Exam Modal -->
    <div id="create-exam-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Exam</h2>
                <span class="close" onclick="closeCreateExamModal()">&times;</span>
            </div>
            <form id="create-exam-form">
                <div class="form-group">
                    <label>Exam Type *</label>
                    <select id="exam-type" required onchange="toggleSubjectField()">
                        <option value="">Select Exam Type</option>
                        <option value="Final">Final Exam</option>
                        <option value="Midterm">Midterm Exam</option>
                        <option value="ClassTest">Class Test</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Quiz">Quiz</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester *</label>
                    <select id="semester" required>
                        <option value="">Select Semester</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department *</label>
                    <select id="department-id" required onchange="loadSubjects()">
                        <option value="">Select Department</option>
                        <?php
                        $deptResult = $conn->query("SELECT id, name, code FROM departments ORDER BY name");
                        while ($dept = $deptResult->fetch_assoc()):
                        ?>
                            <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?> (<?= $dept['code'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" id="subject-field" style="display:none;">
                    <label>Subject *</label>
                    <select id="subject-id">
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Marks</label>
                    <input type="number" id="total-marks" placeholder="100">
                </div>
                <div class="form-group">
                    <label>Exam Date</label>
                    <input type="date" id="exam-date">
                </div>
                <div class="form-group">
                    <label>Title (auto-generated if left blank)</label>
                    <input type="text" id="title" placeholder="e.g., CT-3 - Data Structures - Sem 6">
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                    <button type="button" class="btn" onclick="closeCreateExamModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load exams on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadExams();
        });

        function loadExams() {
            const examType = document.getElementById('filter-exam-type').value;
            const semester = document.getElementById('filter-semester').value;
            const departmentId = document.getElementById('filter-department').value;

            let url = 'api/exam_list.php?';
            if (examType) url += `exam_type=${examType}&`;
            if (semester) url += `semester=${semester}&`;
            if (departmentId) url += `department_id=${departmentId}&`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayExams(data.exams);
                    } else {
                        alert('Error loading exams: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading exams');
                });
        }

        function displayExams(exams) {
            const container = document.getElementById('exam-list');

            if (exams.length === 0) {
                container.innerHTML = '<p>No exams found. Create your first exam above.</p>';
                return;
            }

            container.innerHTML = exams.map(exam => `
                <div class="exam-card">
                    <h3>
                        <span class="exam-type-badge exam-type-${exam.exam_type.toLowerCase()}">${exam.exam_type}</span>
                        ${exam.title}
                    </h3>
                    <div class="exam-meta">
                        <div class="exam-meta-item">
                            <span class="exam-meta-label">Semester</span>
                            <span class="exam-meta-value">Semester ${exam.semester}</span>
                        </div>
                        <div class="exam-meta-item">
                            <span class="exam-meta-label">Department</span>
                            <span class="exam-meta-value">${exam.department_name}</span>
                        </div>
                        ${exam.subject_name ? `
                            <div class="exam-meta-item">
                                <span class="exam-meta-label">Subject</span>
                                <span class="exam-meta-value">${exam.subject_name}</span>
                            </div>
                        ` : ''}
                        <div class="exam-meta-item">
                            <span class="exam-meta-label">Total Marks</span>
                            <span class="exam-meta-value">${exam.total_marks || 'N/A'}</span>
                        </div>
                        <div class="exam-meta-item">
                            <span class="exam-meta-label">Exam Date</span>
                            <span class="exam-meta-value">${exam.exam_date || 'Not set'}</span>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-success" onclick="uploadResults(${exam.id})">Upload Results</button>
                        <button class="btn btn-warning" onclick="downloadTemplate(${exam.id})">Download Template</button>
                    </div>
                </div>
            `).join('');
        }

        function openCreateExamModal() {
            document.getElementById('create-exam-modal').style.display = 'block';
        }

        function closeCreateExamModal() {
            document.getElementById('create-exam-modal').style.display = 'none';
            document.getElementById('create-exam-form').reset();
        }

        function toggleSubjectField() {
            const examType = document.getElementById('exam-type').value;
            const subjectField = document.getElementById('subject-field');
            const subjectSelect = document.getElementById('subject-id');

            if (examType === 'ClassTest') {
                subjectField.style.display = 'block';
                subjectSelect.required = true;
            } else {
                subjectField.style.display = 'none';
                subjectSelect.required = false;
            }
        }

        function loadSubjects() {
            const departmentId = document.getElementById('department-id').value;
            const semester = document.getElementById('semester').value;
            const subjectSelect = document.getElementById('subject-id');

            if (!departmentId || !semester) {
                return;
            }

            // Fetch subjects for this department and semester
            fetch(`../get_subjects.php?department_id=${departmentId}&semester=${semester}`)
                .then(response => response.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    data.forEach(subject => {
                        subjectSelect.innerHTML += `<option value="${subject.id}">${subject.subject_name} (${subject.subject_code})</option>`;
                    });
                })
                .catch(error => console.error('Error loading subjects:', error));
        }

        document.getElementById('create-exam-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const data = {
                exam_type: document.getElementById('exam-type').value,
                semester: parseInt(document.getElementById('semester').value),
                department_id: parseInt(document.getElementById('department-id').value),
                subject_id: document.getElementById('subject-id').value || null,
                total_marks: document.getElementById('total-marks').value || null,
                exam_date: document.getElementById('exam-date').value || null,
                title: document.getElementById('title').value || null
            };

            fetch('api/exam_create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Exam created successfully!');
                    closeCreateExamModal();
                    loadExams();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating exam');
            });
        });

        function uploadResults(examId) {
            window.location.href = `upload_results.php?exam_id=${examId}`;
        }

        function downloadTemplate(examId) {
            window.location.href = `api/download_template.php?exam_id=${examId}`;
        }
    </script>
</body>
</html>
