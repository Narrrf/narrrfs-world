# 🎮 Character Model Integration Guide

**Created:** November 13, 2025  
**Purpose:** Guide for integrating GLTF/GLB character models into Three.js Cheese Temple  
**Status:** ✅ **ACTIVE - READY FOR USE**

---

## 🎯 **Overview**

The Three.js Cheese Temple now supports loading and displaying character models from GLTF/GLB files. This system replaces or supplements the simple placeholder player model with fully animated 3D characters.

### **Key Features:**
- ✅ **GLTF/GLB Model Loading** - Support for standard 3D model formats
- ✅ **Animation Support** - Automatic animation playback (Idle, Walk, Run, etc.)
- ✅ **Third-Person View** - Character visible in third-person camera mode
- ✅ **Movement-Based Animations** - Animations switch based on player movement
- ✅ **NPC Support** - Load multiple characters for NPCs, enemies, etc.
- ✅ **Model Caching** - Models are cached for performance

---

## 📋 **Unreal Engine Asset Conversion**

### **❌ Unreal Engine Assets Cannot Be Used Directly**

Unreal Engine assets (`.uasset`, `.fbx` with UE-specific settings) **cannot be used directly** in Three.js. They must be converted to GLTF/GLB format.

### **✅ Conversion Methods:**

#### **Method 1: Export from Unreal Engine to FBX, then Convert to GLTF**
1. **Export from Unreal Engine:**
   - Open your character in Unreal Engine
   - File → Export → FBX
   - Export with animations if available
   - Export textures separately (BaseColor, Normal, etc.)

2. **Convert FBX to GLTF/GLB:**
   - Use **Blender** (Free, recommended):
     - Import FBX file
     - Export as GLTF 2.0 (.gltf or .glb)
     - Include animations in export
     - Export textures as separate files or embed in GLB
   - Use **Online Converters:**
     - https://products.aspose.app/3d/conversion/fbx-to-gltf
     - https://www.convert3d.com/
   - Use **Command Line Tools:**
     - `gltf-pipeline` (Node.js): `npx gltf-pipeline -i input.fbx -o output.gltf`
     - `FBX2glTF` (Python): `python FBX2glTF.py input.fbx`

#### **Method 2: Direct Export from Blender (If You Have .blend Files)**
1. **Open in Blender:**
   - File → Import → FBX (if starting from FBX)
   - Or open existing .blend file

2. **Export as GLTF:**
   - File → Export → glTF 2.0 (.gltf/.glb)
   - Select "Export Selected" if needed
   - Include animations: ✅
   - Include textures: ✅ (or export separately)
   - Format: GLTF Separate (.gltf + .bin + textures) or GLB Binary (.glb)

#### **Method 3: Use Existing GLTF Models**
- **Use models from your collection:**
  - `three.js/public/textures/3d models/Monster 1/Big/glTF/` (17 models available)
  - `three.js/public/textures/3d models/Monster 1/Blob/glTF/` (17 models available)
  - `three.js/public/textures/3d models/Monster 1/Flying/glTF/` (17 models available)

---

## 🚀 **Using Character Models**

### **1. Load Player Character (Automatic)**

The player character is loaded automatically after the level is built. The default model is:

```javascript
// Default model path (can be changed)
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/Ninja.gltf")
```

### **2. Change Player Character Model**

To use a different character model, modify the model path in `main.js`:

```javascript
// In main.js, find the loadPlayerCharacter call:
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/YourCharacter.gltf")
```

### **3. Available Character Models**

**Monster 1 - Big Models:**
- `Alien.gltf`
- `Birb.gltf`
- `BlueDemon.gltf`
- `Bunny.gltf`
- `Cactoro.gltf`
- `Demon.gltf`
- `Dino.gltf`
- `Fish.gltf`
- `Frog.gltf`
- `Monkroose.gltf`
- `MushroomKing.gltf`
- `Ninja.gltf` (default)
- `Orc_Skull.gltf`
- `Orc.gltf`
- `Tribal.gltf`
- `Yeti.gltf`

**Monster 1 - Blob Models:**
- Same names as Big models, but smaller blob versions

**Monster 1 - Flying Models:**
- Same names as Big models, but optimized for flying

