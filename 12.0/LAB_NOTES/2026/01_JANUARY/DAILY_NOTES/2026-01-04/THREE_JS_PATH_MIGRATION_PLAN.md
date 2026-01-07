# 🚀 Three.js Asset Path Migration Plan - Production Ready

**Date:** January 4, 2026  
**Purpose:** Migrate all asset paths from local development to production-ready format  
**Status:** 📋 **PLAN CREATED - READY FOR IMPLEMENTATION**

---

## 🎯 **OBJECTIVE**

Update all asset paths in the three.js game to use production-ready absolute paths that work on both local development and Render production environment.

**Current State:**
- Local paths work: `C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\...`
- Production paths needed: `/public/three.js/public/...` (absolute from web root)

**Target State:**
- All paths use absolute format: `/public/three.js/public/[category]/[file]`
- Works in both local (`http://localhost/public/three.js/public/...`) and production (`https://narrrfs.world/public/three.js/public/...`)

---

## 📋 **PATH MIGRATION STRATEGY**

### **Current Path Patterns (Need Update):**

1. **Audio Paths** (config-system.js):
   - `/audio/character/footstep_cheese.ogg` → `/public/three.js/public/audio/character/footstep_cheese.ogg`
   - `/sounds/music/level1.mp3` → `/public/three.js/public/sounds/music/level1.mp3`
   - `/sounds/invaders/weapons/normal_shoot.wav` → `/public/three.js/public/sounds/invaders/weapons/normal_shoot.wav`

2. **Model Paths** (main.js, chest-system.js, weapon-system.js, etc.):
   - `/textures/3d models/chest2/Chest2.glb` → `/public/three.js/public/textures/3d models/chest2/Chest2.glb`
   - `/textures/3d models/tree-with-arms/tree-with-arms.glb` → `/public/three.js/public/textures/3d models/tree-with-arms/tree-with-arms.glb`
   - `/textures/3d models/Fire Weapons 1/FBX/Rifle_1.fbx` → `/public/three.js/public/textures/3d models/Fire Weapons 1/FBX/Rifle_1.fbx`

3. **Texture Paths** (grass-system.js, phoenix2.js, alien-spider.js):
   - Any texture paths need `/public/three.js/public/` prefix

---

## 🔧 **IMPLEMENTATION PLAN**

### **STEP 1: Update config-system.js (Audio Paths)**

**File:** `public/three.js/config-system.js`

**Changes Needed:**
```javascript
// ❌ OLD (Current):
export const CHARACTER_FOOTSTEP_AUDIO = "/audio/character/footstep_cheese.ogg";
export const CHARACTER_JUMP_AUDIO = "/audio/character/jump_cheese.ogg";
export const BACKGROUND_MUSIC_PATHS = {
  [LEVEL_IDS.LEVEL1]: "/sounds/music/level1.mp3",
  // ...
};

// ✅ NEW (Production Ready):
export const CHARACTER_FOOTSTEP_AUDIO = "/public/three.js/public/audio/character/footstep_cheese.ogg";
export const CHARACTER_JUMP_AUDIO = "/public/three.js/public/audio/character/jump_cheese.ogg";
export const BACKGROUND_MUSIC_PATHS = {
  [LEVEL_IDS.LEVEL1]: "/public/three.js/public/sounds/music/level1.mp3",
  [LEVEL_IDS.LEVEL2]: "/public/three.js/public/sounds/music/level2.mp3",
  [LEVEL_IDS.LEVEL3]: "/public/three.js/public/sounds/music/level3.mp3",
  [LEVEL_IDS.LEVEL4]: "/public/three.js/public/sounds/music/level4.mp3",
  [LEVEL_IDS.LEVEL5]: "/public/three.js/public/sounds/music/level5.mp3",
  [LEVEL_IDS.LEVEL6]: "/public/three.js/public/sounds/music/level6.mp3"
};
export const CHEESE_PLATFORM_AUDIO = "/public/three.js/public/audio/gameplay/cheese_platform_active.ogg";
export const CHEESE_AIM_CLEAR_AUDIO = "/public/three.js/public/audio/gameplay/cheese_aim_clear.wav";
export const LEVER_AUDIO = "/public/three.js/public/audio/gameplay/slever.ogg";
export const BLOCK_MOVED_AUDIO = "/public/three.js/public/audio/gameplay/block_moved_correct.ogg";
export const LEVEL_UP_AUDIO = "/public/three.js/public/audio/gameplay/LEVEL%20UP!.wav";
export const LEVEL4_SHOOT_AUDIO = "/public/three.js/public/sounds/invaders/weapons/normal_shoot.wav";
export const LEVEL4_SF13_SHOOT_AUDIO = "/public/three.js/public/sounds/invaders/weapons/normal_shoot.wav";
```

