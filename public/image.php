<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require AWC_ROOT . '/app/media.php';

$id = (string)($_GET['camera'] ?? '');
$stamp = (string)($_GET['stamp'] ?? '');
$file = snapshot_path($id, $stamp);
if (!$file) { http_response_code(404); exit; }
$format = (string)($_GET['format'] ?? 'webp');
if ($format !== 'webp') { http_response_code(400); exit; }
if (isset($_GET['w'])) {
    $width = filter_var($_GET['w'] ?? 840, FILTER_VALIDATE_INT);
    if (!in_array($width, [400, 640, 840], true)) { http_response_code(400); exit; }
    $file = webp_variant($file, $id, $stamp, $width);
    if (!$file) { http_response_code(503); exit; }
}
header('Content-Type: image/' . $format);
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($file));
readfile($file);