### **4. Load NPC Character**

To load an NPC character (enemy, NPC, etc.):

```javascript
// Load NPC at specific position
const npc = await loadNPCCharacter(
  "/textures/3d models/Monster 1/Big/glTF/Orc.gltf",
  new THREE.Vector3(10, 0, 10), // Position
  1.0 // Scale (1.0 = normal size)
);

// NPC will be added to scene automatically
// Animations will play automatically if available
```

---

## 🎬 **Animation System**

### **Supported Animation Names:**
- **Idle:** `Idle`, `idle`
- **Walk:** `Walk`, `walk`
- **Run:** `Running`, `running`, `Run`, `run`

### **Animation Behavior:**
- **Idle:** Plays when player is not moving
- **Walk:** Plays when player is moving (not sprinting)
- **Run:** Plays when player is sprinting (Shift key)

### **Custom Animations:**
If your model has different animation names, you can modify the animation detection in `loadPlayerCharacter`:

```javascript
// In loadPlayerCharacter function, modify defaultAnimations array:
const defaultAnimations = ['YourIdleAnim', 'YourWalkAnim', 'YourRunAnim'];
```

---

## 🎨 **Character Scaling**

### **Default Scaling:**
- **Player Character:** Scale `0.5, 0.5, 0.5` (50% of original size)
- **NPC Character:** Scale `1.0, 1.0, 1.0` (100% of original size)

### **Adjust Character Size:**
Modify the scale in `loadPlayerCharacter` or `loadNPCCharacter`:

```javascript
// In loadPlayerCharacter:
playerCharacterModel.scale.set(0.5, 0.5, 0.5); // 50% size

// In loadNPCCharacter:
npcModel.scale.set(1.0, 1.0, 1.0); // 100% size
```

### **Height Offset:**
Character height offset is automatically calculated based on scale:

```javascript
// In updatePlayerCharacter:
playerCharacterModel.position.y -= 0.85 * playerCharacterModel.scale.y;
```

---

## 🔧 **Technical Details**

### **Model Loading:**
- **Format:** GLTF 2.0 or GLB
- **Loader:** `GLTFLoader` from Three.js
- **Caching:** Models are cached to avoid reloading
- **Cloning:** Models are cloned for multiple instances (NPCs)

### **Animation System:**
- **Mixer:** `THREE.AnimationMixer` for animation playback
- **Actions:** Animation actions are stored in `playerCharacterAnimations` object
- **State Machine:** Simple state machine for Idle/Walk/Run animations
- **Fade Transitions:** Smooth fade between animations (0.2s)

### **Character Positioning:**
- **Position:** Matches player collider center
- **Rotation:** Faces movement direction (smooth rotation)
- **Height:** Automatically adjusted based on character scale

### **Visibility:**
- **First-Person:** Character is hidden
- **Third-Person:** Character is visible
- **Joystick View:** Character is visible

---

## 📝 **Implementation Checklist**

### **For New Character Models:**
- [ ] Export character from Unreal Engine to FBX
- [ ] Convert FBX to GLTF/GLB using Blender or online converter
- [ ] Place GLTF/GLB file in `three.js/public/textures/3d models/` directory
- [ ] Update model path in `loadPlayerCharacter` call
- [ ] Test character loading in game
- [ ] Adjust scale if needed
- [ ] Test animations (Idle, Walk, Run)
- [ ] Test visibility in first-person and third-person views

### **For NPC Characters:**
- [ ] Load character model using `loadNPCCharacter`
- [ ] Set position and scale
- [ ] Test animations
- [ ] Add NPC logic (AI, movement, etc.)

---

## 🚨 **Common Issues**

### **Issue 1: Model Not Loading**
- **Solution:** Check file path is correct
- **Solution:** Ensure file is in GLTF/GLB format
- **Solution:** Check browser console for errors

### **Issue 2: Character Too Large/Small**
- **Solution:** Adjust scale in `loadPlayerCharacter` or `loadNPCCharacter`
- **Solution:** Check model's original scale in Blender

### **Issue 3: Animations Not Playing**
- **Solution:** Check animation names match expected names (Idle, Walk, Run)
- **Solution:** Verify animations are exported in GLTF file
- **Solution:** Check browser console for animation errors

