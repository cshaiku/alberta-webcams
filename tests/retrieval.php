<?php
declare(strict_types=1);
$root = sys_get_temp_dir() . '/awc-retrieval-test-' . bin2hex(random_bytes(8));
putenv('AWC_DATA_ROOT=' . $root);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/retrieval.php';
try {
    file_put_contents($root . '/state.json', json_encode(['attemptedAt' => time()]));
    try { collect_snapshots('scheduled'); throw new RuntimeException('Cooldown was not enforced.'); }
    catch (RetrievalUnavailable $e) { if ($e->getCode() !== 429) throw $e; echo "PASS scheduled collector respects manual cooldown\n"; }
    $lock = fopen($root . '/retrieve.lock', 'c+');
    if (!flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Test lock unavailable');
    try { collect_snapshots(); throw new RuntimeException('Concurrent collector was allowed.'); }
    catch (RetrievalUnavailable $e) { if ($e->getCode() !== 409) throw $e; echo "PASS manual collector respects scheduled lock\n"; }
    flock($lock, LOCK_UN); fclose($lock);
} finally {
    foreach (glob($root . '/*') ?: [] as $file) unlink($file);
    rmdir($root);
}
