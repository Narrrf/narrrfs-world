# 🎮 UNIVERSAL LEVEL REQUIREMENTS RULE

**Rule Version:** 1.0  
**Date Created:** November 18, 2025  
**Status:** ✅ **ENFORCED**  
**Scope:** All 3D game levels (Level 1, 2, 3, 4, and future levels)

---

## 🎯 **CRITICAL RULE**

**ALL levels MUST have identical GOD Mode features, sound systems, controls, and player mechanics. These features are implemented at the GLOBAL level and automatically extend to ALL levels.**

**NO EXCEPTIONS. NO LEVEL-SPECIFIC OVERRIDES.**

---

## ✅ **REQUIRED FEATURES (Same in ALL Levels)**

### **1. GOD Mode System**
- **Double Speed:** 2x movement speed when enabled
- **Fly Mode:** Space = fly up, Shift = fly down
- **No Gravity:** Gravity disabled when GOD Mode active
- **Ground Collision:** ✅ **ALWAYS ACTIVE** - Player can "feel the ground" even in GOD Mode (prevents going through floor)
- **Toggle:** Options menu toggle (persists in localStorage)
- **Status:** ✅ Works in ALL levels with consistent ground collision behavior

### **2. Level Selector (L Key)**
- **Key:** Press **L** in GOD Mode to open level selector menu
- **Features:**
  - Instant level warping between all available levels
  - Current level highlighted in menu
  - Escape key or Close button to dismiss
  - Pauses game automatically when opened
- **Implementation:** Global event handler (document level)
- **Status:** ✅ Works in ALL levels

### **3. Riddle Cycling (G Key)**
- **Key:** Press **G** in GOD Mode to cycle riddle steps
- **Features:**
  - Level 1: Cycles Riddle #1, #2, #3 steps
  - Level 2: Cycles Step 0, Step 1, Step 2
  - Level 3: Cycles Step 0, Step 1, Step 2
  - Level 4: Cycles Step 0, Step 1
- **Implementation:** Global event handler (document level)
- **Status:** ✅ Works in ALL levels

### **3b. Level 6 Boss Cycling (F Key, N Key) – February 3, 2026**
- **F Key:** Cycles Phoenix Dragon behavior (15 patterns)
- **N Key:** Cycles Alien Spider behavior (12 patterns)
- **HUD:** Combined boss HUD shows current behavior; per-frame sync in animate loop
- **Options Menu:** Pause → Options → Boss tab – behavior dropdowns for both bosses
- **Status:** ✅ Works in Level 6 (God Mode)
- **Technical Doc:** `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` (Level 6 Boss HUD section)

### **4. Sound System**
- **Footstep Sounds:** Play when moving on ground (horizontal speed > 0.5)
- **Jump Sound:** Plays on Space key (when on ground)
- **Level-Up Sound:** Plays on portal completion (all levels)
- **Shooting Sound:** Level 4 only (Space Invaders normal_shoot.wav)
- **Audio Settings:** Sound FX toggle in Options menu
- **Status:** ✅ Same sound system in ALL levels

### **5. Player Speed**
- **Normal Speed:** 12 units/second (1.5x base)
- **Sprint Speed:** 21 units/second (1.5x base)
- **GOD Mode Speed:** 2x multiplier (24 normal, 42 sprint)
- **Status:** ✅ Same speed in ALL levels

### **6. Controls**
- **Movement:** WASD or Arrow keys
- **Jump:** Space (normal mode) / Fly up (GOD mode)
- **Sprint:** Shift (normal mode) / Fly down (GOD mode)
- **Camera Toggle:** V key (cycles: 1st → 3rd → Joystick → 1st)
- **Pause:** P or Escape key
- **Status:** ✅ Same controls in ALL levels

### **7. Camera Modes**
- **First-Person:** Camera at head level, pointer lock enabled
- **Third-Person:** Camera follows behind player, pointer lock enabled
- **Joystick View:** Third-person with virtual joysticks, pointer lock disabled
- **Toggle:** V key or Options menu
- **Status:** ✅ Same camera modes in ALL levels

### **8. Options Menu**
- **Location:** Pause menu → Options button
- **Features:**
  - GOD Mode toggle (On/Off)
  - Camera mode selection (1st/3rd/Joystick)
  - Mobile controls toggle (desktop testing)
  - Sound FX toggle
- **Status:** ✅ Same options menu in ALL levels

---

## 🔧 **TECHNICAL IMPLEMENTATION**

