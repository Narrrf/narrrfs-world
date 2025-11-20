# LEVEL BACKGROUND MUSIC SYNC — 2025-11-19

## Summary
- Issue: Level soundtrack sometimes remained on `level1.mp3` when spawning directly into Levels 2–4 (debug warp) or when closing the God Mode selector.
- Fix: Added `ensureBackgroundMusicForCurrentLevel(force = false)` helper, tagged each `THREE.Audio` instance with its owning `levelId`, and called the helper from every warp/restart pathway plus the selector close handler.
- Result: Level-specific tracks now start immediately after warps, restarts, and menu closes. Options menu volume/toggle settings persist and apply across all levels without popping errors.

## Technical Details
1. **Helper + Metadata**
   - Created `ensureBackgroundMusicForCurrentLevel()` (main.js ~1427) which compares `currentLevel` against the `levelId` stored on the active audio clip and restarts the correct mp3 when required.
   - `loadBackgroundMusic()` now sets `music.userData.levelId` and `music._levelId` for compatibility with existing logging.
2. **Warp/Restart Integration**
   - Inserted `ensureBackgroundMusicForCurrentLevel(true)` inside `warpToLevel2/3/4`, `restartLevel1/2/3/4`, and the Level 4 debug auto-warp path.
   - Level selector (God Mode L key) now re-checks music whenever the popup closes so choosing a level always swaps to the matching soundtrack.
3. **Safety Guards**
   - Wrapped music helpers in try/catch blocks to avoid `audioListener` ReferenceErrors during bootstrap.
   - `applyLevelEnvironment()` only plays music if the listener exists; otherwise `startGame()` triggers playback once the renderer/camera stack is ready.

## Verification
- Reloaded with `DEBUG_FORCE_LEVEL4_START` → Level 4 track starts instantly (no Level 1 bleed).
- Warped Level 4 → Level 2 via God Mode; correct `level2.mp3` loop begins w/o refresh.
- Restarted Level 1 & Level 3 via portal buttons; tracks restarted in sync every time.
- Toggled music Off/On in options menu; state persists + resumes proper level track.

## Impact / Follow-up
- Documentation updated (`HYTOPIA_THREE_TECH_DOCUMENTATION.md` Section 25, Daily Status, Quick Status).
- Future levels only need to add their mp3 to `BACKGROUND_MUSIC_PATHS` and call `ensureBackgroundMusicForCurrentLevel(true)` inside new warp/restart helpers.
- No further action required; monitor logs for any `THREE.Audio: Audio is already playing` warnings after future level additions.

