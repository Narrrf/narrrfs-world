# 🐛 BUG #152 - ACHIEVEMENT SYNCHRONIZATION TEST PLAN

**Date:** October 26, 2025  
**Time:** 20:25  
**Bug ID:** #152  
**Reporter:** justm  
**Issue:** Mismatch between unlocked and displayed achievements  
**Severity:** HIGH - Affects user satisfaction  
**Impact:** All 3 main games (Tetris, Snake, Space Invaders)  

---

## 🎯 **PROBLEM STATEMENT**

### **User Report:**
Users are experiencing mismatches between:
- Achievements they've unlocked during gameplay
- Achievements displayed on their profile page
- Achievements stored in the database

### **Symptoms:**
1. Achievement unlocks in-game (popup shows)
2. Achievement doesn't appear on profile page
3. OR: Achievement shows on profile but appears "locked" (greyed out)
4. OR: Achievement shows as unlocked when it shouldn't be

### **Affected Games:**
- 🧩 **Tetris** - `tbl_tetris_achievements`
- 🐍 **Snake** - `tbl_snake_achievements`
- 👾 **Space Invaders** - `tbl_space_invaders_achievements`

---

## 🔍 **ROOT CAUSE ANALYSIS**

### **Potential Issues:**

**1. Database Field Mismatch:**
- Frontend checks `unlocked_at` field
- API might not be returning this field
- Or field is NULL when it should have a timestamp

**2. User ID Field Confusion:**
- Achievements use `user_id` field
- Scores use `discord_id` field
- Mismatch between these could cause sync issues

**3. API Response Structure:**
- `get-tetris-achievements.php` might not return all fields
- `get-snake-achievements.php` integrated in user-game-missions
- `get-space-invaders-achievements.php` might be missing

**4. Frontend Display Logic:**
- Profile page might check wrong field for "unlocked" status
- JavaScript might not be parsing API response correctly

**5. Achievement Saving:**
- In-game popup triggers save
- But save might fail silently
- Or save to wrong user_id

---

## 📋 **COMPREHENSIVE TEST PLAN**

### **Phase 1: Database Verification**

**Test 1.1 - Check Achievement Tables Exist:**
```sql
-- Verify all 3 achievement tables exist
.tables
-- Should show:
-- tbl_tetris_achievements
-- tbl_snake_achievements
-- tbl_space_invaders_achievements
```

**Test 1.2 - Check Table Schemas:**
```sql
-- Tetris achievements
.schema tbl_tetris_achievements
-- Expected fields: id, user_id, achievement_key, achievement_title, 
--                  achievement_description, achievement_icon, 
--                  unlocked_at, game_score, etc.

-- Snake achievements
.schema tbl_snake_achievements
-- Expected fields: id, user_id, achievement_key, achievement_title, 
--                  achievement_description, achievement_icon, 
--                  unlocked_at, game_score, etc.

-- Space Invaders achievements
.schema tbl_space_invaders_achievements
-- Expected fields: id, user_id, achievement_key, achievement_title, 
--                  achievement_description, achievement_icon, 
--                  unlocked_at, game_score, etc.
```

**Test 1.3 - Verify unlocked_at Field:**
```sql
-- Check if unlocked_at is being set properly
SELECT user_id, achievement_key, unlocked_at 
FROM tbl_tetris_achievements 
WHERE user_id = 'TEST_USER_ID' 
LIMIT 5;

SELECT user_id, achievement_key, unlocked_at 
FROM tbl_snake_achievements 
WHERE user_id = 'TEST_USER_ID' 
LIMIT 5;

SELECT user_id, achievement_key, unlocked_at 
FROM tbl_space_invaders_achievements 
WHERE user_id = 'TEST_USER_ID' 
LIMIT 5;
```

**Expected Result:** `unlocked_at` should have TIMESTAMP, not NULL

---

### **Phase 2: API Endpoint Testing**

**Test 2.1 - Tetris Achievements API:**
```bash
# Test endpoint exists and returns data
curl -X POST http://localhost/api/user/get-tetris-achievements.php \
  -H "Content-Type: application/json" \
  -d '{"user_id":"TEST_USER_DISCORD_ID"}'
```

