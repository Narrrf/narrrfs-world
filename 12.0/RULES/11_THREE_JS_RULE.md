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
