# 🎁 Level 5 – 50 Chests Integration Plan

**Date:** February 5, 2026  
**Status:** 📋 **PLAN – NOT YET IMPLEMENTED**  
**Purpose:** Plan for adding 50 chests (50 DSPOINC each) spread across the entire Level 5 Klagenfurt map  

---

## 📋 **REQUIREMENTS SUMMARY**

| Item | Value |
|------|--------|
| **Total chests** | 50 |
| **Reward per chest** | 50 DSPOINC (base, before role multipliers) |
| **Spread** | Across entire Level 5 (not clustered near spawn) |
| **Common flags** | `type: 'chest2'`, `levelId: 'CHEESE_TEMPLE_LEVEL5'` |
| **Chest IDs** | `chest_010` through `chest_059` |

---

## 🗺️ **LEVEL 5 MAP CONTEXT**

- **Map:** `klagenfurt.gltf` (city map with uneven terrain)
- **Ground detection:** Raycast at each chest (X, Z) onto `level5State.mapMesh` to get Y
- **Current single chest:** Position (33, raycastY, -41)
- **Spawn:** Approx. (0, 0) area (to be verified in `buildLevel5TheWalk`)
- **Map extent:** Large city map – assumed X/Z range roughly **-100 to +100** (verify in-game or via map bounding box)

---

## 🎯 **POSITIONING STRATEGY**

### Option A: Grid-based (recommended for even spread)

Define a 2D grid covering the level and place one chest per cell:

- **Grid:** 7×7 or 8×6 cells (42–48 chests) or 7×8 (56) → pick 50 cells
- **X range:** -90 to +90 (step ~25–30)
- **Z range:** -90 to +90 (step ~25–30)
- **Exclude:** Spawn area (e.g. radius 15 around 0,0) to avoid clustering
- **Result:** ~50 positions spread across corners, edges, and center

### Option B: Predefined waypoints

Manually choose 50 positions based on landmarks (squares, alleys, corners). Better for design, but requires knowledge of Klagenfurt layout.

### Option C: Procedural scatter with spacing

- Generate random (X, Z) in range
- Enforce minimum distance between chests (e.g. 15–20 units)
- Reject positions inside walls (optional: raycast validation)

**Recommendation:** Start with **Option A (grid)** for predictability; adjust positions if some fall in walls or inaccessible areas.

---

## 📐 **PROPOSED COORDINATE RANGES**

| Axis | Min | Max | Step (grid) | Chests per axis |
|------|-----|-----|-------------|-----------------|
| X | -90 | +90 | 25 | 8 positions |
| Z | -90 | +90 | 25 | 8 positions |

**Grid:** 8×8 = 64 cells. Select 50 by:

- Skipping 14 cells (e.g. center spawn area, or every 5th cell)
- Or using a 7×7 grid + 1 extra = 50
- Or 5×10 = 50 (5 X positions × 10 Z positions)

**Simple 50-cell grid example:**

```
X positions: [-85, -60, -35, -10, 15, 40, 65, 90]  (8 values)
Z positions: [-85, -60, -35, -10, 15, 40, 65, 90]  (8 values)
→ 64 total; exclude 14 (e.g. center 4×4 = 16 cells near spawn) → 48 chests
→ Or use 5×10 grid for exactly 50
```

**Refined 50-position grid:**

- X: [-90, -70, -50, -30, -10, 10, 30, 50, 70, 90] (10 values)
- Z: [-90, -70, -50, -30, -10, 10, 30, 50, 70, 90] (10 values)
- Use 5×10 = 50 cells (e.g. 5 X × 10 Z or 10 X × 5 Z)
- Skip (0,0) area: e.g. exclude |X|<20 and |Z|<20

---

## 🔧 **COMMON CHEST CONFIG (FLAGS)**

Every Level 5 chest uses:

```javascript
{
  id: 'chest_XXX',           // chest_010 … chest_059
  type: 'chest2',            // Standardized
  position: new THREE.Vector3(x, detectedY, z),  // Y from raycast
  dspoincAmount: 50,         // 50 DSPOINC each
  levelId: 'CHEESE_TEMPLE_LEVEL5'
}
```

**Rules alignment:**

