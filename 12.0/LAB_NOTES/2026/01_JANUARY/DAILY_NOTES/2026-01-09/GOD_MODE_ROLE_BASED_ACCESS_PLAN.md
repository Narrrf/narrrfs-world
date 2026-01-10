# 🔐 God Mode Role-Based Access Control - Implementation Plan

**Date:** January 9, 2026  
**Status:** 📋 **PLANNING PHASE**  
**Priority:** 🚨 **HIGH - Security & User Experience**

---

## 🎯 **OBJECTIVES**

### **1. God Mode Access Control**
- **Goal:** Only Discord roles "Admin", "Moderator", and "Game Tester" (role ID: `1428901285754830858`) should have access to God Mode
- **Current State:** God Mode is available to all users (including guests)
- **Target State:** God Mode toggle only visible/enabled for authorized roles

### **2. Login Advice Display**
- **Goal:** Show advice on main menu to login with Discord to earn DSPOINC and prizes
- **Current State:** No login advice shown
- **Target State:** Advice banner/message shown to non-logged-in users

### **3. User Info Display on Main Menu**
- **Goal:** When logged in, show username, profile picture, and DSPOINC balance on the "NEW GAME" GUI (first screen/main menu)
- **Current State:** Main menu shows generic welcome message
- **Target State:** Personalized welcome with user info for logged-in users

---

## 📋 **CURRENT IMPLEMENTATION ANALYSIS**

### **1. God Mode Current Implementation**

**Location:** `public/three.js/gui-system.js` and `public/three.js/main.js`

**Key Findings:**
- God Mode is checked via `this.config.getGodMode()` in character selection (lines ~3021, ~3053)
- Options menu likely contains God Mode toggle
- Need to find where options menu is created

**Current Behavior:**
- God Mode can be enabled/disabled by any user
- No role-based checking currently implemented

### **2. Discord Login Current Implementation**

**API Endpoint:** `/api/user/details.php`

**Current Response:**
```json
{
  "success": true,
  "user": {
    "discord_id": "328601656659017732",
    "username": "Narrrf",
    "avatar_url": "https://cdn.discordapp.com/...",
    "balance": 12345,
    "roles": ["admin", "premium"],  // ✅ Already returns roles!
    "traits": ["CHEESE_LOVER"]
  }
}
```

**Key Finding:** ✅ **API already returns roles!** No backend changes needed.

**Frontend Implementation:**
- `fetchPlayerDetails()` in `main.js` (lines ~14801-14905) fetches user details
- `hydratePlayerProfile()` called on page load
- Global variables: `resolvedDiscordId`, `playerDisplayName`, `currentTotalDspoinc`
- ⚠️ **Missing:** Role checking and storage

### **3. Main Menu Current Implementation**

**Location:** `public/three.js/gui-system.js` (lines ~2625-2735)

**Current Structure:**
- Welcome message: "Welcome to Narrrf's World 3D Riddle Game"
- Buttons: New Game, Options, Controls, Exit
- No user info displayed
- No login advice shown

---

## 🛠️ **IMPLEMENTATION PLAN**

### **PHASE 1: Role-Based God Mode Access Control**

#### **Step 1.1: Update `fetchPlayerDetails()` to Store Roles**

**File:** `public/three.js/main.js` (lines ~14801-14905)

**Changes:**
```javascript
// Add global variable for user roles
let userRoles = [];  // Store user's Discord roles
let hasGodModeAccess = false;  // Cache God Mode access status

async function fetchPlayerDetails() {
  // ... existing code ...
  
  if (data.success && data.user) {
    // ... existing updates ...
    
    // NEW: Store user roles and check God Mode access
    userRoles = data.user.roles || [];
    hasGodModeAccess = checkGodModeAccess(userRoles);
    
    console.log("✅ [DEBUG] User roles:", userRoles);
    console.log("🔐 [DEBUG] Has God Mode access:", hasGodModeAccess);
  }
}

// NEW: Function to check God Mode access
function checkGodModeAccess(roles) {
  // Allowed roles: Admin, Moderator, Game Tester (role ID: 1428901285754830858)
  const allowedRoleNames = ['admin', 'administrator', 'moderator', 'mod'];
  const gameTesterRoleId = '1428901285754830858';
  
  // Check if user has any allowed role name
  const hasAllowedRoleName = roles.some(role => 
    allowedRoleNames.some(allowed => 
      role.toLowerCase().includes(allowed.toLowerCase())
    )
  );
  
  // Check if user has Game Tester role ID
  // Note: We need to check if roles array contains role IDs or names
  // If API returns role IDs, check for game tester ID
  // If API returns role names, check for "Game Tester"
  const hasGameTesterRole = roles.some(role => 
    role === gameTesterRoleId || 
    role.toLowerCase().includes('game tester') ||
    role.toLowerCase().includes('gametester')
  );
  
  return hasAllowedRoleName || hasGameTesterRole;
}
```

