// 🚫 Full page scroll prevention
window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) {
    e.preventDefault();
  }
}, { passive: false });

// ✅ GENOME 10.5 FINAL FIXED — Mutation on Score 100 + No Duplication + Global Scope Fix

let gameInterval; // ✅ Global scope
window.brainUnlocked = false; // ✅ Mutation flag, resets on game start

// 🧬 Load images (global scope)
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

const cheeseImg = new Image();
cheeseImg.src = "img/snake/cheese.png";

// 🧠 Mutation flag (false by default)
let mutationActive = false;

// ✅ DOM-ready game boot
window.addEventListener("DOMContentLoaded", () => {
  initSnake(); // ✅ Runs only after DOM is ready
});

function initSnake() {
  const canvas = document.getElementById("snake-canvas");
  if (!canvas) {
    console.error("Canvas element with id 'snake-canvas' not found.");
    return;
  }
  const ctx = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("snake-score");

  const gridSize = 20;
  const tileCountX = 10;
  const tileCountY = 20;
  let snake = [{ x: 5, y: 10 }];
  let velocity = { x: 0, y: -1 };
  let food = { x: 7, y: 7 };
  let score = 0;
  let isSnakePaused = false;

  function startGameWithCountdown() {
    const countdownEl = document.getElementById("snake-countdown");
    let count = 5;

    if (!countdownEl) {
      console.warn("Countdown element not found.");
      startGame(); // fallback
      return;
    }

    countdownEl.classList.remove("hidden");
    countdownEl.textContent = count;

    const countdownInterval = setInterval(() => {
      count--;
      if (count > 0) {
        countdownEl.textContent = count;
      } else if (count === 0) {
        countdownEl.textContent = "GO!";
      } else {
        clearInterval(countdownInterval);
        countdownEl.classList.add("hidden");
        startGame(); // begin actual game
      }
    }, 1000);
  }

  function startGame() {
    clearInterval(gameInterval);
    resetGame();
    gameInterval = setInterval(moveSnake, 250); // slow start
    enableGlobalSnakeTouch(); // Enable touch controls when game starts
  }

  function resetGame() {
    snake = [{ x: 5, y: 10 }];
    velocity = { x: 0, y: -1 };
    placeFood();
    score = 0;
    updateScore();
    isSnakePaused = false;
    window.brainUnlocked = false;
    mutationActive = false;
    localStorage.removeItem("snake_mutation");
    checkMutationStatus();
    const btn = document.getElementById("pause-snake-btn");
    if (btn) btn.textContent = "⏸️ Pause";
    
    // 🎯 Ensure scrolling is locked when game is reset and active
    if (gameInterval) {
      lockSnakeScroll();
    }
  }

  function placeFood() {
    food = {
      x: Math.floor(Math.random() * tileCountX),
      y: Math.floor(Math.random() * tileCountY)
    };
  }

  function updateScore() {
    if (scoreDisplay) {
      scoreDisplay.textContent = `💰 Snake Score: $${score * 10} DSPOINC`;
    }
  }

  // 🧭 Direction helper
  function getDirection(from, to) {
    if (!to) return "right";
    if (to.x > from.x) return "right";
    if (to.x < from.x) return "left";
    if (to.y > from.y) return "down";
    if (to.y < from.y) return "up";
    return "up";
  }

  // ✅ GENETIC MODE CHECK FUNCTION
  function checkMutationStatus() {
    const badge = document.getElementById("mutation-badge");
    if (mutationActive) {
      document.body.classList.add("mutation-mode");
      if (badge) badge.classList.remove("hidden");
    } else {
      document.body.classList.remove("mutation-mode");
      if (badge) badge.classList.add("hidden");
    }
  }

  // ✅ TRAIT TRIGGER FUNCTION (only when x cheese eaten defined in score threshold is passed for first time)
  function tryActivateMutation(score) {
    if (score >= 100 && !window.brainUnlocked) {
      unlockTrait("GENETIC_SENTINEL");
      mutationActive = true;
      localStorage.setItem("snake_mutation", "true");

      setTimeout(() => {
        mutationActive = false;
        checkMutationStatus();
      }, 15000);

      window.brainUnlocked = true;
      checkMutationStatus();
      console.log("🧬 Mutation triggered at score:", score);
    }
  }

  // 🖌️ Draw Function
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    checkMutationStatus();

    // 🔁 Trail glow
    for (let i = 0; i < snake.length; i++) {
      const segment = snake[i];
      const t = i / snake.length;
      const fade = 0.25 * (1 - t);
      ctx.save();
      ctx.globalAlpha = fade;
      ctx.fillStyle = "#39FF14";
      ctx.beginPath();
      ctx.arc(
        segment.x * gridSize + gridSize / 2,
        segment.y * gridSize + gridSize / 2,
        gridSize * 0.3,
        0,
        2 * Math.PI
      );
      ctx.fill();
      ctx.restore();
    }

    // 🧬 Render Snake
    snake.forEach((segment, index) => {
      const isHead = index === 0;
      const img = isHead ? snakeHeadImg : snakeDnaImg;
      const next = snake[index + 1] || snake[index - 1] || segment;
      const dir = getDirection(segment, next);

      const pulse = 0.5 + 0.5 * Math.sin(performance.now() / 120 + index);
      ctx.globalAlpha = 0.85 + pulse * 0.1;

      const posX = segment.x * gridSize + gridSize / 2;
      const posY = segment.y * gridSize + gridSize / 2;

      if (img.complete) {
        ctx.save();
        ctx.translate(posX, posY);

        switch (dir) {
          case "up": ctx.rotate(0); break;
          case "down": ctx.rotate(Math.PI); break;
          case "left": ctx.rotate(-Math.PI / 2); break;
          case "right": ctx.rotate(Math.PI / 2); break;
        }

        ctx.shadowColor = isHead && mutationActive ? "white" : "lime";
        ctx.shadowBlur = isHead
          ? mutationActive
            ? 30 + 10 * Math.sin(performance.now() / 100)
            : 20
          : 15;

        ctx.drawImage(img, -gridSize / 2, -gridSize / 2, gridSize, gridSize);
        ctx.restore();
      } else {
        ctx.fillStyle = isHead ? "#FFD700" : "#39FF14";
        ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
      }

      ctx.globalAlpha = 1;
      ctx.filter = "none";
    });

    // 🧀 Draw cheese
    if (cheeseImg.complete) {
      ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
    } else {
      ctx.fillStyle = "#FFA500";
      ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
    }
  } // ✅ End of draw()

  function moveSnake() {
    if (isSnakePaused) return;

    const head = { x: snake[0].x + velocity.x, y: snake[0].y + velocity.y };

    // Game over logic
    if (
      head.x < 0 || head.x >= tileCountX ||
      head.y < 0 || head.y >= tileCountY ||
      snake.some(seg => seg.x === head.x && seg.y === head.y)
    ) {
      clearInterval(gameInterval);
      onGameOver();
      return;
    }

    snake.unshift(head);

    // 🍽️ Check if snake eats cheese
    const ate = head.x === food.x && head.y === food.y;
    if (ate) {
      score++;
      updateScore();
      placeFood();
      tryActivateMutation(score); // ✅ Now runs exactly on score increase
    } else {
      snake.pop(); // ✅ Don't grow if no cheese
    }

    draw(); // 🖌️ Always render after logic
  }

  // --- Event Listeners (only add once!) ---

  // Keyboard controls
  document.addEventListener("keydown", e => {
    switch (e.key) {
      case "ArrowLeft": case "a": if (velocity.x === 0) velocity = { x: -1, y: 0 }; break;
      case "ArrowRight": case "d": if (velocity.x === 0) velocity = { x: 1, y: 0 }; break;
      case "ArrowUp": case "w": if (velocity.y === 0) velocity = { x: 0, y: -1 }; break;
      case "ArrowDown": case "s": if (velocity.y === 0) velocity = { x: 0, y: 1 }; break;
    }
  });

