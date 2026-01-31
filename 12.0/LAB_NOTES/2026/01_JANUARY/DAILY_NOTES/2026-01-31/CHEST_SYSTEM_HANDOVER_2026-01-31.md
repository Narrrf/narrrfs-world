# 🎁 HANDOVER – Narrrfs World Chest System & Reset Logic (Desktop + VR)

**Date Received:** January 31, 2026  
**Project:** Narrrfs World – 3D Riddle Game  
**Status:** ✅ New chest3 GLBs (closed/opened) running – still needs little review  

**Main Files:**
- `public/three.js/main.js` – main loop, level loading, XR setup, input aggregation
- `public/three.js/chest-system.js` – chest registry, dual-model handling, interaction, reset
- `public/three.js/gui-system.js` – God Mode / dev UI buttons, including chest reset

---

## 0. Project Context (for orientation)

Browser 3D game using Three.js with multiple levels. Target VR device: Meta Quest (WebXR). Chest system affects both desktop and VR.

---

## 1. Chest System Architecture (Current State)

### 1.1 Dual-Model Chests: Closed vs Opened

Chests now use **two separate GLB models**:

| State | Path |
|-------|------|
| **Closed** | `/textures/3d models/chest3/chest-closed.glb` |
| **Opened** | `/textures/3d models/chest3/chest-opened.glb` |

### 1.2 ChestSystem Instantiation (main.js)

```javascript
chestSystem = new ChestSystem(
  scene,
  loadModel,
  processWeaponMaterial,
  grassSystem,
  resolveAssetPath,
  {
    closedModelPath: "/textures/3d models/chest3/chest-closed.glb",
    openedModelPath: "/textures/3d models/chest3/chest-opened.glb",

    // 🔁 HARD RESET SUPPORT: recreate chests per level
    onRecreateLevelChests: (levelId) => {
      console.log("🔁 [CHEST SYSTEM] Recreating chests for level:", levelId);
      if (levelId === LEVEL_IDS.LEVEL1)      createLevel1Chests();
      else if (levelId === LEVEL_IDS.LEVEL2) createLevel2Chests();
      else if (levelId === LEVEL_IDS.LEVEL3) createLevel3Chests();
      else if (levelId === LEVEL_IDS.LEVEL4) createLevel4Chests();
      else if (levelId === LEVEL_IDS.LEVEL5) createLevel5Chests();
      else if (levelId === LEVEL_IDS.LEVEL6) createLevel6Chests();
      else console.warn("⚠️ [CHEST SYSTEM] No chest recreation handler for level:", levelId);
    }
  }
);
```

**Key point:** `onRecreateLevelChests(levelId)` is the only way the chest system asks the game to rebuild chests for a level. It delegates back to `createLevelXChests()` functions in main.js.

---

## 2. Chest Interaction: E Key → chestSystem.tryInteract

### 2.1 E-Key Handler in main.js

E key is **prioritized for chest interaction** before any other "use" logic (portals, levers, riddles, etc.):

```javascript
case "KeyE":
  if (!event.repeat) {
    // 1️⃣ Chest interaction first
    if (chestSystem && typeof chestSystem.tryInteract === "function") {
      let playerPos = camera?.position?.clone() || lerp(playerCollider.start, playerCollider.end, 0.5);
      if (playerPos) {
        const didChestInteract = chestSystem.tryInteract(playerPos, currentLevel);
        if (didChestInteract) break;  // 🛑 stop here if chest handled it
      }
    }
    // 2️⃣ Only if no chest handled it: legacy E logic (portal, lever, riddles, etc.)
  }
  break;
```

### 2.2 Runtime Safety Shim for tryInteract

If `ChestSystem.prototype.tryInteract` is not defined, a runtime helper is attached:

- **Production:** Only reject if chest is actually opened or explicitly locked. IGNORE `canInteract` (defaults false on fresh chests).
- **Localhost:** Extra forgiving – can always re-test chests even if opened/locked.