**Expected Response:**
```json
{
  "success": true,
  "achievements": [
    {
      "achievement_key": "firstGame",
      "achievement_title": "First Game",
      "achievement_description": "Complete your first game",
      "achievement_icon": "🎮",
      "unlocked_at": "2025-10-26 20:25:00",  // CRITICAL: Must be present
      "game_score": 100
    }
  ]
}
```

**Test 2.2 - Snake Achievements API:**
```bash
# Check if integrated in user-game-missions.php
curl -X POST http://localhost/api/user/user-game-missions.php \
  -H "Content-Type: application/json" \
  -d '{"user_id":"TEST_USER_DISCORD_ID"}'
```

**Check Response Structure:**
```json
{
  "success": true,
  "data": {
    "games": {
      "snake": {
        "achievements": [
          {
            "achievement_key": "firstKill",
            "unlocked_at": "2025-10-26 20:25:00"  // CRITICAL: Must be present
          }
        ]
      }
    }
  }
}
```

**Test 2.3 - Space Invaders Achievements API:**
```bash
# Test endpoint exists
curl -X POST http://localhost/api/user/get-space-invaders-achievements.php \
  -H "Content-Type: application/json" \
  -d '{"user_id":"TEST_USER_DISCORD_ID"}'
```

**Test 2.4 - Check API File Paths:**
```bash
# Verify API files exist
ls -la api/user/get-tetris-achievements.php
ls -la api/user/get-snake-achievements.php
ls -la api/user/get-space-invaders-achievements.php
```

---

### **Phase 3: Frontend Display Testing**

**Test 3.1 - Profile Page Achievement Display:**

1. Open `profile.html` in browser
2. Log in with test user
3. Navigate to each game's achievement section
4. Check JavaScript console for errors

**Console Commands to Test:**
```javascript
// Check if achievements are loaded
console.log('Tetris achievements:', tetrisAchievements);
console.log('Snake achievements:', snakeAchievements);
console.log('Space Invaders achievements:', spaceInvadersAchievements);

// Check if unlocked_at field is present
console.log('First achievement unlocked_at:', 
  tetrisAchievements[0]?.unlocked_at);
```

**Test 3.2 - Check Display Logic:**

Look for this pattern in profile.html:
```javascript
// CORRECT: Check unlocked_at field
if (achievement.unlocked_at) {
  // Show as unlocked
} else {
  // Show as locked
}

// WRONG: Other field checks
if (achievement.unlocked) { /* This might be wrong field */ }
```

---

### **Phase 4: Game-Specific Testing**

**Test 4.1 - Tetris Achievement Flow:**

1. **Play Tetris:**
   - Clear 1 line → Should unlock "First Line" achievement
   - Clear 4 lines at once → Should unlock "Tetris Master" achievement
   - Get high score → Should unlock score-based achievements

2. **Check In-Game Popup:**
   - Does popup appear?
   - Does it show correct achievement?
   - Does console log "Achievement saved" message?

3. **Check Database:**
```sql
SELECT * FROM tbl_tetris_achievements 
WHERE user_id = 'YOUR_DISCORD_ID' 
ORDER BY unlocked_at DESC 
LIMIT 5;
```

4. **Check Profile Page:**
   - Refresh profile
   - Navigate to Tetris achievements
   - Are newly unlocked achievements shown as unlocked?

**Test 4.2 - Snake Achievement Flow:**

1. **Play Snake:**
   - Eat 100 food → Should unlock achievement
   - Reach score 500 → Should unlock achievement
   - Die → Should unlock "First Death" achievement

2. **Check In-Game Popup:**
   - Verify popup appears
   - Check console logs

3. **Check Database:**
```sql
SELECT * FROM tbl_snake_achievements 
WHERE user_id = 'YOUR_DISCORD_ID' 
ORDER BY unlocked_at DESC 
LIMIT 5;
```

4. **Check Profile Page:**
   - Verify achievements display correctly

**Test 4.3 - Space Invaders Achievement Flow:**

1. **Play Space Invaders:**
   - Kill 100 enemies → Should unlock achievement
   - Reach wave 5 → Should unlock achievement
   - Use special weapon → Should unlock achievement

2. **Check In-Game Popup:**
   - Verify popup appears
   - Check console logs

3. **Check Database:**
```sql
SELECT * FROM tbl_space_invaders_achievements 
WHERE user_id = 'YOUR_DISCORD_ID' 
ORDER BY unlocked_at DESC 
LIMIT 5;
```

4. **Check Profile Page:**
   - Verify achievements display correctly

