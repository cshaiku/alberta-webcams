<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!function_exists('imagewebp') && !in_array('--webp-worker', $argv, true)) {
    $command = array_merge([PHP_BINARY, '-d', 'extension=gd', __FILE__], array_slice($argv, 1), ['--webp-worker']);
    passthru(implode(' ', array_map('escapeshellarg', $command)), $status);
    exit($status);
}
require dirname(__DIR__) . '/app/bootstrap.php';
require AWC_ROOT . '/app/media.php' . (in_array('--staged', $argv, true) ? '.pending' : '');
$lock = fopen(AWC_DATA . '/retrieve.lock', 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "Collector is busy. Retry after it finishes.\n"); exit(1); }
try {
    $verified = []; $jpegBytes = 0; $webpBytes = 0; $removedBytes = 0; $removedVariants = 0;
    foreach (cameras() as $camera) {
        foreach (glob(AWC_DATA . '/archive/' . $camera['id'] . '/*/*/*/*.jpg') ?: [] as $source) {
            $target = substr($source, 0, -4) . '.webp';
            $original = getimagesize($source);
            if (!is_file($target) && !store_webp_snapshot(file_get_contents($source), $target)) throw new RuntimeException('WebP conversion failed: ' . basename($source));
            $converted = getimagesize($target);
            if (!$original || !$converted || $converted['mime'] !== 'image/webp' || $original[0] !== $converted[0] || $original[1] !== $converted[1] || !imagecreatefromwebp($target)) throw new RuntimeException('Converted capture verification failed.');
            $jpegBytes += filesize($source); $webpBytes += filesize($target);
            $verified[] = $source;
        }
    }
    if (in_array('--prune', $argv, true)) {
        foreach ($verified as $source) { if (!unlink($source)) throw new RuntimeException('Unable to remove verified JPEG.'); }
        foreach (cameras() as $camera) {
            foreach (glob(AWC_DATA . '/archive/' . $camera['id'] . '/*/*/*/*.webp') ?: [] as $master) {
                $stamp = basename($master, '.webp'); $width = getimagesize($master)[0];
                foreach ([400,640,840] as $candidate) {
                    $duplicate = AWC_DATA . '/variants/' . $camera['id'] . '/' . $stamp . '-' . $candidate . '.webp';
                    if ($candidate >= $width && is_file($duplicate)) { $removedBytes += filesize($duplicate); if (!unlink($duplicate)) throw new RuntimeException('Unable to remove redundant variant.'); $removedVariants++; }
                }
            }
        }
    }
    echo json_encode(['capturesVerified'=>count($verified),'jpegBytes'=>$jpegBytes,'webpBytes'=>$webpBytes,'jpegCopiesRemoved'=>in_array('--prune',$argv,true)?count($verified):0,'redundantVariantsRemoved'=>$removedVariants,'redundantVariantBytesRemoved'=>$removedBytes],JSON_UNESCAPED_SLASHES) . PHP_EOL;
} finally { flock($lock, LOCK_UN); fclose($lock); }
