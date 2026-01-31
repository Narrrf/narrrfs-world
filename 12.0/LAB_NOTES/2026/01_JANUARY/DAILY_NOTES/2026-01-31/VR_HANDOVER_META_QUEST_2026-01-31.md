# 🧠 HANDOVER – Narrrfs World VR (Meta Quest)

**Date Received:** January 31, 2026  
**Project:** Narrrfs World – 3D Riddle Game  
**Stack:** Three.js + WebXR (Oculus Browser)  
**Target:** Meta Quest 2 / Quest 3  
**Status:** Desktop stable, VR near-final but needs last fixes

**Main Files:**
- `public/three.js/main.js`
- `public/three.js/gui-system.js`
- `public/three.js/vr-input-provider.js`

---

## 1. PROJECT STATE (HIGH LEVEL)

### Desktop (Non-VR) ✅ Fully stable – DO NOT CHANGE
- Levels 1–6 load correctly
- Player movement works
- Pointer lock works
- Weapons work (Levels 4–6)
- Physics & collider stable
- FPS capped ~60
- No runtime errors

### VR (Meta Quest) 🚧 In final stabilization phase
- Rendering loop issues fixed
- Camera architecture finalized
- VR spawn system partially implemented
- Controller input pipeline exists but not fully verified
- **Remaining issues:** spawn correctness, input confirmation, UI flow in VR

---

## 2. RENDER LOOP (CRITICAL – DO NOT BREAK)

### ✅ Correct & Final Setup
```javascript
renderer.setAnimationLoop(animate);
```

**Inside animate():**
- ❌ **NO** `requestAnimationFrame(animate)`
- ❌ **NO** manual recursion
- XR frames are driven exclusively by `setAnimationLoop`

### Allowed
- Small RAF loops for non-render tasks (e.g. rotating a loading mesh)
- `requestAnimationFrame(animateLoader);` // OK

### ❌ Forbidden
- `requestAnimationFrame(animate);` // breaks VR (double render loop)

**This bug caused:** "Cinema wall" flickering, corrupted VR scenes, controllers appearing dead, levels seeming to load twice. **Now fixed.**

---

## 3. VR CAMERA ARCHITECTURE (FINAL – DO NOT REWRITE)

### Rules
- WebXR controls rotation
- Physics collider controls position
- Camera position is **NOT** set inside `animate()`

### Implementation
```javascript
originalXRUpdateCamera = renderer.xr.updateCamera;
renderer.xr.updateCamera = function (camera, ...args) {
  originalXRUpdateCamera(camera, ...args); // XR pose
  if (playerCollider?.start && playerCollider?.end) {
    const center = new THREE.Vector3().lerpVectors(
      playerCollider.start,
      playerCollider.end,
      0.5
    );
    const eyeHeight = 1.6;
    camera.position.set(
      center.x,
      center.y + eyeHeight,
      center.z
    );
    camera.updateMatrixWorld(true);
  }
};
```

**Why this matters:** Prevents XR fighting the game loop, no head drift, no snapping/teleporting, correct VR locomotion model.

---

## 4. PLAYER COLLIDER DEBUG (KEY TOOL)

- Magenta wireframe sphere renders at: `lerp(playerCollider.start, playerCollider.end, 0.5)`
- Visible in desktop and VR
- Shows where physics thinks the player is
- **If this does NOT move → input is not reaching physics**
- Use this to debug everything

---

## 5. VR INPUT PIPELINE (DO NOT REWRITE)

### Architecture
```
VRInputProvider
   ↓
vrMovementFlags
   ↓
updateAggregatedMovement()
   ↓
movement object
   ↓
getCurrentMovementState()
   ↓
physics → playerCollider
```

### Registration flow
```javascript
vrInputProvider = new VRInputProvider(session);
vrInputProvider.enable();
playerControls.registerInputProvider(vrInputProvider);
playerControls.enableVR(session);
```

### Aggregation
- Keyboard + joystick + VR combined
- VR uses analog values (0–1)
- Physics reads via `getCurrentMovementState()`

### Constraints
- ❌ Do **NOT** bypass PlayerControls
- ❌ Do **NOT** rewrite physics

---

## 6. VR SPAWN SYSTEM (NEW, IMPORTANT)

### Problem
- Desktop spawns are correct
- VR spawns often: outside the level, near unfinished GLBs (e.g. red bunny), inside geometry
- Causes broken visuals & "no movement" illusion

### Solution: VR-only fixed spawns
```javascript
const VR_SPAWN_POINTS = {
  [LEVEL_IDS.LEVEL1]: new THREE.Vector3(60, 2.5, 15)
};
```

**Helper:** `applyVRSpawnForLevel(levelId)`
- Moves player collider, not camera
- Updates magenta debug sphere
- Runs only in VR
- Does NOT affect desktop

### Where it runs
- After level build (if VR already active)
- On VR session start **(critical)**

---

## 7. VR SESSION START PATCH (VERY IMPORTANT)

Inside `startVRSession()` after:
```javascript
await renderer.xr.setSession(session);
installVRCameraAnchor();
```

**Added:**
```javascript
if (typeof applyVRSpawnForLevel === "function") {
  const levelId = currentLevel || LEVEL_IDS.LEVEL1;
  applyVRSpawnForLevel(levelId);
}
```

**Why:** On Quest, players often load level in flat mode then enter VR afterward. Without this hook, VR spawn override never ran. This patch ensures VR spawn snaps correctly when entering VR (fixes "red bunny outside level" issue).

---

## 8. CURRENT VR ISSUES (OPEN)

**Still broken / unverified:**
- Meta Quest controller input: Left thumbstick movement not yet confirmed
- Some levels (3–6) appear corrupted in VR
- VR UI / menu flow messy on Quest browser
- Magenta collider sometimes not visible due to bad spawn

**What to test FIRST:**
1. Level 1
2. Enter VR
3. Confirm: Stable image (no flicker), Spawn near (60, 2.5, 15), Magenta sphere visible
4. Push **LEFT THUMBSTICK**
5. If sphere moves → input pipeline works
6. If not → inspect `VRInputProvider.getMovementState()`

---

## 9. HARD CONSTRAINTS (DO NOT VIOLATE)

### ❌ Do NOT:
- Add another render loop
- Set camera position inside `animate()`
- Let headset control X/Z position
- Rewrite PlayerControls
- Rewrite physics

### ✅ Do:
- Debug via collider marker
- Use VR spawn overrides
- Fix input at provider / aggregation level only

---

## 10. FINAL GOAL

Make Meta Quest VR fully playable:
- Left stick → move
- Right stick → smooth turn
- Trigger → shoot
- Grip → interact
- X / A → jump
- Y / B → menu
- Stable weapon, no head drift

---

## 🚦 CURRENT PRIORITY FOR CURSOR / LLM

**Verify Level 1 VR spawn + confirm whether the magenta collider moves when pushing left thumbstick.**

That single answer determines the final fix path.

---

**Status:** ✅ Synced to handover inbox | ⏳ Awaiting chest system handover
