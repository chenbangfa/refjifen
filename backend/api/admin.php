<?php
// backend/api/admin.php
session_start();
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents("php://input"));

// --- Helper Functions ---
function json_out($code, $msg, $data = null)
{
    echo json_encode(["code" => $code, "message" => $msg, "data" => $data]);
    exit;
}

// --- Auth Bypass for Login ---
if ($action == 'login') {
    if (!isset($data->username) || !isset($data->password))
        json_out(400, "Missing credentials");

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$data->username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($data->password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        json_out(200, "Login success", ["username" => $admin['username']]);
    } else {
        json_out(401, "Invalid username or password");
    }
}

if ($action == 'logout') {
    session_destroy();
    json_out(200, "Logout success");
}

// --- Auth Check for other actions ---
if (!isset($_SESSION['admin_id'])) {
    json_out(401, "Unauthorized - Please Login");
}

// --- Business Logic ---

if ($action == 'users') {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $where = "WHERE 1=1";
    $params = [];
    if ($search) {
        $where .= " AND (u.mobile LIKE ? OR u.nickname LIKE ? OR u.id = ?)";
        $params = ["%$search%", "%$search%", $search];
    }

    // Count
    $stmt = $db->prepare("SELECT count(*) FROM users u $where");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // List
    // List
    // List
    $sql = "SELECT u.id, u.mobile, u.nickname, u.invite_code, u.level, u.created_at, u.parent_id, u.position, u.is_frozen,
                   a.balance, a.traffic_points, a.vouchers,
                   p.mobile as parent_mobile, p.nickname as parent_nickname,
                   s.mobile as sponsor_mobile, s.nickname as sponsor_nickname,
                   perf.left_total, perf.right_total,
                   (SELECT count(*) FROM users WHERE parent_id = u.id AND position = 'L') as left_directs,
                   (SELECT count(*) FROM users WHERE parent_id = u.id AND position = 'R') as right_directs,
                   (SELECT count(*) FROM users WHERE sponsor_id = u.id) as sponsor_count
            FROM users u 
            LEFT JOIN assets a ON u.id = a.user_id 
            LEFT JOIN users p ON u.parent_id = p.id
            LEFT JOIN users s ON u.sponsor_id = s.id
            LEFT JOIN performance perf ON u.id = perf.user_id
            $where ORDER BY u.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_out(200, "success", ["list" => $list, "total" => $total]);

} elseif ($action == 'update_user') {
    if (!isset($data->id))
        json_out(400, "ID missing");

    $fields = [];
    $params = [];

    if (isset($data->nickname)) {
        $fields[] = "nickname=?";
        $params[] = $data->nickname;
    }
    if (isset($data->mobile)) {
        $fields[] = "mobile=?";
        $params[] = $data->mobile;
    }
    if (isset($data->parent_id)) {
        $fields[] = "parent_id=?";
        $params[] = $data->parent_id;
    }
    if (isset($data->position)) {
        $fields[] = "position=?";
        $params[] = $data->position;
    }
    if (isset($data->is_frozen)) {
        $fields[] = "is_frozen=?";
        $params[] = intval($data->is_frozen);
    }

    if (isset($data->password) && $data->password !== '') {
        $fields[] = "password=?";
        $params[] = password_hash($data->password, PASSWORD_BCRYPT);
    }

    if (empty($fields))
        json_out(200, "No changes");

    $params[] = $data->id;
    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id=?";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        json_out(200, "User updated");
    } catch (Exception $e) {
        json_out(500, "Update failed: " . $e->getMessage());
    }

} elseif ($action == 'recharge') {
    // Admin Recharge
    if (!isset($data->user_id) || !isset($data->amount) || !isset($data->type))
        json_out(400, "Missing data");

    $amount = floatval($data->amount);
    if ($amount == 0)
        json_out(400, "Amount cannot be 0");

    $type = $data->type; // 'balance' or 'traffic_points' or 'vouchers'
    $col = ($type == 'balance') ? 'balance' : (($type == 'vouchers') ? 'vouchers' : 'traffic_points');

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT $col FROM assets WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$data->user_id]);
        $current = $stmt->fetchColumn();

        $new = $current + $amount;

        $stmt = $db->prepare("UPDATE assets SET $col = ? WHERE user_id = ?");
        $stmt->execute([$new, $data->user_id]);

        // Log
        $stmt = $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'admin_recharge', ?, ?, ?, ?, ?)");
        $stmt->execute([$data->user_id, $type, $amount, $current, $new, "管理员充值/扣补"]);

        $db->commit();
        json_out(200, "Recharge success");
    } catch (Exception $e) {
        $db->rollBack();
        json_out(500, "Error: " . $e->getMessage());
        json_out(500, "Error: " . $e->getMessage());
    }

} elseif ($action == 'user_transactions') {
    $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
    if (!$user_id)
        json_out(400, "User ID required");

    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    $where = "WHERE user_id = ?";
    $params = [$user_id];

    if ($startDate) {
        $where .= " AND created_at >= ?";
        $params[] = $startDate . " 00:00:00";
    }
    if ($endDate) {
        $where .= " AND created_at <= ?";
        $params[] = $endDate . " 23:59:59";
    }

    $limit = 100;
    $sql = "SELECT * FROM logs_finance $where ORDER BY id DESC LIMIT $limit";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_out(200, "success", $list);

} elseif ($action == 'user_team') {
    // For Admin Team Tree
    // If id is passed, return that node as root.
    // If parent_id is passed, return children.

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

    $nodes = [];

    if ($id > 0) {
        // Fetch specific root node
        $sql = "SELECT u.id, u.nickname, u.mobile, u.level, u.position, p.left_total, p.right_total 
                FROM users u 
                LEFT JOIN performance p ON u.id = p.user_id 
                WHERE u.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // Check if has children
            $c_stmt = $db->prepare("SELECT count(*) FROM users WHERE parent_id = ?");
            $c_stmt->execute([$row['id']]);
            $hasChild = $c_stmt->fetchColumn() > 0;

            $nodes[] = [
                'id' => $row['id'],
                'label' => ($row['nickname'] ?: '未命名') . " (ID:{$row['id']})",
                'leaf' => !$hasChild,
                'data' => $row
            ];
        }
    } elseif ($parentId > 0) {
        // Fetch children
        $sql = "SELECT u.id, u.nickname, u.mobile, u.level, u.position, p.left_total, p.right_total 
                FROM users u 
                LEFT JOIN performance p ON u.id = p.user_id 
                WHERE u.parent_id = ? ORDER BY u.position ASC"; // L first then R
        $stmt = $db->prepare($sql);
        $stmt->execute([$parentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            // Check if has children
            $c_stmt = $db->prepare("SELECT count(*) FROM users WHERE parent_id = ?");
            $c_stmt->execute([$row['id']]);
            $hasChild = $c_stmt->fetchColumn() > 0;

            $nodes[] = [
                'id' => $row['id'],
                'label' => ($row['position'] == 'L' ? '[左] ' : '[右] ') . ($row['nickname'] ?: '未命名') . " (ID:{$row['id']})",
                'leaf' => !$hasChild,
                'data' => $row
            ];
        }
    }

    json_out(200, "success", $nodes);

} elseif ($action == 'withdrawals') {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : ''; // 0, 1, 2

    $where = "WHERE 1=1";
    $params = [];
    if ($status !== '') {
        $where .= " AND w.status = ?";
        $params[] = $status;
    }

    // Count
    $countSql = "SELECT count(*) FROM withdrawals w JOIN users u ON w.user_id = u.id $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // List
    $sql = "SELECT w.*, w.proof_image, u.mobile, u.nickname FROM withdrawals w JOIN users u ON w.user_id = u.id $where ORDER BY w.id DESC LIMIT $limit OFFSET $offset";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_out(200, "success", ["list" => $list, "total" => $total]);
    } catch (Exception $e) {
        json_out(500, "Database Error: " . $e->getMessage());
    }

} elseif ($action == 'audit_withdraw') {
    if (!isset($data->id) || !isset($data->status))
        json_out(400, "Missing data");

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT * FROM withdrawals WHERE id = ? FOR UPDATE");
        $stmt->execute([$data->id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$req || $req['status'] != 0) {
            $db->rollBack();
            json_out(400, "Invalid request or already processed");
        }

        $newStatus = (int) $data->status; // 1=Approve, 2=Reject
        $note = isset($data->admin_note) ? $data->admin_note : '';
        $proof_image = isset($data->proof_image) ? $data->proof_image : '';

        if ($newStatus == 2) {
            // Reject -> Refund Balance
            $stmt = $db->prepare("UPDATE assets SET balance = balance + ? WHERE user_id = ?");
            $stmt->execute([$req['amount'], $req['user_id']]);
            // Log Refund
            $stmt = $db->prepare("INSERT INTO logs_finance (user_id, type, asset_type, amount, before_val, after_val, memo) VALUES (?, 'withdraw_refund', 'balance', ?, 0, 0, ?)");
            $stmt->execute([$req['user_id'], $req['amount'], "提现驳回退款"]);
        }

        $stmt = $db->prepare("UPDATE withdrawals SET status = ?, admin_note = ?, proof_image = ? WHERE id = ?");
        $stmt->execute([$newStatus, $note, $proof_image, $data->id]);

        $db->commit();
        json_out(200, "Audit processed");
    } catch (Exception $e) {
        $db->rollBack();
        json_out(500, "Error: " . $e->getMessage());
    }

} elseif ($action == 'categories') {
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY sort ASC, id DESC");
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'save_category') {
    if (!isset($data->name))
        json_out(400, "Missing name");

    if (isset($data->id) && $data->id) {
        $stmt = $db->prepare("UPDATE categories SET name=?, sort=?, status=? WHERE id=?");
        $stmt->execute([$data->name, isset($data->sort) ? $data->sort : 0, isset($data->status) ? $data->status : 1, $data->id]);
    } else {
        $stmt = $db->prepare("INSERT INTO categories (name, sort, status) VALUES (?, ?, ?)");
        $stmt->execute([$data->name, isset($data->sort) ? $data->sort : 0, isset($data->status) ? $data->status : 1]);
    }
    json_out(200, "Saved");

} elseif ($action == 'delete_category') {
    if (!isset($data->id))
        json_out(400, "Missing ID");
    // Check if used
    $stmt = $db->prepare("SELECT count(*) FROM products WHERE category_id = ?");
    $stmt->execute([$data->id]);
    if ($stmt->fetchColumn() > 0)
        json_out(400, "Cannot delete: Category is in use");

    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$data->id]);
    json_out(200, "Deleted");

} elseif ($action == 'products') {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $zone = isset($_GET['zone']) ? $_GET['zone'] : '';
    $catId = isset($_GET['category_id']) ? $_GET['category_id'] : '';

    $where = "WHERE 1=1";
    $params = [];
    if ($search) {
        $where .= " AND p.title LIKE ?";
        $params[] = "%$search%";
    }
    if ($zone) {
        $where .= " AND p.zone = ?";
        $params[] = $zone;
    }
    if ($catId) {
        $where .= " AND p.category_id = ?";
        $params[] = $catId;
    }

    // Count
    $stmt = $db->prepare("SELECT count(*) FROM products p $where");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Join categories to get name
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json_out(200, "success", ["list" => $stmt->fetchAll(PDO::FETCH_ASSOC), "total" => $total]);

} elseif ($action == 'reward_rules') {
    $stmt = $db->prepare("SELECT * FROM traffic_reward_rules ORDER BY min_amount ASC");
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'save_reward_rule') {
    $data = json_decode(file_get_contents("php://input"));
    if (!isset($data->min_amount) || !isset($data->ratio))
        json_out(400, "Missing data");

    if ($data->id) {
        $stmt = $db->prepare("UPDATE traffic_reward_rules SET min_amount = ?, ratio = ? WHERE id = ?");
        $stmt->execute([$data->min_amount, $data->ratio, $data->id]);
    } else {
        $stmt = $db->prepare("INSERT INTO traffic_reward_rules (min_amount, ratio) VALUES (?, ?)");
        $stmt->execute([$data->min_amount, $data->ratio]);
    }
    json_out(200, "Success");

} elseif ($action == 'delete_reward_rule') {
    $data = json_decode(file_get_contents("php://input"));
    if (!$data->id)
        json_out(400, "ID required");
    $db->prepare("DELETE FROM traffic_reward_rules WHERE id = ?")->execute([$data->id]);
    json_out(200, "Success");

} elseif ($action == 'save_product') {
    if (!isset($data->title) || !isset($data->price) || !isset($data->zone))
        json_out(400, "Missing data");

    $description = isset($data->description) ? $data->description : '';
    $traffic_ratio = isset($data->traffic_ratio) ? floatval($data->traffic_ratio) : 1.00;
    $category_id = isset($data->category_id) ? intval($data->category_id) : 0;
    $unit = isset($data->unit) ? $data->unit : '件';
    $sales = isset($data->sales) ? intval($data->sales) : 0;

    if (isset($data->id) && $data->id) {
        // Update
        $sql = "UPDATE products SET title=?, price=?, traffic_ratio=?, zone=?, image=?, stock=?, sales=?, unit=?, status=?, description=?, category_id=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$data->title, $data->price, $traffic_ratio, $data->zone, $data->image, $data->stock, $sales, $unit, $data->status, $description, $category_id, $data->id]);
    } else {
        // Insert
        $sql = "INSERT INTO products (title, price, traffic_ratio, zone, image, stock, sales, unit, status, description, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$data->title, $data->price, $traffic_ratio, $data->zone, $data->image, $data->stock, $sales, $unit, isset($data->status) ? $data->status : 1, $description, $category_id]);
    }
    json_out(200, "Saved");

} elseif ($action == 'ship_order') {
    if (!isset($data->id) || !isset($data->tracking_number))
        json_out(400, "Missing data");

    $express_company = isset($data->express_company) ? $data->express_company : '';

    try {
        $stmt = $db->prepare("UPDATE orders SET status = 2, tracking_number = ?, express_company = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data->tracking_number, $express_company, $data->id]);
        json_out(200, "Order Shipped");
    } catch (Exception $e) {
        json_out(500, "Database Error: " . $e->getMessage());
    }

} elseif ($action == 'update_order') {
    if (!isset($data->id))
        json_out(400, "Missing ID");

    $fields = [];
    $values = [];

    if (isset($data->status)) {
        $fields[] = "status = ?";
        $values[] = intval($data->status);
    }
    if (isset($data->admin_remark)) {
        $fields[] = "admin_remark = ?";
        $values[] = $data->admin_remark;
    }

    if (empty($fields))
        json_out(200, "Nothing to update");

    $values[] = $data->id;
    $sql = "UPDATE orders SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    json_out(200, "Order Updated");

} elseif ($action == 'orders') {
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $export = isset($_GET['export']) ? $_GET['export'] : 0;

    // Search Filter
    $whereSnippet = "1=1";
    $params = [];
    if ($search) {
        // Basic fields + Product title check via subquery logic inside WHERE or just simpler approach
        // Simplified SQL string construction for the snippet
        $whereSnippet .= " AND (u.mobile LIKE ? OR u.nickname LIKE ? OR o.receiver_info LIKE ? OR o.order_sn LIKE ? OR EXISTS (SELECT 1 FROM orders o2 JOIN products p2 ON o2.product_id = p2.id WHERE o2.order_sn = o.order_sn AND p2.title LIKE ?))";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
    }

    // Count (Distinct Order SN)
    if (!$export) {
        $countSql = "SELECT COUNT(DISTINCT o.order_sn) 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     WHERE $whereSnippet";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
    }

    $sql_sn = "SELECT o.order_sn 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE $whereSnippet 
               GROUP BY o.order_sn 
               ORDER BY MAX(o.id) DESC";

    if (!$export) {
        $sql_sn .= " LIMIT $limit OFFSET $offset";
    }

    $stmt = $db->prepare($sql_sn);
    $stmt->execute($params);
    $sns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $list = [];
    foreach ($sns as $sn) {
        $q = "SELECT o.*, u.mobile, u.nickname, 
                     p.title as product_title, p.image as product_image, p.price as product_price,
                     as_sale.type as as_type, as_sale.status as as_status, as_sale.reason as as_reason, as_sale.images as as_images, as_sale.created_at as as_time
              FROM orders o
              JOIN users u ON o.user_id = u.id
              JOIN products p ON o.product_id = p.id
              LEFT JOIN after_sales as_sale ON as_sale.id = (SELECT id FROM after_sales WHERE order_sn = o.order_sn ORDER BY id DESC LIMIT 1)
              WHERE o.order_sn = ?";

        $sth = $db->prepare($q);
        $sth->execute([$sn]);
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows)
            continue;

        $first = $rows[0];
        $orderData = [
            'id' => $first['id'],
            'order_sn' => $first['order_sn'],
            'user_id' => $first['user_id'],
            'mobile' => $first['mobile'],
            'nickname' => $first['nickname'],
            'receiver_info' => $first['receiver_info'],
            'remark' => $first['remark'],
            'admin_remark' => $first['admin_remark'],
            'status' => $first['status'],
            'express_company' => $first['express_company'],
            'tracking_number' => $first['tracking_number'],
            'created_at' => $first['created_at'],
            // After Sales
            'as_type' => $first['as_type'],
            'as_status' => $first['as_status'],
            'as_reason' => $first['as_reason'],
            'as_images' => $first['as_images'],
            'as_time' => $first['as_time'],
            'goods_list' => [],
            'amount' => 0
        ];

        foreach ($rows as $r) {
            $orderData['amount'] += $r['amount'];
            $orderData['goods_list'][] = [
                'title' => $r['product_title'],
                'image' => $r['product_image'],
                'quantity' => $r['quantity'],
                'amount' => $r['amount']
            ];
        }
        $orderData['amount'] = number_format($orderData['amount'], 2, '.', '');

        $list[] = $orderData;
    }

    if ($export) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_' . date('Ymd') . '.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID', '订单号', '用户手机', '商品详情', '总金额', '状态', '收货信息', '留言', '物流单号', '时间']);

        foreach ($list as $row) {
            $goodsStr = "";
            foreach ($row['goods_list'] as $g) {
                $goodsStr .= "{$g['title']} x{$g['quantity']} (¥{$g['amount']}); ";
            }

            $addr = json_decode($row['receiver_info'], true);
            $addrStr = $addr ? ($addr['name'] . ' ' . $addr['mobile'] . ' ' . $addr['address']) : '';
            $statusMap = [0 => '待支付', 1 => '待发货', 2 => '已发货', 3 => '已完成', 5 => '售后中'];

            fputcsv($output, [
                $row['id'],
                $row['order_sn'],
                $row['mobile'],
                $goodsStr,
                $row['amount'],
                $statusMap[$row['status']] ?? '未知',
                $addrStr,
                $row['remark'],
                $row['tracking_number'],
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    json_out(200, "success", ["list" => $list, "total" => $total]);

} elseif ($action == 'banners') {
    $stmt = $db->prepare("SELECT * FROM banners ORDER BY sort DESC, id DESC");
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'add_banner') {
    if (!isset($data->image))
        json_out(400, "Image required");

    $sort = isset($data->sort) ? intval($data->sort) : 0;

    $stmt = $db->prepare("INSERT INTO banners (image, sort, status) VALUES (?, ?, 1)");
    $stmt->execute([$data->image, $sort]);
    json_out(200, "Added");

} elseif ($action == 'delete_banner') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $db->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    json_out(200, "Deleted");

} elseif ($action == 'upload') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0)
        json_out(400, "Upload failed");

    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed))
        json_out(400, "Invalid file type");

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $filename = date('YmdHis') . '_' . rand(1000, 9999) . '.' . $ext;
    $target = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Return relative URL assuming api is in /backend/api and uploads in /backend/uploads
        // The frontend expects full URL or path. Let's return path relative to domain root.
        // If domain is ref.tajian.cc, and file is in backend/uploads...
        $url = '/backend/uploads/' . $filename;
        json_out(200, "success", ["url" => $url]);
    } else {
        json_out(500, "Save failed");
    }

} elseif ($action == 'rules') {
    $stmt = $db->prepare("SELECT * FROM acceleration_rules ORDER BY min_performance ASC");
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'save_rule') {
    if (!isset($data->min_performance) || !isset($data->daily_bonus))
        json_out(400, "Missing data");

    $min = floatval($data->min_performance);
    $bonus = floatval($data->daily_bonus);

    if (isset($data->id) && $data->id) {
        $stmt = $db->prepare("UPDATE acceleration_rules SET min_performance = ?, daily_bonus = ? WHERE id = ?");
        $stmt->execute([$min, $bonus, $data->id]);
    } else {
        $stmt = $db->prepare("INSERT INTO acceleration_rules (min_performance, daily_bonus) VALUES (?, ?)");
        $stmt->execute([$min, $bonus]);
    }
    json_out(200, "Saved");

} elseif ($action == 'delete_rule') {
    if (!isset($data->id))
        json_out(400, "Missing ID");

    $stmt = $db->prepare("DELETE FROM acceleration_rules WHERE id = ?");
    $stmt->execute([$data->id]);
    json_out(200, "Deleted");

} elseif ($action == 'change_password') {
    if (!isset($data->old_password) || !isset($data->new_password))
        json_out(400, "Missing data");

    $admin_id = $_SESSION['admin_id'];
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($data->old_password, $admin['password'])) {
        json_out(400, "原密码错误");
    }

    $newHash = password_hash($data->new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $stmt->execute([$newHash, $admin_id]);

    json_out(200, "Password changed");

} elseif ($action == 'articles') {
    $stmt = $db->prepare("SELECT * FROM articles ORDER BY id DESC");
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'save_article') {
    if (!isset($data->title) || !isset($data->content))
        json_out(400, "Missing data");

    $status = isset($data->status) ? intval($data->status) : 1;

    if (isset($data->id) && $data->id) {
        $stmt = $db->prepare("UPDATE articles SET title = ?, content = ?, status = ? WHERE id = ?");
        $stmt->execute([$data->title, $data->content, $status, $data->id]);
    } else {
        $stmt = $db->prepare("INSERT INTO articles (title, content, status) VALUES (?, ?, ?)");
        $stmt->execute([$data->title, $data->content, $status]);
    }
    json_out(200, "Saved");

} elseif ($action == 'delete_article') {
    if (!isset($data->id))
        json_out(400, "Missing ID");
    $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$data->id]);
    json_out(200, "Deleted");

} elseif ($action == 'chat_list') {
    // List conversations.
    // Group by user_id. Get latest message time. Count unread (sender=user, is_read=0).
    // Join users table for nickname/avatar.
    $sql = "SELECT m.user_id, u.nickname, u.mobile, MAX(m.created_at) as last_time,
                   (SELECT COUNT(*) FROM chat_messages WHERE user_id = m.user_id AND sender = 'user' AND is_read = 0) as unread
            FROM chat_messages m
            LEFT JOIN users u ON m.user_id = u.id
            GROUP BY m.user_id
            ORDER BY last_time DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'chat_history') {
    $uid = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if (!$uid)
        json_out(400, "Missing User ID");

    // Fetch messages
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$uid]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark as read
    $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND sender = 'user'")->execute([$uid]);

    json_out(200, "success", $list);

} elseif ($action == 'chat_reply') {
    if (!isset($data->user_id) || (!$data->content && !$data->image))
        json_out(400, "Missing data");

    $type = $data->type ?? 'text'; // 'text' or 'image'
    $content = $data->content; // If image type, content is url

    $stmt = $db->prepare("INSERT INTO chat_messages (user_id, admin_id, type, content, sender, created_at) VALUES (?, ?, ?, ?, 'admin', NOW())");
    $stmt->execute([$data->user_id, $_SESSION['admin_id'], $type, $content]);

    json_out(200, "Sent");

} elseif ($action == 'nav_items') {
    $stmt = $db->prepare("SELECT * FROM nav_items ORDER BY sort ASC, id ASC");
    $stmt->execute();
    json_out(200, "success", $stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action == 'save_nav_item') {
    if (!isset($data->name) || !isset($data->icon))
        json_out(400, "Missing data");

    $type = isset($data->type) ? intval($data->type) : 1;
    $sort = isset($data->sort) ? intval($data->sort) : 0;
    $status = isset($data->status) ? intval($data->status) : 1;
    $content = isset($data->content) ? $data->content : '';

    if (isset($data->id) && $data->id) {
        $stmt = $db->prepare("UPDATE nav_items SET name=?, icon=?, type=?, content=?, sort=?, status=? WHERE id=?");
        $stmt->execute([$data->name, $data->icon, $type, $content, $sort, $status, $data->id]);
    } else {
        $stmt = $db->prepare("INSERT INTO nav_items (name, icon, type, content, sort, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data->name, $data->icon, $type, $content, $sort, $status]);
    }
    json_out(200, "Saved");

} elseif ($action == 'delete_nav_item') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $db->prepare("DELETE FROM nav_items WHERE id = ?")->execute([$id]);
    json_out(200, "Deleted");

} else {
    json_out(404, "Action not found");
}
?>