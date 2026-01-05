<?php
// backend/api/user_actions.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwtHandler = new JWTHandler();

// Middleware: Verify Token
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
$user_data = $jwtHandler->validate_jwt($jwt);

if (!$user_data) {
    echo json_encode(["message" => "Unauthorized", "code" => 401]);
    exit;
}
$user_id = $user_data['data']['id'];

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper from crons (duplicated for standalone scope)
function getPerformanceRelease($min_perf)
{
    if ($min_perf >= 20000000)
        return 24000;
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

if ($action == 'checkin') {
    $today = date('Y-m-d');

    // 1. Check Previous Checkin
    $check = $db->prepare("SELECT id FROM daily_checkin_logs WHERE user_id = ? AND checkin_date = ?");
    $check->execute([$user_id, $today]);
    if ($check->rowCount() > 0) {
        echo json_encode(["message" => "Already checked in today", "code" => 400]);
        exit;
    }

    // 2. Refresh User Data
    $sql = "SELECT u.id, u.level, a.traffic_points, p.left_total, p.right_total 
            FROM users u 
            JOIN assets a ON u.id = a.user_id 
            JOIN performance p ON u.id = p.user_id 
            WHERE u.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["message" => "User not found", "code" => 404]);
        exit;
    }

    if ($user['traffic_points'] <= 0) {
        echo json_encode(["message" => "No Traffic Points to release", "code" => 400]);
        exit;
    }

    // 3. Logic
    $base_release = ($user['level'] == 2) ? 80 : (($user['level'] == 1) ? 8 : 0);
    $min_perf = min($user['left_total'], $user['right_total']);
    $perf_release = getPerformanceRelease($min_perf);
    $total_theoretical = $base_release + $perf_release;

    $actual_release = min($total_theoretical, $user['traffic_points']);

    if ($actual_release <= 0) {
        echo json_encode(["message" => "Release amount is 0", "code" => 400]);
        exit;
    }

    // 4. Transaction
    $db->beginTransaction();
    try {
        $to_balance = $actual_release * 0.70;
        $to_vouchers = $actual_release * 0.30;

        $upd = $db->prepare("UPDATE assets SET 
            traffic_points = traffic_points - ?,
            balance = balance + ?,
            vouchers = vouchers + ?
            WHERE user_id = ?");
        $upd->execute([$actual_release, $to_balance, $to_vouchers, $user['id']]);

        $log = $db->prepare("INSERT INTO daily_checkin_logs (user_id, checkin_date, release_amount) VALUES (?, ?, ?)");
        $log->execute([$user['id'], $today, $actual_release]);

        $fin = $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'mixed', ?, 0, 0, ?)");
        $fin->execute([$user['id'], $actual_release, "Daily Release"]);

        $db->commit();
        echo json_encode(["message" => "Checkin Successful. Released: $actual_release", "code" => 200]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => "Error: " . $e->getMessage(), "code" => 500]);
    }

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>