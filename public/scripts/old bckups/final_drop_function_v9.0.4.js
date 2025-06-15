
function drop() {
  if (!collide(current.shape, current.row + 1, current.col)) {
    current.row++;
  } else {
    // 💣 Check if explosive piece BEFORE merge
    if (
      current.shape.length === 1 &&
      current.shape[0].length === 1 &&
      current.shape[0][0] === 6
    ) {
      const cx = current.col;
      const cy = current.row;
      const countdown = Math.floor(Math.random() * 30) + 1;

      activeExplosive = { x: cx, y: cy, countdown, start: Date.now() };

      setTimeout(() => {
        explode(cx, cy);
        activeExplosive = null;
      }, countdown * 1000);
    }

    merge();
    clearLines();

    // 🧱 Spawn new piece
    current = {
      shape: nextPiece,
      row: 0,
      col: 3
    };
    nextPiece = randomPiece();
    renderNextBlock(nextPiece);

    // 🧠 Game Over check
    if (collide(current.shape, current.row, current.col)) {
      clearInterval(gameInterval);
      onTetrisGameOver(score);

      const modal = document.getElementById("game-over-modal");
      const finalScoreText = document.getElementById("final-score-text");

      if (modal && finalScoreText) {
        finalScoreText.textContent = `You earned $${score} DSPOINC`;
        modal.classList.remove("hidden");
      }

      if (document.getElementById("leaderboard-list")) {
        loadLeaderboard();
      }

      return;
    }
  }

  draw(); // ✅ Redraw updated state
}
