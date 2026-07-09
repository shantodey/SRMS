<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['teacher_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Get exam_id from query param
$examId = $_GET['exam_id'] ?? null;

if (!$examId) {
    header('Location: manage_exams.php');
    exit;
}

// Fetch exam details
$sql = "SELECT e.*, d.name as department_name, d.code as department_code,
               s.subject_name, s.subject_code
        FROM exams e
        INNER JOIN departments d ON e.department_id = d.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $examId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage_exams.php');
    exit;
}

$exam = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results - <?= htmlspecialchars($exam['title']) ?></title>
    <link rel="stylesheet" href="../css/admin-style.css">
    <style>
        .upload-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .exam-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item label {
            display: block;
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .info-item value {
            display: block;
            font-weight: 600;
            color: #2c3e50;
        }
        .upload-zone {
            border: 2px dashed #3498db;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-zone:hover {
            background: #ecf0f1;
            border-color: #2980b9;
        }
        .upload-zone.dragging {
            background: #d5dbdb;
            border-color: #2980b9;
        }
        .upload-icon {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 15px;
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
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .preview-table th,
        .preview-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .preview-table th {
            background: #3498db;
            color: white;
            font-weight: 600;
        }
        .preview-table tr.valid {
            background: #d5f4e6;
        }
        .preview-table tr.invalid {
            background: #fadbd8;
        }
        .preview-table tr.duplicate {
            background: #fef5e7;
        }
        .error-badge {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.85em;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .stat-card.valid {
            background: #d5f4e6;
        }
        .stat-card.invalid {
            background: #fadbd8;
        }
        .stat-card.duplicate {
            background: #fef5e7;
        }
        .conflict-policy {
            display: flex;
            gap: 15px;
            align-items: center;
            margin: 20px 0;
        }
        .conflict-policy label {
            font-weight: 600;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>Upload Results</h1>

        <!-- Exam Information -->
        <div class="section">
            <h2><?= htmlspecialchars($exam['title']) ?></h2>
            <div class="exam-info">
                <div class="info-item">
                    <label>Exam Type</label>
                    <value><?= htmlspecialchars($exam['exam_type']) ?></value>
                </div>
                <div class="info-item">
                    <label>Semester</label>
                    <value>Semester <?= $exam['semester'] ?></value>
                </div>
                <div class="info-item">
                    <label>Department</label>
                    <value><?= htmlspecialchars($exam['department_name']) ?> (<?= htmlspecialchars($exam['department_code']) ?>)</value>
                </div>
                <?php if ($exam['subject_name']): ?>
                <div class="info-item">
                    <label>Subject</label>
                    <value><?= htmlspecialchars($exam['subject_name']) ?> (<?= htmlspecialchars($exam['subject_code']) ?>)</value>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <label>Total Marks</label>
                    <value><?= $exam['total_marks'] ?? 'N/A' ?></value>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="downloadTemplate()">Download Excel Template</button>
                <a href="manage_exams.php" class="btn">Back to Exams</a>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="section" id="upload-section">
            <h2>Upload Excel File</h2>
            <div class="upload-zone" id="upload-zone">
                <div class="upload-icon">📁</div>
                <p>Click to select file or drag and drop here</p>
                <p style="font-size: 0.9em; color: #7f8c8d;">Accepted formats: .xlsx, .xls</p>
                <input type="file" id="file-input" accept=".xlsx,.xls" style="display:none;">
            </div>
        </div>

        <!-- Preview Section -->
        <div class="section hidden" id="preview-section">
            <h2>Upload Preview</h2>

            <div class="stats-grid" id="stats-grid"></div>

            <div class="conflict-policy">
                <label>Conflict Policy:</label>
                <label><input type="radio" name="conflict-policy" value="overwrite" checked> Overwrite existing results</label>
                <label><input type="radio" name="conflict-policy" value="skip"> Skip duplicates</label>
            </div>

            <div>
                <button class="btn btn-success" onclick="commitUpload()">Commit Results</button>
                <button class="btn btn-danger" onclick="cancelUpload()">Cancel</button>
            </div>

            <div id="preview-tables"></div>
        </div>

        <!-- Success Section -->
        <div class="section hidden" id="success-section">
            <h2>Upload Complete</h2>
            <div id="success-message"></div>
            <button class="btn btn-primary" onclick="location.reload()">Upload Another File</button>
            <a href="manage_exams.php" class="btn">Back to Exams</a>
        </div>
    </div>

    <script>
        const examId = <?= $examId ?>;
        let previewData = null;

        // Upload zone drag and drop
        const uploadZone = document.getElementById('upload-zone');
        const fileInput = document.getElementById('file-input');

        uploadZone.addEventListener('click', () => {
            fileInput.click();
        });

        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragging');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragging');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragging');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        function handleFile(file) {
            // Validate file type
            const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
            if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
                alert('Invalid file type. Please upload an Excel file (.xlsx or .xls)');
                return;
            }

            // Upload and preview
            const formData = new FormData();
            formData.append('file', file);
            formData.append('exam_id', examId);

            // Show loading
            uploadZone.innerHTML = '<div class="upload-icon">⏳</div><p>Processing file...</p>';

            fetch('api/upload_preview.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    previewData = data;
                    previewData.filename = file.name;
                    showPreview(data);
                } else {
                    alert('Error: ' + data.message);
                    resetUploadZone();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading file');
                resetUploadZone();
            });
        }

        function resetUploadZone() {
            uploadZone.innerHTML = `
                <div class="upload-icon">📁</div>
                <p>Click to select file or drag and drop here</p>
                <p style="font-size: 0.9em; color: #7f8c8d;">Accepted formats: .xlsx, .xls</p>
            `;
        }

        function showPreview(data) {
            // Hide upload section, show preview
            document.getElementById('upload-section').classList.add('hidden');
            document.getElementById('preview-section').classList.remove('hidden');

            // Display stats
            const statsGrid = document.getElementById('stats-grid');
            statsGrid.innerHTML = `
                <div class="stat-card">
                    <div class="stat-number">${data.stats.total}</div>
                    <div class="stat-label">Total Rows</div>
                </div>
                <div class="stat-card valid">
                    <div class="stat-number">${data.stats.valid}</div>
                    <div class="stat-label">Valid</div>
                </div>
                <div class="stat-card invalid">
                    <div class="stat-number">${data.stats.invalid}</div>
                    <div class="stat-label">Invalid</div>
                </div>
                <div class="stat-card duplicate">
                    <div class="stat-number">${data.stats.duplicates}</div>
                    <div class="stat-label">Duplicates</div>
                </div>
            `;

            // Display preview tables
            const previewTables = document.getElementById('preview-tables');
            let html = '';

            if (data.valid.length > 0) {
                html += '<h3>Valid Rows (first 20)</h3>';
                html += renderPreviewTable(data.valid.slice(0, 20), 'valid');
            }

            if (data.duplicates.length > 0) {
                html += '<h3>Duplicate Rows</h3>';
                html += renderPreviewTable(data.duplicates.slice(0, 20), 'duplicate');
            }

            if (data.invalid.length > 0) {
                html += '<h3>Invalid Rows (first 20)</h3>';
                html += renderPreviewTable(data.invalid.slice(0, 20), 'invalid');
            }

            previewTables.innerHTML = html;
        }

        function renderPreviewTable(rows, rowClass) {
            let html = '<table class="preview-table"><thead><tr>';
            html += '<th>Row</th><th>Index No</th><th>Student Name</th><th>Marks</th><th>Grade</th>';
            if (rowClass === 'invalid') {
                html += '<th>Errors</th>';
            }
            html += '</tr></thead><tbody>';

            rows.forEach(row => {
                html += `<tr class="${rowClass}">`;
                html += `<td>${row.data.row_number}</td>`;
                html += `<td>${row.data.index_no || 'N/A'}</td>`;
                html += `<td>${row.data.student_name || 'N/A'}</td>`;
                html += `<td>${row.data.marks_obtained || 'N/A'} / ${row.data.total_marks || 'N/A'}</td>`;
                html += `<td>${row.data.grade || 'N/A'}</td>`;
                if (rowClass === 'invalid') {
                    html += `<td>${row.errors.map(e => `<span class="error-badge">${e}</span>`).join(' ')}</td>`;
                }
                html += '</tr>';
            });

            html += '</tbody></table>';
            return html;
        }

        function commitUpload() {
            if (!previewData || previewData.stats.valid === 0) {
                alert('No valid rows to commit');
                return;
            }

            if (!confirm(`Commit ${previewData.stats.valid} valid rows to database?`)) {
                return;
            }

            const conflictPolicy = document.querySelector('input[name="conflict-policy"]:checked').value;

            const payload = {
                exam_id: examId,
                valid_rows: previewData.valid,
                conflict_policy: conflictPolicy,
                filename: previewData.filename
            };

            fetch('api/upload_commit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data);
                } else {
                    alert('Error committing results: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error committing results');
            });
        }

        function showSuccess(data) {
            document.getElementById('preview-section').classList.add('hidden');
            document.getElementById('success-section').classList.remove('hidden');

            const message = `
                <div class="stats-grid">
                    <div class="stat-card valid">
                        <div class="stat-number">${data.inserted}</div>
                        <div class="stat-label">Inserted</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.updated}</div>
                        <div class="stat-label">Updated</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${data.skipped}</div>
                        <div class="stat-label">Skipped</div>
                    </div>
                    ${data.errors.length > 0 ? `
                        <div class="stat-card invalid">
                            <div class="stat-number">${data.errors.length}</div>
                            <div class="stat-label">Errors</div>
                        </div>
                    ` : ''}
                </div>
                ${data.errors.length > 0 ? `
                    <h3>Errors:</h3>
                    <ul>
                        ${data.errors.map(e => `<li>${e}</li>`).join('')}
                    </ul>
                ` : ''}
            `;

            document.getElementById('success-message').innerHTML = message;
        }

        function cancelUpload() {
            if (confirm('Cancel upload and discard preview?')) {
                previewData = null;
                document.getElementById('preview-section').classList.add('hidden');
                document.getElementById('upload-section').classList.remove('hidden');
                resetUploadZone();
            }
        }

        function downloadTemplate() {
            window.location.href = `api/download_template.php?exam_id=${examId}`;
        }
    </script>
</body>
</html>
