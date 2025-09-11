// 🧀 Cheese Tetris Scroll v9.8 + PNG BLOCKS (all features from perfect backup, plus PNG support)

// 🔧 MOBILE INITIALIZATION
let isMobileDevice = false;

// Detect mobile device
if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
  isMobileDevice = true;
  console.log('📱 Mobile device detected');
  
  // Add mobile-specific meta viewport if not present
  if (!document.querySelector('meta[name="viewport"]')) {
    const viewport = document.createElement('meta');
    viewport.name = 'viewport';
    viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    document.head.appendChild(viewport);
    console.log('📱 Added mobile viewport meta tag');
  }
}

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

// 🎵 TETRIS SOUND SYSTEM - Professional Web Audio API sounds
class TetrisSoundManager {
  constructor() {
    this.audioContext = null;
    this.sounds = {};
    this.initAudio();
  }

  initAudio() {
    try {
      this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
      console.log('🎵 Tetris Sound System initialized');
    } catch (error) {
      console.warn('🎵 Audio not supported:', error);
    }
  }

  // Generate professional sound effects using Web Audio API
  playSound(type) {
    if (!this.audioContext) return;

    const oscillator = this.audioContext.createOscillator();
    const gainNode = this.audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(this.audioContext.destination);

    // Professional sound design
    switch (type) {
      case 'piecePlace':
        // Soft landing sound - low frequency with quick decay
        oscillator.frequency.setValueAtTime(220, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(110, this.audioContext.currentTime + 0.1);
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.15);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.15);
        break;

      case 'lineClear':
        // Satisfying clear sound - ascending chord
        oscillator.frequency.setValueAtTime(440, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(880, this.audioContext.currentTime + 0.2);
        oscillator.type = 'triangle';
        gainNode.gain.setValueAtTime(0.4, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.3);
        break;

      case 'levelUp':
        // Triumphant level up sound - ascending scale
        oscillator.frequency.setValueAtTime(523, this.audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659, this.audioContext.currentTime + 0.1); // E5
        oscillator.frequency.setValueAtTime(784, this.audioContext.currentTime + 0.2); // G5
        oscillator.type = 'square';
        gainNode.gain.setValueAtTime(0.5, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.4);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.4);
        break;

      case 'gameOver':
        // Dramatic game over sound - descending tone
        oscillator.frequency.setValueAtTime(440, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(110, this.audioContext.currentTime + 0.5);
        oscillator.type = 'sawtooth';
        gainNode.gain.setValueAtTime(0.6, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.6);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + 0.6);
        break;
    }
  }
}

// Initialize Tetris sound manager
const tetrisSounds = new TetrisSoundManager();

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
  } else {
    // 🔧 MOBILE FIX: Ensure button is clickable on mobile
    btn.style.touchAction = "manipulation";
    btn.style.webkitTapHighlightColor = "transparent";
    
    // Add mobile-specific event listeners
    if ('ontouchstart' in window) {
      console.log('📱 Mobile device detected, adding touch event listeners');
      btn.addEventListener('touchstart', (e) => {
        e.preventDefault();
        console.log('📱 Mobile Tetris start button touched');
        window.startTetrisGame();
        btn.disabled = true;
        btn.textContent = "🕹️ Playing...";
      }, { passive: false });
    }
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
      
      // 🔧 MOBILE INITIALIZATION
      if (isMobileDevice) {
        console.log('📱 All Tetris images loaded, mobile initialization complete');
        
        // Ensure mobile-specific setup is complete
        setTimeout(() => {
          const canvas = document.getElementById('tetris-canvas');
          const startBtn = document.getElementById('start-tetris-btn');
          
          if (canvas && startBtn) {
            console.log('📱 Mobile Tetris elements ready');
            
            // Test mobile functionality
            if (typeof window.testMobileTetris === 'function') {
              window.testMobileTetris();
            }
          }
        }, 500);
      }
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
    }

    // Vertical movement (quick drop)
    if (deltaY > SWIPE_THRESHOLD) {
      while (!collide(current.shape, current.row + 1, current.col)) {
        current.row++;
      }
      draw();
    }
  }, { passive: false });
}

