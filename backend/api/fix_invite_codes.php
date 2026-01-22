<?php
// backend/api/fix_invite_codes.php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Select users with empty invite code
$stmt = $db->query("SELECT id FROM users WHERE invite_code IS NULL OR invite_code = ''");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;
foreach ($users as $u) {
    // Generate Unique Code
    $new_invite_code = '';
    while (true) {
        $new_invite_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check = $db->prepare("SELECT id FROM users WHERE invite_code = ?");
        $check->execute([$new_invite_code]);
        if ($check->rowCount() == 0)
            break;
    }

    $update = $db->prepare("UPDATE users SET invite_code = ? WHERE id = ?");
    $update->execute([$new_invite_code, $u['id']]);
    echo "Updated User ID {$u['id']} with code $new_invite_code <br>\n";
    $count++;
}

echo "Done. Fixed $count users.";
?>