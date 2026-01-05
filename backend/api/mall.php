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

if ($action == 'products') {
    $stmt = $db->prepare("SELECT * FROM products WHERE status = 1");
    $stmt->execute();
    echo json_encode(["code" => 200, "data" => $stmt->fetchAll()]);

} elseif ($action == 'buy') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->product_id) || !isset($data->pay_password)) {
        echo json_encode(["message" => "Missing data", "code" => 400]);
        exit;
    }

    // 1. Get Product Info
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$data->product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(["message" => "Product not found", "code" => 404]);
        exit;
    }

    $db->beginTransaction();
    try {
        // 2. Check Balance and Deduct
        $price = $product['price'];
        $zone = $product['zone'];

        // Lock Assets
        $stmt = $db->prepare("SELECT balance, vouchers FROM assets WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $assets = $stmt->fetch();

        if ($zone == 'A') {
            if ($assets['balance'] < $price)
                throw new Exception("Insufficient Balance");
            $db->prepare("UPDATE assets SET balance = balance - ? WHERE user_id = ?")->execute([$price, $user_id]);
        } else {
            if ($assets['vouchers'] < $price)
                throw new Exception("Insufficient Vouchers");
            $db->prepare("UPDATE assets SET vouchers = vouchers - ? WHERE user_id = ?")->execute([$price, $user_id]);
        }

        // 3. Zone A Specific Logic (Points + Performance)
        if ($zone == 'A') {
            // Get User Level
            $ustmt = $db->prepare("SELECT level FROM users WHERE id = ?");
            $ustmt->execute([$user_id]);
            $uinfo = $ustmt->fetch();

            // Multiple: Gold(1) -> 3x, Diamond(2) -> 4x. 
            // If user is neither but buys product, maybe product determines level?
            // Requirement 2.2 says: "Buy 4000 -> Gold", "Buy 20000 -> Diamond".
            // So we should check the PRODUCT PRICE or upgrade the user.

            $multiplier = 3;
            if ($price >= 20000 || $uinfo['level'] == 2)
                $multiplier = 4;

            // Update User Level if upgraded
            if ($price >= 20000 && $uinfo['level'] < 2) {
                $db->prepare("UPDATE users SET level = 2 WHERE id = ?")->execute([$user_id]);
            } elseif ($price >= 4000 && $uinfo['level'] < 1) {
                $db->prepare("UPDATE users SET level = 1 WHERE id = ?")->execute([$user_id]);
            }

            $points = $price * $multiplier;
            $db->prepare("UPDATE assets SET traffic_points = traffic_points + ? WHERE user_id = ?")->execute([$points, $user_id]);

            // Performance Bubble Up
            bubbleUpPerformance($db, $user_id, $price);
        }

        // 4. Create Order
        $order_sn = date('YmdHis') . rand(1000, 9999);
        $stmt = $db->prepare("INSERT INTO orders (order_sn, user_id, product_id, amount, zone, status) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$order_sn, $user_id, $data->product_id, $price, $zone]);

        $db->commit();
        echo json_encode(["message" => "Purchase successful", "code" => 200]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => $e->getMessage(), "code" => 500]);
    }

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>