// Touch controls with scroll prevention - Enhanced for mobile
let touchStartX = 0, touchStartY = 0;
let isSnakeGameActive = false;
let snakeScrollLocked = false;
const SNAKE_SWIPE_THRESHOLD = 50; // Increased from 30 to 50 for better mobile control

function enableGlobalSnakeTouch() { 
  isSnakeGameActive = true; 
  document.body.style.overflow = "hidden";
  snakeScrollLocked = true;
}

function disableGlobalSnakeTouch() { 
  isSnakeGameActive = false;
  document.body.style.overflow = "";
  snakeScrollLocked = false;
  unlockSnakeScroll(); // 🎯 Ensure scrolling is unlocked when disabling touch
}

// Helper: Lock/unlock scrolling for Snake (like Tetris)
function lockSnakeScroll() {
  if (!snakeScrollLocked) {
    document.body.style.overflow = "hidden";
    snakeScrollLocked = true;
  }
}

function unlockSnakeScroll() {
  if (snakeScrollLocked) {
    document.body.style.overflow = "";
    snakeScrollLocked = false;
  }
}

// Prevent scrolling on the game canvas - Enhanced for mobile
document.addEventListener('touchmove', function(e) {
  if (isSnakeGameActive && !isSnakePaused) {
    e.preventDefault();
    e.stopPropagation();
  }
}, { passive: false });

