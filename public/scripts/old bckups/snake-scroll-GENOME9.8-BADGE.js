// 🖌️ Draw Function with mutation-mode class + badge + persistent unlock
function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  if (score >= 100 && !window.brainUnlocked) {
    unlockTrait("GENETIC_SENTINEL");
    mutationActive = true;
    localStorage.setItem("snake_mutation", "true");
    document.body.classList.add("mutation-mode");
    setTimeout(() => {
      mutationActive = false;
      document.body.classList.remove("mutation-mode");
    }, 15000);
    window.brainUnlocked = true;
  }

  if (mutationActive || localStorage.getItem("snake_mutation") === "true") {
    document.body.classList.add("mutation-mode");
    console.log("🧬 Mutation stored:", localStorage.getItem("snake_mutation"));
  }

  const badge = document.getElementById("mutation-badge");
  if (badge) {
    if (mutationActive || localStorage.getItem("snake_mutation") === "true") {
      badge.classList.remove("hidden");
    } else {
      badge.classList.add("hidden");
    }
  }

  // Trail glow
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

  // Snake rendering
  snake.forEach((segment, index) => {
    const isHead = index === 0;
    const img = isHead ? snakeHeadImg : snakeDnaImg;
    const next = snake[index + 1] || snake[index - 1] || segment;
    const dir = getDirection(segment, next);

    const pulse = 0.5 + 0.5 * Math.sin(performance.now() / 120 + index);
    const wobble = Math.sin(index * 0.5 + performance.now() / 160) * 1.5;
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

  // 🧀 Draw cheese
  if (cheeseImg.complete) {
    ctx.drawImage(cheeseImg, food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  } else {
    ctx.fillStyle = "#FFA500";
    ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
  }
}
