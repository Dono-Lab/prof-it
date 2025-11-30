<?php
$targetDir = __DIR__ . '/../assets/img/avatars';
echo "Target Dir (Raw): " . $targetDir . "\n";
echo "Target Dir (Real): " . realpath($targetDir) . "\n";
echo "Is Dir: " . (is_dir($targetDir) ? 'Yes' : 'No') . "\n";
echo "Is Writable: " . (is_writable($targetDir) ? 'Yes' : 'No') . "\n";

echo "Files in dir:\n";
$files = scandir($targetDir);
foreach ($files as $file) {
    echo $file . "\n";
}
?>
