
// 🎨 Block colors for pieces (only define this ONCE)
const colors = ["", "#fcd34d", "#4ade80", "#60a5fa", "#f472b6", "#c084fc", "#facc15"]; // color[6] = glowing

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

document.addEventListener("DOMContentLoaded", () => {
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
  let nextPiece = randomPiece();

  const pieces = [
    [[1, 1, 1], [0, 1, 0]],     // T
    [[2, 2], [2, 2]],           // O
    [[0, 3, 3], [3, 3, 0]],     // S
    [[4, 4, 0], [0, 4, 4]],     // Z
    [[5, 5, 5, 5]],             // I
    [[6]]                       // 💣
  ];

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

  function renderNextBlock(shape) {
    if (!nextCtx || !shape) return;
    nextCtx.clearRect(0, 0, nextCanvas.width, nextCanvas.height);

    const offsetX = Math.floor((4 - shape[0].length) / 2);
    const offsetY = Math.floor((4 - shape.length) / 2);

    shape.forEach((row, y) => {
      row.forEach((val, x) => {
        if (val) {
          nextCtx.fillStyle = colors[val];
          nextCtx.fillRect((x + offsetX) * 20, (y + offsetY) * 20, 20, 20);
          nextCtx.strokeStyle = "#1f2937";
          nextCtx.strokeRect((x + offsetX) * 20 + 0.5, (y + offsetY) * 20 + 0.5, 19, 19);
        }
      });
    });
  }

  // ✅ Final game loop initialization
  loadLeaderboard();
  draw();
  renderNextBlock(nextPiece);
  gameInterval = setInterval(drop, dropInterval);
});
