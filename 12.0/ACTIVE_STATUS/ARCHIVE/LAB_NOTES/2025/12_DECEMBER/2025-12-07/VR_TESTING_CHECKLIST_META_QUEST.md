# 🥽 VR TESTING CHECKLIST - META QUEST

**Date:** December 7, 2025  
**Test Device:** Meta Quest (Oculus Quest)  
**Test Scope:** All 5 Levels  
**Status:** 📋 **READY FOR TESTING**

---

## 🎯 PRE-TEST SETUP

### **Hardware Setup:**
- [ ] Meta Quest headset fully charged
- [ ] Controllers connected and working
- [ ] Quest Link cable connected (if using PC VR)
- [ ] Air Link configured (if using wireless)
- [ ] Headset tracking calibrated

### **Software Setup:**
- [ ] Game opened in WebXR-compatible browser
  - [ ] Chrome/Edge with WebXR support enabled
  - [ ] Firefox with WebXR support enabled
  - [ ] Or Quest Browser (if testing standalone)
- [ ] VR support detected (check console for "🥽 [VR] WebXR support: ✅ Available")
- [ ] VR button visible in Options menu (should appear when VR is supported)

### **Initial Checks:**
- [ ] Console shows: `🥽 [VR] WebXR renderer enabled`
- [ ] Console shows: `🥽 [VR] WebXR support: ✅ Available`
- [ ] Options menu shows "🥽 VR Mode" section with "Enter VR" button
- [ ] No console errors before starting VR

---

## 🎮 LEVEL 1 TEST - "THE TEMPLE"

### **VR Session Start:**
- [ ] Click "Enter VR" button in Options menu
- [ ] VR session starts successfully
- [ ] Console shows: `🥽 [VR] VR session started successfully`
- [ ] Headset displays game view (not black screen)
- [ ] Controllers visible in VR space

### **Movement & Controls:**
- [ ] **Left Thumbstick:** Move forward/backward/left/right
- [ ] **Head Movement:** Camera rotates with head movement
- [ ] **Smooth Movement:** No stuttering or lag
- [ ] **Jump:** Test jump button (if mapped)
- [ ] **Sprint:** Test sprint button (if mapped)

### **Visual & Rendering:**
- [ ] **Character Model:** Mouse character visible in 3rd person (if applicable)
- [ ] **Character Model Hidden:** Mouse character hidden in 1st person
- [ ] **Environment:** Level 1 environment renders correctly
- [ ] **Lighting:** Lighting looks correct
- [ ] **Performance:** Smooth frame rate (90 FPS target for Quest)

### **Riddle Interactions:**
- [ ] **Riddle 1:** Can interact with levers
- [ ] **Riddle 2:** Can interact with blocks
- [ ] **Riddle 3:** Can interact with movable blocks
- [ ] **Portal:** Can enter portal to Level 2

### **Death Animation:**
- [ ] **Bear Trap:** Death animation triggers correctly
- [ ] **Animation Plays:** Death animation visible in VR
- [ ] **Respawn:** Respawn works correctly

### **Comfort & Performance:**
- [ ] **No Motion Sickness:** Comfortable movement experience
- [ ] **Frame Rate:** Stable 90 FPS (or acceptable performance)
- [ ] **No Lag:** Smooth head tracking
- [ ] **No Drift:** Headset tracking stable

### **Exit VR:**
- [ ] Click "Exit VR" button (or use controller button)
- [ ] Returns to desktop mode correctly
- [ ] Console shows: `🖥️ [VR] VR session ended, returned to desktop mode`

---

## 🎮 LEVEL 2 TEST - "THE SPAWN"

### **VR Session Start:**
- [ ] Warp to Level 2
- [ ] Enter VR mode
- [ ] VR session starts successfully

### **Movement & Controls:**
- [ ] **Left Thumbstick:** Movement works correctly
- [ ] **Head Movement:** Camera rotation works
- [ ] **Smooth Movement:** No issues

### **Visual & Rendering:**
- [ ] **Environment:** Level 2 white room renders correctly
- [ ] **Character Model:** Visible/hidden correctly based on camera mode
- [ ] **Performance:** Smooth frame rate

### **Riddle Interactions:**
- [ ] **Inspection Zones:** Can inspect all zones
- [ ] **Gallery:** Can interact with weapon gallery
- [ ] **Portal:** Can enter portal to Level 3

