# 🧀 NARRRFS WORLD 12.0 - RULES INDEX

**STATUS:** ✅ **ACTIVE - COMPREHENSIVE RULES COLLECTION**  
**CREATED:** September 14, 2025  
**PURPOSE:** Centralized rules management for decades of development  

---

## 📋 **RULES ORGANIZATION**

### **🎯 PRIMARY RULES (MUST FOLLOW):**

1. **`01_MASTER_RULESET.md`** - **THE SINGLE SOURCE OF TRUTH**
   - **Size:** 72,180 bytes (comprehensive)
   - **Purpose:** Unified professional system for all development
   - **Status:** ✅ **ACTIVE - SUPERSEDES ALL PREVIOUS RULES**

2. **`08_CRITICAL_CODE_PRESERVATION_RULE.md`** - **🚨 CRITICAL CODE SAFETY**
   - **Size:** 8,500 bytes
   - **Purpose:** Prevent code deletion and modification issues that cause huge problems
   - **Status:** ✅ **ACTIVE - HIGHEST PRIORITY RULE**

3. **`09_RESET_SEASON_PROTOCOL_RULE.md`** - **🚀 SEASON RESET OPERATIONS**
   - **Size:** 12,000 bytes
   - **Purpose:** Professional season reset protocol for all future seasons
   - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**

4. **`02_PROFESSIONAL_ORGANIZATION_RULE.md`** - **WORK ORGANIZATION**
   - **Size:** 9,720 bytes
   - **Purpose:** Professional file organization and LLM synchronization
   - **Status:** ✅ **INTEGRATED INTO MASTER RULESET**

### **🔧 TECHNICAL RULES (CRITICAL FOR DEVELOPMENT):**

5. **`03_LOCALHOST_URL_RULE.md`** - **API URL PATTERNS**
   - **Purpose:** Correct localhost URL patterns for local development
   - **Critical:** Prevents 404 errors in API calls
   - **Status:** ✅ **INTEGRATED INTO MASTER RULESET**

6. **`04_GAME_SCORING_SYSTEM_RULES.md`** - **GAME DATA MAPPING**
   - **Purpose:** Correct field mappings for all 5 games
   - **Critical:** Prevents data synchronization issues
   - **Status:** ✅ **CLEANED AND VERIFIED**

7. **`05_TOKEN_LIMIT_RULE.md`** - **SESSION MANAGEMENT**
   - **Purpose:** Token limit management and work continuity
   - **Critical:** Prevents lost work across sessions
   - **Status:** ✅ **CLEANED AND UPDATED**

8. **`06_ADMIN_INTERFACE_RULE.md`** - **ADMIN SYSTEM ARCHITECTURE**
   - **Purpose:** Enterprise admin interface design principles
   - **Critical:** Scalable game management system
   - **Status:** ✅ **UPDATED WITH DATABASE OVERVIEW TAB**

9. **`07_GAME_SCORE_RETRIEVAL_SYSTEM.md`** - **SCORE SYSTEM ARCHITECTURE**
   - **Purpose:** Dual table strategy and field mapping accuracy
   - **Critical:** Mission status and admin interface synchronization
   - **Status:** ✅ **CORRECTED FIELD MAPPINGS**

10. **`10_FILE_PATH_LOCAL_VS_PRODUCTION_RULE.md`** - **PATH HANDLING**
    - **Purpose:** Prevents #1 most common deployment mistake
    - **Critical:** Local vs production path differences
    - **Status:** ✅ **ACTIVE - CRITICAL DEPLOYMENT RULE**

11. **`11_THREE_JS_RULE.md`** - **THREE.JS DEVELOPMENT**
    - **Purpose:** Three.js game development guidelines
    - **Status:** ✅ **ACTIVE**

12. **`12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md`** - **🎮 LEVEL CONSISTENCY**
    - **Purpose:** Ensures all levels have identical GOD Mode, sound, and controls
    - **Critical:** Level selector (L key), GOD Mode, sound system consistency
    - **Status:** ✅ **ACTIVE - MANDATORY FOR ALL LEVELS**
    - **Created:** November 18, 2025

13. **`13_3D_GAME_DSPOINC_SYNC_RULE.md`** - **🎯 DSPOINC REWARDS SYNC**
    - **Purpose:** Standardizes how DSPOINC rewards and traits from 3D game are synced to DB and profiles
    - **Critical:** Recent Score Changes display, trait unlocking, riddle reward API usage
    - **Status:** ✅ **ACTIVE - MANDATORY FOR ALL 3D GAME RIDDLE REWARDS**
    - **Created:** November 18, 2025

