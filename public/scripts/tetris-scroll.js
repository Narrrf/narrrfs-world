// 🧀 Cheese Tetris Scroll v9.8 + PNG BLOCKS (all features from perfect backup, plus PNG support)

// 🚫 Full page scroll prevention
window.addEventListener("touchmove", function(e) {
  if (e.target.closest("#tetris-canvas")) {
    e.preventDefault();
  }
}, { passive: false });

window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });

// --- PNG Block Support simple only one template for all can be defined with new img/tetris ---
let allImagesLoaded = false;
let loadedCount = 0;

const blockImages = {};
const pieceImageMap = {
  1: "block_T.png",
  2: "block_O.png",
  3: "block_S.png",
  4: "block_Z.png",
  5: "block_I.png",
  6: "block_BOMB.png",
  7: "block_L.png",
  8: "block_J.png"
};

// 🎮 Touch control variables
let touchStartX = 0;
let touchStartY = 0;
let touchStartTime = 0;
const SWIPE_THRESHOLD = 30; // Minimum distance for a swipe
const SWIPE_TIME_THRESHOLD = 300; // Maximum time for a swipe in milliseconds
const DOUBLE_TAP_THRESHOLD = 300; // Maximum time between taps for double tap
let lastTapTime = 0;

function checkAndStartTetris() {
  const btn = document.getElementById("start-tetris-btn");
  if (!btn) {
    // auto-start fallback if no button present
    window.startTetrisGame();
  }
}

Object.entries(pieceImageMap).forEach(([key, filename]) => {
  const img = new Image();
  img.src = "img/tetris/" + filename;
  img.onload = () => {
    loadedCount++;
    if (loadedCount === Object.keys(pieceImageMap).length) {
      allImagesLoaded = true;
      checkAndStartTetris(); // ✅ Ensure the game can start after loading
    }
  };
  blockImages[key] = img;
});

// Improved touch controls for Tetris
function initTouchControls(canvas, currentPiece, dropInterval) {
  let lastSwipeDirection = null;
  let lastSwipeTime = 0;
  const SWIPE_COOLDOWN = 100; // Minimum time between swipes

  canvas.addEventListener("touchstart", e => {
    if (isTetrisPaused) return; // Prevent touch controls while paused
    e.preventDefault();
    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    touchStartTime = Date.now();

    // Check for double tap (rotation)
    const currentTime = Date.now();
    if (currentTime - lastTapTime < DOUBLE_TAP_THRESHOLD) {
      rotatePiece();
      e.preventDefault();
    }
    lastTapTime = currentTime;
  }, { passive: false });

  canvas.addEventListener("touchmove", e => {
    if (isTetrisPaused) return; // Prevent touch controls while paused
    e.preventDefault();
    const touch = e.touches[0];
    const deltaX = touch.clientX - touchStartX;
    const deltaY = touch.clientY - touchStartY;
    const touchTime = Date.now() - touchStartTime;

    // Horizontal movement
    if (Math.abs(deltaX) > SWIPE_THRESHOLD) {
      if (deltaX > 0) {
        if (!collide(current.shape, current.row, current.col + 1)) {
          current.col++;
          draw();
        }
      } else {
        if (!collide(current.shape, current.row, current.col - 1)) {
          current.col--;
          draw();
        }
      }
      touchStartX = touch.clientX;
    }

    // Fast drop (swipe down)
    if (deltaY > SWIPE_THRESHOLD && touchTime < SWIPE_TIME_THRESHOLD) {
      dropInterval = 50; // Speed up dropping
    }
  }, { passive: false });

  canvas.addEventListener("touchend", e => {
    if (isTetrisPaused) return; // Prevent touch controls while paused
    e.preventDefault();
    dropInterval = 500; // Reset drop speed
  }, { passive: false });
}

window.startTetrisGame = function () {
  if (!allImagesLoaded) {
    console.warn("Assets still loading...");
    return;
  }
  startTetris(); // ← main game logic
};



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

  // 💣 Bomb glow (active countdown)
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
    context.fillStyle = colors[val] || "#FFFFFF";
    context.fillRect(x * blockSize, y * blockSize, blockSize, blockSize);

    // 👁️ Optional stroke for clarity
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
          scoreDisplay.textContent = `💰 $DSPOINC earned: ${score * 10}`;
      
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
// 🛑 Pause Logic — now mobile compatible
let isTetrisPaused = false;
let gameInterval;

