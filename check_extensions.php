<?php
echo "<h3>PHP Extension Check</h3>";

$required = ['zip', 'xml', 'fileinfo', 'libxml', 'mbstring', 'openssl'];
$loaded = get_loaded_extensions();

foreach ($required as $ext) {
    if (in_array($ext, $loaded)) {
        echo "✓ <span style='color: green;'>$ext</span> is loaded<br>";
    } else {
        echo "✗ <span style='color: red;'>$ext</span> is MISSING<br>";
    }
}

echo "<h3>ZipArchive Class Check</h3>";
if (class_exists('ZipArchive')) {
    echo "✓ <span style='color: green;'>ZipArchive class is available</span><br>";
    
    // Test functionality
    $zip = new ZipArchive();
    echo "ZipArchive test: <span style='color: green;'>Working</span><br>";
} else {
    echo "✗ <span style='color: red;'>ZipArchive class NOT available</span><br>";
}

echo "<h3>PHP.ini Info</h3>";
echo "Loaded php.ini: " . php_ini_loaded_file() . "<br>";
echo "Additional .ini files: " . (php_ini_scanned_files() ?: 'None') . "<br>";

echo "<h3>Project Directory Structure</h3>";
function listDirectory($dir, $prefix = '') {
    $files = scandir($dir);
    $files = array_filter($files, function($file) {
        return !in_array($file, ['.', '..']);
    });
    $files = array_values($files);
    $total = count($files);

    echo "<div style='font-family: monospace; background-color: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    foreach($files as $i => $file) {
        $isLast = ($i === $total - 1);
        $path = $dir . '/' . $file;
        
        // Print current item
        echo $prefix;
        echo $isLast ? "└── " : "├── ";
        
        if(is_dir($path)) {
            echo "<span style='color: #0366d6;'>📁 $file</span><br>";
            // Recursive call with updated prefix
            $newPrefix = $prefix . ($isLast ? "    " : "│   ");
            listDirectory($path, $newPrefix);
        } else {
            echo "📄 $file<br>";
        }
    }
    echo "</div>";
}

listDirectory(__DIR__);
?>