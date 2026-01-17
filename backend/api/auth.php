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
    // For MVP, invite_code IS the parent_id.
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$data->invite_code]);
    if ($stmt->rowCount() == 0) {
        // Root node exception: if DB is empty, allow invite_code=0
        $count = $db->query("SELECT count(*) FROM users")->fetchColumn();
        if ($count > 0 || $data->invite_code != 0) {
            echo json_encode(["message" => "Invalid Invite Code", "code" => 400]);
            exit;
        }
    }

    // 4. Create User
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    // Auto-assign position logic
    // Default is Left
    $position = 'L';

    // Check if Parent already has a Left child
    $stmt = $db->prepare("SELECT id FROM users WHERE parent_id = ? AND position = 'L'");
    $stmt->execute([$data->invite_code]);
    if ($stmt->rowCount() > 0) {
        // Left is occupied, place in Right
        $position = 'R';
    }

    $sql = "INSERT INTO users (mobile, password, parent_id, position, is_sub_account) VALUES (?, ?, ?, ?, 0)";
    $stmt = $db->prepare($sql);

    try {
        if ($stmt->execute([$data->mobile, $password_hash, $data->invite_code, $position])) {
            $user_id = $db->lastInsertId();

            // Initialize Assets
            $db->prepare("INSERT INTO assets (user_id) VALUES (?)")->execute([$user_id]);
            // Initialize Performance
            $db->prepare("INSERT INTO performance (user_id) VALUES (?)")->execute([$user_id]);

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
    // Removed invite_code from SELECT as it doesn't exist in DB schema
    $stmt = $db->prepare("SELECT id, mobile, nickname, is_sub_account, level FROM users WHERE mobile = ? ORDER BY id ASC");
    $stmt->execute([$mobile]);
    $all_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = [];
    foreach ($all_accounts as $acc) {
        $acc['is_current'] = ($acc['id'] == $current_id);
        $acc['invite_code'] = $acc['id']; // Alias ID as invite_code
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