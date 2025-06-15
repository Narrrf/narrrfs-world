
// 🧀 Cheese Tetris Scroll v9.9 FINAL
// Includes: cheese PNG blocks, bomb countdowns, defuse popup, mobile controls, scoring, and persistence.

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

let allImagesLoaded = false;
let loadedCount = 0;
Object.entries(pieceImageMap).forEach(([key, filename]) => {
  const img = new Image();
  img.src = "http://localhost/public/img/tetris/" + filename;
  img.onload = () => {
    loadedCount++;
    if (loadedCount === Object.keys(pieceImageMap).length) {
      allImagesLoaded = true;
      startTetris();
    }
  };
  blockImages[key] = img;
});

// 🚫 Prevent page scroll from arrow keys
window.addEventListener("keydown", function (e) {
  const keys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "a", "s", "d", "w"];
  if (keys.includes(e.key)) e.preventDefault();
}, { passive: false });

function startTetris() {
  const canvas = document.getElementById("tetris-canvas");
  const context = canvas.getContext("2d");
  const scoreDisplay = document.getElementById("spoink-score");
  const nextCanvas = document.getElementById("next-canvas");
  const nextCtx = nextCanvas.getContext("2d");
  const bombWarning = document.getElementById("bomb-warning");

  const gridWidth = 10, gridHeight = 20, blockSize = 20;
  const grid = Array.from({ length: gridHeight }, () => Array(gridWidth).fill(0));

  let score = 0, linesClearedTotal = 0, dropInterval = 500, gameInterval;
  let activeExplosive = null;
  const sensitivity = 20;

  const pieces = [
    [[1, 1, 1], [0, 1, 0]], [[2, 2], [2, 2]], [[0, 3, 3], [3, 3, 0]],
    [[4, 4, 0], [0, 4, 4]], [[5, 5, 5, 5]], [[7, 0], [7, 0], [7, 7]],
    [[0, 8], [0, 8], [8, 8]], [[6]]
  ];

  function randomPiece() {
    return Math.random() < 0.1 ? [[6]] : pieces[Math.floor(Math.random() * pieces.length)];
  }

  let nextPiece = randomPiece();
  let current = { shape: nextPiece, row: 0, col: 3 };
  nextPiece = randomPiece();

  function drawBlock(x, y, val) {
    const img = blockImages[val];
    if (!img) return;
    context.drawImage(img, x * blockSize, y * blockSize, blockSize, blockSize);
  }

  function draw() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    for (let y = 0; y < gridHeight; y++) {
      for (let x = 0; x < gridWidth; x++) {
        if (grid[y][x]) drawBlock(x, y, grid[y][x]);
      }
    }
    current.shape.forEach((row, dy) => {
      row.forEach((val, dx) => {
        if (val) drawBlock(current.col + dx, current.row + dy, val);
      });
    });
  }

  function renderNextBlock(shape) {
    nextCtx.clearRect(0, 0, nextCanvas.width, nextCanvas.height);
    shape.forEach((row, y) => {
      row.forEach((val, x) => {
        if (val) nextCtx.drawImage(blockImages[val], x * 20, y * 20, 20, 20);
      });
    });
    if (bombWarning) bombWarning.classList.toggle("hidden", !(shape.length === 1 && shape[0][0] === 6));
  }

  function collide(shape, row, col) {
    return shape.some((r, y) =>
      r.some((v, x) => {
        const ny = row + y, nx = col + x;
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
    for (let y = gridHeight - 1; y >= 0; y--) {
      if (grid[y].every(v => v !== 0)) {
        if (grid[y].includes(6)) showBombDefusedPopup();
        grid.splice(y, 1);
        grid.unshift(Array(gridWidth).fill(0));
        linesClearedTotal++; score += 10;
        dropInterval = Math.max(100, dropInterval - 20);
        clearInterval(gameInterval);
        gameInterval = setInterval(drop, dropInterval);
        y++;
      }
    }
    scoreDisplay.textContent = `💰 $DSPOINC earned: ${score}`;
  }

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

  function explode(cx, cy) {
    for (let y = -1; y <= 1; y++) {
      for (let x = -1; x <= 1; x++) {
        if (grid[cy + y]?.[cx + x]) grid[cy + y][cx + x] = 0;
      }
    }
  }

  function drop() {
    if (!collide(current.shape, current.row + 1, current.col)) {
      current.row++;
    } else {
      if (current.shape.length === 1 && current.shape[0][0] === 6) {
        const cx = current.col, cy = current.row;
        activeExplosive = { x: cx, y: cy, countdown: 5, start: Date.now() };
        setTimeout(() => {
          if (grid[cy]?.[cx] === 6) explode(cx, cy);
          activeExplosive = null;
        }, 5000);
      }
      merge();
      clearLines();
      current = { shape: nextPiece, row: 0, col: 3 };
      nextPiece = randomPiece();
      renderNextBlock(nextPiece);
      if (collide(current.shape, current.row, current.col)) {
        clearInterval(gameInterval);
        alert("Game Over");
        return;
      }
    }
    draw();
  }

  function rotatePiece() {
    const rotated = current.shape[0].map((_, i) => current.shape.map(row => row[i]).reverse());
    if (!collide(rotated, current.row, current.col)) current.shape = rotated;
  }

  document.addEventListener("keydown", e => {
    switch (e.key) {
      case "a": case "ArrowLeft": if (!collide(current.shape, current.row, current.col - 1)) current.col--; break;
      case "d": case "ArrowRight": if (!collide(current.shape, current.row, current.col + 1)) current.col++; break;
      case "s": case "ArrowDown": drop(); break;
      case "w": case "ArrowUp": rotatePiece(); break;
    }
    draw();
  });

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

  renderNextBlock(nextPiece);
  gameInterval = setInterval(drop, dropInterval);
  draw();
}

