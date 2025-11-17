# 🧩 RIDDLE #1 — THE SPAWN (LEVEL 2)

**Document Created:** November 15, 2025  
**Riddle ID:** `CHEESE_TEMPLE_LEVEL2_RIDDLE_01`  
**Level:** Cheese Temple — Level 2 “The Spawn” (Matrix Construct)  
**Status:** ⛳ **PLAYABLE PROTOTYPE** — environment + progression logic live, GLB showcases pending  
**Trait / Reward:** _TBD_ (reward wiring deferred until Level 2 balancing)

---

## 📋 OVERVIEW

### Objective
Recreate the iconic **Matrix Construct** showcase: players warp into a white infinite room, explore every aisle of weapon shelves and the monster runway, and **only after inspecting all rows** does the exit portal for Level 3 manifest.

### Flow Summary
1. **Step 0 (Hidden Cheese Stone):** Immediately after arrival, a golden trigger block spawns near the left aisle. Standing on it for 10 seconds (same audio cue as Level 1) arms the Construct riddle, unlocks trait `CHEESE_TEMPLE_LEVEL2_STEP0`, and cues Step 1.
2. **Step 1 (Gallery Lever):** A lever materializes at the far wall. Pulling it (same interaction + sound effects as Level 1) unlocks the gallery blocks behind the shelves, spawns the first weapon display, and unlocks trait `CHEESE_TEMPLE_LEVEL2_STEP1`.
3. **Exploration Loop:** After Step 1 completes, the three inspection zones (left shelves, right shelves, monster runway) begin logging progress.
4. **Portal Activation:** Once every zone reports `visited = true`, the Level 2 portal becomes visible at the far white wall.
5. **Completion Placeholder:** Entering the portal currently shows a “Level 3 coming soon” toast while we scaffold Level 3.

---

## 🎮 HOW TO SOLVE (PLAYER VIEW)

1. **Arrive in The Spawn**
   - Warp spawns the player at `origin (0, 1, 575)` with fog + ambient light turned white.
   - HUD toast: _“Inspect every shelf row and the creature lineup…”_

2. **Wake the hidden cheese stone (Step 0)**
   - Stand on the golden platform for 10 seconds (same countdown/audio as Level 1).
   - Toast: _“Riddle awakened — document every aisle.”_
   - Trait unlocked: `CHEESE_TEMPLE_LEVEL2_STEP0`.

3. **Pull the lever (Step 1)**
   - Lever appears at the far wall once Step 0 completes.
   - Press `E` near the lever to unlock the gallery; sound + texture swap match Level 1.
   - Trait unlocked: `CHEESE_TEMPLE_LEVEL2_STEP1`.
   - Toast: _“Gallery unlocked — inspect the first weapon.”_

4. **Inspect each row**
   - **Left Shelf Lane** — walk the entire aisle; pedestals mark future weapon GLBs.
   - **Right Shelf Lane** — mirrored layout for armor / gadgets.
   - **Creature Lineup** — monsters stand on circular pads in a row; walking past them logs the final zone.
   - Progress is silent for now but dev console logs `🧱 [LEVEL 2] <zone> inspected.`; riddle HUD update planned.

5. **Open the Portal**
   - After the third zone, `activateLevel2Portal()` sets the exit portal visible at `(0, 5, 628)`.
   - Portal effect + suction mimic Level 1’s finale so muscle memory remains consistent.

6. **Placeholder Finish**
   - Stepping into the portal displays a toast: “Level 3 portal coming soon — thanks for exploring The Spawn!”
   - Once Level 3 is ready we replace this with the actual warp + reward hooks.

---

## 🔧 TECHNICAL IMPLEMENTATION

### Environment Scaffolding
`level2Config` centralizes every dimension so art tweaks stay data-driven (`three.js/main.js`):

```4758:4793:three.js/main.js
const level2Config = {
  size: 60,
  origin: new THREE.Vector3(0, 0, 600),
  wallHeight: 24,
  spawnPosition: new THREE.Vector3(0, 1, 575),
  portalPosition: new THREE.Vector3(0, 5, 628)
};
```

