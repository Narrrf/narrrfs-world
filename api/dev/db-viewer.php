<?php
// 🧠 SQL Junior 5.0 – Live Table Viewer
$path = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ DB Connection Failed: " . $e->getMessage());
}

$tables = ['tbl_users', 'tbl_user_roles'];
echo "<h1>🧠 Narrrfs World DB Viewer</h1>";

foreach ($tables as $table) {
    echo "<h2>📊 Table: $table</h2>";
    $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) === 0) {
        echo "<p><i>No rows found.</i></p>";
        continue;
    }

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>";
    foreach (array_keys($rows[0]) as $col) {
        echo "<th>$col</th>";
    }
    echo "</tr>";

    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table><br>";
}
?>
