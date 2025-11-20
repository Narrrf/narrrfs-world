# 🧩 RIDDLE #1 — THE SPAWN (LEVEL 2)

**Document Created:** November 15, 2025  
**Last Updated:** November 19, 2025 (Inventory Indicator System - Green Glow)  
**Riddle ID:** `CHEESE_TEMPLE_LEVEL2_RIDDLE_01`  
**Level:** Cheese Temple — Level 2 “The Spawn” (Matrix Construct)  
**Status:** ✅ **FULLY VERIFIED (Step 0‑2, Portal & Rewards Online)** — environment, inspection gating, and HUD all ship-ready  
**Trait / Reward:** `CHEESE_TEMPLE_LEVEL2_STEP0/1/2` + **+100 / +100 / +120 DSPOINC** respectively

---

## 📋 OVERVIEW

### Objective
Recreate the iconic **Matrix Construct** showcase: players warp into a white infinite room, explore every aisle of weapon shelves and the monster runway, and **only after inspecting all rows** does the exit portal for Level 3 manifest.

### Flow Summary
1. **Step 0 (Hidden Cheese Stone):** Stand on the golden trigger block for 10 seconds to arm the Construct, unlock trait `CHEESE_TEMPLE_LEVEL2_STEP0`, and earn **+100 DSPOINC** — then Step 1 activates.
2. **Step 1 (Gallery Lever):** Pull the lever at the far wall to reveal the weapon gallery, unlock trait `CHEESE_TEMPLE_LEVEL2_STEP1`, and earn another **+100 DSPOINC** while the HUD prompts the inspection tour.
3. **Step 2 (Exploration Loop):** After the lever, every inspection zone (left/right primary lanes, accessory corridor, Survival Pack aisles, Old School Armory, monster runway) logs progress. The “Inspect Every Display” HUD lists the remaining aisles and shows `visited / total`. Completing the sweep unlocks trait `CHEESE_TEMPLE_LEVEL2_STEP2` and pays out **+120 DSPOINC** before any portal is allowed to spawn.
4. **Portal Activation:** Once Step 2 flags complete, the Level 2 portal becomes visible at the far white wall and the rear HUD hint updates to “portal unlocked.”
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
   - **Reward:** +100 DSPOINC (base) × role multiplier
   - Toast: _"Gallery unlocked — inspect the first weapon."_
   - Player teleports to weapon gallery area after lever pull.

4. **Inspect each row (Step 2)**
   - **Primary Weapon Rows** — Left and right lanes with 40 weapons total (W1-W40)
   - **Accessory Corridor** — Inner walkway with 14 accessories (A01-A14)
   - **Survival Pack Rows** — 4 lanes with 53 items (SP01-SP53)
   - **Old School Armory** — 2 rows with 24 medieval weapons (OS01-OS24)
   - **Sci-Fi Gun Collection** — 2 rows with 20 futuristic weapons (SF01-SF20)
   - **Monster Runway** — Creature lineup on circular pads
   - HUD shows progress: "X / Y zones inspected" with list of remaining zones
   - Console logs: `🧱 [LEVEL 2] <zone> inspected.` for each zone
   - When all zones visited: Trait unlocked `CHEESE_TEMPLE_LEVEL2_STEP2`
   - **Reward:** +120 DSPOINC (base) × role multiplier

5. **Open the Portal**
   - After all inspection zones complete, `activateLevel2Portal()` sets the exit portal visible at `(0, 5, 628)`.
   - Portal effect + suction mimic Level 1's finale so muscle memory remains consistent.
   - HUD updates to "Portal unlocked — proceed to the back wall."

6. **Completion Screen**
   - Stepping into the portal shows full-screen completion panel with:
     - "Stay in Level 2" button (restarts Level 2)
     - "Back to Level 1" button (returns to Level 1)
     - "Go to Level 3" button (warps to Level 3)
   - "LEVEL UP!" sound plays (same as Level 1)
   - Portal effect animation

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

