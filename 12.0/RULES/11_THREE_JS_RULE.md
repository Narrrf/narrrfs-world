# THREE.JS IMPLEMENTATION RULESET

Sign when applied: 🧀 three.js rule applied

1. **Scene Boot**
   - Set scene.background to a visible color during debugging; revert to dark once geometry renders.
   - Test visibility with scene.overrideMaterial = new THREE.MeshBasicMaterial({ color: 0x00ff00 }) if meshes vanish.
   - Keep camera frustum tight, never leave the camera at (0,0,0), and keep the world centered near the origin.

2. **Geometry & Instancing**
   - Generate block data via tools/generate-level.js and render with THREE.InstancedMesh grouped by block type.
   - No per-frame mesh creation; reuse geometries, materials, textures.
   - For static objects set matrixAutoUpdate = false and call updateMatrix() only when edits occur.

3. **Renderer Configuration**
   - Create renderer with { powerPreference: 'high-performance', antialias: !isMobile }.
   - Cap pixel ratio (<= 1.5 desktop, <= 1 mobile) and disable shadows on mobile unless required.
   - Render only on movement/animation; throttle idle frames.

4. **Textures & Materials**
   - Use power-of-two textures; set texture.encoding = THREE.sRGBEncoding for color/emissive maps.
   - Prefer MeshLambertMaterial for matte surfaces; avoid transparency unless necessary.

5. **Lighting & Shadows**
   - Minimal light setup (ambient + single directional). Keep shadow frusta tight and update maps only when geometry changes.

6. **Movement & Loop**
   - Use clock.getDelta() for frame-independent physics. Avoid allocations in animate().
   - Sprint/jump allowed, but clamp speed to maintain control.

7. **Performance Profiling**
   - Use scene.overrideMaterial with MeshBasicMaterial to detect GPU bottlenecks.
   - Monitor draw calls; instanced meshes should keep count low.
   - Limit mobile pixel ratio and disable expensive effects by default.

8. **Documentation & Sync**
   - Record changes in 12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md.
   - When following this ruleset in replies, include the sign 🧀 three.js rule applied.

9. **Riddle Note Command System (MANDATORY)**
   - **EVERY TIME** we work on a riddle, we MUST make a "Riddle Note" to update the riddle documentation.
   - When user types "Riddle Note" or "make a riddle note", update the specified riddle documentation.
   - Update the riddle file in `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_XX_[NAME].md`.
   - Document ALL changes: detection methods, timer behavior, difficulty adjustments, technical implementation details.
   - Update version number and changelog in the riddle document.
   - Include technical details: code locations, function names, line numbers, configuration options.
   - Format: "Riddle Note [RIDDLE_NUMBER]" - e.g., "Riddle Note 1" updates RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md
   - **This rule is MANDATORY** - every riddle work session must end with a riddle note update.

10. **Riddle DSPOINC Reward System (November 12, 2025)**
    - **Base Reward:** 500 DSPOINC per riddle completion
    - **Role Multipliers:** Applied automatically based on player's role (VIP: ×2.0, Holder: ×1.5, etc.)
    - **One-Time Reward:** Each riddle can only be completed once per player (duplicate prevention)
    - **API Endpoint:** `/api/dev/riddle-reward.php` - Awards DSPOINC rewards for solving riddles
    - **Database Table:** `tbl_riddle_completions` - Tracks riddle completions with unique constraint
    - **DSPOINC Tracking:** All rewards tracked in `tbl_user_scores` (game: `cheese_temple_riddles`, source: `riddle_completion`)
    - **DSPOINC Audit:** All rewards tracked in `tbl_score_adjustments` (admin: `system-riddle-reward`)
    - **HUD Integration:** DSPOINC balance automatically updated in pause menu and HUD
    - **Reward Notification:** Visual notification shows DSPOINC amount and multiplier
    - **Error Handling:** Comprehensive error handling for API failures and duplicate completions (409 Conflict)
    - **Implementation:** `completeRiddle()` function handles trait unlock + DSPOINC reward
    - **Status:** ✅ **PRODUCTION READY** (November 12, 2025)
    - **Documentation:** See `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
    - **Lab Notes:** See `12.0/LAB_NOTES/2025/11_NOVEMBER/DAILY_NOTES/2025-11-11/RIDDLE_DSPOINC_REWARD_IMPLEMENTATION.md`

11. **Weapon Rendering System for FBX Models (December 13, 2025)**
    - **CRITICAL:** FBX weapon models require special material processing to render correctly
    - **Material Brightening:** ALWAYS brighten dark materials (3x for brightness < 0.3, 2x for < 0.6)
    - **Emissive Glow:** Add emissive properties for very dark materials (brightness < 0.3)
    - **Duplicate Prevention:** ALWAYS check for and remove duplicate weapons before animations
    - **Position Preservation:** Use `preservePosition = true` during bobbing/recoil animations
    - **Visibility Enforcement:** ALWAYS set `frustumCulled = false` and `renderOrder = 999`
    - **Scale Configuration:** Use `targetSize: 0.35` for all weapon types (consistent first-person size, reduced from 0.45)
    - **Base Position:** `(0.0, -0.4, -0.5)` - Negative Z places weapon in front of camera
    - **Files:** `three.js/main.js` (processWeaponMaterial, updateLevel4WeaponAnimation), `three.js/weapon-system.js` (loadWeapon, _applyWeaponTransforms)
    - **Status:** ✅ **PRODUCTION READY** (December 13, 2025)
    - **Scope:** All weapon slots (1-9), all levels (4-9), all FBX weapon models
    - **Documentation:** See `12.0/RULES/17_WEAPON_RENDERING_RULE.md` for complete guide
    - **Technical Docs:** See `12.0/TECHNICAL_DOCUMENTATION/WEAPON_RENDERING_RULES.md` and `WEAPON_RENDERING_SOLUTION_2025-12-13.md`

12. **Grass Blade Length System (December 13, 2025)**
    - **Feature:** Adjustable grass blade length via shader uniform multiplier
    - **Implementation:** Add `bladeLengthMultiplier` uniform to vertex shader (0.5x to 2.0x range)
    - **Real-time Updates:** Blade length adjustable without regenerating geometry
    - **UI Integration:** Slider in God Mode menu Ground Controls section
    - **Per-Level Settings:** Blade length saved/loaded per level (similar to grass quality)
    - **Performance:** No additional geometry or calculations (shader-based scaling)
    - **Files:** `three.js/grass-system.js` (shader, setBladeLength method), `three.js/main.js` (UI controls)
    - **Status:** 📋 **PLANNED** (December 13, 2025)
    - **Reference:** [Making Grass with Triangles in GLSL using Three.js](https://medium.com/antaeus-ar/making-grass-with-triangles-in-glsl-using-three-js-e106771a71ff)
    - **Documentation:** See `12.0/TECHNICAL_DOCUMENTATION/GRASS_BLADE_LENGTH_INTEGRATION_PLAN.md` for complete implementation plan
