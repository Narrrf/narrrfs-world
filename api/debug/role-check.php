<?php
$pdo = new PDO("sqlite:C:/xampp-server/htdocs/narrrfs-world/db/narrrf_world.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = $pdo->query("SELECT user_id, role_name, timestamp FROM tbl_user_roles ORDER BY timestamp DESC");
$results = $query->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>🔍 Synced Roles:</h2><pre>";
print_r($results);
echo "</pre>";