document.body.addEventListener("touchstart", function(e) {
  if (!isSnakeGameActive || isSnakePaused) return;
  e.preventDefault();
  e.stopPropagation();
  const touch = e.touches[0];
  touchStartX = touch.clientX;
  touchStartY = touch.clientY;
}, { passive: false });

document.body.addEventListener("touchend", function(e) {
  if (!isSnakeGameActive || isSnakePaused) return;
  e.preventDefault();
  e.stopPropagation();
  const touch = e.changedTouches[0];
  const deltaX = touch.clientX - touchStartX;
  const deltaY = touch.clientY - touchStartY;
  
  // Increased minimum swipe distance for better mobile control
  const minSwipeDistance = SNAKE_SWIPE_THRESHOLD;
  
  // Only process swipes if game is active and not paused
  if (Math.abs(deltaX) > Math.abs(deltaY)) {
    if (deltaX > minSwipeDistance && velocity.x === 0) {
      velocity = { x: 1, y: 0 };
    } else if (deltaX < -minSwipeDistance && velocity.x === 0) {
      velocity = { x: -1, y: 0 };
    }
  } else {
    if (deltaY > minSwipeDistance && velocity.y === 0) {
      velocity = { x: 0, y: 1 };
    } else if (deltaY < -minSwipeDistance && velocity.y === 0) {
      velocity = { x: 0, y: -1 };
    }
  }
}, { passive: false });

// Add touch control activation/deactivation to game functions
function startGame() {
  clearInterval(gameInterval);
  resetGame();
  gameInterval = setInterval(moveSnake, 250); // slow start
  enableGlobalSnakeTouch(); // Enable touch controls when game starts
  lockSnakeScroll(); // 🎯 Lock scrolling when game starts (like Tetris)
}

function onGameOver() {
  clearInterval(gameInterval);
  gameInterval = null;
  isSnakePaused = true;

  // 🎯 Unlock scrolling when game over (like Tetris)
  unlockSnakeScroll();

  // 🐍 Capture the final score before any potential resets
  const finalScore = score;
  console.log("🐍 Game Over - Final Score:", finalScore);

  const modal = document.getElementById("snake-over-modal");
  const finalScoreText = document.getElementById("snake-final-score-text");
  const pauseBtn = document.getElementById("pause-snake-btn");

  if (modal && finalScoreText) {
    // ✅ Only update score content, no style changes — handled in HTML
    finalScoreText.textContent = `You earned $${finalScore * 10} DSPOINC`;
    console.log("🐍 Displaying score:", finalScore);

    modal.classList.remove("hidden");
    modal.style.display = "flex"; // fallback for older browsers
  }

  if (pauseBtn) {
    pauseBtn.textContent = "⏸️ Pause";
  }

  // 🐍 Save the captured final score
  saveScore(finalScore);
}

