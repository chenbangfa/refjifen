<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/jwt.php';

$database = new Database();
$db = $database->getConnection();
$jwtHandler = new JWTHandler();

// --- Auto Migration ---
try {
    $check = $db->query("SHOW TABLES LIKE 'chat_messages'");
    if ($check->rowCount() == 0) {
        $sql = "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            admin_id INT DEFAULT 0,
            type ENUM('text', 'image') DEFAULT 'text',
            content TEXT,
            sender ENUM('user', 'admin') NOT NULL,
            is_read TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->exec($sql);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/chat_error.log', "Migration Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

// --- Validate Token ---
$headers = null;
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
}

$authHeader = null;
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // Some environments rename it
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

$jwt = $authHeader ? str_replace('Bearer ', '', $authHeader) : null;
$user_data = $jwtHandler->validate_jwt($jwt);

if (!$user_data) {
    echo json_encode(["message" => "Unauthorized", "code" => 401]);
    exit;
}
$user_id = $user_data['data']['id'];

$action = isset($_GET['action']) ? $_GET['action'] : '';
$input = file_get_contents("php://input");
$data = json_decode($input);

if ($action == 'history') {
    // Get last 100 messages
    $limit = 100;
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$user_id]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark admin messages as read
    $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND sender = 'admin' AND is_read = 0")->execute([$user_id]);

    echo json_encode(["code" => 200, "data" => $msgs]);

} elseif ($action == 'send') {
    $content = (isset($data->content)) ? trim($data->content) : '';

    if ($content === '') {
        echo json_encode(["message" => "Empty message", "code" => 400]);
        exit;
    }

    $type = isset($data->type) ? $data->type : 'text';

    try {
        $stmt = $db->prepare("INSERT INTO chat_messages (user_id, type, content, sender, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $stmt->execute([$user_id, $type, $content]);
        echo json_encode(["code" => 200, "message" => "Sent"]);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/chat_error.log', "Send Error: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["message" => "Database Error", "code" => 500]);
    }

} elseif ($action == 'upload') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
        echo json_encode(["message" => "Upload failed", "code" => 400]);
        exit;
    }

    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode(["message" => "Invalid file type", "code" => 400]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/chat/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $filename = date('YmdHis') . '_' . rand(1000, 9999) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Return full URL
        // Assuming domain is ref.tajian.cc
        $url = 'https://ref.tajian.cc/backend/uploads/chat/' . $filename;
        echo json_encode(["code" => 200, "data" => ["url" => $url]]);
    } else {
        echo json_encode(["message" => "Save failed", "code" => 500]);
    }

} else {
    echo json_encode(["message" => "Action not found", "code" => 404]);
}
?>