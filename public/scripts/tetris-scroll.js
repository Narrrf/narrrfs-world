// 🧠 Cheese Architect Tetris v1.3 – Mobile Touch Fix + Leaderboard Bug Patch
document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;

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

  if (!wallet || wallet === "null") {
    console.warn("❌ Wallet missing — score will not be saved.");
    return;
  }

  if (!discordName || discordName === "null") {
    console.warn("⚠️ Discord name missing — fallback to wallet-only save.");
  }

  fetch("/api/dev/save-score.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      wallet,
      score: finalScore,
      discord_id: discordId,
      discord_name: discordName
    })
  })
    .then(res => {
      if (!res.ok) throw new Error("Save failed: HTTP " + res.status);
      return res.json();
    })
    .then(data => console.log("💾 Score saved:", data))
    .catch(err => console.error("❌ Score save error:", err));
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
      const name = (entry.discord_name && entry.discord_name !== "null")
        ? entry.discord_name
        : `${entry.wallet.slice(0, 6)}...${entry.wallet.slice(-4)}`;
      const li = document.createElement("li");
      li.textContent = `#${i + 1} ${name} – ${entry.score} $DSPOINC`;
      list.appendChild(li);
    });

  } catch (err) {
    console.error("Leaderboard error:", err);
    list.innerHTML = "<li>❌ Could not load leaderboard</li>";
  }
}

// 🎮 Desktop key controls
document.addEventListener("keydown", e => {
  if (e.key === "ArrowLeft" || e.key === "a") {
    if (!collide(current.shape, current.row, current.col - 1)) current.col--;
  } else if (e.key === "ArrowRight" || e.key === "d") {
    if (!collide(current.shape, current.row, current.col + 1)) current.col++;
  } else if (e.key === "ArrowDown" || e.key === "s") {
    drop();
  } else if (e.key === "ArrowUp" || e.key === "w") {
    rotatePiece();
  }
  draw();
});

// 🚫 Prevent arrow keys from scrolling the page
window.addEventListener("keydown", function (e) {
  if (["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " "].includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });


// 📱 Mobile swipe support (fine-tuned)
let touchStartX = 0;
let touchStartY = 0;

canvas.addEventListener("touchstart", e => {
  const touch = e.touches[0];
  touchStartX = touch.clientX;
  touchStartY = touch.clientY;
}, { passive: true });

canvas.addEventListener("touchend", e => {
  const touch = e.changedTouches[0];
  const deltaX = touch.clientX - touchStartX;
  const deltaY = touch.clientY - touchStartY;

  if (Math.abs(deltaX) > Math.abs(deltaY)) {
    // Horizontal movement
    if (deltaX > 25 && !collide(current.shape, current.row, current.col + 1)) {
      current.col++;
    } else if (deltaX < -25 && !collide(current.shape, current.row, current.col - 1)) {
      current.col--;
    }
  } else {
    // Vertical movement
    if (deltaY > 25) {
      drop();
    } else if (deltaY < -25) {
      rotatePiece();
    }
  }

  draw();
}, { passive: true });


// 🚀 Start the game
loadLeaderboard();
draw();
const gameInterval = setInterval(drop, 500);
});