#### **Step 1.2: Update Options Menu to Hide God Mode Toggle**

**File:** `public/three.js/gui-system.js` (need to find options menu creation)

**Changes:**
```javascript
showOptionsMenu() {
  // ... existing menu creation code ...
  
  // NEW: Check if user has God Mode access
  const hasGodModeAccess = window.hasGodModeAccess || false;
  
  // Only show God Mode toggle if user has access
  if (hasGodModeAccess) {
    // Create God Mode toggle checkbox
    const godModeToggle = this._createCheckboxOption(
      "God Mode",
      "Enable developer features (level selector, riddle jumps, boss controls)",
      this.config.getGodMode ? this.config.getGodMode() : false,
      (enabled) => {
        if (this.config.setGodMode) {
          this.config.setGodMode(enabled);
        }
      }
    );
    optionsContainer.appendChild(godModeToggle);
  } else {
    // For non-authorized users, God Mode is always off
    // Ensure God Mode is disabled if somehow enabled
    if (this.config.setGodMode) {
      this.config.setGodMode(false);
    }
  }
}
```

#### **Step 1.3: Disable God Mode on Initialization for Non-Authorized Users**

**File:** `public/three.js/main.js` (after `fetchPlayerDetails()` call)

**Changes:**
```javascript
// After fetching player details, ensure God Mode is disabled if user doesn't have access
async function hydratePlayerProfile() {
  console.log("🚀 [DEBUG] hydratePlayerProfile called on page load");
  await fetchPlayerDetails();
  
  // NEW: Disable God Mode if user doesn't have access
  if (!hasGodModeAccess && window.setGodMode) {
    window.setGodMode(false);
    console.log("🔐 [DEBUG] God Mode disabled - user doesn't have access");
  }
}
```

#### **Step 1.4: Update Character Selection Menu to Respect God Mode Access**

**File:** `public/three.js/gui-system.js` (lines ~3020-3041, ~3052-3073)

**Changes:**
```javascript
// In showCharacterSelectionMenu(), update God Mode check:
const isGodMode = hasGodModeAccess && (this.config.getGodMode ? this.config.getGodMode() : false);

if (isGodMode) {
  // Show level selector
} else {
  // Start game normally
}
```

---

### **PHASE 2: Login Advice Display**

#### **Step 2.1: Add Login Advice Banner to Main Menu**

**File:** `public/three.js/gui-system.js` (lines ~2667-2675)

