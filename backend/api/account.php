<?php
// backend/api/account.php
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
$data = json_decode(file_get_contents("php://input"));

if ($action == 'info') {
    // Get User Info + Assets + Performance
    $sql = "SELECT u.id, u.mobile, u.nickname, u.avatar, u.invite_code, u.level, u.position, u.is_sub_account, u.parent_id, u.is_frozen,
                   IF(u.pay_password IS NOT NULL AND u.pay_password != '', 1, 0) as has_pay_password,
                   a.balance, a.traffic_points, a.vouchers,
                   p.left_total, p.right_total
            FROM users u
            JOIN assets a ON u.id = a.user_id
            JOIN performance p ON u.id = p.user_id
            WHERE u.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch assoc to be safe

    // Calculate Personal Performance (Zone A purchases)
    $stmt = $db->prepare("SELECT IFNULL(SUM(amount), 0) as total FROM orders WHERE user_id = ? AND zone = 'A' AND status >= 1");
    $stmt->execute([$user_id]);
    $personal_perf = $stmt->fetch()['total'];

    // Calculate Direct Fans Count
    $stmt = $db->prepare("SELECT count(*) as count FROM users WHERE parent_id = ?");
    $stmt->execute([$user_id]);
    $fans_count = $stmt->fetch()['count'];

    $info['personal_performance'] = $personal_perf;
    $info['community_performance'] = $info['left_total'] + $info['right_total']; // Total Community
    $info['fans_count'] = $fans_count;

    echo json_encode(["code" => 200, "data" => $info]);

} elseif ($action == 'update_profile') {
    $data = json_decode(file_get_contents("php://input"));
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    $decoded = $jwtHandler->validate_jwt($jwt);

    if (!$decoded) {
        echo json_encode(["message" => "Unauthorized", "code" => 401]);
        exit;
    }

    $user_id = $decoded['data']['id'];

    // Fetch current info to check consistency
    $stmt = $db->prepare("SELECT mobile FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Updates
    $updates = [];
    $params = [];

    if (isset($data->nickname)) {
        $updates[] = "nickname = ?";
        $params[] = $data->nickname;
    }
    if (isset($data->avatar)) {
        $updates[] = "avatar = ?";
        $params[] = $data->avatar;
    }
    if (isset($data->mobile)) {
        // Only check/update if mobile actually changed
        if ($currentUser['mobile'] !== $data->mobile) {
            $stmt = $db->prepare("SELECT id FROM users WHERE mobile = ? AND id != ?");
            $stmt->execute([$data->mobile, $user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "该手机号已被使用", "code" => 409]);
                exit;
            }
            $updates[] = "mobile = ?";
            $params[] = $data->mobile;
        }
    }

    if (empty($updates)) {
        echo json_encode(["message" => "没有变更", "code" => 200]);
        exit;
    }

    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["message" => "资料更新成功", "code" => 200]);
    } catch (Exception $e) {
        echo json_encode(["message" => "更新失败", "code" => 500]);
    }

} elseif ($action == 'set_pay_password') {
    $data = json_decode(file_get_contents("php://input"));
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    $decoded = $jwtHandler->validate_jwt($jwt);

    if (!$decoded) {
        echo json_encode(["message" => "Unauthorized", "code" => 401]);
        exit;
    }

    $user_id = $decoded['data']['id'];

    if (!isset($data->password) || !ctype_digit($data->password) || strlen($data->password) != 6) {
        echo json_encode(["message" => "Password must be 6 digits", "code" => 400]);
        exit;
    }

    // Hash it (using simple MD5 or same hash algo as login? 
    // Usually pay password is 6 digits, hashing is good practice. 
    // Login uses BCrypt. Pay password often numeric, BCrypt is fine too but slow? 
    // Let's use BCrypt for consistency and security.)
    $hash = password_hash($data->password, PASSWORD_BCRYPT);

    $sql = "UPDATE users SET pay_password = ? WHERE id = ?";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$hash, $user_id]);
        echo json_encode(["message" => "Payment password set successfully", "code" => 200]);
    } catch (Exception $e) {
        echo json_encode(["message" => "Failed to set password", "code" => 500]);
    }

} elseif ($action == 'activate_sub') {
    // Activate Sub Account
    // 1. Check if already has sub
    $stmt = $db->prepare("SELECT linked_mobile FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch();

    if ($current['linked_mobile']) {
        echo json_encode(["message" => "子账户已存在", "code" => 400]);
        exit;
    }

    if (!isset($data->sub_mobile) || !isset($data->password) || !isset($data->position)) {
        echo json_encode(["message" => "数据不完整", "code" => 400]);
        exit;
    }

    // 2. Validate Position (Left or Right)
    // IMPORTANT: Sub account must be placed under Master account's tree
    // Ideally, User chooses: "Place my sub account on my Left"
    // So Parent of Sub = Master.

    $sub_mobile = $data->sub_mobile;
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
    $position = $data->position; // 'L' or 'R'

    // Check if position is occupied
    $stmt = $db->prepare("SELECT id FROM users WHERE parent_id = ? AND position = ?");
    $stmt->execute([$user_id, $position]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["message" => "Position already occupied", "code" => 409]);
        exit;
    }

    $db->beginTransaction();
    try {
        // Create Sub User
        $stmt = $db->prepare("INSERT INTO users (mobile, password, parent_id, position, is_sub_account, linked_mobile) VALUES (?, ?, ?, ?, 1, ?)");
        // Link Sub -> Master's mobile (assuming master's mobile is known, strictly we should query it)
        $master_mobile = $user_data['data']['mobile'];
        $stmt->execute([$sub_mobile, $password_hash, $user_id, $position, $master_mobile]);
        $sub_id = $db->lastInsertId();

        // Update Master -> Linked Sub
        $stmt = $db->prepare("UPDATE users SET linked_mobile = ? WHERE id = ?");
        $stmt->execute([$sub_mobile, $user_id]);

        // Init Assets for Sub
        $db->prepare("INSERT INTO assets (user_id) VALUES (?)")->execute([$sub_id]);
        $db->prepare("INSERT INTO performance (user_id) VALUES (?)")->execute([$sub_id]);

        $db->commit();
        echo json_encode(["message" => "Sub-account activated", "code" => 200]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => "Error: " . $e->getMessage(), "code" => 500]);
    }

} elseif ($action == 'team') {
    // Return direct children (Binary Tree)
    // Structure: { self: {...}, left: {...}, right: {...} }
    // For deeper levels, frontend can request recursively or we provide 2 levels here.

    function getNode($db, $uid)
    {
        if (!$uid)
            return null;
        $sql = "SELECT u.id, u.nickname, u.mobile, u.level, u.position, p.left_total, p.right_total 
                FROM users u 
                LEFT JOIN performance p ON u.id = p.user_id 
                WHERE u.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$uid]);
        $node = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($node) {
            // Calculate Personal Performance
            $stmt = $db->prepare("SELECT IFNULL(SUM(amount), 0) as total FROM orders WHERE user_id = ? AND zone = 'A' AND status >= 1");
            $stmt->execute([$uid]);
            $node['personal_performance'] = $stmt->fetch()['total'];
        }

        return $node;
    }

    $root = getNode($db, $user_id);

    // Get Children
    $stmt = $db->prepare("SELECT id, position FROM users WHERE parent_id = ?");
    $stmt->execute([$user_id]);
    $children = $stmt->fetchAll();

    $left = null;
    $right = null;

    foreach ($children as $child) {
        if ($child['position'] == 'L')
            $left = getNode($db, $child['id']);
        if ($child['position'] == 'R')
            $right = getNode($db, $child['id']);
    }

    echo json_encode([
        "code" => 200,
        "data" => [
            "root" => $root,
            "left" => $left,
            "right" => $right
        ]
    ]);

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>