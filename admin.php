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
    'students_with_results' => 0,
    'total_departments' => 0,
    'active_notices' => 0,
    'recent_results' => [],
    'grade_distribution' => [],
    'average_percentage' => 0,
    'pass_rate' => 0
];

try {
    // Get total students
    $result = $conn->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $result->fetch_assoc()['count'];

    // Get students with results
    $result = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM results");
    $stats['students_with_results'] = $result->fetch_assoc()['count'];

    // Get total departments
    $result = $conn->query("SELECT COUNT(*) as count FROM departments");
    $stats['total_departments'] = $result->fetch_assoc()['count'];

    // Get active notices
    $result = $conn->query("SELECT COUNT(*) as count FROM notices WHERE status = 'published'");
    $stats['active_notices'] = $result->fetch_assoc()['count'];

    // Get recent results (last 5 students with results)
    $result = $conn->query("
        SELECT
            s.student_name,
            s.index_no,
            d.code as department_code,
            s.semester,
            SUM(r.marks_obtained) as total_marks,
            SUM(r.total_marks) as total_possible,
            ROUND((SUM(r.marks_obtained) / SUM(r.total_marks)) * 100, 2) as percentage,
            MAX(r.created_at) as result_date
        FROM results r
        INNER JOIN students s ON r.student_id = s.id
        LEFT JOIN departments d ON s.department_id = d.id
        GROUP BY r.student_id, s.semester
        ORDER BY MAX(r.created_at) DESC
        LIMIT 5
    ");
    while ($row = $result->fetch_assoc()) {
        $stats['recent_results'][] = $row;
    }

    // Get grade distribution and statistics
    $result = $conn->query("
        SELECT
            SUM(r.marks_obtained) as total_marks,
            SUM(r.total_marks) as total_possible,
            s.id as student_id,
            s.semester
        FROM results r
        INNER JOIN students s ON r.student_id = s.id
        GROUP BY r.student_id, s.semester
    ");

    $grade_dist = ['A+' => 0, 'A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0, 'B-' => 0, 'C+' => 0, 'C' => 0, 'C-' => 0, 'D' => 0, 'F' => 0];
    $total_students_graded = 0;
    $total_percentage = 0;
    $passing_count = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['total_possible'] > 0) {
            $percentage = ($row['total_marks'] / $row['total_possible']) * 100;
            $total_percentage += $percentage;
            $total_students_graded++;

            // Determine grade
            if ($percentage >= 80) $grade = 'A+';
            elseif ($percentage >= 75) $grade = 'A';
            elseif ($percentage >= 70) $grade = 'A-';
            elseif ($percentage >= 65) $grade = 'B+';
            elseif ($percentage >= 60) $grade = 'B';
            elseif ($percentage >= 55) $grade = 'B-';
            elseif ($percentage >= 50) $grade = 'C+';
            elseif ($percentage >= 45) $grade = 'C';
            elseif ($percentage >= 40) $grade = 'C-';
            elseif ($percentage >= 33) $grade = 'D';
            else $grade = 'F';

            $grade_dist[$grade]++;
            if ($grade !== 'F') $passing_count++;
        }
    }

    $stats['grade_distribution'] = $grade_dist;
    $stats['average_percentage'] = $total_students_graded > 0 ? round($total_percentage / $total_students_graded, 2) : 0;
    $stats['pass_rate'] = $total_students_graded > 0 ? round(($passing_count / $total_students_graded) * 100, 2) : 0;

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

// Get user info from session
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : (isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'admin@srms.edu');
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin User');
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';
$profile_picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : null;
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
    <!-- Desktop Sidebar Toggle Button -->
    <button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
        <i class="bi bi-chevron-left"></i>
    </button>

    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()" aria-label="Toggle Menu">
        <i class="bi bi-list"></i>
    </button>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="closeMobileSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
                <i class="bi bi-mortarboard-fill"></i>
                <h4>SRMS</h4>
        </div>

        <div style="padding: 0 15px 30px;">
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary w-100" style="border-radius: 50px; padding: 12px; font-weight: 600;" onclick="showAddTeacherModal()">
                <i class="bi bi-person-plus-fill me-2"></i><span>Create New Teacher</span>
            </button>
            <?php endif; ?>
        </div>

       <ul class="nav-menu">
    <li>
        <a href="javascript:void(0)" class="active" onclick="showSection('dashboard', event)">
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <?php if (isAdmin()): ?>
    <li>
        <a href="javascript:void(0)" onclick="showSection('importStudents', event)">
            <i class="bi bi-people-fill"></i>
            <span>Import Students</span>
        </a>
    </li>
    <?php endif; ?>
    <li>
        <a href="javascript:void(0)" onclick="showSection('importResults', event)">
            <i class="bi bi-file-earmark-bar-graph-fill"></i>
            <span><?php echo isAdmin() ? 'Import Results' : 'Add Results'; ?></span>
        </a>
    </li>
    <?php if (!isAdmin()): ?>
    <li>
        <a href="javascript:void(0)" onclick="showSection('manageResults', event)">
            <i class="bi bi-clipboard-data-fill"></i>
            <span>Manage Results</span>
        </a>
    </li>
    <?php endif; ?>
    <li>
        <a href="javascript:void(0)" onclick="showSection('manageStudents', event)">
            <i class="bi bi-person-lines-fill"></i>
            <span><?php echo isAdmin() ? 'Manage Students' : 'View Students'; ?></span>
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
    <?php if (isAdmin()): ?>
    <li>
        <a href="javascript:void(0)" onclick="showSection('manageTeachers', event)">
            <i class="bi bi-person-badge-fill"></i>
            <span>Manage Teachers</span>
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
    <?php endif; ?>
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
                <small class="text-muted">Welcome back, <?php echo ucfirst($user_type); ?>!</small>
            </div>
            <div class="user-info">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user_name); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars($user_email); ?></small>
                </div>
                <div class="user-avatar">
                    <?php if ($profile_picture && file_exists("uploads/teacher_profiles/" . $profile_picture)): ?>
                        <img src="uploads/teacher_profiles/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="page-section active">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 style="font-weight: 700; color: #0A0A0A; margin: 0;">Academic Overview</h3>
                <small class="text-muted">Real-time statistics from your database</small>
            </div>
            <div class="row g-3 mb-5">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #E8D5F2; color: #8B5CF6;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <p>Total Students</p>
                        <h3 id="totalStudents"><?php echo $stats['total_students']; ?></h3>
                        <small class="text-muted">Enrolled in all departments</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #FFE0D5; color: #FF6B35;">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                        <p>Students with Results</p>
                        <h3><?php echo $stats['students_with_results']; ?> <small>/ <?php echo $stats['total_students']; ?></small></h3>
                        <small class="text-muted">
                            <?php
                            $percentage = $stats['total_students'] > 0
                                ? round(($stats['students_with_results'] / $stats['total_students']) * 100, 1)
                                : 0;
                            echo $percentage . '% have published results';
                            ?>
                        </small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #D5E8FF; color: #3B82F6;">
                            <i class="bi bi-building"></i>
                        </div>
                        <p>Departments</p>
                        <h3 id="totalDepartments"><?php echo $stats['total_departments']; ?></h3>
                        <small class="text-muted">Active academic departments</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #FFF4D5; color: #F59E0B;">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                        <p>Active Notices</p>
                        <h3 id="activeNotices"><?php echo $stats['active_notices']; ?></h3>
                        <small class="text-muted">Published announcements</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 style="margin: 0;"><i class="bi bi-graph-up me-2"></i>Recent Results Summary</h4>
                            <small class="text-muted">Last 5 students with results published</small>
                        </div>
                        <div class="data-table">
                            <table class="table mb-0" style="--bs-table-bg: #f2eae5;">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Index No</th>
                                        <th>Department</th>
                                        <th>Semester</th>
                                        <th>Total Marks</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($stats['recent_results'])) {
                                        foreach ($stats['recent_results'] as $result) {
                                            $percentage = $result['percentage'];

                                            // Determine grade
                                            if ($percentage >= 80) { $grade = 'A+'; $grade_class = 'success'; }
                                            elseif ($percentage >= 75) { $grade = 'A'; $grade_class = 'success'; }
                                            elseif ($percentage >= 70) { $grade = 'A-'; $grade_class = 'success'; }
                                            elseif ($percentage >= 65) { $grade = 'B+'; $grade_class = 'info'; }
                                            elseif ($percentage >= 60) { $grade = 'B'; $grade_class = 'info'; }
                                            elseif ($percentage >= 55) { $grade = 'B-'; $grade_class = 'info'; }
                                            elseif ($percentage >= 50) { $grade = 'C+'; $grade_class = 'warning'; }
                                            elseif ($percentage >= 45) { $grade = 'C'; $grade_class = 'warning'; }
                                            elseif ($percentage >= 40) { $grade = 'C-'; $grade_class = 'warning'; }
                                            elseif ($percentage >= 33) { $grade = 'D'; $grade_class = 'secondary'; }
                                            else { $grade = 'F'; $grade_class = 'danger'; }

                                            echo "<tr>";
                                            echo "<td><strong>" . htmlspecialchars($result['student_name']) . "</strong></td>";
                                            echo "<td>" . htmlspecialchars($result['index_no']) . "</td>";
                                            echo "<td>" . htmlspecialchars($result['department_code']) . "</td>";
                                            echo "<td class='text-center'>" . $result['semester'] . "</td>";
                                            echo "<td>" . $result['total_marks'] . " / " . $result['total_possible'] . "</td>";
                                            echo "<td><strong>" . $percentage . "%</strong></td>";
                                            echo "<td><span class='badge bg-$grade_class'>$grade</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center text-muted py-4'>No results published yet. Import student results to see them here.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 style="margin: 0;"><i class="bi bi-bar-chart-fill me-2"></i>Result Analytics</h4>
                        </div>

                        <!-- Pass Rate Circular Progress -->
                        <div style="text-align: center; margin: 30px 0;">
                            <div style="position: relative; width: 200px; height: 200px; margin: 0 auto;">
                                <?php
                                $passRate = $stats['pass_rate'];
                                $circumference = 534; // 2 * π * 85
                                $offset = $circumference - ($passRate / 100) * $circumference;
                                $color = $passRate >= 80 ? '#10b981' : ($passRate >= 60 ? '#f59e0b' : '#ef4444');
                                ?>
                                <svg width="200" height="200" style="transform: rotate(-90deg);">
                                    <!-- Background circle -->
                                    <circle cx="100" cy="100" r="85" fill="none" stroke="#f0f0f0" stroke-width="12"/>
                                    <!-- Progress arc -->
                                    <circle cx="100" cy="100" r="85" fill="none" stroke-width="12"
                                        stroke="<?php echo $color; ?>"
                                        stroke-dasharray="<?php echo $circumference; ?>"
                                        stroke-dashoffset="<?php echo $offset; ?>"
                                        style="stroke-linecap: round; transition: stroke-dashoffset 0.5s;"/>
                                </svg>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <div style="font-size: 2.5rem; font-weight: 700; color: #0A0A0A;"><?php echo $passRate; ?>%</div>
                                    <div style="color: #94a3b8; font-size: 0.9rem;">Pass Rate</div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Performance -->
                        <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 12px; margin-bottom: 20px;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;"><?php echo $stats['average_percentage']; ?>%</div>
                            <div style="color: #64748b; font-size: 0.9rem; margin-top: 4px;">Average Percentage</div>
                        </div>

                        <!-- Grade Distribution -->
                        <h5 style="font-size: 0.9rem; color: #64748b; margin-bottom: 15px;">Grade Distribution</h5>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                            <?php
                            $grade_colors = [
                                'A+' => '#10b981', 'A' => '#059669', 'A-' => '#34d399',
                                'B+' => '#3b82f6', 'B' => '#2563eb', 'B-' => '#60a5fa',
                                'C+' => '#f59e0b', 'C' => '#d97706', 'C-' => '#fbbf24',
                                'D' => '#6b7280', 'F' => '#ef4444'
                            ];
                            $total_graded = array_sum($stats['grade_distribution']);
                            foreach ($stats['grade_distribution'] as $grade => $count):
                                if ($count > 0 || in_array($grade, ['A+', 'A', 'B+', 'F'])): // Show key grades even if zero
                            ?>
                            <div style="text-align: center; padding: 10px; background: <?php echo $grade_colors[$grade]; ?>15; border-radius: 8px; border: 1px solid <?php echo $grade_colors[$grade]; ?>30;">
                                <div style="font-size: 1.3rem; font-weight: 700; color: <?php echo $grade_colors[$grade]; ?>;"><?php echo $count; ?></div>
                                <div style="color: #64748b; font-size: 0.75rem; margin-top: 2px;"><?php echo $grade; ?> Grade</div>
                            </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
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
                <p class="text-muted mb-4">Select exam type, then upload Excel file with student marks</p>

                <!-- Exam Type Selection -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Exam Type <span class="text-danger">*</span></label>
                        <select id="examTypeSelect" class="form-select" onchange="handleExamTypeChange()" required>
                            <option value="">Choose Exam Type...</option>
                            <option value="Final">Final Exam (Semester-wide)</option>
                            <option value="Midterm">Midterm Exam (Semester-wide)</option>
                            <option value="ClassTest">Class Test (Subject-specific)</option>
                            <option value="Assignment">Assignment</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="semesterSelectDiv">
                        <label class="form-label fw-semibold">Semester <span class="text-danger">*</span></label>
                        <select id="semesterSelect" class="form-select" required>
                            <option value="">Choose Semester...</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                            <option value="7">Semester 7</option>
                            <option value="8">Semester 8</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="departmentSelectDiv">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select id="departmentSelect" class="form-select" onchange="checkIfCanUpload()" required>
                            <option value="">Choose Department...</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?> (<?= $dept['code'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Test/Assignment Number Field -->
                <div class="row g-3 mb-4" id="classTestFields" style="display: none;">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" id="testNumberLabel">Test/Assignment Number</label>
                        <input type="number" id="testNumberInput" class="form-control" placeholder="e.g., 1, 2, 3..." min="1" value="1">
                        <small class="text-muted">Specify which number (1st, 2nd, 3rd, etc.)</small>
                    </div>
                </div>

                <div class="alert alert-info" id="uploadInstruction" style="display: none;">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="uploadInstructionText"></span>
                </div>

                <div id="resultsUploadZone" class="upload-zone" style="opacity: 0.5; pointer-events: none;">
                    <i class="bi bi-cloud-upload"></i>
                    <h5>Drag & Drop Excel File Here</h5>
                    <p class="text-muted">First select exam type above</p>
                    <button class="btn btn-success mt-3">
                        <i class="bi bi-folder2-open me-2"></i>Choose File
                    </button>
                </div>

                <div id="resultsPreview" class="mt-4">
                    <!-- Preview will be shown here -->
                </div>

                <div class="mt-4">
                    <button class="btn btn-outline-secondary btn-lg" onclick="downloadExamTemplate()" disabled id="downloadTemplateBtn">
                        <i class="bi bi-download me-2"></i>Download Template
                    </button>
                    <small class="text-muted ms-2">Select exam details to enable template download</small>
                </div>
            </div>
        </div>

        <!-- Manage Results Section (Teachers Only) -->
        <?php if (!isAdmin()): ?>
        <div id="manageResults" class="page-section">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-clipboard-data-fill me-2"></i>Manage Results</h4>
                    <button id="undoUploadBtn" class="btn btn-warning" style="display: none;">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Undo Last Upload
                    </button>
                </div>

                <!-- Filter and Search Section -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Search Student</label>
                        <input type="text" id="resultsSearch" class="form-control" placeholder="Student name or index no...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Exam Type</label>
                        <select id="resultsExamTypeFilter" class="form-select">
                            <option value="">All Types</option>
                            <option value="Final">Final</option>
                            <option value="Midterm">Midterm</option>
                            <option value="ClassTest">Class Test</option>
                            <option value="Assignment">Assignment</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Semester</label>
                        <select id="resultsSemesterFilter" class="form-select">
                            <option value="">All Semesters</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>">Semester <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Department</label>
                        <select id="resultsDepartmentFilter" class="form-select">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Subject</label>
                        <select id="resultsSubjectFilter" class="form-select">
                            <option value="">All Subjects</option>
                        </select>
                    </div>
                </div>

                <div class="data-table">
                    <table class="table table-hover mb-0" id="resultsTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No</th>
                                <th>Index No</th>
                                <th>Student Name</th>
                                <th>Exam</th>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Date</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTableBody" style="--bs-table-bg: #f2eae5;">
                            <tr>
                                <td colspan="10" class="text-center" style="padding: 40px;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="mt-2 text-muted">Loading results...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="resultsCount">Loading...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Manage Students Section -->
        <div id="manageStudents" class="page-section">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-person-lines-fill me-2"></i>All Students</h4>
                    <?php if (isAdmin()): ?>
                    <button class="btn btn-primary" onclick="showAddStudentModal()">
                        <i class="bi bi-plus-circle me-2"></i>Add Student
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Filter and Search Section -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Search by Name or Board Roll</label>
                        <input type="text" id="studentSearch" class="form-control" placeholder="Type student name or board roll...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Filter by Department</label>
                        <select id="departmentFilter" class="form-select">
                            <option value="">All Departments</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem; color: #64748b;">Filter by Batch</label>
                        <select id="batchFilter" class="form-select">
                            <option value="">All Batches</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                </div>

                <div class="data-table">
                    <table class="table table-hover mb-0" id="studentTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No</th>
                                <th>Index No</th>
                                <th>Name</th>
                                <th>Board Roll</th>
                                <th>Department</th>
                                <th>Batch</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody" style="--bs-table-bg: #f2eae5;">
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 40px;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="mt-2 text-muted">Loading students...</div>
                                </td>
                            </tr>
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
                        <tbody style="--bs-table-bg: #f2eae5;">
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
                <form class="form-modern" id="accountSettingsForm" method="POST" onsubmit="return false;">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('currentPassword', 'currentPasswordIcon')">
                                <i class="bi bi-eye-fill" id="currentPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPassword', 'newPasswordIcon')">
                                <i class="bi bi-eye-fill" id="newPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirmPassword', 'confirmPasswordIcon')">
                                <i class="bi bi-eye-fill" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-key me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Manage Teachers Section -->
        <?php if (isAdmin()): ?>
        <div id="manageTeachers" class="page-section">
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-person-badge-fill me-2"></i>All Teachers</h4>
                    <button class="btn btn-primary" onclick="showAddTeacherModal()">
                        <i class="bi bi-person-plus-fill me-2"></i>Add Teacher
                    </button>
                </div>

                <div class="data-table">
                    <table class="table table-hover mb-0" id="teachersTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No</th>
                                <th style="width: 80px;">Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th style="width: 220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teachersTableBody" style="--bs-table-bg: #f2eae5;">
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 40px;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="mt-2 text-muted">Loading teachers...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="teacherCount">Loading...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

    <!-- Edit Result Modal -->
    <div class="modal fade" id="editResultModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editResultForm">
                    <div class="modal-body">
                        <input type="hidden" id="editResultId" name="result_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student</label>
                                <input type="text" class="form-control" id="editResultStudent" readonly style="background: #f8f9fa;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Exam</label>
                                <input type="text" class="form-control" id="editResultExam" readonly style="background: #f8f9fa;">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" id="editResultSubject" readonly style="background: #f8f9fa;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Marks Obtained <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editResultMarks" name="marks_obtained" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Marks <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editResultTotalMarks" name="total_marks" min="1" step="0.01" required readonly style="background: #f8f9fa;">
                                <small class="text-muted">Total marks cannot be changed</small>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> Grade will be automatically recalculated based on the new marks.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Create New Teacher Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addTeacherForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email (Gmail) <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" minlength="6" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Profile Picture (Optional)</label>
                                <input type="file" class="form-control" name="profile_picture" accept="image/*">
                                <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Create Teacher Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
    <?php if (!isAdmin()): ?>
    <script src="js/manage_results.js"></script>
    <?php endif; ?>
    <script>
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const toggleIcon = toggleBtn.querySelector('i');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
            toggleBtn.classList.toggle('collapsed');

            // Change icon direction
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('bi-chevron-left');
                toggleIcon.classList.add('bi-chevron-right');
            } else {
                toggleIcon.classList.remove('bi-chevron-right');
                toggleIcon.classList.add('bi-chevron-left');
            }

            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        // Load dashboard stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Restore sidebar state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                document.querySelector('.sidebar').classList.add('collapsed');
                document.querySelector('.main-content').classList.add('sidebar-collapsed');
                document.getElementById('sidebarToggleBtn').classList.add('collapsed');
                const toggleIcon = document.getElementById('sidebarToggleBtn').querySelector('i');
                toggleIcon.classList.remove('bi-chevron-left');
                toggleIcon.classList.add('bi-chevron-right');
            }

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
                'manageResults': 'Manage Results',
                'manageStudents': 'Manage Students',
                'manageNotices': 'Manage Notices',
                'manageTeachers': 'Manage Teachers',
                'settings': 'Settings',
                'batches': 'Batches & Departments',
                'reports': 'Reports'
            };
            document.getElementById('pageTitle').textContent = titles[sectionId] || 'Dashboard';

            // Close sidebar on mobile after selecting a menu item
            closeSidebarOnMobile();

            // Load data based on section
            if (sectionId === 'manageStudents') {
                initializeStudentFilters();
            } else if (sectionId === 'manageResults') {
                if (typeof initializeResultFilters === 'function') {
                    initializeResultFilters();
                }
            } else if (sectionId === 'manageNotices') {
                loadNotices();
            } else if (sectionId === 'manageTeachers') {
                loadTeachers();
            }
        }

        // Password visibility toggle function
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        }

        // ================================================
        // EXAM MANAGEMENT FUNCTIONS
        // ================================================

        function handleExamTypeChange() {
            const examType = document.getElementById('examTypeSelect').value;
            const classTestFields = document.getElementById('classTestFields');
            const uploadZone = document.getElementById('resultsUploadZone');
            const uploadInstruction = document.getElementById('uploadInstruction');
            const uploadInstructionText = document.getElementById('uploadInstructionText');
            const downloadBtn = document.getElementById('downloadTemplateBtn');

            if (!examType) {
                classTestFields.style.display = 'none';
                uploadZone.style.opacity = '0.5';
                uploadZone.style.pointerEvents = 'none';
                uploadInstruction.style.display = 'none';
                downloadBtn.disabled = true;
                return;
            }

            // Show/hide test number field for ClassTest and Assignment
            if (examType === 'ClassTest' || examType === 'Assignment') {
                classTestFields.style.display = 'flex';
                const label = examType === 'ClassTest' ? 'Class Test Number' : 'Assignment Number';
                document.getElementById('testNumberLabel').textContent = label;
            } else {
                classTestFields.style.display = 'none';
            }

            // All exam types use the same Excel format
            uploadInstructionText.textContent = 'Upload Excel with columns: Index No | Board Roll | Subject Code | Subject Name | Marks Obtained | Total Marks';

            uploadInstruction.style.display = 'block';

            // Check if all required fields are filled
            checkIfCanUpload();
        }

        function checkIfCanUpload() {
            const examType = document.getElementById('examTypeSelect').value;
            const semester = document.getElementById('semesterSelect').value;
            const department = document.getElementById('departmentSelect').value;
            const uploadZone = document.getElementById('resultsUploadZone');
            const downloadBtn = document.getElementById('downloadTemplateBtn');

            let canUpload = examType && semester && department;

            // Subject is no longer required - all info comes from Excel file

            if (canUpload) {
                uploadZone.style.opacity = '1';
                uploadZone.style.pointerEvents = 'auto';
                uploadZone.querySelector('p').textContent = 'or click to browse';
                downloadBtn.disabled = false;
            } else {
                uploadZone.style.opacity = '0.5';
                uploadZone.style.pointerEvents = 'none';
                uploadZone.querySelector('p').textContent = 'Complete exam details first';
                downloadBtn.disabled = true;
            }
        }

        // Subject loading removed - subjects now come from Excel file
        document.addEventListener('DOMContentLoaded', function() {
            const semesterSelect = document.getElementById('semesterSelect');

            if (semesterSelect) {
                semesterSelect.addEventListener('change', function() {
                    checkIfCanUpload();
                });
            }
        });

        function downloadExamTemplate() {
            const examType = document.getElementById('examTypeSelect').value;
            const semester = document.getElementById('semesterSelect').value;
            const department = document.getElementById('departmentSelect').value;
            const testNumber = document.getElementById('testNumberInput').value;

            if (!examType || !semester || !department) {
                alert('Please select exam type, semester, and department');
                return;
            }

            // Build URL with parameters
            let url = `admin/generate_exam_template.php?exam_type=${examType}&semester=${semester}&department_id=${department}&test_number=${testNumber}`;

            // Open in new tab to download
            window.open(url, '_blank');
        }

    </script>
</body>
</html>