### **Issue 4: Character Not Visible**
- **Solution:** Switch to third-person view (character is hidden in first-person)
- **Solution:** Check `playerCharacterModel.visible` is `true`
- **Solution:** Verify character is added to scene

### **Issue 5: Character Position Wrong**
- **Solution:** Adjust height offset in `updatePlayerCharacter`
- **Solution:** Check character's pivot point in Blender
- **Solution:** Verify character scale is correct

---

## 🎯 **Best Practices**

### **Model Optimization:**
- **Vertex Count:** Keep under 10,000 vertices for player characters
- **Texture Size:** Use 512x512 or 1024x1024 textures
- **Animation Count:** Limit to 3-5 animations per character
- **File Size:** Keep GLTF/GLB files under 5MB

### **Animation Optimization:**
- **Frame Rate:** Export animations at 30 FPS (not 60 FPS)
- **Keyframes:** Remove unnecessary keyframes
- **Duration:** Keep animations short (1-3 seconds for loops)

### **Performance:**
- **Model Caching:** Models are automatically cached
- **Frustum Culling:** Enabled by default
- **Shadow Casting:** Enabled for characters
- **Animation Updates:** Only update when character is visible

---

## 📚 **Resources**

### **Model Conversion Tools:**
- **Blender:** https://www.blender.org/ (Free, recommended)
- **Online Converters:** https://products.aspose.app/3d/conversion/fbx-to-gltf
- **Command Line Tools:** `gltf-pipeline`, `FBX2glTF`

### **GLTF Documentation:**
- **GLTF Specification:** https://www.khronos.org/gltf/
- **Three.js GLTFLoader:** https://threejs.org/docs/#examples/en/loaders/GLTFLoader
- **Animation System:** https://threejs.org/docs/#manual/en/animation/Animation-system

### **Model Sources:**
- **Your Collection:** `three.js/public/textures/3d models/`
- **Free Models:** https://sketchfab.com/ (filter by GLTF format)
- **Paid Models:** https://www.turbosquid.com/ (filter by GLTF format)

---

## 🎉 **Success Criteria**

### **Character Integration is Successful When:**
- ✅ Character model loads without errors
- ✅ Character is visible in third-person view
- ✅ Character is hidden in first-person view
- ✅ Character position matches player position
- ✅ Character rotation matches movement direction
- ✅ Animations play correctly (Idle, Walk, Run)
- ✅ Character scale is appropriate for game world
- ✅ Character performance is good (60 FPS)

---

## 🚀 **Next Steps**

### **Future Enhancements:**
- **Custom Character Selection:** Allow players to choose their character
- **Character Customization:** Allow players to customize character appearance
- **More Animations:** Add more animation states (Jump, Attack, etc.)
- **NPC AI:** Add AI for NPC characters
- **Character Physics:** Add physics-based character movement
- **Character Interactions:** Add character-to-character interactions

---

## 📝 **Example Usage**

### **Load Player Character:**
```javascript
// Automatic loading (already implemented)
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/Ninja.gltf")
  .then((character) => {
    console.log("Character loaded:", character);
  })
  .catch((error) => {
    console.error("Error loading character:", error);
  });
```

### **Load NPC Character:**
```javascript
// Load NPC at specific position
const npc = await loadNPCCharacter(
  "/textures/3d models/Monster 1/Big/glTF/Orc.gltf",
  new THREE.Vector3(10, 0, 10),
  1.0
);

// NPC is automatically added to scene
// Animations will play automatically
```

### **Change Player Character:**
```javascript
// Change player character model
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/Yeti.gltf")
  .then((character) => {
    console.log("Player character changed to Yeti");
  });
```

---

## 🧀 **Final Notes**

- **Character models are optional** - The game works with or without character models
- **Simple player model is fallback** - If GLTF character fails to load, simple player model is used
- **Performance is important** - Keep character models optimized for good performance
- **Animations are automatic** - No manual animation management needed
- **Character visibility is automatic** - Character is shown/hidden based on camera mode

---

**Last Updated:** November 13, 2025  
**Status:** ✅ **ACTIVE - READY FOR USE**  
**Version:** 1.0.0

