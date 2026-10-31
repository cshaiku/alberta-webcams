<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require AWC_ROOT . '/app/presentation.php';
require AWC_ROOT . '/app/media.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/awc/' && (isset($_GET['view']) || $path === '/awc/index.php')) {
    header('Location: /awc/' . ($_GET ? '?' . http_build_query(array_intersect_key($_GET, array_flip(['view','camera','date','hour']))) : ''), true, 302);
    exit;
}
$view = in_array($_GET['view'] ?? '', ['archive','map'], true) ? $_GET['view'] : 'overview';
$catalog = cameras();
$snapshotState = state();
$available = count(array_filter($snapshotState['cameras'] ?? [], fn($s) => ($s['status'] ?? '') === 'ok'));
$canonical = 'https://sltd.ca/awc/';
$isCanonical = $view === 'overview' && !$_GET;
$title = 'Alberta Webcams | Highway Cameras & Snapshot Archive';
$description = 'Explore Alberta highway camera snapshots from Edmonton, Leduc, Red Deer, Calgary and beyond. Browse the camera grid and revisit saved images by date and hour.';
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('Cache-Control: no-store');
header('X-Robots-Tag: ' . ($isCanonical ? 'index, follow' : 'noindex, follow'));
$schema = ['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'WebSite', '@id' => $canonical . '#website', 'url' => $canonical, 'name' => 'Alberta Webcams', 'inLanguage' => 'en-CA', 'publisher' => ['@type' => 'Person', 'name' => 'Chris Simmons']],
    ['@type' => 'WebPage', '@id' => $canonical . '#webpage', 'url' => $canonical, 'name' => $title, 'description' => $description, 'inLanguage' => 'en-CA', 'isPartOf' => ['@id' => $canonical . '#website'], 'about' => ['@type' => 'Place', 'name' => 'Alberta, Canada']]
]];
?>
<!doctype html>
<html lang="en-CA" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#09151f">
    <meta name="robots" content="<?= $isCanonical ? 'index,follow,max-image-preview:large' : 'noindex,follow' ?>">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="https://sltd.ca/awc/">
    <meta property="og:site_name" content="Alberta Webcams">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Alberta Webcams | Alberta, in view.">
    <meta property="og:description" content="Highway camera snapshots. A clearer view of Alberta, one place at a time.">
    <meta property="og:url" content="https://sltd.ca/awc/">
    <meta property="og:locale" content="en_CA">
    <meta property="og:image" content="https://sltd.ca/awc/assets/brand/social-card.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Alberta Webcams. Alberta, in view. Highway cameras and snapshot archive.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Alberta Webcams | Alberta, in view.">
    <meta name="twitter:description" content="Explore Alberta highway camera snapshots and the image archive.">
    <meta name="twitter:image" content="https://sltd.ca/awc/assets/brand/social-card.png">
    <link rel="icon" type="image/svg+xml" href="/awc/assets/brand/awc.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/awc/assets/brand/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/awc/assets/brand/apple-touch-icon.png">
    <link rel="sitemap" type="application/xml" href="/awc/sitemap.xml">
    <link rel="stylesheet" href="/awc/assets/site.css?v=20261031-timeline1">
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
    <script type="module" src="/awc/assets/app.js?v=20261031-timeline1"></script>
    <script type="module" src="/awc/assets/accessibility/components/preferences-center/index.js?v=20261031-preferences"></script>
</head>
<body class="rail-hidden" id="app" data-sdou-a11y-content data-default-theme="dark" data-default-font="sans">
<a class="skip" href="#main">Skip to camera images</a>
<header class="header">
    <a class="brand" href="/awc/"><img src="/awc/assets/brand/awc.svg" width="38" height="38" alt=""><span>Alberta Webcams<small>A different perspective.</small></span></a>
    <button id="menu-toggle" class="icon-button" aria-label="Open controls" aria-controls="header-controls" aria-expanded="false"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <div id="header-controls"><button id="rail-toggle" class="icon-button" aria-label="Toggle camera sidebar" aria-controls="sidebar" aria-expanded="true" title="Toggle camera sidebar"><?= icon('rail') ?></button>
    <nav class="views" aria-label="Camera views">
        <a href="/awc/" data-view="overview"<?= $view === 'overview' ? ' aria-current="page"' : '' ?>><?= icon('grid') ?><span>Grid view</span></a>
        <a href="/awc/?view=archive" data-view="archive"<?= $view === 'archive' ? ' aria-current="page"' : '' ?>><?= icon('archive') ?><span>Archive</span></a>
        <a href="/awc/?view=map" data-view="map"<?= $view === 'map' ? ' aria-current="page"' : '' ?>><?= icon('map') ?><span>Map</span></a>
    </nav>

    <div class="collection-toolbar" id="grid-toolbar"><div class="filters" role="group" aria-label="Filter camera collection"><button data-filter="all" aria-pressed="true">All cameras</button><button data-filter="edmonton" aria-pressed="false">Edmonton</button><button data-filter="highways" aria-pressed="false">Highways</button><button data-filter="unavailable" aria-pressed="false">Unavailable</button></div><div class="toolbar-tools"><label class="search"><?= icon('search') ?><span class="sr-only">Search cameras</span><input id="search" type="search" placeholder="Find a camera" autocomplete="off"></label></div></div>
    </div>
    <sdou-preferences-center target="#app" storage-key="awc.reading.v1"></sdou-preferences-center>
