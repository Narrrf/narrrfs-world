# 🚀 SEASON 7 RESET ACTION PLAN - COMPLETE EXECUTION GUIDE

**Date:** January 2, 2026  
**Status:** 🚀 **READY FOR EXECUTION**  
**Objective:** Reset live database and redesign frontend pages for Season 7 launch

---

## 📋 **EXECUTION OVERVIEW**

### **Phase 1: Database Reset (Production)**
1. Pre-reset verification
2. Database backup
3. Archive historical stats (Season 6)
4. Execute database reset
5. Create Season 7
6. Post-reset verification

### **Phase 2: API Updates (6 Files)**
1. Update mission status API
2. Update score saving API
3. Update season stats API
4. Update current season settings API
5. Update all games stats API
6. Update admin interface

### **Phase 3: Frontend Redesign (2 Files)**
1. Redesign `index.html` - Season 6 frozen, Season 7 active
2. Redesign `profile.html` - Season 6 frozen, Season 7 active

### **Phase 4: Testing & Deployment**
1. Test all changes locally
2. Clear browser cache
3. Deploy to production
4. Final verification

---

## 🗄️ **PHASE 1: DATABASE RESET (PRODUCTION)**

### **✅ STEP 1: PRE-RESET VERIFICATION**

**Connect to Production Server (Render Shell):**
```bash
# Access Render shell
# Navigate to database directory
cd /var/www/html/db
```

**Verify Current Season Status:**
```bash
sqlite3 narrrf_world.sqlite "SELECT season_id, season_name, start_date, end_date, is_active FROM tbl_seasons WHERE is_active = 1;"
```

**Expected Output:**
```
8|Season 6|2025-11-30 23:01:45|2025-12-30 23:01:45|1
```

**Count Existing Data for Verification:**
```bash
# 3 Main Games (to be reset)
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris' AND season = 'Season 6';"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake' AND season = 'Season 6';"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders' AND season = 'Season 6';"

# Preserved Data (NEVER reset)
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_rumble_participants;"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;"
sqlite3 narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"
```

**Document Pre-Reset State:**
```bash
echo "=== PRE-RESET VERIFICATION - $(date) ===" >> /data/season_reset_log.txt
echo "Season 6 data counts documented" >> /data/season_reset_log.txt
```

---

### **✅ STEP 2: DATABASE BACKUP (CRITICAL)**

**Create Backup:**
```bash
# Backup to /data directory (survives deployments)
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world_backup_$(date +%Y%m%d_%H%M%S).sqlite

# Verify backup created
ls -lh /data/narrrf_world_backup_*.sqlite

# Document backup
echo "Database backup created: $(date)" >> /data/season_reset_log.txt
```

---

### **✅ STEP 3: ARCHIVE HISTORICAL STATS (CRITICAL - MANDATORY)**

**🚨 MOST CRITICAL STEP - Archive Season 6 data BEFORE deletion!**

**Archive Season 6 Data:**
```bash
curl https://narrrfs.world/api/admin/archive-season-stats.php
```

**Expected Response:**
```json
{
  "success": true,
  "season_archived": "Season 6",
  "games_archived": {
    "tetris": 104,
    "snake": 279,
    "space_invaders": 167
  },
  "cheese_users_archived": X
}
```