const pauseBtn = document.getElementById("pause-tetris-btn");
if (pauseBtn) {
  pauseBtn.style.padding = "12px 24px";
  pauseBtn.style.fontSize = "18px";
  pauseBtn.style.touchAction = "manipulation";

  const pauseHandler = (e) => {
    e.preventDefault();

    isTetrisPaused = !isTetrisPaused;
    pauseBtn.textContent = isTetrisPaused ? "▶️ Resume" : "⏸️ Pause";

    if (isTetrisPaused) {
      clearInterval(gameInterval);
    } else {
      clearInterval(gameInterval); // always reset interval
      gameInterval = setInterval(drop, dropInterval);
      drop(); // redraw immediately
    }
  };

  pauseBtn.addEventListener("click", pauseHandler);
  pauseBtn.addEventListener("touchend", pauseHandler, { passive: false });
}


// Add keyboard event listener for movement controls
document.addEventListener("keydown", e => {
  if (isTetrisPaused) return; // Prevent movement while paused

  switch (e.key) {
    case "ArrowLeft":
    case "a":
      if (!collide(current.shape, current.row, current.col - 1)) {
        current.col--;
        draw();
      }
      break;
    case "ArrowRight":
    case "d":
      if (!collide(current.shape, current.row, current.col + 1)) {
        current.col++;
        draw();
      }
      break;
    case "ArrowDown":
    case "s":
      if (!collide(current.shape, current.row + 1, current.col)) {
        current.row++;
        draw();
      }
      break;
    case "ArrowUp":
    case "w":
    case " ":
      rotatePiece();
      draw();
      break;
  }
});

// 🧱 Drop Function
function drop() {
  if (isTetrisPaused) return; // ⛔ Early return if paused

  if (!collide(current.shape, current.row + 1, current.col)) {
    current.row++;
  } else {
    // 💣 Bomb piece logic
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
        if (grid[cy]?.[cx] === 6) {
          explode(cx, cy);
        }
        activeExplosive = null;
      }, countdown * 1000);
    }

    merge();
    clearLines();

    current = {
      shape: nextPiece,
      row: 0,
      col: 3
    };
    nextPiece = randomPiece();
    renderNextBlock(nextPiece);

if (collide(current.shape, current.row, current.col)) {
  clearInterval(gameInterval);
  gameInterval = null;
  isTetrisPaused = true;
  onTetrisGameOver(score);

  const modal = document.getElementById("game-over-modal");
  const finalScoreText = document.getElementById("final-score-text");
  const pauseBtn = document.getElementById("pause-tetris-btn");

  if (modal && finalScoreText) {
    const gameOverText = modal.querySelector('h2') || modal.querySelector('strong');
    if (gameOverText) {
      gameOverText.innerHTML = '🧠 GAME OVER';
    }
    finalScoreText.textContent = `You earned $${score * 10} DSPOINC`;
    modal.classList.remove('hidden');
cleanupTouchControls();
  }

  if (pauseBtn) {
    pauseBtn.textContent = "⏸️ Pause";
  }

  if (typeof loadCombinedLeaderboards === "function") {
    loadCombinedLeaderboards();
  }

  return;
}

  }

  draw(); // ✅ Always redraw
}

