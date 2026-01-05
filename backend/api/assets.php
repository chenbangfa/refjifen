<?php
// backend/api/assets.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwtHandler = new JWTHandler();

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

if ($action == 'transfer') {
    // 1. Validate Input
    if (!isset($data->target_id) || !isset($data->amount) || !isset($data->pay_password)) {
        echo json_encode(["message" => "Missing data", "code" => 400]);
        exit;
    }

    $amount = floatval($data->amount);
    if ($amount < 100 || $amount % 200 != 0) { // Constraint: Multiple of 200 ?? Readme says 200.
        // Readme 2.4 says: "Amount must be integer multiple of 200"
        echo json_encode(["message" => "Amount must be a multiple of 200", "code" => 400]);
        exit;
    }

    // 2. Validate Pay Password (TODO: Implement real hash check)
    // For now, assume any 6 digit works if not set, or check DB
    // $stmt = $db->prepare("SELECT pay_password FROM users WHERE id = ?"); ...

    // 3. Check Balance
    $stmt = $db->prepare("SELECT balance FROM assets WHERE user_id = ? FOR UPDATE"); // Lock row
    $db->beginTransaction();
    try {
        $stmt->execute([$user_id]);
        $balance = $stmt->fetchColumn();

        if ($balance < $amount) {
            $db->rollBack();
            echo json_encode(["message" => "Insufficient balance", "code" => 402]);
            exit;
        }

        // 4. Check Target Exists
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$data->target_id]);
        if ($stmt->rowCount() == 0) {
            $db->rollBack();
            echo json_encode(["message" => "Target user not found", "code" => 404]);
            exit;
        }

        // 5. Execute Transfer
        // Deduct from sender
        $stmt = $db->prepare("UPDATE assets SET balance = balance - ? WHERE user_id = ?");
        $stmt->execute([$amount, $user_id]);

        // Add to receiver
        $stmt = $db->prepare("UPDATE assets SET balance = balance + ? WHERE user_id = ?");
        $stmt->execute([$amount, $data->target_id]);

        // Log Sender
        $stmt = $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'transfer_out', 'balance', ?, ?, ?, ?)");
        $stmt->execute([$user_id, $amount, $balance, $balance - $amount, "Transfer to " . $data->target_id]);

        // Log Receiver (Optional, or separate query to get their balance)

        $db->commit();
        echo json_encode(["message" => "Transfer successful", "code" => 200]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => "Error: " . $e->getMessage(), "code" => 500]);
    }
} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>