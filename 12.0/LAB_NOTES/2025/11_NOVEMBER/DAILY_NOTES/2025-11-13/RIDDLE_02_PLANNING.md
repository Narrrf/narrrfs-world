# 🧩 RIDDLE #2 PLANNING - CHEESE TEMPLE LEVEL 1

**Date:** November 13, 2025  
**Session:** Three.js Dimension Development - Riddle #2 Planning  
**Status:** 🔄 **PLANNING** - Awaiting Implementation Details  
**Riddle ID:** `CHEESE_TEMPLE_RIDDLE_02`  
**Level:** Cheese Temple - Level 1 (Same Map)

---

## 🎯 **OBJECTIVE**

Create Riddle #2 in the same Cheese Temple map (level1.json) as Riddle #1, providing players with a second challenge to complete in the same level.

---

## 📋 **RIDDLE #2 REQUIREMENTS**

### **✅ Completed:**
- ✅ Riddle #2 documentation structure created
- ✅ Riddle #2 lab note created
- ✅ Ready for user specifications

### **⏳ Awaiting User Specifications:**
- ⏳ **Riddle Mechanics:** How to solve Riddle #2
- ⏳ **Steps:** Number of steps and what each step requires
- ⏳ **Location:** Where in the map Riddle #2 should be located
- ⏳ **Visual Elements:** What visual elements are needed (blocks, triggers, etc.)
- ⏳ **Detection Method:** How to detect completion (aiming, standing, interacting, etc.)
- ⏳ **Difficulty:** Difficulty level and timing requirements
- ⏳ **Reward:** DSPOINC reward amount (base reward)
- ⏳ **Trait Name:** Trait name for unlocking (planned: `CHEESE_TEMPLE_RIDDLE_02_SOLVED`)

---

## 🔧 **TECHNICAL PLANNING**

### **State Management:**
Riddle #2 will need its own state management. Options:

#### **Option 1: Separate State Object**
```javascript
let riddleState2 = {
  step0Complete: false,
  step1Complete: false,
  step2Complete: false,
  // ... other state properties
};
```

#### **Option 2: Extended State Object**
```javascript
let riddleState = {
  // Riddle #1 state
  step0Complete: false,
  step1Complete: false,
  step2Complete: false,
  // Riddle #2 state
  riddle2: {
    step0Complete: false,
    step1Complete: false,
    step2Complete: false,
    // ... other state properties
  }
};
```

#### **Option 3: Array of Riddles**
```javascript
let riddles = [
  {
    id: 'CHEESE_TEMPLE_RIDDLE_01',
    step0Complete: false,
    step1Complete: false,
    step2Complete: false,
    // ... other state properties
  },
  {
    id: 'CHEESE_TEMPLE_RIDDLE_02',
    step0Complete: false,
    step1Complete: false,
    step2Complete: false,
    // ... other state properties
  }
];
```

**Recommendation:** Option 2 (Extended State Object) - Keeps code organized and allows easy access to both riddles.

### **UI System:**
Riddle #2 will need its own progress UI or extend the existing UI system. Options:

#### **Option 1: Separate UI Element**
- Create separate progress UI element for Riddle #2
- Display both UIs simultaneously if both riddles are active
- Different colors or indicators to distinguish between riddles

#### **Option 2: Single UI with Tabs**
- Single progress UI that switches between Riddle #1 and Riddle #2
- Tab or button to switch between active riddles
- Shows progress for currently active riddle

#### **Option 3: Combined UI**
- Single UI showing progress for both riddles
- Separate progress bars for each riddle
- Visual indicators for which riddle is currently active

**Recommendation:** Option 1 (Separate UI Element) - Simpler implementation, clearer visual feedback.

