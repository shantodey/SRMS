<?php
session_start();
require_once 'config/database.php';
require_once 'admin/auth.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Fetch statistics for dashboard
$stats = [
    'total_students' => 0,
    'published_results' => 0,
    'total_departments' => 0,
    'active_notices' => 0
];

try {
    // Get total students
    $result = $conn->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $result->fetch_assoc()['count'];

    // Get published results count
    $result = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM results");
    $stats['published_results'] = $result->fetch_assoc()['count'];

    // Get total departments
    $result = $conn->query("SELECT COUNT(*) as count FROM departments");
    $stats['total_departments'] = $result->fetch_assoc()['count'];

    // Get active notices
    $result = $conn->query("SELECT COUNT(*) as count FROM notices WHERE status = 'published'");
    $stats['active_notices'] = $result->fetch_assoc()['count'];
} catch (Exception $e) {
    // Handle error silently for now
}

// Fetch batches
$batches = [];
try {
    $result = $conn->query("SELECT * FROM batches ORDER BY year DESC");
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
} catch (Exception $e) {
    // Handle error
}

// Fetch departments
$departments = [];
try {
    $result = $conn->query("SELECT * FROM departments ORDER BY name ASC");
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
} catch (Exception $e) {
    // Handle error
}

// Fetch grade scale
$grade_scale = [];
try {
    $result = $conn->query("SELECT * FROM grade_scale ORDER BY min_percentage DESC");
    while ($row = $result->fetch_assoc()) {
        $grade_scale[] = $row;
    }
} catch (Exception $e) {
    // Handle error
}

// Get admin email from session (default for now)
$admin_email = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'admin@srms.edu';
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="icon" type="image/x-icon" href="assets/admin.png">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">

</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()" aria-label="Toggle Menu">
        <i class="bi bi-list"></i>
    </button>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="closeMobileSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-shield-check me-2"></i>Admin</h4>
            <small>Management Panel</small>
        </div>

       <ul class="nav-menu">
    <li>
        <a href="javascript:void(0)" class="active" onclick="showSection('dashboard', event)">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('importStudents', event)">
            <i class="bi bi-people-fill"></i>
            <span>Import Students</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('importResults', event)">
            <i class="bi bi-file-earmark-bar-graph-fill"></i>
            <span>Import Results</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('manageStudents', event)">
            <i class="bi bi-person-lines-fill"></i>
            <span>Manage Students</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('manageNotices', event)">
            <i class="bi bi-megaphone-fill"></i>
            <span>Manage Notices</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('settings', event)">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('batches', event)">
            <i class="bi bi-collection-fill"></i>
            <span>Batches & Departments</span>
        </a>
    </li>
    <li>
        <a href="javascript:void(0)" onclick="showSection('reports', event)">
            <i class="bi bi-file-text-fill"></i>
            <span>Reports</span>
        </a>
    </li>
    <li>
        <a href="admin/logout.php" style="margin-top: 50px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </li>