window.startTetrisGame = function () {
  console.log('🎮 startTetrisGame called');
  
  if (!allImagesLoaded) {
    console.warn("Assets still loading...");
    return;
  }
  
  // 🔧 MOBILE FIX: Ensure canvas is properly sized for mobile
  const canvas = document.getElementById("tetris-canvas");
  if (canvas) {
    // Force canvas size for mobile compatibility
    canvas.width = 200;
    canvas.height = 400;
    canvas.style.width = '200px';
    canvas.style.height = '400px';
    
    // Mobile-specific canvas settings
    if ('ontouchstart' in window) {
      console.log('📱 Mobile canvas setup');
      canvas.style.touchAction = 'none';
      canvas.style.webkitUserSelect = 'none';
      canvas.style.userSelect = 'none';
    }
  }
  
  console.log('🚀 Starting Tetris game...');
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
const nextCtx = nextCanvas?.getContext("2d");

function startTetris() {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");

  const gridWidth = 10;
  const gridHeight = 20;
  const blockSize = 20;

  let score = 0;
  let linesClearedTotal = 0;
  let piecesDropped = 0;
  let tetrisClears = 0;
  let dropInterval = 500;
  const grid = Array.from({ length: gridHeight }, () => Array(gridWidth).fill(0));
  
  // 🏆 Achievement tracking to prevent duplicate checks
  let achievementsCheckedThisGame = new Set();
  
  // 🎉 Achievement popups array (Inside Game Scope)
  let achievementPopups = [];

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
        if (ny >= 0 && ny < gridHeight && nx >= 0 && nx < gridWidth && grid[ny]?.[nx]) {
          grid[ny][nx] = 0;
        }
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
    
    // 🏆 Draw achievement popups on canvas (like Space Invaders)
    drawAchievementPopups();
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
          piecesDropped++; // Track pieces dropped
        }
      
      function clearLines() {
        let lines = 0;
        for (let y = gridHeight - 1; y >= 0; y--) {
          if (grid[y].every(v => v !== 0)) {
            // 🧠 Check for bomb BEFORE removing the row
            if (grid[y].includes(6)) {
              showBombDefusedPopup();
          score += 10; // Bonus for defusing bomb (reduced for balance)
          if (scoreDisplay) {
            scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
          }
            }
      
            grid.splice(y, 1);
            grid.unshift(Array(gridWidth).fill(0));
            lines++;
            y++; // Re-check same row index
          }
        }
      
        if (lines > 0) {
          // 🎵 Play line clear sound
          tetrisSounds.playSound('lineClear');
          
          // 🎵 Check for level up (every 20 lines)
          const oldLevel = Math.floor(linesClearedTotal / 20);
          linesClearedTotal += lines;
          const newLevel = Math.floor(linesClearedTotal / 20);
          
          if (newLevel > oldLevel) {
            tetrisSounds.playSound('levelUp');
            console.log(`🎵 Level up! Now at level ${newLevel}`);
          }
          
          // Use database configuration for DSPOINC calculation
          score += lines * 2; // Season 3: 2 DSPOINC per line (balanced for ~5k max)
          
          // 🏆 Track Tetris clears (4 lines at once)
          if (lines === 4) {
            tetrisClears++;
            console.log('🏆 Tetris clear! Total tetris clears:', tetrisClears);
          }
          
          // 🏆 Check achievements immediately when lines are cleared
          // This gives players instant feedback when they unlock achievements
          console.log('🧩 Lines cleared! Checking achievements...', { linesClearedTotal, score, tetrisClears });
          checkTetrisAchievements(localStorage.getItem('discord_id') || '1337', score, linesClearedTotal, Math.floor(linesClearedTotal / 20), piecesDropped, tetrisClears);
          
      if (scoreDisplay) {
          scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
      }
      
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
    // 🎵 Play piece placement sound
    tetrisSounds.playSound('piecePlace');
    clearLines();

    current = {
      shape: nextPiece,
      row: 0,
      col: 3
    };
    nextPiece = randomPiece();
    renderNextBlock(nextPiece);

      // 🚨 CRITICAL: Check for game over (blocks reached top)
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
    finalScoreText.textContent = `You earned $${score} DSPOINC`;
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
        // 🎵 Play game over sound
        tetrisSounds.playSound('gameOver');
        
        let wallet = localStorage.getItem("walletAddress");
        let discordId = localStorage.getItem("discord_id");
        let discordName = localStorage.getItem("discord_name");
      
        // 🏆 Reset achievement tracking for new game
        achievementsCheckedThisGame.clear();
      
        // 🛠️ Mock fallback if testing locally
        if (!discordId) {
          discordId = "1337";
          discordName = "Anonymous Mouse";
          localStorage.setItem("discord_id", discordId);
          localStorage.setItem("discord_name", discordName);
        }
      
        if (!wallet) {
      wallet = discordId; // fallback
    }

    // 🏆 Check and unlock Tetris achievements
    console.log('🏆 Game Over Stats:', {
      discordId,
      finalScore,
      linesClearedTotal,
      levelReached: Math.floor(linesClearedTotal / 20),
      piecesDropped,
      tetrisClears
    });
    checkTetrisAchievements(discordId, finalScore, linesClearedTotal, Math.floor(linesClearedTotal / 20), piecesDropped, tetrisClears);

    // 💾 Save score to database
        const payload = {
      wallet: wallet,
      score: finalScore,
          discord_id: discordId,
      discord_name: discordName,
      game: "tetris"
        };
      
        console.log("⏎ Sending score payload:", payload);
      
    // 🌍 Environment-aware API endpoint (use relative path like Space Invaders)
    const apiUrl = '/api/dev/save-score.php';

    console.log(`🔗 API URL: ${apiUrl}`);

    fetch(apiUrl, {
          method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("✅ Score saved successfully:", data);
            // ✅ Force leaderboard refresh after short delay
            setTimeout(() => {
              if (document.getElementById("leaderboard-list")) {
            // 🌍 Environment-aware API endpoint for leaderboard (use relative path)
            const leaderboardUrl = `/api/dev/get-leaderboard.php?t=${Date.now()}`;
            console.log(`🔗 Leaderboard API URL: ${leaderboardUrl}`);

            fetch(leaderboardUrl)
              .then((response) => response.json())
              .then((data) => {
                console.log("✅ Leaderboard refreshed:", data);
                if (typeof loadCombinedLeaderboards === "function") {
                  loadCombinedLeaderboards();
                }
              })
              .catch((error) => {
                console.error("❌ Leaderboard refresh failed:", error);
              });
          }
        }, 1000);
      })
      .catch((error) => {
        console.error("❌ Score save failed:", error);
      });
  }

  // Touch controls setup
let heldDown = false;
  let dropHoldTimeout;
  let touchDropInterval;
  const sensitivity = 50;

  // 🏆 Tetris Achievement Checking Function (Inside Game Scope)
  function checkTetrisAchievements(userId, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears) {
    console.log('🏆 Checking Tetris achievements for user:', userId);
    console.log('🏆 Game stats:', { gameScore, linesCleared, levelReached, piecesDropped, tetrisClears });
    
    // Define achievement checks
    const achievementChecks = [
      // Basic Achievements
      { key: 'first_line', condition: linesCleared >= 1 },
      { key: 'line_master', condition: linesCleared >= 10 },
      { key: 'tetris_pro', condition: linesCleared >= 50 },
      { key: 'line_legend', condition: linesCleared >= 100 },
      { key: 'speed_demon', condition: levelReached >= 5 },
      { key: 'level_master', condition: levelReached >= 10 },
      { key: 'high_roller', condition: gameScore >= 2000 },
      { key: 'score_hunter', condition: gameScore >= 1000 },
      { key: 'point_master', condition: gameScore >= 3000 },
      { key: 'tetris_king', condition: gameScore >= 5000 },
      
      // Advanced Achievements
      { key: 'piece_dropper', condition: piecesDropped >= 100 },
      { key: 'block_master', condition: piecesDropped >= 500 },
      { key: 'tetris_clear', condition: tetrisClears >= 1 },
      { key: 'tetris_master', condition: tetrisClears >= 5 },
      { key: 'tetris_god', condition: tetrisClears >= 10 },
      { key: 'combo_starter', condition: linesCleared >= 2 },
      { key: 'combo_master', condition: linesCleared >= 5 },
      { key: 'combo_legend', condition: linesCleared >= 10 },
      { key: 'back_to_back', condition: tetrisClears >= 2 },
      
      // Expert Achievements
      { key: 'level_warrior', condition: levelReached >= 15 },
      { key: 'level_champion', condition: levelReached >= 20 },
      { key: 'score_legend', condition: gameScore >= 4000 },
      { key: 'score_god', condition: gameScore >= 5000 },
      { key: 'line_destroyer', condition: linesCleared >= 200 },
      { key: 'piece_legend', condition: piecesDropped >= 1000 },
      { key: 'tetris_legend', condition: tetrisClears >= 25 }
    ];
    
    // Check each achievement
    achievementChecks.forEach(achievement => {
      if (achievement.condition) {
        // 🏆 Check if we already checked this achievement this game
        if (achievementsCheckedThisGame.has(achievement.key)) {
          console.log(`ℹ️ Achievement ${achievement.key} already checked this game - skipping`);
          return;
        }
        
        console.log(`🏆 Achievement condition met: ${achievement.key}`);
        
        // Mark as checked this game
        achievementsCheckedThisGame.add(achievement.key);
        
        // Check if achievement is already unlocked (Season 3 Final Version)
        checkAndUnlockAchievement(userId, achievement.key, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears);
      }
    });
  }

  // 🏆 Check if Achievement Already Unlocked (Inside Game Scope)
  function checkAndUnlockAchievement(userId, achievementKey, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears) {
    // First check if achievement is already unlocked
    fetch('/api/user/get-tetris-achievements.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId }),
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.achievements) {
        // Check if this specific achievement is already unlocked
        const alreadyUnlocked = data.achievements.some(achievement => 
          achievement.achievement_key === achievementKey && achievement.unlocked_at
        );
        
        if (alreadyUnlocked) {
          console.log(`ℹ️ Achievement ${achievementKey} already unlocked - skipping popup`);
          return; // Don't show popup for already unlocked achievements
        }
        
        // Achievement not unlocked yet - unlock it and show popup
        console.log(`🏆 Achievement ${achievementKey} not yet unlocked - unlocking now`);
        
        // Unlock the achievement
        fetch('/api/dev/unlock-tetris-achievement.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: userId,
            achievement_key: achievementKey,
            game_score: gameScore,
            lines_cleared: linesCleared,
            level_reached: levelReached,
            pieces_dropped: piecesDropped,
            tetris_clears: tetrisClears
          }),
        })
        .then(response => response.json())
        .then(unlockData => {
          if (unlockData.success) {
            console.log(`🏆 Achievement ${achievementKey} unlocked successfully!`);
            // Show achievement popup
            showAchievementPopup(achievementKey, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears);
          } else {
            console.error(`❌ Failed to unlock achievement ${achievementKey}:`, unlockData.error);
          }
        })
        .catch(error => {
          console.error(`❌ Error unlocking achievement ${achievementKey}:`, error);
        });
      } else {
        console.error('❌ Failed to fetch achievements:', data.error);
      }
    })
    .catch(error => {
      console.error('❌ Error fetching achievements:', error);
    });
  }

  // 🏆 Show Achievement Popup Function (Inside Game Scope)
  function showAchievementPopup(achievementKey, gameScore, linesCleared, levelReached, piecesDropped, tetrisClears) {
    const achievementTitles = {
      'first_line': 'First Line',
      'line_master': 'Line Master',
      'tetris_pro': 'Tetris Pro',
      'line_legend': 'Line Legend',
      'speed_demon': 'Speed Demon',
      'level_master': 'Level Master',
      'high_roller': 'High Roller',
      'score_hunter': 'Score Hunter',
      'point_master': 'Point Master',
      'tetris_king': 'Tetris King',
      'piece_dropper': 'Piece Dropper',
      'block_master': 'Block Master',
      'tetris_clear': 'Tetris Clear',
      'tetris_master': 'Tetris Master',
      'tetris_god': 'Tetris God',
      'combo_starter': 'Combo Starter',
      'combo_master': 'Combo Master',
      'combo_legend': 'Combo Legend',
      'back_to_back': 'Back-to-Back',
      'level_warrior': 'Level Warrior',
      'level_champion': 'Level Champion',
      'score_legend': 'Score Legend',
      'score_god': 'Score God',
      'line_destroyer': 'Line Destroyer',
      'piece_legend': 'Piece Legend',
      'tetris_legend': 'Tetris Legend'
    };
    
    const title = achievementTitles[achievementKey] || achievementKey;
    showAchievementNotification(achievementKey, title);
  }

  // 🧹 Clean up touch controls (Inside Game Scope)
  function cleanupTouchControls() {
    clearTimeout(dropHoldTimeout);
    clearTimeout(touchDropInterval);
    heldDown = false;
    unlockTetrisScroll();
  }

  // 🎉 Show Achievement Notification (Inside Game Scope)
  function showAchievementNotification(achievementKey, achievementTitle) {
    const canvas = document.getElementById("tetris-canvas");
    if (!canvas) return;
    
    const popup = {
      title: achievementTitle,
      description: `Achievement Unlocked!`,
      icon: '🏆',
      life: 30, // 0.5 seconds at 60fps (much faster)
      maxLife: 30,
      scale: 1, // No scale animation - instant appearance
      maxScale: 1,
      y: canvas.height / 2,
      color: '#ffd700' // Gold color for achievements
    };
    
    achievementPopups.push(popup);
    console.log(`🏆 Achievement Unlocked: ${achievementTitle}`);
  }

  // 🎨 Draw Achievement Popups on Canvas (Inside Game Scope)
  function drawAchievementPopups() {
    const canvas = document.getElementById("tetris-canvas");
    if (!canvas) return;
    
    const ctx = canvas.getContext("2d");
    const centerX = canvas.width / 2;
    
    achievementPopups.forEach((popup, index) => {
      popup.life--;
      
      // Remove expired popups
      if (popup.life <= 0) {
        achievementPopups.splice(index, 1);
        return;
      }
      
      const alpha = popup.life / popup.maxLife;
      
      // Draw background (Tetris-optimized size)
      ctx.fillStyle = `rgba(0, 0, 0, ${alpha * 0.9})`;
      ctx.fillRect(centerX - 150, popup.y - 30, 300, 60);
      
      // Draw border (thinner for Tetris)
      ctx.strokeStyle = `rgba(255, 215, 0, ${alpha})`;
      ctx.lineWidth = 2;
      ctx.strokeRect(centerX - 150, popup.y - 30, 300, 60);
      
      // Draw icon (Tetris-optimized size with proper positioning)
      ctx.fillStyle = `rgba(255, 215, 0, ${alpha})`;
      ctx.font = `20px Arial`; // Slightly smaller to ensure it fits
      ctx.textAlign = 'center';
      ctx.fillText(popup.icon, centerX - 80, popup.y + 5); // Better positioning
      
      // Draw title (Tetris-optimized size)
      ctx.fillStyle = `rgba(255, 255, 255, ${alpha})`;
      ctx.font = `bold 18px Arial`;
      ctx.fillText(popup.title, centerX, popup.y - 10);
      
      // Draw description (Tetris-optimized size)
      ctx.fillStyle = `rgba(200, 200, 200, ${alpha})`;
      ctx.font = `12px Arial`;
      ctx.fillText(popup.description, centerX, popup.y + 12);
    });
  }

  // 🚀 Start the game loop immediately when game starts
  gameInterval = setInterval(drop, dropInterval);
  draw(); // Initial draw
}