### **Comfort & Performance:**
- [ ] **No Motion Sickness:** Comfortable experience
- [ ] **Frame Rate:** Stable performance
- [ ] **No Issues:** All systems working

---

## 🎮 LEVEL 3 TEST - "THE HUNT"

### **VR Session Start:**
- [ ] Warp to Level 3
- [ ] Enter VR mode
- [ ] VR session starts successfully

### **Movement & Controls:**
- [ ] **Left Thumbstick:** Movement works correctly
- [ ] **Head Movement:** Camera rotation works
- [ ] **Smooth Movement:** No issues

### **Visual & Rendering:**
- [ ] **Environment:** Level 3 environment renders correctly
- [ ] **Character Model:** Visible/hidden correctly
- [ ] **Performance:** Smooth frame rate

### **Riddle Interactions:**
- [ ] **Monster Hunting:** Can interact with monsters
- [ ] **Portal:** Can enter portal to Level 4

### **Death Animation:**
- [ ] **Crushed Walls:** Death animation triggers correctly
- [ ] **Animation Plays:** Death animation visible in VR

### **Comfort & Performance:**
- [ ] **No Motion Sickness:** Comfortable experience
- [ ] **Frame Rate:** Stable performance
- [ ] **No Issues:** All systems working

---

## 🎮 LEVEL 4 TEST - "THE FIRST SHOT" ⭐ **CRITICAL**

### **VR Session Start:**
- [ ] Warp to Level 4
- [ ] Enter VR mode
- [ ] VR session starts successfully

### **Weapon System - CRITICAL TESTS:**
- [ ] **Weapon Viewmodel:** Weapon is visible in VR (attached to headset)
- [ ] **Weapon Position:** Weapon positioned correctly (not floating)
- [ ] **Weapon Rendering:** Weapon model renders correctly (not green/unrendered)
- [ ] **Slot 1 (Pistol):** Weapon in slot 1 visible and functional
- [ ] **Slot 2 (SF13):** Weapon in slot 2 available (preloaded)

### **Shooting - CRITICAL TESTS:**
- [ ] **Trigger Button:** Right controller trigger fires weapon
- [ ] **Bullet Visibility:** Bullets are visible when fired
- [ ] **Bullet Movement:** Bullets move correctly through space
- [ ] **Yellow Bullets (Slot 1):** Yellow bullets visible and working
- [ ] **Purple Bullets (Slot 2):** Purple triple-shot bullets visible and working

### **Weapon Switching:**
- [ ] **Key 1:** Switch to slot 1 works (if keyboard accessible)
- [ ] **Key 2:** Switch to slot 2 works (if keyboard accessible)
- [ ] **Controller Button:** Test if controller button can switch weapons (if mapped)
- [ ] **Switching Smooth:** Weapon switching is smooth, no glitches

### **Step 0 (Hidden Cheese Stone):**
- [ ] **Movement:** Can move around arena
- [ ] **Stone Detection:** Can find and stand on hidden cheese stone
- [ ] **Timer:** 10-second timer works correctly
- [ ] **Step 1 Activation:** Step 1 activates after timer

### **Step 1 (Cheese Hunting):**
- [ ] **Cheese Spawning:** Cheeses spawn correctly
- [ ] **Shooting Cheeses:** Can shoot cheeses with weapon
- [ ] **Hit Detection:** Cheese hit detection works correctly
- [ ] **HUD Visibility:** HUD elements visible in VR (heat, weapon info, progress)
- [ ] **Progress Tracking:** Cheese count updates correctly
- [ ] **Completion:** Step 1 completes after 50 cheeses

### **Step 2 (Monster Waves):**
- [ ] **Monster Spawning:** Monsters spawn correctly
- [ ] **Shooting Monsters:** Can shoot monsters with weapon
- [ ] **Hit Detection:** Monster hit detection works correctly
- [ ] **HUD Visibility:** HUD elements visible (wave counter, monster count)
- [ ] **Triple Shot:** Slot 2 triple-shot works correctly
- [ ] **Completion:** Step 2 completes after 30 monsters

### **Step 3 (Portal):**
- [ ] **Portal Activation:** Portal appears after Step 2
- [ ] **Portal Entry:** Can enter portal to Level 5
- [ ] **Completion Screen:** Completion screen works correctly

