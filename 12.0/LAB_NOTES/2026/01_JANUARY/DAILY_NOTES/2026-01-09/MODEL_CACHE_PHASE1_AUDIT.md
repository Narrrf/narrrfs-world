# 📦 Phase 1 Audit — Model Usage & Caching Baseline (Object3D Cache Project)

**Date:** January 10, 2026  
**Phase:** 1 — Foundation & Analysis  
**Goal:** Identify *which* 3D models are reused most (best ROI for processed Object3D caching), and establish baseline metrics to prove speedups.

---

## ✅ Current Reality in Code (Today)

- **Network-level caching**: `THREE.Cache.enabled = true` (raw file data; prevents re-downloading)
- **Object-level caching (partial)**: `modelCache = new Map()` in `public/three.js/main.js`
  - Stores **raw loader outputs** (GLTF object or FBX payload)
  - On every `loadModel()` cache hit, we still do: `cached.scene.clone(true)`
  - This avoids re-downloading + re-parsing, but **does not guarantee “fully processed Object3D” reuse**

**Why Phase 1 matters:** we need a usage map + baseline metrics so Phase 2 can focus on the highest-value assets first (weapons, bosses, player models, chests).

### Phase 1 instrumentation added (now)
- Added debug-toggleable model cache stats in `public/three.js/main.js`:
  - `window.__narrrfsModelCacheStats` (global stats + per-path Map)
  - Tracks: **hits / misses / errors / clones**, plus **loadMsTotal / cloneMsTotal**
  - Toggle log sampling with: `DEBUG_SETTINGS.logModelCacheStats = true`

---

## 📌 Model Asset Frequency Snapshot (from scanning `public/three.js/**/*.js`)

> Notes:
> - This is a **string frequency** scan; it finds repeated `*.glb/*.gltf/*.fbx` references in code.
> - It **does not** yet represent runtime spawn counts (e.g., “how many chests spawn per level”). That’s Phase 1.2.

### Top repeated referenced model paths (filtered to those containing `/`)

```
Count  Path
4      models/phoenix2/dragons1.glb
4      pack/fbx/beartrap_open.fbx
3      pack/fbx/beartrap_closed.fbx
3      models/chest2/chest2.glb
3      models/mouse/glb/glb/character/character.glb
3      models/mouse/glb/glb/animation/idle.glb
3      models/mouse/glb/glb/animation/run.glb
3      models/mouse/glb/glb/animation/jump.glb
3      models/mouse/glb/glb/animation/death.glb
3      models/mouse/glb/glb/animation/climb.glb
3      models/mouse/glb/glb/animation/somersoult.glb
3      1/afc_03/afc_03.fbx
3      pack/guns/fbx/pistol_1.fbx
2      /textures/plants/phormium_fbx/phormium_tenax_1.fbx
2      models/tree-with-arms/tree-with-arms.glb
2      1/fbx/assaultrifle_1.fbx
2      1/fbx/shotgun_1.fbx
2      1/fbx/sniperrifle_1.fbx
```

### What these mean (impact + priority)

- **Boss models (HIGH priority)**  
  - `models/phoenix2/dragons1.glb` (Phoenix Boss)  
  - `1/afc_03/afc_03.fbx` (Alien Spider Boss)
  - These are heavy animation rigs → best ROI for “processed Object3D cache + animation pre-processing”.

- **Weapon models (HIGH priority)**  
  - `pack/guns/fbx/pistol_1.fbx`, `1/fbx/*`  
  - Weapons are loaded/switching frequently in Levels 4–6 and should be *instantly spawnable*.

- **Player character (HIGH priority)**  
  - `models/mouse/glb/glb/character/character.glb` + animation clips  
  - Spawned every session; also commonly reloaded during warps / character selection.

- **Chest models (HIGH priority)**  
  - `models/chest2/chest2.glb`  
  - Many instances per level → processed clone performance matters.

- **Environment props (MED priority)**  
  - `pack/fbx/beartrap_open.fbx`, `pack/fbx/beartrap_closed.fbx`
  - Spawned as gameplay props; can benefit from processed Object3D caching if they’re instanced often.

- **Plants/trees (MED priority)**  
  - `/textures/plants/phormium_fbx/phormium_tenax_1.fbx`, `models/tree-with-arms/tree-with-arms.glb`
  - Many spawns can happen → caching helps; but we should start with bosses/weapons/player/chests first.

---

## 🧩 Glyph3D (Future Models — Not Used Yet, But Ready)

You asked about new GLBs added here:
- `public/glyph/glyph3d/`

### Inventory (current)
- **36 `.glb` files** (A–Z + 0–9), file names like: `A 3d.glb`, `7 3d.glb`, etc.

### Status
- These files are **not referenced by** `public/three.js/main.js` today, so:
  - They **do not appear** in the “usage frequency snapshot” yet
  - They **won’t generate cache stats** until we actually call `loadModel()` for them somewhere

### Phase 2 integration plan (when we start using Glyph3D in the 3D world)
- Add a small “Glyph3D preload list” (A–Z + 0–9) for instant loads
- Store them in the future **processed Object3D cache** so repeated glyph spawns are nearly free
- Because of spaces in filenames (`A 3d.glb`), we should normalize naming (optional) or ensure URLs are always encoded correctly (recommended before production use)

---

## 🧪 Phase 1.2 (Next in Phase 1): Runtime Spawn Frequency

String frequency is helpful, but the real ROI comes from **runtime instance count** and **processing cost**.

Tomorrow we’ll add lightweight runtime counters for:
- **How many times each `loadModel(path)` is called**
- **How many clones are created per path**
- **How many instances get added to the scene per level**

Deliverable: “Top 10 models by runtime clones per session” (this determines what goes into processed cache first).

---

## 📏 Baseline Metrics (Phase 1 Deliverable)

We will track (per model path):
- cache hits vs misses
- clone time (ms)
- load/parse time (ms) for cold loads

This baseline lets Phase 2 prove:
- reduced clone cost (by avoiding repeated post-processing)
- reduced “first spawn” time (by storing a processed base Object3D)

---

## 🎯 Phase 1 Output Decisions (Locked for Phase 2)

### Processed cache keys
- Key format: **`resolvedPath + "::" + variant`**
- Default: `variant = "default"`

### Initial processed cache scope (start small; biggest wins first)
1. Player character models (+ animation clips)
2. Weapon viewmodels
3. Boss models
4. Chest models
5. High-count props (plants / traps) as next wave

---

## 🔜 Next Step (Phase 1 → Phase 2 bridge)

Once metrics are in place and we know the top runtime offenders, we implement:
- `processedModelCache: Map<string, { base: Object3D, animations?: AnimationClip[], meta }>`
- a clone strategy that preserves processed state while avoiding accidental shared-mutable materials where needed.

