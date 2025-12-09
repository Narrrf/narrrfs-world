# 🔄 FBX2GLTF INTEGRATION PLAN

**Date:** December 8, 2025  
**Status:** 📋 **PLANNING PHASE**  
**Tool:** [FBX2glTF by Facebook](https://github.com/facebookincubator/FBX2glTF)  
**Purpose:** Convert FBX models to GLTF format for better web compatibility and performance

---

## 🎯 OBJECTIVES

1. **Convert FBX models to GLTF** - Better web performance, smaller file sizes, better texture/material support
2. **Fix Phoenix Model Issues** - Convert Phoenix FBX to GLTF to resolve scaling, animation, and texture issues
3. **Batch Conversion Tool** - Convert multiple FBX files at once
4. **Dev Team Integration** - Accessible via God Mode or dev interface
5. **Automated Workflow** - Streamline model conversion process

---

## 📊 CURRENT SITUATION

### **FBX Models in Project:**
- **Weapons:** `/textures/3d models/Fire Weapons 1/FBX/` (50+ FBX files)
- **Monsters:** `/textures/3d models/Monster 1/Big/FBX/` (16 FBX files)
- **Phoenix:** `/textures/3d models/phoenix/Phoenix.fbx/Base mesh/` (9 FBX files)
- **Phoenix Animations:** `/textures/3d models/phoenix/Phoenix_Animations/Animations_FBX/` (29 FBX files)
- **Sci-Fi Weapons:** `/textures/3d models/Sci-Fi Modular Gun Pack/Guns/FBX/` (20 FBX files)
- **Old School Weapons:** `/textures/3d models/Old School Weapons/FBX/` (24 FBX files)
- **Survival Pack:** `/textures/3d models/Survival Pack/FBX/` (53 FBX files)

### **Current Issues:**
- **Phoenix Model:** Scaling, animation, and texture issues with FBX format
- **File Size:** FBX files are larger than GLTF
- **Web Performance:** GLTF is optimized for web, FBX is not
- **Material Support:** GLTF has better PBR material support
- **Animation:** GLTF animations are more reliable in Three.js

### **Existing GLTF Models:**
- Some models already have GLTF versions (Monster 1, Sci-Fi Weapons)
- Maps are already in GLTF format
- Mouse character has GLB versions

---

## 🔧 FBX2GLTF TOOL OVERVIEW

### **Tool Information:**
- **Repository:** https://github.com/facebookincubator/FBX2glTF
- **License:** 3-clause BSD
- **Language:** C++ (command-line tool)
- **NPM Package:** Available at `npm/fbx2gltf` (may be easier for integration)
- **Features:**
  - Converts FBX to glTF 2.0
  - Supports animations (baking method)
  - Supports materials (PBR metallic-roughness)
  - Supports Draco compression (optional)
  - Supports blend shapes
  - Command-line interface

### **Installation Options:**

#### **Option 1: NPM Package (Recommended for Node.js Integration)**
```bash
npm install fbx2gltf
```
- Easier to integrate with Node.js scripts
- Cross-platform
- May have limitations compared to full C++ build

#### **Option 2: Pre-built Binary (Easiest)**
- Download pre-built executable for Windows
- Place in project tools directory
- Call from Node.js scripts

#### **Option 3: Build from Source (Most Control)**
- Requires FBX SDK, CMake, Conan
- Full feature support
- More complex setup

---

## 🏗️ INTEGRATION ARCHITECTURE

### **Phase 1: Command-Line Tool Setup**

#### **1.1 Install FBX2glTF**
```bash
# Option A: NPM package (if available)
npm install --save-dev fbx2gltf

# Option B: Download pre-built binary
# Download from GitHub releases
# Place in: three.js/tools/fbx2gltf/FBX2glTF.exe (Windows)
```

#### **1.2 Create Conversion Script**
**File:** `three.js/tools/fbx2gltf/convert-fbx-to-gltf.js`

```javascript
/**
 * FBX to GLTF Converter Tool
 * Converts FBX models to GLTF format using FBX2glTF
 */

import { exec } from 'child_process';
import { promisify } from 'util';
import path from 'path';
import fs from 'fs';

const execAsync = promisify(exec);

class FBX2GLTFConverter {
  constructor(options = {}) {
    this.fbx2gltfPath = options.fbx2gltfPath || './tools/fbx2gltf/FBX2glTF.exe';
    this.outputDir = options.outputDir || './public/textures/3d models';
    this.dracoCompression = options.dracoCompression || false;
    this.pbrMetallicRoughness = options.pbrMetallicRoughness || true;
  }

  /**
   * Convert single FBX file to GLTF
   * @param {string} inputPath - Path to FBX file
   * @param {string} outputPath - Path to output GLTF file (optional)
   * @returns {Promise<string>} Path to converted GLTF file
   */
  async convertFile(inputPath, outputPath = null) {
    if (!fs.existsSync(inputPath)) {
      throw new Error(`FBX file not found: ${inputPath}`);
    }

    // Generate output path if not provided
    if (!outputPath) {
      const dir = path.dirname(inputPath);
      const name = path.basename(inputPath, '.fbx');
      outputPath = path.join(dir, 'glTF', `${name}.gltf`);
    }

    // Ensure output directory exists
    const outputDir = path.dirname(outputPath);
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }

    // Build command
    let command = `"${this.fbx2gltfPath}" -i "${inputPath}" -o "${outputPath}"`;
    
    if (this.pbrMetallicRoughness) {
      command += ' --pbr-metallic-roughness';
    }
    
    if (this.dracoCompression) {
      command += ' --draco';
    }

    console.log(`🔄 Converting: ${inputPath} → ${outputPath}`);
    console.log(`📝 Command: ${command}`);

    try {
      const { stdout, stderr } = await execAsync(command);
      if (stderr) {
        console.warn(`⚠️ Warnings: ${stderr}`);
      }
      console.log(`✅ Converted: ${outputPath}`);
      return outputPath;
    } catch (error) {
      console.error(`❌ Conversion failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Convert multiple FBX files (batch conversion)
   * @param {string[]} inputPaths - Array of FBX file paths
   * @param {Object} options - Conversion options
   * @returns {Promise<string[]>} Array of converted GLTF file paths
   */
  async convertBatch(inputPaths, options = {}) {
    const results = [];
    const errors = [];

    for (const inputPath of inputPaths) {
      try {
        const outputPath = await this.convertFile(inputPath, options.outputPath);
        results.push(outputPath);
      } catch (error) {
        errors.push({ inputPath, error: error.message });
        console.error(`❌ Failed to convert ${inputPath}:`, error.message);
      }
    }

    console.log(`\n📊 Batch Conversion Complete:`);
    console.log(`   ✅ Successful: ${results.length}`);
    console.log(`   ❌ Failed: ${errors.length}`);

    if (errors.length > 0) {
      console.log(`\n❌ Errors:`);
      errors.forEach(({ inputPath, error }) => {
        console.log(`   - ${inputPath}: ${error}`);
      });
    }

    return results;
  }

  /**
   * Find all FBX files in a directory
   * @param {string} dirPath - Directory to search
   * @param {boolean} recursive - Search recursively
   * @returns {string[]} Array of FBX file paths
   */
  findFBXFiles(dirPath, recursive = true) {
    const fbxFiles = [];

    function searchDir(dir) {
      const files = fs.readdirSync(dir);
      
      for (const file of files) {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);

        if (stat.isDirectory() && recursive) {
          searchDir(filePath);
        } else if (file.endsWith('.fbx')) {
          fbxFiles.push(filePath);
        }
      }
    }

    searchDir(dirPath);
    return fbxFiles;
  }
}