**Verify Archival Worked (MANDATORY):**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season, game, COUNT(*) FROM tbl_historical_stats WHERE season = 'Season 6' GROUP BY season, game;"
```

**Expected Output:**
```
Season 6|snake|279
Season 6|space_invaders|167
Season 6|tetris|104
```

**Document Archival:**
```bash
echo "Historical stats archived for Season 6: $(date)" >> /data/season_reset_log.txt
```

**🚨 IF ARCHIVAL FAILS - DO NOT PROCEED WITH RESET!**

---

### **✅ STEP 4: DATABASE RESET COMMANDS**

**⚠️ CRITICAL: Season 6 ended exactly on January 1, 2026 at 00:01:00 AM**

**Execute Reset (Single Transaction):**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "
BEGIN TRANSACTION;

-- Reset 3 main games only (CRITICAL: Must delete ALL old data)
DELETE FROM tbl_tetris_scores WHERE game IN ('tetris', 'snake', 'space_invaders');
DELETE FROM tbl_user_season_achievements WHERE game IN ('tetris', 'snake', 'space_invaders');

-- Deactivate current season (Season 6 ended on 2026-01-01 00:01:00)
UPDATE tbl_seasons SET is_active = 0, end_date = '2026-01-01 00:01:00' WHERE season_name = 'Season 6';

-- Create new season (Season 7, 30-day duration, starting after Season 6 end)
INSERT INTO tbl_seasons (season_name, start_date, end_date, is_active) 
VALUES ('Season 7', '2026-01-02 00:00:00', datetime('2026-01-02 00:00:00', '+30 days'), 1);

COMMIT;
"
```

**CRITICAL VERIFICATION: Ensure old data is completely deleted**
```bash
echo "Verifying reset completion..." >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as tetris_count FROM tbl_tetris_scores WHERE game = 'tetris';" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as snake_count FROM tbl_tetris_scores WHERE game = 'snake';" >> /data/season_reset_log.txt
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) as space_invaders_count FROM tbl_tetris_scores WHERE game = 'space_invaders';" >> /data/season_reset_log.txt

echo "Database reset completed: $(date)" >> /data/season_reset_log.txt
```

**All three games MUST show 0 counts!**

---

### **✅ STEP 5: COPY DATABASE TO /DATA**

**Critical for next deployment:**
```bash
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
echo "Database copied to /data: $(date)" >> /data/season_reset_log.txt
```

---

### **✅ STEP 6: POST-RESET VERIFICATION**

**1. Verify New Season is Active:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"
```

**Expected Output:**
```
Season 7|2026-01-02 00:00:00|2026-02-01 00:00:00|1
```

**2. Verify 3 Main Games are Reset (MUST be 0):**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';" # Must be 0
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';" # Must be 0
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';" # Must be 0
```

**3. Verify Preserved Data (should match pre-reset counts):**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_cheese_clicks;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_race_participants;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_rumble_participants;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_achievements;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_snake_achievements;"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_space_invaders_achievements;"
```

---

## 🔧 **PHASE 2: API UPDATES (6 FILES)**

### **✅ FILE 1: `api/user/user-game-missions.php`**

**Changes Required:**
- Add current season query at start
- Replace ALL hardcoded "Season 6" filters with dynamic season detection
- Update 6 locations:
  - Tetris query (line ~204)
  - Snake query (line ~236)
  - Space Invaders query (line ~276)
  - Cheese Hunt query (line ~310)
  - Cheese Hunt fallback query (line ~349)
  - Discord Race query (line ~411)

**Pattern to Use:**
```php
// Get current season dynamically
$seasonStmt = $pdo->prepare("SELECT season_name FROM tbl_seasons WHERE is_active = 1 ORDER BY season_id DESC LIMIT 1");
$seasonStmt->execute();
$currentSeason = $seasonStmt->fetchColumn() ?: 'Season 7'; // Fallback to Season 7

