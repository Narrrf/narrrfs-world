# ✅ POST-DEPLOYMENT VERIFICATION CHECKLIST - SEASON 7

**Date:** January 2, 2026  
**Status:** ⏳ **WAITING FOR RENDER RESTART**  
**Deployment:** ✅ **CODE PUSHED - VERIFICATION PENDING**

---

## 🎯 **VERIFICATION CHECKLIST (After Render Restarts)**

### **1. Database Status Verification**

**Check on Render Server:**
```bash
# Verify Season 7 is active
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT season_id, season_name, start_date, end_date, is_active FROM tbl_seasons WHERE is_active = 1;"
# Expected: Season 7, 2026-01-02 00:00:00, is_active = 1

# Verify all 3 games are reset
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris';"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake';"
sqlite3 /var/www/html/db/narrrf_world.sqlite "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders';"
# Expected: All should be 0
```

**✅ Checklist:**
- [ ] Season 7 is active in database
- [ ] Season 6 is deactivated (is_active = 0)
- [ ] All 3 games show 0 scores
- [ ] Preserved data intact (Cheese Hunt, Discord Race, achievements)

---

### **2. Frontend Pages Verification**

#### **A. index.html (Main Landing Page)**

**URL:** `https://narrrfs.world/index.html`

**Check:**
- [ ] Top banner shows "Season 6 FROZEN ⏸️ • Season 7 LOADING 🎮"
- [ ] Banner uses blue/purple gradient (frozen) and green (loading)
- [ ] Meta description updated to reflect Season 6 frozen + Season 7 loading
- [ ] CTA sections mention Season 7 loading
- [ ] Features banner shows correct season status
- [ ] Roadmap section updated
- [ ] Game descriptions mention Season 7

**✅ Expected:**
- Blue theme for "Season 6 FROZEN"
- Green theme for "Season 7 LOADING"
- No references to "Season 6 Running" or "Season 6 is Live"

---

#### **B. profile.html (User Profile Page)**

**URL:** `https://narrrfs.world/profile.html`

**Check:**
- [ ] Page title shows "Season 6 FROZEN ⏸️ • Season 7 LOADING 🎮"
- [ ] Top season banner shows frozen/loading status
- [ ] Main season banner updated
- [ ] Wallet & Traits banner updated
- [ ] Mint section status updated
- [ ] Store banner updated
- [ ] "Current Season Statistics" shows **0 for all 6 games**:
  - [ ] Tetris: 0 games, 0 DSPOINC
  - [ ] Snake: 0 games, 0 DSPOINC
  - [ ] Space Invaders: 0 games, 0 DSPOINC
  - [ ] Cheese Hunt: Shows all-time data (preserved)
  - [ ] Discord Race: Shows all-time data (preserved)
  - [ ] Cheese Rumble: Shows all-time data (preserved)
- [ ] Leaderboard at bottom shows **frozen Season 6** (blue theme)
- [ ] Leaderboard header says "Season 6 FROZEN ⏸️"
- [ ] Game cards show "Season 6 Boss Mode (Freezed)" or "Season 6 Leaderboard Freezed"
- [ ] Game cards show "Season 7 LOADING 🎮"

**✅ Expected:**
- All "Current Season Statistics" show 0 for Tetris, Snake, Space Invaders
- Leaderboard shows frozen Season 6 (from historical stats)
- All banners use blue theme (frozen) and green theme (loading)
- No old "Season 6 Starting Soon" or "Season 6 is Live" messages

---

#### **C. project-updates.html**

**URL:** `https://narrrfs.world/project-updates.html`

**Check:**
- [ ] Main description shows "⏸️ Season 6 FROZEN ⏸️ • 🎮 Season 7 LOADING 🎮"
- [ ] Season status card shows frozen/loading status
- [ ] Season status card uses blue theme (frozen)
- [ ] Footer mentions Season 7 loading

**✅ Expected:**
- Blue theme for frozen status
- Green theme for loading status
- No references to "Season 6 Running"

---

#### **D. get-roles.html**

**URL:** `https://narrrfs.world/get-roles.html`

**Check:**
- [ ] Main description shows "⏸️ Season 6 FROZEN ⏸️ • 🎮 Season 7 LOADING 🎮"
- [ ] Snake game card shows "⏸️ Season 6 leaderboards frozen"
- [ ] Space Invaders game card shows "⏸️ Season 6 leaderboards frozen"
- [ ] Features list shows frozen/loading status
- [ ] Current Season card shows frozen/loading status
- [ ] Current Season card uses blue theme (frozen)

**✅ Expected:**
- Blue theme for frozen status
- Green theme for loading status
- No references to "Season 6 leaderboards active"

---

### **3. API Endpoints Verification**

#### **A. Mission Status API**

**URL:** `https://narrrfs.world/api/user/user-game-missions.php`

**Test:**
```bash
curl -X POST https://narrrfs.world/api/user/user-game-missions.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": "YOUR_DISCORD_ID"}'
```

**Check Response:**
- [ ] `data.games.tetris.season_data` shows 0 games, 0 DSPOINC
- [ ] `data.games.snake.season_data` shows 0 games, 0 DSPOINC
- [ ] `data.games.space_invaders.season_data` shows 0 games, 0 DSPOINC
- [ ] `data.games.cheese_hunt.current_data` shows all-time data (preserved)
- [ ] `data.games.discord_race.race_data` shows all-time data (preserved)
- [ ] `data.games.cheese_rumble.rumble_data` shows all-time data (preserved)

**✅ Expected:**
- All 3 main games show 0 for Season 7
- Non-season games show all-time data

---

#### **B. Leaderboard API**

**URL:** `https://narrrfs.world/api/dev/get-leaderboard.php`

