<?php
// backend/api/upload.php
// Suppress all error output to prevent invalid JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../utils/jwt.php';

// Check Auth
session_start();

// Check Auth: Admin Session OR User JWT
$isAdmin = isset($_SESSION['admin_id']);
$isUser = false;

if (!$isAdmin) {
    $jwtHandler = new JWTHandler();
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    $user_data = $jwtHandler->validate_jwt($jwt);
    if ($user_data) {
        $isUser = true;
    }
}

if (!$isAdmin && !$isUser) {
    echo json_encode(["message" => "Unauthorized", "code" => 401]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/../uploads/';

    // Ensure upload dir exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) { // 5MB limit
                $newFileName = uniqid('', true) . "." . $fileExt;
                $fileDestination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // Return the accessible URL
                    // Assuming server layout: domain/backend/api/upload.php -> domain/backend/uploads/file.jpg
                    // Adjust this base URL to match your actual server config or use relative if frontend handles it
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $host = $_SERVER['HTTP_HOST'];
                    // Construct URL: domain/backend/uploads/xxx
                    // We are in /backend/api, so uploads is ../uploads
                    // But from URL perspective: /backend/api/upload.php vs /backend/uploads/xxx
                    $url = "$protocol://$host/backend/uploads/$newFileName";

                    echo json_encode(["code" => 200, "message" => "Upload success", "url" => $url]);
                } else {
                    // DEBUG INFO
                    $lasterr = error_get_last();
                    $debug = [
                        "uploadDir" => $uploadDir,
                        "is_dir" => is_dir($uploadDir),
                        "is_writable" => is_writable($uploadDir),
                        "fileTmpName" => $fileTmpName,
                        "fileDestination" => $fileDestination,
                        "php_err" => $lasterr
                    ];
                    echo json_encode(["code" => 500, "message" => "Failed to move uploaded file", "debug" => $debug]);
                }
            } else {
                echo json_encode(["code" => 400, "message" => "File too large (Max 5MB)"]);
            }
        } else {
            echo json_encode(["code" => 400, "message" => "Upload error code: $fileError"]);
        }
    } else {
        echo json_encode(["code" => 400, "message" => "Invalid file type. Allowed: jpg, jpeg, png, gif"]);
    }
} else {
    echo json_encode(["code" => 400, "message" => "No file uploaded"]);
}
?>