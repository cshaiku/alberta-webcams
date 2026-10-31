<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
function check(bool $condition, string $label): void { if (!$condition) throw new RuntimeException($label); echo "PASS $label\n"; }
check(count(cameras()) === 18, 'Eighteen configured camera records');
check(valid_date('2024-02-29') && !valid_date('2025-02-29') && !valid_date('../../etc/passwd'), 'Strict date validation');
check(camera('../../etc/passwd') === null && frames('../../etc/passwd', '2026-10-31') === [], 'Camera traversal rejected');
check(frames(cameras()[0]['id'], '../../etc/passwd') === [], 'Archive date traversal rejected');
foreach (cameras() as $c) { check((bool)preg_match('~^https://511\.alberta\.ca/map/Cctv/[0-9]+$~D', $c['url']), 'Approved snapshot URL for ' . $c['id']); }