**Changes:**
```javascript
showMainMenu() {
  // ... existing menu creation ...
  
  // NEW: Add login advice banner if user is not logged in
  if (!resolvedDiscordId || !playerDisplayName || playerDisplayName === "Guest") {
    const loginAdviceBanner = document.createElement("div");
    Object.assign(loginAdviceBanner.style, {
      background: "linear-gradient(135deg, rgba(139, 92, 246, 0.9), rgba(99, 102, 241, 0.9))",
      border: "2px solid rgba(167, 139, 250, 0.5)",
      borderRadius: "12px",
      padding: "16px 24px",
      marginBottom: "24px",
      textAlign: "center",
      boxShadow: "0 4px 12px rgba(139, 92, 246, 0.3)"
    });
    
    const adviceTitle = document.createElement("div");
    adviceTitle.textContent = "🔐 Login with Discord to Earn DSPOINC & Prizes!";
    Object.assign(adviceTitle.style, {
      fontSize: "clamp(16px, 3vw, 20px)",
      fontWeight: "700",
      color: "#fef3c7",
      marginBottom: "8px",
      textShadow: "0 2px 8px rgba(0, 0, 0, 0.5)"
    });
    
    const adviceText = document.createElement("div");
    adviceText.textContent = "Log in with Discord to track your progress, earn DSPOINC rewards, and unlock prizes!";
    Object.assign(adviceText.style, {
      fontSize: "clamp(12px, 2vw, 14px)",
      color: "#e9d5ff",
      marginBottom: "12px"
    });
    
    const loginButton = document.createElement("button");
    loginButton.textContent = "🔗 Login with Discord";
    Object.assign(loginButton.style, {
      padding: "10px 20px",
      fontSize: "clamp(14px, 2.5vw, 16px)",
      fontWeight: "600",
      color: "#ffffff",
      background: "rgba(255, 255, 255, 0.2)",
      border: "2px solid rgba(255, 255, 255, 0.3)",
      borderRadius: "8px",
      cursor: "pointer",
      transition: "all 0.2s"
    });
    loginButton.onclick = () => {
      window.location.href = this.config.PROFILE_URL || '/profile.html';
    };
    loginButton.onmouseenter = () => {
      loginButton.style.background = "rgba(255, 255, 255, 0.3)";
      loginButton.style.transform = "scale(1.05)";
    };
    loginButton.onmouseleave = () => {
      loginButton.style.background = "rgba(255, 255, 255, 0.2)";
      loginButton.style.transform = "scale(1)";
    };
    
    loginAdviceBanner.appendChild(adviceTitle);
    loginAdviceBanner.appendChild(adviceText);
    loginAdviceBanner.appendChild(loginButton);
    
    // Insert before buttons container
    panel.insertBefore(loginAdviceBanner, buttonsContainer);
  }
}
```

---

### **PHASE 3: User Info Display on Main Menu**

#### **Step 3.1: Add User Info Section to Main Menu**

**File:** `public/three.js/gui-system.js` (lines ~2668-2675)

**Changes:**
```javascript
showMainMenu() {
  // ... existing menu creation ...
  
  // Title section
  const title = document.createElement("div");
  
  // NEW: If user is logged in, show personalized welcome
  if (resolvedDiscordId && playerDisplayName && playerDisplayName !== "Guest") {
    // Create user info container
    const userInfoContainer = document.createElement("div");
    Object.assign(userInfoContainer.style, {
      display: "flex",
      alignItems: "center",
      gap: "16px",
      marginBottom: "24px",
      padding: "16px 20px",
      background: "rgba(255, 224, 102, 0.1)",
      border: "1px solid rgba(255, 224, 102, 0.3)",
      borderRadius: "12px"
    });
    
    // Profile picture
    const avatarUrl = window.userAvatarUrl || '';
    if (avatarUrl) {
      const avatarImg = document.createElement("img");
      avatarImg.src = avatarUrl;
      Object.assign(avatarImg.style, {
        width: "64px",
        height: "64px",
        borderRadius: "50%",
        border: "2px solid rgba(255, 224, 102, 0.5)",
        boxShadow: "0 4px 8px rgba(0, 0, 0, 0.3)"
      });
      userInfoContainer.appendChild(avatarImg);
    }
    
    // User info text
    const userInfoText = document.createElement("div");
    userInfoText.style.flex = "1";
    
    const welcomeText = document.createElement("div");
    welcomeText.textContent = `Welcome back, ${playerDisplayName}!`;
    Object.assign(welcomeText.style, {
      fontSize: "clamp(18px, 3.5vw, 24px)",
      fontWeight: "700",
      color: "#ffe066",
      marginBottom: "4px"
    });
    
    const balanceText = document.createElement("div");
    const formattedBalance = (currentTotalDspoinc || 0).toLocaleString();
    balanceText.textContent = `DSPOINC: ${formattedBalance}`;
    Object.assign(balanceText.style, {
      fontSize: "clamp(14px, 2.5vw, 16px)",
      color: "#fef3c7",
      opacity: "0.9"
    });
    
    userInfoText.appendChild(welcomeText);
    userInfoText.appendChild(balanceText);
    userInfoContainer.appendChild(userInfoText);
    
    // Insert before title
    panel.insertBefore(userInfoContainer, title);
    
    // Update title text
    title.textContent = "Narrrf's World 3D Riddle Game";
  } else {
    // Default title for guests
    title.textContent = "Welcome to Narrrf's World 3D Riddle Game";
  }
  
  // ... rest of existing code ...
}
```