14. **`14_GLTF_SKELETON_CLONING_RULE.md`** - **🚨 GLTF CLONING RENDERING**
    - **Purpose:** Prevent invisible GLTF monster rendering issues
    - **Critical:** SkeletonUtils.clone() required for animated GLTF models
    - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**
    - **Created:** November 23, 2025
    - **Discovery:** Level 4 monster waves - 10+ attempts to fix invisible monsters
    - **Solution:** Use SkeletonUtils.clone() instead of standard clone() for skinned meshes

15. **`15_RIDDLE_REWARD_DATABASE_RULE.md`** - **🎯 RIDDLE REWARD & DATABASE SYSTEM**
    - **Purpose:** Document database tables and reward system for riddle completions
    - **Critical:** Database table structure, API endpoints, reward calculation flow
    - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**
    - **Created:** November 26, 2025
    - **Tables:** `tbl_user_traits`, `tbl_riddle_completions`, `tbl_user_scores`, `tbl_score_adjustments`
    - **Scope:** All riddle rewards, all trait unlocks, all DSPOINC awards

16. **`16_DISCORD_BOT_MANAGEMENT_RULE.md`** - **🤖 DISCORD BOT MANAGEMENT**
    - **Purpose:** Standardized Discord bot management for live database operations
    - **Critical:** Command deployment, bot lifecycle, database interaction, giveaway system
    - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**
    - **Created:** December 3, 2025
    - **Scope:** All bot operations, command deployment, database interactions, server functions
    - **Features:** Giveaway system, bug tracker monitoring, command management

17. **`17_WEAPON_RENDERING_RULE.md`** - **🔫 WEAPON RENDERING - FBX MODELS**
    - **Purpose:** Complete weapon rendering guide for FBX weapon models in first-person view
    - **Critical:** Material brightening, duplicate prevention, visibility enforcement, animation compatibility
    - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**
    - **Created:** December 13, 2025
    - **Scope:** All weapon slots (1-9), all levels (4-9), all FBX weapon models
    - **Features:** Material brightening system, duplicate detection, position preservation, scale configuration

18. **`18_3D_MODEL_RENDERING_RULE.md`** - **🎨 3D MODEL RENDERING - DECORATIVE MODELS**
   - **Purpose:** Standardized method for adding 3D models (GLB/GLTF/FBX) to game levels
   - **Critical:** Model loading, position calculation, material processing, scene integration, FBX vs GLB/GLTF differences
   - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**
   - **Created:** December 13, 2025
   - **Last Updated:** December 13, 2025 - Added comprehensive FBX vs GLB/GLTF rendering differences section
   - **Scope:** All 3D models, all levels, decorative and interactive elements
   - **Features:** 
     - Complete implementation pattern for GLB/GLTF models
     - **CRITICAL FBX pattern** - Cloning requirements, material processing, dark material brightening
     - Complete comparison table (FBX vs GLB/GLTF)
     - Debugging guide and troubleshooting checklist
     - Working examples (trees, bear trap, weapons)

19. **`19_CHEST_SYSTEM_RULE.md`** - **🎁 CHEST SYSTEM - TREASURE CHEST IMPLEMENTATION**
   - **Purpose:** Complete guide for implementing treasure chests with animation and rewards
   - **Critical:** Chest creation, animation system, state management, reward integration, counter system
   - **Status:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**
   - **Created:** December 15, 2025
   - **Scope:** All chests across all levels, standardized chest2 model with animation
   - **Features:**
     - Complete chest creation guide with examples
     - Animation system (lid rotation, state switching)
     - Reward system integration (DSPOINC via API)
     - Chest counter system (tracks opened chests)
     - Standardization (all chests use chest2)
     - Position guidelines (Y = 1.0, matches bear trap)
     - Naming conventions (chest_001, chest_002, etc.)

---

## 🚨 **CRITICAL RULE HIERARCHY**

### **✅ THIS IS THE SINGLE SOURCE OF TRUTH:**
- **`01_MASTER_RULESET.md`** - **SUPERSEDES ALL OTHER RULES**
- **`08_CRITICAL_CODE_PRESERVATION_RULE.md`** - **🚨 CRITICAL - SUPERSEDES ALL OTHER RULES**
- **All other rules are REFERENCE ONLY** - They are integrated into the Master Ruleset
- **NEVER create conflicting rules** - Always update the Master Ruleset instead

### **🔄 RULE CONSOLIDATION STATUS:**
- **✅ Professional Organization** - Integrated into Master Ruleset
- **✅ Localhost URL Rules** - Integrated into Master Ruleset  
- **✅ Game Scoring System** - Integrated into Master Ruleset
- **✅ Token Limit Management** - Integrated into Master Ruleset
- **✅ Admin Interface Rules** - Integrated into Master Ruleset
- **✅ Score Retrieval System** - Integrated into Master Ruleset

---

## 📁 **RULES FOLDER STRUCTURE**

