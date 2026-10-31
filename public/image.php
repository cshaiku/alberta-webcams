<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$id = (string)($_GET['camera'] ?? ''); $stamp = (string)($_GET['stamp'] ?? '');
if (!camera($id) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})_\d{6}$/D', $stamp, $m) || !valid_date("$m[1]-$m[2]-$m[3]")) { http_response_code(404); exit; }
$file = AWC_DATA . "/archive/$id/$m[1]/$m[2]/$m[3]/$stamp.jpg";
if (!is_file($file)) { http_response_code(404); exit; }
header('Content-Type: image/jpeg'); header('X-Content-Type-Options: nosniff'); header('Cache-Control: public, max-age=31536000, immutable'); header('Content-Length: ' . filesize($file)); readfile($file);
