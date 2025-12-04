# 🌱 GRASS/GROUND SYSTEM IMPLEMENTATION PLAN

**Date:** December 2, 2025  
**Source:** [James Smyth - Breath of the Wild Style Grass](https://github.com/James-Smyth/three-grass-demo)  
**Status:** 📋 **PLANNING PHASE**  
**Version:** 1.0

---

## 📋 OVERVIEW

Implement a modular grass/ground system inspired by Breath of the Wild style grass that can be easily configured per level. The system will support:
- **Grass Mode:** Animated grass blades with wind effects
- **Blank Mode:** Simple flat ground
- **Color Mode:** Solid color ground
- **Per-Level Configuration:** Each level can save its own ground type
- **Easy Switching:** God mode controls to change ground type in real-time

---

## 🎯 IMPLEMENTATION GOALS

### **Phase 1: Level 1 Integration**
- ✅ Create modular grass system (`grass-system.js`)
- ✅ Integrate into Level 1
- ✅ Add god mode controls for ground type switching
- ✅ Test grass rendering and wind animation

### **Phase 2: Per-Level Save System**
- ✅ Add save/load functionality for ground type per level
- ✅ Persist settings to localStorage
- ✅ Auto-load saved settings on level entry

### **Phase 3: Expand to All Levels**
- ✅ Integrate into Levels 2, 3, 4, 5
- ✅ Test all ground types across all levels
- ✅ Verify performance

---

## 🔧 TECHNICAL ARCHITECTURE

### **System Components:**

#### **1. Grass System Module (`three.js/grass-system.js`)**
```javascript
export class GrassSystem {
  constructor(scene, options = {}) {
    // Ground type: 'grass', 'blank', 'color'
    // Grass density, wind speed, texture options
    // Color options for solid color mode
  }
  
  setGroundType(type) {
    // Switch between grass, blank, color
  }
  
  update(delta) {
    // Update wind animation
  }
  
  dispose() {
    // Cleanup resources
  }
}
```

#### **2. Ground Types:**

**A. Grass Mode:**
- 5-vertex grass blades (performance optimized)
- Vertex shader for wind animation
- Fragment shader for texture sampling
- Wind gusts using UV + time in Sin function
- Vertex colors (black bottom, gray middle, white top)

**B. Blank Mode:**
- Simple flat plane
- No textures, no animation
- Minimal performance impact

**C. Color Mode:**
- Solid color plane
- Configurable color per level
- No textures, no animation

---

## 📐 GRASS GEOMETRY SPECIFICATIONS

### **Grass Blade Structure:**
Based on the article, each grass blade uses **5 vertices**:
- **Bottom 2 vertices:** Base of blade (black vertex color)
- **Middle vertex:** Center point (gray vertex color)
- **Top 2 vertices:** Tip of blade (white vertex color, half width of bottom)

### **Field Generation:**
- **Center Position:** Random position within field bounds
- **Rotation Angle:** Random rotation for each blade
- **UV Coordinates:** Normalized center position (0-1)
- **Vertex Colors:** Used for wind animation (bottom=black, middle=gray, top=white)

### **Performance Optimization:**
- Low vertex count (5 per blade) allows more grass
- Efficient shader calculations
- Instanced rendering if needed

---

## 🎨 SHADER IMPLEMENTATION

### **Vertex Shader:**
```glsl
// Wind animation using time + UV position
// Sin function with vertex color to determine movement
// Bottom vertices static, middle slight offset, top maximum movement
```

### **Fragment Shader:**
```glsl
// Sample grass texture (splattered green)
// Sample cloud noise texture for shadows
// Combine for final grass appearance
```

### **Key Features:**
- **Time-based animation:** Wind effect using time variable
- **UV-based variation:** Different blades move at different times (wind gusts)
- **Vertex color interpolation:** Smooth movement from bottom to top
- **Texture sampling:** Grass texture + cloud shadow texture

---

## 🔄 INTEGRATION WITH EXISTING SYSTEM

### **Similar to Sky System:**
- Modular ES module (`grass-system.js`)
- Per-level configuration object
- Save/load to localStorage
- God mode controls in options menu
- Proper disposal on level cleanup

### **Integration Points:**
1. **Level Building:** Replace/update floor geometry with grass system
2. **Level Entry:** Initialize grass system with saved settings
3. **Level Cleanup:** Dispose grass system when leaving level
4. **God Mode:** Add ground type selector to options menu

---

## 📁 FILE STRUCTURE

### **New Files:**
```
three.js/
  ├── grass-system.js          # Main grass system module
  └── shaders/
      ├── grass-vertex.glsl    # Vertex shader for grass
      └── grass-fragment.glsl   # Fragment shader for grass
```

### **Modified Files:**
```
three.js/
  └── main.js                  # Integration and management
```

---

## ⚙️ CONFIGURATION SYSTEM

### **Per-Level Configuration:**
```javascript
const levelGroundConfigs = {
  LEVEL1: {
    groundType: 'grass',      // 'grass', 'blank', 'color'
    grassDensity: 1000,       // Number of grass blades
    windSpeed: 1.0,           // Wind animation speed
    grassColor: 0x4a7c59,     // Base grass color
    groundColor: 0xaaaaaa,    // For 'color' mode
    texturePath: '/textures/grass/grass-texture.png',
    shadowTexturePath: '/textures/grass/cloud-noise.png'
  },
  // ... other levels
};
```

### **Saved Settings (localStorage):**
```javascript
{
  groundType: 'grass',
  grassDensity: 1000,
  windSpeed: 1.0,
  grassColor: 0x4a7c59,
  groundColor: 0xaaaaaa
}
```

---

## 🎮 GOD MODE CONTROLS

### **Options Menu Section:**
```
🌱 Ground System Configuration
├── Ground Type: [Grass ▼] [Blank] [Color]
├── Grass Density: [Slider: 0-5000]
├── Wind Speed: [Slider: 0.0-3.0]
├── Grass Color: [Color Picker]
├── Ground Color: [Color Picker] (for color mode)
└── 💾 Save Ground for Level [Button]
```

### **Real-Time Updates:**
- Changing ground type immediately updates the scene
- Sliders update grass density and wind speed in real-time
- Color pickers update grass/ground colors instantly

---

## 🔍 IMPLEMENTATION STEPS

### **Step 1: Extract Grass Code from GitHub**
1. Clone/download the [three-grass-demo](https://github.com/James-Smyth/three-grass-demo) repository
2. Analyze the source code structure
3. Extract shader code (vertex + fragment)
4. Extract geometry generation logic
5. Extract texture loading logic

### **Step 2: Create Modular System**
1. Create `grass-system.js` module
2. Implement `GrassSystem` class
3. Implement `GrassBlade` geometry generation
4. Implement shader material creation
5. Implement wind animation update

### **Step 3: Integrate into Level 1**
1. Import `GrassSystem` in `main.js`
2. Create `initializeGrassSystem(levelId)` function
3. Replace Level 1 floor with grass system
4. Test grass rendering and animation
5. Verify performance

### **Step 4: Add Ground Type Switching**
1. Implement `setGroundType(type)` method
2. Add grass/blank/color modes
3. Test switching between modes
4. Verify cleanup when switching

### **Step 5: Add God Mode Controls**
1. Add "Ground System Configuration" section to options menu
2. Add ground type selector
3. Add sliders for density and wind speed
4. Add color pickers
5. Add save button

### **Step 6: Add Per-Level Save**
1. Create `saveGroundSettingsForLevel(levelId)` function
2. Create `loadGroundSettingsForLevel(levelId)` function
3. Integrate save/load into level initialization
4. Test persistence across sessions

### **Step 7: Expand to All Levels**
1. Add grass system to Level 2
2. Add grass system to Level 3
3. Add grass system to Level 4
4. Add grass system to Level 5
5. Test all levels with different ground types

---

## 📊 PERFORMANCE CONSIDERATIONS

### **Optimization Strategies:**
- **Low Vertex Count:** 5 vertices per blade (as per article)
- **Efficient Shaders:** Optimized GLSL code
- **LOD System:** Reduce grass density at distance (future)
- **Frustum Culling:** Only render visible grass
- **Instanced Rendering:** Use instancing for many blades (if needed)

### **Target Performance:**
- **FPS:** Maintain 60 FPS with grass enabled
- **Memory:** Efficient texture usage
- **CPU:** Minimal impact from wind calculations

---

## 🧪 TESTING CHECKLIST

### **Level 1 Testing:**
- [ ] Grass renders correctly
- [ ] Wind animation works smoothly
- [ ] Ground type switching works
- [ ] Performance maintained (60 FPS)
- [ ] God mode controls functional
- [ ] Save/load works correctly

### **All Levels Testing:**
- [ ] All levels can use grass mode
- [ ] All levels can use blank mode
- [ ] All levels can use color mode
- [ ] Settings persist per level
- [ ] No performance issues
- [ ] Proper cleanup on level exit

---

## 📚 RESOURCES

### **Source Code:**
- **GitHub Repo:** [James-Smyth/three-grass-demo](https://github.com/James-Smyth/three-grass-demo)
- **Article:** [Breath of the Wild Style Grass in Three.js](https://jamessmyth.co.uk/blog/breath-of-the-wild-style-grass-in-three-js)

### **Key Concepts:**
- **5-vertex grass blades** for performance
- **Vertex colors** for wind animation (black→gray→white)
- **Sin function** with time + UV for wind gusts
- **Texture sampling** for grass color and shadows

---

## 🚀 NEXT STEPS

1. **Research GitHub Repo:** Download and analyze source code
2. **Extract Shaders:** Get vertex and fragment shader code
3. **Create Module:** Build `grass-system.js` with extracted code
4. **Test in Level 1:** Integrate and verify functionality
5. **Add Controls:** Implement god mode UI
6. **Add Save System:** Implement per-level persistence
7. **Expand:** Add to all levels

---

## 📝 NOTES

### **Similar to Sky System:**
- Modular architecture
- Per-level configuration
- Save/load functionality
- God mode controls
- Proper disposal

### **Key Differences:**
- Ground system replaces floor geometry
- Multiple ground types (not just one system)
- Texture-based rendering (grass mode)
- Wind animation (grass mode only)

---

**Last Updated:** December 2, 2025  
**Status:** 📋 **PLANNING PHASE - READY FOR IMPLEMENTATION**