// Use $currentSeason in all queries instead of hardcoded 'Season 6'
```

---

### **✅ FILE 2: `api/dev/save-score.php`**

**Changes Required:**
- Update fallback: `'Season 6'` → `'Season 7'` (line ~83)

**Pattern:**
```php
$season = $seasonSettings['season_name'] ?? 'Season 7'; // Updated fallback
```

---

### **✅ FILE 3: `api/admin/get-season-stats.php`**

**Changes Required:**
- Update fallback: `'Season 6'` → `'Season 7'` (line ~45)
- Add season name mapping for dropdown values

**Pattern:**
```php
$current_season = $current_season_result['season_name'] ?? 'Season 7'; // Updated fallback
```

---

### **✅ FILE 4: `api/admin/get-current-season-settings.php`** ⚠️ **CRITICAL**

**Changes Required:**
- Update fallback: `'Season 6'` → `'Season 7'` (line ~20)

**Why Critical:** Admin interface uses this API on page load, causing it to display wrong season

**Pattern:**
```php
$currentSeason = $seasonResult['season_name'] ?? 'Season 7'; // Updated fallback
```

---

### **✅ FILE 5: `api/admin/get-all-games-stats.php`** ⚠️ **CRITICAL**

**Changes Required:**
- Update fallback: `'Season 6'` → `'Season 7'` (line ~35)
- Remove fallback logic for Tetris that shows all-time data when season has 0 scores
- Remove season filtering from Cheese Hunt and Discord Race (they show all-time data)

**Pattern:**
```php
$currentSeason = $seasonResult['season_name'] ?? 'Season 7'; // Updated fallback
```

---

### **✅ FILE 6: `public/admin-interface.html`**

**Changes Required:**
- Add "Season 7" option to all 3 season dropdowns:
  - `seasonSelector` (line ~3006)
  - `seasonSwitchSelector` (line ~3857)
  - `viewSeasonSelector` (line ~3871)
- Update hardcoded displays:
  - `currentSeasonDisplay`: "Season 6" → "Season 7" (line ~2839)
  - `overviewSeasonName`: "Season 6" → "Season 7" (line ~2900)
- Update JavaScript fallbacks:
  - `updateSeasonDisplay()` fallback: "Season 6" → "Season 7" (line ~25289)
  - `overviewSeasonTimeLeft`: "Season 6 Active" → "Season 7 Active" (line ~25307)

---

## 🎨 **PHASE 3: FRONTEND REDESIGN (2 FILES)**

### **✅ FILE 7: `public/index.html`**

**Design Concept:**
- **Season 6:** Mark as "FROZEN" (blue theme, ⏸️ icon)
- **Season 7:** Show as "LOADING" or "ACTIVE" (green theme, 🎮 icon)

**Locations to Update (8 locations):**

1. **Meta Description (line ~11):**
   - Change: "Season 6 Running" → "Season 7 Loading"
   - Change: "Season 6" → "Season 7"

2. **Title (line ~41):**
   - Change: "Season 6 Running!" → "Season 7 Loading!"

3. **OG Tags (lines ~42-48):**
   - Change: "Season 6 Running" → "Season 7 Loading"
   - Change: "Season 6 is Live" → "Season 7 Loading"

4. **Top Banner (line ~1188):**
   - Change: "SEASON 6 IS LIVE!" → "SEASON 6 FROZEN ⏸️ • SEASON 7 LOADING 🎮"
   - Theme: Blue (frozen) + Green (loading)

5. **CTA Section (line ~1301):**
   - Change: "SEASON 6 IS LIVE!" → "SEASON 6 FROZEN ⏸️ • SEASON 7 LOADING 🎮"
   - Theme: Blue (frozen) + Green (loading)

6. **Features Banner (line ~1364):**
   - Change: "SEASON 6 IS LIVE!" → "SEASON 6 FROZEN ⏸️ • SEASON 7 LOADING 🎮"
   - Add: "Season 6 leaderboards frozen • Season 7 starting soon!"

7. **Game Descriptions (line ~1435):**
   - Change: "Season 6 is Live!" → "Season 7 Loading!"

8. **Roadmap Section (line ~2045):**
   - Update: "Season 6 Freeze Coming Soon" → "Season 6 FROZEN ⏸️"
   - Update: "Season 7 Preview" → "Season 7 LOADING 🎮"

**Visual Theme:**
- **Season 6 (Frozen):** Blue gradient (`from-blue-500 via-indigo-500 to-purple-500`)
- **Season 7 (Loading):** Green gradient (`from-green-500 via-emerald-500 to-teal-500`)
- **Icons:** ⏸️ (frozen) and 🎮 (loading)

---

### **✅ FILE 8: `public/profile.html`**

**Design Concept:**
- **Season 6:** Mark as "FROZEN" (blue theme, ⏸️ icon)
- **Season 7:** Show as "LOADING" or "ACTIVE" (green theme, 🎮 icon)
- **Dynamic Status:** Use leaderboard API `is_frozen` flag to update dynamically

**Locations to Update (12 locations):**

1. **Page Title (line ~13):**
   - Change: "Season 6 Running!" → "Season 7 Loading!"

2. **Top Banner (line ~500):**
   - Change: "Season 6 Running!" → "Season 6 FROZEN ⏸️ • Season 7 LOADING 🎮"

3. **Season Status Main (line ~560):**
   - Change: "SEASON 6 IS LIVE!" → "SEASON 6 FROZEN ⏸️"
   - Theme: Blue (frozen)

4. **Season Status Subtitle (line ~563):**
   - Change: "SEASON 6 RUNNING!" → "SEASON 7 LOADING 🎮"
   - Theme: Green (loading)

5. **Season Status Details (line ~566):**
   - Change: "Season 6 Running" → "Season 6 FROZEN ⏸️ • Season 7 LOADING 🎮"

6. **Mint Section (line ~722):**
   - Change: "Season 6 Running!" → "Season 7 Loading!"

7. **Store Banner (line ~742):**
   - Change: "Season 6 Running!" → "Season 7 Loading!"

8. **Store Features (line ~750):**
   - Change: "Season 6 Running Features" → "Season 7 Loading Features"
   - Change: "Season 6 is Live!" → "Season 7 Loading!"

9. **Main Season Title (line ~761):**
   - Change: "SEASON 6 IS LIVE!" → "SEASON 6 FROZEN ⏸️ • SEASON 7 LOADING 🎮"

10. **Main Season Subtitle (line ~762):**
    - Change: "Season 6 Running" → "Season 6 FROZEN ⏸️ • Season 7 LOADING 🎮"

11. **Main Season Details (line ~763):**
    - Change: "Season 6 Active" → "Season 6 FROZEN ⏸️ • Season 7 LOADING 🎮"

12. **Game Cards (line ~1544):**
    - Change: "Season 6 Starting Soon!" → "Season 7 Loading!"

**Dynamic Status Updates:**
- Use `loadLeaderboard()` function to check `is_frozen` flag
- Update all banners dynamically based on API response
- Blue theme when frozen, Green theme when active

**JavaScript Pattern:**
```javascript
// Load leaderboard to check season status
async function loadLeaderboard() {
  try {
    const response = await fetch(`${API_BASE_URL}/api/dev/get-leaderboard.php`);
    const data = await response.json();
    
    if (data.success) {
      const isFrozen = data.is_frozen;
      const displaySeason = data.display_season;
      
      // Update all season status elements
      updateSeasonStatus(isFrozen, displaySeason);
    }
  } catch (error) {
    console.error('Error loading leaderboard:', error);
  }
}