export default FBX2GLTFConverter;
```

### **Phase 2: Dev Interface Integration**

#### **2.1 God Mode Integration**
**File:** `three.js/main.js` (add to God Mode menu)

```javascript
// Add to God Mode key handler (G key)
if (godMode && event.key === 'C' && event.ctrlKey) {
  // Ctrl+C = Convert FBX to GLTF
  showFBXConverterMenu();
  event.preventDefault();
}

function showFBXConverterMenu() {
  // Create modal or overlay for FBX conversion
  // Options:
  // - Convert single file
  // - Convert directory
  // - Batch convert all FBX files
  // - Convert Phoenix model specifically
}
```

#### **2.2 Dev Tool Page**
**File:** `public/dev-tools/fbx-converter.html`

```html
<!DOCTYPE html>
<html>
<head>
  <title>FBX to GLTF Converter - Dev Tools</title>
  <style>
    /* Professional dev tool styling */
  </style>
</head>
<body>
  <h1>🔄 FBX to GLTF Converter</h1>
  
  <div class="converter-panel">
    <h2>Single File Conversion</h2>
    <input type="file" id="fbxFile" accept=".fbx">
    <button onclick="convertSingle()">Convert</button>
    
    <h2>Directory Conversion</h2>
    <input type="text" id="directoryPath" placeholder="/textures/3d models/phoenix/">
    <button onclick="convertDirectory()">Convert All FBX in Directory</button>
    
    <h2>Batch Conversion</h2>
    <button onclick="convertPhoenix()">Convert Phoenix Model</button>
    <button onclick="convertWeapons()">Convert All Weapons</button>
    <button onclick="convertMonsters()">Convert All Monsters</button>
    
    <div id="progress"></div>
    <div id="results"></div>
  </div>
  
  <script>
    // Integration with Node.js backend API
    // Or client-side conversion if using WebAssembly version
  </script>
