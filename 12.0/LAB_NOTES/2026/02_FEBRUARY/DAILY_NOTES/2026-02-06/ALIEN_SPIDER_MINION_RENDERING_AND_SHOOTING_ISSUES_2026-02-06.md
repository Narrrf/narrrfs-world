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

## ✅ **FIXES APPLIED (Feb 6, 2026 – Session 2)**

1. **Rendering:** Switched from `SkeletonUtils.clone(model)` to `model.clone(true)` – FBX may render full body with standard clone
2. **Hitbox:** `hitboxRadius = 1.0 / Math.max(0.01, scale)` – world size ~1.0 unit (was 0.04)
3. **Hitbox material:** `transparent: true, opacity: 0` – raycastable (visible:false skips raycast)
4. **Explosion:** Extended `createMonsterExplosionEffect` for Level 6; added `level6State.explosionParticles`; call in `onMinionDied`; particle update in `updateLevel6`

## ✅ **PROGRESS**

- [x] Size fix (minions no longer fill screen)
- [x] Materials applied (basePath, tgaLoader)
- [x] Full body/head visibility (standard clone for FBX – test)
- [x] Shooting damage (hitbox world-size fix)
- [x] Explosion effect (Level 6 support)
