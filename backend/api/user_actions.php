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

// ... (Keep existing headers)

function json_out($code, $msg, $data = null)
{
    echo json_encode(["code" => $code, "message" => $msg, "data" => $data]);
    exit;
}

if ($action == 'checkin') {
    $today = date('Y-m-d');

    // 1. Check Previous Checkin
    $check = $db->prepare("SELECT id FROM daily_checkin_logs WHERE user_id = ? AND checkin_date = ?");
    $check->execute([$user_id, $today]);
    if ($check->rowCount() > 0) {
        json_out(409, "今日已完成签到");
    }

    // 2. Fetch User & Rules
    $sql = "SELECT u.id, u.level, a.traffic_points, p.left_total, p.right_total 
            FROM users u 
            JOIN assets a ON u.id = a.user_id 
            JOIN performance p ON u.id = p.user_id 
            WHERE u.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        json_out(404, "User not found");
    }

    if ($user['traffic_points'] <= 0.01) { // 0.01 tolerance
        json_out(400, "流量分不足，无法签到释放");
    }

    // 3. Dynamic Logic
    // Base Release (Level Based) - Only Level 2 (Gold) and 3 (Diamond)
    $base_release = ($user['level'] >= 2) ? 80 : 0;

    // Performance Release (DB Rules)
    $stmt = $db->prepare("SELECT * FROM acceleration_rules ORDER BY min_performance DESC");
    $stmt->execute();
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $min_perf = min($user['left_total'], $user['right_total']);
    $perf_release = 0;

    foreach ($rules as $rule) {
        if ($min_perf >= $rule['min_performance']) {
            $perf_release = $rule['daily_bonus'];
            break; // Found highest matching rule
        }
    }

    // New Logic: Exclusive. If Dynamic exists, take Dynamic. Else take Base.
    if ($perf_release > 0) {
        $total_theoretical = $perf_release;
    } else {
        $total_theoretical = $base_release;
    }

    // Detailed feedback for failure
    if ($total_theoretical <= 0) {
        json_out(400, "暂无释放额度。请升级会员(金/钻)或双区业绩达4000以上。当前双区Min: " . number_format($min_perf, 2));
    }

    $actual_release = min($total_theoretical, $user['traffic_points']);

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

        $log = $db->prepare("INSERT IGNORE INTO daily_checkin_logs (user_id, checkin_date, release_amount) VALUES (?, ?, ?)");
        $log->execute([$user['id'], $today, $actual_release]);

        // 1. Log Traffic Points Deduction
        $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'traffic_points', ?, 0, 0, ?)")
            ->execute([$user['id'], -$actual_release, "每日签到释放扣除"]);

        // 2. Log Balance Addition
        $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'balance', ?, 0, 0, ?)")
            ->execute([$user['id'], $to_balance, "每日签到释放(70%)"]);

        // 3. Log Vouchers Addition
        $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'vouchers', ?, 0, 0, ?)")
            ->execute([$user['id'], $to_vouchers, "每日签到释放(30%)"]);

        $db->commit();
        json_out(200, "签到成功", ["release_amount" => $actual_release]);
    } catch (Exception $e) {
        $db->rollBack();
        json_out(500, "签到失败: " . $e->getMessage());
    }

} else {
    json_out(404, "Action not found");
}
?>