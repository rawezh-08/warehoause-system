<?php
// Script to add authentication check to all PHP files in AJAX directory

// Directory paths
$ajaxDir = __DIR__ . '/ajax';
$processDir = __DIR__ . '/process';

// Authentication line to add
$authLine = <<<EOT
<?php
// Include authentication check
require_once '../includes/auth.php';

EOT;

// Function to add authentication to files
function addAuthToFiles($directory) {
    global $authLine;
    
    $fileCount = 0;
    $modifiedCount = 0;
    
    // Get all PHP files in the directory
    $files = glob($directory . '/*.php');
    
    foreach ($files as $file) {
        $fileCount++;
        
        // Read file content
        $content = file_get_contents($file);
        
        // Check if file already has auth check
        if (strpos($content, "require_once '../includes/auth.php'") !== false) {
            echo "File already has auth check: " . basename($file) . "\n";
            continue;
        }
        
        // Replace opening PHP tag with auth check
        $newContent = str_replace('<?php', $authLine, $content);
        
        // Write back to file
        file_put_contents($file, $newContent);
        
        $modifiedCount++;
        echo "Added auth check to: " . basename($file) . "\n";
    }
    
    return array($fileCount, $modifiedCount);
}

// Process AJAX directory
echo "Processing AJAX directory...\n";
list($ajaxTotal, $ajaxModified) = addAuthToFiles($ajaxDir);
echo "Processed $ajaxTotal files in AJAX directory, modified $ajaxModified files.\n\n";

// Process Process directory
echo "Processing Process directory...\n";
list($processTotal, $processModified) = addAuthToFiles($processDir);
echo "Processed $processTotal files in Process directory, modified $processModified files.\n";

echo "\nCompleted adding authentication checks to files.\n";
?> 