# 2025-11-17 — Weapon Gallery Baseline & Weapon Rows

## Highlights
- Captured the three-weapon gallery baseline (W1–W3) with the grey backdrop removed so QA screenshots stay clean.
- Added a 40-slot primary weapon ring around the monster runway using numbered pedestals only (no FBX meshes yet).
- Documented the save point + ring details across ACTIVE_STATUS, QUICK_STATUS, and 3d_riddles/RIDDLE_01_THE_SPAWN_LEVEL_2.md for future reference.

## Technical Notes
1. createLevel2WeaponGallery() now skips the tall shelf mesh; only the existing pedestals + labels render so the Mouse/Animation Library characters have a clear line of sight.
2. New constants:
   - LEVEL2_WEAPON_ROW_CONFIG (lane spacing, forward spacing, label height)
   - LEVEL2_PRIMARY_WEAPON_SLOTS (40-entry mapping of Fire Weapons FBX files)
3. Weapon pedestals now form mirrored rows (two lanes per side, ten slots each) hugging the inner corridor with explicit lane offsets (`[2.6u, 4.75u]`). Each slot spawns its FBX immediately, using the same pipeline as the W1‑W3 gallery weapons.
4. Added an “Accessory Corridor” along the center path (x = ±1.45u, 7 pads per lane) that showcases all 14 attachments from `/Fire Weapons 1/FBX/Accessories`. Uses the same PBR material conversion plus compact transforms per accessory type so nothing clips the walkway.
5. Gallery cleanup: W1‑W3 pedestals now use the slim 0.95u bases + smaller labels, weapons reuse the row transforms, and the lever moved behind the trio so testers walk past the display before unlocking it.
6. Disabled the automatic Level 2 warp/gallery-unlock flags by default; QA can flip `localStorage.debug_force_level2_start` / `.debug_level2_gallery_start` to `true` locally when they need shortcuts, but the standard build now requires standing on the cheese stone for Step 0 → lever press for Step 1.
7. Matched Level 2’s HUD with Level 1 (shared riddle toasts for Steps 0‑2), added the missing Step 2 trait unlock + hint, wired +100 DSPOINC payouts for each step, and replaced the placeholder portal message with a full completion screen offering “Stay” (soft restart), “Back to Level 1”, or a Level 3 teaser.
4. `PRIMARY_RING_CATEGORY_TRANSFORMS` still drive per-weapon scale/offset/rotation for the row layout (defaults ≈ 0.006 scale, 0.6y lift, π/2 rotation; pistols/revolvers flip 180°) so every gun stays readable without overwhelming the pedestals.

## Next Steps
- Map each numbered pedestal to its final weapon animation pose before loading meshes.
- Add VFX/sound cues once the ring becomes interactive (e.g., hover highlight per slot).
- Plan camera rails/screenshots so the circular layout can be showcased in Trailermode.
