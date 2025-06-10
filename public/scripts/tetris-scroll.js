// 🧀 Cheese Tetris Scroll v9.8 + PNG BLOCKS (all features from perfect backup, plus PNG support)

// --- PNG Block Support simple only one template for all can be defined with new img/tetris ---
const blockImages = {};
const pieceImageMap = {
  1: "block_T.png", // --- T
  2: "block_O.png", // --- O
  3: "block_S.png", // --- S
  4: "block_Z.png", // --- Z
  5: "block_I.png", // --- I
  6: "block_BOMB.png", // --- Bomb
  7: "block_L.png", // --- L
  8: "block_J.png" // --- J
};
let allImagesLoaded = false;
let loadedCount = 0;
let domReady = false;
// Replace current tryStartTetris logic with:
function checkAndStartTetris() {
  if (allImagesLoaded && domReady) {
    startTetris();
  }
}

// Make sure this is called after either state flips:
document.addEventListener("DOMContentLoaded", () => {
  domReady = true;
  checkAndStartTetris(); // ← will only run startTetris when both are true
});

Object.entries(pieceImageMap).forEach(([key, filename]) => {
  const img = new Image();
  img.src = "http:/public/img/tetris/" + filename;
  img.onload = () => {
    loadedCount++;
    if (loadedCount === Object.keys(pieceImageMap).length) {
      allImagesLoaded = true;
      checkAndStartTetris(); // ← called here too
    }
  };
  blockImages[key] = img;
});


// 🎨 Cheese-Themed Block Colors do not work now code does so kind of backup 
const colors = [
  "#000000",    // 0 - empty
  "#FFB347",    // 1 - T (cheddar orange)
  "#FFFACD",    // 2 - O (lemon cream)
  "#EEDC82",    // 3 - S (aged parmesan)
  "#FFDEAD",    // 4 - Z (mild gouda)
  "#FFFF99",    // 5 - I (soft mozzarella)
  "#FFD700",    // 6 - bomb (keep yellow explosion)
  "#FFA500",    // 7 - L (sharp cheddar)
  "#F4C430"     // 8 - J (gruyère gold)
];

let activeExplosive = null; // track position and countdown

// 🚫 Full page scroll prevention
window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });

// 🔮 Preview canvas setup
const nextCanvas = document.getElementById("next-canvas");
const nextCtx = nextCanvas.getContext("2d");