function updateSeasonStatus(isFrozen, season) {
  // Update all banners based on frozen status
  if (isFrozen) {
    // Season 6 frozen, Season 7 loading
    document.getElementById('season-status-main').textContent = '⏸️ SEASON 6 FROZEN';
    document.getElementById('season-status-main').className = 'text-blue-300 text-lg font-bold animate-pulse';
    // ... update all other elements
  } else {
    // Season 7 active
    document.getElementById('season-status-main').textContent = '🎮 SEASON 7 IS LIVE!';
    document.getElementById('season-status-main').className = 'text-green-300 text-lg font-bold animate-bounce';
    // ... update all other elements
  }
}
```

---

## 🧪 **PHASE 4: TESTING & DEPLOYMENT**

### **✅ STEP 1: TEST LOCALLY**

**1. Test API Updates:**
```bash
# Test mission status API
curl http://localhost/api/user/user-game-missions.php

# Test leaderboard API
curl http://localhost/api/dev/get-leaderboard.php

# Verify Season 7 appears in responses
```

**2. Test Frontend Pages:**
- Open `http://localhost/public/index.html`
- Open `http://localhost/public/profile.html`
- Verify Season 6 shows as frozen (blue theme)
- Verify Season 7 shows as loading (green theme)

**3. Clear Browser Cache:**
```bash
# Method 1: Hard Refresh
CTRL + SHIFT + R (or CTRL + F5)

# Method 2: Clear All Cache
F12 → Application tab → Clear Storage → Clear site data
```