**Lines to Update:**
- Line 147: `CHARACTER_FOOTSTEP_AUDIO`
- Line 148: `CHARACTER_JUMP_AUDIO`
- Line 149: `CHEESE_PLATFORM_AUDIO`
- Line 150: `CHEESE_AIM_CLEAR_AUDIO`
- Line 151: `LEVER_AUDIO`
- Line 152: `BLOCK_MOVED_AUDIO`
- Line 153: `LEVEL_UP_AUDIO`
- Line 154: `LEVEL4_SHOOT_AUDIO`
- Line 155: `LEVEL4_SF13_SHOOT_AUDIO`
- Lines 161-168: `BACKGROUND_MUSIC_PATHS` object (all 6 levels)

---

### **STEP 2: Update main.js (Model & Texture Paths)**

**File:** `public/three.js/main.js`

**Search Pattern:** Find all paths starting with `/textures/` or `./public/textures/`

**Common Patterns to Update:**
```javascript
// ❌ OLD:
"/textures/3d models/chest2/Chest2.glb"
"./public/textures/3d models/tree-with-arms/tree-with-arms.glb"
"/textures/3d models/Survival Pack/FBX/BearTrap_Open.fbx"

// ✅ NEW:
"/public/three.js/public/textures/3d models/chest2/Chest2.glb"
"/public/three.js/public/textures/3d models/tree-with-arms/tree-with-arms.glb"
"/public/three.js/public/textures/3d models/Survival Pack/FBX/BearTrap_Open.fbx"
```

**Key Functions to Check:**
- `loadModel()` - Model loading function
- `loadTexture()` - Texture loading function
- `normalizeAssetPath()` - Path normalization function (may need update)
- All level creation functions (createLevel1Tree, createLevel1BearTrap, etc.)
- Chest creation functions
- Weapon loading functions
- Boss model loading (Phoenix, Alien Spider)

**Estimated Locations:**
- Chest model paths: ~50-100 locations
- Tree/decorative model paths: ~20-30 locations
- Weapon model paths: ~10-20 locations
- Boss model paths: ~5-10 locations
- Texture paths: ~20-30 locations

---

### **STEP 3: Update chest-system.js**

**File:** `public/three.js/chest-system.js`

**Search Pattern:** Find chest model path

**Update:**
```javascript
// ❌ OLD:
const chestModelPath = "/textures/3d models/chest2/Chest2.glb";
// OR
const chestModelPath = "./public/textures/3d models/chest2/Chest2.glb";

// ✅ NEW:
const chestModelPath = "/public/three.js/public/textures/3d models/chest2/Chest2.glb";
```

**Also Check:**
- Chest sound effect paths (if any)
- Any other asset references

---

### **STEP 4: Update weapon-system.js**

**File:** `public/three.js/weapon-system.js`

**Search Pattern:** Find weapon model paths

**Update:**
```javascript
// ❌ OLD:
"/textures/3d models/Fire Weapons 1/FBX/Rifle_1.fbx"
"./public/textures/3d models/Fire Weapons 1/FBX/Pistol_1.fbx"

// ✅ NEW:
"/public/three.js/public/textures/3d models/Fire Weapons 1/FBX/Rifle_1.fbx"
"/public/three.js/public/textures/3d models/Fire Weapons 1/FBX/Pistol_1.fbx"
```

**Check:**
- All weapon slot definitions (LEVEL4_WEAPON_SLOTS or similar)
- Weapon loading functions

