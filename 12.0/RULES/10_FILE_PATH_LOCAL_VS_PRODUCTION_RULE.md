# 🚨 CRITICAL FILE PATH RULE - LOCAL vs PRODUCTION

**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**CREATED:** October 29, 2025  
**PURPOSE:** Prevent /public/ path errors on production  
**PRIORITY:** 🚨 **CRITICAL - PREVENTS 404 ERRORS**  

---

## 🎯 **THE MOST COMMON MISTAKE WE MAKE**

### **CORE PROBLEM:**
**Local development uses `/public/` subdirectory, Production does NOT!**

We test locally with `/public/` paths, then deploy to production and get **404 errors** because `/public/` doesn't exist on Render!

**This happens ALL THE TIME - this rule prevents it forever!**

---

## 📁 **FILE STRUCTURE DIFFERENCES**

### **LOCAL DEVELOPMENT (XAMPP):**
```
C:\xampp-server\htdocs\narrrfs-world\
├── public/
│   ├── img/
│   │   └── partners/        ← Images HERE
│   ├── profile.html
│   └── admin-interface.html
├── api/
└── db/
```

**Access Paths:**
- Files: `__DIR__ . '/../../public/img/partners/'`
- URLs: `http://localhost/public/profile.html`
- Database: `__DIR__ . '/../../db/narrrf_world.sqlite'`

---

### **PRODUCTION (RENDER):**
```
/var/www/html/
├── img/
│   └── partners/            ← Images HERE (NO public/ folder!)
├── profile.html
├── admin-interface.html
├── api/
└── db/
```

**Access Paths:**
- Files: `/var/www/html/img/partners/`
- URLs: `https://narrrfs.world/profile.html`
- Database: `/var/www/html/db/narrrf_world.sqlite`

---

## ✅ **CORRECT IMPLEMENTATION PATTERN**

### **MANDATORY ENVIRONMENT DETECTION:**

```php
<?php
// 🚨 CRITICAL: ALWAYS detect environment for file operations

// Detect production environment
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;

if ($isProduction) {
    // ✅ PRODUCTION: NO public/ subdirectory
    $imagePath = '/var/www/html/img/partners/' . $filename;
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
    $cssPath = '/var/www/html/css/styles.css';
} else {
    // ✅ LOCAL: Use public/ subdirectory
    $imagePath = __DIR__ . '/../../public/img/partners/' . $filename;
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
    $cssPath = __DIR__ . '/../../public/css/styles.css';
}

// ✅ ALWAYS log which path is being used
error_log("📁 File path: $imagePath (Production: " . ($isProduction ? 'YES' : 'NO') . ")");
```

---

## ❌ **WRONG PATTERNS (NEVER USE THESE)**

### **❌ PATTERN 1: Hardcoded public/ on Production**
```php
// ❌ WRONG: This works locally but fails on production!
$uploadPath = __DIR__ . '/../../public/img/partners/' . $filename;

// On Render, this tries to save to:
// /var/www/html/api/admin/../../public/img/partners/file.png
// Which resolves to: /var/www/html/public/img/partners/file.png
// But /public/ doesn't exist! → 404 error!
```

### **❌ PATTERN 2: No Environment Detection**
```php
// ❌ WRONG: Same path for both environments
$uploadPath = '/var/www/html/img/partners/' . $filename;

// This fails locally because local path is:
// C:\xampp-server\htdocs\narrrfs-world\public\img\partners\
```

### **❌ PATTERN 3: URL Paths Mixed with File Paths**
```php
// ❌ WRONG: Using URL path for file operations
$uploadPath = 'https://narrrfs.world/img/partners/' . $filename;
move_uploaded_file($tmpFile, $uploadPath); // FAILS!
```

---

## 🎯 **WHEN TO USE THIS RULE**

### **ALWAYS apply environment detection for:**

1. **📤 File Uploads:**
   - Partner logos and banners
   - Profile pictures
   - Game assets (images, sounds)
   - User-generated content

2. **📥 File Downloads:**
   - Database backups
   - Export files
   - Generated reports
   - Log files

3. **🗑️ File Deletions:**
   - Image cleanup
   - Temporary file removal
   - Old asset deletion

4. **📂 Directory Operations:**
   - Creating upload directories
   - Checking directory existence
   - Setting permissions

5. **🗄️ Database Connections:**
   - SQLite file path
   - Database backups
   - Migration scripts

---

## 📋 **IMPLEMENTATION CHECKLIST**

### **Before Writing File Operation Code:**

- [ ] **Identify environment** - Is this local or production?
- [ ] **Add detection code** - Use `$isProduction` check
- [ ] **Set correct paths** - Different for local vs production
- [ ] **Add logging** - Always log which path is used
- [ ] **Test locally** - Verify local path works
- [ ] **Test production** - Verify production path works

