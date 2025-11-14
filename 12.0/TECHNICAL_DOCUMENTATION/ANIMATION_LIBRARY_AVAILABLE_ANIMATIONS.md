# 🎬 Animation Library [Standard] - Available Animations

**Model:** Animation Library [Standard] by Quaternius  
**Format:** GLB (Godot export)  
**Total Animations:** 46  
**Last Updated:** November 14, 2025

---

## 📊 Animation Inventory

### ✅ **Currently Used Animations (5/46)**

| Animation Name | Status | Usage | Notes |
|---------------|--------|-------|-------|
| `Idle_Loop` | ✅ Active | Default idle pose | Loops continuously when stationary |
| `Walk_Loop` | ✅ Active | Normal walking | Loops when moving (WASD) |
| `Sprint_Loop` | ✅ Active | Fast running | Loops when sprinting (Shift + movement) |
| `Jump_Start` | ✅ Active | Jump takeoff | Plays when jumping (Space) |
| `Jump_Land` | ✅ Active | Landing | Plays when landing after jump |

---

### 🎯 **Recommended Additional Animations (High Priority)**

| Animation Name | Duration | Loop | Recommended Usage | Priority |
|---------------|----------|------|-------------------|----------|
| `Jog_Fwd_Loop` | 0.93s | ✅ Loop | Alternative to Sprint (medium speed) | 🔴 High |
| `Walk_Formal_Loop` | 1.33s | ✅ Loop | Slow, formal walking animation | 🟡 Medium |
| `Crouch_Idle_Loop` | 2.93s | ✅ Loop | Crouching idle pose | 🟡 Medium |
| `Crouch_Fwd_Loop` | 2.00s | ✅ Loop | Crouching forward movement | 🟡 Medium |
| `Jump_Loop` | 2.50s | ✅ Loop | Mid-air jump animation | 🟢 Low |

---

### 🎮 **Combat & Interaction Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Punch_Jab` | 0.87s | ❌ Once | Quick punch attack |
| `Punch_Cross` | 1.00s | ❌ Once | Strong punch attack |
| `Punch_Enter` | 0.87s | ❌ Once | Entering combat stance |
| `Sword_Attack` | 1.53s | ❌ Once | Sword swing attack |
| `Sword_Attack_RM` | 1.53s | ❌ Once | Sword attack (root motion) |
| `Sword_Idle` | 1.67s | ✅ Loop | Holding sword idle |
| `Roll` | 1.47s | ❌ Once | Dodge roll |
| `Roll_RM` | 1.47s | ❌ Once | Dodge roll (root motion) |
| `Hit_Chest` | 0.33s | ❌ Once | Taking damage (chest) |
| `Hit_Head` | 0.43s | ❌ Once | Taking damage (head) |
| `Interact` | 2.00s | ❌ Once | Interaction animation (picking up, using) |
| `PickUp_Table` | 0.83s | ❌ Once | Picking up item from table |

---

### 🎭 **Pose & Social Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Idle_Talking_Loop` | 2.93s | ✅ Loop | NPC conversation idle |
| `Idle_Torch_Loop` | 1.27s | ✅ Loop | Holding torch idle |
| `Sitting_Idle_Loop` | 1.67s | ✅ Loop | Sitting idle pose |
| `Sitting_Enter` | 1.30s | ❌ Once | Entering sitting position |
| `Sitting_Exit` | 1.03s | ❌ Once | Exiting sitting position |
| `Sitting_Talking_Loop` | 2.93s | ✅ Loop | Sitting while talking |
| `Dance_Loop` | 1.00s | ✅ Loop | Celebration dance |
| `Fixing_Kneeling` | 5.20s | ❌ Once | Repairing/crafting animation |

---

### 🎯 **Weapon Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Pistol_Idle_Loop` | 1.67s | ✅ Loop | Holding pistol idle |
| `Pistol_Shoot` | 0.63s | ❌ Once | Firing pistol |
| `Pistol_Reload` | 1.67s | ❌ Once | Reloading pistol |
| `Pistol_Aim_Neutral` | 0.17s | ❌ Once | Aiming pistol (neutral) |
| `Pistol_Aim_Up` | 0.17s | ❌ Once | Aiming pistol (up) |
| `Pistol_Aim_Down` | 0.17s | ❌ Once | Aiming pistol (down) |

---

