<?php
$rootPath = dirname(__DIR__);

$now = date('Y-m-d H:i:s');
$files = scandir($rootPath);
$files = array_diff($files, ['.', '..']); // Remove current and parent directories
$filesSize = 0;
$pushCount = 0;
$filesToPush = [];

function getFilesRecursively($directory) {
    $files = scandir($directory);
    $files = array_diff($files, ['.', '..']); // Remove current and parent directories
    foreach ($files as $file) {
        $filePath = $directory . '/' . $file;
        if (is_dir($filePath)) {
            getFilesRecursively($filePath);
        } else {
            $fileSize = filesize($filePath);
            global $filesSize, $pushCount, $filesToPush;
            $filesSize += $fileSize;

            if ($filesSize > 524288000) { // 500 * 1024 * 1024 = 500MB
                $pushCount++;
                $filesToPush[$pushCount][] = escapeshellarg($filePath); // Use full path
                $filesSize = $fileSize; // Reset for the next push
            } else {
                $filesToPush[$pushCount][] = escapeshellarg($filePath); // Use full path
            }
        }
    }
}

getFilesRecursively($rootPath);

if (!empty($filesToPush)) {
    foreach ($filesToPush as $pushIndex => $files) {
        foreach ($files as $file) {
            exec("cd {$rootPath} && /usr/bin/git add {$file}");
        }
        $escapedMessage = escapeshellarg("auto update @ {$now} - Push {$pushIndex}");
        exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m {$escapedMessage}");
        exec("cd {$rootPath} && /usr/bin/git push origin master");
    }
}