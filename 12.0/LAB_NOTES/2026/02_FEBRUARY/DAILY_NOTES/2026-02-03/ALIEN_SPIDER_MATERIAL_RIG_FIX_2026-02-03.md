# 🕷️ Alien Spider Material & Rig Fix – Path Resolution

**Date:** February 3, 2026  
**Status:** ✅ **FIXED** – Material and animation paths corrected  
**Purpose:** Fix Alien Spider boss missing materials and non-moving rig in Level 6

---

## 🚨 **ISSUE REPORTED**

> "The spider is missing the material and also does not have a rig that moves – only the ground spider in black is visible and moves over Level 6 but without mesh or rig."

---

## 🔍 **ROOT CAUSE**

1. **Texture/Animation paths:** `alien-spider.js` used hardcoded `/textures/3d models/...`, which does **not** match the game’s resolved asset root.
2. **Asset location:** Alien Spider 1 assets lived only in `three.js/public/textures/3d models/` and were **not** present under `public/three.js/public/textures/` (where the game loads from).
3. **Result:** Model position updates (behavior code) worked, but:
   - TGA textures failed to load → black mesh
   - Animation FBX files failed to load → rig not animated

---

## ✅ **FIXES APPLIED**

### 1. **Dynamic base path in `alien-spider.js`**

- Derive `assetBasePath` from the model path passed by `main.js` (which uses `resolveAssetPath`).
- Use `assetBasePath` for:
  - `loader.setResourcePath()`
  - TGA texture loads
  - Animation FBX paths

**Change:**
```javascript
// Before: Hardcoded
const basePath = "/textures/3d models/Alien Spider 1/AFC_03/";

// After: Derived from model path
this.assetBasePath = modelPath.replace(/\/[^/]+$/, '/');
// e.g. /public/three.js/public/textures/3d models/Alien Spider 1/AFC_03/
```

### 2. **Animation paths**

- Replaced `animationPaths` (full URLs) with `animationFileNames` (filenames only).
- Build full paths as `this.assetBasePath + fileName` when loading animations.

### 3. **Asset copy**

- Copied `Alien Spider 1` into:
  - `public/textures/3d models/Alien Spider 1/` (for symlink use)
  - `public/three.js/public/textures/3d models/Alien Spider 1/` (direct game path)

---

## 📁 **FILES MODIFIED**

| File | Changes |
|------|---------|
| `public/three.js/alien-spider.js` | Dynamic base path, `animationFileNames`, `assetBasePath` usage |
| `three.js/alien-spider.js` | Same updates (source sync) |

---

## 🧪 **VERIFICATION**

1. Load Level 6.
2. Confirm Alien Spider has correct materials (no black mesh).
3. Confirm legs/body animate (Walk, Idle, Attack, etc.).
4. Open DevTools → Network and check for 200 responses for:
   - `AFC_03.fbx`
   - `AFC_03_color.tga` (or Fur_1/Fur_2)
   - `AFC_03@Idle_1.fbx`, `AFC_03@Walk.fbx`, etc.

---

## 📋 **CACHE BUSTING**

If the fix is not visible, hard-refresh: **Ctrl+Shift+R** (or Cmd+Shift+R on Mac).

---

## ✅ **RIG FIX (Feb 3, 2026 – Follow-up)**

**Issue:** Material fixed, but rig still did not animate (legs/body frozen).

**Root cause:** Base model `AFC_03.fbx` and animation FBX files (`AFC_03@*.fbx`) had bone name/hierarchy mismatch. `clipAction(animation)` binds by name; mismatches → no movement.

**Fix:** Use animation FBX as base model instead of static base:
- Load `AFC_03@Idle_1.fbx` as base (has rig that matches all 7 animations)
- Mixer root = that model → all animation clips bind correctly
- **Result:** ✅ Alien Spider moving with rig and textures

**Code:** `alien-spider.js` `loadModel()` – `animBasePath = assetBasePath + "AFC_03@Idle_1.fbx"`

---

**Lab note created:** February 3, 2026  
**Rig fix added:** February 3, 2026