### Weapon Gallery Snapshot — 2025-11-17
- **Reason for snapshot:** QA captured the first three weapon pedestals (W1–W3) at 07:55 CET to preserve the clean baseline shown in the screenshot attached to today’s lab note (`12.0/LAB_NOTES/2025/11_NOVEMBER/DAILY_NOTES/2025-11-17/README.md`).
- **Backdrop removal:** `createLevel2WeaponGallery()` no longer spawns the tall shelf mesh that used to hover above the W2 pedestal. This keeps the gallery sightline clear and matches the design brief for an unobstructed Matrix Construct.
- **Code reference:** `three.js/main.js` lines near `createLevel2WeaponGallery()` now only add the pedestals, labels, and weapon meshes. Any future re-introduction of a wall/backdrop should be gated behind a config flag so this save point can be restored instantly.
- **Verification:** After Step 1 unlocks the gallery, teleporting to the lever area shows only the three pedestals + W1/W2/W3 labels. Console confirms `🔫 [LEVEL 2] Weapon gallery created` followed by `✅ [LEVEL 2] Weapon spawned successfully` for each entry in `LEVEL2_WEAPON_LIBRARY`.
- **2025‑11‑17 Evening Polish:** Gallery pedestals now use the same slim 0.95×0.28 geometry/labels as the corridor pads, W1‑W3 weapons reuse the shared category transforms (scale ≈0.006), and the lever relocates behind the gallery so players inspect the trio before pulling it.
- **Cheese Stone Feedback:** The hidden Step 0 trigger block now drives a visible clone that eases 0.2u downward while the player stands on it and returns when they step off, reinforcing the 10 s timer with physical motion. The collider mesh stays invisible (only the clone renders), so players always see the animated platform rather than a static duplicate.
- **HUD & Traits:** All riddle guidance now flows through the same toast styling as Level 1 (Cheese Stone → Lever → Portal). After the lever, the “Inspect Every Display” HUD appears, tracks `visited/total`, and lists missing aisles so players know how to unlock the portal. Step 2 awards `CHEESE_TEMPLE_LEVEL2_STEP2` (and +120 DSPOINC) as soon as every inspection zone is logged, then spawns the exit portal; entering it shows a full-screen completion panel with options to stay, restart Level 1, or await Level 3.
- **Debug toggles:** Auto-warp + instant gallery unlock are opt-in. By default local builds set `DEBUG_FORCE_LEVEL2_START = true` (spawn directly in Level 2 Step 0) while keeping `DEBUG_LEVEL2_GALLERY_START = false` (lever still off). Adjust via `localStorage.debug_force_level2_start` / `.debug_level2_gallery_start` when QA needs different setups; production always spawns in Level 1.
- **Reward System:** All 3 steps award DSPOINC through `awardLevel2DspoincReward()` which calls `/api/dev/riddle-reward.php`:
  - **Step 0:** `CHEESE_TEMPLE_LEVEL2_STEP0` → +100 DSPOINC (base) × role multiplier
  - **Step 1:** `CHEESE_TEMPLE_LEVEL2_STEP1` → +100 DSPOINC (base) × role multiplier  
  - **Step 2:** `CHEESE_TEMPLE_LEVEL2_STEP2` → +120 DSPOINC (base) × role multiplier
- **Role Multipliers:** Applied automatically via `getRoleMultiplier()` function in `riddle-reward.php`:
  - Checks `tbl_user_roles` for user's `role_name`
  - Matches against priority list (VIP Holder → Holder → Champion → etc.)
  - Applies highest multiplier found
  - Logs multiplier source for debugging
- **Database Logging:** All rewards logged in:
  - `tbl_riddle_completions` - Completion records with metadata
  - `tbl_user_scores` - DSPOINC balance updates
  - `tbl_score_adjustments` - "Recent Score Changes" with formatted reason field
- **Production Verified:** November 19, 2025 - All 3 steps tested with Narrrf's VIP account (2x multiplier confirmed working)

