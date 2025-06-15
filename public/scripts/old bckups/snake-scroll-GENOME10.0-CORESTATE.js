// 🧬 Load head and dna segment images
const snakeHeadImg = new Image();
snakeHeadImg.src = "img/snake/snake-head.png";

const snakeDnaImg = new Image();
snakeDnaImg.src = "img/snake/snake-dna.png";

// 🧠 Mutation state
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

// 🖌️ Draw Function with persistent unlock logic
function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

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

  checkMutationStatus(); // ✅ call here every frame

  // ⬇️ Insert trail glow and snake render logic here
}
