
// 🧠 Cheese Architect Tetris v1.1 – Full Discord + Wallet Score Sync + Leaderboard
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
    [[1, 1, 1], [0, 1, 0]],
    [[2, 2], [2, 2]],
    [[0, 3, 3], [3, 3, 0]],
    [[4, 4, 0], [0, 4, 4]]
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
    grid.forEach((row, y) =>
      row.forEach((value, x) => value && drawBlock(x, y, colors[value]))
    );
    current.shape.forEach((row, y) =>
      row.forEach((value, x) => {
        if (value) drawBlock(current.col + x, current.row + y, colors[value]);
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
      row.forEach((value, x) => {
        if (value) grid[current.row + y][current.col + x] = value;
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
        y++;
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
        alert("🧠 Game Over! You earned $" + score + " DSPOINC!");
        clearInterval(gameInterval);
        onTetrisGameOver(score);
      }
    }
    draw();
  }

  function rotatePiece() {
    const rotated = current.shape[0].map((_, i) => current.shape.map(row => row[i]).reverse());
    if (!collide(rotated, current.row, current.col)) current.shape = rotated;
  }

  function onTetrisGameOver(finalScore) {
    const wallet = localStorage.getItem("walletAddress");
    const discordId = localStorage.getItem("discord_id");
    const discordName = localStorage.getItem("discord_name");
    if (!wallet) return;

    fetch("/api/dev/save-score.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ wallet, score: finalScore, discord_id: discordId, discord_name: discordName })
    }).then(res => res.json()).then(data => console.log("💾 Score saved:", data));
  }

  document.addEventListener("keydown", e => {
    if (e.key === "ArrowLeft" && !collide(current.shape, current.row, current.col - 1)) current.col--;
    else if (e.key === "ArrowRight" && !collide(current.shape, current.row, current.col + 1)) current.col++;
    else if (e.key === "ArrowDown") drop();
    else if (e.key === "ArrowUp" || e.key === "w") rotatePiece();
    draw();
  });

  function loginAndReload() {
    window.location.href = "https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=identify";
  }

  async function loadLeaderboard() {
    const res = await fetch("/api/tetris-leaderboard.php");
    const list = document.getElementById("leaderboard-list");
    if (!res.ok) return list.innerHTML = "<li>❌ Failed to load leaderboard</li>";
    const scores = await res.json();
    list.innerHTML = "";
    scores.forEach((entry, index) => {
      const user = entry.discord_name || entry.wallet.slice(0, 6) + "..." + entry.wallet.slice(-4);
      list.innerHTML += `<li>#${index + 1} ${user} – ${entry.score} $DSPOINC</li>`;
    });
  }

  loadLeaderboard();
  gameInterval = setInterval(drop, 500);
});
