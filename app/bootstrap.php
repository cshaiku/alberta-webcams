<?php
declare(strict_types=1);
date_default_timezone_set('America/Edmonton');
$base = getenv('AWC_APP_ROOT') ?: dirname(__DIR__);
if (is_file(__DIR__ . '/location.php')) { $base = require __DIR__ . '/location.php'; }
define('AWC_ROOT', $base);
$local = is_file(AWC_ROOT . '/config/local.php') ? require AWC_ROOT . '/config/local.php' : [];
define('AWC_DATA', getenv('AWC_DATA_ROOT') ?: ($local['data_root'] ?? AWC_ROOT . '/runtime'));
if (!is_dir(AWC_DATA)) { mkdir(AWC_DATA, 0700, true); }
function cameras(): array { return json_decode(file_get_contents(AWC_ROOT . '/config/cameras.json'), true, 512, JSON_THROW_ON_ERROR); }
function camera(string $id): ?array { foreach (cameras() as $c) { if ($c['id'] === $id) return $c; } return null; }
function state(): array { $s = @file_get_contents(AWC_DATA . '/state.json'); return $s ? (json_decode($s, true) ?: []) : []; }
function json_response(array $value, int $status = 200): never { http_response_code($status); header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); exit; }
function valid_date(string $date): bool { $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date); return $d && $d->format('Y-m-d') === $date; }
function frames(string $id, string $date): array {
    if (!camera($id) || !valid_date($date)) return [];
    $files = glob(AWC_DATA . '/archive/' . $id . '/' . str_replace('-', '/', $date) . '/*.jpg') ?: [];
    sort($files); $out = [];
    foreach ($files as $file) { $stamp = basename($file, '.jpg'); $out[] = ['stamp' => $stamp, 'url' => 'image.php?camera=' . rawurlencode($id) . '&stamp=' . rawurlencode($stamp)]; }
    return $out;
}
function dates(string $id): array {
    if (!camera($id)) return [];
    $out = [];
    foreach (glob(AWC_DATA . '/archive/' . $id . '/*/*/*', GLOB_ONLYDIR) ?: [] as $dir) { $parts = array_slice(explode('/', $dir), -3); $out[] = implode('-', $parts); }
    rsort($out); return $out;
}
