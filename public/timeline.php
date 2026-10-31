<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require AWC_ROOT . '/app/presentation.php';
$id = is_string($_GET['camera'] ?? null) ? $_GET['camera'] : '';
$camera = camera($id);
if (!$camera) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Camera not found.'; exit; }
$catalog = cameras();
$position = array_search($id, array_column($catalog, 'id'), true);
$previousCamera = $catalog[($position + count($catalog) - 1) % count($catalog)];
$nextCamera = $catalog[($position + 1) % count($catalog)];
$days = dates($id);
$requested = is_string($_GET['date'] ?? null) ? $_GET['date'] : '';
$day = valid_date($requested) ? $requested : ($days[0] ?? date('Y-m-d'));
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, follow');
?>
<!doctype html>
<html lang="en-CA" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="dark light">
<title><?= e($camera['title']) ?> | Timeline | <?= e(app_settings()['site']['name']) ?></title>
<meta name="description" content="Explore saved snapshots of <?= e($camera['title']) ?> with a thumbnail filmstrip and time lapse playback."><meta name="robots" content="noindex,follow">
<link rel="canonical" href="https://sltd.ca/awc/timeline.php?camera=<?= e($id) ?>">
<link rel="icon" type="image/svg+xml" href="/awc/assets/brand/awc.svg">
<link rel="stylesheet" href="/awc/assets/site.css?v=20261031-credit1"><link rel="stylesheet" href="/awc/assets/timeline.css?v=20261031-credit1">
<script type="module" src="/awc/assets/timeline.js?v=20261031-personal1"></script>
<script type="module" src="/awc/assets/accessibility/components/preferences-center/index.js?v=20261031-pills1"></script>
<script type="application/json" id="awc-settings"><?= json_encode(public_settings(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body id="app" class="timeline-page" data-camera="<?= e($id) ?>" data-day="<?= e($day) ?>" data-sdou-a11y-content data-default-theme="<?= e(app_settings()['ui']['defaultTheme']) ?>" data-default-font="sans">
<a class="skip" href="#timeline-main">Skip to timeline</a>
<header class="header timeline-header"><div class="timeline-context"><nav class="timeline-nav" aria-label="Camera views"><a href="/awc/?view=overview" class="timeline-home" aria-label="Network"><?= icon('grid') ?><span>Network</span></a><a href="/awc/?view=archive&amp;camera=<?= e($id) ?>" class="timeline-archive" aria-label="Grid"><?= icon('archive') ?><span>Grid</span></a><a href="/awc/timeline.php?camera=<?= e($id) ?>" class="timeline-current" aria-current="page"><?= icon('clock') ?><span>Timeline</span></a><a class="comparison-link" href="/awc/compare.php?camera=<?= e($id) ?>"><?= icon('expand') ?><span>Compare</span></a></nav><div class="timeline-heading"><div class="camera-identity" title="<?= e($camera['title']) ?>"><span role="img" aria-label="<?= e($camera['title']) ?>"><?= icon('camera') ?></span><h1><?= e($camera['title']) ?></h1></div><label class="day-picker">Day <select id="timeline-day"<?= !$days ? ' disabled' : '' ?>><?php if (!$days): ?><option value="<?= e($day) ?>">No saved days</option><?php endif; ?><?php foreach ($days as $date): ?><option value="<?= e($date) ?>"<?= $date === $day ? ' selected' : '' ?>><?= e($date) ?></option><?php endforeach; ?><?php if ($days && !in_array($day,$days,true)): ?><option value="<?= e($day) ?>" selected><?= e($day) ?></option><?php endif; ?></select></label></div></div><div class="playback-controls"><div class="transport"><button id="timeline-prev" aria-label="Previous snapshot" disabled>←</button><button id="timeline-play" aria-label="Play snapshots" disabled>Play</button><button id="timeline-next" aria-label="Next snapshot" disabled>→</button></div><label class="speed-picker">Speed <select id="timeline-speed"><?php foreach (app_settings()['timeline']['speeds'] as $speed): ?><option value="<?= (int)$speed['milliseconds'] ?>"<?= $speed['milliseconds'] === app_settings()['timeline']['defaultSpeedMs'] ? ' selected' : '' ?>><?= e($speed['label']) ?></option><?php endforeach; ?></select></label></div><sdou-preferences-center target="#app" storage-key="<?= e(app_settings()['ui']['preferencesStorageKey']) ?>"></sdou-preferences-center></header>
<main id="timeline-main" tabindex="-1">

<section class="timeline-stage" aria-label="Selected snapshot"><a id="camera-previous" class="camera-edge camera-edge-previous" href="/awc/timeline.php?camera=<?= e($previousCamera['id']) ?>&amp;date=<?= e($day) ?>" aria-label="Previous camera: <?= e($previousCamera['title']) ?>" title="Previous camera: <?= e($previousCamera['title']) ?>"><span aria-hidden="true">‹</span></a><a id="camera-next" class="camera-edge camera-edge-next" href="/awc/timeline.php?camera=<?= e($nextCamera['id']) ?>&amp;date=<?= e($day) ?>" aria-label="Next camera: <?= e($nextCamera['title']) ?>" title="Next camera: <?= e($nextCamera['title']) ?>"><span aria-hidden="true">›</span></a><img id="timeline-image" alt="" hidden><div id="timeline-empty">Loading snapshots…</div><a id="timeline-original" target="_blank" rel="noopener" hidden>Open original <?= icon('external') ?></a></section>
<section class="timeline-console" aria-label="Timeline controls">

<div id="timeline-filmstrip" class="timeline-filmstrip" aria-label="Snapshot thumbnails"><div class="timeline-frames"></div></div>
<p id="timeline-status" class="sr-only" role="status" aria-live="polite"></p>
</section>
<noscript>This timeline needs JavaScript. <a href="/awc/?view=archive&amp;camera=<?= e($id) ?>">Browse the archive grid.</a></noscript>
</main>
<footer class="product-credit">Built with love by <a href="https://sltd.ca/">Starlit Digital</a>.</footer>
</body></html>
