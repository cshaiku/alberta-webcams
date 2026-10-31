<?php
declare(strict_types=1);

/** Resolve only catalogued, dated snapshots inside the private archive. */
function snapshot_path(string $id, string $stamp): ?string
{
    if (!camera($id) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})_\d{6}$/D', $stamp, $m)
        || !valid_date("$m[1]-$m[2]-$m[3]")) {
        return null;
    }
    $file = AWC_DATA . "/archive/$id/$m[1]/$m[2]/$m[3]/$stamp.jpg";
    return is_file($file) ? $file : null;
}

/** Generate bounded WebP derivatives once, preserving the original JPEG. */
function webp_variant(string $source, string $id, string $stamp, int $width): ?string
{
    if (!in_array($width, [400, 640, 840], true) || !function_exists('imagewebp')) return null;
    $target = AWC_DATA . "/variants/$id/$stamp-$width.webp";
    if (is_file($target)) return $target;
    $info = @getimagesize($source);
    if (!$info || $info[0] * $info[1] > 16000000) return null;
    $image = @imagecreatefromjpeg($source);
    if (!$image) return null;
    $w = min($width, $info[0]);
    $h = max(1, (int)round($info[1] * $w / $info[0]));
    $scaled = imagecreatetruecolor($w, $h);
    if (!$scaled || !imagecopyresampled($scaled, $image, 0, 0, 0, 0, $w, $h, $info[0], $info[1])) return null;
    $dir = dirname($target);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) return null;
    $temp = $target . '.' . bin2hex(random_bytes(6)) . '.tmp';
    $written = imagewebp($scaled, $temp, 84);
    if (!$written || !rename($temp, $target)) { @unlink($temp); return null; }
    return $target;
}