function rotatePiece() {
  if (isTetrisPaused) return; // Prevent rotation while paused

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
      
        fetch("https://narrrfs.world/api/dev/save-score.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        })
          .then(res => res.json())
          .then(data => {
            console.log("💾 Score saved:", data);
            
            // 🏆 Check for WL Role Grant
            if (data.wl_check && data.wl_check.eligible) {
              console.log("🎉 WL Role granted:", data.wl_check);
              
              // Show WL notification
              showWLNotification(data.wl_check);
            }
      
            // ✅ Force leaderboard refresh after short delay
            setTimeout(() => {
              if (document.getElementById("leaderboard-list")) {
                fetch(`https://narrrfs.world/api/dev/get-leaderboard.php?t=${Date.now()}`)
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
      
      
 
      
        // 🏆 WL Role Notification Function
        function showWLNotification(wlData) {
          // Create notification element
          const notification = document.createElement('div');
          notification.className = 'fixed top-4 right-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white p-4 rounded-lg shadow-lg z-50 transform transition-all duration-500';
          notification.style.maxWidth = '400px';
          
          const roleName = getRoleName(wlData.role_id);
          
          notification.innerHTML = `
            <div class="flex items-center space-x-3">
              <div class="text-2xl">🏆</div>
              <div>
                <div class="font-bold text-lg">WL Role Granted!</div>
                <div class="text-sm opacity-90">${roleName}</div>
                <div class="text-xs opacity-75">+${wlData.bonus_points} DSPOINC Bonus</div>
              </div>
            </div>
          `;
          
          document.body.appendChild(notification);
          
          // Animate in
          setTimeout(() => {
            notification.style.transform = 'translateX(0)';
          }, 100);
          
          // Remove after 5 seconds
          setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
              if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
              }
            }, 500);
          }, 5000);
        }
        
        // Helper function to get role name from ID
        function getRoleName(roleId) {
          const roleMap = {
            '1399651053682692208': '🧀 Cheese Hunter',
            '1332017770937847809': '🏆 Alpha Caller',
            '1332017420591697972': '🥇 Champion',
            '1332016526848692345': '👑 VIP Cheese Lord',
            '1333347801408737323': '✅ Verified'
          };
          return roleMap[roleId] || 'WL Role';
        }
      
        window.loginAndReload = function () {
          window.location.href =
            "https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=identify";
        };
      
        // 🎮 Desktop Keyboard Controls (WASD + Arrows)
        // document.addEventListener("keydown", e => { // This block is now handled by the global keydown listener
        //   switch (e.key) {
        //     case "ArrowLeft":
        //     case "a":
        //       if (!collide(current.shape, current.row, current.col - 1)) current.col--;
        //       break;
        //     case "ArrowRight":
        //     case "d":
        //       if (!collide(current.shape, current.row, current.col + 1)) current.col++;
        //       break;
        //     case "ArrowDown":
        //     case "s":
        //       drop();
        //       break;
        //     case "ArrowUp":
        //     case "w":
        //       rotatePiece();
        //       break;
        //   }
        //   draw();
        // }, { passive: false });
      
      // ✅ Enhanced Mobile Touch Controls: Swipe + Hold for Fast Drop
// === TETRIS: Enhanced Full-Screen Touch Controls with Scroll Prevention ===
let touchStartX = 0, touchStartY = 0;
let touchDropInterval = null;
let dropHoldTimeout = null;
let heldDown = false;
let tetrisScrollLocked = false;
const sensitivity = 20; // Set your swipe threshold here

// Helper: Lock/unlock scrolling for full screen (mobile fix)
function lockTetrisScroll() {
  if (!tetrisScrollLocked) {
    document.body.style.overflow = "hidden";
    tetrisScrollLocked = true;
  }
}

function unlockTetrisScroll() {
  if (tetrisScrollLocked) {
    document.body.style.overflow = "";
    tetrisScrollLocked = false;
  }
}

// Listen anywhere on screen!
document.addEventListener("touchstart", e => {
  // Don't handle touch events if game is paused or over
  if (isTetrisPaused || !gameInterval) return;
  
  if (e.cancelable) e.preventDefault();
  lockTetrisScroll();

  const touch = e.touches[0];
  touchStartX = touch.clientX;
  touchStartY = touch.clientY;

  heldDown = false;

  // Clear any existing intervals first
  clearTimeout(dropHoldTimeout);
  clearInterval(touchDropInterval);

  dropHoldTimeout = setTimeout(() => {
    if (!isTetrisPaused && gameInterval) {
      heldDown = true;
      touchDropInterval = setInterval(() => {
        if (!isTetrisPaused && gameInterval) {
          drop();
          draw();
        }
      }, 75);
    }
  }, 500);
}, { passive: false });

document.addEventListener("touchend", e => {
  // Always clear timeouts/intervals on touch end
  clearTimeout(dropHoldTimeout);
  clearInterval(touchDropInterval);

  // Don't process swipes if game is paused or over
  if (isTetrisPaused || !gameInterval) {
    unlockTetrisScroll();
    return;
  }

  if (heldDown) {
    heldDown = false;
    unlockTetrisScroll();
    return;
  }

  const touch = e.changedTouches[0];
  const deltaX = touch.clientX - touchStartX;
  const deltaY = touch.clientY - touchStartY;

  if (Math.abs(deltaX) > Math.abs(deltaY)) {
    if (deltaX > sensitivity && !collide(current.shape, current.row, current.col + 1)) {
      current.col++;
      draw();
    } else if (deltaX < -sensitivity && !collide(current.shape, current.row, current.col - 1)) {
      current.col--;
      draw();
    }
  } else {
    if (deltaY < -sensitivity) {
      rotatePiece(); // swipe up = rotate
      draw();
    }
    // Optionally enable quick drop on swipe down:
    // else if (deltaY > sensitivity) {
    //   while (!collide(current.shape, current.row + 1, current.col)) {
    //     current.row++;
    //   }
    //   draw();
    // }
  }

  unlockTetrisScroll();
}, { passive: false });

// Clean up touch controls on game over
function cleanupTouchControls() {
  clearTimeout(dropHoldTimeout);
  clearInterval(touchDropInterval);
  heldDown = false;
  unlockTetrisScroll();
}


      
      // ✅ Final game loop initialization
      draw();
      renderNextBlock(nextPiece);
      gameInterval = setInterval(drop, dropInterval);
    }
  





