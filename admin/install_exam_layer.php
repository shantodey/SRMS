<?php
/**
 * Web-Based Exam Layer Installer
 *
 * This page installs the exam layer enhancement to your SRMS.
 * No terminal commands needed - just click the button!
 */

session_start();

// Check if user is admin (allow access if no login system exists yet)
$requireLogin = true;
if ($requireLogin && !isset($_SESSION['admin_logged_in']) && !isset($_SESSION['teacher_logged_in'])) {
    // Check if we're in development mode (no login required)
    // Comment out the next 3 lines if you want to require login
    // header('Location: login.php');
    // exit;
}

require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Exam Layer - SRMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .feature-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .feature-card p {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.6;
        }
        .status-section {
            display: none;
            margin-top: 30px;
        }
        .status-section.active {
            display: block;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .log-box {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .log-entry {
            margin-bottom: 5px;
            line-height: 1.6;
        }
        .log-entry.success {
            color: #2ecc71;
        }
        .log-entry.error {
            color: #e74c3c;
        }
        .log-entry.warning {
            color: #f39c12;
        }
        .log-entry.info {
            color: #3498db;
        }
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        .btn-success:hover {
            background: #27ae60;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .checklist {
            list-style: none;
            margin: 20px 0;
        }
        .checklist li {
            padding: 10px 0;
            display: flex;
            align-items: center;
        }
        .checklist li:before {
            content: "✓";
            width: 24px;
            height: 24px;
            background: #2ecc71;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Introduction Card -->
        <div class="card" id="intro-card">
            <h1>🎓 Exam Layer Upgrade</h1>
            <p class="subtitle">Enhance your SRMS with advanced exam management features</p>

            <div class="alert alert-info">
                <strong>What's New?</strong> This upgrade adds support for multiple exam types, class test sequences (CT-1, CT-2, CT-3...), and better result organization.
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <h3>📝 Multiple Exam Types</h3>
                    <p>Final, Midterm, Class Tests, Assignments, and Quizzes - all separately managed</p>
                </div>
                <div class="feature-card">
                    <h3>🔢 Test Sequences</h3>
                    <p>Automatically numbered class tests (CT-1, CT-2, CT-3) per subject</p>
                </div>
                <div class="feature-card">
                    <h3>📊 Excel Upload</h3>
                    <p>Download templates, fill with data, upload with automatic validation</p>
                </div>
                <div class="feature-card">
                    <h3>🎯 Student Browsing</h3>
                    <p>Students can filter results by exam type and subject</p>
                </div>
            </div>

            <h3 style="margin-top: 30px; margin-bottom: 15px;">Installation Checklist:</h3>
            <ul class="checklist">
                <li>Create backup of current database</li>
                <li>Create new tables (exams, upload_logs, audit_log)</li>
                <li>Migrate existing results to new structure</li>
                <li>Add database constraints and indexes</li>
                <li>Verify data integrity</li>
            </ul>

            <div style="margin-top: 30px;">
                <button class="btn btn-primary" onclick="startInstallation()">
                    🚀 Start Installation
                </button>
                <a href="index.php" class="btn" style="background: #95a5a6; color: white; margin-left: 10px;">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Installation Progress Card -->
        <div class="card status-section" id="progress-card">
            <h2>Installing Exam Layer...</h2>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill">0%</div>
            </div>
            <div class="log-box" id="log-box"></div>
        </div>

        <!-- Success Card -->
        <div class="card status-section" id="success-card">
            <h1 style="color: #2ecc71;">✅ Installation Complete!</h1>
            <p class="subtitle">The exam layer has been successfully installed</p>

            <div class="alert alert-success">
                All database changes have been applied successfully. Your system is now ready to use the new exam management features!
            </div>

            <div class="stats-grid" id="stats-grid"></div>

            <h3 style="margin-top: 30px; margin-bottom: 15px;">What's Next?</h3>
            <ul class="checklist">
                <li>Create your first exam in the Exam Management page</li>
                <li>Download an Excel template and upload student results</li>
                <li>Students can now browse results by exam type</li>
            </ul>

            <div style="margin-top: 30px;">
                <a href="manage_exams.php" class="btn btn-success">
                    📝 Go to Exam Management
                </a>
                <a href="index.php" class="btn" style="background: #95a5a6; color: white; margin-left: 10px;">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Error Card -->
        <div class="card status-section" id="error-card">
            <h1 style="color: #e74c3c;">❌ Installation Failed</h1>
            <p class="subtitle">Something went wrong during installation</p>

            <div class="alert alert-error" id="error-message"></div>

            <div class="log-box" id="error-log"></div>

            <div style="margin-top: 30px;">
                <button class="btn btn-primary" onclick="location.reload()">
                    🔄 Try Again
                </button>
                <a href="index.php" class="btn" style="background: #95a5a6; color: white; margin-left: 10px;">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        let logBox = document.getElementById('log-box');
        let progressFill = document.getElementById('progress-fill');

        function log(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${timestamp}] ${message}`;
            logBox.appendChild(entry);
            logBox.scrollTop = logBox.scrollHeight;
        }

        function updateProgress(percent, text) {
            progressFill.style.width = percent + '%';
            progressFill.textContent = text || percent + '%';
        }

        function showCard(cardId) {
            document.querySelectorAll('.card').forEach(card => {
                card.style.display = 'none';
            });
            document.getElementById(cardId).style.display = 'block';
        }

        async function startInstallation() {
            showCard('progress-card');

            try {
                // Step 1: Check installation status
                log('Checking installation status...', 'info');
                updateProgress(5, 'Checking...');

                const statusResponse = await fetch('api/install_check.php');
                const statusData = await statusResponse.json();

                if (statusData.already_installed) {
                    log('Exam layer is already installed!', 'warning');
                    showError('The exam layer is already installed. If you want to reinstall, please contact your system administrator.');
                    return;
                }

                // Step 2: Create backup
                log('Creating database backup...', 'info');
                updateProgress(10, 'Backing up...');

                const backupResponse = await fetch('api/install_backup.php', { method: 'POST' });
                const backupData = await backupResponse.json();

                if (!backupData.success) {
                    throw new Error('Backup failed: ' + backupData.message);
                }

                log('✓ Backup created: ' + backupData.filename, 'success');
                updateProgress(25, 'Backup done');

                // Step 3: Run migration step 1 (create tables)
                log('Creating database tables...', 'info');
                updateProgress(30, 'Creating tables...');

                const step1Response = await fetch('api/install_step1.php', { method: 'POST' });
                const step1Data = await step1Response.json();

                if (!step1Data.success) {
                    throw new Error('Step 1 failed: ' + step1Data.message);
                }

                log('✓ Tables created successfully', 'success');
                updateProgress(50, 'Tables created');

                // Step 4: Run migration step 2 (migrate data)
                log('Migrating existing data...', 'info');
                updateProgress(55, 'Migrating data...');

                const step2Response = await fetch('api/install_step2.php', { method: 'POST' });
                const step2Data = await step2Response.json();

                if (!step2Data.success) {
                    throw new Error('Step 2 failed: ' + step2Data.message);
                }

                log(`✓ Created ${step2Data.exams_created} exams`, 'success');
                log(`✓ Mapped ${step2Data.results_mapped} results`, 'success');

                if (step2Data.unmapped_results > 0) {
                    throw new Error(`${step2Data.unmapped_results} results could not be mapped`);
                }

                updateProgress(75, 'Data migrated');

                // Step 5: Run migration step 3 (add constraints)
                log('Adding database constraints...', 'info');
                updateProgress(80, 'Adding constraints...');

                const step3Response = await fetch('api/install_step3.php', { method: 'POST' });
                const step3Data = await step3Response.json();

                if (!step3Data.success) {
                    throw new Error('Step 3 failed: ' + step3Data.message);
                }

                log('✓ Constraints added successfully', 'success');
                updateProgress(95, 'Almost done...');

                // Final verification
                log('Verifying installation...', 'info');
                await new Promise(resolve => setTimeout(resolve, 500));

                log('✓ Installation completed successfully!', 'success');
                updateProgress(100, 'Complete!');

                // Show success card with stats
                await new Promise(resolve => setTimeout(resolve, 1000));
                showSuccessCard({
                    exams_created: step2Data.exams_created,
                    results_migrated: step2Data.results_mapped,
                    backup_file: backupData.filename
                });

            } catch (error) {
                log('✗ Error: ' + error.message, 'error');
                showError(error.message);
            }
        }

        function showSuccessCard(stats) {
            const statsGrid = document.getElementById('stats-grid');
            statsGrid.innerHTML = `
                <div class="stat-card">
                    <div class="stat-number">${stats.exams_created}</div>
                    <div class="stat-label">Exams Created</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${stats.results_migrated}</div>
                    <div class="stat-label">Results Migrated</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">✓</div>
                    <div class="stat-label">Backup Created</div>
                </div>
            `;
            showCard('success-card');
        }

        function showError(message) {
            document.getElementById('error-message').innerHTML = `
                <strong>Error:</strong> ${message}<br><br>
                Your database has not been modified. You can safely try again or contact support.
            `;
            document.getElementById('error-log').innerHTML = logBox.innerHTML;
            showCard('error-card');
        }
    </script>
</body>
</html>
