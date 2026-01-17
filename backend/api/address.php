<?php
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
    json_out(401, "Unauthorized");
}
$user_id = $user_data['data']['id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

function json_out($code, $msg, $data = null)
{
    echo json_encode(["code" => $code, "message" => $msg, "data" => $data]);
    exit;
}

if ($action == 'list') {
    $stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmt->execute([$user_id]);
    json_out(200, "OK", $stmt->fetchAll());

} elseif ($action == 'detail') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $stmt = $db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    json_out(200, "OK", $stmt->fetch());

} elseif ($action == 'save') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->name) || empty($data->mobile) || empty($data->detail)) {
        json_out(400, "Incomplete info");
    }

    $is_default = isset($data->is_default) && $data->is_default ? 1 : 0;

    if ($is_default) {
        $db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    }

    if (isset($data->id) && $data->id) {
        $sql = "UPDATE addresses SET name=?, mobile=?, province=?, city=?, district=?, detail=?, is_default=? WHERE id=? AND user_id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$data->name, $data->mobile, $data->province, $data->city, $data->district, $data->detail, $is_default, $data->id, $user_id]);
    } else {
        // If first address, make default
        $check = $db->prepare("SELECT COUNT(*) FROM addresses WHERE user_id = ?");
        $check->execute([$user_id]);
        if ($check->fetchColumn() == 0)
            $is_default = 1;

        $sql = "INSERT INTO addresses (user_id, name, mobile, province, city, district, detail, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $data->name, $data->mobile, $data->province, $data->city, $data->district, $data->detail, $is_default]);
    }
    json_out(200, "Saved");

} elseif ($action == 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
    json_out(200, "Deleted");
}
?>