function startTetris() {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;
  const sensitivity = 20;

  let score = 0;
  let linesClearedTotal = 0;
  let dropInterval = 500;
  const grid = Array.from({ length: gridHeight }, () => Array(gridWidth).fill(0));

  const pieces = [
    [[1, 1, 1], [0, 1, 0]],     // T
    [[2, 2], [2, 2]],           // O
    [[0, 3, 3], [3, 3, 0]],     // S
    [[4, 4, 0], [0, 4, 4]],     // Z
    [[5, 5, 5, 5]],             // I
    [[7, 0], [7, 0], [7, 7]],   // L
    [[0, 8], [0, 8], [8, 8]],   // J ← mirrored L block
    [[6]]                       // 💣
  ];

  let nextPiece = randomPiece();
  let current = {
    shape: nextPiece,
    row: 0,
    col: 3,
    timer: null
  };
  nextPiece = randomPiece();

  function randomPiece() {
    const isExplosive = Math.random() < 0.1;
    return isExplosive ? [[6]] : pieces[Math.floor(Math.random() * pieces.length)];
  }

  function explode(centerX, centerY) {
    for (let y = -1; y <= 1; y++) {
      for (let x = -1; x <= 1; x++) {
        const ny = centerY + y;
        const nx = centerX + x;
        if (grid[ny]?.[nx]) grid[ny][nx] = 0;
      }
    }
    draw();
  }

  // --- Drawing blocks: PNG if available, else color ---
  function drawBlock(x, y, val) {
    context.save();
    if (activeExplosive && activeExplosive.x === x && activeExplosive.y === y) {
      const timeElapsed = (Date.now() - activeExplosive.start) / 1000;
      const remaining = activeExplosive.countdown - timeElapsed;
      const intensity = Math.max(0, Math.min(1, 1 - remaining / activeExplosive.countdown));
      context.shadowColor = '#facc15';
      context.shadowBlur = 10 + 30 * intensity;
    }
    const img = blockImages[val];
    if (img && img.complete) {
      context.drawImage(img, x * blockSize, y * blockSize, blockSize, blockSize);
    } else {
      context.fillStyle = colors[val];
      context.fillRect(x * blockSize, y * blockSize, blockSize, blockSize);
      context.strokeStyle = "#1f2937";
      context.strokeRect(x * blockSize + 0.5, y * blockSize + 0.5, blockSize - 1, blockSize - 1);
    }
    context.restore();
  }

  // --- Main draw loop ---
  function draw() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.save();
    context.translate(0.5, 0.5);
    grid.forEach((row, y) =>
      row.forEach((val, x) => {
        if (val) drawBlock(x, y, val);
      })
    );
    current.shape.forEach((row, y) =>
      row.forEach((val, x) => {
        if (val) drawBlock(current.col + x, current.row + y, val);
      })
    );
    context.restore();
  }

  // --- Next block preview: PNG if available, else color ---
  function renderNextBlock(shape) {
    if (!nextCtx || !shape) return;
    nextCtx.clearRect(0, 0, nextCanvas.width, nextCanvas.height);
    const offsetX = Math.floor((4 - shape[0].length) / 2);
    const offsetY = Math.floor((4 - shape.length) / 2);
    shape.forEach((row, y) => {
      row.forEach((val, x) => {
        if (val) {
          const img = blockImages[val];
          if (img && img.complete) {
            nextCtx.drawImage(img, (x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
          } else {
            nextCtx.fillStyle = colors[val];
            nextCtx.fillRect((x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
            nextCtx.strokeStyle = "#1f2937";
            nextCtx.strokeRect((x + offsetX) * 20 + 0.5, (y + offsetY) * 20 + 0.5, 19, 19);
          }
        }
      });
    });
    // 💣 Bomb detection & warning toggle
    const bombWarning = document.getElementById("bomb-warning");
    const isBomb = shape.length === 1 && shape[0].length === 1 && shape[0][0] === 6;
    if (bombWarning) {
      bombWarning.classList.toggle("hidden", !isBomb);
    }
  }

// ... existing code from perfect backup continues unchanged ... 

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
            // 🧠 Check for bomb BEFORE removing the row
            if (grid[y].includes(6)) {
              showBombDefusedPopup();
            }
      
            grid.splice(y, 1);
            grid.unshift(Array(gridWidth).fill(0));
            lines++;
            y++; // Re-check same row index
          }
        }
      
        if (lines > 0) {
          linesClearedTotal += lines;
          score += lines * 10;
          scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
      
          // ⏩ Speed up every 20 lines
          if (linesClearedTotal % 20 === 0) {
            dropInterval = Math.max(100, dropInterval - 50);
            clearInterval(gameInterval);
            gameInterval = setInterval(drop, dropInterval);
          }
        }
      }
      
      // 🔔 Defused popup UI logic
      function showBombDefusedPopup() {
        const popup = document.getElementById("bomb-defused-popup");
        if (!popup) return;
      
        popup.classList.remove("hidden");
        popup.classList.add("animate-pop");
      
        setTimeout(() => {
          popup.classList.add("hidden");
          popup.classList.remove("animate-pop");
        }, 2000);
      }
      
      
      
      function drop() {
        if (!collide(current.shape, current.row + 1, current.col)) {
          current.row++;
        } else {
          // 💣 Check if explosive piece BEFORE merge
          if (
            current.shape.length === 1 &&
            current.shape[0].length === 1 &&
            current.shape[0][0] === 6
          ) {
            const cx = current.col;
            const cy = current.row;
            const countdown = Math.floor(Math.random() * 30) + 1;
      
            activeExplosive = { x: cx, y: cy, countdown, start: Date.now() };
      
            setTimeout(() => {
              // 🧪 Verify bomb still exists before exploding
              if (grid[cy]?.[cx] === 6) {
                explode(cx, cy);
              }
              activeExplosive = null;
            }, countdown * 1000);
          }
      
          merge();
          clearLines();
      
          // 🧱 Spawn new piece
          current = {
            shape: nextPiece,
            row: 0,
            col: 3
          };
          nextPiece = randomPiece();
          renderNextBlock(nextPiece);
      
          // 🧠 Game Over check
          if (collide(current.shape, current.row, current.col)) {
            clearInterval(gameInterval);
            onTetrisGameOver(score);
      
            const modal = document.getElementById("game-over-modal");
            const finalScoreText = document.getElementById("final-score-text");
      
            if (modal && finalScoreText) {
              finalScoreText.textContent = `You earned $${score} DSPOINC`;
              modal.classList.remove("hidden");
            }
      
            if (document.getElementById("leaderboard-list")) {
              loadLeaderboard();
            }
      
            return;
          }
        }
      
        draw(); // ✅ Always redraw
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
        let wallet = localStorage.getItem("walletAddress");
        let discordId = localStorage.getItem("discord_id");
        let discordName = localStorage.getItem("discord_name");
      
        // 🛠️ Mock fallback if testing locally
        if (!discordId) {
          discordId = "1337";
          discordName = "Anonymous Mouse";
          localStorage.setItem("discord_id", discordId);
          localStorage.setItem("discord_name", discordName);
        }
      
        // ✅ For Local Testing
        if (!wallet) {
          localStorage.setItem("walletAddress", "TestWallet123456789XYZ");
          wallet = "TestWallet123456789XYZ";
        }
      
        if (!discordId) {
          discordId = "1337";
          discordName = "Anonymous Mouse";
          localStorage.setItem("discord_id", discordId);
          localStorage.setItem("discord_name", discordName);
        }
      
        // ✅ Basic validation
        if (!wallet || wallet.length < 15 || finalScore <= 0) {
          console.warn("❌ Invalid wallet or zero score — skipping save.");
          return;
        }
      
        const payload = {
          wallet,
          score: finalScore,
          discord_id: discordId,
          discord_name: discordName
        };
      
        console.log("⏎ Sending score payload:", payload);
      
        fetch("/api/dev/save-score.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(data => {
            console.log("💾 Score saved:", data);
      
            // ✅ Force leaderboard refresh after short delay
            setTimeout(() => {
              if (document.getElementById("leaderboard-list")) {
                fetch(`/api/dev/get-leaderboard.php?t=${Date.now()}`)
                  .then(res => res.json())
                  .then(result => {
                    const scores = result.leaderboard || [];
                    const list = document.getElementById("leaderboard-list");
                    list.innerHTML = "";
      
                    const rankColors = ["text-yellow-400", "text-gray-300", "text-yellow-200"];
                    const rankEmojis = ["👑", "🥈", "🥉"];
      
                    scores.forEach((entry, i) => {
                      const name = entry.discord_name || `${entry.wallet.slice(0, 6)}...${entry.wallet.slice(-4)}`;
                      const li = document.createElement("li");
                      const emoji = rankEmojis[i] || "";
      
                      li.innerHTML = `${emoji} #${i + 1} <strong>${name}</strong> – ${entry.score} $DSPOINC`;
                      li.classList.add("animate-pop", rankColors[i] || "text-white");
                      list.appendChild(li);
                    });
                  });
              }
            }, 500);
          })
          .catch(err => console.error("Score save failed:", err));
      }
      
      
      async function loadLeaderboard() {
        const list = document.getElementById("leaderboard-list");
        if (!list) {
          console.warn("⚠️ Leaderboard element not found.");
          return;
        }
      
        try {
          const res = await fetch("/api/dev/get-leaderboard.php");
          const result = await res.json();
          const scores = result.leaderboard || [];
      
          if (!Array.isArray(scores)) throw new Error("Invalid leaderboard format");
      
          list.innerHTML = "";
      
          const rankColors = ["text-yellow-400", "text-gray-300", "text-yellow-200"];
          const rankEmojis = ["👑", "🥈", "🥉"];
      
          scores.forEach((entry, i) => {
            const name = entry.discord_name || `${entry.wallet.slice(0, 6)}...${entry.wallet.slice(-4)}`;
            const li = document.createElement("li");
            const emoji = rankEmojis[i] || "";
      
            li.innerHTML = `${emoji} #${i + 1} <strong>${name}</strong> – ${entry.score} $DSPOINC`;
            li.classList.add("animate-pop", rankColors[i] || "text-white");
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
      
      // ✅ Enhanced Mobile Touch Controls: Swipe + Hold for Fast Drop
      let touchStartX = 0, touchStartY = 0;
      let touchDropInterval = null;
      let dropHoldTimeout = null;
      
      canvas.addEventListener("touchstart", e => {
        if (e.cancelable) e.preventDefault();
        const touch = e.touches[0];
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
      
        // ⏱️ Delay fast drop until held for 1 second
        dropHoldTimeout = setTimeout(() => {
          touchDropInterval = setInterval(() => {
            drop();
            draw();
          }, 75);
        }, 1000);
      }, { passive: false });
      
      canvas.addEventListener("touchend", e => {
        clearTimeout(dropHoldTimeout);
        clearInterval(touchDropInterval);
      
        const touch = e.changedTouches[0];
        const deltaX = touch.clientX - touchStartX;
        const deltaY = touch.clientY - touchStartY;
      
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
          if (deltaX > sensitivity && !collide(current.shape, current.row, current.col + 1)) current.col++;
          else if (deltaX < -sensitivity && !collide(current.shape, current.row, current.col - 1)) current.col--;
        } else {
          if (deltaY < -sensitivity) rotatePiece();
        }
      
        draw();
      }, { passive: false });
      
      
      
      // ✅ Final game loop initialization
      loadLeaderboard();
      draw();
      renderNextBlock(nextPiece);
      gameInterval = setInterval(drop, dropInterval);
    }
  





