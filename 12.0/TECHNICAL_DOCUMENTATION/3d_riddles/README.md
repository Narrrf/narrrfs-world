# 🧩 3D RIDDLES DOCUMENTATION

**Folder Created:** November 12, 2025  
**Purpose:** Centralized documentation for all 3D adventure riddles  
**Status:** ✅ **ACTIVE**

---

## 📋 **OVERVIEW**

This folder contains comprehensive documentation for all riddles implemented in the 3D Cheese Temple adventure game. Each riddle document includes:

- **Riddle Description:** How to solve the riddle
- **Technical Implementation:** Code details and functions
- **Configuration:** How to adjust difficulty, timing, and rewards
- **Testing Guidelines:** Debugging and testing procedures
- **Visual Design:** UI/UX specifications

---

## 📚 **RIDDLE INDEX**

### **Riddle #1: Cheese Temple Level 1**
- **File:** `RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Status:** ✅ **IMPLEMENTED & TESTED**
- **Difficulty:** Medium-Hard
- **Trait:** `CHEESE_TEMPLE_RIDDLE_SOLVED`
- **Description:** Three-step challenge (hidden discovery → aim cheese → aim unlockable block)
- **Version:** 2.2 (Updated Nov 13, 2025 - Trait unlock API fix complete)
- **Last Updated:** November 13, 2025 - Riddle Note: Trait unlock API fix complete, database schema verified

### **Riddle #2: Cheese Temple Level 1 (Same Map)**
- **File:** `RIDDLE_02_CHEESE_TEMPLE_LEVEL_1.md`
- **Status:** ✅ **SUCCESSFULLY IMPLEMENTED AND TESTED**
- **Difficulty:** *[To be determined]*
- **Trait:** `CHEESE_TEMPLE_RIDDLE_02_SOLVED`
- **Description:** Move the Cheese Stone to the oak stone, then aim at the floating cheese entity
- **Version:** 2.1 (Created Nov 13, 2025 - Successfully implemented and tested)
- **Last Updated:** November 13, 2025 - Riddle Note: Successfully implemented and tested! Block movement physics, oak stone blinking, proximity detection, and API integration all working correctly.

### **Riddle #3: Cheese Temple Level 1 (Same Map)**
- **File:** `RIDDLE_03_CHEESE_TEMPLE_LEVEL_1.md`
- **Status:** ✅ **IMPLEMENTED & TESTED (Nov 15, 2025)**
- **Difficulty:** Hard (multi-step puzzle + precision portal entry)
- **Trait:** `CHEESE_TEMPLE_RIDDLE_03_SOLVED`
- **Description:** Press hidden lever → push liberated block onto oak stone → giant portal spawns. Player must jump directly into the portal; suction radius (5u) assists, but completion requires <2.5u horizontal & <3u vertical distance.
- **Reward:** 750 base DSPOINC + Discord role multipliers
- **Version:** 2.0 (Updated Nov 15, 2025 - portal suction + completion gating live)
- **Last Updated:** November 15, 2025 - Riddle Note: “RIDDLE_PORTAL_SUCTION_NOTE.md” documents new jump-in requirement & suction force.

### **Riddle #1: The Spawn (Level 2)**
- **File:** `RIDDLE_01_THE_SPAWN_LEVEL_2.md`
- **Status:** ⛳ **PLAYABLE PROTOTYPE (Nov 15, 2025)**
- **Description:** Matrix-style Construct room. Players must inspect both shelf rows and the creature lineup to materialize the Level 3 portal. Currently linked to Level 1’s portal warp with placeholder completion toast.

### **Future Riddles:**
- Riddle #3: Cheese Temple Level 2 (Planned)
- Riddle #4: Cheese Temple Level 3 (Planned)
- Additional riddles will be added as they are implemented

---

## 🎯 **RIDDLE SYSTEM ARCHITECTURE**

### **Core Components:**
1. **State Management:** `riddleState` object tracks progress
2. **Aiming Detection:** `THREE.Raycaster` for crosshair-based detection
3. **Timer System:** Frame-based delta timing with decay mechanism
4. **Progress UI:** Real-time visual feedback
5. **Trait Unlocking:** API integration for reward system

### **File Structure:**
```
3d_riddles/
├── README.md (this file)
├── RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md (✅ Implemented)
├── RIDDLE_02_CHEESE_TEMPLE_LEVEL_1.md (✅ Implemented)
├── RIDDLE_03_CHEESE_TEMPLE_LEVEL_1.md (🔄 Planning)
├── RIDDLE_04_CHEESE_TEMPLE_LEVEL_2.md (future)
└── RIDDLE_05_CHEESE_TEMPLE_LEVEL_3.md (future)
```

---

## 🔧 **COMMON PATTERNS**

### **Naming Convention:**
- **Riddle Files:** `RIDDLE_##_[LEVEL_NAME].md`
- **Trait Names:** `CHEESE_TEMPLE_RIDDLE_##` or `CHEESE_TEMPLE_[SPECIFIC_NAME]`
- **Function Prefixes:** `updateRiddle...`, `completeRiddle...`, `unlockRiddle...`

### **Standard Sections:**
1. Riddle Overview
2. How to Solve
3. Technical Implementation
4. Configuration & Adjustment
5. Debugging
6. Testing Checklist
7. Visual Design
8. Future Enhancements

---

## 📝 **DOCUMENTATION STANDARDS**

### **Required Information:**
- ✅ Riddle ID and level
- ✅ Step-by-step solution guide
- ✅ Technical implementation details
- ✅ Configuration options (timing, difficulty, etc.)
- ✅ API endpoints and database schema
- ✅ Visual design specifications
- ✅ Testing procedures

### **Code Examples:**
- Include actual code snippets from implementation
- Show configuration variables and their default values
- Provide examples for common adjustments

### **Visual References:**
- Describe UI elements and their styling
- Include color codes and positioning
- Document animation and transition effects

---

## 🚀 **QUICK REFERENCE**

### **Adjusting Riddle Difficulty:**
1. **Aim Time:** Change `RIDDLE_AIM_TIME` constant
2. **Timer Decay:** Modify decay multiplier in `updateRiddleAiming()`
3. **Detection Range:** Adjust distance threshold in `updateCrosshairAim()`

### **Adding New Riddles:**
1. Create new riddle document in this folder
2. Implement riddle logic in `three.js/main.js`
3. Add riddle state to `riddleState` object
4. Create trait unlock API call
5. Update this README with new riddle entry

---

## 🔗 **RELATED DOCUMENTATION**

- **Main Technical Doc:** `../HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- **Cheese Hunt System:** `../CHEESE_HUNT_SYSTEM_SPECIFICATION.md`
- **Controls System:** See Chapter 13 in Hytopia Three Tech Documentation
- **Trait API:** `api/user/unlock-trait.php`

---

**Folder Version:** 1.2  
**Last Updated:** November 13, 2025  
**Maintained By:** Narrrf's Lab Tech Council

**Latest Update:** November 13, 2025 - Added Riddle #3 documentation structure (planning phase), portal image (`Portal1.png`) documented for use. Riddle #2 status updated to "Successfully Implemented and Tested".

