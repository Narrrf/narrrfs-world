# 🕷️ Alien Spider – Rig/Animation Status

**Date:** February 3, 2026  
**Status:** ✅ **FULLY WORKING** – Material, rig, and textures all operational  
**Purpose:** Document fix and current state

---

## ✅ **RESOLVED – ALL WORKING**

- **Material/textures:** Spider has visible materials (TGA textures, brightness control)
- **Rig movement:** Legs, body animate correctly – all 7 animations play
- **Animation playback:** `mixer.update(delta)` drives skeleton bones
- **Position/movement:** Spider moves around Level 6 with behavior patterns
- **Path resolution:** Dynamic `assetBasePath` works for textures and animations

---

## 🔧 **FIX APPLIED (Feb 2026)**

### Root cause

Base model (`AFC_03.fbx`) and separate animation FBX files (`AFC_03@Idle_1.fbx`, etc.) had **bone name/hierarchy mismatch**. `mixer.clipAction(animation)` binds by name; mismatches → no movement.

### Solution

**Use the animation FBX as the base model** instead of the static base FBX:

- **Before:** Load `AFC_03.fbx` → mixer created with that root → load 7 animation files → track names didn't match base model hierarchy → no motion
- **After:** Load `AFC_03@Idle_1.fbx` as base → mixer created with that root (has correct rig) → load other 6 animations → all track names match → rig animates

### Code change

**File:** `public/three.js/alien-spider.js` – `loadModel()`

```javascript
// CRITICAL: Use animation FBX as base model - it has the rig that matches all 7 animations.
const animBasePath = this.assetBasePath + "AFC_03@Idle_1.fbx";
loader.load(animBasePath, ...);  // Was: modelPath (AFC_03.fbx)
```

---

## 📋 **RELEVANT CODE LOCATIONS**

- **`public/three.js/alien-spider.js`**
  - `loadModel()` – loads `AFC_03@Idle_1.fbx` as base (rig-compatible)
  - `loadAllAnimations()` – loads remaining 6 animation FBX files
  - `playAnimation()` – fades in/out, plays action
  - `update()` – calls `mixer.update(delta)`

### Animation files (7 total)

- `AFC_03@Idle_1.fbx` (base model + Idle_1)
- `AFC_03@Idle_2.fbx`, `AFC_03@Walk.fbx`, `AFC_03@Run.fbx`
- `AFC_03@Attack_1.fbx`, `AFC_03@Attack_2.fbx`, `AFC_03@Damage_taken.fbx`

---

## 📚 **REFERENCE**

- **14_GLTF_SKELETON_CLONING_RULE.md** – SkeletonUtils for GLTF; Alien Spider uses FBX (different pipeline)
- **Phoenix boss** – Uses GLB with embedded animations (single file)
- **Three.js issue #14903** – FBXLoader bone/hierarchy mismatches with separate animation files

---

**Lab note created:** February 3, 2026  
**Fix applied:** February 3, 2026  
**Status:** ✅ Production ready