### ✨ **Magic/Spell Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Spell_Simple_Enter` | 0.53s | ❌ Once | Entering spell casting pose |
| `Spell_Simple_Exit` | 0.43s | ❌ Once | Exiting spell casting pose |
| `Spell_Simple_Idle_Loop` | 2.10s | ✅ Loop | Holding spell ready |
| `Spell_Simple_Shoot` | 0.50s | ❌ Once | Casting spell |

---

### 🏊 **Swimming Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Swim_Idle_Loop` | 3.33s | ✅ Loop | Floating in water |
| `Swim_Fwd_Loop` | 1.33s | ✅ Loop | Swimming forward |

---

### 🚗 **Vehicle Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Driving_Loop` | 1.67s | ✅ Loop | Driving vehicle |

---

### 💀 **Death & Special Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Death01` | 2.40s | ❌ Once | Death animation |
| `A_TPose` | 0.17s | ❌ Once | T-pose (reference) |

---

### 🔧 **Utility Animations (Not Currently Used)**

| Animation Name | Duration | Loop | Potential Usage |
|---------------|----------|------|-----------------|
| `Push_Loop` | 2.67s | ✅ Loop | Pushing objects (like blocks in riddles!) |

---

## 🎯 **Recommended Implementation Priority**

### **Phase 1: Movement Refinement (Current Issue)**
1. ✅ Fix Walk_Loop → Idle_Loop transition (fade out properly)
2. 🔴 Add `Jog_Fwd_Loop` for medium-speed movement
3. 🟡 Add `Walk_Formal_Loop` for slow, careful movement

### **Phase 2: Enhanced Movement**
1. 🟡 Add `Crouch_Idle_Loop` and `Crouch_Fwd_Loop` for stealth
2. 🟢 Improve `Jump_Loop` for mid-air animation
3. 🟢 Add `Roll` for dodge mechanics

### **Phase 3: Interaction & Combat**
1. 🟡 Add `Interact` for riddle interactions (lever, blocks)
2. 🟡 Add `Push_Loop` for pushing blocks in riddles
3. 🟢 Add `Punch_Jab` and `Punch_Cross` for combat
4. 🟢 Add `Sword_Attack` and `Sword_Idle` for melee combat

### **Phase 4: Social & Environmental**
1. 🟢 Add `Sitting_Idle_Loop` for rest points
2. 🟢 Add `Dance_Loop` for celebrations
3. 🟢 Add `Idle_Torch_Loop` for dark areas

---

## 🔧 **Current Animation System Status**

### **Working:**
- ✅ Idle_Loop plays when stationary
- ✅ Walk_Loop plays when moving
- ✅ Sprint_Loop plays when sprinting
- ✅ Jump_Start plays when jumping
- ✅ Jump_Land plays when landing

### **Issues to Fix:**
- ❌ **Walk_Loop doesn't fade out when stopping** (current bug)
- ❌ Animation transitions are too slow
- ❌ No medium-speed movement option (only walk/sprint)

---

## 📝 **Implementation Notes**

### **Animation Naming Convention:**
- `*_Loop` = Looping animation (use `setLoop(THREE.LoopRepeat, Infinity)`)
- `*_RM` = Root motion version (includes character movement in animation)
- `*_Enter` / `*_Exit` = Transition animations

### **Animation Weight Management:**
- Target animation: Start at 0.3 weight, fade in to 1.0 over 0.15s
- Non-target animations: Fade out to 0.0 over 0.15s, then stop
- Critical: Ensure previous animation is fully faded out before new one starts

### **Animation Priority:**
1. Jump/Fall animations (highest priority)
2. Movement animations (Walk, Sprint, Jog)
3. Idle animations (lowest priority)

---

## 🎮 **Game Integration Recommendations**

### **For Riddle System:**
- `Interact` - Use for lever pulling, button pressing
- `Push_Loop` - Use for pushing blocks (Riddle #2, #3)
- `PickUp_Table` - Use for picking up cheese stones

### **For Combat (Future):**
- `Punch_Jab` / `Punch_Cross` - Quick melee attacks
- `Sword_Attack` - Weapon attacks
- `Roll` - Dodge mechanics
- `Hit_Chest` / `Hit_Head` - Taking damage

### **For Exploration:**
- `Crouch_Fwd_Loop` - Stealth movement
- `Walk_Formal_Loop` - Careful movement
- `Idle_Torch_Loop` - Dark area exploration

---

**Last Updated:** November 14, 2025  
**Status:** Animation transition bug fix in progress  
**Next Steps:** Fix Walk_Loop fade-out, add Jog_Fwd_Loop for medium speed

