window.startSnakeGame = function () {
  initSnake();
};

function initSnake() {
  const canvas = document.getElementById("snake-canvas");
  const ctx = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("snake-score");

  const gridSize = 20;
  const tileCountX = 10;
  const tileCountY = 20;
  let snake = [{ x: 5, y: 10 }];
  let velocity = { x: 0, y: -1 };
  let food = { x: 7, y: 7 };
  let score = 0;
  let gameInterval;
  let isSnakePaused = false;

document.getElementById("pause-snake-btn")?.addEventListener("click", () => {
  isSnakePaused = !isSnakePaused;
  const btn = document.getElementById("pause-snake-btn");
  btn.textContent = isSnakePaused ? "▶️ Resume" : "⏸️ Pause";
});


  // 👇 All existing logic copied from your current code (resetGame, placeFood, moveSnake, etc.)
  function resetGame() {
    snake = [{ x: 5, y: 10 }];
    velocity = { x: 0, y: -1 };
    placeFood();
    score = 0;
    updateScore();
  }

  function placeFood() {
    food = {
      x: Math.floor(Math.random() * tileCountX),
      y: Math.floor(Math.random() * tileCountY)
    };
  }

  function updateScore() {
    scoreDisplay.textContent = `💰 Snake Score: $${score * 10} DSPOINC`;
  }

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    snake.forEach(segment => {
      ctx.fillStyle = "#FFD700";
      ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
    });
    ctx.fillStyle = "#FFA500";
    ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  }

function moveSnake() {
  if (isSnakePaused) return; // ⛔ Don't move if paused

  const head = { x: snake[0].x + velocity.x, y: snake[0].y + velocity.y };

  if (
    head.x < 0 || head.x >= tileCountX ||
    head.y < 0 || head.y >= tileCountY ||
    snake.some(seg => seg.x === head.x && seg.y === head.y)
  ) {
    clearInterval(gameInterval);
    onGameOver(score * 10);
    return;
  }

  snake.unshift(head);

  if (head.x === food.x && head.y === food.y) {
    score++;
    updateScore();
    placeFood();
  } else {
    snake.pop();
  }

  draw();
}


function onGameOver(finalScore) {
  const modal = document.getElementById("snake-over-modal");
  const text = document.getElementById("snake-final-score-text");

  if (modal && text) {
    text.textContent = `You earned $${finalScore} DSPOINC`;
    modal.classList.remove("hidden");
  }

  fetch("/api/dev/save-score.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      wallet: localStorage.getItem("walletAddress") || "TestWallet123456789XYZ",
      score: finalScore,
      game: "snake",
      discord_id: localStorage.getItem("discord_id") || "1337",
      discord_name: localStorage.getItem("discord_name") || "Anonymous Mouse"
    })
  });
}


  // ✅ Controls
  document.addEventListener("keydown", e => {
    switch (e.key) {
      case "ArrowLeft": case "a":
        if (velocity.x === 0) velocity = { x: -1, y: 0 };
        break;
      case "ArrowRight": case "d":
        if (velocity.x === 0) velocity = { x: 1, y: 0 };
        break;
      case "ArrowUp": case "w":
        if (velocity.y === 0) velocity = { x: 0, y: -1 };
        break;
      case "ArrowDown": case "s":
        if (velocity.y === 0) velocity = { x: 0, y: 1 };
        break;
    }
  });

  // ✅ Swipe support
  let touchStartX = 0, touchStartY = 0;
  canvas.addEventListener("touchstart", e => {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
  });

  canvas.addEventListener("touchend", e => {
    const deltaX = e.changedTouches[0].clientX - touchStartX;
    const deltaY = e.changedTouches[0].clientY - touchStartY;
    if (Math.abs(deltaX) > Math.abs(deltaY)) {
      if (deltaX > 20 && velocity.x === 0) velocity = { x: 1, y: 0 };
      else if (deltaX < -20 && velocity.x === 0) velocity = { x: -1, y: 0 };
    } else {
      if (deltaY > 20 && velocity.y === 0) velocity = { x: 0, y: 1 };
      else if (deltaY < -20 && velocity.y === 0) velocity = { x: 0, y: -1 };
    }
  });

function resetGame() {
  snake = [{ x: 5, y: 10 }];
  velocity = { x: 0, y: -1 };
  placeFood();
  score = 0;
  updateScore();

  // 🟡 Reset pause state on game restart
  isSnakePaused = false;
  const btn = document.getElementById("pause-snake-btn");
  if (btn) btn.textContent = "⏸️ Pause";
}
// ✅ Start game after reset
resetGame();
clearInterval(gameInterval);
gameInterval = setInterval(moveSnake, 150);
}