</ul>

    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h2 id="pageTitle">Dashboard</h2>
                <small class="text-muted">Welcome back, Admin!</small>
            </div>
            <div class="user-info">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($admin_name); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($admin_email); ?></small>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 2)); ?></div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="page-section active">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="aa">
                            <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                        
                        <p>Total Students</p>
                        <h3 id="totalStudents"><?php echo $stats['total_students']; ?></h3>
                        <small class="text-muted">Currently enrolled</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="aa">
                            <div class="stat-icon" style="background: #dcfce7; color: #166534;">
                                <i class="bi bi-journal-check"></i>
                            </div>
                        </div>
                        <p>Published Results</p>
                        <h3 id="publishedResults"><?php echo $stats['published_results']; ?></h3>
                        <small class="text-muted">Total published results</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="aa">
                            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                        <p>Departments</p>
                        <h3 id="totalDepartments"><?php echo $stats['total_departments']; ?></h3>
                        <small class="text-muted">Across all batches</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="aa">
                            <div class="stat-icon" style="background: #fce7f3; color: #9f1239;">
                                <i class="bi bi-megaphone"></i>
                            </div>
                        </div>
                        <p>Active Notices</p>
                        <h3 id="activeNotices"><?php echo $stats['active_notices']; ?></h3>
                        <small class="text-muted">Currently published</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="content-card">
                        <h4><i class="bi bi-activity me-2"></i>Recent Activities</h4>
                        <div class="list-group list-group-flush" id="recentActivities">
                            <div class="list-group-item d-flex justify-content-between align-items-center border-0">
                                <div>
                                    <i class="bi bi-info-circle text-muted me-2"></i>
                                    <strong>No recent activities</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="content-card">
                        <h4><i class="bi bi-calendar-event me-2"></i>Quick Actions</h4>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="showSection('importStudents', event)">
                                <i class="bi bi-upload me-2"></i>Import Students
                            </button>
                            <button class="btn btn-success" onclick="showSection('importResults', event)">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i>Import Results
                            </button>
                            <button class="btn btn-info" onclick="showSection('manageNotices', event)">
                                <i class="bi bi-megaphone me-2"></i>Create Notice
                            </button>
                            <button class="btn btn-warning" onclick="showSection('reports', event)">
                                <i class="bi bi-download me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Students Section -->
        <div id="importStudents" class="page-section">
            <div class="content-card">
                <h4><i class="bi bi-people-fill me-2"></i>Import Students from Excel</h4>
                <p class="text-muted mb-4">Upload an Excel file with student data. Required columns: batch, semester, department, student_name, roll, index_no, board_roll</p>

                <div id="studentsUploadZone" class="upload-zone">
                    <i class="bi bi-cloud-upload"></i>
                    <h5>Drag & Drop Excel File Here</h5>
                    <p class="text-muted">or click to browse</p>
                    <button class="btn btn-primary mt-3">
                        <i class="bi bi-folder2-open me-2"></i>Choose File
                    </button>
                </div>

                <div id="studentsPreview" class="mt-4">
                    <!-- Preview will be shown here -->
                </div>

                <div class="mt-4">
                    <a href="admin/generate_templates.php?type=students" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-download me-2"></i>Download Template
                    </a>
                </div>
            </div>

            <div class="content-card">
                <h4><i class="bi bi-clock-history me-2"></i>Recent Imports</h4>
                <div class="data-table" id="recentImportsTable">
                    <p class="text-muted">No recent imports found.</p>
                </div>
            </div>
        </div>

        <!-- Import Results Section -->
        <div id="importResults" class="page-section">
            <div class="content-card">
                <h4><i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Import Results from Excel</h4>
                <p class="text-muted mb-4">Upload an Excel file with result data. Required columns: index_no, board_roll, subject_code, subject_name, marks_obtained, total_marks</p>

                <div id="resultsUploadZone" class="upload-zone">
                    <i class="bi bi-cloud-upload"></i>
                    <h5>Drag & Drop Excel File Here</h5>
                    <p class="text-muted">or click to browse</p>
                    <button class="btn btn-success mt-3">
                        <i class="bi bi-folder2-open me-2"></i>Choose File
                    </button>
                </div>

                <div id="resultsPreview" class="mt-4">
                    <!-- Preview will be shown here -->
                </div>

                <div class="mt-4">
                    <a href="admin/generate_templates.php?type=results" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-download me-2"></i>Download Template
                    </a>
                </div>
            </div>
        </div>

        <!-- Manage Students Section -->
        <div id="manageStudents" class="page-section">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-person-lines-fill me-2"></i>All Students</h4>
                    <div>
                        <input type="text" id="studentSearch" class="form-control d-inline-block w-auto me-2" placeholder="Search students...">
                        <button class="btn btn-primary" onclick="showAddStudentModal()">
                            <i class="bi bi-plus-circle me-2"></i>Add Student
                        </button>
                    </div>
                </div>

                <div class="data-table">
                    <table class="table table-hover mb-0" id="studentTable">
                        <thead>
                            <tr>
                                <th>Index No</th>
                                <th>Name</th>
                                <th>Roll</th>
                                <th>Department</th>
                                <th>Batch</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded dynamically via JavaScript -->
                            <!-- <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr> -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="studentCount">Loading...</div>
                    <nav id="studentPagination">
                        <!-- Pagination will be added dynamically -->
                    </nav>
                </div>
            </div>
        </div>

        <!-- Manage Notices Section -->
        <div id="manageNotices" class="page-section">
            <div class="content-card">
                <h4><i class="bi bi-megaphone-fill me-2"></i>Create New Notice</h4>
                <form class="form-modern" id="noticeForm">
                    <div class="mb-3">
                        <label class="form-label">Notice Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Enter notice title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notice Body (Bengali Supported)</label>
                        <textarea name="content" class="form-control" rows="6" placeholder="পরীক্ষার সময়সূচী সম্পর্কিত বিজ্ঞপ্তি..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Publish Date</label>
                            <input type="date" name="publish_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Publish Notice
                    </button>
                    <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="bi bi-x-circle me-2"></i>Clear Form
                    </button>
                </form>
            </div>

            <div class="content-card">
                <h4><i class="bi bi-list-ul me-2"></i>All Notices</h4>
                <div class="data-table">
                    <table class="table table-hover mb-0" id="noticesTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded dynamically -->
                            <tr>
                                <td colspan="4" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div id="settings" class="page-section">
            <div class="content-card">
                <h4><i class="bi bi-sliders me-2"></i>Grade Scale Configuration</h4>
                <p class="text-muted mb-4">Configure the percentage thresholds for each grade</p>

                <form class="form-modern" id="gradeScaleForm">
                    <div class="row g-3">
                        <?php foreach ($grade_scale as $grade): ?>
                            <?php if ($grade['grade'] !== 'F'): ?>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo htmlspecialchars($grade['grade']); ?> Grade (Minimum %)</label>
                                <input type="number"
                                       name="grade_<?php echo htmlspecialchars($grade['grade']); ?>"
                                       class="form-control"
                                       value="<?php echo $grade['min_percentage']; ?>"
                                       min="0"
                                       max="100"
                                       step="0.01">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg mt-4">
                        <i class="bi bi-check-circle me-2"></i>Save Settings
                    </button>
                </form>
            </div>

            <div class="content-card">
                <h4><i class="bi bi-shield-lock me-2"></i>Account Settings</h4>
                <form class="form-modern" id="accountSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-key me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Batches & Departments Section -->
        <div id="batches" class="page-section">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="content-card">
                        <h4><i class="bi bi-calendar3 me-2"></i>Manage Batches</h4>
                        <form class="form-modern mb-4" id="addBatchForm">
                            <div class="input-group">
                                <input type="text" name="batch_name" class="form-control" placeholder="Enter batch name (e.g., Batch 2026)" required>
                                <input type="number" name="batch_year" class="form-control" placeholder="Year" min="2020" max="2099" required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-circle me-2"></i>Add Batch
                                </button>
                            </div>
                        </form>

                        <div class="list-group" id="batchesList">
                            <?php foreach ($batches as $batch): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calendar-check me-2 text-primary"></i><?php echo htmlspecialchars($batch['name']); ?></span>
                                <div>
                                    <button class="action-btn btn-edit" onclick="editBatch(<?php echo $batch['id']; ?>, '<?php echo htmlspecialchars($batch['name']); ?>', <?php echo $batch['year']; ?>)"><i class="bi bi-pencil"></i></button>
                                    <button class="action-btn btn-delete" onclick="deleteBatch(<?php echo $batch['id']; ?>)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="content-card">
                        <h4><i class="bi bi-building me-2"></i>Manage Departments</h4>
                        <form class="form-modern mb-4" id="addDepartmentForm">
                            <div class="input-group">
                                <input type="text" name="dept_name" class="form-control" placeholder="Department name" required>
                                <input type="text" name="dept_code" class="form-control" placeholder="Code" required>
                                <button class="btn btn-success" type="submit">
                                    <i class="bi bi-plus-circle me-2"></i>Add Department
                                </button>
                            </div>
                        </form>

                        <div class="list-group" id="departmentsList">
                            <?php foreach ($departments as $dept): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-laptop me-2 text-success"></i><?php echo htmlspecialchars($dept['name']); ?> (<?php echo htmlspecialchars($dept['code']); ?>)</span>
                                <div>
                                    <button class="action-btn btn-edit" onclick="editDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name']); ?>', '<?php echo htmlspecialchars($dept['code']); ?>')"><i class="bi bi-pencil"></i></button>
                                    <button class="action-btn btn-delete" onclick="deleteDepartment(<?php echo $dept['id']; ?>)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="page-section">
            <div class="content-card">
                <h4><i class="bi bi-file-earmark-text me-2"></i>Generate Reports</h4>
                <p class="text-muted mb-4">Export student and result data in various formats</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h5><i class="bi bi-people me-2 text-primary"></i>Student Reports</h5>
                                <p class="text-muted">Export complete student data</p>
                                <select class="form-select mb-3" id="studentReportDept">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['code']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary w-100" onclick="exportStudents()">
                                    <i class="bi bi-download me-2"></i>Export to Excel
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h5><i class="bi bi-bar-chart me-2 text-success"></i>Result Reports</h5>
                                <p class="text-muted">Export result data and statistics</p>
                                <select class="form-select mb-3" id="resultReportBatch">
                                    <option value="">All Batches</option>
                                    <?php foreach ($batches as $batch): ?>
                                    <option value="<?php echo $batch['id']; ?>"><?php echo htmlspecialchars($batch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-success w-100" onclick="exportResults()">
                                    <i class="bi bi-download me-2"></i>Export to PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Student Modal -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewStudentDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editStudentForm">
                    <div class="modal-body">
                        <input type="hidden" id="editStudentId" name="student_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editStudentName" name="student_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Index No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editStudentIndex" name="index_no" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Roll No</label>
                                <input type="text" class="form-control" id="editStudentRoll" name="roll_no">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Board Roll</label>
                                <input type="text" class="form-control" id="editStudentBoardRoll" name="board_roll">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Batch <span class="text-danger">*</span></label>
                                <select class="form-select" id="editStudentBatch" name="batch_id" required>
                                    <?php foreach ($batches as $batch): ?>
                                        <option value="<?php echo $batch['id']; ?>"><?php echo htmlspecialchars($batch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="editStudentDepartment" name="department_id" required>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editStudentSemester" name="semester" min="1" max="12" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addStudentForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="student_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Index No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="index_no" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Roll No</label>
                                <input type="text" class="form-control" name="roll_no">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Board Roll</label>
                                <input type="text" class="form-control" name="board_roll">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Batch <span class="text-danger">*</span></label>
                                <select class="form-select" name="batch_id" required>
                                    <option value="">Select Batch</option>
                                    <?php foreach ($batches as $batch): ?>
                                        <option value="<?php echo $batch['id']; ?>"><?php echo htmlspecialchars($batch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="semester" min="1" max="12" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Notice Modal -->
    <div class="modal fade" id="editNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-megaphone me-2"></i>Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editNoticeForm">
                    <div class="modal-body">
                        <input type="hidden" id="editNoticeId" name="notice_id">
                        <div class="mb-3">
                            <label class="form-label">Notice Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editNoticeTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notice Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="editNoticeContent" name="content" rows="6" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Publish Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="editNoticeDate" name="publish_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="editNoticeStatus" name="status" required>
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
    <script>
        // Load dashboard stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            // Load students if on that section
            if (document.getElementById('manageStudents').classList.contains('active')) {
                loadStudentList();
            }
        });

        function showSection(sectionId, event) {
            // Hide all sections
            document.querySelectorAll('.page-section').forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');

            // Update active menu item
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.classList.remove('active');
            });
            if (event && event.target) {
                event.target.closest('a').classList.add('active');
            }

            // Update page title
            const titles = {
                'dashboard': 'Dashboard',
                'importStudents': 'Import Students',
                'importResults': 'Import Results',
                'manageStudents': 'Manage Students',
                'manageNotices': 'Manage Notices',
                'settings': 'Settings',
                'batches': 'Batches & Departments',
                'reports': 'Reports'
            };
            document.getElementById('pageTitle').textContent = titles[sectionId] || 'Dashboard';

            // Close sidebar on mobile after selecting a menu item
            closeSidebarOnMobile();

            // Load data based on section
            if (sectionId === 'manageStudents') {
                loadStudentList();
            } else if (sectionId === 'manageNotices') {
                loadNotices();
            }
        }
    </script>
</body>
</html>