---

## 🔧 **SYSTEMATIC TESTING PROTOCOL**

### **For Each Game (Tetris, Snake, Space Invaders):**

**Step 1: Clean Slate Test**
- Delete test user's achievements: `DELETE FROM tbl_[game]_achievements WHERE user_id = 'TEST_USER';`
- Play game fresh
- Unlock FIRST achievement
- Verify it appears on profile

**Step 2: Multiple Achievements Test**
- Unlock 3-5 different achievements
- Check database after EACH unlock
- Verify EACH appears on profile

**Step 3: Page Refresh Test**
- Unlock achievement
- Hard refresh profile page (Ctrl+F5)
- Verify achievement still shows as unlocked

**Step 4: Re-Login Test**
- Unlock achievement
- Log out
- Log back in
- Verify achievement persists

**Step 5: Cross-Session Test**
- Unlock achievement in one browser
- Open profile in different browser
- Verify achievement shows in both

---

## 🐛 **COMMON BUG PATTERNS TO CHECK**

### **Pattern 1: Missing unlocked_at in API**

**File to Check:** `api/user/get-*-achievements.php`

**Look for:**
```php
// WRONG: Missing unlocked_at in SELECT
$stmt = $pdo->prepare("
  SELECT achievement_key, achievement_title, achievement_description
  FROM tbl_tetris_achievements
  WHERE user_id = ?
");

// CORRECT: Include unlocked_at
$stmt = $pdo->prepare("
  SELECT achievement_key, achievement_title, achievement_description, 
         achievement_icon, unlocked_at, game_score
  FROM tbl_tetris_achievements
  WHERE user_id = ?
");
```

### **Pattern 2: Missing unlocked_at in INSERT**

**File to Check:** `api/user/save-*-achievement.php`

**Look for:**
```php
// WRONG: No unlocked_at field
$stmt = $pdo->prepare("
  INSERT INTO tbl_tetris_achievements (user_id, achievement_key)
  VALUES (?, ?)
");

// CORRECT: Include CURRENT_TIMESTAMP
$stmt = $pdo->prepare("
  INSERT INTO tbl_tetris_achievements 
    (user_id, achievement_key, achievement_title, unlocked_at)
  VALUES (?, ?, ?, CURRENT_TIMESTAMP)
");
```

### **Pattern 3: Wrong Field Check in Frontend**

**File to Check:** `public/profile.html`

**Look for:**
```javascript
// WRONG: Checking wrong field
if (achievement.is_unlocked) { /* Wrong field */ }
if (achievement.unlocked) { /* Wrong field */ }

// CORRECT: Check unlocked_at
if (achievement.unlocked_at) { /* Correct field */ }
if (achievement.unlocked_at !== null) { /* More explicit */ }
```

### **Pattern 4: User ID Mismatch**

**Check if:**
- Game saves with `discord_id`
- Achievement API queries with `user_id`
- But they're the SAME value (should work)
- Or they're DIFFERENT (would cause mismatch!)

**Test:**
```sql
-- Check what's actually in the table
SELECT DISTINCT user_id FROM tbl_tetris_achievements LIMIT 5;
SELECT DISTINCT discord_id FROM tbl_tetris_scores LIMIT 5;

-- Are they the same format?
-- Both should be Discord ID strings like "1107633105185013790"
```

---

## 📊 **VERIFICATION CHECKLIST**

### **Database Level:**
- [ ] All 3 achievement tables exist
- [ ] All tables have `unlocked_at` column
- [ ] `unlocked_at` is set to TIMESTAMP when achievement unlocked
- [ ] User IDs match between scores and achievements
- [ ] No duplicate achievement entries for same user

### **API Level:**
- [ ] All 3 achievement APIs exist and respond
- [ ] APIs return `unlocked_at` field in response
- [ ] APIs use correct `user_id` field for queries
- [ ] Save achievement APIs set `unlocked_at` to CURRENT_TIMESTAMP
- [ ] No database path errors (../../db/narrrf_world.sqlite)

### **Frontend Level:**
- [ ] Profile page loads achievements for all 3 games
- [ ] JavaScript checks `unlocked_at` field (not other fields)
- [ ] Console shows no errors when loading achievements
- [ ] Achievements display with correct locked/unlocked state
- [ ] Achievement icons and descriptions show correctly