**Test:**
```bash
curl https://narrrfs.world/api/dev/get-leaderboard.php
```

**Check Response:**
- [ ] `is_frozen: true` (since Season 7 has 0 scores)
- [ ] `season: "Season 6"` (frozen season)
- [ ] `leaderboard` shows frozen Season 6 data from `tbl_historical_stats`
- [ ] All 3 games show top 10 players from Season 6

**✅ Expected:**
- `is_frozen: true` when Season 7 has < 3 total scores
- Leaderboard shows frozen Season 6 data
- Auto-switches to Season 7 when 3+ games are played

---

#### **C. Admin Interface APIs**

**URL:** `https://narrrfs.world/public/admin-interface.html`

**Check:**
- [ ] Admin interface loads correctly
- [ ] Season dropdown shows "Season 7" option
- [ ] Current season display shows "Season 7"
- [ ] Overview tab shows Season 7 statistics
- [ ] All 3 games show 0 scores in admin interface
- [ ] Game Management tabs show correct season data

**✅ Expected:**
- Admin interface displays Season 7 correctly
- All game statistics show 0 for new season
- Season dropdown includes Season 7

---

### **4. Leaderboard Auto-Switch Test**

**After 3 Games Are Played:**

**Test Scenario:**
1. Play 1 game of Tetris → Total = 1 → Should still show frozen Season 6
2. Play 1 game of Snake → Total = 2 → Should still show frozen Season 6
3. Play 1 game of Space Invaders → Total = 3 → **Should auto-switch to active Season 7**

**Check:**
- [ ] Leaderboard API `is_frozen: false` after 3 games
- [ ] Leaderboard shows active Season 7 data
- [ ] Frontend updates to green theme (active)
- [ ] All banners update to "Season 7 RUNNING 🎮"
- [ ] Leaderboard header updates to "Season 7 RUNNING 🎮"

**✅ Expected:**
- Auto-switch happens automatically when 3+ total scores exist
- No manual intervention needed
- Frontend updates dynamically

---

### **5. Browser Cache Check**

**Important:** After deployment, clear browser cache to see updates:

**Methods:**
1. **Hard Refresh:** `CTRL + SHIFT + R` (Windows) or `CMD + SHIFT + R` (Mac)
2. **Clear Cache:** F12 → Application → Clear Storage → Clear site data
3. **Incognito Mode:** Test in incognito/private window

**Check:**
- [ ] All pages show updated Season 7 content
- [ ] No old "Season 6 Running" messages
- [ ] All banners show correct frozen/loading status
- [ ] Leaderboard shows correct frozen Season 6 data

**✅ Expected:**
- Fresh content after cache clear
- No stale data from previous season

---

## 🚨 **COMMON ISSUES TO WATCH FOR**

### **Issue 1: "Current Season Statistics" Shows Old Data**

**Symptom:** Profile page shows old Season 6 scores instead of 0

**Possible Causes:**
- API not updated with Season 7 fallback
- Browser cache showing old data
- Database query not filtering correctly

**Fix:**
- Clear browser cache
- Verify API fallback is "Season 7"
- Check database query filters by current season

---

### **Issue 2: Leaderboard Shows Wrong Season**

**Symptom:** Leaderboard shows active Season 7 when it should be frozen

**Possible Causes:**
- API `is_frozen` logic not working
- Total score count incorrect
- Historical stats not loaded

**Fix:**
- Verify total score count across all 3 games
- Check `is_frozen` logic in API
- Verify historical stats exist for Season 6

---

### **Issue 3: Frontend Shows Old Messages**

**Symptom:** Pages still show "Season 6 Running" or "Season 6 is Live"

**Possible Causes:**
- Browser cache
- JavaScript not updated
- Hardcoded strings not replaced

**Fix:**
- Clear browser cache (CTRL + SHIFT + R)
- Verify all hardcoded strings updated
- Check JavaScript functions updated

---

## ✅ **VERIFICATION COMPLETE CHECKLIST**

**After Render Restarts:**

- [ ] Database status verified (Season 7 active, all games reset)
- [ ] index.html verified (frozen/loading status correct)
- [ ] profile.html verified (0 scores, frozen leaderboard)
- [ ] project-updates.html verified (frozen/loading status)
- [ ] get-roles.html verified (frozen/loading status)
- [ ] Mission Status API verified (0 scores for Season 7)
- [ ] Leaderboard API verified (frozen Season 6)
- [ ] Admin interface verified (Season 7 displayed)
- [ ] Browser cache cleared (fresh content visible)
- [ ] All pages show correct theming (blue frozen, green loading)

---

## 📝 **POST-VERIFICATION NOTES**

**After completing verification, document:**

1. **Any Issues Found:**
   - What was wrong
   - How it was fixed
   - Prevention measures

2. **Performance:**
   - Page load times
   - API response times
   - Any slowdowns

3. **User Experience:**
   - All messages clear and consistent
   - Theming consistent across pages
   - Navigation working correctly

---

## 🎯 **SUCCESS CRITERIA**

**Deployment is successful when:**

- ✅ All 4 public pages show correct Season 6 frozen + Season 7 loading status
- ✅ "Current Season Statistics" shows 0 for all 3 main games
- ✅ Leaderboard shows frozen Season 6 (from historical stats)
- ✅ All APIs return correct Season 7 data
- ✅ Admin interface displays Season 7 correctly
- ✅ No old "Season 6 Running" messages anywhere
- ✅ Browser cache cleared and fresh content visible
- ✅ Auto-switch system ready (will activate when 3+ games played)

---

**Status:** ⏳ **WAITING FOR RENDER RESTART**  
**Next Step:** Verify all checklist items after Render restarts  
**Documentation:** Update this file with verification results

