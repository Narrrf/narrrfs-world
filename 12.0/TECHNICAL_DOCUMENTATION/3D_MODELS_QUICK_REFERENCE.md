# 3D Models Quick Reference

**Last Updated:** November 13, 2025 (Updated with Animation Library [Standard] Collection, Survival Pack Collection, and Old School Weapons Collection)

## 🎮 Quick Path Reference

### Current Player Character
```javascript
/textures/3d models/Monster 1/Big/glTF/Ninja.gltf
```

### Alternative Character Models

#### Big Monsters (Recommended for Player)
```javascript
/textures/3d models/Monster 1/Big/glTF/Orc.gltf
/textures/3d models/Monster 1/Big/glTF/Tribal.gltf
/textures/3d models/Monster 1/Big/glTF/Demon.gltf
/textures/3d models/Monster 1/Big/glTF/Yeti.gltf
```

#### Blob Monsters (Smaller Characters)
```javascript
/textures/3d models/Monster 1/Blob/glTF/Wizard.gltf
/textures/3d models/Monster 1/Blob/glTF/Ninja.gltf
/textures/3d models/Monster 1/Blob/glTF/Orc.gltf
```

#### Flying Monsters (Aerial Characters)
```javascript
/textures/3d models/Monster 1/Flying/glTF/Dragon.gltf
/textures/3d models/Monster 1/Flying/glTF/Ghost.gltf
/textures/3d models/Monster 1/Flying/glTF/Demon.gltf
```

### Weapon Models (FBX Format)
```javascript
/textures/3d models/Fire Weapons 1/FBX/AssaultRifle_1.fbx
/textures/3d models/Fire Weapons 1/FBX/Pistol_1.fbx
/textures/3d models/Fire Weapons 1/FBX/Shotgun_1.fbx
```

### Weapon Accessories
```javascript
/textures/3d models/Fire Weapons 1/FBX/Accessories/Scope_1.fbx
/textures/3d models/Fire Weapons 1/FBX/Accessories/Silencer_1.fbx
/textures/3d models/Fire Weapons 1/FBX/Accessories/Flashlight.fbx
```

### Survival Pack Items (FBX Format)
```javascript
// Tools
/textures/3d models/Survival Pack/FBX/Axe.fbx
/textures/3d models/Survival Pack/FBX/Shovel.fbx
/textures/3d models/Survival Pack/FBX/Knife.fbx

// Survival Items
/textures/3d models/Survival Pack/FBX/Backpack.fbx
/textures/3d models/Survival Pack/FBX/FirstAidKit.fbx
/textures/3d models/Survival Pack/FBX/WaterBottle_1.fbx

// Camp Items
/textures/3d models/Survival Pack/FBX/Tent.fbx
/textures/3d models/Survival Pack/FBX/Torch.fbx
/textures/3d models/Survival Pack/FBX/Bonfire.fbx

// Weapons
/textures/3d models/Survival Pack/FBX/Pistol_1.fbx
/textures/3d models/Survival Pack/FBX/Revolver_1.fbx
/textures/3d models/Survival Pack/FBX/Shotgun_1.fbx
```

### Old School Weapons (Medieval Weapons - FBX Format)
```javascript
// Swords
/textures/3d models/Old School Weapons/FBX/Sword.fbx
/textures/3d models/Old School Weapons/FBX/Sword_Golden.fbx
/textures/3d models/Old School Weapons/FBX/Claymore.fbx

// Bows
/textures/3d models/Old School Weapons/FBX/Bow_Wooden.fbx
/textures/3d models/Old School Weapons/FBX/Bow_Golden.fbx
/textures/3d models/Old School Weapons/FBX/Bow_Evil.fbx

// Axes
/textures/3d models/Old School Weapons/FBX/Axe.fbx
/textures/3d models/Old School Weapons/FBX/Axe_Double.fbx

// Shields
/textures/3d models/Old School Weapons/FBX/Shield_Round.fbx
/textures/3d models/Old School Weapons/FBX/Shield_Celtic_Golden.fbx

// Other Weapons
/textures/3d models/Old School Weapons/FBX/Spear.fbx
/textures/3d models/Old School Weapons/FBX/Scythe.fbx
/textures/3d models/Old School Weapons/FBX/Arrow.fbx
```

