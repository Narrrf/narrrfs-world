# 🕷️ Alien Spider Minion – Rendering & Shooting Issues

**Date:** February 6, 2026  
**Status:** ✅ **FIXES APPLIED (Session 2)**  
**Scope:** Level 6 – Minion visibility (only feet visible) + shooting/explosion

---

## 📋 **ISSUES OBSERVED**

### **Issue 1: Only feet visible – no body/head**
- **Symptom:** 4 minion spiders spawn, but only their feet/lower legs render. Body and head are absent.
- **Possible causes:**
  - **SkeletonUtils.clone for FBX:** Rule 14 targets GLTF; FBX skinned meshes may clone differently
  - **Material/visibility:** Some mesh groups (body, head) may have `visible=false`, wrong materials, or culling
  - **Hierarchy/pivot:** Body/head meshes might be positioned far from feet (wrong pivot or bone offset)
  - **Mesh groups:** FBX may have separate mesh groups; only "feet" group rendering correctly

### **Issue 2: Shots don't harm minions**
- **Symptom:** Player shoots at minions; no damage, no explosion.
- **Expected:** Minions should explode like Level 5 monsters (colored cubes).
- **Possible causes:**
  - **Raycast target:** Weapon raycast may not be hitting minion hitbox
  - **Hitbox alignment:** Minion hitbox may be misaligned (only feet visible → hitbox at feet?)
  - **Level 6 weapon config:** Raycast may not include spider minions in Level 6
  - **Damage handler:** No `onMinionHit` or health reduction when shot

### **Issue 3: Explosion effect**
- **Expected:** Minions should explode like Level 5 monsters (colored cubes).
- **Reference:** Level 5 monster death → explosion particles (colored cubes).

---

## 🔍 **INVESTIGATION PLAN**

1. **Rendering:** Compare boss vs minion model usage – boss renders fully; minion uses SkeletonUtils.clone. Check FBX structure, mesh visibility, materials.
2. **Weapon/raycast:** Find Level 5 monster hit detection and Level 6 weapon config. Ensure spider minions are in raycast target list.
3. **Explosion:** Find Level 5 monster death/explosion logic; replicate for spider minions.

---

## 📁 **RELEVANT FILES**

- `alien-spider.js` – AlienSpiderMinion.createFromCache(), hitbox, model
- `weapon-system.js` – Raycast, hit detection, Level 6 config
- `main.js` – Level 5 monster explosion, spider minion spawn, onMinionDied

---

## 🔧 **ROOT CAUSE ANALYSIS (Feb 6, 2026 – Session 2)**

### **Issue 1: Only feet visible**
- **Hypothesis:** SkeletonUtils.clone() may behave differently for FBX vs GLTF (Rule 14 targets GLTF)
- **Action:** Try standard `model.clone(true)` for FBX – Rule 18 says FBX can use clone for multiple instances
- **Alternative:** Check if FBX mesh hierarchy has body/head in different coordinate space; ensure all meshes get materials

### **Issue 2: Shots don't harm minions – ROOT CAUSE FOUND**
- **Hitbox scaling:** Hitbox is a child of clone, so it inherits clone's scale (~0.05). With hitboxRadius=0.8, world size = 0.8×0.05 = **0.04 units** – too small to hit!
- **Fix:** Use `hitboxRadius = 1.0 / Math.max(0.01, scale)` so world size ≈ 1.0 unit (shootable)
- **Raycast:** Weapon system already checks spider minions (weapon-system.js 1341–1368); hitbox just too small

### **Issue 3: Explosion**
- **Reference:** `createMonsterExplosionEffect(position, sizeMultiplier)` in main.js
- **Level 6 support:** Function only supports Level 4/5; need to add Level 6 (level6State.explosionParticles, level6State.group)
- **Integration:** Call in onMinionDied before dispose(); add particle update loop in updateLevel6()

---

## ✅ **FIXES APPLIED (Feb 6, 2026 – Session 3)**

**Reverted standard clone** – It caused one giant non-moving model. Back to SkeletonUtils.clone for correct rig + movement.

1. **Rendering:** Kept `SkeletonUtils.clone(model)`; added `_forceMinionVisible(clone)` – mirrors Level 5’s forceLevel5MonsterVisible: visible=true, frustumCulled=false, mat.skinning=true for SkinnedMesh, brighten dark materials, DoubleSide
2. **Materials:** `_applyMinionMaterials` now handles MeshPhongMaterial as well (FBX often uses Phong)
3. **Update loop:** Periodic `_forceMinionVisible(this.model)` (~2% per frame) to keep body/head visible
4. **Hitbox:** Still using world-size fix (1/scale) and transparent material
5. **Explosion:** Level 6 support unchanged

## ✅ **PROGRESS**

