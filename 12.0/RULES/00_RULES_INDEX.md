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
   - **Purpose:** Correct field mappings for all 7 games
   - **Critical:** Prevents data synchronization issues
   - **Status:** ✅ **UPDATED - ALL 7 GAMES DOCUMENTED (December 20, 2025)**

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

20. **`20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md`** - **📚 TECHNICAL DOCUMENTATION SYNCHRONIZATION**
   - **Purpose:** Synchronize rules with technical documentation for decades of development
   - **Critical:** Rules ↔ Technical Documentation sync, verification protocols, reference patterns
   - **Status:** ✅ **ACTIVE - CRITICAL REFERENCE RULE**
   - **Created:** December 20, 2025
   - **Scope:** All rules, all technical documentation, all development work
   - **Features:**
     - Complete synchronization protocol
     - Verification procedures
     - Reference patterns
     - Update procedures
     - Cross-referencing guidelines
     - Maintenance workflows

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
- **✅ Technical Documentation Sync** - New rule for rules ↔ technical docs synchronization

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
├── 19_CHEST_SYSTEM_RULE.md              # 🎁 CHEST SYSTEM - TREASURE CHEST IMPLEMENTATION
└── 20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md # 📚 TECHNICAL DOCUMENTATION SYNCHRONIZATION
```

---

## 🎯 **RULE USAGE GUIDELINES**

### **FOR ALL DEVELOPMENT SESSIONS:**
1. **ALWAYS start with `01_MASTER_RULESET.md`** - This is the single source of truth
2. **ALWAYS check `20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md`** - Verify against technical documentation
3. **ALWAYS reference technical documentation** - `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md`
4. **Reference other rules ONLY for historical context** - They are integrated
5. **NEVER create new rule files** - Update the Master Ruleset instead
6. **MAINTAIN chronological order** - All rules are timestamped in Master Ruleset

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
**LAST UPDATED:** December 20, 2025 - Added Technical Documentation Sync Rule  
**STATUS:** ACTIVE - COMPREHENSIVE RULES COLLECTION  
**PURPOSE:** Centralized rules management for decades of development  
**SCOPE:** All development sessions, all rule updates, all LLM synchronization, all technical documentation sync  
**SYNC STATUS:** ✅ All rules updated to reflect 12 technical documentation files complete integration  

---

## 📚 **MASTER DEVELOPMENT REFERENCE**

**For complete documentation of all levels, games, riddles, traits, rewards, APIs, database structures, and code patterns:**

**`12.0/TECHNICAL_DOCUMENTATION/MASTER_DEVELOPMENT_REFERENCE.md`** (Legacy reference)

**`12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md`** ⭐ **NEW - COMPLETE 2025 TECHNICAL DOCUMENTATION**

### **🚨 CRITICAL: ALWAYS REFERENCE TECHNICAL DOCUMENTATION**

**BEFORE ANY DEVELOPMENT WORK:**
1. **ALWAYS check** `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md`
2. **ALWAYS verify** implementation details against technical documentation
3. **ALWAYS cross-reference** rules with technical documentation
4. **ALWAYS update** technical documentation when making changes
5. **ALWAYS verify** both sources are synchronized

**See:** `12.0/RULES/20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md` for complete synchronization protocol

---

### **2025 Complete Technical Documentation (December 20, 2025):**

This comprehensive documentation serves as the **single source of truth** for:
- ✅ **All 7 Games** - Complete technical documentation (Tetris, Snake, Space Invaders, Cheese Hunt, Discord Race, Cheese Rumble, 3D Hytopia)
- ✅ **Admin Interface** - Complete integration documentation (17 tabs, 90+ APIs)
- ✅ **Discord Bot** - Complete bot system documentation (50+ commands)
- ✅ **Database System** - Complete database documentation (66 tables)
- ✅ **Frontend Website** - Complete frontend documentation (20+ pages)
- ✅ **Cheese Engine 13.0** - Complete agent system documentation (12 agents)
- ✅ **All 6 Live Games** - Full integration details (profile.html, store, achievements, Discord)
- ✅ **3D Hytopia Game** - Complete modular architecture (12 modules, 6 levels, riddle system)
- ✅ **Database Schemas** - All tables with correct field mappings
- ✅ **API Endpoints** - Complete API documentation with examples
- ✅ **Integration Details** - profile.html, Admin Interface, Discord bot, shop system, achievements
- ✅ **Code Examples** - Ready-to-use code snippets
- ✅ **Future Plans** - Implementation roadmaps

**Complete File List (12 Technical Documentation Files):**
- `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md` - Master navigation ⭐ **START HERE**
- `12.0/YEAR_END_2025/GAME_01_TETRIS_COMPLETE_TECHNICAL.md` - Game 1
- `12.0/YEAR_END_2025/GAME_02_SNAKE_COMPLETE_TECHNICAL.md` - Game 2
- `12.0/YEAR_END_2025/GAME_03_SPACE_INVADERS_COMPLETE_TECHNICAL.md` - Game 3
- `12.0/YEAR_END_2025/GAME_04_CHEESE_HUNT_COMPLETE_TECHNICAL.md` - Game 4
- `12.0/YEAR_END_2025/GAME_05_DISCORD_RACE_COMPLETE_TECHNICAL.md` - Game 5
- `12.0/YEAR_END_2025/GAME_06_CHEESE_RUMBLE_COMPLETE_TECHNICAL.md` - Game 6
- `12.0/YEAR_END_2025/GAME_07_3D_HYTOPIA_COMPLETE_TECHNICAL.md` - Game 7
- `12.0/YEAR_END_2025/ADMIN_INTERFACE_COMPLETE_TECHNICAL.md` - Admin Interface
- `12.0/YEAR_END_2025/DISCORD_BOT_COMPLETE_TECHNICAL.md` - Discord Bot
- `12.0/YEAR_END_2025/DATABASE_COMPLETE_TECHNICAL.md` - Database System
- `12.0/YEAR_END_2025/FRONTEND_WEBSITE_COMPLETE_TECHNICAL.md` - Frontend Website
- `12.0/YEAR_END_2025/CHEESE_ENGINE_13.0_AGENT_SYSTEM_COMPLETE_TECHNICAL.md` - Cheese Engine 13.0

**Use this 2025 technical documentation alongside the rules for all future development to ensure consistency and synchronization across decades of development.**

---

**🧀 THIS IS THE CENTRAL HUB FOR ALL NARRRFS WORLD RULES! 🧀**
