<?php
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    $sql = "ALTER TABLE orders 
            ADD COLUMN points_status tinyint(1) DEFAULT 0 COMMENT '0=Pending, 1=Settled' AFTER status,
            ADD COLUMN settle_date date DEFAULT NULL COMMENT 'Date to settle points' AFTER points_status,
            ADD COLUMN pending_points decimal(20,4) DEFAULT 0.0000 COMMENT 'Points pending settlement' AFTER settle_date";

    $db->exec($sql);
    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column") !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>