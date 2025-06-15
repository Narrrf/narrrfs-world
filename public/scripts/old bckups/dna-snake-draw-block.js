// 🧬 Load head and dna segment images
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

// 🧬 Draw function using PNG head + DNA strand segments
function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  snake.forEach((segment, index) => {
    const img = (index === 0) ? snakeHeadImg : snakeDnaImg;

    if (img.complete) {
      ctx.drawImage(
        img,
        segment.x * gridSize,
        segment.y * gridSize,
        gridSize,
        gridSize
      );
    } else {
      ctx.fillStyle = index === 0 ? "#FFD700" : "#7CF";
      ctx.fillRect(
        segment.x * gridSize,
        segment.y * gridSize,
        gridSize,
        gridSize
      );
    }
  });

  // 🧀 Draw cheese food
  if (cheeseImg.complete) {
    ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  } else {
    ctx.fillStyle = "#FFA500";
    ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  }
}
