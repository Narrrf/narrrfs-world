# LEVEL 5 · STEP 0 PLATE & BULLET FIXES — 2025-11-24

## 🎯 Goal
Fix two critical issues with Level 5 Step 0 implementation:
1. **Plate Height:** Trigger plate was floating too high, not at floor level
2. **Bullet Visibility:** Bullets were not visible when shooting (shooting worked but bullets invisible)

## ✅ Issues Fixed

### 1. Plate Height Fixed ✅
**Problem:** Trigger plate was positioned relative to spawn position with a `-0.4` Y offset, causing it to float above ground level.

**Solution:**
- Calculate and store actual ground level (`groundLevelY`) when map loads via raycast
- Position plate at ground level: `plateCenterY = groundLevel + (plateHeight / 2)`
- Plate now sits perfectly flush with the ground (top of plate = ground level)

**Technical Details:**
- Added `level5State.groundLevelY` to store ground level from raycast
- Modified `getLevel5TriggerPlatePosition()` to use ground level instead of spawn-relative offset
- Ground level calculated from lowest raycast intersection point when map loads
- Plate height = 0.35, so center positioned at `groundLevel + 0.175` (half height)

**Code Changes:**
- `buildLevel5TheWalk()`: Store `groundLevelY` from raycast result
- `getLevel5TriggerPlatePosition()`: Use stored ground level for plate Y position
- `level5State`: Added `groundLevelY` property

### 2. Bullet Visibility Fixed ✅
**Problem:** `updateLevel4Bullets()` function had hardcoded check that cleaned up all bullets when `currentLevel !== LEVEL_IDS.LEVEL4`, preventing bullets from updating/rendering in Level 5.

**Solution:**
- Updated `updateLevel4Bullets()` to allow Level 5 bullets when weapons are enabled
- Bullets now update and render correctly in Level 5

**Technical Details:**
- Modified check: `shouldUpdateBullets = currentLevel === LEVEL_IDS.LEVEL4 || (currentLevel === LEVEL_IDS.LEVEL5 && level5RiddleState.weaponsEnabled)`
- Bullets now use same visual system as Level 4:
  - Slot 1: Yellow cheese bullets (`createLevel4CheeseBullet()`)
  - Slot 2: Purple SF13 bullets (`createLevel4SF13Bullet()`)
- Bullet update loop already called in `updateLevel5()` when weapons enabled

**Code Changes:**
- `updateLevel4Bullets()`: Updated condition to include Level 5 with weapons enabled
- Bullets already created correctly via `fireLevel4SingleShot()` in Level 5
- No changes needed to bullet creation - just update logic

## 📊 Verification

### Plate Position
- ✅ Plate spawns at ground level (flush with map surface)
- ✅ Plate positioned near spawn point (4 units X offset, -4 units Z offset)
- ✅ Plate visible and properly textured (cheese-stone.png)
- ✅ Plate animates down correctly when player stands on it

### Bullet System
- ✅ Bullets visible when shooting in Level 5
- ✅ Yellow bullets for slot 1 (cheese texture)
- ✅ Purple bullets for slot 2 (SF13 glow)
- ✅ Bullets move correctly toward crosshair target
- ✅ Bullets disappear after lifetime expires

## 📂 Files Modified
- `three.js/main.js`
  - `updateLevel4Bullets()`: Added Level 5 bullet support
  - `getLevel5TriggerPlatePosition()`: Use ground level for plate Y position
  - `buildLevel5TheWalk()`: Calculate and store ground level from raycast
  - `level5State`: Added `groundLevelY` property

## 🔜 Next Steps
- Continue with Level 5 riddle design (Step 1+)
- Test plate positioning with different spawn locations
- Verify bullet collision when targets are added to Level 5

## 📝 Notes
- Plate uses ground level from initial raycast (same method as spawn positioning)
- Bullet system fully shared between Level 4 and Level 5 for consistency
- Both fixes maintain compatibility with existing Level 4 functionality