---

### **STEP 5: Update grass-system.js**

**File:** `public/three.js/grass-system.js`

**Search Pattern:** Find texture paths for grass and clouds

**Update:**
```javascript
// ❌ OLD:
"/textures/grass/grass_texture.png"
"./public/textures/clouds/cloud_texture.png"

// ✅ NEW:
"/public/three.js/public/textures/grass/grass_texture.png"
"/public/three.js/public/textures/clouds/cloud_texture.png"
```

---

### **STEP 6: Update phoenix2.js**

**File:** `public/three.js/phoenix2.js`

**Search Pattern:** Find Phoenix model and texture paths

**Update:**
```javascript
// ❌ OLD:
"/textures/3d models/Dragons1.glb"
"/textures/3d models/phoenix_textures/Black.png"

// ✅ NEW:
"/public/three.js/public/textures/3d models/Dragons1.glb"
"/public/three.js/public/textures/3d models/phoenix_textures/Black.png"
```

---

### **STEP 7: Update alien-spider.js**

**File:** `public/three.js/alien-spider.js`

**Search Pattern:** Find Alien Spider model and texture paths

**Update:**
```javascript
// ❌ OLD:
"/textures/3d models/AFC_03.fbx"
"/textures/3d models/alien_spider_textures/Default.tga"

// ✅ NEW:
"/public/three.js/public/textures/3d models/AFC_03.fbx"
"/public/three.js/public/textures/3d models/alien_spider_textures/Default.tga"
```

---

### **STEP 8: Update player-model.js**

**File:** `public/three.js/player-model.js`

**Search Pattern:** Find player model paths

**Update:**
```javascript
// ❌ OLD:
"/textures/3d models/player/player_model.glb"

// ✅ NEW:
"/public/three.js/public/textures/3d models/player/player_model.glb"
```

---

### **STEP 9: Check normalizeAssetPath() Function**

**File:** `public/three.js/main.js`

**Purpose:** This function may need updates to handle the new path format correctly.

**Current Implementation (from rule):**
```javascript
function normalizeAssetPath(path) {
  if (!path) return path;
  // If already absolute (starts with /), return as-is
  if (path.startsWith('/')) return path;
  // If relative path starts with ./public/, convert to absolute
  if (path.startsWith('./public/')) {
    return '/public/three.js' + path.substring(1); // Remove leading . to get /public/...
  }
  // If relative path starts with public/, add /public/three.js prefix
  if (path.startsWith('public/')) {
    return '/public/three.js/' + path;
  }
  // Otherwise return as-is (might be a URL or already correct)
  return path;
}
```

**Update Needed:**
- Ensure it handles `/textures/` paths (adds `/public/three.js/public/` prefix)
- Ensure it handles `/sounds/` paths (adds `/public/three.js/public/` prefix)
- Ensure it handles `/audio/` paths (adds `/public/three.js/public/` prefix)

**Proposed Update:**
```javascript
function normalizeAssetPath(path) {
  if (!path) return path;
  
  // If already has /public/three.js/public/ prefix, return as-is
  if (path.startsWith('/public/three.js/public/')) return path;
  
  // If already absolute (starts with /), add /public/three.js/public prefix
  if (path.startsWith('/') && !path.startsWith('/public/three.js/')) {
    // Handle paths like /textures/, /sounds/, /audio/
    if (path.startsWith('/textures/') || path.startsWith('/sounds/') || path.startsWith('/audio/')) {
      return '/public/three.js/public' + path;
    }
    return path; // Other absolute paths (like /api/) return as-is
  }
  
  // If relative path starts with ./public/, convert to absolute
  if (path.startsWith('./public/')) {
    return '/public/three.js' + path.substring(1); // Remove leading . to get /public/...
  }
  
  // If relative path starts with public/, add /public/three.js prefix
  if (path.startsWith('public/')) {
    return '/public/three.js/' + path;
  }
  
  // Otherwise return as-is (might be a URL or already correct)
  return path;
}
```

---

## 📝 **IMPLEMENTATION CHECKLIST**

