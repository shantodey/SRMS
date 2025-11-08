<?php
/**
 * Create database backup before installation
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Create backups directory if it doesn't exist
    $backupDir = __DIR__ . '/../backups';
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
        DB_PASS === '' ? DB_USER : DB_PASS,
        DB_HOST,
        DB_NAME,
        escapeshellarg($backupFile)
    );

    // Execute backup
    exec($command, $output, $returnVar);

    if ($returnVar === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
        $fileSize = filesize($backupFile);
        echo json_encode([
            'success' => true,
            'filename' => basename($backupFile),
            'filepath' => $backupFile,
            'size' => round($fileSize / 1024, 2) . ' KB',
            'timestamp' => $timestamp
        ]);
    } else {
        throw new Exception('Backup creation failed. Output: ' . implode("\n", $output));
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