### **Code Review Checklist:**

- [ ] **No hardcoded `/public/` paths** in production code
- [ ] **Environment detection present** for all file operations
- [ ] **Logging added** to track path usage
- [ ] **Both paths tested** (local and production)
- [ ] **Error handling** for file operation failures

---

## 🚨 **HISTORICAL MISTAKES (NEVER REPEAT THESE)**

### **Mistake 1: Partner Portal Images (October 29, 2025)**

**What happened:**
- Code used `__DIR__ . '/../../public/img/partners/'` for both local and production
- On Render, this tried to save to `/var/www/html/public/img/partners/`
- Directory doesn't exist → Upload fails silently
- Database saves filename, but file doesn't exist → 404 errors

**Error messages:**
```
GET https://narrrfs.world/img/partners/1_logo_1761747386.PNG 404 (Not Found)
GET https://narrrfs.world/img/partners/1_banner_1761747387.PNG 404 (Not Found)
```

**Fix applied:**
```php
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
if ($isProduction) {
    $uploadPath = '/var/www/html/img/partners/' . $filename;
} else {
    $uploadPath = __DIR__ . '/../../public/img/partners/' . $filename;
}
```

**Files fixed:**
- `api/admin/partner-management.php` (upload_image and delete_image actions)

---

### **Mistake 2: Achievement API Database Path (Previous)**

**What happened:**
- Used `db/narrrf_world.sqlite` instead of relative path
- Failed to connect on both local and production

**Fix applied:**
```php
$dbPath = $isProduction 
    ? '/var/www/html/db/narrrf_world.sqlite' 
    : __DIR__ . '/../../db/narrrf_world.sqlite';
```

---

### **Mistake 3: API URL Patterns (Previous)**

**What happened:**
- Used `http://localhost/narrrfs-world/api/...` on local
- Should be `http://localhost/api/...`

**See:** `03_LOCALHOST_URL_RULE.md` for details

---

## 🔧 **QUICK REFERENCE CARD**

### **Copy This Template for All File Operations:**

```php
<?php
// 🚨 STEP 1: Detect environment
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;

// 🚨 STEP 2: Set paths based on environment
if ($isProduction) {
    // Production Render paths (NO public/)
    $uploadDir = '/var/www/html/img/[category]/';
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
} else {
    // Local XAMPP paths (WITH public/)
    $uploadDir = __DIR__ . '/../../public/img/[category]/';
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
}

// 🚨 STEP 3: Create directory if needed
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 🚨 STEP 4: Perform file operation
$filePath = $uploadDir . $filename;
move_uploaded_file($tmpFile, $filePath);

// 🚨 STEP 5: Log for debugging
error_log("📁 File saved to: $filePath (Production: " . ($isProduction ? 'YES' : 'NO') . ")");
?>
```

---

## 🎯 **ENFORCEMENT PROTOCOL**

### **Code Review Requirements:**

**Every file operation MUST have:**
1. ✅ Environment detection (`$isProduction` check)
2. ✅ Separate paths for local and production
3. ✅ Error logging with environment info
4. ✅ Directory creation with proper permissions
5. ✅ File existence checks before operations

**If ANY file operation lacks environment detection:**
- ❌ **Code review REJECTED**
- ❌ **Must add environment detection**
- ❌ **Must test on both environments**

---

## 📊 **SUCCESS METRICS**

### **Zero Tolerance Goals:**

- **0** file path errors on production deployments
- **0** 404 errors from wrong paths
- **100%** of file operations with environment detection
- **100%** success rate for image uploads on production

### **Quality Indicators:**

- ✅ All images load on first try after upload
- ✅ No "file not found" errors in logs
- ✅ Clean production deployments
- ✅ Consistent behavior across environments

---

## 🚀 **TESTING PROTOCOL**

### **Before Deploying ANY File Operation:**

**Local Testing:**
1. Upload test file locally
2. Verify file saved to `public/img/[category]/`
3. Verify file accessible at `http://localhost/img/[category]/file.png`
4. Verify database path correct

**Production Testing:**
1. Deploy code to Render
2. Upload test file via production interface
3. Verify file saved to `/var/www/html/img/[category]/`
4. Verify file accessible at `https://narrrfs.world/img/[category]/file.png`
5. Check Render logs for path confirmation

**Both Environments:**
- [ ] File upload works
- [ ] File displays on frontend
- [ ] File can be deleted
- [ ] No 404 errors
- [ ] No permission errors

---

## 🔮 **FUTURE PROOFING**

### **When Adding New File Operations:**

1. **Image Uploads:** Always use environment detection
2. **File Storage:** Always check local vs production
3. **Asset Management:** Always verify paths
4. **Database Operations:** Always use correct DB path

### **Template Files to Reference:**

