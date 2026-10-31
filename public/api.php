<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require AWC_ROOT . '/app/media.php';
header('X-Content-Type-Options: nosniff');
session_name('awc_session'); session_set_cookie_params(['path' => '/awc/', 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Strict']); session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
$action = $_GET['action'] ?? 'catalog';
if ($action === 'catalog') { json_response(['cameras' => array_map(function($c) { unset($c['url']);
    $saved = state()['cameras'][$c['id']]['stamp'] ?? '';
    $path = snapshot_path($c['id'], $saved);
    $dimensions = $path ? @getimagesize($path) : false;
    $c['width'] = $dimensions[0] ?? 840;
    $c['height'] = $dimensions[1] ?? 630;
    return $c; }, cameras()), 'state' => state(), 'csrf' => $_SESSION['csrf'], 'timezone' => 'America/Edmonton']); }
if ($action === 'archive') {
    $id = (string)($_GET['camera'] ?? ''); $date = (string)($_GET['date'] ?? date('Y-m-d'));
    if (!camera($id) || ($date !== 'all' && !valid_date($date))) json_response(['error' => 'Choose a valid camera and date.'], 400);
        $availableDates = dates($id);
    $result = $date === 'all' ? array_merge([], ...array_map(fn($day) => frames($id, $day), $availableDates)) : frames($id, $date);
    usort($result, fn($a, $b) => strcmp($b['stamp'], $a['stamp']));
    json_response(['frames' => $result, 'dates' => $availableDates]);
}
if ($action !== 'retrieve') json_response(['error' => 'Unknown action.'], 404);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Use the Retrieve images button.'], 405);
if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) json_response(['error' => 'Reload this page and try again.'], 403);
session_write_close();
require AWC_ROOT . '/app/retrieval.php';
try {
    json_response(collect_snapshots());
} catch (RetrievalUnavailable $error) {
    $data = ['error' => $error->getMessage()];
    if ($error->retryAfter) $data['retryAfter'] = $error->retryAfter;
    json_response($data, $error->getCode());
}