### Animation Library [Standard] (NEW - Quaternius - GLB Format)
```javascript
// Universal Animation Library (Quaternius) - FREE, CC0 1.0 License
/textures/3d models/Animation Libary/Animation Library[Standard]/Godot/AnimationLibrary_Godot_Standard.glb
```

## 🔧 Usage in Code

### Load Player Character
```javascript
// In three.js/main.js
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/Ninja.gltf");
```

### Load Different Character
```javascript
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/Orc.gltf");
```

### Enable Character Loading
Uncomment this line in `main.js` (around line 550):
```javascript
loadPlayerCharacter("/textures/3d models/Monster 1/Big/glTF/Ninja.gltf");
```

## 📊 Model Statistics

- **Total Monster Models:** 51 (17 Big + 17 Blob + 17 Flying)
- **Total Weapon Models:** 35+ weapons (Fire Weapons 1)
- **Total Accessories:** 13 types (Fire Weapons 1)
- **Total Survival Items:** 53 items (Survival Pack)
- **Total Medieval Weapons:** 24 weapons (Old School Weapons)
- **Animation Library:** 1 GLB file (Animation Library [Standard] - Quaternius) ✅ **NEW**
- **Recommended Format:** glTF/GLB for characters, FBX for weapons and items
- **Animation Support:** Yes (for Monster models and Animation Library)
- **Texture Atlas:** `Atlas_Monsters.png` (for Monster models)

## ✅ Verification

- [x] Models exist in correct folder structure
- [x] Paths are correct (`/textures/3d models/...`)
- [x] glTF files available for all Monster 1 characters
- [x] FBX files available for all Fire Weapons 1 weapons
- [x] FBX files available for all Survival Pack items
- [x] FBX files available for all Old School Weapons items
- [x] GLB file available for Animation Library [Standard] ✅ **NEW**
- [x] Character loading function implemented
- [x] Animation system implemented
- [ ] Character loading enabled (currently disabled for debugging)
- [ ] Animation Library [Standard] tested (need to verify file size, animations, character count)

---

## 🎭 Animation Library [Standard] (NEW)

**Path:** `/textures/3d models/Animation Libary/Animation Library[Standard]/Godot/AnimationLibrary_Godot_Standard.glb`  
**Format:** GLB ✅ **DIRECT THREE.JS SUPPORT**  
**License:** CC0 1.0 Universal (Public Domain) ✅ **FREE**  
**Publisher:** Quaternius (@Quaternius)  
**Status:** ⏳ **TO BE TESTED**

**Quick Notes:**
- ✅ **FREE:** €0 (completely free - public domain)
- ✅ **Direct GLB Support:** Works directly with Three.js (no conversion needed)
- ✅ **Public Domain License:** CC0 1.0 - no license restrictions
- ✅ **File Size:** 6.36 MB (VERIFIED - much smaller than Mouse Character!)
- ✅ **Implementation Status:** ✅ **IMPLEMENTED** - Now loading as player character
- ⚠️ **Animations:** [TO BE VERIFIED IN-GAME]
- ⚠️ **Character Count:** [TO BE VERIFIED IN-GAME]

**Comparison:**
- **vs Mouse Character:** FREE vs €4.12-€10.33
- **vs Mouse Knight:** FREE vs €13.44
- **vs Free Models:** Same license (free), need to compare file size

**For full comparison, see:** `ANIMATION_LIBRARY_STANDARD_COMPARISON.md`  
**For full documentation, see:** `3D_MODELS_INVENTORY.md`

