<?php
// 🧠 SQL Junior 5.0 – Matrix-Style Live Table Viewer
$path = __DIR__ . '/../../db/narrrf_world.sqlite';

try {
    $pdo = new PDO("sqlite:$path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ DB Connection Failed: " . $e->getMessage());
}

// Get all tables
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
$selectedTable = $_GET['table'] ?? $tables[0] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧠 Matrix DB Viewer - Narrrfs World</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0,255,0,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0,255,0,0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(0,255,0,0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        
        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent 98%, rgba(0,255,0,0.1) 100%);
            background-size: 20px 20px;
            pointer-events: none;
            z-index: -2;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .header h1 {
            font-size: 2.5em;
            text-shadow: 0 0 10px #00ff00, 0 0 20px #00ff00;
            margin-bottom: 10px;
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from { text-shadow: 0 0 10px #00ff00, 0 0 20px #00ff00; }
            to { text-shadow: 0 0 15px #00ff00, 0 0 25px #00ff00, 0 0 35px #00ff00; }
        }
        
        .subtitle {
            color: #00cc00;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        
        .controls {
            background: rgba(0,20,0,0.8);
            border: 1px solid #00ff00;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
        }
        
        .table-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .table-selector label {
            font-weight: bold;
            color: #00ff00;
            font-size: 1.1em;
        }
        
        .table-selector select {
            background: #001100;
            color: #00ff00;
            border: 2px solid #00ff00;
            border-radius: 5px;
            padding: 10px 15px;
            font-family: 'Courier New', monospace;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        
        .table-selector select:hover {
            background: #002200;
            box-shadow: 0 0 10px rgba(0,255,0,0.5);
        }
        
        .table-selector select:focus {
            outline: none;
            box-shadow: 0 0 15px rgba(0,255,0,0.7);
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .stat-box {
            background: rgba(0,20,0,0.6);
            border: 1px solid #00aa00;
            border-radius: 5px;
            padding: 10px 15px;
            min-width: 120px;
        }
        
        .stat-label {
            color: #00cc00;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .stat-value {
            color: #00ff00;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .table-container {
            background: rgba(0,20,0,0.8);
            border: 1px solid #00ff00;
            border-radius: 8px;
            padding: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
            overflow-x: auto;
        }
        
        .table-title {
            color: #00ff00;
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 0 5px #00ff00;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0,10,0,0.9);
            border-radius: 5px;
            overflow: hidden;
        }
        
        th {
            background: #002200;
            color: #00ff00;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #00ff00;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 1px;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #004400;
            color: #00cc00;
            font-size: 0.9em;
            word-break: break-word;
            max-width: 200px;
        }
        
        tr:hover {
            background: rgba(0,255,0,0.1);
        }
        
        .empty-message {
            text-align: center;
            color: #00aa00;
            font-style: italic;
            padding: 40px;
            font-size: 1.1em;
        }
        
        .loading {
            text-align: center;
            color: #00ff00;
            padding: 40px;
            font-size: 1.2em;
        }
        
        .error {
            background: rgba(255,0,0,0.2);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .cheese-icon {
            display: inline-block;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -3;
            opacity: 0.1;
        }
        
        .matrix-rain::before {
            content: '01';
            position: absolute;
            top: -100%;
            left: 0;
            width: 100%;
            height: 200%;
            background: linear-gradient(transparent, #00ff00, transparent);
            animation: matrix-fall 10s linear infinite;
        }
        
        @keyframes matrix-fall {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header h1 { font-size: 2em; }
            .table-selector { flex-direction: column; align-items: stretch; }
            .table-selector select { min-width: auto; }
            .stats { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="matrix-rain"></div>
    
    <div class="container">
        <div class="header">
            <h1><span class="cheese-icon">🧠</span> Matrix DB Viewer <span class="cheese-icon">🧀</span></h1>
            <div class="subtitle">Narrrfs World Database - Genesis 12.0</div>
        </div>
        
        <div class="controls">
            <div class="table-selector">
                <label for="table">Select Table:</label>
                <select name="table" id="table" onchange="loadTableData(this.value)">
                    <?php foreach ($tables as $table): ?>
                        <option value="<?= htmlspecialchars($table) ?>" <?= $selectedTable === $table ? 'selected' : '' ?>>
                            <?= htmlspecialchars($table) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="tableStats">
                <?php if ($selectedTable): ?>
                    <?php
                    try {
                        $rowCount = $pdo->query("SELECT COUNT(*) FROM $selectedTable")->fetchColumn();
                        $columns = $pdo->query("PRAGMA table_info($selectedTable)")->fetchAll(PDO::FETCH_ASSOC);
                        $columnCount = count($columns);
                    } catch (Exception $e) {
                        $rowCount = 0;
                        $columnCount = 0;
                    }
                    ?>
                    <div class="stats">
                        <div class="stat-box">
                            <div class="stat-label">Table</div>
                            <div class="stat-value"><?= htmlspecialchars($selectedTable) ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Rows</div>
                            <div class="stat-value"><?= number_format($rowCount) ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Columns</div>
                            <div class="stat-value"><?= $columnCount ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Total Tables</div>
                            <div class="stat-value"><?= count($tables) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="table-container">
            <div id="tableContent">
                <?php if ($selectedTable): ?>
                    <div class="table-title">📊 <?= htmlspecialchars($selectedTable) ?></div>
                    <?php
                    try {
                        $rows = $pdo->query("SELECT * FROM $selectedTable LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($rows) === 0) {
                            echo '<div class="empty-message">No data found in this table.</div>';
                        } else {
                            echo '<table>';
                            echo '<thead><tr>';
        foreach (array_keys($rows[0]) as $col) {
                                echo '<th>' . htmlspecialchars($col) . '</th>';
        }
                            echo '</tr></thead>';
                            echo '<tbody>';

        foreach ($rows as $row) {
                                echo '<tr>';
            foreach ($row as $value) {
                                    $displayValue = $value ?? '';
                                    if (is_string($displayValue) && strlen($displayValue) > 50) {
                                        $displayValue = substr($displayValue, 0, 47) . '...';
                                    }
                                    echo '<td>' . htmlspecialchars($displayValue) . '</td>';
                                }
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            
                            if (count($rows) >= 1000) {
                                echo '<div style="text-align: center; margin-top: 15px; color: #00aa00; font-style: italic;">Showing first 1000 rows...</div>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="error">Error loading table: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                <?php else: ?>
                    <div class="empty-message">Select a table to view data.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Add some matrix-style effects
        document.addEventListener('DOMContentLoaded', function() {
            // Matrix rain effect
            const canvas = document.createElement('canvas');
            canvas.style.position = 'fixed';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '-4';
            canvas.style.opacity = '0.05';
            document.body.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            const matrix = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789@#$%^&*()*&^%+-/~{[|`]}";
            const matrixArray = matrix.split("");
            
            const fontSize = 10;
            const columns = canvas.width / fontSize;
            const drops = [];
            
            for (let x = 0; x < columns; x++) {
                drops[x] = 1;
            }
            
            function draw() {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.04)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                ctx.fillStyle = '#0F0';
                ctx.font = fontSize + 'px monospace';
                
                for (let i = 0; i < drops.length; i++) {
                    const text = matrixArray[Math.floor(Math.random() * matrixArray.length)];
                    ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                    
                    if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                        drops[i] = 0;
                    }
                    drops[i]++;
                }
            }
            
            setInterval(draw, 35);
            
            // Resize canvas on window resize
            window.addEventListener('resize', function() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            });
        });
        
        // AJAX function to load table data
        async function loadTableData(tableName) {
            const tableContent = document.getElementById('tableContent');
            const tableStats = document.getElementById('tableStats');
            
            // Show loading state
            tableContent.innerHTML = '<div class="loading">Loading table data...</div>';
            tableStats.innerHTML = '<div class="loading">Loading stats...</div>';
            
            try {
                // Load table data
                const response = await fetch(`/api/dev/db-viewer-data.php?table=${encodeURIComponent(tableName)}`);
                const data = await response.json();
                
                if (data.success) {
                    // Update table content
                    tableContent.innerHTML = data.html;
                    
                    // Update stats
                    tableStats.innerHTML = `
                        <div class="stats">
                            <div class="stat-box">
                                <div class="stat-label">Table</div>
                                <div class="stat-value">${data.tableName}</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Rows</div>
                                <div class="stat-value">${data.rowCount.toLocaleString()}</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Columns</div>
                                <div class="stat-value">${data.columnCount}</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Total Tables</div>
                                <div class="stat-value">${data.totalTables}</div>
                            </div>
                        </div>
                    `;
                } else {
                    tableContent.innerHTML = `<div class="error">Error loading table: ${data.error}</div>`;
                    tableStats.innerHTML = '';
                }
            } catch (error) {
                tableContent.innerHTML = `<div class="error">Error loading table: ${error.message}</div>`;
                tableStats.innerHTML = '';
            }
        }
    </script>
</body>
</html>
