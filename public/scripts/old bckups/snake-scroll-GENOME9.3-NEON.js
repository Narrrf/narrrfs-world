// 🧬 Load head and dna segment images
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

// 🧬 Mutation toggle
let mutationActive = false;

// 🖌️ Draw Function with green neon sync
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

    const t = index / snake.length;
    const pulse = 0.5 + 0.5 * Math.sin(performance.now() / 120 + index);
    const wobble = Math.sin(index * 0.5 + performance.now() / 160) * 1.5;

    ctx.globalAlpha = 0.85 + pulse * 0.1;

    // 🧠 Mutation shimmer (optional hue rotate)
    if (mutationActive && !isHead && img.complete) {
      ctx.filter = `hue-rotate(${index * 30}deg)`;
    }

    if (img.complete) {
      ctx.save();
      ctx.shadowColor = "lime";
      ctx.shadowBlur = 20;
      ctx.drawImage(
        img,
        segment.x * gridSize + wobble,
        segment.y * gridSize,
        gridSize,
        gridSize
      );
      ctx.restore();
    } else {
      ctx.fillStyle = isHead ? "#FFD700" : "#39FF14";
      ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
    }

    ctx.globalAlpha = 1;
    ctx.filter = "none";
  });

  // 🧀 Draw cheese food block
  if (cheeseImg.complete) {
    ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  } else {
    ctx.fillStyle = "#FFA500";
    ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  }
}
