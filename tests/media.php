<?php
declare(strict_types=1);
$root = sys_get_temp_dir() . '/awc-media-test-' . bin2hex(random_bytes(8));
putenv('AWC_DATA_ROOT=' . $root);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/media.php';
function verify(bool $condition, string $label): void { if (!$condition) throw new RuntimeException($label); echo "PASS $label\n"; }
try {
    verify(snapshot_path('../../etc/passwd', '2026-10-31_010000') === null, 'Media traversal rejected');
    verify(snapshot_path('ab_128', '2026-02-30_010000') === null, 'Invalid snapshot date rejected');
    $dir = $root . '/archive/ab_128/2026/10/31';
    mkdir($dir, 0700, true);
    $source = $dir . '/2026-10-31_010000.webp';
    $image = imagecreatetruecolor(640, 400);
    ob_start(); imagejpeg($image, null, 90); $jpeg = ob_get_clean();
    verify(store_webp_snapshot($jpeg, $source), 'JPEG provider data converts to verified WebP capture');
    verify(!store_webp_snapshot('invalid', $dir . '/invalid.webp'), 'Invalid capture is rejected');
    $file = snapshot_path('ab_128', '2026-10-31_010000');
    verify($file === $source, 'Catalogued snapshot resolves inside archive');
    verify(count(frames('ab_128', '2026-10-31')) === 1, 'Archive enumerates WebP captures');
    verify(glob($dir . '/*.jpg') === [], 'No JPEG capture is written');
    $variant = webp_variant($source, 'ab_128', '2026-10-31_010000', 400);
    $info = getimagesize($variant);
    verify($info[0] === 400 && $info[1] === 250 && $info['mime'] === 'image/webp', 'WebP resize preserves proportion');
    $larger = webp_variant($source, 'ab_128', '2026-10-31_010000', 840);
    verify($larger === $source && getimagesize($larger)[0] === 640, 'Large candidates reuse the master without upscaling or duplicate files');
    verify(webp_variant($source, 'ab_128', '2026-10-31_010000', 999) === null, 'Unbounded variant sizes rejected');
    $originalHash = hash_file('sha256', $source);
    verify(prepare_webp_variants($source, 'ab_128', '2026-10-31_010000') === 3, 'Capture preparation creates all three WebP candidates');
    verify(file_exists($source) && hash_file('sha256', $source) === $originalHash, 'WebP master is preserved unchanged');
} finally {
    if (is_dir($root)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) { $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname()); }
        rmdir($root);
    }
}