- [x] Size fix (minions no longer fill screen)
- [x] Materials applied (basePath, tgaLoader)
- [x] Full body/head visibility (standard clone for FBX – test)
- [x] Shooting damage (hitbox world-size fix)
- [x] Explosion effect (Level 6 support)

## ✅ **FIX: Minion Materials (White/Untextured → Proper Fur/Color) – Feb 6, 2026**

### **Issue**
Minions appeared white/untextured (“not meshed”) – movement, shooting, explosion all working, but materials missing.

### **Root Cause**
1. **Material type filter too restrictive** – `_applyMinionMaterials` only processed `MeshStandardMaterial` and `MeshPhongMaterial`, skipping FBX materials like `MeshLambertMaterial`.
2. **Incomplete texture set** – Minions only loaded color texture; boss loads color, normal, ao, metalness, roughness.
3. **No visible fallback** – If texture load failed or was slow, minions stayed white (default untextured material).

### **Fix Applied**
1. **Process ALL material types** – Removed type filter; now processes any material that supports map (Lambert, Phong, Standard, Basic).
2. **Full texture set like boss** – Added `AFC_03_normal.tga`, `AFC_03_ao.tga` (and `Eye_normal.tga` for eyes) alongside `AFC_03_color.tga` / `Eye_color.tga`.
3. **Synchronous fallback color** – Set `mat.color` to visible brown/gray (0x664422) immediately before texture load, so minions never appear white.
4. **Path handling** – Ensured `pathBase` has trailing slash for correct texture path construction.

### **Files Changed**
- `public/three.js/alien-spider.js` – `_applyMinionMaterials()` expanded (lines ~1628–1696)

---

## ✅ **FIX: Minion Polish – White Bodies, Size, Speed (Feb 6, 2026)**

### **Issue**
- Minions still appeared white (“not meshed”) – silhouette correct, materials wrong.
- Minions felt too small and too fast.

### **Fixes Applied**
1. **White materials → brown tint:** For body meshes with nearly white materials (brightness > 0.9), force `mat.color.setHex(0x664422)` so they never appear as white placeholders.
2. **Bigger minions:** `maxMinionSize` 1.0→1.3, `targetSize` 1.0→1.2, `minionTargetSize` bossSize×0.25→0.3, bossScale passed as `bossScale * (minionTargetSize / bossSize)`.
3. **Slower minions:** `followSpeed` 3.0→2.0 in config and default.

### **Files Changed**
- `public/three.js/alien-spider.js` – `_applyMinionMaterials()` white→brown branch, maxMinionSize, followSpeed default
- `public/three.js/main.js` – LEVEL6_SPIDER_WAVE_CONFIG (targetSize, followSpeed), bossScale formula

---

## ✅ **FIX: White Sphere Around Minions (Feb 6, 2026)**

### **Issue**
A white sphere appeared around minion spiders, encapsulating their lower body/legs. Texture, rig, speed, and size were correct.

### **Root Cause**
1. The raycast hitbox is a `SphereGeometry` with `MeshBasicMaterial` – needs to stay invisible.
2. **`_forceMinionVisible`** was overwriting the hitbox: it sets `colorWrite = true` and `opacity = 1.0` on ALL meshes (to fix invisible body/head). That made the hitbox render as a solid white sphere.

### **Fix Applied**
1. Add `colorWrite: false` to the hitbox material (prevents rendering when not overwritten).
2. **Exclude hitbox from `_forceMinionVisible`** – skip children with `name === 'spider_minion_hitbox'` or `userData.isSpiderMinionHitbox`. The hitbox stays invisible; body/head meshes still get the visibility fix.

### **Files Changed**
- `public/three.js/alien-spider.js` – hitbox material (line ~1567), _forceMinionVisible skip (line ~1608)

---

## ✅ **FINAL STATUS (Feb 6, 2026) – ALL ISSUES RESOLVED**

**Minion Wave System – Production Ready**

| Issue | Status | Fix |
|-------|--------|-----|
| Only feet visible | ✅ Fixed | SkeletonUtils.clone + _forceMinionVisible |
| White/untextured bodies | ✅ Fixed | White→brown (b>0.9), full texture set |
| Shots don't harm | ✅ Fixed | Hitbox world-size (1/scale) |
| No explosion | ✅ Fixed | createMonsterExplosionEffect Level 6 |
| Too small | ✅ Fixed | targetSize 1.2, maxMinionSize 1.3 |
| Too fast | ✅ Fixed | followSpeed 2.0 |
| White sphere around minions | ✅ Fixed | colorWrite:false + exclude hitbox from _forceMinionVisible |

**Files:** `alien-spider.js`, `main.js`, `weapon-system.js`  
**Lab note:** `ALIEN_SPIDER_WAVE_MINIONS_IMPLEMENTATION_PLAN_2026-02-06.md`