</body>
</html>
```

### **Phase 3: Backend API (Optional)**

#### **3.1 Node.js API Endpoint**
**File:** `api/dev/convert-fbx-to-gltf.php` or Node.js server endpoint

```javascript
// If using Node.js backend
app.post('/api/dev/convert-fbx', async (req, res) => {
  const { inputPath, outputPath, options } = req.body;
  
  try {
    const converter = new FBX2GLTFConverter();
    const result = await converter.convertFile(inputPath, outputPath);
    res.json({ success: true, outputPath: result });
  } catch (error) {
    res.status(500).json({ success: false, error: error.message });
  }
});
```

---

## 🎯 IMPLEMENTATION STEPS

### **STEP 1: Setup FBX2glTF Tool**
- [ ] Download or build FBX2glTF executable
- [ ] Place in `three.js/tools/fbx2gltf/` directory
- [ ] Test command-line conversion manually
- [ ] Verify output GLTF files work in Three.js

### **STEP 2: Create Conversion Script**
- [ ] Create `three.js/tools/fbx2gltf/convert-fbx-to-gltf.js`
- [ ] Implement single file conversion
- [ ] Implement batch conversion
- [ ] Add error handling and logging
- [ ] Test with sample FBX files

### **STEP 3: Test Phoenix Model Conversion**
- [ ] Convert Phoenix main model FBX to GLTF
- [ ] Convert Phoenix animation FBX files to GLTF
- [ ] Update `phoenix.js` to use GLTF loader instead of FBX
- [ ] Test scaling, animations, and textures
- [ ] Compare results with FBX version

### **STEP 4: Dev Interface Integration**
- [ ] Create dev tool HTML page
- [ ] Add to God Mode menu (optional)
- [ ] Implement file selection UI
- [ ] Implement batch conversion UI
- [ ] Add progress indicators
- [ ] Add conversion results display

### **STEP 5: Batch Conversion**
- [ ] Convert all weapon FBX files
- [ ] Convert all monster FBX files
- [ ] Convert Phoenix model and animations
- [ ] Update model paths in code to use GLTF
- [ ] Test all converted models

### **STEP 6: Documentation**
- [ ] Document conversion process
- [ ] Document tool usage
- [ ] Update model loading code documentation
- [ ] Create troubleshooting guide

---

## 📋 CONVERSION PRIORITIES

### **High Priority (Fix Current Issues):**
1. **Phoenix Model** - Main model and all 29 animations
   - **Reason:** Currently having scaling, animation, and texture issues
   - **Expected Benefit:** Better animation support, proper scaling, texture loading

### **Medium Priority (Performance):**
2. **Weapon Models** - All Fire Weapons 1 FBX files
   - **Reason:** Smaller file sizes, better web performance
   - **Expected Benefit:** Faster loading, better material support

3. **Monster Models** - All Monster 1 FBX files
   - **Reason:** Some already have GLTF, standardize format
   - **Expected Benefit:** Consistent format, better performance

### **Low Priority (Future):**
4. **Other Weapon Packs** - Sci-Fi, Old School, Survival
   - **Reason:** Not currently in use
   - **Expected Benefit:** Ready for future use

---

## 🔧 TECHNICAL CONSIDERATIONS

### **Animation Conversion:**
- FBX2glTF uses "baking" method for animations
- May create larger files for complex animations
- Phoenix animations should convert correctly
- Test animation playback after conversion

### **Material Conversion:**
- FBX materials convert to PBR metallic-roughness
- Textures should be preserved
- May need material adjustments after conversion
- Test material appearance after conversion

### **File Structure:**
- GLTF files can be `.gltf` (JSON + external files) or `.glb` (binary)
- `.glb` is preferred for single-file distribution
- Textures may need to be copied to correct paths
- Update model paths in code after conversion

### **Code Updates Required:**
- Replace `FBXLoader` with `GLTFLoader` for converted models
- Update model paths in code
- Test all converted models
- Update documentation

---

## 🚀 QUICK START GUIDE

### **For Phoenix Model (Priority Fix):**

1. **Install FBX2glTF:**
   ```bash
   cd three.js
   # Download pre-built binary or use npm package
   ```

2. **Convert Phoenix Model:**
   ```bash
   node tools/fbx2gltf/convert-fbx-to-gltf.js \
     --input "public/textures/3d models/phoenix/Phoenix.fbx/Base mesh/Base Mesh.fbx" \
     --output "public/textures/3d models/phoenix/Phoenix.glb"
   ```

3. **Convert Phoenix Animations:**
   ```bash
   node tools/fbx2gltf/convert-fbx-to-gltf.js \
     --batch "public/textures/3d models/phoenix/Phoenix_Animations/Animations_FBX/"
   ```

4. **Update phoenix.js:**
   ```javascript
   // Replace FBXLoader with GLTFLoader
   import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
   
   // Update loadModel call
   const loader = new GLTFLoader();
   const modelData = await loader.loadAsync(modelPath);
   ```

5. **Test:**
   - Load Phoenix model in Level 6
   - Verify scaling is correct
   - Verify animations play
   - Verify textures load

---

## 📚 RESOURCES

- **FBX2glTF Repository:** https://github.com/facebookincubator/FBX2glTF
- **GLTF Specification:** https://www.khronos.org/gltf/
- **Three.js GLTFLoader:** https://threejs.org/docs/#examples/en/loaders/GLTFLoader
- **Draco Compression:** https://github.com/google/draco

---

## ⚠️ POTENTIAL CHALLENGES

1. **FBX SDK Dependency:** May require FBX SDK installation
2. **Windows Build:** Windows build instructions may be outdated
3. **Animation Baking:** Large animation files may result
4. **Material Conversion:** May need manual material adjustments
5. **Texture Paths:** Textures may need path updates after conversion

---

## 🎯 SUCCESS CRITERIA

- ✅ Phoenix model converts successfully
- ✅ Phoenix animations convert and play correctly
- ✅ Model scaling is correct (no more "huge" Phoenix)
- ✅ Textures load correctly
- ✅ Animations play smoothly
- ✅ File sizes are smaller than FBX
- ✅ Dev tool is accessible and functional
- ✅ Batch conversion works for all models

---

**Last Updated:** December 8, 2025  
**Status:** 📋 **PLANNING PHASE**  
**Next:** Begin Phase 1 - Setup FBX2glTF Tool