---

### **✅ STEP 2: DEPLOY TO PRODUCTION**

**Git Workflow:**
```bash
# 1. Stage all changes
git add .

# 2. Commit with descriptive message
git commit -m "Season 7 Launch: Complete Reset & Frontend Redesign

- Database reset completed for Season 7
- Season 6 marked as frozen (ended 2026-01-01 00:01:00)
- Season 7 created (start 2026-01-02 00:00:00)
- Updated 6 API files with Season 7 references
- Redesigned index.html: Season 6 frozen, Season 7 loading
- Redesigned profile.html: Season 6 frozen, Season 7 loading
- All leaderboard data preserved in historical stats
- Ready for live Season 7 launch"

# 3. Push to render-deploy branch
git push origin render-deploy

# 4. Document deployment
echo "Code deployed to production: $(date)" >> /data/season_reset_log.txt
```

---

### **✅ STEP 3: FINAL VERIFICATION**

**1. Verify Production Database:**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT * FROM tbl_seasons WHERE is_active = 1;"
# Should show: Season 7|2026-01-02 00:00:00|2026-02-01 00:00:00|1
```

**2. Verify Frontend Pages:**
- Visit `https://narrrfs.world/`
- Visit `https://narrrfs.world/profile.html`
- Verify Season 6 shows as frozen (blue theme)
- Verify Season 7 shows as loading (green theme)

**3. Verify API Responses:**
- Check leaderboard API returns Season 7 data
- Check mission status API returns Season 7 data
- Verify no "Season 6" hardcoded references remain

---

## 📋 **CHECKLIST SUMMARY**

### **Database Reset:**
- [ ] Pre-reset verification completed
- [ ] Database backup created
- [ ] Historical stats archived (Season 6)
- [ ] Database reset executed
- [ ] Season 7 created
- [ ] Post-reset verification passed

### **API Updates:**
- [ ] `api/user/user-game-missions.php` updated
- [ ] `api/dev/save-score.php` updated
- [ ] `api/admin/get-season-stats.php` updated
- [ ] `api/admin/get-current-season-settings.php` updated
- [ ] `api/admin/get-all-games-stats.php` updated
- [ ] `public/admin-interface.html` updated

### **Frontend Redesign:**
- [ ] `public/index.html` redesigned (8 locations)
- [ ] `public/profile.html` redesigned (12 locations)
- [ ] Dynamic status updates implemented
- [ ] Visual themes applied (blue frozen, green loading)

### **Testing & Deployment:**
- [ ] Local testing completed
- [ ] Browser cache cleared
- [ ] Code committed and pushed
- [ ] Production deployment verified
- [ ] Final verification passed

---

## 🚨 **CRITICAL REMINDERS**

1. **ALWAYS backup database before reset**
2. **ALWAYS archive historical stats before deletion**
3. **ALWAYS verify all 3 games reset to 0**
4. **ALWAYS clear browser cache after updates**
5. **ALWAYS test locally before deploying**
6. **ALWAYS verify production after deployment**

---

**Status:** 🚀 **READY FOR EXECUTION**

**Next Step:** Begin Phase 1 - Database Reset (Production)

