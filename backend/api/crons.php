<?php
// backend/api/crons.php
// Scheduled tasks
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

function json_out($code, $msg, $data = null)
{
    header('Content-Type: application/json');
    echo json_encode(["code" => $code, "message" => $msg, "data" => $data]);
    exit;
}

if ($action == 'settle_points') {
    // Settle pending traffic points from orders
    // Logic: Look for orders with points_status = 0 AND settle_date <= TODAY AND status >= 1 (Paid)

    $today = date('Y-m-d');

    // Find eligible orders
    $sql = "SELECT id, user_id, order_sn, pending_points, settle_date FROM orders 
            WHERE points_status = 0 AND status >= 1 AND settle_date <= ? AND pending_points > 0";
    $stmt = $db->prepare($sql);
    $stmt->execute([$today]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    $total_released = 0;
    $errors = [];

    foreach ($orders as $order) {
        $db->beginTransaction();
        try {
            // 1. Fetch User Assets for Before Value
            $uid = $order['user_id'];
            $points = floatval($order['pending_points']);

            $u_stmt = $db->prepare("SELECT traffic_points FROM assets WHERE user_id = ? FOR UPDATE");
            $u_stmt->execute([$uid]);
            $asset = $u_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$asset) {
                // User assets missing? Create or skip
                throw new Exception("User assets not found for UID $uid");
            }

            $before = floatval($asset['traffic_points']);
            $after = $before + $points;

            // 2. Update Assets
            $upd = $db->prepare("UPDATE assets SET traffic_points = ? WHERE user_id = ?");
            $upd->execute([$after, $uid]);

            // 3. Log Finance
            $log = $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'traffic_points', ?, ?, ?, ?)");
            $log->execute([$uid, $points, $before, $after, "订单流量分结算: " . $order['order_sn']]);

            // 4. Mark Order as Settled
            $mark = $db->prepare("UPDATE orders SET points_status = 1 WHERE id = ?");
            $mark->execute([$order['id']]);

            $db->commit();
            $count++;
            $total_released += $points;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Order " . $order['id'] . ": " . $e->getMessage();
        }
    }

    json_out(200, "Settlement executed", [
        "processed_count" => $count,
        "total_released" => $total_released,
        "errors" => $errors
    ]);

} else {
    json_out(404, "Unknown action");
}
?>