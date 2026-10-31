<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require AWC_ROOT . '/app/retrieval.php';

if (in_array('--check', $argv, true)) {
    echo json_encode(['mode' => 'check', 'cameras' => count(cameras()), 'timezone' => date_default_timezone_get(), 'runtimeWritable' => is_writable(AWC_DATA), 'curl' => extension_loaded('curl')], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit;
}
try {
    $result = collect_snapshots('scheduled');
    echo json_encode(['at' => $result['state']['lastResult']['at'], 'saved' => $result['saved'], 'unavailable' => $result['failed'], 'trigger' => 'scheduled'], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($result['saved'] > 0 ? 0 : 1);
} catch (RetrievalUnavailable $error) {
    echo json_encode(['at' => date(DateTimeInterface::ATOM), 'skipped' => true, 'reason' => $error->getMessage(), 'retryAfter' => $error->retryAfter], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(in_array($error->getCode(), [409,429], true) ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, 'AWC retrieval failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
