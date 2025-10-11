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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
                </div>
            </a>
            <a href="login.php" class="btn btn-outline-primary">
                <i class="bi bi-shield-lock me-2"></i>Admin Panel
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Hero Search Section -->
        <div id="searchSection" class="card custom-shadow border-0 mb-4">
            <div class="card-body p-5 text-center">
                <h1 class="display-4 fw-bold mb-3">Search Your Results</h1>
                <p class="lead text-muted mb-5">Enter your Name or Board Roll to view results</p>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="input-group input-group-lg mb-3">
                            <input type="text" class="form-control border-start-0" id="searchInput"
                                   placeholder=" You name our Board Roll">
                            <button class="btn btn-gradient px-5" onclick="searchResult()">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </div>

                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <span class="badge bg-light text-dark">Example: 759292</span>
                            <span class="badge bg-light text-dark">Example: Shanto dey</span>
                        </div>
                    </div>
                </div>

                <!-- Error/Info Messages -->
                <div id="searchMessage" class="mt-3"></div>
            </div>
        </div>

        <!-- Multiple Results Selection Section (Hidden Initially) -->
        <div id="multipleResultsSection" style="display: none;">
            <div class="card custom-shadow border-0 mb-4">
                <div class="card-header bg-gradient text-white py-4">
                    <h4 class="mb-0">
                        <i class="bi bi-search me-2"></i>Multiple Students Found
                    </h4>
                    <p class="mb-0 mt-2 opacity-75" id="multipleResultsMessage">Please select the student you want to view</p>
                </div>
                <div class="card-body p-4">
                    <button class="btn btn-light mb-4" onclick="hideMultipleResults()">
                        <i class="bi bi-arrow-left me-2"></i>Back to Search
                    </button>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
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

        <!-- Result Section (Hidden Initially) -->
        <div id="resultSection" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-light" onclick="hideResult()">
                    <i class="bi bi-arrow-left me-2"></i>Back to Search
                </button>
                <div>
                    <button class="btn btn-outline-light" onclick="printResult()">
                        <i class="bi bi-printer me-2"></i>Print Result
                    </button>
                    <button class="btn btn-outline-light" onclick="downloadPDF()">
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
                            <thead >
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
    <script src="js/custom.js"></script>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == '1'): ?>
    <script>
        // Show logout success notification
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.createElement('div');
            toast.className = 'toast-notification toast-success show';
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>You have been logged out successfully!</span>
                </div>
            `;
            document.body.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);

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
