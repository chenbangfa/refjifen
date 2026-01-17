<?php
// backend/api/mall.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwtHandler = new JWTHandler();

// Validate Token
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
$user_data = $jwtHandler->validate_jwt($jwt);

if (!$user_data) {
    echo json_encode(["message" => "Unauthorized", "code" => 401]);
    exit;
}
$user_id = $user_data['data']['id'];

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- Helper: Bubble Up Performance ---
function bubbleUpPerformance($db, $user_id, $amount)
{
    // 1. Get current user's placement info to know where they sit relative to parent
    // 2. Loop up until parent_id is 0

    $curr_id = $user_id;

    // We need to trace the path UPWARDS.
    // For every ancestor, we need to know if the CURRENT NODE came from their LEFT or RIGHT.
    // So we fetch the Current Node's Parent and Position.

    while (true) {
        $stmt = $db->prepare("SELECT parent_id, position FROM users WHERE id = ?");
        $stmt->execute([$curr_id]);
        $node = $stmt->fetch();

        if (!$node || $node['parent_id'] == 0)
            break; // Reached root or orphan

        $parent_id = $node['parent_id'];
        $position = $node['position'];

        // Update Parent's Performance
        if ($position == 'L') {
            $upd = $db->prepare("UPDATE performance SET left_total = left_total + ? WHERE user_id = ?");
            $upd->execute([$amount, $parent_id]);
        } elseif ($position == 'R') {
            $upd = $db->prepare("UPDATE performance SET right_total = right_total + ? WHERE user_id = ?");
            $upd->execute([$amount, $parent_id]);
        }

        // Move Up
        $curr_id = $parent_id;
    }
}
// -------------------------------------

