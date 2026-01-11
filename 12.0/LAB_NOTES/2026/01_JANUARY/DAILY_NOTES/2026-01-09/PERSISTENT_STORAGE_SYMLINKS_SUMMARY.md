# 📁 PERSISTENT STORAGE & SYMLINKS SUMMARY

**Created:** January 9, 2026  
**Purpose:** Complete list of all persistent directories and their symlinks on Render

---

## 📊 **COMPLETE LIST OF PERSISTENT STORAGE**

Based on `scripts/render-startup.sh`, here are ALL directories with persistent storage and symlinks:

---

### **1. PARTNER IMAGES** 🤝

- **Persistent Storage:** `/data/img/partners/`
- **Symlink:** `/var/www/html/img/partners/` → `/data/img/partners/`
- **Files:** Partner logos, banners, images
- **Created by:** `scripts/render-startup.sh` (STEP 2)

---

### **2. THREE.JS GAME ASSETS** 🎮

**Base Path:** `/data/public/three.js/public/`  
**Symlink Base:** `/var/www/html/public/three.js/public/`

#### **Textures:**
- **3D Models:** `/data/public/three.js/public/textures/3d models/` → `/var/www/html/public/three.js/public/textures/3d models/`
- **Grass:** `/data/public/three.js/public/textures/grass/` → `/var/www/html/public/three.js/public/textures/grass/`
- **Backgrounds:** `/data/public/three.js/public/textures/backgrounds/` → `/var/www/html/public/three.js/public/textures/backgrounds/`
- **Blocks:** `/data/public/three.js/public/textures/blocks/` → `/var/www/html/public/three.js/public/textures/blocks/`
- **Plants:** `/data/public/three.js/public/textures/plants/` → `/var/www/html/public/three.js/public/textures/plants/`

#### **Audio:**
- **Sounds:** `/data/public/three.js/public/sounds/` → `/var/www/html/public/three.js/public/sounds/`
- **Audio:** `/data/public/three.js/public/audio/` → `/var/www/html/public/three.js/public/audio/`

#### **Models & Videos:**
- **Models:** `/data/public/three.js/public/models/` → `/var/www/html/public/three.js/public/models/`
- **Videos:** `/data/public/three.js/public/videos/` → `/var/www/html/public/three.js/public/videos/`

**Created by:** `scripts/render-startup.sh` (STEP 3)

---

### **3. GLYPH GAME ASSETS** 🧀

- **Persistent Storage:** `/data/public/glyph/glyph3d/`
- **Symlink:** `/var/www/html/public/glyph/glyph3d/` → `/data/public/glyph/glyph3d/`
- **Files:** 36 GLB files (0-9, A-Z)
- **Created by:** `scripts/render-startup.sh` (STEP 3.5)

---

## ✅ **TOTAL PERSISTENT DIRECTORIES:**

1. **Partner Images** (1 directory)
2. **Three.js Assets** (9 directories: 5 textures + 2 audio + 2 other)
3. **Glyph Assets** (1 directory)

**Total:** **11 persistent directories with symlinks**

---

## 🔍 **VERIFICATION COMMANDS FOR RENDER**

Run these commands on Render to verify all symlinks:

```bash
# Quick verification - check all symlinks exist
echo "=== SYMLINK VERIFICATION ==="
echo "Partner Images: $(ls -la /var/www/html/img/partners 2>/dev/null | head -1)"
echo "3D Models: $(ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep '3d models')"
echo "Grass: $(ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep grass)"
echo "Backgrounds: $(ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep backgrounds)"
echo "Blocks: $(ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep blocks)"
echo "Plants: $(ls -la /var/www/html/public/three.js/public/textures/ 2>/dev/null | grep plants)"
echo "Sounds: $(ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep '^l.*sounds')"
echo "Audio: $(ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep '^l.*audio')"
echo "Models: $(ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep '^l.*models')"
echo "Videos: $(ls -la /var/www/html/public/three.js/public/ 2>/dev/null | grep '^l.*videos')"
echo "Glyph3d: $(ls -la /var/www/html/public/glyph/glyph3d 2>/dev/null | head -1)"

# Count files in persistent storage
echo ""
echo "=== FILE COUNTS IN PERSISTENT STORAGE ==="
echo "Partner Images: $(find /data/img/partners/ -type f 2>/dev/null | wc -l) files"
echo "3D Models: $(find /data/public/three.js/public/textures/3d\ models/ -type f 2>/dev/null | wc -l) files"
echo "Grass: $(find /data/public/three.js/public/textures/grass/ -type f 2>/dev/null | wc -l) files"
echo "Backgrounds: $(find /data/public/three.js/public/textures/backgrounds/ -type f 2>/dev/null | wc -l) files"
echo "Blocks: $(find /data/public/three.js/public/textures/blocks/ -type f 2>/dev/null | wc -l) files"
echo "Plants: $(find /data/public/three.js/public/textures/plants/ -type f 2>/dev/null | wc -l) files"
echo "Sounds: $(find /data/public/three.js/public/sounds/ -type f 2>/dev/null | wc -l) files"
echo "Audio: $(find /data/public/three.js/public/audio/ -type f 2>/dev/null | wc -l) files"
echo "Models: $(find /data/public/three.js/public/models/ -type f 2>/dev/null | wc -l) files"
echo "Videos: $(find /data/public/three.js/public/videos/ -type f 2>/dev/null | wc -l) files"
echo "Glyph3d: $(find /data/public/glyph/glyph3d/ -type f 2>/dev/null | wc -l) files"
```

**Or use the verification script:**
```bash
bash /var/www/html/12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/VERIFY_ALL_SYMLINKS.sh
```

---

## 📋 **SUMMARY TABLE**

| Directory | Persistent Path | Symlink Path | Files Count |
|-----------|----------------|--------------|-------------|
| **Partner Images** | `/data/img/partners/` | `/var/www/html/img/partners/` | ? |
| **3D Models** | `/data/public/three.js/public/textures/3d models/` | `/var/www/html/public/three.js/public/textures/3d models/` | 1,300+ |
| **Grass** | `/data/public/three.js/public/textures/grass/` | `/var/www/html/public/three.js/public/textures/grass/` | ? |
| **Backgrounds** | `/data/public/three.js/public/textures/backgrounds/` | `/var/www/html/public/three.js/public/textures/backgrounds/` | ? |
| **Blocks** | `/data/public/three.js/public/textures/blocks/` | `/var/www/html/public/three.js/public/textures/blocks/` | ? |
| **Plants** | `/data/public/three.js/public/textures/plants/` | `/var/www/html/public/three.js/public/textures/plants/` | ? |
| **Sounds** | `/data/public/three.js/public/sounds/` | `/var/www/html/public/three.js/public/sounds/` | ? |
| **Audio** | `/data/public/three.js/public/audio/` | `/var/www/html/public/three.js/public/audio/` | ? |
| **Models** | `/data/public/three.js/public/models/` | `/var/www/html/public/three.js/public/models/` | ? |
| **Videos** | `/data/public/three.js/public/videos/` | `/var/www/html/public/three.js/public/videos/` | ? |
| **Glyph3d** | `/data/public/glyph/glyph3d/` | `/var/www/html/public/glyph/glyph3d/` | **36** |

---

## 🚀 **CREATED BY**

All symlinks are automatically created by `scripts/render-startup.sh` on every deployment:
- **Partner Images:** STEP 2
- **Three.js Assets:** STEP 3 (9 directories)
- **Glyph Assets:** STEP 3.5 (1 directory)

**Script Location:** `scripts/render-startup.sh`  
**Last Updated:** January 9, 2026 (Added glyph support)

---

**Status:** ✅ **ALL SYMLINKS CREATED AUTOMATICALLY ON DEPLOYMENT**