### **HUD Elements in VR:**
- [ ] **Heat Bar:** Weapon heat bar visible and updates correctly
- [ ] **Weapon Info:** Weapon slot and name visible
- [ ] **Progress Counter:** Cheese/monster progress visible
- [ ] **Wave Counter:** Wave information visible
- [ ] **HUD Position:** HUD elements positioned correctly (not blocking view)

### **Comfort & Performance:**
- [ ] **No Motion Sickness:** Comfortable shooting experience
- [ ] **Frame Rate:** Stable performance during shooting
- [ ] **No Lag:** Smooth weapon movement and shooting
- [ ] **Bullet Performance:** No performance issues with multiple bullets

---

## 🎮 LEVEL 5 TEST - "THE WALK" ⭐ **CRITICAL**

### **VR Session Start:**
- [ ] Warp to Level 5 (from Level 4 portal or menu)
- [ ] Enter VR mode
- [ ] VR session starts successfully

### **Weapon System - CRITICAL TESTS:**
- [ ] **Instant Access:** Both weapon slots (1 & 2) instantly available
- [ ] **Weapon Viewmodel:** Weapon is visible in VR (attached to headset)
- [ ] **Weapon Position:** Weapon positioned correctly
- [ ] **Weapon Rendering:** Weapon model renders correctly

### **Shooting - CRITICAL TESTS:**
- [ ] **Trigger Button:** Right controller trigger fires weapon
- [ ] **Bullet Visibility:** Bullets are visible when fired
- [ ] **Bullet Movement:** Bullets move correctly through space
- [ ] **Yellow Bullets (Slot 1):** Yellow bullets visible and working
- [ ] **Purple Bullets (Slot 2):** Purple triple-shot bullets visible and working

### **Weapon Switching:**
- [ ] **Key 1:** Switch to slot 1 works
- [ ] **Key 2:** Switch to slot 2 works
- [ ] **Switching Smooth:** Weapon switching is smooth

### **Environment:**
- [ ] **City Map:** Klagenfurt city map renders correctly
- [ ] **Map Scale:** Map appears at correct scale (5x larger)
- [ ] **Movement:** Can move around large city environment
- [ ] **Performance:** Smooth frame rate in large environment

### **HUD Elements in VR:**
- [ ] **HUD Visibility:** Any HUD elements visible and positioned correctly
- [ ] **Weapon Info:** Weapon information visible (if applicable)

### **Comfort & Performance:**
- [ ] **No Motion Sickness:** Comfortable experience in large environment
- [ ] **Frame Rate:** Stable performance in large map
- [ ] **No Lag:** Smooth movement and shooting

---

## 🔧 GENERAL VR TESTS (All Levels)

### **Controller Functionality:**
- [ ] **Left Controller:** Thumbstick movement works
- [ ] **Right Controller:** Trigger shooting works (Level 4 & 5)
- [ ] **Controller Tracking:** Controllers track correctly in space
- [ ] **Controller Visibility:** Controllers visible in VR (if enabled)
- [ ] **Button Mapping:** All mapped buttons work correctly

### **Camera & Head Tracking:**
- [ ] **Head Rotation:** Camera rotates smoothly with head movement
- [ ] **No Drift:** Headset tracking stable (no drift over time)
- [ ] **Smooth Tracking:** No stuttering or lag in head tracking
- [ ] **First-Person View:** First-person view works correctly
- [ ] **Third-Person View:** Third-person view works correctly (if applicable)

### **Performance:**
- [ ] **Frame Rate:** Stable 90 FPS (or acceptable performance for Quest)
- [ ] **No Stuttering:** Smooth frame delivery
- [ ] **No Lag:** Responsive controls
- [ ] **Memory:** No memory leaks (test over extended session)

### **Comfort:**
- [ ] **No Motion Sickness:** Comfortable experience across all levels
- [ ] **Movement Speed:** Movement speed feels appropriate
- [ ] **Rotation Speed:** Camera rotation speed feels appropriate
- [ ] **Comfort Settings:** Any comfort settings work correctly

### **Audio:**
- [ ] **3D Audio:** Audio spatialization works correctly
- [ ] **Weapon Sounds:** Weapon firing sounds play correctly
- [ ] **Background Music:** Background music plays correctly
- [ ] **Audio Quality:** Audio quality acceptable in VR

