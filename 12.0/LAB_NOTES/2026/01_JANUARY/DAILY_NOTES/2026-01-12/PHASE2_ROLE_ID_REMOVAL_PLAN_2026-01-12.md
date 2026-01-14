# 🔒 PHASE 2: Role ID Removal Implementation Plan

**Date:** 2026-01-12  
**Status:** 🚧 **IN PROGRESS**  
**Goal:** Remove role ID exposure from all browser-facing code while maintaining all functionality

---

## 🎯 **OBJECTIVE**

Remove `role_ids` from all API responses and switch all client-side logic to use **role names only** instead of role IDs. This prevents role IDs from being exposed to browsers while maintaining all existing functionality (multipliers, themes, access control).

---

## ✅ **PHASE 1 STATUS (COMPLETE)**

**Discord Server Settings:**
- ✅ All roles/pings disabled in Discord server (user action completed)
- ✅ Primary spam vector blocked at Discord level

---

## 📋 **PHASE 2 IMPLEMENTATION STEPS**

### **STEP 1: Remove `role_ids` from API Response** 🚧 **IN PROGRESS**

**File:** `api/auth/sync-role.php`

**Change:**
- Remove `'role_ids' => $discordRoleIds` from JSON response (line 105)
- Keep `'roles' => $userRoles` (role names only)
- Keep all internal logic unchanged (still uses IDs for mapping, just doesn't expose them)

**Impact:** 
- Frontend games/pages that read `data.role_ids` will get `undefined` (expected — we'll update them next)
- No breaking changes to database sync (still works internally)

---

### **STEP 2: Update Profile Page (`public/profile.html`)**

**Changes:**
- Remove all `roleIds.includes('...')` checks (lines 668-683)
- Switch to role name checks only (case-insensitive string matching)
- Keep Game Tester, VIP Holder, Holder checks working via role names

**Files to modify:**
- `public/profile.html` (lines ~668-695)

---

### **STEP 3: Update Tetris Game (`public/scripts/tetris-scroll.js`)**

**Changes:**
- Remove `userRoleIDs` array and `fetchUserRoleIDs()` function
- Replace with `userRoleNames` array and `fetchUserRoleNames()` function
- Update multiplier logic to use role names (already has name-based multiplier map)
- Update all `userRoleIDs.includes(roleID)` → `userRoleNames.includes(roleName)`

**Files to modify:**
- `public/scripts/tetris-scroll.js`

---

### **STEP 4: Update Snake Game (`public/scripts/snake-scroll.js`)**

**Changes:**
- Remove `snakeUserRoleIDs` array and `fetchSnakeUserRoleIDs()` function
- Replace with `snakeUserRoleNames` array and `fetchSnakeUserRoleNames()` function
- Update multiplier/theme logic to use role names (already has name-based maps)
- Update all role ID checks → role name checks

**Files to modify:**
- `public/scripts/snake-scroll.js`

---

### **STEP 5: Update Space Invaders (`public/scripts/space-cheese-invaders.js`)**

**Changes:**
- Remove `spaceInvadersUserRoleIDs` array and `fetchSpaceInvadersUserRoleIDs()` function
- Replace with `spaceInvadersUserRoleNames` array and `fetchSpaceInvadersUserRoleNames()` function
- Update multiplier/theme logic to use role names (already has name-based maps)
- Update all role ID checks → role name checks

**Files to modify:**
- `public/scripts/space-cheese-invaders.js`

---

### **STEP 6: Update Three.js Game (`public/three.js/main.js`)**

**Changes:**
- Remove hardcoded `ROLE_MULTIPLIERS_BY_ID` constant (line 903)
- Remove `GOD_MODE_ROLE_ID` constant (line 900)
- Use existing `ROLE_MULTIPLIERS_BY_NAME` map (already present)
- Update `getHighestRoleMultiplier()` to use role names only
- Update `checkGodModeAccess()` to use role names only
- Keep `ROLE_PRIORITY` array but switch to role names (or remove if not needed)

**Files to modify:**
- `public/three.js/main.js` (lines ~900-990)

---

### **STEP 7: Verify Glyph Game**

**Status:** Need to check if glyph game uses role IDs
**Action:** Verify glyph.html/glyph scripts don't use role IDs (if they do, update them)

---

### **STEP 8: Optional — Clean Up `discord-tools/role_map.php`**

**Future enhancement:**
- Split `role_map.php` into game roles only (remove ping roles from web-facing file)
- Keep ping roles bot-side only (if bot needs them)

**Status:** Low priority — bot-side only, not exposed to browsers

---

## 🔍 **ROLE NAME MAPPING (Reference)**

**Game multiplier roles (must be preserved):**
- `🎴 VIP Holder` → 2.0x multiplier
- `🏆 Holder` → 1.5x multiplier
- `Champion` → 1.4x multiplier
- `Season Tester` → 1.3x multiplier
- `WL` → 1.3x multiplier
- `Early Bird` → 1.2x multiplier
- `🧀 Cheese Hunter` → 1.1x multiplier
- `Game Tester` → God Mode access

**Note:** Games already have name-based multiplier maps — we just need to use them!

---

## ✅ **TESTING CHECKLIST**

After each step:
- [ ] Game still loads correctly
- [ ] Role multipliers still work (test with different roles)
- [ ] Role themes still apply (golden/silver/etc.)
- [ ] Access control still works (VIP/Holder checks, Game Tester access)
- [ ] No console errors
- [ ] Browser DevTools shows no `role_ids` in network responses

---

## 🚨 **CRITICAL RULES TO FOLLOW**

1. ✅ **NEVER delete working code** — Only modify/add, preserve functionality
2. ✅ **Test after each file change** — Verify game still works
3. ✅ **Keep role name mappings** — All games already have name-based maps
4. ✅ **Preserve database sync** — `sync-role.php` still syncs to DB (just doesn't expose IDs)
5. ✅ **Backward compatibility** — Games should work with role names (they already do!)

---

## 📝 **IMPLEMENTATION STATUS**

- [x] Phase 1: Discord server settings (user action — COMPLETE)
- [x] Step 1: Remove `role_ids` from `api/auth/sync-role.php` (✅ COMPLETE — 2026-01-12)
- [x] Step 2: Update `public/profile.html` (✅ COMPLETE — 2026-01-12)
- [x] Step 3: Update Tetris game script (✅ COMPLETE)
- [x] Step 4: Update Snake game script (✅ COMPLETE)
- [x] Step 5: Update Space Invaders game script (✅ COMPLETE)
- [x] Step 6: Update Three.js main.js (✅ **COMPLETE — 2026-01-14**)
- [ ] Step 7: Verify Glyph game
- [ ] Step 8: Testing & verification

---

**Last Updated:** 2026-01-14  
**Status:** ✅ **STEP 6 COMPLETE - READY FOR TESTING**
