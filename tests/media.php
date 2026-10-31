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
    $source = $dir . '/2026-10-31_010000.jpg';
    $image = imagecreatetruecolor(640, 400);
    imagejpeg($image, $source, 90);
    $file = snapshot_path('ab_128', '2026-10-31_010000');
    verify($file === $source, 'Catalogued snapshot resolves inside archive');
    $variant = webp_variant($source, 'ab_128', '2026-10-31_010000', 400);
    $info = getimagesize($variant);
    verify($info[0] === 400 && $info[1] === 250 && $info['mime'] === 'image/webp', 'WebP resize preserves proportion');
    $larger = webp_variant($source, 'ab_128', '2026-10-31_010000', 840);
    verify(getimagesize($larger)[0] === 640, 'Variants never upscale the original');
    verify(webp_variant($source, 'ab_128', '2026-10-31_010000', 999) === null, 'Unbounded variant sizes rejected');
    verify(file_exists($source), 'Original JPEG is preserved');
} finally {
    if (is_dir($root)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) { $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname()); }
        rmdir($root);
    }
}