#### **Step 3.2: Store Avatar URL in Global Variable**

**File:** `public/three.js/main.js` (in `fetchPlayerDetails()`)

**Changes:**
```javascript
async function fetchPlayerDetails() {
  // ... existing code ...
  
  if (data.success && data.user) {
    // ... existing updates ...
    
    // NEW: Store avatar URL globally
    window.userAvatarUrl = data.user.avatar_url || '';
    console.log("✅ [DEBUG] User avatar URL:", window.userAvatarUrl);
  }
}
```

---

## 🔍 **REQUIRED INVESTIGATIONS**

### **1. Options Menu Location**
- [ ] Find where options menu is created in `gui-system.js`
- [ ] Locate God Mode toggle implementation
- [ ] Understand how God Mode state is managed

### **2. API Role Response Format**
- [ ] Verify if `/api/user/details.php` returns role IDs or role names
- [ ] Check if Game Tester role is returned as ID `1428901285754830858` or name "Game Tester"
- [ ] Test API response structure for different user roles

### **3. Role Checking Logic**
- [ ] Verify role names in database (Admin vs admin, Moderator vs moderator)
- [ ] Check if role ID `1428901285754830858` is stored in `tbl_user_roles`
- [ ] Confirm role matching logic works correctly

---

## 📝 **FILES TO MODIFY**

1. **`public/three.js/main.js`**
   - Update `fetchPlayerDetails()` to store roles and check God Mode access
   - Add `checkGodModeAccess()` function
   - Add `userRoles` and `hasGodModeAccess` global variables
   - Update `hydratePlayerProfile()` to disable God Mode for non-authorized users
   - Store `userAvatarUrl` globally

2. **`public/three.js/gui-system.js`**
   - Update `showMainMenu()` to show login advice and user info
   - Update `showOptionsMenu()` to conditionally show God Mode toggle
   - Update `showCharacterSelectionMenu()` to respect God Mode access

3. **Testing Files (Optional)**
   - Create test cases for role checking
   - Test with different user roles (Admin, Moderator, Game Tester, Regular User, Guest)

---

## ✅ **TESTING CHECKLIST**

### **God Mode Access Control:**
- [ ] Admin can see and enable God Mode
- [ ] Moderator can see and enable God Mode
- [ ] Game Tester (role ID `1428901285754830858`) can see and enable God Mode
- [ ] Regular user cannot see God Mode toggle
- [ ] Guest user cannot see God Mode toggle
- [ ] God Mode is disabled for non-authorized users on page load
- [ ] Character selection respects God Mode access

### **Login Advice:**
- [ ] Login advice banner shows for guests
- [ ] Login advice banner shows for non-logged-in users
- [ ] Login advice banner does NOT show for logged-in users
- [ ] Login button redirects to profile page
- [ ] Banner styling is correct and responsive

### **User Info Display:**
- [ ] Username displays correctly for logged-in users
- [ ] Profile picture displays correctly for logged-in users
- [ ] DSPOINC balance displays correctly for logged-in users
- [ ] Default welcome message shows for guests
- [ ] Layout is responsive on mobile devices

---

## 🚨 **CRITICAL REQUIREMENTS**

### **Security:**
- ⚠️ **God Mode must be server-validated** - Frontend checks can be bypassed
- ⚠️ **Role checking must be done server-side** for sensitive operations
- ⚠️ **God Mode state should be stored in session/localStorage securely**

### **User Experience:**
- ✅ Login advice should be clear and non-intrusive
- ✅ User info display should not clutter the main menu
- ✅ God Mode toggle should be hidden (not just disabled) for non-authorized users

### **Performance:**
- ✅ Role checking should be cached to avoid multiple API calls
- ✅ User info should be fetched once on page load
- ✅ Main menu should update user info when refreshed

---

## 📚 **RELATED DOCUMENTATION**

- Discord Login System: `12.0/LAB_NOTES/2026/01_JANUARY/DAILY_NOTES/2026-01-09/GUI_DISCORD_LOGIN_WORKING.md`
- API Endpoint: `api/user/details.php`
- Role Gate System: `public/js/role-gate.js`
- Main Technical Docs: `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md`

---

**Last Updated:** January 9, 2026  
**Status:** 📋 **PLANNING PHASE - Ready for Implementation**  
**Next Step:** Investigate options menu location and API role response format