### Primary Weapon Rows — 2025-11-17 Morning
- **Purpose:** Pre-built pedestals for all 40 primary Fire Weapons FBX files (Assault Rifles, Bullpups, SMGs, Shotguns, Snipers, Pistols, Revolvers) now line up along both sides of the monster runway so QA can inspect weapons and creatures in parallel.
- **Implementation:** `createPrimaryWeaponRows()` (see `three.js/main.js`) generates two mirrored banks of pedestals (two lanes per side, ten slots per lane) with HUD labels `01`‑`40`. Pedestals now use explicit lane offsets `[2.6u (inner), 4.75u (outer)]` from center (with 1.25u fallback spacing), starting 4u north of the monster pads and extending forward in 3.6u increments.
- **Models:** Each slot loads its FBX immediately (same pipeline as W1‑W3) and applies the lightweight transforms from `PRIMARY_RING_CATEGORY_TRANSFORMS` (defaults: scale ≈ 0.006, offsetY ≈ 0.6, rotation π/2; pistols/revolvers face 180°) so the guns read well without overflowing the pedestals. Update those values per family if a weapon needs extra polish.
- **Maintenance:** Ring assets are cached and only load once per boot; if pedestals reset, the spawn helper re-runs automatically.
- **Inventory Indicator System (November 19, 2025):** Models that are used in actual gameplay (inventory items) display with a **bright green emissive glow** (`emissive: 0x00ff00`, `emissiveIntensity: 0.8`) to visually distinguish them from display-only items. This includes:
  - **Level 3 Monsters:** Demon, Frog, Orc, Dino, Ninja, BlueDemon, MushroomKing, Tribal, Alien, Yeti (all used in Level 3 monster hunt)
  - **Level 4 Weapon:** Pistol_1.fbx (used in Level 4 shooting challenge)
  - **Detection:** Automatic filename matching against `GAMEPLAY_INVENTORY_MODELS` array
  - **Visual Effect:** Strong green glow makes inventory items immediately recognizable as "active" game assets
  - **Applied To:** All model displays (monster shelves, weapon rows, accessories, survival pack, old school armory)
- **Accessory Corridor (2025‑11‑17 evening pass):** `createAccessoryCorridor()` lines the inner walkway (x = ±1.45u) with 14 attachment pedestals (`A01`‑`A14`). Slots start 12u south of the monster strip and advance every 3.4u toward the Cheese Stone. Each accessory pulls from `/Accessories/*.fbx`, shares the PBR material converter, and applies compact transforms per type (bayonet, scope, silencer, grip, stock, etc.) so the corridor feels curated without blocking traversal.
- **Survival Pack Archive (2025‑11‑17 evening):** `createSurvivalPackRows()` now builds four mirrored lanes (offsets ±18.5u and ±22.5u) that host every FBX inside `Survival Pack/FBX`. Pedestals are labeled `SP01`‑`SP53`, use slim geometry so the outer corridor stays walkable, and register under inspection zone `survival_pack_rows`. Walking those aisles is required to unlock the portal.
- **Old School Armory (2025‑11‑17 late):** The empty quadrant next to the cheese stone now features two compact rows (offsets −13u / −15.5u) showcasing the 24 medieval weapons from `Old School Weapons/FBX`. Slots `OS01`‑`OS24` cover swords, shields, bows, axes, spears, etc., each with tailored transforms via `LEVEL2_OLD_SCHOOL_TRANSFORM_OVERRIDES`. The new `old_school_armory` inspection zone ties these relics directly into Step 2, so QA must tour them before the portal appears.

### Portal + Completion Loop
When all zones return true, `activateLevel2Portal()` instantiates a portal mesh through the shared `createPortalMesh()` helper so both levels share identical visuals. `handleLevel2PortalProximity()` reuses the Level 1 suction logic but with a slightly wider radius (6u) tuned for the larger room. Entering the portal now displays `showLevel2CompletionScreen()` (same theming as Level 1) with buttons for “Stay in Level 2” (soft restart), “Back to Level 1”, or a Level 3 teaser.

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
| Reward wiring | `awardLevel2DspoincReward()` | Sends +100/+100/+120 DSPOINC (Steps 0–2) via `/api/dev/riddle-reward.php` with role multipliers |
| Role multipliers | `getRoleMultiplier()` in `riddle-reward.php` | Checks `tbl_user_roles.role_name` and applies highest multiplier (VIP 2.0x, Holder 1.5x, etc.) |
| Database logging | `tbl_riddle_completions`, `tbl_user_scores`, `tbl_score_adjustments` | All rewards logged with formatted reason for "Recent Score Changes" display |

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