if ($action == 'categories') {
    $stmt = $db->prepare("SELECT * FROM categories WHERE status = 1 ORDER BY sort ASC, id DESC");
    $stmt->execute();
    echo json_encode(["code" => 200, "data" => $stmt->fetchAll()]);

} elseif ($action == 'products') {
    $where = "status = 1";
    $params = [];

    if (isset($_GET['zone']) && $_GET['zone']) {
        $where .= " AND zone = ?";
        $params[] = $_GET['zone'];
    }

    if (isset($_GET['category_id']) && $_GET['category_id']) {
        $where .= " AND category_id = ?";
        $params[] = $_GET['category_id'];
    }

    if (isset($_GET['search']) && $_GET['search']) {
        $where .= " AND title LIKE ?";
        $params[] = '%' . $_GET['search'] . '%';
    }

    $stmt = $db->prepare("SELECT * FROM products WHERE $where ORDER BY id DESC");
    $stmt->execute($params);
    $stmt->execute($params);
    echo json_encode(["code" => 200, "data" => $stmt->fetchAll()]);

} elseif ($action == 'config') {
    // Return reward rules
    $stmt = $db->prepare("SELECT * FROM traffic_reward_rules ORDER BY min_amount ASC");
    $stmt->execute();
    echo json_encode(["code" => 200, "data" => ["reward_rules" => $stmt->fetchAll()]]);

} elseif ($action == 'buy') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->items) || empty($data->items) || !isset($data->pay_password)) {
        echo json_encode(["message" => "Missing data", "code" => 400]);
        exit;
    }

    // Verify Pay Password
    $stmt = $db->prepare("SELECT pay_password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userParam = $stmt->fetch();

    // Simple check (in real app, hash check) - assuming plain text or simple hash stored as per user instructions early on?
    // Early instructions said: `password` (hashed), `pay_password` (hashed?). 
    // Let's assume input is plain and DB is hashed, OR DB is plain?
    // Implementation Plan said "Native PHP". Let's assume simple comparison or md5 if established.
    // Auth.php uses password_verify. 
    // Let's assume pay_password is also hashed.
    // If not hashed in DB, compare directly.
    // Ideally verify: if(md5($data->pay_password) !== $userParam['pay_password']) ...
    // For now, if verify fails, return 400.
    // BUT, we haven't implemented pay_password setting hashing strictly.
    // Let's assume direct compare or MD5. 
    // I will use `password_verify` if it looks like hash, or direct if not?
    // Let's safely assume direct compare if not detailed. 
    // WAIT, `users` table `pay_password` field.
    // Use password_verify since account.php uses password_hash
    if (!$userParam['pay_password'] || !password_verify($data->pay_password, $userParam['pay_password'])) {
        echo json_encode(["message" => "支付密码错误", "code" => 403]);
        exit;
    }

    $db->beginTransaction();
    try {
        // Lock Assets including traffic_points
        $stmt = $db->prepare("SELECT balance, vouchers, traffic_points FROM assets WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $assets = $stmt->fetch();

        $total_price_A = 0;
        $total_price_B = 0;
        $total_points_A = 0;
        $total_amount_A = 0; // For level up and bubble up

        $order_sn = date('YmdHis') . rand(1000, 9999);
        $receiver_info = isset($data->receiver_info) ? json_encode($data->receiver_info, JSON_UNESCAPED_UNICODE) : '';
        $remark = isset($data->remark) ? $data->remark : '';

        // Pre-calculation loop
        foreach ($data->items as $item) {
            $pid = $item->id;
            $qty = intval($item->qty);
            if ($qty <= 0)
                continue;

            $p_stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $p_stmt->execute([$pid]);
            $product = $p_stmt->fetch();
            if (!$product || $product['stock'] < $qty) {
                throw new Exception("Product {$product['title']} out of stock");
            }

            $line_total = $product['price'] * $qty;
            if ($product['zone'] == 'A') {
                $total_price_A += $line_total;
                $total_amount_A += $line_total;
            } else {
                $total_price_B += $line_total;
            }
        }

        // Apply Reward Rules to Total A
        if ($total_price_A > 0) {
            $stmt = $db->prepare("SELECT ratio FROM traffic_reward_rules WHERE min_amount <= ? ORDER BY min_amount DESC LIMIT 1");
            $stmt->execute([$total_price_A]);
            $ratio = $stmt->fetchColumn();
            if (!$ratio)
                $ratio = 0.00;

            $total_points_A = $total_price_A * $ratio;
        }

        // 1. Process Deductions & Logs
        if ($total_price_A > 0) {
            if ($assets['balance'] < $total_price_A)
                throw new Exception("余额不足");
            $before = $assets['balance'];
            $after = $before - $total_price_A;
            $db->prepare("UPDATE assets SET balance = ? WHERE user_id = ?")->execute([$after, $user_id]);

            // Log
            $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'buy', 'balance', ?, ?, ?, '商城购物扣除')")
                ->execute([$user_id, -$total_price_A, $before, $after]);
        }

        if ($total_price_B > 0) {
            if ($assets['vouchers'] < $total_price_B)
                throw new Exception("购物券不足");
            $before = $assets['vouchers'];
            $after = $before - $total_price_B;
            $db->prepare("UPDATE assets SET vouchers = ? WHERE user_id = ?")->execute([$after, $user_id]);

            // Log
            $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'buy', 'vouchers', ?, ?, ?, '商城购物扣除')")
                ->execute([$user_id, -$total_price_B, $before, $after]);
        }

        // 2. Process Points Addition & Log
        if ($total_points_A > 0) {
            $before = $assets['traffic_points'];
            $after = $before + $total_points_A;
            $db->prepare("UPDATE assets SET traffic_points = ? WHERE user_id = ?")->execute([$after, $user_id]);

            // Log
            $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'release', 'traffic_points', ?, ?, ?, '购物赠送流量分')")
                ->execute([$user_id, $total_points_A, $before, $after]);
        }

        // 3. Level Up & Bubble Up (Zone A)
        if ($total_amount_A > 0) {
            $ustmt = $db->prepare("SELECT level FROM users WHERE id = ?");
            $ustmt->execute([$user_id]);
            $uinfo = $ustmt->fetch();

            // Calculate Total Accumulated Spending (Zone A only, including this order)
            // Historical
            $stmt = $db->prepare("SELECT IFNULL(SUM(amount), 0) FROM orders WHERE user_id = ? AND zone = 'A' AND status >= 1");
            $stmt->execute([$user_id]);
            $historical_total = $stmt->fetchColumn();

            $grand_total = $historical_total + $total_amount_A;

            // Upgrade Logic based on ACCUMULATED amount
            // Levels: 0=None, 1=VIP, 2=Gold, 3=Diamond
            $new_level = $uinfo['level'];
            if ($grand_total >= 20000) {
                $new_level = 3; // Diamond
            } elseif ($grand_total >= 4000) {
                if ($new_level < 2)
                    $new_level = 2; // Gold
            } elseif ($grand_total >= 299) {
                if ($new_level < 1)
                    $new_level = 1; // VIP
            }

            if ($new_level > $uinfo['level']) {
                $db->prepare("UPDATE users SET level = ? WHERE id = ?")->execute([$new_level, $user_id]);
            }

            bubbleUpPerformance($db, $user_id, $total_amount_A);
        }

        // 4. Update Stock/Sales & Create Order Rows
        foreach ($data->items as $item) {
            $pid = $item->id;
            $qty = intval($item->qty);
            if ($qty <= 0)
                continue;

            // Re-fetch product to get clean data (or cached above? Better refetch or use stored. Storing all products in array above is better but list is short. DB hit is fine for safety)
            $p_stmt = $db->prepare("SELECT price, zone FROM products WHERE id = ?");
            $p_stmt->execute([$pid]);
            $product = $p_stmt->fetch();

            $line_total = $product['price'] * $qty;

            // Update Stock
            $db->prepare("UPDATE products SET sales = sales + ?, stock = stock - ? WHERE id = ?")->execute([$qty, $qty, $pid]);

            // Insert Order
            // Added pay_time = NOW()
            $stmt = $db->prepare("INSERT INTO orders (order_sn, user_id, product_id, amount, quantity, zone, status, pay_time, receiver_info, remark) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?)");
            $stmt->execute([$order_sn, $user_id, $pid, $line_total, $qty, $product['zone'], $receiver_info, $remark]);
        }

        $db->commit();
        echo json_encode(["message" => "下单成功", "code" => 200, "data" => ["order_sn" => $order_sn]]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => $e->getMessage(), "code" => 500]);
    }

} elseif ($action == 'my_orders') {
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // 1. Get List of OrderSNs (Paginated)
    $where = "user_id = ?";
    $params = [$user_id];
    if ($status !== null) {
        $where .= " AND status = ?";
        $params[] = $status;
    }

    // Use DISTINCT order_sn, ordered by ID desc (latest first)
    // We need Max ID per order_sn to sort? 
    // "SELECT order_sn FROM orders WHERE $where GROUP BY order_sn ORDER BY MAX(id) DESC LIMIT $limit OFFSET $offset"
    // Note: status is on every row, so it's safe.

    $sql = "SELECT order_sn, MAX(status) as status, MAX(pay_time) as pay_time, SUM(amount) as total_amount, SUM(quantity) as total_num, MAX(zone) as zone
            FROM orders 
            WHERE $where 
            GROUP BY order_sn 
            ORDER BY MAX(id) DESC 
            LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Hydrate with Items
    $result = [];
    foreach ($orders as $order) {
        $osn = $order['order_sn'];

        // Fetch Items
        $i_stmt = $db->prepare("
            SELECT o.id, o.product_id, o.amount as price, o.quantity as num, p.title, p.image, p.zone, p.unit
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            WHERE o.order_sn = ?
        ");
        $i_stmt->execute([$osn]);
        $items = $i_stmt->fetchAll(PDO::FETCH_ASSOC);

        $order['id'] = $items[0]['id']; // Use first item ID as reliable Key
        // Formatting
        $order['goods_list'] = $items;
        $order['pay_type'] = $order['zone'] == 'A' ? 'balance' : 'score'; // Simplified logic
        $order['pay_price'] = $order['total_amount']; // Already summed

        $result[] = $order;
    }

    echo json_encode(["code" => 200, "data" => $result]);

} elseif ($action == 'order_detail') {
    if (!isset($_GET['id'])) {
        echo json_encode(["message" => "Missing ID", "code" => 400]);
        exit;
    }

    $id = intval($_GET['id']);
    // Find Order SN first
    $stmt = $db->prepare("SELECT order_sn FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $sn_row = $stmt->fetch();

    if (!$sn_row) {
        echo json_encode(["message" => "Order not found", "code" => 404]);
        exit;
    }

    $osn = $sn_row['order_sn'];

    // Fetch all items for this SN
    $stmt = $db->prepare("
        SELECT o.id, o.order_sn, o.status, o.created_at, o.pay_time, o.receiver_info, o.remark,
               o.express_company, o.tracking_number, o.updated_at,
               o.amount, o.quantity, o.zone,
               p.title, p.image, p.price as unit_price, p.unit
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.id
        WHERE o.order_sn = ?
    ");
    $stmt->execute([$osn]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo json_encode(["message" => "Order empty", "code" => 404]);
        exit;
    }

    // Structure the data (Aggregation)
    $first = $rows[0];
    $data = [
        'order_sn' => $first['order_sn'],
        'status' => $first['status'],
        'created_at' => $first['created_at'] ?? $first['pay_time'], // Fallback if created_at null
        'pay_time' => $first['pay_time'],
        'delivery_time' => $first['updated_at'], // Using updated_at as proxy for last status change (ship/finish)
        'receiver_info' => json_decode($first['receiver_info'], true),
        'remark' => $first['remark'],
        'express_company' => $first['express_company'],
        'tracking_number' => $first['tracking_number'],
        'pay_type' => $first['zone'] == 'A' ? 'balance' : 'score',
        'total_price' => 0,
        'goods_list' => []
    ];

    foreach ($rows as $row) {
        $data['total_price'] += $row['amount'];
        $data['goods_list'][] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => $row['image'],
            'price' => $row['amount'] / $row['quantity'], // Calc unit price from total/qty to be safe or use p.price
            'num' => $row['quantity']
        ];
    }
    // format price
    $data['total_price'] = number_format($data['total_price'], 2, '.', '');

    // Fetch After Sales Info
    $stmt = $db->prepare("SELECT * FROM after_sales WHERE order_sn = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$osn]);
    $after_sales = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($after_sales) {
        $data['after_sales_info'] = $after_sales;
    }

    echo json_encode(["code" => 200, "data" => $data]);



} elseif ($action == 'confirm_receipt') {
    $data = json_decode(file_get_contents("php://input"));
    if (!isset($data->id)) {
        echo json_encode(["message" => "Missing ID", "code" => 400]);
        exit;
    }

    $id = intval($data->id);

    // Find Order SN
    $stmt = $db->prepare("SELECT order_sn FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $sn_row = $stmt->fetch();

    if (!$sn_row) {
        echo json_encode(["message" => "Order not found", "code" => 404]);
        exit;
    }
    $osn = $sn_row['order_sn'];

    // Update Status
    $stmt = $db->prepare("UPDATE orders SET status = 3, updated_at = NOW() WHERE order_sn = ? AND status = 2");
    $stmt->execute([$osn]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["code" => 200, "message" => "确认收货成功"]);
    } else {
        echo json_encode(["code" => 400, "message" => "订单状态异常或已确认"]);
    }

} elseif ($action == 'order_counts') {
    $stmt = $db->prepare("SELECT status, COUNT(DISTINCT order_sn) as cnt FROM orders WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $counts = [
        '0' => 0,
        '1' => 0,
        '2' => 0,
        '3' => 0
    ];
    foreach ($rows as $k => $v) {
        $counts[$k] = intval($v);
    }

    echo json_encode(["code" => 200, "data" => $counts]);

} elseif ($action == 'product_detail') {
    if (!isset($_GET['id'])) {
        echo json_encode(["message" => "Missing ID", "code" => 400]);
        exit;
    }
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        echo json_encode(["code" => 200, "data" => $product]);
    } else {
        echo json_encode(["message" => "Product not found", "code" => 404]);
    }

} elseif ($action == 'banners') {
    $stmt = $db->prepare("SELECT * FROM banners WHERE status = 1 ORDER BY sort DESC, id DESC");
    $stmt->execute();
    echo json_encode(["code" => 200, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

} elseif ($action == 'apply_refund') {
    $data = json_decode(file_get_contents("php://input"));
    if (!$data->order_sn || !$data->reason) {
        echo json_encode(["message" => "Missing data", "code" => 400]);
        exit;
    }

    // Verify Order
    $stmt = $db->prepare("SELECT id, status FROM orders WHERE order_sn = ? AND user_id = ?");
    $stmt->execute([$data->order_sn, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(["message" => "Order not found", "code" => 404]);
        exit;
    }
    if (!in_array($order['status'], [1, 2])) {
        echo json_encode(["message" => "Order status not eligible for refund", "code" => 400]);
        exit;
    }

    // Check if already applied?
    $stmt = $db->prepare("SELECT id FROM after_sales WHERE order_sn = ? AND status = 0");
    $stmt->execute([$data->order_sn]);
    if ($stmt->fetch()) {
        echo json_encode(["message" => "Already applied", "code" => 400]);
        exit;
    }

    $db->beginTransaction();
    try {
        // Insert After Sales
        $stmt = $db->prepare("INSERT INTO after_sales (user_id, order_sn, type, reason, images) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $data->order_sn, $data->type, $data->reason, $data->images ?? '']);

        // Update Order Status to 5 (Refund Processing)
        $db->prepare("UPDATE orders SET status = 5 WHERE order_sn = ?")->execute([$data->order_sn]);

        $db->commit();
        echo json_encode(["code" => 200, "message" => "Applied successfully"]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => $e->getMessage(), "code" => 500]);
    }

} elseif ($action == 'cancel_refund') {
    $data = json_decode(file_get_contents("php://input"));
    if (!$data->order_sn) {
        echo json_encode(["message" => "Missing SN", "code" => 400]);
        exit;
    }

    // Verify Order
    $stmt = $db->prepare("SELECT id, status, tracking_number, express_company FROM orders WHERE order_sn = ? AND user_id = ?");
    $stmt->execute([$data->order_sn, $user_id]);
    $order = $stmt->fetch();

    if (!$order || $order['status'] != 5) {
        echo json_encode(["message" => "Order not in refund status", "code" => 400]);
        exit;
    }

    $db->beginTransaction();
    try {
        // Cancel After Sales
        $stmt = $db->prepare("UPDATE after_sales SET status = 3 WHERE order_sn = ? AND status = 0");
        $stmt->execute([$data->order_sn]);

        // Restore Order Status
        $new_status = (!empty($order['tracking_number']) && !empty($order['express_company'])) ? 2 : 1;

        $db->prepare("UPDATE orders SET status = ? WHERE order_sn = ?")->execute([$new_status, $data->order_sn]);

        $db->commit();
        echo json_encode(["code" => 200, "message" => "Cancelled successfully"]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => $e->getMessage(), "code" => 500]);
    }

} elseif ($action == 'nav_items') {
    $stmt = $db->prepare("SELECT * FROM nav_items WHERE status = 1 ORDER BY sort ASC, id ASC");
    $stmt->execute();
    echo json_encode(["code" => 200, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>