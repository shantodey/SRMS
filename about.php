<?php
session_start();

// Team members data structure (ready for you to edit)
$teamMembers = [
    [
        'name' => 'Shanto Chandra Dey',
        'role' => 'Full Stack Developer',
        'contribution' => 'Developed the core authentication system, database architecture, and admin dashboard interface.',
        'image' => 'assets/Shanto.jpg', // Add image path later (4:3 ratio)
        'initials' => 'TM1'
    ],
    [
        'name' => 'Pattia Dio',
        'role' => 'Database Administrator',
        'contribution' => 'Designed the database schema, optimized queries, and implemented data security measures.',
        'image' => 'assets/pattia.jpg', 
        'initials' => 'TM2'
    ],
    [
        'name' => 'Jorge Martin D Silva',
        'role' => 'Backend Developer',
        'contribution' => 'Built the result management system, student data processing, and Excel import functionality.',
        'image' => 'assets/jorge.jpg', // Add image path later
        'initials' => 'TM3'
    ],
    [
        'name' => 'Sonjit Chiran',
        'role' => 'Frontend Developer',
        'contribution' => 'Designed and implemented the responsive UI/UX, created the BBK-style theme and animations.',
        'image' => 'assets/sonjit.jpg', // Add image path later
        'initials' => 'TM4'
    ],
    [
        'name' => 'Saima Sattar',
        'role' => 'Quality Assurance',
        'contribution' => 'Conducted comprehensive testing, bug fixes, and ensured system reliability and performance.',
        'image' => '', // Add image path later
        'initials' => 'TM5'
    ],
    [
        'name' => 'Benedicta Kha Kha',
        'role' => 'Project Manager',
        'contribution' => 'Coordinated team efforts, managed timelines, and ensured successful project delivery.',
        'image' => 'assets/suchon.jpeg', // Add image path later
        'initials' => 'TM6'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/fabicon.png">
    <title>About Us - SRMS Team</title>

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
                        <a class="nav-link" href="about.php" style="background: rgba(94, 179, 246, 0.1);">About Us</a>
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

    <!-- Page Header -->
    <section class="bbk-hero" style="padding: 3rem 0 2rem;">
        <div class="container">
            <h1 style="font-size: 2.5rem;">Meet Our Team</h1>
            <p style="font-size: 1rem;">The talented developers behind SRMS</p>
        </div>
    </section>

    <!-- Team Section -->
    <section class="bbk-team-section" style="background: transparent; padding: 2rem 0 4rem;">
        <div class="container">
            <div class="bbk-team-title" style="color: white; margin-bottom: 0.5rem;">Our Development Team</div>
            <div class="bbk-team-subtitle" style="color: rgba(255,255,255,0.9);">
                Meet the passionate developers who built this system
            </div>

            <div class="bbk-team-grid" style="padding: 0 1rem;">
                <?php foreach ($teamMembers as $member): ?>
                <div class="bbk-team-card">
                    <div class="bbk-team-photo">
                        <?php if (!empty($member['image'])): ?>
                            <img src="<?php echo htmlspecialchars($member['image']); ?>"
                                 alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <?php else: ?>
                            
                            <div class="bbk-team-photo-placeholder">
                                <?php echo htmlspecialchars($member['initials']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="bbk-team-info">
                        <div class="bbk-team-name"><?php echo htmlspecialchars($member['name']); ?></div>
                        <div class="bbk-team-role"><?php echo htmlspecialchars($member['role']); ?></div>
                        <div class="bbk-team-contribution">
                            <?php echo htmlspecialchars($member['contribution']); ?>
                        </div>
                        <div class="bbk-team-social">
                            <a href="#" title="GitHub" onclick="return false;">
                                <i class="bi bi-github"></i>
                            </a>
                            <a href="#" title="LinkedIn" onclick="return false;">
                                <i class="bi bi-linkedin"></i>
                            </a>
                            <a href="#" title="Email" onclick="return false;">
                                <i class="bi bi-envelope-fill"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Project Info Section -->
            <div class="row mt-5 g-4" style="padding: 0 1rem;">
                <div class="col-md-4">
                    <div class="bbk-feature-card" style="background: white; border-radius: 20px; padding: 2rem;">
                        <div class="bbk-feature-icon" style="margin: 0 auto 1rem;">
                            <i class="bi bi-code-slash"></i>
                        </div>
                        <h4>Modern Technology</h4>
                        <p>Built with PHP, MySQL, Bootstrap 5, and modern JavaScript for optimal performance.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bbk-feature-card" style="background: white; border-radius: 20px; padding: 2rem;">
                        <div class="bbk-feature-icon" style="margin: 0 auto 1rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4>Secure & Reliable</h4>
                        <p>Implementing best security practices to protect student data and ensure system reliability.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bbk-feature-card" style="background: white; border-radius: 20px; padding: 2rem;">
                        <div class="bbk-feature-icon" style="margin: 0 auto 1rem;">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <h4>Built with Passion</h4>
                        <p>Every line of code written with dedication to create the best result management system.</p>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="text-center mt-5">
                <a href="index.php" class="btn btn-login" style="padding: 1rem 3rem; font-size: 1.1rem;">
                    <i class="bi bi-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bbk-footer">
        <div class="container">
            <div class="bbk-footer-content">
                <div class="bbk-footer-brand">
                    <i class="bi bi-mortarboard-fill me-2"></i>SRMS.com
                </div>
                <p class="bbk-footer-text">
                    Student Result Management System - Making education management easier
                </p>
                <div class="bbk-footer-links">
                    <a href="index.php">Home</a>
                    <a href="about.php">About Us</a>
                    <a href="features.php">Features</a>
                    <a href="login.php">Login</a>
                </div>
                <div class="bbk-footer-bottom">
                    &copy; <?php echo date('Y'); ?> SRMS - Student Result Management System. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Smooth Scroll -->
    <script>
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>
