// 🧬 Load head and dna segment images
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

let mutationActive = false;

// Direction helper
function getDirection(from, to) {
  if (!to) return "right";
  if (to.x > from.x) return "right";
  if (to.x < from.x) return "left";
  if (to.y > from.y) return "down";
  if (to.y < from.y) return "up";
  return "up";
}

// 🖌️ Draw Function with trail + blink mutation
function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (score >= 100 && !window.brainUnlocked) {
    unlockTrait("GENETIC_SENTINEL");
    mutationActive = true;
    setTimeout(() => mutationActive = false, 15000);
    window.brainUnlocked = true;
  }

  // Optional glow trail
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

  // Main snake render pass
  snake.forEach((segment, index) => {
    const isHead = index === 0;
    const img = isHead ? snakeHeadImg : snakeDnaImg;
    const next = snake[index + 1] || snake[index - 1] || segment;
    const dir = getDirection(segment, next);

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

      if (isHead) {
        ctx.shadowColor = mutationActive ? "white" : "lime";
        ctx.shadowBlur = mutationActive
          ? 30 + 10 * Math.sin(performance.now() / 100)
          : 20;
      } else {
        ctx.shadowColor = "lime";
        ctx.shadowBlur = 15;
      }

      ctx.drawImage(img, -gridSize / 2, -gridSize / 2, gridSize, gridSize);
      ctx.restore();
    } else {
      ctx.fillStyle = isHead ? "#FFD700" : "#39FF14";
      ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
    }

    ctx.globalAlpha = 1;
    ctx.filter = "none";
  });

  // 🧀 Cheese
  if (cheeseImg.complete) {
    ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  } else {
    ctx.fillStyle = "#FFA500";
    ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  }
}
