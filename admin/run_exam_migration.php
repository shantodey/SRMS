<?php
/**
 * EXAM LAYER MIGRATION RUNNER
 *
 * This script safely runs all exam layer migration steps:
 * 1. Creates backup
 * 2. Creates tables
 * 3. Migrates data from results to exams
 * 4. Adds constraints
 *
 * Usage:
 *   php run_exam_migration.php [--step=N] [--dry-run] [--force]
 *
 * Options:
 *   --step=N      Run only step N (1, 2, or 3)
 *   --dry-run     Show what would be done without executing
 *   --force       Skip confirmation prompts
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Parse command line arguments
$options = getopt('', ['step:', 'dry-run', 'force']);
$step = isset($options['step']) ? (int)$options['step'] : 'all';
$dryRun = isset($options['dry-run']);
$force = isset($options['force']);

// ANSI color codes for terminal output
$colors = [
    'reset'   => "\033[0m",
    'red'     => "\033[31m",
    'green'   => "\033[32m",
    'yellow'  => "\033[33m",
    'blue'    => "\033[34m",
    'magenta' => "\033[35m",
    'cyan'    => "\033[36m",
    'bold'    => "\033[1m",
];

function colorize($text, $color, $bold = false) {
    global $colors;
    $output = $bold ? $colors['bold'] : '';
    $output .= $colors[$color] . $text . $colors['reset'];
    return $output;
}

function log_info($message) {
    echo colorize("[INFO] ", 'blue', true) . $message . "\n";
}

function log_success($message) {
    echo colorize("[SUCCESS] ", 'green', true) . $message . "\n";
}

function log_warning($message) {
    echo colorize("[WARNING] ", 'yellow', true) . $message . "\n";
}

function log_error($message) {
    echo colorize("[ERROR] ", 'red', true) . $message . "\n";
}

function log_step($stepNum, $message) {
    echo "\n" . colorize("=== STEP $stepNum: $message ===", 'cyan', true) . "\n";
}

function confirm($message) {
    global $force;
    if ($force) {
        return true;
    }
    echo colorize($message . " (y/N): ", 'yellow');
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return strtolower(trim($line)) === 'y';
}

// Display header
echo "\n";
echo colorize(str_repeat("=", 70), 'cyan') . "\n";
echo colorize("  EXAM LAYER MIGRATION RUNNER", 'cyan', true) . "\n";
echo colorize(str_repeat("=", 70), 'cyan') . "\n\n";

if ($dryRun) {
    log_warning("DRY RUN MODE - No changes will be made");
}

// =====================================================
// STEP 0: PRE-FLIGHT CHECKS
// =====================================================

log_step(0, "PRE-FLIGHT CHECKS");

// Check database connection
if ($conn->connect_error) {
    log_error("Database connection failed: " . $conn->connect_error);
    exit(1);
}
log_success("Database connection OK");

// Check if results table exists
$result = $conn->query("SHOW TABLES LIKE 'results'");
if ($result->num_rows === 0) {
    log_error("Results table does not exist. Cannot proceed.");
    exit(1);
}
log_success("Results table exists");

// Count existing results
$result = $conn->query("SELECT COUNT(*) as count FROM results");
$resultCount = $result->fetch_assoc()['count'];
log_info("Found $resultCount existing result records");

// Check migration status
$result = $conn->query("SHOW TABLES LIKE 'migration_status'");
$migrationTableExists = $result->num_rows > 0;

if ($migrationTableExists) {
    $result = $conn->query("SELECT * FROM migration_status WHERE migration_name LIKE 'exam_layer%' ORDER BY id");
    if ($result->num_rows > 0) {
        log_info("Previous migration runs found:");
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $color = $status === 'completed' ? 'green' : ($status === 'failed' ? 'red' : 'yellow');
            echo "  - {$row['migration_name']}: " . colorize($status, $color) . "\n";
        }
    }
}

// =====================================================
// BACKUP DATABASE
// =====================================================

if (!$dryRun && ($step === 'all' || $step === 1)) {
    log_step('BACKUP', "Creating database backup");

    if (!confirm("Create database backup before migration?")) {
        log_error("User cancelled. Backup is strongly recommended!");
        exit(1);
    }

    exec('php backup_database.php 2>&1', $output, $returnVar);

    if ($returnVar === 0) {
        log_success("Database backup created successfully");
        foreach ($output as $line) {
            echo "  $line\n";
        }
    } else {
        log_error("Backup failed!");
        foreach ($output as $line) {
            echo "  $line\n";
        }
        if (!confirm("Continue without backup? (NOT RECOMMENDED)")) {
            exit(1);
        }
    }
}

// =====================================================
// FUNCTION: Run SQL File
// =====================================================

function runSqlFile($conn, $filename, $dryRun = false) {
    if (!file_exists($filename)) {
        log_error("SQL file not found: $filename");
        return false;
    }

    log_info("Reading SQL file: $filename");
    $sql = file_get_contents($filename);

    if ($dryRun) {
        log_info("DRY RUN: Would execute " . strlen($sql) . " bytes of SQL");
        return true;
    }

    // Split into individual statements (basic split by semicolon)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );

    log_info("Executing " . count($statements) . " SQL statements...");

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $index => $statement) {
        // Skip comments and empty statements
        if (empty(trim($statement)) || preg_match('/^\s*--/', $statement)) {
            continue;
        }

        if ($conn->multi_query($statement . ';')) {
            do {
                if ($result = $conn->store_result()) {
                    // Display SELECT results
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        if (isset($row['status'])) {
                            log_info("  " . $row['status']);
                        } else {
                            // Display first row for verification queries
                            foreach ($row as $key => $value) {
                                echo "  $key: $value\n";
                            }
                        }
                    }
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            $successCount++;
        } else {
            if ($conn->errno !== 0) {
                log_warning("  Statement " . ($index + 1) . " error: " . $conn->error);
                $errorCount++;
            }
        }
    }

    log_info("Completed: $successCount successful, $errorCount errors");

    return $errorCount === 0;
}

// =====================================================
// STEP 1: CREATE TABLES
// =====================================================

if ($step === 'all' || $step === 1) {
    log_step(1, "Creating exams table and audit tables");

    if (!$dryRun && !confirm("Run Step 1: Create tables?")) {
        log_warning("Step 1 skipped by user");
    } else {
        $success = runSqlFile($conn, __DIR__ . '/exam_layer_migration.sql', $dryRun);

        if ($success) {
            log_success("Step 1 completed successfully");
        } else {
            log_error("Step 1 failed - check errors above");
            if (!confirm("Continue to next step despite errors?")) {
                exit(1);
            }
        }
    }
}

// =====================================================
// STEP 2: MIGRATE DATA
// =====================================================

if ($step === 'all' || $step === 2) {
    log_step(2, "Migrating existing results to exams");

    log_warning("This step will create exam records from existing results data");

    if (!$dryRun && !confirm("Run Step 2: Migrate data?")) {
        log_warning("Step 2 skipped by user");
    } else {
        $success = runSqlFile($conn, __DIR__ . '/exam_layer_migration_step2.sql', $dryRun);

        if ($success) {
            log_success("Step 2 completed successfully");

            // Display migration summary
            if (!$dryRun) {
                $examCount = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
                $mappedResults = $conn->query("SELECT COUNT(*) as count FROM results WHERE exam_id IS NOT NULL")->fetch_assoc()['count'];
                $unmappedResults = $conn->query("SELECT COUNT(*) as count FROM results WHERE exam_id IS NULL")->fetch_assoc()['count'];

                echo "\n";
                log_info("Migration Summary:");
                echo "  - Exams created: " . colorize($examCount, 'green') . "\n";
                echo "  - Results mapped: " . colorize($mappedResults, 'green') . "\n";
                echo "  - Results unmapped: " . colorize($unmappedResults, $unmappedResults > 0 ? 'red' : 'green') . "\n";

                if ($unmappedResults > 0) {
                    log_error("Some results could not be mapped to exams!");
                    log_warning("You must resolve this before proceeding to Step 3");
                    exit(1);
                }
            }
        } else {
            log_error("Step 2 failed - check errors above");
            exit(1);
        }
    }
}

// =====================================================
// STEP 3: ADD CONSTRAINTS
// =====================================================

if ($step === 'all' || $step === 3) {
    log_step(3, "Adding foreign keys and unique constraints");

    log_warning("This step adds constraints and cannot be easily reversed");

    if (!$dryRun && !confirm("Run Step 3: Add constraints?")) {
        log_warning("Step 3 skipped by user");
    } else {
        $success = runSqlFile($conn, __DIR__ . '/exam_layer_migration_step3.sql', $dryRun);

        if ($success) {
            log_success("Step 3 completed successfully");
        } else {
            log_error("Step 3 failed - check errors above");
            exit(1);
        }
    }
}

// =====================================================
// FINAL STATUS
// =====================================================

echo "\n";
log_step('COMPLETE', "Migration Summary");

if (!$dryRun) {
    $result = $conn->query("SELECT * FROM migration_status WHERE migration_name LIKE 'exam_layer%' ORDER BY id");

    $allCompleted = true;
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        $color = $status === 'completed' ? 'green' : ($status === 'failed' ? 'red' : 'yellow');
        echo "  {$row['migration_name']}: " . colorize($status, $color) . "\n";

        if ($status !== 'completed') {
            $allCompleted = false;
        }

        if ($row['error_message']) {
            echo "    Error: " . colorize($row['error_message'], 'red') . "\n";
        }
    }

    echo "\n";
    if ($allCompleted) {
        log_success("All migration steps completed successfully!");
        echo "\n";
        log_info("Next steps:");
        echo "  1. Test the system with sample data\n";
        echo "  2. Verify exam creation and result upload workflows\n";
        echo "  3. Check student result browsing UI\n";
        echo "  4. Once verified, you can remove the old exam_type column from results\n";
    } else {
        log_error("Some migration steps failed. Review errors above.");
        echo "\n";
        log_info("To rollback:");
        echo "  1. Restore from backup: mysql -u root mawts < backups/mawts_backup_TIMESTAMP.sql\n";
        echo "  2. Review migration logs and fix issues\n";
        echo "  3. Re-run migration\n";
    }
} else {
    log_info("DRY RUN completed - no changes were made");
}

echo "\n";
$conn->close();
