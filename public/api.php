<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
header('X-Content-Type-Options: nosniff');
session_name('awc_session'); session_set_cookie_params(['path' => '/awc/', 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Strict']); session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
$action = $_GET['action'] ?? 'catalog';
if ($action === 'catalog') { json_response(['cameras' => array_map(function($c) { unset($c['url']); return $c; }, cameras()), 'state' => state(), 'csrf' => $_SESSION['csrf'], 'timezone' => 'America/Edmonton']); }
if ($action === 'archive') {
    $id = (string)($_GET['camera'] ?? ''); $date = (string)($_GET['date'] ?? date('Y-m-d'));
    if (!camera($id) || !valid_date($date)) json_response(['error' => 'Choose a valid camera and date.'], 400);
    json_response(['frames' => frames($id, $date), 'dates' => dates($id)]);
}
if ($action !== 'retrieve') json_response(['error' => 'Unknown action.'], 404);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Use the Retrieve images button.'], 405);
if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) json_response(['error' => 'Reload this page and try again.'], 403);
session_write_close();
$lock = fopen(AWC_DATA . '/retrieve.lock', 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) json_response(['error' => 'A retrieval is already running. Please wait.'], 409);
$state = state();
$wait = 60 - (time() - ($state['attemptedAt'] ?? 0));
if ($wait > 0) json_response(['error' => "Please wait $wait seconds before retrieving again.", 'retryAfter' => $wait], 429);
if (!extension_loaded('curl')) json_response(['error' => 'Image retrieval needs the PHP cURL extension.'], 503);
$state['attemptedAt'] = time();
file_put_contents(AWC_DATA . '/state.json', json_encode($state), LOCK_EX);
$multi = curl_multi_init(); $jobs = []; $now = new DateTimeImmutable(); $stamp = $now->format('Y-m-d_His');
foreach (cameras() as $c) {
    // Only operator controlled 511 snapshot URLs are accepted. No redirects or arbitrary user URLs.
    if (!preg_match('~^https://511\.alberta\.ca/map/Cctv/[0-9]+$~D', $c['url'])) continue;
    $h = curl_init($c['url']); $bytes = '';
    $jobs[$c['id']] = ['handle' => $h, 'data' => ''];
    $id = $c['id'];
    curl_setopt_array($h, [CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 18, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_USERAGENT => 'AlbertaWebcams/1.0 (manual snapshot viewer)', CURLOPT_WRITEFUNCTION => function($h, $chunk) use (&$jobs, $id) { if (strlen($jobs[$id]['data']) + strlen($chunk) > 5 * 1024 * 1024) return 0; $jobs[$id]['data'] .= $chunk; return strlen($chunk); }]);
    curl_multi_add_handle($multi, $h);
}
do { $result = curl_multi_exec($multi, $running); if ($running) curl_multi_select($multi, 1); } while ($running && $result === CURLM_OK);
$ok = 0; $failed = 0;
foreach ($jobs as $id => $job) {
    $h = $job['handle']; $image = @getimagesizefromstring($job['data']);
    if (curl_getinfo($h, CURLINFO_HTTP_CODE) === 200 && $image && ($image['mime'] ?? '') === 'image/jpeg' && $image[0] <= 10000 && $image[1] <= 10000) {
        $dir = AWC_DATA . '/archive/' . $id . '/' . $now->format('Y/m/d');
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        $path = $dir . '/' . $stamp . '.jpg';
        $written = file_put_contents($path . '.tmp', $job['data'], LOCK_EX);
        if ($written !== false && rename($path . '.tmp', $path)) {
            $state['cameras'][$id] = ['stamp' => $stamp, 'retrievedAt' => $now->format(DateTimeInterface::ATOM), 'status' => 'ok']; $ok++;
        } else { $state['cameras'][$id]['status'] = 'unavailable'; $failed++; }
    } else { $state['cameras'][$id]['status'] = 'unavailable'; $failed++; }
    curl_multi_remove_handle($multi, $h);
}
curl_multi_close($multi);
$state['lastResult'] = ['saved' => $ok, 'failed' => $failed, 'at' => $now->format(DateTimeInterface::ATOM)];
file_put_contents(AWC_DATA . '/state.json.tmp', json_encode($state, JSON_PRETTY_PRINT), LOCK_EX); rename(AWC_DATA . '/state.json.tmp', AWC_DATA . '/state.json');
flock($lock, LOCK_UN); fclose($lock);
json_response(['saved' => $ok, 'failed' => $failed, 'state' => $state]);
