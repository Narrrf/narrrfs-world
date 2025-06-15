// ✅ GENETIC MODE CHECK FUNCTION
// Called every frame from `draw()` or from unlock trigger
function checkMutationStatus() {
  const badge = document.getElementById("mutation-badge");

  const isActive = mutationActive || localStorage.getItem("snake_mutation") === "true";

  // 🧬 DEV NOTE:
  // mutationActive = runtime flag (e.g. 15s after trait unlock)
  // localStorage = persistent flag across sessions
  // If either true, mutation is "visibly active"

  if (isActive) {
    document.body.classList.add("mutation-mode");
    if (badge) badge.classList.remove("hidden");
    console.log("🧬 Mutation stored:", localStorage.getItem("snake_mutation"));
  } else {
    document.body.classList.remove("mutation-mode");
    if (badge) badge.classList.add("hidden");
  }
}
