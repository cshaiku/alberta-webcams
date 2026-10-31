<?php
declare(strict_types=1);
$base = getenv('AWC_APP_ROOT') ?: dirname(__DIR__);
if (is_file(__DIR__ . '/location.php')) { $base = require __DIR__ . '/location.php'; }
define('AWC_ROOT', $base);
function app_settings(): array {
    static $settings;
    if ($settings !== null) return $settings;
    $settings = json_decode(file_get_contents(AWC_ROOT . '/config/site.json'), true, 512, JSON_THROW_ON_ERROR);
    if (($settings['schemaVersion'] ?? null) !== 1 || ($settings['media']['format'] ?? '') !== 'webp') throw new RuntimeException('Invalid site configuration.');
    if (!in_array($settings['site']['timezone'] ?? '', DateTimeZone::listIdentifiers(), true)) throw new RuntimeException('Invalid site timezone.');
    foreach (['retrieval'=>['intervalMinutes','cooldownSeconds','connectTimeoutSeconds','requestTimeoutSeconds','maxDownloadBytes'],'media'=>['quality','maxPixels'],'ui'=>['desktopColumns'],'timeline'=>['previewWidth','thumbnailSourceWidth','cacheLimit','prefetchConcurrency','defaultSpeedMs'],'compare'=>['cacheLimit'],'map'=>['minZoom','maxZoom','clusterRadiusPixels','clusterMaxZoom','previewWidth']] as $section=>$fields) {
        foreach ($fields as $field) if (!is_int($settings[$section][$field] ?? null) || $settings[$section][$field] < 1) throw new RuntimeException('Invalid setting: ' . $section . '.' . $field);
    }
    if ($settings['media']['quality'] > 100 || !$settings['media']['widths'] || count(array_unique($settings['media']['widths'])) !== count($settings['media']['widths'])) throw new RuntimeException('Invalid image settings.');
    foreach ($settings['media']['widths'] as $width) if (!is_int($width) || $width < 100 || $width > 2000) throw new RuntimeException('Invalid image width.');
    if (!in_array($settings['timeline']['previewWidth'], $settings['media']['widths'], true) || !in_array($settings['timeline']['thumbnailSourceWidth'], $settings['media']['widths'], true) || !in_array($settings['map']['previewWidth'], $settings['media']['widths'], true)) throw new RuntimeException('Preview width is not configured.');
    if (!preg_match('~^https://tile\.openstreetmap\.org/\{z\}/\{x\}/\{y\}\.png$~D', $settings['map']['tileUrl'] ?? '')) throw new RuntimeException('Unapproved map tile source.');
    $ids = [];
    foreach ($settings['cameras'] ?? [] as $camera) {
        if (!preg_match('/^ab_[0-9]+$/D', $camera['id'] ?? '') || isset($ids[$camera['id']]) || !preg_match('~^https://511\.alberta\.ca/map/Cctv/[0-9]+$~D', $camera['url'] ?? '') || !is_numeric($camera['lat'] ?? null) || !is_numeric($camera['lng'] ?? null) || abs($camera['lat']) > 90 || abs($camera['lng']) > 180 || empty($camera['title']) || empty($camera['region'])) throw new RuntimeException('Invalid camera configuration.');
        $ids[$camera['id']] = true;
    }
    foreach ($settings['timeline']['speeds'] ?? [] as $speed) if (!is_int($speed['milliseconds'] ?? null) || $speed['milliseconds'] < 100 || empty($speed['label'])) throw new RuntimeException('Invalid playback speed.');
    if (!in_array($settings['timeline']['defaultSpeedMs'], array_column($settings['timeline']['speeds'] ?? [], 'milliseconds'), true)) throw new RuntimeException('Invalid default playback speed.');
    if (!$ids) throw new RuntimeException('At least one camera is required.');
    return $settings;
}
function public_settings(): array { $settings = app_settings(); unset($settings['cameras']); $settings['retrieval'] = ['intervalMinutes'=>$settings['retrieval']['intervalMinutes']]; return $settings; }
function cameras(): array { return app_settings()['cameras']; }
date_default_timezone_set(app_settings()['site']['timezone']);
$local = is_file(AWC_ROOT . '/config/local.json') ? json_decode(file_get_contents(AWC_ROOT . '/config/local.json'), true, 512, JSON_THROW_ON_ERROR) : [];
define('AWC_DATA', getenv('AWC_DATA_ROOT') ?: ($local['data_root'] ?? AWC_ROOT . '/runtime'));
if (!is_dir(AWC_DATA)) { mkdir(AWC_DATA, 0700, true); }
function camera(string $id): ?array { foreach (cameras() as $c) { if ($c['id'] === $id) return $c; } return null; }
function state(): array { $s = @file_get_contents(AWC_DATA . '/state.json'); return $s ? (json_decode($s, true) ?: []) : []; }
function json_response(array $value, int $status = 200): never { http_response_code($status); header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); exit; }
function valid_date(string $date): bool { $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date); return $d && $d->format('Y-m-d') === $date; }
function frames(string $id, string $date): array {
    if (!camera($id) || !valid_date($date)) return [];
    $files = glob(AWC_DATA . '/archive/' . $id . '/' . str_replace('-', '/', $date) . '/*.webp') ?: [];
    sort($files); $out = [];
    foreach ($files as $file) { $stamp = basename($file, '.webp'); $out[] = ['stamp' => $stamp, 'url' => 'image.php?camera=' . rawurlencode($id) . '&stamp=' . rawurlencode($stamp)]; }
    return $out;
}
function dates(string $id): array {
    if (!camera($id)) return [];
    $out = [];
    foreach (glob(AWC_DATA . '/archive/' . $id . '/*/*/*', GLOB_ONLYDIR) ?: [] as $dir) { $parts = array_slice(explode('/', $dir), -3); $out[] = implode('-', $parts); }
    rsort($out); return $out;
}