`buildLevel2WhiteRoom()` constructs:
- 240×240 plane, subtle grid, back-sided cube shell for endless white feel  
- Ambient + directional lights tuned for zero shadows  
- Two shelf rows (6 segments each, 3 pedestals per segment) recorded inside `level2State.previewAnchors` for future GLB placement  
- Monster runway pads (6 positions) for the creature lineup

### Progress Tracking
Each aisle registers an axis-aligned bounding box via `addLevel2InspectionZone(id, label, bounds)`. `updateLevel2(delta)` checks the player capsule center every frame; once a zone contains the player, it flips `visited = true` and logs to console.

### Portal + Completion Loop
When all zones return true, `activateLevel2Portal()` instantiates a portal mesh through the shared `createPortalMesh()` helper so both levels share identical visuals. `handleLevel2PortalProximity()` reuses the Level 1 suction logic but with a slightly wider radius (6u) tuned for the larger room. Completion currently calls `showLevel2PortalPlaceholder()` until Level 3 is implemented.

### Warp + UX Layer
`warpToLevel2()` now replaces the previous “Next Level coming soon” alert:
- Builds the room (first call only), calls `resetLevel2Progress()`, hides Level 1 floating cheese + riddle UI, reapplies fog, and teleports the capsule via `setPlayerFeetPosition()`.
- Displays a temporary toast via `showLevel2IntroToast()` with instructions.
- `level2State.group` visibility toggles between Level 1 and 2, so the room can coexist in the same scene without reloading geometry.
 - `loadLevel2PreviewModels()` consumes the precomputed shelf anchors and drops in the first Monster 1 statue (`Blob/glTF/GreenBlob.gltf`) beside the cheese stone so we can validate the Construct showcase pipeline before loading the full catalog.

### Collision & Bounds
Level 1 collisions rely on the BVH mesh; Level 2 uses a lightweight override:

```4149:4215:three.js/main.js
if (currentLevel === LEVEL_IDS.LEVEL2) {
  handleLevel2Collisions();
  return;
}
```

`handleLevel2Collisions()` clamps the capsule to:
- Floor plane at `origin.y + PLAYER_RADIUS`
- Axis bounds defined by `level2Config.size`
- Resets velocities whenever clamped, ensuring the avatar cannot fall through the Construct or walk outside the white room.

---

## ⚙️ CONFIGURATION & FUTURE HOOKS

| Setting | Location | Notes |
| --- | --- | --- |
| Room size / origin | `level2Config` | Update once final world scale is approved |
| Shelf counts / spacing | `shelfSegments`, `shelfSpacing` inside `buildLevel2WhiteRoom()` | Increase when adding more GLBs |
| Inspection zones | `addLevel2InspectionZone()` calls | Duplicate pattern for new riddles |
| Portal placement | `level2Config.portalPosition` | Shares `Portal1.png` texture + suction constants |
| Reward wiring | _Pending_ | Hook into `/api/user/riddle-reward.php` once Level 2 scoring is defined |

---

## 🧪 TEST PLAN (CURRENT BUILD)

1. **Trigger warp** — Finish Level 1 portal, confirm automatic teleport + white fog theme.
2. **Clipping test** — Attempt to sprint diagonally; verify capsule clamps before leaving the room.
3. **Inspection detection** — Walk one aisle at a time; ensure console logs each visit and portal remains hidden until all three are complete.
4. **Portal spawn** — After final zone, confirm portal visibility + suction behavior.
5. **Portal entry** — Step into portal and confirm placeholder toast appears exactly once.
6. **Restart Level 1** — Call `restartLevel1()` (pause menu) and ensure environment resets to Cheese Temple (fog, floating cheese visible again).

---

## 🚀 NEXT STEPS

1. **GLB Showcase** – Attach actual weapon + creature models to `level2State.previewAnchors`.  
2. **Riddle HUD** – Extend `updateRiddleProgressUI()` to display Level 2 progress (3/3 inspected).  
3. **Reward Hook** – Decide on trait + DSPOINC reward for “The Spawn” and tie into `updateLevel2()` complete branch.  
4. **Portal Destination** – Replace placeholder toast with Level 3 warp once room spec is approved.  
5. **Accessibility Tweaks** – Optional mini-map overlay for the Construct to help new players track visited aisles.

---

_Maintained by Narrrf’s Lab Tech Council — last updated 2025-11-15._

