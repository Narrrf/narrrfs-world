// 🧬 Load head and dna segment images
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

// 🧬 Mutation toggle
let mutationActive = false;

// 🧬 Determine direction between two segments
function getDirection(prev, current) {
  if (!prev) return "up";
  if (current.x > prev.x) return "right";
  if (current.x < prev.x) return "left";
  if (current.y > prev.y) return "down";
  return "up";
}

// 🖌️ Draw Function with rotation and glow
function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (score >= 100 && !window.brainUnlocked) {
    unlockTrait("BRAIN_HELIX_EVOLVED");
    mutationActive = true;
    setTimeout(() => mutationActive = false, 15000);
    window.brainUnlocked = true;
  }

  snake.forEach((segment, index) => {
    const isHead = index === 0;
    const img = isHead ? snakeHeadImg : snakeDnaImg;
    const prev = snake[index + 1]; // tail is at higher index
    const dir = getDirection(prev, segment);

    const t = index / snake.length;
    const pulse = 0.5 + 0.5 * Math.sin(performance.now() / 120 + index);
    const wobble = Math.sin(index * 0.5 + performance.now() / 160) * 1.5;

    ctx.globalAlpha = 0.85 + pulse * 0.1;

    if (mutationActive && !isHead && img.complete) {
      ctx.filter = `hue-rotate(${index * 30}deg)`;
    }

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

      ctx.shadowColor = "lime";
      ctx.shadowBlur = 20;
      ctx.drawImage(img, -gridSize / 2, -gridSize / 2, gridSize, gridSize);
      ctx.restore();
    } else {
      ctx.fillStyle = isHead ? "#FFD700" : "#39FF14";
      ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
    }

    ctx.globalAlpha = 1;
    ctx.filter = "none";
  });

  // 🧀 Draw cheese food
  if (cheeseImg.complete) {
    ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  } else {
    ctx.fillStyle = "#FFA500";
    ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  }
}
