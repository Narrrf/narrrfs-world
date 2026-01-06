# 🧊 Three.js Production Asset Upload Gaps (2026-01-04)

**Purpose:** Record which three.js assets are **NOT** in git (removed during the 3.6GB cleanup) and must be uploaded manually to Render so games stay fully online.  
**Related:** `FIX_LARGE_COMMIT_SAFE.ps1`, `SAFE_3.6GB_COMMIT_FIX.md`, `GIT_CLEANUP_VERIFICATION_REPORT.md`  
**Rules linked:** `11_THREE_JS_RULE.md` §13, `10_FILE_PATH_LOCAL_VS_PRODUCTION_RULE.md`, `20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md`

---

## 🚨 Where assets must exist on Render
- `/var/www/html/public/three.js/public/textures/3d models/`
- `/var/www/html/public/three.js/public/sounds/`
- `/var/www/html/public/three.js/public/audio/`
- (If needed) `/var/www/html/three.js/` for runtime three.js assets not shipped via git

**Local source to upload from:**  
`C:\xampp-server\htdocs\narrrfs-world\public\three.js\public\` and `C:\xampp-server\htdocs\narrrfs-world\three.js\`

**Transfer method:** Render shell/SFTP/rsync. Do **not** add these back to git (keeps pushes small).

---

## 📦 Key models/textures that are NOT in git (must be on Render)
- Chest models & textures: `public/three.js/public/textures/3d models/chest1/` and `chest2/` (GLB + all TGA maps)
- Survival Pack models/textures (incl. OBJ/FBX and previews)
- Phoenix2 dragon/Eye variant textures (Black/Blue/Brown/Eye/Gold/Green/Red/White)
- Decorative GLBs: `secret-door.glb`, `tree-with-arms.glb`, `tree dead lians.glb`
- Any other assets under `public/three.js/public/textures/3d models/` that were removed from tracking during cleanup
- All SFX/BGM under `public/three.js/public/sounds/` and `public/three.js/public/audio/`

---

## ✅ Deployment checklist (per release)
1) **Pull latest code** (renders fast; assets are ignored).  
2) **Upload assets** to Render paths listed above.  
3) **Verify on Render**: `ls /var/www/html/public/three.js/public/textures/3d models/` (spot-check key GLBs/textures), `ls /var/www/html/public/three.js/public/sounds/`, `ls /var/www/html/public/three.js/public/audio/`.  
4) **Smoke test games**: ensure models/SFX load (no 404s).  
5) **Keep .gitignore intact**; never commit assets.

---

## 🔗 Technical documentation cross-check
- `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` (asset lists per level)
- `12.0/RULES/11_THREE_JS_RULE.md` §13 (Production Asset Upload Checklist)
- `12.0/RULES/10_FILE_PATH_LOCAL_VS_PRODUCTION_RULE.md` (correct local vs production paths)

---

## 🧭 Notes
- Current git repo size includes historical large files (acceptable); new commits stay small.  
- If an asset 404s in production, re-upload from local paths above—do **not** commit.  
- Keep this note for release ops until a permanent asset pipeline is in place.

