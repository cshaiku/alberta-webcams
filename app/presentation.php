<?php
declare(strict_types=1);

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function icon(string $name): string
{
    $paths = [
        'rail' => '<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16m-3-9h0m0 4h0"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'archive' => '<rect x="3" y="4" width="18" height="5" rx="1.5"/><path d="M5 9v11h14V9m-10 4h6"/>',
        'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Zm6-3v15m6-12v15"/>',
        'refresh' => '<path d="M20 7v5h-5M4 17v-5h5m-4.2-3A7.5 7.5 0 0 1 18 5l2 2M4 17l2 2a7.5 7.5 0 0 0 13.2-4"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'external' => '<path d="M14 3h7v7m0-7L10 14m0-10H4v16h16v-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'expand' => '<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/>',
        'camera' => '<path d="m8 5 2-2h4l2 2h4v15H4V5Z"/><circle cx="12" cy="12" r="4"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'pin' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['camera']) . '</svg>';
}
function image_url(string $id, string $stamp): string
{
    return '/awc/image.php?' . http_build_query(['camera' => $id, 'stamp' => $stamp, 'format' => 'webp']);
}
function picture_html(string $id, string $stamp, string $title, bool $priority = false): string
{
    $src = image_url($id, $stamp);
    $source = snapshot_path($id, $stamp);
    $dimensions = $source ? @getimagesize($source) : false;
    $width = $dimensions[0] ?? 840;
    $height = $dimensions[1] ?? 630;
    $widths = array_values(array_filter(app_settings()['media']['widths'], fn($w) => $w <= $width));
    if (!$widths) $widths = [app_settings()['media']['widths'][0]];
    $sizes = '(max-width: 540px) 100vw, (max-width: 920px) 50vw, (min-width: 1600px) 25vw, 33.333vw';
    $set = implode(', ', array_map(fn($w) => e($src . '&format=webp&w=' . $w) . " {$w}w", $widths));
    return '<picture><source type="image/webp" srcset="' . $set . '" sizes="' . $sizes . '"><img src="' . e($src) . '" width="' . $width . '" height="' . $height . '" alt="' . e($title . ' camera snapshot') . '" loading="' . ($priority ? 'eager' : 'lazy') . '" decoding="async"' . ($priority ? ' fetchpriority="high"' : '') . ' sizes="' . $sizes . '"></picture>';
}
function card_html(array $c, array $status, int $index): string
{
    $stamp = $status['stamp'] ?? '';
    $available = ($status['status'] ?? '') === 'ok';
    $time = $stamp ? substr($stamp,11,2) . ':' . substr($stamp,13,2) . ':' . substr($stamp,15,2) . ' MT' : 'Awaiting snapshot';
    $date = $stamp ? substr($stamp,0,10) : '';
    $datetime = $stamp ? DateTimeImmutable::createFromFormat('!Y-m-d_His', $stamp)?->format(DateTimeInterface::ATOM) : '';
    $visual = $stamp
        ? '<a class="image-button" href="' . e('/awc/?view=archive&camera=' . rawurlencode($c['id'])) . '" data-camera="' . e($c['id']) . '" data-index="' . $index . '" aria-label="View archive for ' . e($c['title']) . '">' . picture_html($c['id'], $stamp, $c['title'], $index === 0) . '<span class="image-expand">' . icon('expand') . '</span></a>'
        : '<div class="unavailable">' . icon('camera') . '<strong>Camera unavailable</strong><p>The source has no image right now.</p></div>';
    return '<article class="camera-card"><figure>' . $visual . '<figcaption class="card-caption"><div class="card-topline"><span class="region">' . e($c['region']) . '</span><span class="feed-state"><i class="feed-dot' . ($available ? '' : ' offline') . '"></i>' . ($available ? 'Snapshot' : ($stamp ? 'Previous image' : 'Unavailable')) . '</span></div><h2>' . e($c['title']) . '</h2><div class="caption-meta"><time' . ($datetime ? ' datetime="' . e($datetime) . '"' : '') . '>' . icon('clock') . '<span>' . e($time) . '<small>' . e($date) . '</small></span></time><a class="archive-link" href="/awc/?view=archive&amp;camera=' . e($c['id']) . '" data-camera="' . e($c['id']) . '">Archive ' . icon('arrow') . '</a></div></figcaption></figure></article>';
}
