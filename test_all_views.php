<?php
require_once __DIR__ . '/app/autoload.php';

function getFilesRecursively($dir, &$results = array()) {
    $files = scandir($dir);
    foreach ($files as $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getFilesRecursively($path, $results);
        }
    }
    return $results;
}

$files = getFilesRecursively(__DIR__ . '/views');
$errors = 0;

foreach ($files as $file) {
    $output = [];
    $return_var = 0;
    exec("php -l \"" . $file . "\"", $output, $return_var);
    if ($return_var !== 0) {
        echo "SYNTAX ERROR in: $file\n" . implode("\n", $output) . "\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "SUCCESS: All " . count($files) . " PHP view files passed linting check cleanly!\n";
} else {
    echo "FAILED: $errors files had syntax errors.\n";
}
