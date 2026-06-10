<?php
$dir = __DIR__ . '/../src';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$files = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (substr($file->getFilename(), -4) !== '.php') continue;
    $files[] = $file->getPathname();
}
$missing = [];
foreach ($files as $file) {
    $lines = file($file);
    $count = count($lines);
    for ($i=0; $i<$count; $i++) {
        if (preg_match('/\bpublic\s+function\b/', $lines[$i])) {
            // look back up to 20 lines for a docblock starting with '/**'
            $doc = false;
            $j = $i-1;
            $checked = 0;
            while ($j >= 0 && $checked < 20) {
                $line = $lines[$j];
                if (preg_match('/\/\*\*/', $line) === 1 || preg_match('/^\s*\/\*/', $line) === 1) { $doc = true; break; }
                if (trim($line) === '') { $j--; $checked++; continue; }
                // keep searching even if we meet other code lines — docblock might be separated
                $j--; $checked++;
            }
            if (!$doc) {
                $missing[] = [
                    'file' => $file,
                    'line' => $i+1,
                    'code' => trim($lines[$i])
                ];
            }
        }
    }
}
foreach ($missing as $m) {
    echo "{$m['file']}:{$m['line']}: {$m['code']}\n";
}
echo "Total missing: " . count($missing) . "\n";
