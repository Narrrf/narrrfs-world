# 🔧 Level 5 Green Walls Collision – Handover Note

**Date:** February 2, 2026  
**Status:** ⏳ **INVESTIGATION – Collision still not working after fix**  
**Purpose:** Handover for next agent/session to continue Level 5 collision debugging  

---

## 📋 **WHAT WAS DONE**

### Fix applied (previous session)

A fix was added to `main.js` `buildLevel()` (lines ~18237–18305) to prevent overwriting Level 5’s collision mesh:

1. **Preserve Level 5 mesh:** If `currentLevel === LEVEL5` and `collisionMesh.name === 'Level5_CollisionMesh'`, preserve it instead of rebuilding.
2. **Skip procedural mesh for Level 5:** If in Level 5, do not create the procedural block-based collision mesh (Level 5 uses GLTF-based collision from `buildLevel5TheWalk`).

### Bug addressed

- **Problem:** Players could walk through green labyrinth walls in Level 5.
- **Cause:** `buildLevel()` was overwriting the Level 5 GLTF-based collision mesh with a procedural block mesh that did not include the green walls.
- **Fix:** In `buildLevel()`, detect Level 5 and preserve its collision mesh instead of replacing it.

---

## ⚠️ **CURRENT ISSUE**

After the fix:

- The other agent/session appears to have stopped making progress.
- Collision in Level 5 still does not work; players can walk through walls.

---

## 🔍 **HYPOTHESIS – WHY FIX MAY NOT BE ACTIVE**

### 1. `buildLevel()` might not run for Level 5

- `buildLevel()` uses procedural blocks (`mapData.blocks`) to build `collisionPositions`.
- Level 5 is built from `klagenfurt.gltf` in `buildLevel5TheWalk`, not from procedural blocks.
- If Level 5’s load path never calls `buildLevel()`, the preservation logic never runs.
- If Level 5 has no blocks, `collisionPositions` is null and the whole `if (collisionPositions)` block is skipped (around lines 18237–18308).

**Action:** Search for when `buildLevel` is called and whether it ever runs while `currentLevel === LEVEL5`.

### 2. Call order / timing

- If `buildLevel` runs *before* `buildLevel5TheWalk` (e.g. during level warp), `collisionMesh` might not yet be `Level5_CollisionMesh`.
- If `buildLevel` runs *after* `buildLevel5TheWalk` but with another level’s mapData, the wrong data might be used.
- Possible race: Level 5 collision mesh created, then something else rebuilds collision from different mapData.

**Action:** Trace warp/load order for Level 5 and confirm when `buildLevel` vs `buildLevel5TheWalk` run.

### 3. Other places overwriting `collisionMesh`

- There may be other assignments to `collisionMesh` (e.g. cleanup, level switch, fallback logic).
- File references: lines ~22688, 23065, 24656, 27869, 28185.

**Action:** Search `collisionMesh =` across `main.js` and ensure Level 5’s mesh is never overwritten inappropriately.

---

## 📁 **RELEVANT CODE LOCATIONS**

| Location | Purpose |
|----------|---------|
| **main.js ~18237–18308** | Level 5 preservation logic in `buildLevel()` |
| **main.js ~22656–23120** | Level 5 collision mesh creation in `buildLevel5TheWalk()` |
| **main.js ~35540–35769** | Horizontal collision checks in animate loop |
| **main.js ~18684** | `playerCollisions()` function |

---

## 🧪 **DEBUGGING STEPS**

### 1. Console logs in Level 5

When entering Level 5, check for:

- `🔒 [LEVEL 5] Preserving Level 5 collision mesh (includes green walls from klagenfurt.gltf)`
- `⏭️ [LEVEL 5] Skipping new collision mesh creation (using GLTF-based collision from buildLevel5TheWalk)`

If these never appear, the preservation path is not being hit.

### 2. Verify collision mesh in Level 5

```javascript
// In browser console while in Level 5:
console.log('collisionMesh:', collisionMesh);
console.log('collisionMesh.name:', collisionMesh?.name);
console.log('collisionMesh.geometry.boundsTree:', collisionMesh?.geometry?.boundsTree);
```

### 3. Search call sites

```powershell
# PowerShell – find buildLevel calls:
Select-String -Path "c:\xampp-server\htdocs\narrrfs-world\public\three.js\main.js" -Pattern "buildLevel\(" | Select-Object LineNumber, Line

# Find collisionMesh assignments:
Select-String -Path "c:\xampp-server\htdocs\narrrfs-world\public\three.js\main.js" -Pattern "collisionMesh\s*=" | Select-Object LineNumber, Line
```

### 4. Trace Level 5 load path

Find where Level 5 warp/load triggers:

- `buildLevel5TheWalk`
- `buildLevel`
- Any level-switch or load functions that might rebuild collision.

---

## 📚 **REFERENCE**

- **Level 5 map:** `klagenfurt.gltf`
- **Collision mesh name:** `Level5_CollisionMesh`
- **buildLevel5TheWalk:** Merges GLTF meshes, border walls, glyph proxies into one BVH collision mesh

---

## 🚀 **NEXT STEPS FOR NEXT AGENT**

1. Search for all `buildLevel(` and `collisionMesh =` occurrences.
2. Confirm whether `buildLevel` runs for Level 5 and under what conditions.
3. If `buildLevel` does not run for Level 5, look for other code that could overwrite the Level 5 collision mesh.
4. Add temporary console logs around Level 5 load and collision rebuilds.
5. Test Level 5 and check the new logs to confirm collision mesh creation and preservation.

---

**Last Updated:** February 2, 2026  
**File:** `12.0/LAB_NOTES/2026/02_FEBRUARY/DAILY_NOTES/2026-02-02/LEVEL_5_COLLISION_HANDOVER_2026-02-02.md`
