<?php
/**
 * Student-facing page for browsing exam results
 * Allows filtering by exam type (Final, Midterm, Class Test)
 * and drilling down to specific tests
 */
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Results - SRMS</title>
    <link rel="stylesheet" href="css/bbk-style.css">
    <style>
        .browse-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .search-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            padding: 15px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .search-box button {
            padding: 15px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .search-box button:hover {
            background: #2980b9;
        }
        .student-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }
        .exam-type-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab:hover {
            color: #3498db;
        }
        .tab.active {
            color: #3498db;
            border-bottom-color: #3498db;
            font-weight: 600;
        }
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .subject-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .subject-card:hover {
            background: #e9ecef;
            border-color: #3498db;
            transform: translateY(-2px);
        }
        .subject-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .subject-code {
            font-size: 0.85em;
            color: #7f8c8d;
        }
        .exam-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .exam-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            cursor: pointer;
            transition: all 0.3s;
        }
        .exam-item:hover {
            background: #ecf0f1;
            transform: translateX(5px);
        }
        .exam-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .exam-meta {
            font-size: 0.85em;
            color: #7f8c8d;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .results-table th,
        .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .results-table th {
            background: #3498db;
            color: white;
            font-weight: 600;
        }
        .results-table tr:hover {
            background: #f8f9fa;
        }
        .grade-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .grade-A\+ { background: #27ae60; color: white; }
        .grade-A { background: #2ecc71; color: white; }
        .grade-A- { background: #3498db; color: white; }
        .grade-B { background: #f39c12; color: white; }
        .grade-C { background: #e67e22; color: white; }
        .grade-D { background: #e74c3c; color: white; }
        .grade-F { background: #95a5a6; color: white; }
        .back-button {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background: #7f8c8d;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="browse-container">
        <h1>Browse Exam Results</h1>

        <!-- Search Section -->
        <div class="search-section">
            <h2>Search Student</h2>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Enter Index Number, Board Roll, or Student Name">
                <button onclick="searchStudent()">Search</button>
            </div>
            <p style="color: #7f8c8d; font-size: 0.9em;">
                Enter student's Index Number, Board Roll, or Name to view their exam results
            </p>
        </div>

        <!-- Student Results Section -->
        <div id="results-section" class="hidden">
            <!-- Student Info Card -->
            <div class="student-card" id="student-card"></div>

            <!-- Exam Type Tabs -->
            <div class="exam-type-tabs">
                <button class="tab active" data-type="all" onclick="switchTab('all')">All Exams</button>
                <button class="tab" data-type="Final" onclick="switchTab('Final')">Final</button>
                <button class="tab" data-type="Midterm" onclick="switchTab('Midterm')">Midterm</button>
                <button class="tab" data-type="ClassTest" onclick="switchTab('ClassTest')">Class Tests</button>
                <button class="tab" data-type="Assignment" onclick="switchTab('Assignment')">Assignments</button>
                <button class="tab" data-type="Quiz" onclick="switchTab('Quiz')">Quizzes</button>
            </div>

            <!-- Content Area -->
            <div id="content-area"></div>
        </div>
    </div>

    <script>
        let currentStudent = null;
        let currentExamType = 'all';

        function searchStudent() {
            const searchTerm = document.getElementById('search-input').value.trim();

            if (!searchTerm) {
                alert('Please enter a search term');
                return;
            }

            fetch(`get_result.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.students && data.students.length > 0) {
                        // If multiple matches, use first one (or show selection UI)
                        currentStudent = data.students[0];
                        loadStudentResults(currentStudent.id);
                    } else {
                        alert('No student found with that search term');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error searching for student');
                });
        }

        function loadStudentResults(studentId) {
            // Fetch student info with exams
            fetch(`api/student_exams.php?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentStudent = data.student;
                        displayStudentInfo(data.student);
                        displayExamsByType(data.exams, currentExamType);
                        document.getElementById('results-section').classList.remove('hidden');
                    } else {
                        alert('Error loading student results: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading student results');
                });
        }

        function displayStudentInfo(student) {
            const card = document.getElementById('student-card');
            card.innerHTML = `
                <h2>${student.student_name}</h2>
                <div class="student-info">
                    <div class="info-item">
                        <span class="info-label">Index Number</span>
                        <span class="info-value">${student.index_no}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Board Roll</span>
                        <span class="info-value">${student.board_roll}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department</span>
                        <span class="info-value">${student.department_name}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Semester</span>
                        <span class="info-value">Semester ${student.semester}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Batch</span>
                        <span class="info-value">${student.batch_name}</span>
                    </div>
                </div>
            `;
        }

        function switchTab(examType) {
            currentExamType = examType;

            // Update tab styling
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.type === examType) {
                    tab.classList.add('active');
                }
            });

            // Reload exams for this type
            loadStudentResults(currentStudent.id);
        }

        function displayExamsByType(exams, examType) {
            const contentArea = document.getElementById('content-area');

            // Filter exams by type
            const filteredExams = examType === 'all'
                ? exams
                : exams.filter(e => e.exam_type === examType);

            if (filteredExams.length === 0) {
                contentArea.innerHTML = '<p>No exam results found for this type.</p>';
                return;
            }

            // For ClassTest, group by subject
            if (examType === 'ClassTest') {
                displayClassTestsBySubject(filteredExams);
            } else {
                displayExamList(filteredExams);
            }
        }

        function displayClassTestsBySubject(exams) {
            const contentArea = document.getElementById('content-area');

            // Group exams by subject
            const bySubject = {};
            exams.forEach(exam => {
                const subjectId = exam.subject_id;
                if (!bySubject[subjectId]) {
                    bySubject[subjectId] = {
                        subject_name: exam.subject_name,
                        subject_code: exam.subject_code,
                        exams: []
                    };
                }
                bySubject[subjectId].exams.push(exam);
            });

            let html = '<h3>Select a Subject</h3><div class="subject-grid">';

            Object.values(bySubject).forEach(subject => {
                html += `
                    <div class="subject-card" onclick='showClassTests(${JSON.stringify(subject.exams)})'>
                        <div class="subject-name">${subject.subject_name}</div>
                        <div class="subject-code">${subject.subject_code}</div>
                        <div class="subject-code">${subject.exams.length} test(s)</div>
                    </div>
                `;
            });

            html += '</div>';
            contentArea.innerHTML = html;
        }

        function showClassTests(exams) {
            const contentArea = document.getElementById('content-area');

            let html = '<button class="back-button" onclick="switchTab(\'ClassTest\')">← Back to Subjects</button>';
            html += '<h3>Class Tests</h3><div class="exam-list">';

            exams.forEach(exam => {
                html += `
                    <div class="exam-item" onclick="viewExamResults(${exam.id})">
                        <div class="exam-title">CT-${exam.exam_number || '1'}: ${exam.title}</div>
                        <div class="exam-meta">Date: ${exam.exam_date || 'Not set'} | Total Marks: ${exam.total_marks || 'N/A'}</div>
                    </div>
                `;
            });

            html += '</div>';
            contentArea.innerHTML = html;
        }

        function displayExamList(exams) {
            const contentArea = document.getElementById('content-area');

            let html = '<div class="exam-list">';

            exams.forEach(exam => {
                html += `
                    <div class="exam-item" onclick="viewExamResults(${exam.id})">
                        <div class="exam-title">${exam.title}</div>
                        <div class="exam-meta">
                            Type: ${exam.exam_type} |
                            Date: ${exam.exam_date || 'Not set'} |
                            ${exam.subject_name ? 'Subject: ' + exam.subject_name + ' | ' : ''}
                            Total Marks: ${exam.total_marks || 'N/A'}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            contentArea.innerHTML = html;
        }

        function viewExamResults(examId) {
            // Fetch results for this exam
            fetch(`api/exam_results.php?exam_id=${examId}&student_id=${currentStudent.id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayExamResults(data.exam, data.results);
                    } else {
                        alert('Error loading exam results: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading exam results');
                });
        }

        function displayExamResults(exam, results) {
            const contentArea = document.getElementById('content-area');

            let html = `<button class="back-button" onclick="switchTab('${exam.exam_type}')">← Back</button>`;
            html += `<h3>${exam.title}</h3>`;

            if (results.length === 0) {
                html += '<p>No results found for this exam.</p>';
            } else {
                html += '<table class="results-table"><thead><tr>';
                html += '<th>Subject</th><th>Marks Obtained</th><th>Total Marks</th><th>Percentage</th><th>Grade</th>';
                html += '</tr></thead><tbody>';

                results.forEach(result => {
                    html += `
                        <tr>
                            <td>${result.subject_name} (${result.subject_code})</td>
                            <td>${result.marks_obtained}</td>
                            <td>${result.total_marks}</td>
                            <td>${result.percentage.toFixed(2)}%</td>
                            <td><span class="grade-badge grade-${result.grade.replace('+', '\\+')}">${result.grade}</span></td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
            }

            contentArea.innerHTML = html;
        }
    </script>
</body>
</html>