```
12.0/RULES/
├── 00_RULES_INDEX.md                    # This index file
├── 01_MASTER_RULESET.md                 # 🎯 THE SINGLE SOURCE OF TRUTH
├── 02_PROFESSIONAL_ORGANIZATION_RULE.md # Reference only (integrated)
├── 03_LOCALHOST_URL_RULE.md             # Reference only (integrated)
├── 04_GAME_SCORING_SYSTEM_RULES.md      # Reference only (integrated)
├── 05_TOKEN_LIMIT_RULE.md               # Reference only (integrated)
├── 06_ADMIN_INTERFACE_RULE.md           # Reference only (integrated)
├── 07_GAME_SCORE_RETRIEVAL_SYSTEM.md    # Reference only (integrated)
├── 08_CRITICAL_CODE_PRESERVATION_RULE.md # 🚨 CRITICAL - NEVER DELETE WORKING CODE
├── 09_RESET_SEASON_PROTOCOL_RULE.md     # 🚀 SEASON RESET OPERATIONS
├── 10_FILE_PATH_LOCAL_VS_PRODUCTION_RULE.md # 🚨 PATH HANDLING
├── 11_THREE_JS_RULE.md                  # THREE.JS DEVELOPMENT
├── 12_UNIVERSAL_LEVEL_REQUIREMENTS_RULE.md # 🎮 LEVEL CONSISTENCY
├── 13_3D_GAME_DSPOINC_SYNC_RULE.md      # 🎯 DSPOINC REWARDS SYNC
├── 14_GLTF_SKELETON_CLONING_RULE.md     # 🚨 GLTF CLONING RENDERING
├── 15_RIDDLE_REWARD_DATABASE_RULE.md    # 🎯 RIDDLE REWARD & DATABASE SYSTEM
├── 16_DISCORD_BOT_MANAGEMENT_RULE.md    # 🤖 DISCORD BOT MANAGEMENT
├── 17_WEAPON_RENDERING_RULE.md          # 🔫 WEAPON RENDERING - FBX MODELS
├── 18_3D_MODEL_RENDERING_RULE.md        # 🎨 3D MODEL RENDERING - DECORATIVE MODELS
└── 19_CHEST_SYSTEM_RULE.md              # 🎁 CHEST SYSTEM - TREASURE CHEST IMPLEMENTATION
```

---

## 🎯 **RULE USAGE GUIDELINES**

### **FOR ALL DEVELOPMENT SESSIONS:**
1. **ALWAYS start with `01_MASTER_RULESET.md`** - This is the single source of truth
2. **Reference other rules ONLY for historical context** - They are integrated
3. **NEVER create new rule files** - Update the Master Ruleset instead
4. **MAINTAIN chronological order** - All rules are timestamped in Master Ruleset

### **FOR RULE UPDATES:**
1. **Update `01_MASTER_RULESET.md`** - Add new rules to the comprehensive document
2. **Update this index** - Document any new rule additions
3. **Preserve historical context** - Keep reference files for context
4. **Maintain LLM sync** - All LLMs must be updated with rule changes

---

## 🚀 **DECADES OF RULE MANAGEMENT**

### **This system ensures:**
- **100 years of development** rules preserved
- **Professional organization** maintained forever
- **LLM council synchronization** never broken
- **Technical knowledge** never lost
- **Rule consistency** across all development sessions
- **Achievement recognition** always documented

### **The Ultimate Goal:**
**Build a rule system that serves developers for generations, maintaining the professional organization and LLM synchronization that makes Narrrfs World the ultimate code genetics system.**

---

**RULES INDEX CREATED:** September 14, 2025  
**STATUS:** ACTIVE - COMPREHENSIVE RULES COLLECTION  
**PURPOSE:** Centralized rules management for decades of development  
**SCOPE:** All development sessions, all rule updates, all LLM synchronization  

---

## 📚 **MASTER DEVELOPMENT REFERENCE**

**For complete documentation of all levels, games, riddles, traits, rewards, APIs, database structures, and code patterns:**

**`12.0/TECHNICAL_DOCUMENTATION/MASTER_DEVELOPMENT_REFERENCE.md`**

This master reference serves as the **single source of truth** for:
- ✅ All 5 levels (Level 1-5) structure and implementation patterns
- ✅ Complete riddle system architecture with naming conventions
- ✅ All trait systems and naming patterns
- ✅ All reward systems and calculation flows
- ✅ All API endpoints with request/response examples
- ✅ All database tables with complete schema and query examples
- ✅ All 5 games (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race) with correct field mappings
- ✅ Code patterns and standards for all development
- ✅ File structure and organization
- ✅ Quick reference checklist for new level/riddle/game development

**Use this master reference alongside the rules for all future development to ensure consistency and synchronization across decades of development.**

---

**🧀 THIS IS THE CENTRAL HUB FOR ALL NARRRFS WORLD RULES! 🧀**