### **Phase 1: Configuration Files**
- [ ] Update `config-system.js` - All audio paths (10+ paths)
- [ ] Test audio loading locally
- [ ] Verify paths work in production format

### **Phase 2: Core Game Files**
- [ ] Update `main.js` - Model paths (100+ locations)
- [ ] Update `main.js` - Texture paths (20+ locations)
- [ ] Update `main.js` - normalizeAssetPath() function
- [ ] Test model loading locally
- [ ] Test texture loading locally

### **Phase 3: System Modules**
- [ ] Update `chest-system.js` - Chest model path
- [ ] Update `weapon-system.js` - Weapon model paths
- [ ] Update `grass-system.js` - Grass texture paths
- [ ] Update `phoenix2.js` - Phoenix model/texture paths
- [ ] Update `alien-spider.js` - Alien Spider model/texture paths
- [ ] Update `player-model.js` - Player model paths (if any)

### **Phase 4: Testing**
- [ ] Test all assets load locally
- [ ] Test all assets load in production (after deployment)
- [ ] Verify no 404 errors in browser console
- [ ] Test game functionality (models, sounds, textures all work)

### **Phase 5: Documentation**
- [ ] Update `10_FILE_PATH_LOCAL_VS_PRODUCTION_RULE.md` with three.js path patterns
- [ ] Document any path normalization changes
- [ ] Create migration notes for future reference

---

## 🔍 **FINDING ALL PATHS TO UPDATE**

### **Search Commands (Run in terminal):**

```bash
# Find all texture paths
grep -r "/textures/" public/three.js/*.js | grep -v "node_modules"

# Find all sound paths
grep -r "/sounds/" public/three.js/*.js | grep -v "node_modules"

# Find all audio paths
grep -r "/audio/" public/three.js/*.js | grep -v "node_modules"

# Find relative paths
grep -r "\./public/" public/three.js/*.js | grep -v "node_modules"

# Find model file extensions
grep -r "\.glb\|\.gltf\|\.fbx" public/three.js/*.js | grep -v "node_modules"

# Find audio file extensions
grep -r "\.mp3\|\.ogg\|\.wav" public/three.js/*.js | grep -v "node_modules"
```

---

## 🚨 **CRITICAL NOTES**

### **Path Format Rules:**
1. **ALWAYS use absolute paths** starting with `/public/three.js/public/`
2. **NO relative paths** like `./public/` or `../public/`
3. **NO paths starting with `/textures/`** or `/sounds/` directly (must have `/public/three.js/public/` prefix)
4. **API paths stay as-is** (e.g., `/api/...` doesn't need prefix)

### **Testing Requirements:**
1. **Local Testing:** Verify all assets load at `http://localhost/public/three.js/public/...`
2. **Production Testing:** Verify all assets load at `https://narrrfs.world/public/three.js/public/...`
3. **Browser Console:** Check for 404 errors after deployment
4. **Game Functionality:** Test that models, sounds, textures all work correctly

### **Backward Compatibility:**
- The `normalizeAssetPath()` function should handle both old and new formats during transition
- After migration, all paths should use new format directly
- Normalization function can be simplified after all paths are updated

---

## 📊 **ESTIMATED SCOPE**

**Files to Update:** ~10-15 files
**Total Path Updates:** ~200-300 locations
**Time Estimate:** 2-4 hours for complete migration
**Risk Level:** Medium (requires careful testing)

---

## ✅ **SUCCESS CRITERIA**

1. ✅ All asset paths use `/public/three.js/public/...` format
2. ✅ No 404 errors in browser console (local or production)
3. ✅ All models load correctly
4. ✅ All sounds play correctly
5. ✅ All textures display correctly
6. ✅ Game functionality unchanged (everything works as before)

---

## 🔄 **ROLLBACK PLAN**

If issues occur:
1. Keep backup of original files
2. Revert changes file by file
3. Test after each revert
4. Document any issues found

---

**Status:** 📋 **PLAN READY - AWAITING IMPLEMENTATION**  
**Next Step:** Begin with `config-system.js` (easiest, most centralized)

