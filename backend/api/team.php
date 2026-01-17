<?php
// backend/api/team.php
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

// -------------------------------------------------------------
// GET PENDING LIST: Users sponsored by me but not placed (parent_id=0)
// -------------------------------------------------------------
if ($action == 'pending_list') {
    $stmt = $db->prepare("SELECT id, nickname, mobile, invite_code, created_at FROM users WHERE sponsor_id = ? AND parent_id = 0 ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["code" => 200, "data" => $pending]);

    // -------------------------------------------------------------
// PLACE USER: Assign pending user to a node
// -------------------------------------------------------------
} elseif ($action == 'place_user') {
    $data = json_decode(file_get_contents("php://input"));

    // Inputs: target_user_id (pending user), parent_id (new parent), position (L/R)
    if (!isset($data->target_user_id) || !isset($data->parent_id) || !isset($data->position)) {
        echo json_encode(["message" => "Missing data", "code" => 400]);
        exit;
    }

    $pending_uid = $data->target_user_id;
    $new_parent_id = $data->parent_id;
    $pos = $data->position;

    if ($pos !== 'L' && $pos !== 'R') {
        echo json_encode(["message" => "Invalid position", "code" => 400]);
        exit;
    }

    $db->beginTransaction();
    try {
        // 1. Verify Pending User: specific user, is pending, sponsored by me
        $stmt = $db->prepare("SELECT id, sponsor_id FROM users WHERE id = ? AND parent_id = 0 FOR UPDATE");
        $stmt->execute([$pending_uid]);
        $pUser = $stmt->fetch();

        if (!$pUser) {
            throw new Exception("User not found or already placed");
        }
        if ($pUser['sponsor_id'] != $user_id) {
            throw new Exception("You are not the sponsor of this user");
        }

        // 2. Verify Target Parent: exists
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$new_parent_id]);
        if ($stmt->rowCount() == 0) {
            throw new Exception("Target parent node not found");
        }

        // 3. Security Check: Target Parent MUST be in my team (can trace up to me) or IS ME
        if ($new_parent_id != $user_id) {
            $is_downstream = false;
            $curr = $new_parent_id;
            // Limit loop to avoid infinite in case of cyclic err (should not happen)
            $safety = 0;
            while ($curr != 0 && $safety < 1000) {
                if ($curr == $user_id) {
                    $is_downstream = true;
                    break;
                }
                $p_stmt = $db->prepare("SELECT parent_id FROM users WHERE id = ?");
                $p_stmt->execute([$curr]);
                $curr = $p_stmt->fetchColumn();
                $safety++;
            }

            if (!$is_downstream) {
                throw new Exception("Target placement node is not in your team");
            }
        }

        // 4. Verify Spot Availability
        $stmt = $db->prepare("SELECT id FROM users WHERE parent_id = ? AND position = ?");
        $stmt->execute([$new_parent_id, $pos]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Target position is already occupied");
        }

        // 5. Execute Placement
        $stmt = $db->prepare("UPDATE users SET parent_id = ?, position = ? WHERE id = ?");
        $stmt->execute([$new_parent_id, $pos, $pending_uid]);

        $db->commit();
        echo json_encode(["code" => 200, "message" => "Placement successful"]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["message" => $e->getMessage(), "code" => 400]);
    }

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>