### **Global Event Handlers**
All GOD Mode features use **document-level event handlers** (not level-specific):

```javascript
// Level Selector (L key) - GLOBAL
document.addEventListener("keydown", (event) => {
  if (event.key === 'l' || event.key === 'L') {
    if (godMode) {
      showLevelSelector();
      event.preventDefault();
      event.stopPropagation();
      return;
    }
  }
});

// Riddle Cycling (G key) - GLOBAL
document.addEventListener("keydown", (event) => {
  if (event.key === 'g' || event.key === 'G') {
    if (godMode) {
      cycleRiddleJump();
      event.preventDefault();
      return;
    }
  }
});
```

### **Global Sound System**
Sound functions are **global** (not level-specific):

```javascript
// Footstep sounds - GLOBAL
function updateFootstepSoundState() {
  // Works in ALL levels
}

// Jump sound - GLOBAL
function playJumpSound() {
  // Works in ALL levels
}

// Level-up sound - GLOBAL
function playLevelUpSound() {
  // Works in ALL levels
}
```

### **Global Player Speed**
Player speed is calculated **globally** (not level-specific):

```javascript
// Player speed - GLOBAL (same in all levels)
const baseSpeed = movement.sprint ? 21 : 12; // 1.5x: 14*1.5=21, 8*1.5=12
const speed = godMode ? baseSpeed * 4 : baseSpeed;
```

---

## 🚫 **PROHIBITED PRACTICES**

### **DO NOT:**
- ❌ Create level-specific event handlers for GOD Mode features
- ❌ Override global sound functions in level-specific code
- ❌ Change player speed in level-specific code
- ❌ Disable GOD Mode features in specific levels
- ❌ Create level-specific camera mode implementations
- ❌ Override global controls in level-specific code

### **DO:**
- ✅ Use global event handlers for all GOD Mode features
- ✅ Use global sound functions for all audio
- ✅ Use global player speed calculation
- ✅ Use global camera mode system
- ✅ Use global options menu system
- ✅ Test features in ALL levels to ensure consistency

---

## 📋 **VERIFICATION CHECKLIST**

When implementing a new level, verify:

- [ ] L key opens level selector menu
- [ ] G key cycles riddle steps (if applicable)
- [ ] GOD Mode toggle works (double speed + fly)
- [ ] Footstep sounds play when moving
- [ ] Jump sound plays on Space key
- [ ] Level-up sound plays on portal completion
- [ ] Player speed is 12 normal / 21 sprint (1.5x)
- [ ] Camera modes work (1st/3rd/Joystick)
- [ ] Options menu accessible from pause menu
- [ ] All controls work identically to other levels

---

## 🔍 **DEBUGGING**

If a feature works in one level but not another:

1. **Check Event Handler Location:**
   - Ensure event handlers are at **document level** (not level-specific)
   - Verify handlers are not inside level-specific functions

2. **Check Z-Index:**
   - Level selector z-index: `10005` (must be above all other UI)
   - Verify no other UI elements have higher z-index

3. **Check Event Propagation:**
   - Ensure `event.preventDefault()` and `event.stopPropagation()` are called
   - Verify no other handlers are intercepting the event

4. **Check GOD Mode State:**
   - Verify `godMode` variable is global (not level-specific)
   - Check localStorage: `cheese_temple_god_mode`

5. **Check Console Logs:**
   - Look for "🎮 [GOD MODE] Level selector opened" message
   - Check for any error messages or warnings

---

## 📚 **RELATED DOCUMENTATION**

- **Technical Docs:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md` (Section 15.9)
- **Riddle Docs:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/README.md` (Universal Level Requirements)
- **Code Implementation:** `three.js/main.js` (Lines 9374-9397 for L key, 761-820 for GOD Mode)

---

## ✅ **CURRENT STATUS**

- **Level 1:** ✅ All features working
- **Level 2:** ✅ All features working
- **Level 3:** ✅ All features working
- **Level 4:** ✅ All features working
- **Level 5:** ✅ All features working
- **Level 6:** ✅ All features working (incl. F/N boss cycling, combined boss HUD)
- **Future Levels:** ✅ Will automatically inherit all features

---

## 🎯 **ENFORCEMENT**

This rule is **MANDATORY** for all levels. Any level that does not comply with these requirements must be fixed before deployment.

**Last Updated:** February 3, 2026 (Level 6 boss cycling F/N keys, combined HUD)  
**Maintained By:** Narrrf's Lab Tech Council

