// 🧠 Cheese Architect Tetris v1.2 – Full Sync + Leaderboard Ready
document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;
  const colors = ["", "#fcd34d", "#4ade80", "#60a5fa", "#f472b6"];

  let score = 0;
  let gameInterval;
  const grid = Array.from({ length: gridHeight }, () => Array(gridWidth).fill(0));

  const pieces = [
    [[1, 1, 1], [0, 1, 0]],   // T
    [[2, 2], [2, 2]],         // O
    [[0, 3, 3], [3, 3, 0]],   // S
    [[4, 4, 0], [0, 4, 4]]    // Z
  ];

  function randomPiece() {
    return pieces[Math.floor(Math.random() * pieces.length)];
  }

  let current = {
    shape: randomPiece(),
    row: 0,
    col: 3
  };

  function drawBlock(x, y, color) {
    context.fillStyle = color;
    context.fillRect(x * blockSize, y * blockSize, blockSize, blockSize);
    context.strokeStyle = "#1f2937";
    context.strokeRect(x * blockSize + 0.5, y * blockSize + 0.5, blockSize - 1, blockSize - 1);
  }

  function draw() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.save();
    context.translate(0.5, 0.5);

    // Draw grid
    grid.forEach((row, y) =>
      row.forEach((val, x) => val && drawBlock(x, y, colors[val]))
    );

    // Draw current piece
    current.shape.forEach((row, y) =>
      row.forEach((val, x) => {
        if (val) drawBlock(current.col + x, current.row + y, colors[val]);
      })
    );

    context.restore();
  }

  function collide(shape, row, col) {
    return shape.some((r, y) =>
      r.some((v, x) => {
        const ny = row + y;
        const nx = col + x;
        return v && (ny >= gridHeight || nx < 0 || nx >= gridWidth || (ny >= 0 && grid[ny][nx]));
      })
    );
  }

  function merge() {
    current.shape.forEach((row, y) =>
      row.forEach((val, x) => {
        if (val) grid[current.row + y][current.col + x] = val;
      })
    );
  }

  function clearLines() {
    let lines = 0;
    for (let y = gridHeight - 1; y >= 0; y--) {
      if (grid[y].every(v => v !== 0)) {
        grid.splice(y, 1);
        grid.unshift(Array(gridWidth).fill(0));
        lines++;
        y++; // Check same line again
      }
    }
    if (lines > 0) {
      score += lines * 10;
      scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
    }
  }

  function drop() {
    if (!collide(current.shape, current.row + 1, current.col)) {
      current.row++;
    } else {
      merge();
      clearLines();
      current = { shape: randomPiece(), row: 0, col: 3 };
      if (collide(current.shape, current.row, current.col)) {
        alert(`🧠 Game Over! You earned $${score} DSPOINC!`);
        clearInterval(gameInterval);
        onTetrisGameOver(score);
        loadLeaderboard();
      }
    }
    draw();
  }

  function rotatePiece() {
    const rotated = current.shape[0].map((_, i) =>
      current.shape.map(row => row[i]).reverse()
    );
    if (!collide(rotated, current.row, current.col)) {
      current.shape = rotated;
    }
  }

  // 🧬 Save score to PHP + SQLite
  function onTetrisGameOver(finalScore) {
    const wallet = localStorage.getItem("walletAddress");
    const discordId = localStorage.getItem("discord_id");
    const discordName = localStorage.getItem("discord_name");

    if (!wallet) {
      console.warn("Wallet not found. Score not saved.");
      return;
    }

    fetch("/api/dev/save-score.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ wallet, score: finalScore, discord_id: discordId, discord_name: discordName })
    })
      .then(res => res.json())
      .then(data => console.log("💾 Score saved:", data))
      .catch(err => console.error("Score save failed:", err));
  }

  // 🏁 Leaderboard display
  async function loadLeaderboard() {
    const list = document.getElementById("leaderboard-list");
    try {
      const res = await fetch("/api/dev/get-leaderboard.php");
      if (!res.ok) throw new Error("Failed to load");
      const scores = await res.json();

      list.innerHTML = "";
      scores.forEach((entry, i) => {
        const name = entry.discord_name || `${entry.wallet.slice(0, 6)}...${entry.wallet.slice(-4)}`;
        const li = document.createElement("li");
        li.textContent = `#${i + 1} ${name} – ${entry.score} $DSPOINC`;
        list.appendChild(li);
      });
    } catch (err) {
      console.error("Leaderboard error:", err);
      list.innerHTML = "<li>❌ Could not load leaderboard</li>";
    }
  }

  // 🔐 Discord login button
  window.loginAndReload = function () {
    window.location.href =
      "https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=identify";
  };

  // 🚀 Start the game
  loadLeaderboard();
  draw();
  gameInterval = setInterval(drop, 500);

  // 🎮 Key controls
  document.addEventListener("keydown", e => {
    if (e.key === "ArrowLeft" && !collide(current.shape, current.row, current.col - 1)) current.col--;
    else if (e.key === "ArrowRight" && !collide(current.shape, current.row, current.col + 1)) current.col++;
    else if (e.key === "ArrowDown") drop();
    else if (e.key === "ArrowUp" || e.key === "w") rotatePiece();
    draw();
  });
});
