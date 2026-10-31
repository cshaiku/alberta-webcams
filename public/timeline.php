<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require AWC_ROOT . '/app/presentation.php';
$id = is_string($_GET['camera'] ?? null) ? $_GET['camera'] : '';
$camera = camera($id);
if (!$camera) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Camera not found.'; exit; }
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
<title><?= e($camera['title']) ?> | Timeline | Alberta Webcams</title>
<meta name="description" content="Explore saved snapshots of <?= e($camera['title']) ?> with a thumbnail filmstrip and time lapse playback."><meta name="robots" content="noindex,follow">
<link rel="canonical" href="https://sltd.ca/awc/timeline.php?camera=<?= e($id) ?>">
<link rel="icon" type="image/svg+xml" href="/awc/assets/brand/awc.svg">
<link rel="stylesheet" href="/awc/assets/site.css?v=20261031-archive1"><link rel="stylesheet" href="/awc/assets/timeline.css?v=20261031-timeline1">
<script type="module" src="/awc/assets/timeline.js?v=20261031-timeline1"></script>
<script type="module" src="/awc/assets/accessibility/components/preferences-center/index.js?v=20261031-preferences"></script>
</head>
<body id="app" class="timeline-page" data-camera="<?= e($id) ?>" data-day="<?= e($day) ?>" data-sdou-a11y-content data-default-theme="dark" data-default-font="sans">
<a class="skip" href="#timeline-main">Skip to timeline</a>
<header class="header timeline-header"><a href="/awc/" class="timeline-home" aria-label="All cameras"><?= icon('grid') ?><span>All cameras</span></a><a href="/awc/?view=archive&amp;camera=<?= e($id) ?>" class="timeline-archive"><?= icon('archive') ?><span>Archive grid</span></a><sdou-preferences-center target="#app" storage-key="awc.reading.v1"></sdou-preferences-center></header>
<main id="timeline-main" tabindex="-1">
<section class="timeline-heading"><div><h1><?= e($camera['title']) ?></h1><p><?= e($camera['region']) ?></p></div><label class="day-picker">Day <select id="timeline-day"<?= !$days ? ' disabled' : '' ?>><?php if (!$days): ?><option value="<?= e($day) ?>">No saved days</option><?php endif; ?><?php foreach ($days as $date): ?><option value="<?= e($date) ?>"<?= $date === $day ? ' selected' : '' ?>><?= e($date) ?></option><?php endforeach; ?><?php if ($days && !in_array($day,$days,true)): ?><option value="<?= e($day) ?>" selected><?= e($day) ?></option><?php endif; ?></select></label></section>
<section class="timeline-stage" aria-label="Selected snapshot"><img id="timeline-image" alt="" hidden><div id="timeline-empty">Loading snapshots…</div><a id="timeline-original" target="_blank" rel="noopener" hidden>Open original <?= icon('external') ?></a></section>
<section class="timeline-console" aria-label="Timeline controls">
<div class="playback-controls"><button id="timeline-prev" aria-label="Previous snapshot" disabled>←</button><button id="timeline-play" aria-label="Play snapshots" disabled>Play</button><button id="timeline-next" aria-label="Next snapshot" disabled>→</button><time id="timeline-time">No snapshot selected</time><label class="speed-picker">Speed <select id="timeline-speed"><option value="1500">Slow</option><option value="750" selected>Normal</option><option value="300">Fast</option></select></label><span id="timeline-count"></span></div>
<label class="scrubber-label" for="timeline-scrubber">Scrub snapshots <span>Mountain time</span></label><input id="timeline-scrubber" type="range" min="0" max="0" value="0" step="1" disabled aria-label="Snapshot timeline">
<div class="timeline-extents"><span id="timeline-start"></span><span id="timeline-end"></span></div>
<div id="timeline-filmstrip" class="timeline-filmstrip" aria-label="Snapshot thumbnails"></div>
<p id="timeline-status" role="status" aria-live="polite">Images record retrieval time. Camera capture times may differ.</p>
</section>
<noscript>This timeline needs JavaScript. <a href="/awc/?view=archive&amp;camera=<?= e($id) ?>">Browse the archive grid.</a></noscript>
</main>
</body></html>