**Result:** Production chests open correctly; local dev can repeatedly test.

---

## 3. Chest Reset API (chest-system.js)

### 3.1 Global Reset: All Levels

```javascript
resetOpenedChests() {
  this.openedChestsCache?.clear();
  this.openedChestsLoaded = false;
  this.openedChestsLoadedForDiscordId = null;

  let totalReset = 0;
  this.chests.forEach((_, levelId) => {
    const count = this.resetOpenedChestsForLevel(levelId);
    totalReset += count;
  });
  return totalReset;
}
```

- Clears `openedChestsCache` entirely
- Resets global flags
- Iterates all levels and delegates to `resetOpenedChestsForLevel(levelId)`
- **Note:** Currently returns 1 per level (logging cosmetics; can be improved)

### 3.2 Per-Level Hard Reset

```javascript
resetOpenedChestsForLevel(levelId) {
  // 1) Clear persistence/cache entries for this level's chests
  // 2) FULL NUKE: clearLevel(levelKey) – remove all chest meshes for this level
  // 3) Recreate chests via onRecreateLevelChests(levelKey) – rebuild in CLOSED state
}
```

**Important:**
- Only affects chests for the given level
- Does NOT warp the player
- Does NOT reload level geometry
- Does NOT touch VR / player collider / spawn logic

**Optional improvement:** Return `levelChests.size` for accurate chest counts.

---

## 4. God Mode GUI Buttons (gui-system.js)

When `isLocal && godModeOn`, two reset buttons:

| Button | Action |
|--------|--------|
| **🔄 Reset Chests in CURRENT Level** | `config.onResetCurrentLevelChests()` |
| **🔁 Reset ALL Chests (ALL Levels)** | `config.onResetAllChests()` |

**Crucial:** Buttons do NOT perform level loads, warps, or restarts. They only call config callbacks and show alerts.

---

## 5. main.js Config Callbacks (GUI → ChestSystem)

| Callback | Behavior |
|----------|----------|
| `onResetCurrentLevelChests` | Calls `chestSystem.resetOpenedChestsForLevel(currentLevel)` |
| `onResetAllChests` | Calls `chestSystem.resetOpenedChests()` |

No warps/restarts. Just chest resets + toasts.

---

## 6. Behavior Summary (What Works Now)

### Desktop (local and prod)
- ✅ Chests start closed
- ✅ Press E near chest → `chestSystem.tryInteract()` → chest opens, model switches to opened variant, rewards apply
- ✅ **God Mode Reset Current Level:** Only affects current level; clears + rebuilds chests; no level reload
- ✅ **God Mode Reset ALL Chests:** Clears all levels; per-level reset under the hood

### VR
- ✅ Chest logic is the same (no VR-specific chest code yet)
- ✅ E-key is canonical; mapping VR button to `chestSystem.tryInteract(...)` will reuse logic

---

## 7. Next Steps for Cursor / LLM

### VR Controller → Chest Interaction
- In `vr-input-provider.js` / PlayerControls, map controller button (e.g. right-hand A/X or trigger) to same flow as E:
  1. Compute player/capsule center position
  2. Call `chestSystem.tryInteract(playerPosition, currentLevel)`

### Optional Improvements
- **Logging counts:** Change `resetOpenedChestsForLevel` to return `levelChests.size`
- **If "scene doesn't load after reset":** Likely NOT chest system (it doesn't touch level geometry). Investigate level-loading flags, `warpToLevel` / `restartLevel`, cleanup functions.

---

## 8. Hard Constraints (Do Not Violate)

- ❌ Do NOT add level warps as part of chest reset
- ❌ Do NOT touch level geometry in chest reset
- ✅ Chest reset is chest-only: clear + recreate chest meshes

---

**Status:** ✅ Synced | Chest system stable – VR controller mapping pending
