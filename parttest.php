<?php
/**
 * Project Structure Viewer
 * Displays the directory structure of your project in text format
 */

// Configuration
$baseDir = __DIR__; // Current directory, change if needed
$excludedDirs = ['.git', 'node_modules', 'vendor']; // Directories to exclude
$excludedFiles = ['.DS_Store', 'Thumbs.db']; // Files to exclude

function displayDirectoryStructure($dir, $prefix = '', $isLast = true) {
    global $excludedDirs, $excludedFiles;
    
    // Get all items in the directory
    $items = scandir($dir);
    $directories = [];
    $files = [];
    
    // Separate directories and files, excluding hidden and specified items
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (in_array($item, $excludedDirs) || in_array($item, $excludedFiles)) continue;
        
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($fullPath)) {
            $directories[] = $item;
        } else {
            $files[] = $item;
        }
    }
    
    // Sort alphabetically
    sort($directories);
    sort($files);
    
    $allItems = array_merge($directories, $files);
    $totalItems = count($allItems);
    
    // Display current directory name (only for subdirectories)
    if ($prefix !== '') {
        $currentDir = basename($dir);
        echo $prefix . ($isLast ? '└── ' : '├── ') . $currentDir . "/\n";
        $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
    } else {
        $newPrefix = '';
        echo basename($dir) . "/\n";
    }
    
    // Display contents
    foreach ($allItems as $index => $item) {
        $isLastItem = ($index === $totalItems - 1);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($fullPath)) {
            // It's a directory - recursively display its contents
            displayDirectoryStructure($fullPath, $newPrefix, $isLastItem);
        } else {
            // It's a file
            echo $newPrefix . ($isLastItem ? '└── ' : '├── ') . $item . "\n";
        }
    }
}

// HTML header for web display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Structure</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Project Structure: <?php echo basename($baseDir); ?></h1>
    <pre>
<?php
// Display the structure
displayDirectoryStructure($baseDir);
?>
    </pre>
    
    <hr>
    <p><small>Generated on: <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>