// 🐍 Snake Score Saving Function
function saveScore(finalScore) {
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
    discord_name: discordName,
    game: "snake" // 🐍 Specify this is a Snake game score
  };

  console.log("⏎ Sending score payload:", payload);
  
  // 🌍 Environment-aware API endpoint (works both locally and in production)
  const isProduction = window.location.hostname === 'narrrfs-world.onrender.com' || window.location.hostname === 'narrrfs.world';
  const apiBaseUrl = isProduction ? 'https://narrrfs.world' : 'http://localhost/narrrfs-world';
  const apiUrl = `${apiBaseUrl}/api/dev/save-score.php`;
  
  console.log(`🌍 Environment: ${isProduction ? 'Production' : 'Local'}`);
  console.log(`🔗 API URL: ${apiUrl}`);

  fetch(apiUrl, {
           method: "POST",
           headers: { "Content-Type": "application/json" },
           body: JSON.stringify(payload)
         })
           .then(res => res.json())
           .then(data => {
             console.log("💾 Snake score saved:", data);
             
             // 🏆 Check for WL Role Grant
             if (data.wl_check && data.wl_check.eligible) {
               console.log("🎉 WL Role granted:", data.wl_check);
               
               // Show WL notification
               showWLNotification(data.wl_check);
             }

             // ✅ Force leaderboard refresh after short delay
             setTimeout(() => {
               if (document.getElementById("leaderboard-list")) {
                 // 🌍 Environment-aware API endpoint for leaderboard
                 const leaderboardUrl = `${apiBaseUrl}/api/dev/get-leaderboard.php?t=${Date.now()}`;
                 console.log(`🔗 Leaderboard API URL: ${leaderboardUrl}`);
                 
                 fetch(leaderboardUrl)
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
           .catch(err => console.error("Snake score save failed:", err));
}



  // Pause button
  const pauseBtn = document.getElementById("pause-snake-btn");
  if (pauseBtn) {
    // Add mobile-friendly styles
    pauseBtn.style.padding = "12px 24px";
    pauseBtn.style.fontSize = "18px";
    pauseBtn.style.touchAction = "manipulation";
    pauseBtn.style.userSelect = "none";
    pauseBtn.style.webkitTapHighlightColor = "transparent";
    
    // Remove any existing listeners
    pauseBtn.replaceWith(pauseBtn.cloneNode(true));
    const newPauseBtn = document.getElementById("pause-snake-btn");
    
    // Add both click and touch events
    const pauseHandler = (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      isSnakePaused = !isSnakePaused;
      newPauseBtn.textContent = isSnakePaused ? "▶️ Resume" : "⏸️ Pause";
      
      if (isSnakePaused) {
        clearInterval(gameInterval);
        // 🎯 Unlock scrolling when paused (like Tetris)
        unlockSnakeScroll();
      } else {
        // Only restart if game is not over
        if (gameInterval) {
          clearInterval(gameInterval);
          gameInterval = setInterval(moveSnake, 250);
          // 🎯 Lock scrolling when resumed (like Tetris)
          lockSnakeScroll();
        }
      }
    };

    newPauseBtn.addEventListener("click", pauseHandler);
    newPauseBtn.addEventListener("touchend", pauseHandler, { passive: false });
  }

  // Start button
  const startBtn = document.getElementById("start-snake-btn");
  if (startBtn) {
    startBtn.addEventListener("click", () => {
      startGameWithCountdown();
    });
  } else {
    startGameWithCountdown();
  }

  // Expose start for external use
  window.startSnakeGame = startGameWithCountdown;

  // 👁️ Watch DOM visibility and re-init if hidden
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) {
        clearInterval(gameInterval);
      }
    });
  }, { threshold: 0.1 });

  observer.observe(canvas);
}

/*
============================================================
🐍 SNAKE 9.0 SCROLL – EXTENDED GLYPH
============================================================
⛏️ AUTHOR: Cheese Architect + SQL Junior
📅 VERSION: 9.0 FINAL · Scroll Timestamp: 2025-06-13
🧠 DESC: Enhanced Narrrf Snake Game Scroll with full trait DOM hooks,
        countdown intro, Discord-linked score sync, pause/resume,
        mobile gesture controls, and future-ready mutation slots.
------------------------------------------------------------
✅ FEATURE OVERVIEW:
- Cheese-styled food block (png)
- Game Over modal with DSPOINC score
- Discord + Wallet linked save logic
- Countdown overlay intro (5..GO!)
- Touch gesture controls for mobile
- Trait-based DOM unlock compatibility
- Extensible logic for bomb mode, glowing snake, and lore unlock

🧬 FUTURE ENHANCEMENTS:
- Snake trail glow (DOM filters)
- Rare cheese blocks that speed up gameplay
- Reward-linked NFT unlock
- Puzzle bridge w/ Riddle Brain 9.0
- LocalStorage streak tracking
============================================================
*/

function unlockTrait(trait) {
  fetch("https://narrrfs.world/api/user/traits.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ trait })
  })
  .then(res => res.json())
  .then(data => {
    console.log(`🔓 Trait Unlocked: ${trait}`, data);
  })
  .catch(err => console.warn("⚠️ Trait unlock failed:", err));
}

// 🧠 Optional trait trigger at score threshold
function checkTraitUnlocks(score) {
  if (score >= 100 && !window.snakeTraitUnlocked) {
    unlockTrait("SNAKE_CHEESE_MASTER");
    const unlockDiv = document.getElementById("snake-unlock");
    if (unlockDiv) unlockDiv.classList.remove("hidden");
    window.snakeTraitUnlocked = true;
  }
}

// 🔁 Local storage bonus tracking
function updateStreakCounter() {
  let streak = parseInt(localStorage.getItem("snake_streak") || "0", 10);
  streak += 1;
  localStorage.setItem("snake_streak", streak);
  console.log(`🔥 Current Snake Streak: ${streak}`);
}

// 🧪 Cheese bomb seed logic (disabled by default)
function maybePlaceBomb() {
  // Reserved for bomb feature in future
  if (Math.random() < 0.05) {
    console.log("💣 Bomb block placed (placeholder)");
  }
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

// 🛠️ UI Debug Helper
function showDebugInfo() {
  // These variables are only available inside initSnake, so this is a placeholder.
  // To use this globally, you would need to expose them to window or refactor.
  console.log("Debug info only available in game context.");
} 