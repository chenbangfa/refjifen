<?php
// backend/api/articles.php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

function json_out($code, $msg, $data = null)
{
    echo json_encode(["code" => $code, "message" => $msg, "data" => $data]);
    exit;
}

if ($action == 'list') {
    // Public list of published articles
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("SELECT id, title, created_at FROM articles WHERE status = 1 ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_out(200, "success", $list);

} elseif ($action == 'detail') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!$id)
        json_out(400, "Missing ID");

    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ? AND status = 1");
    $stmt->execute([$id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($article) {
        // Optional: Increment view count if needed
        json_out(200, "success", $article);
    } else {
        json_out(404, "Article not found");
    }

} else {
    json_out(404, "Action not found");
}
?>