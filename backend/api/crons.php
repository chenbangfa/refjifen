<?php
// backend/api/crons.php
// Recommendation: Run via CLI: php backend/api/crons.php
// Or protect via IP check if running via HTTP.

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Starting Daily Release Cron...\n";

// Configuration (Table 2.3)
function getPerformanceRelease($min_perf)
{
    if ($min_perf >= 20000000)
        return 24000;
    // ... Fill in gaps based on pattern or demand usually ...
    if ($min_perf >= 50000)
        return 300;
    if ($min_perf >= 20000)
        return 150;
    if ($min_perf >= 8000)
        return 80;
    if ($min_perf >= 4000)
        return 40;
    return 0;
}

$today = date('Y-m-d');

// 1. Get All Users who have Traffic Points > 0
$sql = "SELECT u.id, u.level, a.traffic_points, p.left_total, p.right_total 
        FROM users u 
        JOIN assets a ON u.id = a.user_id 
        JOIN performance p ON u.id = p.user_id 
        WHERE a.traffic_points > 0";
$stmt = $db->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll();

foreach ($users as $user) {
    // Check if already ran today
    $check = $db->prepare("SELECT id FROM daily_checkin_logs WHERE user_id = ? AND checkin_date = ?");
    $check->execute([$user['id'], $today]);
    if ($check->rowCount() > 0) {
        echo "User {$user['id']} already processed.\n";
        continue;
    }

    // 2. Base Release
    $base_release = 0;
    if ($user['level'] == 1)
        $base_release = 8;
    if ($user['level'] == 2)
        $base_release = 80;

    // 3. Performance Release
    $min_perf = min($user['left_total'], $user['right_total']);
    $perf_release = getPerformanceRelease($min_perf);

    $total_theoretical = $base_release + $perf_release;

    // 4. Cap by Traffic Points
    $actual_release = min($total_theoretical, $user['traffic_points']);

    if ($actual_release <= 0)
        continue;

    // 5. Transaction
    $db->beginTransaction();
    try {
        // Distribute
        $to_balance = $actual_release * 0.70;
        $to_vouchers = $actual_release * 0.30;

        // Update Assets
        $upd = $db->prepare("UPDATE assets SET 
            traffic_points = traffic_points - ?,
            balance = balance + ?,
            vouchers = vouchers + ?
            WHERE user_id = ?");
        $upd->execute([$actual_release, $to_balance, $to_vouchers, $user['id']]);

        // Log Checkin
        $log = $db->prepare("INSERT INTO daily_checkin_logs (user_id, checkin_date, release_amount) VALUES (?, ?, ?)");
        $log->execute([$user['id'], $today, $actual_release]);

        // Log Finance
        $fin = $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'mixed', ?, 0, 0, ?)");
        $fin->execute([$user['id'], $actual_release, "Daily Release: Base=$base_release, Perf=$perf_release"]);

        $db->commit();
        echo "User {$user['id']} Released: $actual_release\n";

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error User {$user['id']}: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
?>