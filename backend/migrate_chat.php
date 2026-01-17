<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $sql = "";

    $db->exec($sql);
    echo "Table chat_messages created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>