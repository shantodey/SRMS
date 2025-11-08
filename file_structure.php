<?php
// file_structure.php

function listFiles($dir, $prefix = '') {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if ($item === 'vendor') continue; // Skip vendor folder

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $isDir = is_dir($path);

        $icon = $isDir ? "📁" : "📄";
        echo $prefix . $icon . " " . htmlspecialchars($item) . "<br>";

        if ($isDir) {
            listFiles($path, $prefix . "&nbsp;&nbsp;&nbsp;&nbsp;");
        }
    }
}

echo "<pre style='font-family:Consolas, monospace; font-size:14px'>";
echo "📂 Project Structure: " . getcwd() . "\n\n";
listFiles('.');
echo "</pre>";
?>