### **Detection System:**
Riddle #2 will reuse existing detection systems:
- **Raycasting:** For aiming-based challenges (same as Riddle #1)
- **Position Detection:** For standing/interaction-based challenges
- **Collision Detection:** For collision-based challenges
- **Custom Detection:** For unique riddle mechanics

### **API Integration:**
Riddle #2 will use the same API endpoints as Riddle #1:
- **Trait Unlock:** `/api/user/unlock-trait.php` (different trait name)
- **Reward API:** `/api/dev/riddle-reward.php` (different riddle_id)
- **Database:** Same tables (`tbl_user_traits`, `tbl_riddle_completions`)

---

## 📝 **IMPLEMENTATION PLAN**

### **Phase 1: Planning (Current)**
- ✅ Create Riddle #2 documentation structure
- ✅ Create Riddle #2 lab note
- ⏳ Await user specifications for riddle mechanics

### **Phase 2: Implementation (Pending)**
- ⏳ Implement Riddle #2 state management
- ⏳ Implement Riddle #2 detection system
- ⏳ Implement Riddle #2 progress UI
- ⏳ Implement Riddle #2 completion logic
- ⏳ Integrate Riddle #2 with API endpoints

### **Phase 3: Testing (Pending)**
- ⏳ Test Riddle #2 mechanics
- ⏳ Test Riddle #2 UI and visual feedback
- ⏳ Test Riddle #2 API integration
- ⏳ Test Riddle #2 with Riddle #1 (both active simultaneously)

### **Phase 4: Documentation (Pending)**
- ⏳ Update Riddle #2 documentation with implementation details
- ⏳ Update technical documentation
- ⏳ Update lab notes with implementation results
- ⏳ Update daily status files

---

## 🎯 **INTEGRATION WITH RIDDLE #1**

### **Same Map:**
- Both Riddle #1 and Riddle #2 exist in the same Cheese Temple map
- Players can complete both riddles in the same playthrough
- Riddles are independent - completing one doesn't affect the other

### **State Management:**
- Riddle #1 state: `riddleState` (existing)
- Riddle #2 state: `riddleState2` (to be created) or `riddleState.riddle2` (extended)
- Both states are managed independently

### **UI System:**
- Riddle #1 UI: Existing progress UI at bottom-center
- Riddle #2 UI: New progress UI (separate element or combined)
- Both UIs can be displayed simultaneously

### **Detection System:**
- Riddle #1 detection: Existing raycasting and position detection
- Riddle #2 detection: Will reuse existing detection systems
- Both detection systems run independently

### **API Integration:**
- Riddle #1: `CHEESE_TEMPLE_RIDDLE_01`, `CHEESE_TEMPLE_RIDDLE_SOLVED`
- Riddle #2: `CHEESE_TEMPLE_RIDDLE_02`, `CHEESE_TEMPLE_RIDDLE_02_SOLVED`
- Both use same API endpoints (different IDs)

---

## 📋 **QUESTIONS FOR USER**

### **Riddle Mechanics:**
1. **How many steps does Riddle #2 have?** (Riddle #1 has 3 steps)
2. **What does each step require?** (aiming, standing, interacting, etc.)
3. **Where in the map should Riddle #2 be located?** (coordinates or description)
4. **What visual elements are needed?** (blocks, triggers, special objects, etc.)

### **Difficulty & Timing:**
5. **How long should each step take?** (Riddle #1 uses 10 seconds per step)
6. **What is the difficulty level?** (Easy, Medium, Hard)
7. **Should there be a timer decay mechanism?** (Riddle #1 uses 0.5× decay rate)

### **Rewards:**
8. **What is the base DSPOINC reward?** (Riddle #1 uses 500 DSPOINC)
9. **Should role multipliers be applied?** (Yes, same as Riddle #1)
10. **What trait name should be used?** (Planned: `CHEESE_TEMPLE_RIDDLE_02_SOLVED`)

### **Visual Design:**
11. **What visual style should Riddle #2 use?** (similar to Riddle #1 or different?)
12. **What colors should be used?** (Riddle #1 uses golden/yellow theme)
13. **Should there be special effects?** (glow, particles, animations, etc.)

---

## 🚀 **NEXT STEPS**

1. ✅ **Documentation Created:** Riddle #2 documentation structure created
2. ✅ **Lab Note Created:** Riddle #2 planning lab note created
3. ⏳ **Awaiting User Specifications:** User will provide riddle mechanics and implementation details
4. ⏳ **Implementation:** Will implement riddle based on user specifications
5. ⏳ **Testing:** Will test riddle after implementation
6. ⏳ **Documentation Update:** Will update documentation with final implementation details

---

## 📝 **NOTES**

### **Same Map as Riddle #1:**
- Riddle #2 will be implemented in the same Cheese Temple map (level1.json)
- Players can complete both Riddle #1 and Riddle #2 in the same level
- Riddles are independent - completing one doesn't affect the other
- Each riddle has its own state management and progress tracking

### **Integration Points:**
- **State Management:** Will need separate state object or extend existing `riddleState`
- **UI System:** Will need separate progress UI or extend existing UI system
- **Detection System:** Will reuse existing raycasting and detection systems
- **API Integration:** Will use same API endpoints (different riddle_id)

### **Code Structure:**
- **Main Code:** `three.js/main.js`
- **Trait API:** `api/user/unlock-trait.php`
- **Reward API:** `api/dev/riddle-reward.php`
- **Database:** Same tables as Riddle #1

---

**🧩 RIDDLE #2 PLANNING: ✅ DOCUMENTATION READY - AWAITING USER SPECIFICATIONS**

---

**Last Updated:** November 13, 2025  
**Status:** 🔄 **PLANNING** - Awaiting Implementation Details  
**Next Step:** User will provide riddle mechanics and implementation details