- `19_CHEST_SYSTEM_RULE.md`: `type: 'chest2'`, `levelId: 'CHEESE_TEMPLE_LEVELX'`
- Y from raycast at (x, z) onto `level5State.mapMesh` (existing pattern)
- Role multipliers applied by API

---

## 📁 **IMPLEMENTATION TASKS**

### 1. Define `LEVEL5_CHEST_POSITIONS`

**Location:** `main.js` (near `createLevel5Chests` or in constants section)

```javascript
// Level 5: 50 chests, 50 DSPOINC each, spread across Klagenfurt map
const LEVEL5_CHEST_COUNT = 50;
const LEVEL5_CHEST_DSPOINC = 50;
const LEVEL5_CHEST_POSITIONS = [
  // Array of { x, z } - Y will be raycast per position
  { x: -90, z: -90 }, { x: -90, z: -60 }, ...  // 50 entries total
];
```

Options:

- **A:** Hardcode 50 `{ x, z }` in `LEVEL5_CHEST_POSITIONS`
- **B:** Generate grid in code with configurable min, max, step, and exclusion zone

### 2. Add `raycastLevel5GroundYAt(x, z)` helper

**Location:** Inside `createLevel5Chests()` or as shared helper

- Reuse current raycast logic from the single chest (lines ~42206–42251)
- Input: `x`, `z`
- Output: `y` (ground level at that position)
- Fallback: `spawnY` if raycast fails
- Use `level5State.mapMesh` as raycast target

### 3. Refactor `createLevel5Chests()`

**Current:** Creates one chest (`chest_010`).

**New flow:**

1. `chestSystem.clearLevel(LEVEL_IDS.LEVEL5)`
2. Loop over `LEVEL5_CHEST_POSITIONS` (or generated grid)
3. For each position:
   - `y = raycastLevel5GroundYAt(x, z)`
   - `chestSystem.addChest(LEVEL_IDS.LEVEL5, { id: 'chest_0' + (10 + i), ... })`
4. IDs: `chest_010` … `chest_059` (50 chests)

### 4. Verify map bounds

Before finalizing positions:

- In-game: Fly around Level 5 and note X/Z extent
- Or: Log `level5State.mapMesh` bounding box after load
- Adjust `LEVEL5_CHEST_POSITIONS` if map is smaller/larger than -100..+100

---

## ⚠️ **RISKS & MITIGATIONS**

| Risk | Mitigation |
|------|------------|
| Chests inside walls/buildings | Use grid; iterate and skip positions that raycast to non-walkable surfaces (if detectable) |
| Map smaller than -90..+90 | Shrink grid range after verifying bounds |
| Performance (50 raycasts at once) | Raycasts are one-time at creation; acceptable |
| Duplicate/overlap with other level chests | IDs `chest_010`–`chest_059` are Level 5–only; no clash with chest_001–009 (L1–4) or chest_011 (L6) |

---

## 📋 **CHECKLIST BEFORE IMPLEMENTATION**

- [ ] Confirm Level 5 map X/Z bounds (in-game or via bounding box)
- [ ] Choose grid strategy (Option A recommended)
- [ ] Define exact 50 (x, z) positions
- [ ] Add `LEVEL5_CHEST_POSITIONS` constant
- [ ] Extract raycast logic into `raycastLevel5GroundYAt(x, z)`
- [ ] Refactor `createLevel5Chests()` to loop and add 50 chests
- [ ] Test: all 50 chests visible and on ground
- [ ] Test: open chest → 50 DSPOINC + role multiplier
- [ ] Update `19_CHEST_SYSTEM_RULE.md` / technical docs with Level 5 chest count

---

## 📝 **FILES TO MODIFY**

| File | Changes |
|------|---------|
| `public/three.js/main.js` | Add `LEVEL5_CHEST_POSITIONS`, refactor `createLevel5Chests()` |
| `12.0/RULES/19_CHEST_SYSTEM_RULE.md` | Note Level 5: 50 chests, 50 DSPOINC each (optional) |

---

## 🎯 **ESTIMATED EFFORT**

- Define positions: ~30 min
- Code changes: ~1–2 h
- Testing & tuning: ~1 h  

**Total:** ~2–3 h

---

**Plan Created:** February 5, 2026  
**Next Step:** Verify map bounds, then implement per this plan