</header>
<div class="workspace">
    <aside id="sidebar" class="sidebar" aria-label="Camera collection">
        <div class="rail-head"><span class="eyebrow">EXPLORE ALBERTA</span><button class="icon-button mobile-close" id="rail-close" aria-label="Close camera sidebar"><?= icon('close') ?></button></div>
        <button id="all-cameras" class="all selected"><?= icon('grid') ?><span>All cameras</span><span id="camera-count" class="count"><?= count($catalog) ?></span></button>
        <nav id="archive-tree" aria-label="Camera archive timeline" hidden></nav><div id="camera-list"><?php foreach (['Edmonton','Highways'] as $group): ?><h2 class="group-title"><?= e($group) ?></h2><?php foreach ($catalog as $c): if (($c['region'][0] === 'E' && str_starts_with($c['region'],'Edmonton') ? 'Edmonton' : 'Highways') !== $group) continue; ?><a class="camera-link" href="/awc/?view=archive&amp;camera=<?= e($c['id']) ?>" data-camera="<?= e($c['id']) ?>"><span><?= e($c['region']) ?></span><small><?= e($c['title']) ?></small></a><?php endforeach; endforeach; ?></div>
        <div class="rail-foot"><span class="source-emblem"><?= icon('camera') ?></span><strong>A province in perspective.</strong><p>Camera images provided by 511 Alberta.</p><a href="https://511.alberta.ca/" target="_blank" rel="noopener">Visit the source <?= icon('external') ?></a></div>
    </aside>
    <button id="rail-backdrop" class="rail-backdrop" aria-label="Close camera sidebar" hidden></button>
    <main id="main" tabindex="-1">
        <section class="collection-intro" aria-labelledby="page-title"><div class="page-heading"><p class="eyebrow" id="eyebrow">THE CAMERA COLLECTION</p><h1 id="page-title">Alberta, in view.</h1><p class="intro-copy" id="intro-copy">A little perspective before you go. Explore highway snapshots across Alberta, or look back through the archive.</p></div><dl class="collection-stats"><div><dt>Cameras</dt><dd id="stat-cameras"><?= count($catalog) ?></dd></div><div><dt>Available</dt><dd id="stat-available"><?= $available ?></dd></div><div><dt>Time zone</dt><dd class="stat-zone">Mountain<small>Alberta, Canada</small></dd></div></dl></section>

        <form id="archive-controls" class="control-strip" hidden><strong id="archive-heading"></strong><a id="timeline-link" href="/awc/">Timeline view</a><button id="archive-all" type="button">All images</button><div class="date-controls"><button id="prev-day" type="button" aria-label="Previous day">‹</button><label>Date <input id="date" type="date" value="<?= date('Y-m-d') ?>"></label><button id="next-day" type="button" aria-label="Next day">›</button></div><label>Hour <select id="hour"><option value="all">All hours</option></select></label><button id="today" type="button">Today</button><span class="time-zone"><?= icon('clock') ?> Mountain time</span></form>
        <div class="status-row"><p id="status" role="status" aria-live="polite"><?= isset($snapshotState['lastResult']) ? 'Latest collection · ' . $available . ' cameras available' : 'Images are collected automatically every 15 minutes.' ?></p><span id="capture-note"><?= icon('info') ?> Times below record retrieval, not camera capture.</span></div>
        <noscript><p class="notice">The camera grid is available below. Enable JavaScript to use filters and open the archive controls.</p></noscript>
        <section id="gallery" class="gallery" aria-label="Camera snapshots"><?php foreach ($catalog as $index => $c) echo card_html($c, $snapshotState['cameras'][$c['id']] ?? [], $index); ?></section>
        <section id="map-panel" aria-label="Camera locations" hidden></section>
        <section class="collection-notes" aria-label="About this collection"><div><?= icon('camera') ?><h2>Snapshots, at your pace.</h2><p>Retrieve a fresh collection when you need it. Saved snapshots remain in the archive, ready to explore by camera, date and hour.</p></div><div><?= icon('clock') ?><h2>A moment, with context.</h2><p>Each image keeps the source’s original timestamp. The time beneath it tells you when it was retrieved. Source images may be older.</p></div><div><?= icon('map') ?><h2>Keep the wider view.</h2><p>Sources can be delayed or unavailable. For current travel advisories and road conditions, check <a href="https://511.alberta.ca/" target="_blank" rel="noopener">511 Alberta</a>.</p></div></section>
        <footer class="site-footer"><a class="footer-brand" href="/awc/">Alberta Webcams</a><span>Made for a clearer perspective.</span><div><a href="https://sltd.ca/">Starlit Digital</a><a href="https://511.alberta.ca/" target="_blank" rel="noopener">511 Alberta <?= icon('external') ?></a></div></footer>
    </main>
</div>
<dialog id="viewer" aria-labelledby="viewer-title" aria-describedby="viewer-time"><div class="viewer-toolbar"><div><p class="eyebrow">A CLOSER LOOK</p><h2 id="viewer-title"></h2></div><button id="viewer-close" class="icon-button" aria-label="Close image"><?= icon('close') ?></button></div><img id="viewer-image" width="840" height="630" alt=""><div class="viewer-bottom"><button id="viewer-prev" aria-label="Previous image">←</button><p id="viewer-time"></p><a id="viewer-original" href="/awc/" target="_blank" rel="noopener">Original <?= icon('external') ?></a><button id="viewer-next" aria-label="Next image">→</button></div></dialog>

<template id="ui-icons"><?php foreach (['camera','clock','arrow','expand','close','info','pin'] as $name): ?><span data-icon="<?= e($name) ?>"><?= icon($name) ?></span><?php endforeach; ?></template>
</body>
</html>
