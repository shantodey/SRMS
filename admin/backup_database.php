<?php
/**
 * Database Backup Script
 * Run this BEFORE applying any migrations
 * Creates timestamped SQL dump in admin/backups/ folder
 */

require_once '../config/database.php';

// Create backups directory if it doesn't exist
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Generate timestamped filename
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/mawts_backup_' . $timestamp . '.sql';

// MySQL dump command
$command = sprintf(
    'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
    DB_USER,
    DB_PASS,
    DB_HOST,
    DB_NAME,
    escapeshellarg($backupFile)
);

// Execute backup
exec($command, $output, $returnVar);

if ($returnVar === 0 && file_exists($backupFile)) {
    $fileSize = filesize($backupFile);
    echo "SUCCESS: Database backup created\n";
    echo "File: $backupFile\n";
    echo "Size: " . round($fileSize / 1024, 2) . " KB\n";
    echo "Timestamp: $timestamp\n";
    exit(0);
} else {
    echo "ERROR: Backup failed\n";
    echo "Command: $command\n";
    echo "Output: " . implode("\n", $output) . "\n";
    exit(1);
}