function lockTetrisScroll() {
    document.body.style.overflow = "hidden";
}

function unlockTetrisScroll() {
    document.body.style.overflow = "";
}

// Listen anywhere on screen!
document.addEventListener("touchstart", e => {
  // Only handle touch events on the Tetris canvas
  if (!e.target.closest("#tetris-canvas")) return;
  
  // Don't handle touch events if game is paused or over
  if (isTetrisPaused) return;
  
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
  // Only handle touch events on the Tetris canvas
  if (!e.target.closest("#tetris-canvas")) return;
  
  // Always clear timeouts/intervals on touch end
  clearTimeout(dropHoldTimeout);
  clearInterval(touchDropInterval);

  // Don't process swipes if game is paused or over
  if (isTetrisPaused) {
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

// 🔧 MOBILE ERROR HANDLER
window.addEventListener('error', function(e) {
  if (isMobileDevice) {
    console.error('📱 Mobile Tetris error:', e.error);
    
    // Try to recover the game
    if (e.error && e.error.message && e.error.message.includes('tetris')) {
      console.log('📱 Attempting to recover Tetris game...');
      setTimeout(() => {
        try {
          if (typeof startTetris === 'function') {
            startTetris();
          }
        } catch (recoveryError) {
          console.error('📱 Recovery failed:', recoveryError);
        }
      }, 1000);
    }
  }
});

// 🔧 MOBILE TEST FUNCTION
window.testMobileTetris = function() {
  if (isMobileDevice) {
    console.log('📱 Testing mobile Tetris functionality...');
    console.log('📱 Canvas element:', document.getElementById('tetris-canvas'));
    console.log('📱 Start button:', document.getElementById('start-tetris-btn'));
    console.log('📱 startTetrisGame function:', typeof window.startTetrisGame);
    console.log('📱 startTetris function:', typeof startTetris);
    console.log('📱 All images loaded:', allImagesLoaded);
    
    // Test touch events
    const canvas = document.getElementById('tetris-canvas');
    if (canvas) {
      console.log('📱 Canvas touch events:', canvas.ontouchstart, canvas.ontouchmove, canvas.ontouchend);
    }
  } else {
    console.log('🖥️ Not a mobile device');
  }
};
      
      // ✅ Final game loop initialization
      draw();
      renderNextBlock(nextPiece);
      gameInterval = setInterval(drop, dropInterval);
      
      // 🔧 MOBILE FIX: Initialize touch controls properly
      if (isMobileDevice) {
        console.log('📱 Mobile Tetris game started successfully');
        
        // Initialize touch controls for the canvas
        initTouchControls(canvas, current, dropInterval);
        
        // Force a redraw to ensure everything is visible
        setTimeout(() => {
          draw();
          console.log('📱 Mobile Tetris redraw completed');
        }, 100);
      }

