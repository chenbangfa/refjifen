<?php
// backend/api/auth.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwtHandler = new JWTHandler();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents("php://input"));

if ($action == 'register') {
    // 1. Validate Input
    if (!isset($data->mobile) || !isset($data->password) || !isset($data->invite_code)) {
        echo json_encode(["message" => "Incomplete data", "code" => 400]);
        exit;
    }

    // 2. Check if mobile exists
    $stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
    $stmt->execute([$data->mobile]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["message" => "Mobile already registered", "code" => 409]);
        exit;
    }

    // 3. Check Invite Code (Parent ID)
    // New Logic: Lookup user by invite_code
    $sponsor_id = 0;

    if ($data->invite_code == '0') {
        // Root node exception
        $count = $db->query("SELECT count(*) FROM users")->fetchColumn();
        if ($count > 0) {
            echo json_encode(["message" => "Invalid Invite Code", "code" => 400]);
            exit;
        }
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
        $stmt->execute([$data->invite_code]);
        $parent = $stmt->fetch();
        if (!$parent) {
            echo json_encode(["message" => "Invalid Invite Code", "code" => 400]);
            exit;
        }
        $sponsor_id = $parent['id'];
    }

    // 4. Create User
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    // Generate New Invite Code
    $new_invite_code = '';
    while (true) {
        $new_invite_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
        $stmt->execute([$new_invite_code]);
        if ($stmt->rowCount() == 0)
            break;
    }

    // Auto-assign position logic
    // Default is Left
    $position = 'L';
    $parent_id = $sponsor_id; // Default placement under sponsor

    // Check if Parent already has a Left child
    if ($parent_id > 0) {
        $stmt = $db->prepare("SELECT id FROM users WHERE parent_id = ? AND position = 'L'");
        $stmt->execute([$parent_id]);
        if ($stmt->rowCount() > 0) {
            // Left is occupied, place in Right
            $position = 'R';
        }
    }

    $sql = "INSERT INTO users (mobile, password, invite_code, sponsor_id, parent_id, position, is_sub_account) VALUES (?, ?, ?, ?, ?, ?, 0)";
    $stmt = $db->prepare($sql);

    try {
        if ($stmt->execute([$data->mobile, $password_hash, $new_invite_code, $sponsor_id, $parent_id, $position])) {
            $user_id = $db->lastInsertId();

            // Initialize Assets
            $db->prepare("INSERT IGNORE INTO assets (user_id) VALUES (?)")->execute([$user_id]);
            // Initialize Performance
            $db->prepare("INSERT IGNORE INTO performance (user_id) VALUES (?)")->execute([$user_id]);

            echo json_encode(["message" => "Registration successful", "code" => 200, "user_id" => $user_id]);
        } else {
            echo json_encode(["message" => "Unable to register", "code" => 503]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "DB Error: " . $e->getMessage(), "code" => 500]);
    }

} elseif ($action == 'login') {
    if (!isset($data->mobile) || !isset($data->password)) {
        echo json_encode(["message" => "Incomplete data", "code" => 400]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE mobile = ? AND is_sub_account = 0");
    $stmt->execute([$data->mobile]);
    $user = $stmt->fetch();

    if ($user && password_verify($data->password, $user['password'])) {
        $token_payload = [
            "iss" => "refjifen",
            "data" => [
                "id" => $user['id'],
                "mobile" => $user['mobile'],
                "nickname" => $user['nickname']
            ]
        ];
        $jwt = $jwtHandler->generate_jwt($token_payload);
        echo json_encode([
            "message" => "Login successful",
            "token" => $jwt,
            "user" => [
                "id" => $user['id'],
                "mobile" => $user['mobile'],
                "has_sub_account" => !empty($user['linked_mobile']) // Simplified check
            ],
            "code" => 200
        ]);
    } else {
        echo json_encode(["message" => "Invalid credentials", "code" => 401]);
    }


} elseif ($action == 'list_accounts') {
    // Return all accounts sharing the same mobile number
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    $decoded = $jwtHandler->validate_jwt($jwt);

    if (!$decoded) {
        echo json_encode(["message" => "Unauthorized", "code" => 401]);
        exit;
    }

    $current_id = $decoded['data']['id'];
    $mobile = $decoded['data']['mobile'];

    // Improved Logic: Just get all users with this mobile number
    // This handles "One Mobile, Multiple IDs" correctly without relying on linked_mobile
    $stmt = $db->prepare("SELECT id, mobile, nickname, is_sub_account, level, invite_code FROM users WHERE mobile = ? ORDER BY id ASC");
    $stmt->execute([$mobile]);
    $all_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = [];
    foreach ($all_accounts as $acc) {
        $acc['is_current'] = ($acc['id'] == $current_id);
        $users[] = $acc;
    }

    echo json_encode(["code" => 200, "data" => $users]);

} elseif ($action == 'quick_login') {
    // Login to a specific target_id WITHOUT password, if matching mobile
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    $decoded = $jwtHandler->validate_jwt($jwt);

    if (!$decoded) {
        echo json_encode(["message" => "Unauthorized", "code" => 401]);
        exit;
    }

    $current_id = $decoded['data']['id'];
    $current_mobile = $decoded['data']['mobile'];
    $target_id = isset($data->target_id) ? $data->target_id : null;

    if (!$target_id) {
        echo json_encode(["message" => "Target ID required", "code" => 400]);
        exit;
    }

    if ($target_id == $current_id) {
        // Just return success if already logged in (frontend might just want to confirm)
        echo json_encode(["message" => "Already logged in", "code" => 200]);
        exit;
    }

    // Auth Check: Does target have the same mobile?
    // We check if the target_id has the same mobile as the current authenticated session
    $stmt = $db->prepare("SELECT id, mobile, nickname FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        echo json_encode(["message" => "Account not found", "code" => 404]);
        exit;
    }

    if ($targetUser['mobile'] !== $current_mobile) {
        echo json_encode(["message" => "Access Denied: Mobile mismatch", "code" => 403]);
        exit;
    }

    // Allow Switch - Generate new token
    $token_payload = [
        "iss" => "refjifen",
        "data" => [
            "id" => $targetUser['id'],
            "mobile" => $targetUser['mobile'],
            "nickname" => $targetUser['nickname']
        ]
    ];
    $jwt = $jwtHandler->generate_jwt($token_payload);

    echo json_encode([
        "message" => "Switched successfully",
        "token" => $jwt,
        "code" => 200
    ]);

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>