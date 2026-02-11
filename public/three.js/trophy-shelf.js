// 🏆 Trophy image base path
// - On profile.html ( /public/profile.html )  → images live in "img/..."
// - On 3d-riddle-game.html ( /public/three.js/3d-riddle-game.html ) → images are in "../img/..."
const TROPHY_ASSET_BASE = (function () {
  const path = window.location.pathname || "";

  // Any page inside /three.js/ should go one level up to reach /public/img/
  if (path.includes("/three.js/")) {
    return "../img/";
  }

  // Default for normal pages like /public/profile.html
  return "img/";
})();
console.log("🏆 [TROPHIES] Using asset base:", TROPHY_ASSET_BASE);


const trophies = {
  "VIP Holder": {
    img: `${TROPHY_ASSET_BASE}trophy_VIP.png`,
    label: "VIP Cheese Lord",
  },
  "Holder": {
    img: `${TROPHY_ASSET_BASE}trophy_holder.png`,
    label: "🏆 Holder",
  },
  "Cheese Hunter": {
    img: `${TROPHY_ASSET_BASE}trophy_cheese_hunter.png`,
    label: "🧀 Cheese Hunter",
  },
  "🧀 Cheese Hunter": {
    img: `${TROPHY_ASSET_BASE}trophy_cheese_hunter.png`,
    label: "🧀 Cheese Hunter",
  },
  "🏆 Holder": {
    img: `${TROPHY_ASSET_BASE}trophy_holder.png`,
    label: "🏆 Holder",
  },
  "🎴 VIP Holder": {
    img: `${TROPHY_ASSET_BASE}trophy_VIP.png`,
    label: "🎴 VIP Holder",
  },
  "PokerOG": {
    img: `${TROPHY_ASSET_BASE}trophy_PokerOG.png`,
    label: "Poker OG",
  },
  "Champion": {
    img: `${TROPHY_ASSET_BASE}trophy_champion.png`,
    label: "Champion",
  },
  "Rumble": {
    img: `${TROPHY_ASSET_BASE}trophy_rumble.png`,
    label: "Rumble Champ",
  },
  "Engage": {
    img: `${TROPHY_ASSET_BASE}trophy_engage.png`,
    label: "Engager",
  },
  "Moderator": {
    img: `${TROPHY_ASSET_BASE}trophy_moderator.png`,
    label: "Moderator",
  },
  "Server Booster": {
    img: `${TROPHY_ASSET_BASE}trophy_serverbooster.png`,
    label: "Server Booster",
  },
  "Alpha Caller": {
    img: `${TROPHY_ASSET_BASE}trophy_alphacaller.png`,
    label: "Alpha Caller",
  },
  "Crypto Corn Friends": {
    img: `${TROPHY_ASSET_BASE}trophy_cryptocornfriends.png`,
    label: "Corny Companion",
  },
  "Rabbit Friends": {
    img: `${TROPHY_ASSET_BASE}trophy_rabbitfriends.png`,
    label: "Bunny Buddy",
  },
  "Kaleido Friends": {
    img: `${TROPHY_ASSET_BASE}trophy_kaleidofriends.png`,
    label: "Kaleido Supporter",
  },
  "Weedery Friends": {
    img: `${TROPHY_ASSET_BASE}trophy_weederyfriends.png`,
    label: "Weedery 🌿",
  },
  "Community Member": {
    img: `${TROPHY_ASSET_BASE}trophy_community.png`,
    label: "Community Member",
  },
  "Verifiziert": {
    img: `${TROPHY_ASSET_BASE}trophy_verified.png`,
    label: "Verified",
  },
  "Founder": {
    img: `${TROPHY_ASSET_BASE}trophy_founder.png`,
    label: "👑 Founder",
  },
  "Early Bird": {
    img: `${TROPHY_ASSET_BASE}trophy_earlybird.png`,
    label: "🐦 Early Bird",
  },
  "Season Tester": {
    img: `${TROPHY_ASSET_BASE}trophy_season_tester.png`,
    label: "🎮 Season Tester",
  },
  "Monthly Tetris Legend": {
    img: `${TROPHY_ASSET_BASE}trophy_monthly_tetris_legend.png`,
    label: "🏆 Monthly Tetris Legend",
  },
  "Monthly Snake Legend": {
    img: `${TROPHY_ASSET_BASE}trophy_monthly_snake_legend.png`,
    label: "🐍 Monthly Snake Legend",
  },
  "Monthly Cheese Invaders Legend": {
    img: `${TROPHY_ASSET_BASE}trophy_monthly_cheese_invaders_legend.png`,
    label: "👾 Monthly Cheese Invaders Legend",
  },
};


/**
 * Render the trophy shelf into the element with id="cheeseShelf".
 * userRoles: array of role names, e.g. ["VIP Holder", "Cheese Hunter", ...]
 */
function renderTrophyShelf(userRoles = []) {
  console.log("🏆 renderTrophyShelf called with:", userRoles);

  const shelf = document.getElementById("cheeseShelf");
  if (!shelf) {
    console.warn("❌ Trophy shelf element #cheeseShelf not found – nothing to render into.");
    return;
  }

  // Clear existing
  shelf.innerHTML = "";

  const uniqueRoles = [...new Set(userRoles)];

  console.log("Rendering trophy shelf with roles:", uniqueRoles);
  console.log("Available trophies:", Object.keys(trophies));

  uniqueRoles.forEach((role) => {
    // Exact match first
    let trophy = trophies[role];

    // If no exact match, try cleaned variants
    if (!trophy) {
      const cleanRole = role.replace(/[🏆🎴🧀]/g, "").trim();
      trophy = trophies[cleanRole];

      if (!trophy) {
        if (cleanRole === "Holder") {
          trophy = trophies["🏆 Holder"];
        } else if (cleanRole === "VIP Holder") {
          trophy = trophies["🎴 VIP Holder"];
        } else if (cleanRole === "Cheese Hunter") {
          trophy = trophies["🧀 Cheese Hunter"];
        } else if (
          cleanRole === "Season Tester" ||
          (cleanRole.toLowerCase().includes("season") &&
            cleanRole.toLowerCase().includes("tester"))
        ) {
          trophy = trophies["Season Tester"];
        }
      }
    }

    if (trophy) {
      const trophyEl = document.createElement("div");
      trophyEl.className =
        "flex flex-col items-center animate-pop transition-transform hover:scale-105";
      trophyEl.innerHTML = `
        <img src="${trophy.img}" alt="${trophy.label}"
             class="w-16 h-16 sm:w-20 sm:h-20 drop-shadow mb-2" />
        <span class="text-[11px] sm:text-xs text-yellow-100 font-semibold text-center">
          ${trophy.label}
        </span>
      `;
      shelf.appendChild(trophyEl);
    }
  });

  console.log("🏆 Trophy shelf done. Total trophies added:", shelf.children.length);
}

// ✅ Expose for 3D game & profile to call
window.renderTrophyShelf = renderTrophyShelf;

// (Optional) tiny helper for manual console testing:
// window.__testRenderTrophies = () => renderTrophyShelf(Object.keys(trophies));
