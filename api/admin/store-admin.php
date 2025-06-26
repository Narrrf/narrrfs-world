<?php
session_start();
require_once(__DIR__ . '/../api/auth/verify-admin.php');
if (!isAdminOrMod()) { http_response_code(403); die('Not authorized'); }

$db = new PDO("sqlite:/var/www/html/db/narrrf_world.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle add/edit/deactivate POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $db->prepare("INSERT INTO tbl_store_items (name, description, price, active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price']]);
    }
    if (isset($_POST['edit'])) {
        $stmt = $db->prepare("UPDATE tbl_store_items SET name=?, description=?, price=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['id']]);
    }
    if (isset($_POST['toggle'])) {
        $stmt = $db->prepare("UPDATE tbl_store_items SET active = NOT active WHERE id=?");
        $stmt->execute([$_POST['id']]);
    }
    if (isset($_POST['delete'])) {
        $stmt = $db->prepare("DELETE FROM tbl_store_items WHERE id=?");
        $stmt->execute([$_POST['id']]);
    }
    header("Location: store-admin.php"); exit;
}

// List all items
$items = $db->query("SELECT * FROM tbl_store_items ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Store Admin Panel</title>
    <style>
      body { font-family:sans-serif; background: #18181a; color:#ffe484;}
      table { border-collapse:collapse; width:100%; margin-bottom:2em;}
      th,td{border:1px solid #333;padding:8px;}
      tr.inactive{opacity:.5;}
      .btn{padding:4px 12px; background:#fcd34d; border:none; cursor:pointer; border-radius:6px;}
    </style>
</head>
<body>
<h2>🧀 Store Item Admin</h2>
<table>
<thead>
<tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Active</th><th>Actions</th></tr>
</thead><tbody>
<?php foreach ($items as $item): ?>
<tr class="<?= $item['active'] ? '' : 'inactive' ?>">
    <td><?= $item['id'] ?></td>
    <td><?= htmlspecialchars($item['name']) ?></td>
    <td><?= htmlspecialchars($item['description']) ?></td>
    <td><?= number_format($item['price']) ?></td>
    <td><?= $item['active'] ? '✅' : '❌' ?></td>
    <td>
        <form method="post" style="display:inline;">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <input type="hidden" name="name" value="<?= htmlspecialchars($item['name']) ?>">
            <input type="hidden" name="description" value="<?= htmlspecialchars($item['description']) ?>">
            <input type="hidden" name="price" value="<?= $item['price'] ?>">
            <button name="toggle" class="btn" type="submit"><?= $item['active'] ? 'Deactivate' : 'Activate' ?></button>
            <button name="delete" class="btn" onclick="return confirm('Delete this item?');">Delete</button>
        </form>
        <!-- Edit Inline (could also use a modal) -->
        <form method="post" style="display:inline;">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
            <input type="text" name="description" value="<?= htmlspecialchars($item['description']) ?>" required>
            <input type="number" name="price" value="<?= $item['price'] ?>" required min="0">
            <button name="edit" class="btn" type="submit">Save</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h3>Add New Item</h3>
<form method="post">
    <input name="name" placeholder="Item Name" required>
    <input name="description" placeholder="Description" required>
    <input name="price" type="number" placeholder="Price" min="0" required>
    <button name="add" class="btn" type="submit">Add</button>
</form>
</body>
</html>
