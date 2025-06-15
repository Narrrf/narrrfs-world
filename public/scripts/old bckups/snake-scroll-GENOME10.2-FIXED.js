// 🧬 Load head and dna segment images
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

let mutationActive = false;

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
  const isActive = mutationActive || localStorage.getItem("snake_mutation") === "true";

  if (isActive) {
    document.body.classList.add("mutation-mode");
    if (badge) badge.classList.remove("hidden");
    console.log("🧬 Mutation stored:", localStorage.getItem("snake_mutation"));
  } else {
    document.body.classList.remove("mutation-mode");
    if (badge) badge.classList.add("hidden");
  }
}

// 🖌️ Draw Function
function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // ✅ Trigger mutation only once if score crosses threshold during play
  if (score >= 100 && !window.brainUnlocked) {
    unlockTrait("GENETIC_SENTINEL");
    mutationActive = true;
    localStorage.setItem("snake_mutation", "true");
    setTimeout(() => {
      mutationActive = false;
      checkMutationStatus(); // update UI after timer
    }, 15000);
    window.brainUnlocked = true;
  }

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
}
