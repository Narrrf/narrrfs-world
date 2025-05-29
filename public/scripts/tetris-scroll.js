// 🚫 Full page scroll prevention (Arrow keys + WASD + Space)
window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });

document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;
  const sensitivity = 20;

  let score = 0;
  const grid = Array.from({ length: gridHeight }, () => Array(gridWidth).fill(0));

  const colors = ["", "#fcd34d", "#4ade80", "#60a5fa", "#f472b6", "#c084fc"];
  const pieces = [
    [[1, 1, 1], [0, 1, 0]],     // T
    [[2, 2], [2, 2]],           // O
    [[0, 3, 3], [3, 3, 0]],     // S
    [[4, 4, 0], [0, 4, 4]],     // Z
    [[5, 5, 5, 5]]              // I (horizontal)
  ];

  let current = {
    shape: pieces[Math.floor(Math.random() * pieces.length)],
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
      row.forEach((val, x) => val && drawBlock(x, y, colors[val]))
    );

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
      current = { shape: pieces[Math.floor(Math.random() * pieces.length)], row: 0, col: 3 };
      if (collide(current.shape, current.row, current.col)) {
        alert(`🧠 Game Over! You earned $${score} DSPOINC!`);
        clearInterval(gameInterval);
        onTetrisGameOver(score);
        loadLeaderboard();
        return;
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

  async function loadLeaderboard() {
    const list = document.getElementById("leaderboard-list");
    try {
      const res = await fetch("/api/dev/get-leaderboard.php");
      const result = await res.json();
      const scores = result.leaderboard || [];

      if (!Array.isArray(scores)) throw new Error("Invalid leaderboard format");

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

  window.loginAndReload = function () {
    window.location.href =
      "https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=identify";
  };

  // 🎮 Desktop Keyboard Controls (WASD + Arrows)
  document.addEventListener("keydown", e => {
    switch (e.key) {
      case "ArrowLeft":
      case "a":
        if (!collide(current.shape, current.row, current.col - 1)) current.col--;
        break;
      case "ArrowRight":
      case "d":
        if (!collide(current.shape, current.row, current.col + 1)) current.col++;
        break;
      case "ArrowDown":
      case "s":
        drop();
        break;
      case "ArrowUp":
      case "w":
        rotatePiece();
        break;
    }
    draw();
  }, { passive: false });

  // 📱 Touch Swipe Controls
  let touchStartX = 0, touchStartY = 0;

  canvas.addEventListener("touchstart", e => {
    if (e.cancelable) e.preventDefault();
    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
  }, { passive: false });

  canvas.addEventListener("touchend", e => {
    const touch = e.changedTouches[0];
    const deltaX = touch.clientX - touchStartX;
    const deltaY = touch.clientY - touchStartY;

    if (Math.abs(deltaX) > Math.abs(deltaY)) {
      if (deltaX > sensitivity && !collide(current.shape, current.row, current.col + 1)) current.col++;
      else if (deltaX < -sensitivity && !collide(current.shape, current.row, current.col - 1)) current.col--;
    } else {
      if (deltaY > sensitivity) drop();
      else if (deltaY < -sensitivity) rotatePiece();
    }

    draw();
  }, { passive: false });

  // ✅ Launch game loop
  loadLeaderboard();
  draw();
  const gameInterval = setInterval(drop, 500);
});
