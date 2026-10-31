<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require AWC_ROOT . '/app/presentation.php';
$id = is_string($_GET['camera'] ?? null) ? $_GET['camera'] : '';
$camera = camera($id);
if (!$camera) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'Camera not found.'; exit; }
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, follow');
?>
<!doctype html><html lang="en-CA" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="dark light"><meta name="robots" content="noindex,follow">
<title><?= e($camera['title']) ?> | Compare | <?= e(app_settings()['site']['name']) ?></title><meta name="description" content="Compare two saved moments from <?= e($camera['title']) ?> with an interactive image divider.">
<link rel="canonical" href="https://sltd.ca/awc/compare.php?camera=<?= e($id) ?>"><link rel="icon" type="image/svg+xml" href="/awc/assets/brand/awc.svg">
<link rel="stylesheet" href="/awc/assets/site.css?v=20261031-personal1"><link rel="stylesheet" href="/awc/assets/compare.css?v=20261031-personal1">
<script type="module" src="/awc/assets/compare.js?v=20261031-personal1"></script><script type="module" src="/awc/assets/accessibility/components/preferences-center/index.js?v=20261031-personal1"></script>
<script type="application/json" id="awc-settings"><?= json_encode(public_settings(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
</head><body id="app" class="compare-page" data-camera="<?= e($id) ?>" data-sdou-a11y-content data-default-theme="<?= e(app_settings()['ui']['defaultTheme']) ?>" data-default-font="sans">
<a class="skip" href="#compare-main">Skip to comparison</a>
<header class="header compare-header"><nav aria-label="Camera views"><a href="/awc/?view=overview"><?= icon('grid') ?><span>Network</span></a><a href="/awc/?view=archive&amp;camera=<?= e($id) ?>"><?= icon('archive') ?><span>Grid</span></a><a href="/awc/timeline.php?camera=<?= e($id) ?>"><?= icon('clock') ?><span>Timeline</span></a><a href="/awc/compare.php?camera=<?= e($id) ?>" aria-current="page"><?= icon('expand') ?><span>Compare</span></a></nav><h1 title="<?= e($camera['title']) ?>"><?= e($camera['title']) ?></h1><sdou-preferences-center target="#app" storage-key="<?= e(app_settings()['ui']['preferencesStorageKey']) ?>"></sdou-preferences-center></header>
<main id="compare-main" tabindex="-1">
<div class="compare-pickers"><label>Before <select id="compare-before" disabled><option>Loading…</option></select></label><button id="compare-swap" aria-label="Swap the two snapshots" disabled>⇄</button><label>After <select id="compare-after" disabled><option>Loading…</option></select></label><button id="compare-share" disabled>Copy link</button></div>
<section id="compare-stage" aria-label="Image comparison" aria-describedby="compare-help"><img id="compare-after-image" alt="" hidden><img id="compare-before-image" alt="" hidden><div id="compare-divider" hidden aria-hidden="true"><span>↔</span></div><p id="compare-empty">Loading saved moments…</p></section>
<div class="compare-controls"><label for="compare-split">Reveal before / after</label><input id="compare-split" type="range" min="0" max="100" value="50" disabled aria-label="Comparison divider" aria-valuetext="50 percent before, 50 percent after"><p id="compare-help">Drag across the image, or use the slider and arrow keys. Times are when images were retrieved.</p><p id="compare-status" role="status" aria-live="polite"></p><input id="compare-share-url" aria-label="Comparison link" readonly hidden></div>
<noscript>This comparison needs JavaScript. <a href="/awc/?view=archive&amp;camera=<?= e($id) ?>">Browse the camera grid.</a></noscript>
</main></body></html>
