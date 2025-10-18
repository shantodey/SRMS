<?php
session_start();
require_once 'config/database.php';

// Fetch recent published notices for homepage (limit to 3 for BBK design)
$notices = [];
try {
    $sql = "SELECT * FROM notices WHERE status = 'published' ORDER BY publish_date DESC, created_at DESC LIMIT 3";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row;
        }
    }
} catch (Exception $e) {
    // Handle error silently
}

// Notice icons for each card (cycling through different icons)
$noticeIcons = ['bi-megaphone-fill', 'bi-bell-fill', 'bi-info-circle-fill', 'bi-star-fill', 'bi-trophy-fill'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/fabicon.png">
    <title>SRMS - Student Result Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- BBK Style CSS -->
    <link rel="stylesheet" href="css/bbk-style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bbk-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-mortarboard-fill me-2"></i>SRMS.com
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a href="login.php" class="btn btn-login">
                            <i class="bi bi-person-circle me-2"></i>Login as Teacher
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Search -->
    <section class="bbk-hero" id="searchSection">
        <div class="container">
            <h1>Welcome to SRMS</h1>
            <p>Check student results instantly.<br>Search by Name or Board Roll Number to view marks and grades.</p>

            <!-- Search Bar -->
            <div class="bbk-search-container">
                <div class="bbk-search-box">
                    <input type="text"
                           id="searchInput"
                           placeholder="Search Here"
                           aria-label="Search for student results">
                    <button onclick="searchResult()">
                        Search
                    </button>
                </div>

                <!-- Error/Info Messages -->
                <div id="searchMessage" class="mt-3"></div>
            </div>
        </div>
    </section>

    <!-- Notice Cards Section (3 Cards in BBK Style) -->
    <section class="bbk-cards-section">
        <div class="bbk-cards-container">
            <?php if (count($notices) > 0): ?>
                <?php foreach ($notices as $index => $notice): ?>
                <div class="bbk-card">
                    <div class="bbk-card-icon">
                        <i class="bi <?php echo $noticeIcons[$index % count($noticeIcons)]; ?>"></i>
                    </div>
                    <div class="bbk-card-body">
                        <h3 class="bbk-card-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
                        <p class="bbk-card-text">
                            <?php
                            if (!empty($notice['content'])) {
                                echo htmlspecialchars(substr($notice['content'], 0, 120));
                                if (strlen($notice['content']) > 120) echo '...';
                            } else {
                                echo 'Click to read more details about this notice.';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="bbk-card-footer">
                        <div class="bbk-card-date">
                            <i class="bi bi-calendar-event"></i>
                            <span><?php echo date('M d, Y', strtotime($notice['publish_date'])); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-person"></i> Admin
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default placeholders if no notices -->
                <div class="bbk-card">
                    <div class="bbk-card-icon">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div class="bbk-card-body">
                        <h3 class="bbk-card-title">Welcome Notice</h3>
                        <p class="bbk-card-text">
                            Welcome to SRMS! Check back here for important announcements and updates.
                        </p>
                    </div>
                    <div class="bbk-card-footer">
                        <div class="bbk-card-date">
                            <i class="bi bi-calendar-event"></i>
                            <span><?php echo date('M d, Y'); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-person"></i> Admin
                        </div>
                    </div>
                </div>

                <div class="bbk-card">
                    <div class="bbk-card-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div class="bbk-card-body">
                        <h3 class="bbk-card-title">System Updates</h3>
                        <p class="bbk-card-text">
                            Stay tuned for the latest system updates and new features added to SRMS.
                        </p>
                    </div>
                    <div class="bbk-card-footer">
                        <div class="bbk-card-date">
                            <i class="bi bi-calendar-event"></i>
                            <span><?php echo date('M d, Y'); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-person"></i> Admin
                        </div>
                    </div>
                </div>

                <div class="bbk-card">
                    <div class="bbk-card-icon">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <div class="bbk-card-body">
                        <h3 class="bbk-card-title">Important Information</h3>
                        <p class="bbk-card-text">
                            Find all important information and guidelines for students and teachers here.
                        </p>
                    </div>
                    <div class="bbk-card-footer">
                        <div class="bbk-card-date">
                            <i class="bi bi-calendar-event"></i>
                            <span><?php echo date('M d, Y'); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-person"></i> Admin
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Multiple Results Selection Section (Hidden Initially) -->
    <div id="multipleResultsSection" style="display: none;">
        <div class="bbk-result-container">
            <div class="bbk-result-card">
                <div class="bbk-result-header">
                    <h4 class="mb-0">
                        <i class="bi bi-search me-2"></i>Multiple Students Found
                    </h4>
                    <p class="mb-0 mt-2 opacity-75" id="multipleResultsMessage">Please select the student you want to view</p>
                </div>
                <div class="bbk-result-body">
                    <button class="bbk-back-button" onclick="hideMultipleResults()">
                        <i class="bi bi-arrow-left"></i>Back to Search
                    </button>
                    <div class="table-responsive">
                        <table class="bbk-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Index No</th>
                                    <th>Board Roll</th>
                                    <th>Department</th>
                                    <th>Batch</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="multipleResultsTable">
                                <!-- Will be populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Section (Hidden Initially) -->
    <div id="resultSection" style="display: none;">
        <div class="bbk-result-container">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <button class="bbk-back-button" onclick="hideResult()">
                    <i class="bi bi-arrow-left"></i>Back to Search
                </button>
                <div class="d-flex gap-2">
                    <button class="btn btn-login" onclick="printResult()">
                        <i class="bi bi-printer me-2"></i>Print
                    </button>
                    <button class="btn btn-login" onclick="downloadPDF()">
                        <i class="bi bi-download me-2"></i>Download PDF
                    </button>
                </div>
            </div>

            <!-- Student Info Card -->
            <div class="bbk-result-card">
                <div class="bbk-result-header">
                    <h3><i class="bi bi-person-badge me-2"></i>Student Information</h3>
                    <div class="row g-3" id="studentInfo">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>

                <div class="bbk-result-body">
                    <h5 class="mb-4"><i class="bi bi-table me-2"></i>Subject Results</h5>
                    <div class="table-responsive">
                        <table class="bbk-table">
                            <thead>
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
    </div>

    <!-- Footer -->
    <footer class="bbk-footer">
        <div class="container">
            <div class="bbk-footer-content">
                <div class="bbk-footer-brand">
                    <i class="bi bi-mortarboard-fill me-2"></i>srms.page.gd
                </div>
                <p class="bbk-footer-text">
                    Student Result Management System - Making education management easier
                </p>
                <div class="bbk-footer-bottom">
                    &copy; <?php echo date('Y'); ?> SRMS - Student Result Management System. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="js/bbk-custom.js"></script>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
    <script>
        // Show logout success notification
        document.addEventListener('DOMContentLoaded', function() {
            showToast('success', 'You have been logged out successfully!');

            // Clean URL (remove logout parameter)
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('logout');
                url.searchParams.delete('t');
                window.history.replaceState({}, document.title, url.pathname);
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
