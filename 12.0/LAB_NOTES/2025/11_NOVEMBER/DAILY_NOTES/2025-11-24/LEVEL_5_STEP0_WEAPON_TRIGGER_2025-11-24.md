# LEVEL 5 · STEP 0 WEAPON TRIGGER — 2025-11-24

## 🎯 Goal
Add the classic cheese-stone trigger plate (“Step 0”) to **Level 5 – The Walk** so we can begin riddle development with the familiar activation pattern and weapon handoff (slots 1 + 2 like Level 4).

## ✅ What Shipped
1. **Cheese Plate Spawn**
   - New plate spawns beside the player’s spawn point via `createLevel5TriggerPlate()` in `three.js/main.js`.
   - Plate uses cheese-stone texture, sits at spawn height, and lives inside `level5State.group` for visibility toggling.
2. **Step 0 State Machine**
   - Added `level5RiddleState` (plate, timers, weapon flags).
   - `updateLevel5Step0()` detects when the player stands on the plate, animates the press-down, and tracks activation time.
3. **Weapon Unlock**
   - `activateLevel5Weapons()` grants access to weapon slots 1 & 2 (same viewmodels, sounds, bullets as Level 4).
   - Reuses the Level 4 shooter system by generalizing switching/shooting checks (`canUseLevel4ShooterSystem()`).
   - Pointer lock + toast prompt fire automatically.
4. **Input + Shooting Updates**
   - Number keys now check the shared shooter helper (so Level 5 can switch slots 1 and 2).
   - Mouse shooting handler listens for either Level 4 (Step1/Step2) or Level 5 Step0 weapons.
   - Bullet/heat/triple-shot loops run inside `updateLevel5()` when weapons are enabled, so we can test firing without monsters.
5. **Lifecycle Hooks**
   - New `resetLevel5RiddleState()` keeps the plate + weapon state clean whenever we warp/restart Level 5.
   - Warp/restart flows now reposition the plate next to spawn and disable shooter mode until the plate is triggered.

## 📂 Files Touched
- `three.js/main.js`
  - Added Level 5 riddle state, trigger plate creation, Step 0 update loop, weapon activation routine, shooter-system helper, and input modifications.
- `12.0/LAB_NOTES/.../DISCORD_GET_STARTED_REFRESH_2025-11-24.md` (already existed from earlier work; no change here).

## 🔜 Next Steps
- Design Step 1 for Level 5 (cheese objectives, monster placements, or partner NPCs).
- Hook DSPOINC + trait unlock once the new riddle steps are defined.
- Continue Discord onboarding refresh (remaining channels) once Level 5 storyline copy is approved.

