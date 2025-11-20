# LEVEL 3 MOVING LABYRINTH & LIGHTING PASS — 2025-11-19

## Goal
- Make Level 3 (“The Hunt”) feel like a proper 3D puzzle zone by adding large moving barriers and harsher lighting so the monster chase is more tactical.

## Implementation
1. **Moving Wall Specs**
   - Added `LEVEL3_MOVING_WALLS` constant (4 entries) defining width/height/depth, offset from arena origin, movement axis, amplitude, speed, and start delay.
2. **Mesh & Motion Helpers**
   - `createLevel3MovingWalls()` builds the slabs, sets shadow flags, records half-extents, and saves motion metadata (`axis`, `base`, `offset`, `direction`).
   - `updateLevel3MovingWalls(delta)` runs every frame to oscillate each slab between ±amplitude (with optional delays so they de-sync).
   - `resetLevel3MovingWalls()` re-centers slabs whenever the level restarts so the puzzle is deterministic from spawn.
3. **Collision Handling**
   - `handleLevel3Collisions()` now calls `resolvePlayerAgainstLevel3Wall(wall)` for every slab; this treats the slab as an expanded AABB (adds `PLAYER_RADIUS`) and pushes the player out along the smallest penetration axis while zeroing the corresponding velocity.
4. **Lighting & Shadows**
   - Global shadow map re-enabled (`PCFSoftShadowMap`).
   - Floor/shell receive shadows; directional key light now `0xfff2d0 @ 1.4` with full shadow camera setup, ambient dropped to 0.35, plus a warm rim point light for contrast.

## Verification
- Warped into Level 3 via God Mode and watched all four slabs move (with staggered timing).
- Walked/runned into each wall edge and confirmed player gets pushed out smoothly (no jitter).
- Chased monsters through maze; captures + DSPOINC/trait flow unaffected.
- Checked lighting in third-person: slabs cast noticeable shadows, arena feels darker/moodier.
- Allowed two slabs to converge on the player to confirm the crush detector fires (toast + automatic restart after ~0.2s of zero clearance).
- ✅ Retested with slabs converging in the central intersection to ensure the new “center-based” crush detection fires even when the player is surrounded by four walls (no directional dependency).
- ⚠️ Next Session: Run another crush regression (center + near-spawn) after the break to double-confirm no safe spots remain.

## Files Modified
- `three.js/main.js`
- `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- `12.0/ACTIVE_STATUS/DAILY_STATUS_2025-11-19.md`
- `12.0/ACTIVE_STATUS/QUICK_STATUS.md`