- ✅ `api/admin/partner-management.php` - Correct implementation (after Oct 29, 2025 fix)
- ✅ `api/user/get-partners.php` - Correct database path detection
- ✅ Any API with `$isProduction` check

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**

- **Every file operation** MUST detect environment
- **Every upload** MUST use correct path
- **Every deployment** MUST be tested on production
- **Every mistake** MUST be documented here

### **CONSEQUENCES OF VIOLATION:**

- **404 Errors** - Files not found on production
- **Silent Failures** - Uploads appear to work but don't
- **User Frustration** - Features broken on live site
- **Debugging Time** - Hours wasted finding path issues
- **Lost Data** - Files saved to wrong location

### **THE ULTIMATE GOAL:**

**Ensure every file operation works flawlessly on both local development and production, with zero 404 errors and perfect path consistency!**

---

## 📝 **QUICK DECISION TREE**

```
Does your code perform file operations?
├─ YES → Does it run on both local AND production?
│         ├─ YES → ✅ ADD ENVIRONMENT DETECTION
│         └─ NO → Document why it's environment-specific
└─ NO → No path detection needed
```

**If unsure, ADD ENVIRONMENT DETECTION - it never hurts!**

---

**RULE CREATED:** October 29, 2025  
**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**PURPOSE:** Prevent /public/ path errors on production  
**SCOPE:** All file operations, all uploads, all paths  

**🚨 THIS RULE PREVENTS THE #1 DEPLOYMENT MISTAKE! 🚨**

---

## 📚 **RELATED RULES**

- **03_LOCALHOST_URL_RULE.md** - API URL patterns
- **01_MASTER_RULESET.md** - Now includes this rule
- **04_GAME_SCORING_SYSTEM_RULES.md** - Database path examples

**All rules are synchronized to prevent path mistakes!**

---

**🧀 NEVER MAKE THE /public/ PATH MISTAKE AGAIN! 🧀**

---

## 🌐 **JAVASCRIPT/FRONTEND PATH HANDLING (January 6, 2026)**

### **CRITICAL: Three.js Game Asset Paths**

**Context:** The three.js game HTML is located at `/public/three.js/3d-riddle-game.html`, which means relative paths resolve differently than expected in production.

### **Path Resolution Issue:**

**Problem:**
- HTML file location: `/public/three.js/3d-riddle-game.html`
- Relative path: `./public/textures/...` resolves to `/public/three.js/public/textures/...` (correct)
- BUT: Browser requests go to wrong URL: `https://narrrfs.world/three.js/public/...` (missing `/public/` prefix)

**Solution:**
- **ALWAYS use absolute paths** from web root: `/public/three.js/public/textures/...`
- Path normalization function automatically converts relative paths to absolute

### **JavaScript Path Pattern:**

```javascript
// ❌ WRONG: Relative paths (resolve incorrectly in production)
const modelPath = "./public/textures/3d models/chest2/Chest2.glb";
const audioPath = "./public/sounds/music/level1.mp3";

// ✅ CORRECT: Absolute paths from web root
const modelPath = "/public/three.js/public/textures/3d models/chest2/Chest2.glb";
const audioPath = "/public/three.js/public/sounds/music/level1.mp3";
```

### **Path Normalization Function:**

The `normalizeAssetPath()` function in `main.js` automatically converts relative paths to absolute:

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

### **Automatic Normalization:**

The `loadTexture()` and `loadModel()` functions automatically normalize paths:

```javascript
function loadTexture(path) {
  const normalizedPath = normalizeAssetPath(path);
  // ... load texture using normalizedPath
}

function loadModel(path) {
  const normalizedPath = normalizeAssetPath(path);
  // ... load model using normalizedPath
}
```

### **Rule for JavaScript Asset Paths:**

- **✅ ALWAYS use absolute paths** (`/public/three.js/public/...`) for all three.js game assets
- **✅ Normalization function provides backward compatibility** for relative paths
- **✅ New code should use absolute paths** - don't rely on normalization
- **✅ Test in production** - verify no 404 errors in browser console

### **Files Using This Pattern:**

- `config-system.js` - Audio and music paths
- `main.js` - Model, texture, and audio paths
- `grass-system.js` - Grass and cloud texture paths
- `chest-system.js` - Chest model paths
- `alien-spider.js` - Animation and texture paths
- `phoenix2.js` - Model and texture paths (if applicable)

### **Verification:**

After deployment:
- ✅ Check browser console for 404 errors
- ✅ Verify all assets load correctly
- ✅ Test game functionality (models, sounds, textures)
- ✅ No path-related errors in console

**Status:** ✅ **ACTIVE - CRITICAL FOR THREE.JS GAME** (January 6, 2026)  
**Related Rules:** `11_THREE_JS_RULE.md` §14, `18_3D_MODEL_RENDERING_RULE.md`