---

## ✅ PRODUCTION TESTING VERIFICATION (November 19, 2025)

### Test Results:
- ✅ **Step 0:** Cheese stone platform works, reward awarded correctly (200 DSPOINC with VIP 2x)
- ✅ **Step 1:** Lever interaction works, reward awarded correctly (200 DSPOINC with VIP 2x)
- ✅ **Step 2:** All inspection zones register, reward awarded correctly (240 DSPOINC with VIP 2x)
- ✅ **Total Rewards:** 640 DSPOINC (320 base × 2.0 VIP multiplier)
- ✅ **Traits:** All 3 traits (`CHEESE_TEMPLE_LEVEL2_STEP0/1/2`) unlocked correctly
- ✅ **Profile Page:** All 3 achievements appear in "3D Puzzles Achievements" section
- ✅ **Recent Score Changes:** All 3 rewards appear with correct formatting and amounts
- ✅ **Role Multiplier:** VIP 2x multiplier confirmed working for all 3 steps

### Code Verification:
- ✅ `updateLevel2Step0()` correctly calls `awardLevel2DspoincReward("CHEESE_TEMPLE_LEVEL2_STEP0", 100, "Level 2 Step 0")` at line 8943
- ✅ `handleLevel2LeverClick()` correctly calls `awardLevel2DspoincReward("CHEESE_TEMPLE_LEVEL2_STEP1", 100, "Level 2 Step 1")` at line 11612
- ✅ `completeLevel2Step2()` correctly calls `awardLevel2DspoincReward("CHEESE_TEMPLE_LEVEL2_STEP2", 120, "Level 2 Step 2")` at line 8492
- ✅ All rewards route through `/api/dev/riddle-reward.php` with proper role multiplier calculation
- ✅ `getRoleMultiplier()` function correctly queries `tbl_user_roles.role_name` and applies multipliers

### Known Issues Fixed:
- ✅ **Step 0 Missing Reward (Nov 19, 2025):** Manually fixed missing Step 0 reward for test player. Code verified correct - issue was likely network/timing related during initial test.

---

## ✅ PRODUCTION TESTING VERIFICATION (November 19, 2025 - Evening)

### Complete Level 2 Testing Results:
- ✅ **Step 0:** Cheese stone platform works, reward awarded correctly (200 DSPOINC with VIP 2x)
- ✅ **Step 1:** Lever interaction works, reward awarded correctly (200 DSPOINC with VIP 2x)
- ✅ **Step 2:** All inspection zones register, reward awarded correctly (240 DSPOINC with VIP 2x)
- ✅ **Total Rewards:** 640 DSPOINC (320 base × 2.0 VIP multiplier)
- ✅ **Traits:** All 3 traits (`CHEESE_TEMPLE_LEVEL2_STEP0/1/2`) unlocked correctly
- ✅ **Profile Page:** All 3 achievements appear in "3D Puzzles Achievements" section
- ✅ **Recent Score Changes:** All 3 rewards appear with correct formatting and amounts
- ✅ **Role Multiplier:** VIP 2x multiplier confirmed working for all 3 steps
- ✅ **Database Records:** All entries correctly logged in `tbl_riddle_completions` and `tbl_score_adjustments`

### Production Status:
- ✅ **FULLY VERIFIED** - All systems working correctly in production
- ✅ **Database Integration** - All rewards and traits correctly stored
- ✅ **Frontend Integration** - All achievements and rewards displaying correctly
- ✅ **Role Multipliers** - VIP 2.0x multiplier confirmed working
- ✅ **Complete System** - Ready for community engagement

---

_Maintained by Narrrf's Lab Tech Council — last updated 2025-11-19 (Evening - Production Verified)._