### **Integration Level:**
- [ ] In-game popup triggers on achievement unlock
- [ ] Popup calls save-achievement API
- [ ] API successfully saves to database
- [ ] Profile page reflects new achievement immediately
- [ ] Achievement persists after page refresh
- [ ] Achievement persists after re-login

---

## 🔍 **DEBUGGING COMMANDS**

### **Check Specific User's Achievements:**
```sql
-- Tetris
SELECT achievement_key, unlocked_at 
FROM tbl_tetris_achievements 
WHERE user_id = 'JUSTM_DISCORD_ID';

-- Snake
SELECT achievement_key, unlocked_at 
FROM tbl_snake_achievements 
WHERE user_id = 'JUSTM_DISCORD_ID';

-- Space Invaders
SELECT achievement_key, unlocked_at 
FROM tbl_space_invaders_achievements 
WHERE user_id = 'JUSTM_DISCORD_ID';
```

### **Check for NULL unlocked_at:**
```sql
-- Find achievements with NULL unlocked_at (BUG!)
SELECT * FROM tbl_tetris_achievements WHERE unlocked_at IS NULL;
SELECT * FROM tbl_snake_achievements WHERE unlocked_at IS NULL;
SELECT * FROM tbl_space_invaders_achievements WHERE unlocked_at IS NULL;
```

### **Check API Response in Browser:**
```javascript
// In browser console on profile page
fetch('/api/user/get-tetris-achievements.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ user_id: localStorage.getItem('discord_id') })
})
.then(r => r.json())
.then(d => console.log('Tetris achievements:', d));
```

---

## 🎯 **EXPECTED FIXES**

### **If Missing unlocked_at in API:**
1. Update all 3 `get-*-achievements.php` files
2. Add `unlocked_at` to SELECT query
3. Ensure it's returned in JSON response

### **If Missing unlocked_at in INSERT:**
1. Update all 3 `save-*-achievement.php` files
2. Add `unlocked_at` column to INSERT
3. Set value to `CURRENT_TIMESTAMP`

### **If Wrong Field in Frontend:**
1. Update `profile.html`
2. Change all achievement unlock checks to use `unlocked_at`
3. Test that locked/unlocked states display correctly

### **If Database Path Wrong:**
1. Update all achievement APIs
2. Change to `../../db/narrrf_world.sqlite` for local
3. Or `/var/www/html/db/narrrf_world.sqlite` for production

---

## 📝 **TEST EXECUTION LOG**

### **Test Session: [DATE/TIME]**

**Tetris:**
- [ ] Database check
- [ ] API response check
- [ ] In-game unlock test
- [ ] Profile display test
- [ ] Persistence test

**Snake:**
- [ ] Database check
- [ ] API response check
- [ ] In-game unlock test
- [ ] Profile display test
- [ ] Persistence test

**Space Invaders:**
- [ ] Database check
- [ ] API response check
- [ ] In-game unlock test
- [ ] Profile display test
- [ ] Persistence test

**Issues Found:**
1. [Description]
2. [Description]

**Fixes Applied:**
1. [Description]
2. [Description]

---

## 🚀 **DEPLOYMENT CHECKLIST**

### **Before Deployment:**
- [ ] Test all 3 games locally with test user
- [ ] Verify database schema correct
- [ ] Verify API responses include unlocked_at
- [ ] Verify frontend displays correctly
- [ ] Test with multiple users

### **After Deployment:**
- [ ] Test on production with real user (justm?)
- [ ] Check production database for NULL unlocked_at
- [ ] Verify all 3 games show achievements correctly
- [ ] Monitor for new reports of mismatch
- [ ] Update justm that bug is fixed

---

## 🎯 **SUCCESS CRITERIA**

### **Bug #152 is RESOLVED when:**
1. ✅ All achievements unlocked in-game appear on profile
2. ✅ All achievements on profile show correct locked/unlocked state
3. ✅ No mismatches between database and display
4. ✅ Achievements persist across sessions and browsers
5. ✅ No console errors related to achievements
6. ✅ User (justm) confirms issue is fixed

---

**Test Plan Created:** October 26, 2025 - 20:25  
**Status:** Ready for Execution  
**Next Step:** Execute Phase 1 (Database Verification)  
**Reporter:** justm  
**Assigned:** Development Team  

**🎯 LET'S FIX THIS ACHIEVEMENT SYNC ISSUE! 🏆**

