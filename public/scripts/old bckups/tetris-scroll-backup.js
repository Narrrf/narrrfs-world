// 🧠 Cheese Architect Tetris v1.0 – Genesis Scroll Certified
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
    [[1, 1, 1], [0, 1, 0]], // T
    [[2, 2], [2, 2]],       // O
    [[0, 3, 3], [3, 3, 0]], // S
    [[4, 4, 0], [0, 4, 4]]  // Z
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
    context.translate(0.5, 0.5); // fix crisp lines

    // Draw grid
    grid.forEach((row, y) =>
      row.forEach((value, x) => value && drawBlock(x, y, colors[value]))
    );

    // Draw current piece
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
        return v && (
          ny >= gridHeight ||
          nx < 0 ||
          nx >= gridWidth ||
          (ny >= 0 && grid[ny][nx])
        );
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
      current = {
        shape: randomPiece(),
        row: 0,
        col: 3
      };
      if (collide(current.shape, current.row, current.col)) {
        alert("🧠 Game Over! You earned $" + score + " DSPOINC!");
        clearInterval(gameInterval);
        onTetrisGameOver(score);
      }
    }
    draw();
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft" && !collide(current.shape, current.row, current.col - 1)) {
      current.col--;
    } else if (e.key === "ArrowRight" && !collide(current.shape, current.row, current.col + 1)) {
      current.col++;
    } else if (e.key === "ArrowDown") {
      drop();
    }
    draw();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "ArrowUp" || event.key === "w") {
      rotatePiece();
    }
  });

  function rotatePiece() {
    const rotated = current.shape[0].map((_, i) =>
      current.shape.map(row => row[i]).reverse()
    );
    if (!collide(rotated, current.row, current.col)) {
      current.shape = rotated;
    }
  }

  // 💾 Save Score When Game Ends
  function onTetrisGameOver(finalScore) {
    const wallet = localStorage.getItem("walletAddress");

    if (!wallet) {
      console.warn("Wallet not set — skipping score save.");
      return;
    }

    fetch("/api/dev/save-score.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ wallet, score: finalScore }),
    })
    .then(res => res.json())
    .then(data => console.log("💾 Tetris score saved:", data));
  }
  function onTetrisGameOver(finalScore) {
  const wallet = localStorage.getItem("walletAddress");
  const discordId = localStorage.getItem("discord_id");
  const discordName = localStorage.getItem("discord_name");

  if (!wallet) {
    console.warn("Wallet not set — skipping score save.");
    return;
  }

  fetch("/api/dev/save-score.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      wallet,
      score: finalScore,
      discord_id: discordId,
      discord_name: discordName
    }),
  })
  .then(res => res.json())
  .then(data => console.log("💾 Tetris score saved:", data));
}


  // 🧮 Main game loop
  gameInterval = setInterval(drop, 500);
});