### **UI & HUD:**
- [ ] **HUD Visibility:** HUD elements visible in VR
- [ ] **HUD Position:** HUD elements positioned correctly
- [ ] **Menu Access:** Options menu accessible in VR
- [ ] **Text Readability:** Text is readable in VR

### **Session Management:**
- [ ] **Enter VR:** Entering VR works correctly
- [ ] **Exit VR:** Exiting VR works correctly
- [ ] **Session Persistence:** Game state persists when entering/exiting VR
- [ ] **Controller Reconnection:** Controllers reconnect correctly if disconnected

---

## 🐛 KNOWN ISSUES TO CHECK

### **Weapon System:**
- [ ] **Green Object Bug:** No green non-rendered weapon objects appear
- [ ] **Weapon Attachment:** Weapon properly attached to camera/headset
- [ ] **Weapon Visibility:** Weapon visible in first-person, hidden in third-person
- [ ] **Weapon Switching:** Weapon switching works without glitches

### **HUD Elements:**
- [ ] **HUD Positioning:** HUD elements positioned correctly for VR
- [ ] **HUD Visibility:** HUD elements visible and readable
- [ ] **HUD Updates:** HUD updates correctly (heat, progress, etc.)

### **Performance:**
- [ ] **Frame Drops:** No significant frame drops during gameplay
- [ ] **Memory Leaks:** No memory leaks over extended session
- [ ] **Bullet Performance:** No performance issues with multiple bullets

---

## 📝 TESTING NOTES TEMPLATE

### **Level Tested:** [Level Number]
### **Date:** [Date]
### **Duration:** [Time in VR]
### **Issues Found:**
1. [Issue description]
2. [Issue description]

### **Performance:**
- **Frame Rate:** [FPS observed]
- **Stability:** [Stable/Unstable]
- **Comfort:** [Comfortable/Uncomfortable]

### **Controller Mapping:**
- **Left Thumbstick:** [Movement works/Doesn't work]
- **Right Trigger:** [Shooting works/Doesn't work]
- **Other Buttons:** [Notes]

### **Weapon System:**
- **Visibility:** [Visible/Not visible]
- **Position:** [Correct/Incorrect]
- **Shooting:** [Works/Doesn't work]
- **Switching:** [Works/Doesn't work]

### **HUD Elements:**
- **Visibility:** [Visible/Not visible]
- **Position:** [Correct/Incorrect]
- **Readability:** [Readable/Not readable]

### **Comfort:**
- **Motion Sickness:** [None/Mild/Severe]
- **Movement Speed:** [Appropriate/Too fast/Too slow]
- **Rotation Speed:** [Appropriate/Too fast/Too slow]

---

## ✅ POST-TEST SUMMARY

### **Overall Status:**
- [ ] **All Levels Tested:** All 5 levels tested in VR
- [ ] **Weapon System:** Weapon system works correctly in VR
- [ ] **Movement:** Movement works correctly in VR
- [ ] **Performance:** Performance acceptable in VR
- [ ] **Comfort:** Comfortable experience in VR

### **Critical Issues:**
- [ ] **List any critical issues found**
- [ ] **List any blocking issues**

### **Minor Issues:**
- [ ] **List any minor issues found**
- [ ] **List any polish needed**

### **Recommendations:**
- [ ] **List any recommendations for improvements**
- [ ] **List any VR-specific optimizations needed**

---

## 🎯 PRIORITY FIXES (If Issues Found)

### **Critical (Blocking):**
1. [ ] Weapon not visible in VR
2. [ ] Shooting doesn't work
3. [ ] Movement doesn't work
4. [ ] Severe performance issues
5. [ ] Severe motion sickness

### **High Priority:**
1. [ ] HUD elements not visible
2. [ ] Weapon positioning incorrect
3. [ ] Controller mapping issues
4. [ ] Moderate performance issues

### **Medium Priority:**
1. [ ] Minor HUD positioning issues
2. [ ] Minor comfort issues
3. [ ] Minor performance optimizations

### **Low Priority:**
1. [ ] Polish and refinement
2. [ ] Additional VR features
3. [ ] Enhanced comfort options

---

**CREATED:** December 7, 2025  
**STATUS:** 📋 **READY FOR META QUEST TEST SESSION**  
**NEXT:** 🥽 **EXECUTE VR TESTING SESSION**

