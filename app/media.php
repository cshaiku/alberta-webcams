<?php
declare(strict_types=1);

/** Resolve only catalogued, dated snapshots inside the private archive. */
function snapshot_path(string $id, string $stamp): ?string
{
    if (!camera($id) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})_\d{6}$/D', $stamp, $m)
        || !valid_date("$m[1]-$m[2]-$m[3]")) {
        return null;
    }
    $file = AWC_DATA . "/archive/$id/$m[1]/$m[2]/$m[3]/$stamp.webp";
    return is_file($file) ? $file : null;
}

/** Generate bounded WebP derivatives once, from the full-size WebP capture. */
function webp_variant(string $source, string $id, string $stamp, int $width): ?string
{
    if (!in_array($width, app_settings()['media']['widths'], true) || !function_exists('imagewebp')) return null;
    $target = AWC_DATA . "/variants/$id/$stamp-$width.webp";
    $info = @getimagesize($source);
    if (!$info || $info[0] * $info[1] > app_settings()['media']['maxPixels']) return null;
    if ($width >= $info[0]) return $source;
    if (is_file($target)) return $target;
    $image = @imagecreatefromwebp($source);
    if (!$image) return null;
    $w = min($width, $info[0]);
    $h = max(1, (int)round($info[1] * $w / $info[0]));
    $scaled = imagecreatetruecolor($w, $h);
    if (!$scaled || !imagecopyresampled($scaled, $image, 0, 0, 0, 0, $w, $h, $info[0], $info[1])) return null;
    $dir = dirname($target);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) return null;
    $temp = $target . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $written = imagewebp($scaled, $temp, app_settings()['media']['quality']);
    if (!$written || !rename($temp, $target)) { @unlink($temp); return null; }
    return $target;
}

/** Prepare the default browser formats before a new capture becomes visible. */
function prepare_webp_variants(string $source, string $id, string $stamp): int
{
    $prepared = 0;
    foreach (app_settings()['media']['widths'] as $width) {
        if (webp_variant($source, $id, $stamp, $width)) $prepared++;
    }
    return $prepared;
}

/** Convert provider bytes directly into an atomically verified WebP capture. */
function store_webp_snapshot(string $bytes, string $target): bool
{
    if (!function_exists('imagewebp')) return false;
    $info = @getimagesizefromstring($bytes);
    if (!$info || ($info['mime'] ?? '') !== 'image/jpeg' || $info[0] * $info[1] > app_settings()['media']['maxPixels']) return false;
    $image = @imagecreatefromstring($bytes);
    if (!$image) return false;
    $temp = $target . '.' . bin2hex(random_bytes(6)) . '.tmp';
    if (!imagewebp($image, $temp, app_settings()['media']['quality'])) { @unlink($temp); return false; }
    $converted = @getimagesize($temp);
    $decoded = @imagecreatefromwebp($temp);
    if (!$decoded || !$converted || $converted[0] !== $info[0] || $converted[1] !== $info[1] || ($converted['mime'] ?? '') !== 'image/webp') { @unlink($temp); return false; }
    if (!rename($temp, $target)) { @unlink($temp); return false; }
    return true;
}
