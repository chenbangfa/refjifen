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

    // Auto-assign position (Simple placement: alternate or just default 'L')
    // Real logic: User usually chooses L or R. Here we assume 'L' default.
    $position = isset($data->position) && in_array($data->position, ['L', 'R']) ? $data->position : 'L';

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

} elseif ($action == 'switch_account') {
    // 1. Get Token from Header
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    $decoded = $jwtHandler->validate_jwt($jwt);

    if (!$decoded) {
        echo json_encode(["message" => "Unauthorized", "code" => 401]);
        exit;
    }

    $current_id = $decoded['data']['id'];

    // Find the linked account
    // If current is master, find sub. If current is sub, find master.
    // Query Logic: Find record where linked_mobile matches current mobile OR this record's mobile matches linked...
    // Let's use a simpler logic: search by 'linked_mobile' and 'mobile' relationship.
    // Actually, per DB design:
    // Master: mobile=A, linked_mobile=B (or NULL if not created)
    // Sub: mobile=B, linked_mobile=A

    // First get current user details to know the mobile
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_id]);
    $currentUser = $stmt->fetch();

    if (!$currentUser['linked_mobile']) {
        echo json_encode(["message" => "No linked account found", "code" => 404]);
        exit;
    }

    // Find the OTHER account
    $stmt = $db->prepare("SELECT * FROM users WHERE mobile = ?");
    $stmt->execute([$currentUser['linked_mobile']]);
    $targetUser = $stmt->fetch();

    if ($targetUser) {
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
            "user" => [
                "id" => $targetUser['id'],
                "mobile" => $targetUser['mobile'],
                "is_sub_account" => $targetUser['is_sub_account']
            ],
            "code" => 200
        ]);
    } else {
        echo json_encode(["message" => "Linked account data error", "code" => 500]);
    }

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>