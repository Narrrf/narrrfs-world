# 📦 GLB Model Download Guide - CGTrader

**Date:** December 8, 2025  
**Purpose:** Determine which files to download from CGTrader for Phoenix GLB model

---

## 🎯 **FILES NEEDED FOR THREE.JS**

Based on the CGTrader download screenshot, here's what you need:

### **✅ REQUIRED FILES:**

1. **`Dragons1.glb` (or Phoenix equivalent) - 109 MB**
   - **CRITICAL:** This is the main GLB model file
   - Contains: Model geometry, materials, textures (if embedded), animations
   - **This is the primary file you need**

2. **`Dragons1_Textures.zip` (or Phoenix equivalent) - 236 MB**
   - **IMPORTANT:** Contains texture files (diffuse, normal, roughness, etc.)
   - GLB files can embed textures, but sometimes they're separate
   - **Download this to ensure you have all textures**

### **❌ NOT NEEDED (For Three.js):**

- `Dragons_Unrealproject.zip` - Unreal Engine project (not needed)
- `Dragons1.blend` - Blender source file (not needed for Three.js)
- `Dragon_LOD1.fbx` / `Dragon_LOD0.fbx` - FBX files (not needed if using GLB)
- `Dragons1.mtl` - Material file (usually embedded in GLB)
- `Dragons1.obj` - OBJ format (not needed)
- `Dragons1.usdz` / `Dragons1.usdc` - USD format (not needed)
- `Dragons1.x3d` - X3D format (not needed)

---

## 🔍 **HOW TO VERIFY WHAT'S IN THE GLB**

### **Option 1: Check File Size**
- **Large GLB (100+ MB):** Likely has embedded textures ✅
- **Small GLB (< 10 MB):** May need separate textures ⚠️

### **Option 2: Test in Three.js GLTF Viewer**
- Upload GLB to: https://gltf-viewer.donmccurdy.com/
- If textures show correctly → GLB has embedded textures ✅
- If textures are missing → Need separate textures ⚠️

### **Option 3: Check GLB Contents**
- GLB files can embed textures
- If textures are embedded, you only need the GLB file
- If textures are external, you need the textures zip

---

## 📋 **RECOMMENDED DOWNLOAD STRATEGY**

### **Minimum Download (Try First):**
1. ✅ **GLB file** (e.g., `Phoenix.glb`)
2. Test in Three.js
3. If textures work → Done! ✅
4. If textures missing → Download textures zip

### **Complete Download (Safe Option):**
1. ✅ **GLB file** (e.g., `Phoenix.glb`)
2. ✅ **Textures zip** (e.g., `Phoenix_Textures.zip`)
3. Extract textures if needed
4. Place textures in correct directory

---

## 🎯 **FOR PHOENIX MODEL SPECIFICALLY**

### **What to Download:**
1. **`Phoenix.glb`** (or similar name) - Main model file
2. **`Phoenix_Textures.zip`** (or similar name) - Textures (if available)

### **What to Check:**
- ✅ Does the GLB file include animations? (Should be in the file)
- ✅ Are textures embedded in GLB? (Check file size)
- ✅ What's the file size? (Large = likely has textures)

---

## 🔧 **THREE.JS INTEGRATION**

### **If GLB Has Embedded Textures:**
```javascript
// Simple - just load the GLB file
const loader = new GLTFLoader();
loader.load('path/to/Phoenix.glb', (gltf) => {
  scene.add(gltf.scene);
  // Animations are in gltf.animations
  // Textures are embedded in the GLB
});
```

### **If Textures Are Separate:**
```javascript
// Load GLB first
const loader = new GLTFLoader();
loader.load('path/to/Phoenix.glb', (gltf) => {
  scene.add(gltf.scene);
  
  // Manually load textures if needed
  // (Usually GLB handles this automatically)
});
```

---

## ✅ **RECOMMENDATION**

**For the Phoenix model from CGTrader:**

1. **Download:**
   - ✅ `Phoenix.glb` (or equivalent) - Main model
   - ✅ `Phoenix_Textures.zip` (or equivalent) - Textures (just in case)

2. **Test:**
   - Load GLB in Three.js
   - Check if textures appear
   - If textures work → You're done!
   - If textures missing → Extract textures zip and place in correct location

3. **File Size Guide:**
   - **GLB > 50 MB:** Likely has embedded textures ✅
   - **GLB < 10 MB:** May need separate textures ⚠️
   - **Textures zip:** Always good to have as backup

---

## 🎯 **BENEFITS OF GLB FORMAT**

1. **Single File:** Everything in one file (model + textures + animations)
2. **No Conversion:** Already in the right format for Three.js
3. **Better Performance:** Optimized for web
4. **Reliable:** No corruption issues like FBX2glTF conversion

---

## 📝 **NEXT STEPS AFTER DOWNLOAD**

1. **Place GLB file:**
   - `/textures/3d models/phoenix/Phoenix.glb`

2. **Update Phoenix loading code:**
   - Change path to point to GLB file
   - GLB format is simpler - no separate animations needed!

3. **Test:**
   - Load in Three.js
   - Verify textures
   - Verify animations
   - Check scale

---

**Guide Created:** December 8, 2025  
**Status:** ✅ **READY FOR USE**  
**Next:** Download Phoenix GLB model and integrate into Three.js

