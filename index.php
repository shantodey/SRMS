<?php
session_start();
require_once 'config/database.php';

// Fetch recent published notices for homepage
$notices = [];
try {
    $sql = "SELECT * FROM notices WHERE status = 'published' ORDER BY publish_date DESC, created_at DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row;
        }
    }
} catch (Exception $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/fabicon.png">
    <title>Student Result Management System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white custom-shadow mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <div class="bg-gradient rounded p-2 me-3">
                    <i class="bi bi-mortarboard-fill text-white fs-4"></i>
                </div>
                <div>
                    <h5 class="mb-0 gradient-text fw-bold">SRMS</h5>
                    <small class="text-muted">Student Result Management</small>
                </div>
            </a>
            <a href="admin.php" class="btn btn-outline-primary">
                <i class="bi bi-shield-lock me-2"></i>Admin Panel
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Hero Search Section -->
        <div id="searchSection" class="card custom-shadow border-0 mb-4">
            <div class="card-body p-5 text-center">
                <h1 class="display-4 fw-bold mb-3">Search Your Results</h1>
                <p class="lead text-muted mb-5">Enter your Index Number or Board Roll to view results</p>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="input-group input-group-lg mb-3">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchInput"
                                   placeholder="e.g., IDX2025001 or BR-45001">
                            <button class="btn btn-gradient px-5" onclick="searchResult()">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </div>

                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <span class="badge bg-light text-dark">Example: IDX2025001</span>
                            <span class="badge bg-light text-dark">Example: BR-45001</span>
                        </div>
                    </div>
                </div>

                <!-- Error/Info Messages -->
                <div id="searchMessage" class="mt-3"></div>
            </div>
        </div>

        <!-- Result Section (Hidden Initially) -->
        <div id="resultSection" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-light" onclick="hideResult()">
                    <i class="bi bi-arrow-left me-2"></i>Back to Search
                </button>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="printResult()">
                        <i class="bi bi-printer me-2"></i>Print Result
                    </button>
                    <button class="btn btn-outline-success" onclick="downloadPDF()">
                        <i class="bi bi-download me-2"></i>Download PDF
                    </button>
                </div>
            </div>

            <!-- Student Info Card -->
            <div class="card custom-shadow border-0 mb-4">
                <div class="card-header bg-gradient text-white py-4">
                    <h3 class="mb-3"><i class="bi bi-person-badge me-2"></i>Student Information</h3>
                    <div class="row g-3" id="studentInfo">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>

                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="bi bi-table me-2"></i>Subject Results</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th class="text-center">Marks</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Percentage</th>
                                    <th class="text-center">Grade</th>
                                </tr>
                            </thead>
                            <tbody id="resultTableBody">
                                <!-- Will be populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row g-3 mt-3" id="summaryCards">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Notice Board -->
        <div class="card custom-shadow border-0">
            <div class="card-header bg-white py-4">
                <h3 class="mb-0">
                    <i class="bi bi-megaphone gradient-text me-2"></i>Recent Notices
                </h3>
            </div>
            <div class="card-body p-4" id="noticeBoard">
                <?php if (count($notices) > 0): ?>
                    <?php foreach ($notices as $notice): ?>
                    <div class="notice-card bg-light p-4 rounded mb-3">
                        <h5 class="mb-2"><?php echo htmlspecialchars($notice['title']); ?></h5>
                        <?php if (!empty($notice['content'])): ?>
                        <p class="mb-2 text-muted"><?php echo nl2br(htmlspecialchars(substr($notice['content'], 0, 200))); ?>...</p>
                        <?php endif; ?>
                        <div class="text-muted">
                            <i class="bi bi-calendar-event me-2"></i><?php echo date('F d, Y', strtotime($notice['publish_date'])); ?>
                            <i class="bi bi-person ms-3 me-2"></i>Admin
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                        <p>No notices available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center text-white py-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Student Result Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Searching for results...</h5>
                    <p class="text-muted mb-0">Please wait</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentStudentData = null;
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

        // Search for result
        function searchResult() {
            const searchValue = document.getElementById('searchInput').value.trim();

            if (!searchValue) {
                showMessage('Please enter an Index Number or Board Roll', 'warning');
                return;
            }

            // Show loading
            loadingModal.show();
            clearMessage();

            // Fetch result from backend
            fetch('get_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'search=' + encodeURIComponent(searchValue)
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.hide();

                if (data.success) {
                    currentStudentData = data;
                    displayResult(data);
                } else {
                    showMessage(data.message || 'No results found for this Index/Board Roll', 'danger');
                }
            })
            .catch(error => {
                loadingModal.hide();
                console.error('Error:', error);
                showMessage('An error occurred while searching. Please try again.', 'danger');
            });
        }

        // Display result
        function displayResult(data) {
            // Hide search, show result
            document.getElementById('searchSection').style.display = 'none';
            document.getElementById('resultSection').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Populate student info
            const studentInfo = document.getElementById('studentInfo');
            studentInfo.innerHTML = `
                <div class="col-md-3 col-6">
                    <small class="d-block opacity-75">Student Name</small>
                    <strong class="fs-6">${data.student.student_name}</strong>
                </div>
                <div class="col-md-2 col-6">
                    <small class="d-block opacity-75">Index Number</small>
                    <strong class="fs-6">${data.student.index_no}</strong>
                </div>
                <div class="col-md-2 col-6">
                    <small class="d-block opacity-75">Board Roll</small>
                    <strong class="fs-6">${data.student.board_roll}</strong>
                </div>
                <div class="col-md-2 col-6">
                    <small class="d-block opacity-75">Roll</small>
                    <strong class="fs-6">${data.student.roll_no}</strong>
                </div>
                <div class="col-md-2 col-6">
                    <small class="d-block opacity-75">Department</small>
                    <strong class="fs-6">${data.student.department_name} (${data.student.department_code})</strong>
                </div>
                <div class="col-md-1 col-6">
                    <small class="d-block opacity-75">Batch</small>
                    <strong class="fs-6">${data.student.batch_name}</strong>
                </div>
            `;

            // Populate results table
            const tableBody = document.getElementById('resultTableBody');
            tableBody.innerHTML = '';

            if (data.results && data.results.length > 0) {
                data.results.forEach(result => {
                    const percentage = result.percentage;
                    const grade = result.grade;

                    // Determine badge color based on grade
                    let badgeClass = 'bg-secondary';
                    if (grade === 'A+') badgeClass = 'bg-success';
                    else if (grade === 'A') badgeClass = 'bg-success';
                    else if (grade === 'A-') badgeClass = 'bg-info';
                    else if (grade === 'B') badgeClass = 'bg-warning';
                    else if (grade === 'C') badgeClass = 'bg-warning';
                    else if (grade === 'D') badgeClass = 'bg-danger';
                    else if (grade === 'F') badgeClass = 'bg-danger';

                    const row = `
                        <tr>
                            <td><strong>${result.subject_code}</strong></td>
                            <td>${result.subject_name}</td>
                            <td class="text-center">${result.marks_obtained}</td>
                            <td class="text-center">${result.total_marks}</td>
                            <td class="text-center">${percentage}%</td>
                            <td class="text-center">
                                <span class="badge ${badgeClass} grade-badge">${grade}</span>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No results available</td></tr>';
            }

            // Populate summary cards
            const summaryCards = document.getElementById('summaryCards');
            summaryCards.innerHTML = `
                <div class="col-md-3 col-6">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body">
                            <h6 class="opacity-75 mb-2">Total Subjects</h6>
                            <h2 class="mb-0">${data.summary.total_subjects}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body">
                            <h6 class="opacity-75 mb-2">Total Marks</h6>
                            <h2 class="mb-0">${data.summary.total_marks_obtained}/${data.summary.total_marks_possible}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card bg-info text-white text-center">
                        <div class="card-body">
                            <h6 class="opacity-75 mb-2">Average</h6>
                            <h2 class="mb-0">${data.summary.average_percentage}%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card bg-warning text-white text-center">
                        <div class="card-body">
                            <h6 class="opacity-75 mb-2">Overall Grade</h6>
                            <h2 class="mb-0">${data.summary.overall_grade}</h2>
                        </div>
                    </div>
                </div>
            `;
        }

        // Hide result and show search
        function hideResult() {
            document.getElementById('searchSection').style.display = 'block';
            document.getElementById('resultSection').style.display = 'none';
            document.getElementById('searchInput').value = '';
            currentStudentData = null;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Show message
        function showMessage(message, type) {
            const messageDiv = document.getElementById('searchMessage');
            messageDiv.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        // Clear message
        function clearMessage() {
            document.getElementById('searchMessage').innerHTML = '';
        }

        // Print result
        function printResult() {
            if (!currentStudentData) return;
            window.print();
        }

        // Download PDF
        function downloadPDF() {
            if (!currentStudentData) return;

            const searchValue = currentStudentData.student.index_no || currentStudentData.student.board_roll;
            window.location.href = 'download_result_pdf.php?search=' + encodeURIComponent(searchValue);
        }

        // Enter key support
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchResult();
        });
    </script>
</body>
</html>
