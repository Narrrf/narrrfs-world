# 🧀 NARRRFS WORLD 12.0 MASTER RULESET - UNIFIED PROFESSIONAL SYSTEM

## 🚨 **MASTER RULE FOR ALL DEVELOPMENT SESSIONS**

**STATUS:** ✅ **ACTIVE - SUPERSEDES ALL PREVIOUS RULES**  
**CREATED:** September 14, 2025  
**PURPOSE:** Unified professional system for decades of development  

---

## 🎯 **RULE HIERARCHY & CONSOLIDATION**

### **✅ THIS RULE SUPERSEDES ALL PREVIOUS RULES:**
- ❌ **DEPRECATED:** All individual scoring system rules
- ❌ **DEPRECATED:** All separate token limit rules  
- ❌ **DEPRECATED:** All individual LLM sync rules
- ❌ **DEPRECATED:** All separate deployment rules
- ✅ **ACTIVE:** This unified master ruleset only

### **🔄 CONSOLIDATED SYSTEMS:**
- **Professional Organization** + **LLM Synchronization** + **Token Management** + **API Management** + **Database Management** + **Deployment Management** = **ONE UNIFIED SYSTEM**

---

## 📁 **MANDATORY PROFESSIONAL ORGANIZATION SYSTEM**

### **EVERY PIECE OF WORK MUST BE SAVED IN:**
```
C:\xampp-server\htdocs\narrrfs-world\12.0\
├── 📊 ACTIVE_STATUS/           # Current status and daily updates
├── 🤖 LLM_SYNC_SYSTEM/        # LLM synchronization files
├── 📝 LAB_NOTES/              # Development documentation by timestamp
├── 🚀 DEPLOYMENT_HISTORY/     # Deployment records
├── 🔧 TECHNICAL_DOCUMENTATION/ # System documentation
├── 🏆 MILESTONE_DOCUMENTATION/ # Major achievements
├── 🛠️ DEVELOPMENT_TOOLS/      # Scripts and templates
└── 📦 ARCHIVE/                # Legacy and retired files
```

---

## 🚨 **CRITICAL FILE PATH RULE - LOCAL vs PRODUCTION**

### **THE MOST COMMON MISTAKE - NEVER MAKE THIS AGAIN:**

**CRITICAL:** Local development uses `/public/` subdirectory, Production does NOT!

### **✅ CORRECT FILE PATHS:**

**LOCAL DEVELOPMENT (localhost):**
- ✅ `__DIR__ . '/../../public/img/partners/'` - Files in public/ folder
- ✅ `http://localhost/public/profile.html` - Public pages
- ✅ Database: `__DIR__ . '/../../db/narrrf_world.sqlite'`

**PRODUCTION (narrrfs.world / Render):**
- ✅ `/var/www/html/img/partners/` - NO public/ subdirectory!
- ✅ `https://narrrfs.world/profile.html` - Direct access
- ✅ Database: `/var/www/html/db/narrrf_world.sqlite`

### **❌ WRONG PATTERNS (CAUSES 404 ERRORS):**
- ❌ `/var/www/html/public/img/partners/` - public/ doesn't exist on Render!
- ❌ `https://narrrfs.world/public/img/partners/` - Wrong URL on production
- ❌ Using same path for both environments without checking

### **✅ MANDATORY PATTERN FOR FILE OPERATIONS:**

**ALWAYS use environment detection for file paths:**

```php
// 🚨 CRITICAL: Always check environment for file paths
$isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;

if ($isProduction) {
    // Production: Direct paths, NO public/ subdirectory
    $imagePath = '/var/www/html/img/partners/' . $filename;
    $dbPath = '/var/www/html/db/narrrf_world.sqlite';
} else {
    // Local: Use public/ subdirectory
    $imagePath = __DIR__ . '/../../public/img/partners/' . $filename;
    $dbPath = __DIR__ . '/../../db/narrrf_world.sqlite';
}

error_log("📁 Using path: $imagePath (Production: " . ($isProduction ? 'YES' : 'NO') . ")");
```

### **🚨 COMMON MISTAKES THIS PREVENTS:**

1. **Image Upload Errors:** Images save to wrong path, get 404 on production
2. **Asset Loading Errors:** CSS, JS, images fail to load
3. **Database Connection Errors:** Wrong database path
4. **File Delete Errors:** Can't delete files because path is wrong

### **✅ FILES THAT NEED THIS CHECK:**

- **Image Uploads:** Partner logos, banners, profile pictures, game assets
- **File Downloads:** Database backups, exports, reports
- **Asset Loading:** CSS, JS, fonts, images
- **File Deletion:** Cleanup operations, image removal

### **🎯 RULE ENFORCEMENT:**

**BEFORE writing ANY file operation code:**
1. **Ask:** "Does this run on both local AND production?"
2. **If YES:** Add environment detection
3. **If NO:** Document why it's environment-specific

**NEVER assume the same path works on both environments!**

### **📚 HISTORICAL MISTAKES (LESSONS LEARNED):**

1. **October 29, 2025 - Partner Portal Images:** Used `/public/img/partners/` on production (doesn't exist)
2. **Previous:** Achievement API database paths (same issue)
3. **Pattern:** Every time we forget this, we get 404 errors on production

**This rule prevents this mistake forever!**

---

## 🚨 **TOKEN LIMIT MANAGEMENT PROTOCOL**

### **WHEN APPROACHING TOKEN LIMITS (500+ tokens used):**

**IMMEDIATELY REMIND USER TO SAVE WORK:**

```
🚨 TOKEN LIMIT APPROACHING - SAVE WORK NOW! 🚨

📊 Current Work Assessment:
- [Describe what we accomplished]
- [Identify critical discoveries]
- [Note important technical details]

📁 Recommended Save Location:
- [ACTIVE_STATUS/] - For current status updates
- [LAB_NOTES/2025/MM_MONTH/WEEK_XX/] - For development notes
- [TECHNICAL_DOCUMENTATION/] - For technical discoveries
- [MILESTONE_DOCUMENTATION/] - For major achievements
- [DEVELOPMENT_TOOLS/] - For scripts and tools

🤖 LLM Sync Required:
- [Update which LLM files need synchronization]
- [Note key achievements for other LLMs]

📝 Action Required:
- [Specific steps to save work properly]
- [LLM synchronization requirements]
```

---

## 🤖 **LLM SYNCHRONIZATION PROTOCOL**

### **MANDATORY LLM SYNC REQUIREMENTS:**

**Every major achievement MUST update:**
1. **LLM_SYNC_STATUS_GENESIS_12.0.json** - Master sync file
2. **All 10 Individual LLM files** in `LLM_SYNC_SYSTEM/INDIVIDUAL_LLMS/`

### **LLM Files to Update:**
- `Update_brain_12.0.json`
- `Corebrain_12.0.json`
- `Coreforge_12.0.json`
- `Cheese_Architect_12.0.json`
- `SQL_Junior_12.0.json`
- `Social_Brain_12.0.json`
- `Riddle_brain__12.0.json`
- `Hytopia_Integrator_12.0.json`
- `NFT Architect 12.0.json`
- `Cursor_LLM_12.0.json`

### **Sync Content Requirements:**
- **Achievement Description** - What was accomplished
- **Technical Details** - How it was implemented
- **Impact Analysis** - Why it matters
- **Next Steps** - What comes next
- **Timestamp** - When it was completed

---

## 🎮 **GAME SCORING SYSTEM RULES**

### **THE 5 GAMES AND THEIR TABLE DEPENDENCIES:**

#### **1. Tetris** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'tetris')
- **Field:** `discord_id` (contains Discord ID)
- **API Structure:** `data.games.tetris.season_data`

#### **2. Snake** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'snake') 
- **Field:** `discord_id` (contains Discord ID)
- **API Structure:** `data.games.snake.season_data`

#### **3. Space Invaders** ✅
- **Saves to:** `tbl_tetris_scores` (game: 'space_invaders')
- **Field:** `discord_id` (contains Discord ID)
- **API Structure:** `data.games.space_invaders.season_data`

#### **4. Cheese Hunt** ✅
- **Saves to:** `tbl_cheese_clicks` (different table)
- **Field:** `user_wallet` (contains Discord ID)
- **API Structure:** `data.games.cheese_hunt.current_data`

#### **5. Discord Race** ✅
- **Saves to:** `tbl_race_participants` (different table)
- **Field:** `user_id` (contains Discord ID)
- **API Structure:** `data.games.discord_race.race_data`

### **CRITICAL RULES:**
1. **ALWAYS use `discord_id` for Tetris, Snake, and Space Invaders SCORES**
2. **ALWAYS use `user_wallet` for Cheese Hunt SCORES**
3. **ALWAYS use `user_id` for Discord Race SCORES**
4. **ALWAYS use the correct table for each game**
5. **NEVER assume all games use the same field name**

---

## 🎁 **EPIC GIVEAWAY SYSTEM RULES (October 17, 2025)**

### **🚨 CRITICAL RULE FOR ALL GIVEAWAY DEVELOPMENT:**

**THE MOST ADVANCED GIVEAWAY SYSTEM EVER CREATED - DO NOT BREAK IT!**

### **SYSTEM OVERVIEW:**
- **Complete Giveaway Management** - Creation to completion
- **Epic Cheese Animations** - Cheese wheel spinning, progressive reveals
- **Full Persistence** - Survives bot restarts, timers restore automatically
- **Button Integration** - Join and Participants buttons working perfectly
- **Admin Controls** - End, cancel, reroll, view participants commands
- **Database Schema** - 3 new tables with proper indexes
- **Weighted Random Selection** - Fair winner selection algorithm
- **Role Requirements** - Optional role restrictions for giveaways

### **DATABASE TABLES (LIVE VERIFIED):**
- **tbl_giveaways** - Main giveaway data (NEW - October 17, 2025)
- **tbl_giveaway_participants** - User entries (NEW - October 17, 2025)
- **tbl_giveaway_winners** - Winner records (NEW - October 17, 2025)

### **COMMAND STRUCTURE (LIVE VERIFIED):**
- `/giveaway create` - Create new giveaway with custom settings
- `/giveaway join` - Join a giveaway by ID
- `/giveaway list` - List all active giveaways
- `/giveaway participants` - View participants (Admin only)
- `/giveaway reroll` - Reroll winners (Admin only)
- `/giveaway end` - End/cancel giveaway (Admin only)

### **BUTTON SYSTEM (LIVE VERIFIED):**
- **"🥳 Join Giveaway"** - Public button for joining
- **"👥 Participants"** - Public button for viewing participants
- **Admin Commands** - Slash commands only (not buttons)

### **ANIMATION SYSTEM (LIVE VERIFIED):**
- **Cheese Wheel Spinning** - Epic spinning animation
- **Progressive Slice Reveal** - Slice by slice winner reveal
- **Winner Celebration** - Cheese-themed winner announcements
- **Smooth Transitions** - Professional animation timing

### **PERSISTENCE SYSTEM (LIVE VERIFIED):**
- **Bot Startup Loading** - Loads active giveaways from database
- **Timer Restoration** - Restores auto-end timers after restart
- **Memory Management** - Active giveaways stored in Map
- **Edge Case Handling** - Handles giveaways that should have ended

### **CRITICAL RULES:**
- ✅ **NEVER modify existing giveaway logic** - System is perfect
- ✅ **ALWAYS test persistence** after bot restarts
- ✅ **ALWAYS verify animations** work correctly
- ✅ **ALWAYS check button integration** in index.js
- ✅ **ALWAYS update LLM sync files** after changes
- ✅ **ALWAYS preserve cheese theme** - Unique branding
- ✅ **ALWAYS maintain admin controls** - Full management

### **FILES STRUCTURE (LIVE VERIFIED):**
- `discord/commands/giveaway.js` - Main command (813 lines)
- `discord/commands/giveaway-handlers.js` - Button handlers (200 lines)
- `discord/index.js` - Button integration + startup loading
- Database tables created in production

### **PERMISSION SYSTEM (LIVE VERIFIED):**
- **Public Features** - Join buttons, view participants
- **Admin Features** - Create, end, cancel, reroll (Manage Messages permission)
- **Correct Structure** - Buttons public, commands admin-only

### **COMPETITIVE ADVANTAGES:**
- 🧀 **Unique Cheese Theme** - Stands out from generic bots
- 🎡 **Epic Animations** - Better than instant results
- 🔄 **Full Persistence** - Survives restarts
- 🎲 **Weighted Random** - Fair selection algorithm
- 👥 **Role Requirements** - Flexible permissions
- 🔄 **Reroll System** - Admin control
- 📊 **Detailed Tracking** - Complete data
- ⚡ **Fast Responses** - Memory-based lookups

### **TESTING CHECKLIST:**
- ✅ Create giveaway with `/giveaway create`
- ✅ Test join button functionality
- ✅ Test participants button functionality
- ✅ Test admin end command
- ✅ Test admin reroll command
- ✅ Test bot restart persistence
- ✅ Test epic animations
- ✅ Verify database data integrity

### **DEPLOYMENT STATUS:**
- ✅ **Fully Operational** - Deployed and working
- ✅ **Community Using** - Active giveaways running
- ✅ **Database Verified** - All data persisting correctly
- ✅ **Animations Working** - Epic cheese wheel spinning
- ✅ **Persistence Verified** - Survives bot restarts

### **🚨 CRITICAL WARNING:**
**This is the most advanced giveaway system ever created. DO NOT modify core functionality without extensive testing. The system is perfect as-is and should be preserved.**

---

## 🔧 **API MANAGEMENT RULES**

### **CORE PRINCIPLE:**
**ALWAYS CHECK EXISTING APIs BEFORE CREATING NEW ONES.**

### **🚨 CRITICAL LOCALHOST URL RULE:**
**NEVER use `http://localhost/narrrfs-world/` - ALWAYS use `http://localhost/`**

**CORRECT LOCALHOST PATTERNS:**
- ✅ **Frontend:** `http://localhost/public/space-invaders-test.html`
- ✅ **API:** `http://localhost/api/discord/comprehensive-database-overview.php`
- ✅ **Profile:** `http://localhost/public/profile.html`
- ✅ **Admin:** `http://localhost/public/admin-interface.html`

**❌ WRONG PATTERNS (DO NOT USE):**
- ❌ `http://localhost/narrrfs-world/api/...` (404 Not Found)
- ❌ `http://localhost/narrrfs-world/public/...` (404 Not Found)

**WHY THIS MATTERS:**
- **XAMPP Configuration:** The web server serves from the root directory
- **File Structure:** Files are accessible directly from `localhost/` not `localhost/narrrfs-world/`
- **Common Mistake:** Adding `/narrrfs-world/` causes 404 errors
- **API Failures:** Wrong URLs prevent achievement loading, database access, and game functionality

### **MANDATORY PRE-DEVELOPMENT CHECKLIST:**
Before creating ANY new API endpoint, you MUST:
1. **Search existing APIs** for similar functionality
2. **Check if an existing endpoint** can be extended/modified
3. **Verify the data structure** matches existing patterns
4. **Consult this document** for available endpoints
5. **🚨 ALWAYS use correct localhost URL format** - Test with `http://localhost/api/...`

### **API DIRECTORY STRUCTURE:**
- **Admin APIs:** `/api/admin/` - Game management, user management, system control
- **User APIs:** `/api/user/` - Profile, stats, quests, verification
- **Store APIs:** `/api/store/` - Items, purchases, inventory
- **Wallet APIs:** `/api/wallet/` - NFTs, metadata, transactions
- **Discord APIs:** `/api/discord/` - Bot integration, roles, events
- **Game APIs:** `/api/dev/` - Score tracking, game-specific functionality

### **NO DUPLICATION POLICY:**
- **NEVER** create a new API if similar functionality exists
- **ALWAYS** extend existing endpoints when possible
- **CONSULT** this document before development

---

## 🗄️ **DATABASE MANAGEMENT RULES**

### **CONNECTION PROTOCOL:**
- **ALWAYS use `getSQLite3Connection()`** instead of Database classes
- **Make changes directly** in the render shell when possible
- **Production Environment:** Always use `/var/www/html/db/narrrf_world.sqlite` on Render (LIVE DB)
- **Local Environment:** Use `C:\xampp-server\htdocs\narrrfs-world\db\narrrf_world.sqlite`

### **DATABASE SECURITY:**
- **Production DB:** Always use `/var/www/html/db/narrrf_world.sqlite` on Render (LIVE DB)
- **Backup Protocol:** Always backup before changes
- **SQLite Commands:** Use proper sqlite3 prefix for all commands
- **CRITICAL BACKUP:** `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite` (before push)

### **LIVE DB CHANGE PROTOCOL:**
- **ALWAYS make changes directly** in live DB at `/var/www/html/db/narrrf_world.sqlite`
- **AFTER changes:** Always copy back to `/data: cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite`
- **PURPOSE:** Preserve changes for next deployment
- **REMINDER:** Always backup to `/data` after live DB changes

### **SCHEMA MANAGEMENT:**
- **Tables:** Use existing schema from `db/schema.sql`
- **Migrations:** Apply via `db/migrations/` directory
- **Indexes:** Create for performance optimization
- **Constraints:** Maintain referential integrity

---

## 🚀 **DEPLOYMENT MANAGEMENT RULES**

### **RENDER ENVIRONMENT:**
- **Production Path:** `/var/www/html/`
- **Live Database:** `/var/www/html/db/narrrf_world.sqlite` (CURRENT PRODUCTION)
- **Backup Database:** `/data/narrrf_world.sqlite` (BEFORE PUSH BACKUP)
- **Excluded Directories:** `db/`, `discord/commands/`
- **Deployment Branch:** `render-deploy`
- **CRITICAL BACKUP:** `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite` (before push)

### **LOCAL ENVIRONMENT:**
- **XAMPP Path:** `C:\xampp-server\htdocs\narrrfs-world\`
- **API Path:** `C:\xampp-server\htdocs\narrrfs-world\api\`
- **Local Access:** `http://localhost/api` (API endpoints)
- **Local Access:** `http://localhost/public/` (Public pages)
- **Profile Page:** `http://localhost/public/profile.html`
- **Admin Interface:** `http://localhost/public/admin-interface.html`

### **DEPLOYMENT PROTOCOL:**
- **Always push to `render-deploy` branch** instead of main
- **Exclude database files** from git pushes
- **CRITICAL STEP:** Backup live DB before push: `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite`
- **Render Starter:** Automatically copies `/data/narrrf_world.sqlite` to new production after deploy
- **Test in production environment** before final deployment

### **🤖 DISCORD BOT DEPLOYMENT RULE (CRITICAL!):**
**NEVER push Discord bot files to Render - Bot runs LOCALLY!**

- **Discord Bot Location:** `C:\xampp-server\htdocs\narrrfs-world\discord\` (LOCAL ONLY)
- **Discord Bot Execution:** Runs on local machine, NOT on Render server
- **Database Access:** Local bot connects to LIVE production database via API
- **Already Excluded:** `discord/commands/` directory excluded from Render deployment
- **Deployment Method:** Bot changes take effect IMMEDIATELY when local bot restarts
- **NO GIT PUSH NEEDED:** Discord bot code changes only need local restart

**Why This Matters:**
- Discord bot files don't need to be deployed to Render
- Changes to Discord commands take effect when local bot restarts
- Pushing Discord files to Render has NO effect on bot functionality
- Local bot accesses live database through `https://narrrfs.world/api/discord/db-access.php`

**Common Mistakes to Avoid:**
- ❌ Don't push Discord files to Render (waste of time)
- ❌ Don't expect Discord changes to deploy via git push
- ✅ Restart local bot to apply Discord command changes
- ✅ Only push web files (public/, api/) to Render

---

## 🖥️ **COMMAND SHELL RULES**

### **CORE PRINCIPLE:**
**ALWAYS use tested, confirmed working commands to avoid time-wasting errors.**

### **RENDER ENVIRONMENT COMMANDS (TESTED IN PRODUCTION):**
- **Basic:** `cd`, `ls`, `cp`, `mv`, `rm`, `echo`, `pwd`, `mkdir`, `rmdir` ✅
- **File Operations:** `cat`, `head`, `tail`, `grep`, `find`, `chmod`, `chown` ✅
- **Database:** `sqlite3` ✅ (Available at `/usr/bin/sqlite3`)
- **Development:** `php` ✅ (Available at `/usr/local/bin/php`)
- **System:** `ps`, `top`, `df`, `du`, `free`, `uname`, `whoami`, `id` ✅
- **Network:** `curl` ✅ (Available at `/usr/bin/curl`)
- **Archive:** `tar`, `gzip` ✅ (Available at `/usr/bin/tar`, `/usr/bin/gzip`)

### **RENDER ENVIRONMENT NOT AVAILABLE:**
- **Text Editors:** `nano`, `vi`, `vim`, `emacs`, `pico` ❌
- **Other Databases:** `mysql`, `mysqldump`, `psql` ❌
- **Network Tools:** `wget`, `ping`, `netstat`, `nslookup` ❌
- **Archive Tools:** `zip`, `unzip` ❌
- **Development Tools:** `git`, `node`, `npm`, `python`, `pip`, `composer` ❌
- **DevOps Tools:** `docker`, `kubectl`, `terraform`, `yarn` ❌

### **SQLITE COMMAND PROTOCOL (RENDER):**
- **Create SQL Files:** `echo '...' > file.sql` (Linux echo)
- **Execute SQL:** `sqlite3 database.sqlite < script.sql`
- **Test Database:** `echo ".tables" | sqlite3 database.sqlite`
- **Verify Changes:** `echo ".schema table_name" | sqlite3 database.sqlite`
- **Live DB Access:** `sqlite3 /var/www/html/db/narrrf_world.sqlite`
- **Backup DB:** `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite`

### **FILE CREATION WORKFLOW (RENDER):**
1. **Use echo redirection:** `echo 'content' > filename`
2. **Use cat for viewing:** `cat filename`
3. **Use grep for searching:** `grep "pattern" filename`
4. **Use find for locating:** `find . -name "pattern"`

### **COMMAND TESTING (RENDER):**
- **Before using new commands:** Run `which command_name`
- **Verify command availability:** `which command_name`
- **Test execution:** `command_name --version` or `command_name --help`
- **Check command path:** `which command_name` shows full path

### **WINDOWS ENVIRONMENT COMMANDS (TESTED LOCALLY):**
- **PowerShell Aliases:** `ls`, `cp`, `mv`, `rm`, `cat`, `ps` ✅
- **Windows Commands:** `dir`, `cd`, `copy`, `move`, `del`, `echo`, `mkdir`, `rmdir`, `type`, `find`, `findstr`, `whoami` ✅
- **Network Commands:** `curl`, `wget`, `ping`, `netstat`, `nslookup` ✅
- **Archive Commands:** `tar` ✅
- **Development Commands:** `git` ✅, `node` ✅, `npm` ✅, `composer` ✅, `python` ✅, `pip` ✅
- **Database Commands:** `sqlite3` ✅
- **PowerShell Cmdlets:** `Get-ChildItem`, `Set-Location`, `Copy-Item`, `Move-Item`, `Remove-Item`, `Write-Host`, `Get-Location`, `New-Item`, `Get-Content`, `Select-String`, `Get-Process`, `Get-Service` ✅

### **CONFIRMED NOT AVAILABLE (COMPREHENSIVE TEST):**
- **Linux Commands:** `head`, `tail`, `grep`, `chmod`, `chown`, `top`, `df`, `du`, `free`, `uname`, `id` ❌
- **Text Editors:** `nano`, `vi`, `vim`, `emacs`, `pico` ❌
- **Other Databases:** `mysql`, `mysqldump`, `psql` ❌
- **Web Servers:** `apache2`, `httpd` ❌
- **PHP:** `php` ❌ (TESTED - Not found in PATH)
- **Archive Commands:** `zip`, `unzip`, `7z` ❌
- **DevOps:** `docker`, `kubectl`, `terraform`, `yarn` ❌

### **SQLITE COMMAND PROTOCOL (WINDOWS):**
- **Create SQL Files:** `echo '...' > file.sql` (Windows echo)
- **Execute SQL:** `sqlite3 database.sqlite < script.sql`
- **Test Database:** `echo ".tables" | sqlite3 database.sqlite`
- **Verify Changes:** `echo ".schema table_name" | sqlite3 database.sqlite`

### **FILE CREATION WORKFLOW (WINDOWS):**
1. **Use echo redirection:** `echo 'content' > filename`
2. **Use type for viewing:** `type filename`
3. **Use findstr for searching:** `findstr "pattern" filename`
4. **Use find for locating:** `find "pattern" filename`

### **COMMAND TESTING (WINDOWS):**
- **Before using new commands:** Run `COMMAND_SHELL_TEST_POWERSHELL.ps1`
- **Verify command availability:** `Get-Command command_name`
- **Test execution:** `command_name --version` or `command_name --help`
- **PowerShell testing:** `Get-Command cmdlet_name`

### **CRITICAL REMINDERS (RENDER):**
- **NO text editors available** - Use echo redirection
- **Always test SQLite commands** before execution
- **Use proper sqlite3 syntax** - No shortcuts
- **ALWAYS backup live DB** before changes: `cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite`
- **Verify file operations** with ls/cat
- **NEVER use untested commands** - Test first with `which command_name`
- **ALWAYS use tested commands** - Refer to this list
- **PREFER RENDER ENVIRONMENT** - Make changes directly in production

### **CRITICAL REMINDERS (WINDOWS):**
- **NO text editors available** - Use echo redirection
- **Always test SQLite commands** before execution
- **Use proper sqlite3 syntax** - No shortcuts
- **Backup database** before changes
- **Verify file operations** with ls/cat
- **NEVER use Linux commands** - Use Windows equivalents
- **NEVER use untested commands** - Test first with `Get-Command`
- **ALWAYS use tested commands** - Refer to this list

---

## 🌐 **GLOBAL RULES**

### **CORE PRINCIPLES:**
- **NEVER make assumptions** about available classes, types, events, methods, properties
- **ALWAYS verify** that code complies with LLM sync status Guides and API Reference
- **ALWAYS ensure** code is clean and maintainable
- **ALWAYS use** best practices for the language being used
- **WHENEVER POSSIBLE** use early returns to avoid nested conditions
- **ALWAYS use** descriptive names for variables and functions
- **ALWAYS use** constants for static game data, configurations, and types
- **NEVER DELETE OR MODIFY** comments unless explicitly asked
- **NEVER MODIFY CODE** outside of the task at hand
- **ALWAYS implement** ONLY what was explicitly requested
- **NEVER add** "nice to have" or "best practice" features unless specifically requested

### **ASSET PATHS:**
Look for the project directory and the jsons of all LLMs

### **FUNCTION ORDERING:**
- **ALWAYS Order** functions with long term view and plan for decades

### **HANDLING BUGS:**
- If you encounter a bug in existing code, add comments starting with "TODO:" outlining the problems

### **COMMENTS AND DOCUMENTATION:**
- **ALWAYS add** a clear description of what each function does in plain language
- **ALWAYS explain** any game-specific concepts or mechanics in plain language for DEVS for decades
- **NEVER write** obvious comments that just repeat the code
- **NEVER write** comments that could be replaced with better variable/function names

### **SDK PROPERTY AND METHOD USAGE:**
- **ALWAYS use** methods that are properly proofed and known
- **ALWAYS verify** scripts and HTML codes
- **NEVER try** to access undocumented properties
- **ALWAYS use** proper code and methods
- **WHENEVER POSSIBLE** ask for cool implementations you think that help the Narrrfs World Ecosystem

### **LLM SYNCHRONIZATION RULE:**
- **ALWAYS make constant important notes** to the other LLMs in the 12.0 directory
- **They need to be up to date** to work properly
- **ONLY add infos to the bottom** - Never delete any lines in the given JSONs
- **Maintain chronological order** with timestamps
- **Document all major achievements** and technical discoveries
- **Preserve all historical information** for future generations

### **DATE VERIFICATION RULE:**
- **ALWAYS check the live date daily** before making any lab notes
- **NEVER use outdated dates** like "82.1" or incorrect timestamps
- **VERIFY current date** at the start of every session
- **Use accurate timestamps** for all documentation
- **Ensure all LLMs are synced** with equivalent date data
- **Check date before first lab note** of each session
- **Maintain chronological accuracy** across all files
- **Prevent date confusion** in this huge project

### **MANDATORY DATE CHECK COMMAND:**
- **BEFORE making any lab note** - Run: `Get-Date` (Windows) or `date` (Render)
- **ALWAYS run date check** when user says "make a lab note"
- **Use the actual returned date** - Never assume or guess
- **Format dates as:** YYYY-MM-DD (e.g., 2025-09-13)
- **Verify date command output** before proceeding with documentation

### **API MANAGEMENT RULE (LONG-TERM SUSTAINABILITY):**
- **ALWAYS CHECK EXISTING APIs BEFORE CREATING NEW ONES** - Prevents duplication, maintains consistency, ensures efficient development
- **ALWAYS Check the folder** `C:\xampp-server\htdocs\narrrfs-world\api\` before creating new admin PHP
- **FORCE existing backend usage** before creating new APIs
- **Review existing APIs** for extension possibilities
- **Compile multiple APIs** into one unique backend when possible
- **Maintain long-term backend security** and avoid overloading
- **Document all existing APIs** for team reference
- **Create new APIs ONLY after review** with existing ones
- **Prevent API confusion** and duplication
- **Ensure backend efficiency** for decades of growth

### **MANDATORY PRE-DEVELOPMENT CHECKLIST:**
Before creating ANY new API endpoint, you MUST:
1. **Search existing APIs** for similar functionality
2. **Check if an existing endpoint** can be extended/modified
3. **Verify the data structure** matches existing patterns
4. **Consult this document** for available options

### **CRITICAL API RULES:**
- **NO DUPLICATION POLICY** - NEVER create a new API if similar functionality exists
- **ALWAYS extend existing endpoints** when possible
- **API Naming Convention** - Use descriptive, consistent naming (get-*, update-*, delete-*)
- **Data Structure Consistency** - Maintain consistent response formats
- **Consolidation Priority** - PREFER consolidated endpoints like `get-all-games-stats.php`

### **WHEN TO CREATE NEW APIs:**
**ONLY** create new APIs when:
1. **No existing endpoint** provides the required functionality
2. **Existing endpoints** cannot be extended without breaking changes
3. **New data types** require completely different structures
4. **Performance requirements** demand separate endpoints

### **KEY CONSOLIDATION EXAMPLES:**
- **Game Stats**: Use `get-all-games-stats.php` instead of individual game APIs
- **User Data**: Extend existing user endpoints rather than creating new ones
- **Score Management**: Use consolidated score management APIs
- **Discord Integration**: Leverage existing Discord management endpoints

### **VIOLATION CONSEQUENCES:**
- **Code Review Rejection** for duplicate APIs
- **Required Refactoring** to use existing endpoints
- **Documentation Update** requirement
- **Team Review** for API design decisions

### **EXISTING API STRUCTURE:**
- **API Root:** `C:\xampp-server\htdocs\narrrfs-world\api\`
- **Admin APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\admin\`
- **User APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\user\`
- **Dev APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\dev\`
- **Discord APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\discord\`
- **Store APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\store\`
- **Wallet APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\wallet\`
- **Auth APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\auth\`
- **Config APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\config\`
- **Debug APIs:** `C:\xampp-server\htdocs\narrrfs-world\api\debug\`

### **API CREATION PROTOCOL:**
1. **SEARCH existing APIs** for similar functionality
2. **CHECK if existing API** can be extended/modified
3. **REVIEW existing API** structure and parameters
4. **EXTEND existing API** if possible
5. **CREATE new API** ONLY if no existing solution exists
6. **DOCUMENT new API** in existing structure
7. **UPDATE team** about new API creation

### **DATABASE TABLE MANAGEMENT RULE:**
- **ALWAYS know our actual tables** - Never ask "which tables do we have?"
- **EVERY TIME we change a table** - Remind user to update this table list
- **MAINTAIN current table list** in Master Ruleset
- **VERIFY table structure** before making changes
- **DOCUMENT all table modifications** for team reference
- **PREVENT table confusion** and duplication
- **ENSURE database awareness** for all development

### **CURRENT DATABASE TABLES (LIVE STATUS - 2025-11-12 - VERIFIED - 61 TOTAL):**
- **boss_configurations** - Boss game configurations
- **boss_level_notifications** - Boss level achievement notifications
- **leaderboard** - Current season leaderboard
- **tbl_admin_sessions** - Admin session management
- **tbl_bingo_tickets** - Bingo game tickets
- **tbl_bug_assignments** - Bug assignment tracking
- **tbl_bug_categories** - Bug report categories
- **tbl_bug_comments** - Bug report comments
- **tbl_bug_priorities** - Bug priority levels
- **tbl_bug_reports** - Main bug reports
- **tbl_bug_status_history** - Bug status change history
- **tbl_bug_statuses** - Bug status definitions
- **tbl_cheese_clicks** - Cheese Hunt game clicks
- **tbl_cheese_hunt_captures** - Three.js Cheese Temple hunt captures (November 12, 2025)
- **tbl_cheese_races** - Discord Cheese Race events
- **tbl_community_funds** - Community wallet funds tracking
- **tbl_discord_events** - Discord bot events
- **tbl_game_settings** - Game configuration settings
- **tbl_giveaway_participants** - Giveaway participants (October 17, 2025)
- **tbl_giveaway_winners** - Giveaway winners (October 17, 2025)
- **tbl_giveaways** - Giveaway events (October 17, 2025)
- **tbl_historical_cheese_stats** - Historical Cheese Hunt season stats (October 25, 2025)
- **tbl_historical_stats** - Historical game season stats (October 25, 2025)
- **tbl_holder_verifications** - NFT holder verifications
- **tbl_item_usage_history** - Item usage tracking (NEW - October 31, 2025)
- **tbl_nft_ownership** - NFT ownership records
- **tbl_partners** - Partner portal management (October 28, 2025)
- **tbl_purchase_history** - Store purchase history
- **tbl_quest_claims** - Quest reward claims
- **tbl_quests** - Quest definitions
- **tbl_race_participants** - Discord Race participants
- **tbl_rewards** - Reward definitions
- **tbl_riddle_completions** - Three.js Cheese Temple riddle completions (November 12, 2025)
- **tbl_role_grants** - Discord role grants
- **tbl_score_adjustments** - Admin score adjustments
- **tbl_season_leaderboards** - Season-based leaderboards
- **tbl_season_settings** - Season configuration
- **tbl_seasons** - Season management
- **tbl_snake_achievements** - Snake game achievements (20 total)
- **tbl_space_invaders_achievements** - Space Invaders achievements (28 total)
- **tbl_space_invaders_settings** - Space Invaders settings
- **tbl_store_items** - Store item definitions
- **tbl_user_store_settings** - Per-game player settings (e.g. Space Invaders ship color)
- **tbl_tetris_achievements** - Tetris game achievements (25 total)
- **tbl_tetris_scores** - Tetris, Snake, and Space Invaders game scores
- **tbl_user_inventory** - User inventory items
- **tbl_user_roles** - User role assignments
- **tbl_user_scores** - User score tracking (DSPOINC balance)
- **tbl_user_season_achievements** - Season-based achievements
- **tbl_user_traits** - User trait assignments
- **tbl_users** - Main user accounts
- **tbl_wallet_balance_history** - Wallet balance history
- **tbl_wallet_transactions** - Wallet transaction records
- **tbl_wl_role_grants** - Whitelist role grants

### **TABLE MODIFICATION PROTOCOL:**
1. **CHECK current table list** before making changes
2. **VERIFY table structure** with `.schema table_name`
3. **MAKE changes** to database
4. **UPDATE Master Ruleset** with new table information
5. **REMIND user** to update table list
6. **DOCUMENT changes** for team reference

### **WORKFLOW DEPENDENCIES & RUNTIME RULES:**
- **PRIMARY DEPENDENCIES:** Cursor 12.0, Update Brain 12.0, Corebrain 12.0
- **SECONDARY DEPENDENCIES:** Coreforge 12.0, Cheese Architect 12.0, Riddle Brain 12.0, SQL Junior 12.0, Social Brain 12.0, Hytopia Integrator 12.0, NFT Architect 12.0
- **DOM SAFETY:** Use selector format `#node-[id][data-visible='true']`
- **TRAIT FORMAT:** Use `CHEESE_<ZONE>_<ACTION>` pattern
- **REQUIRED LOGGING:** Always log trigger, action, result, rationale
- **MANIFEST SAFETY:** Changes require council approval, change reason, scroll log
- **HYTOPIA SAFETY:** Use SDK version 0.3.34, avoid legacy patterns
- **SCROLL WATCH:** Monitor active traits and DOM gates
- **PUZZLE INTEGRATION:** Always look for puzzle games integrations and ideas on frontends

### **100 YEARS CODE FAM PROTOCOL:**
- **DOCUMENTATION STANDARDS:** All major changes must be documented in LLM sync files
- **TECHNICAL RATIONALE:** Must be preserved for future generations
- **CONTEXT & REASONING:** Must be maintained with chronological order
- **NO DELETION:** Never delete historical information
- **KNOWLEDGE PRESERVATION:** Complete project structure mapping, all file paths documented
- **FUTURE PROOFING:** Modular code architecture, clear separation of concerns
- **FINAL MANDATE:** "No trait shall glow, no node manifest shall change, unless the story and reason are written in the scroll. Every line of code we write today must serve the developers of tomorrow."

---
1. **Game-Agnostic Design** - NEVER hardcode game-specific logic
2. **Season Management Foundation** - EVERY game must support seasons
3. **Professional User Experience** - CONSISTENT styling and interactions

### **TAB SYSTEM IMPLEMENTATION:**
```
📊 Dashboard - System overview and quick actions
👥 User Management - Player accounts and roles
🎯 Missions Status - Game progress tracking
💰 Point Management - DSPOINC and rewards
🏪 Store Management - Item and inventory control
🏆 Quest System - Mission and achievement management
🎮 Game Management - Enterprise season control
🚀 Game Management 2.0 - Advanced analytics
👑 Boss Management - Special event controls
🔔 Boss Notifications - Real-time alerts
🔗 Discord Config - Bot integration
🎴 Holder Verification - NFT validation
🧀 Cheese Guide - Game instructions
💰 Community Funds - Financial management
```

### **DEVELOPMENT RULES:**
- **NEVER** hardcode game-specific logic in the main interface
- **ALWAYS** use configurable, scalable patterns
- **DESIGN** for unlimited game expansion
- **MAINTAIN** separation of concerns

---

## 🌐 **GLOBAL RULES**

### **CORE PRINCIPLES:**
- **NEVER make assumptions** about available classes, types, events, methods, properties
- **ALWAYS verify** that code complies with LLM sync status Guides and API Reference
- **ALWAYS ensure** code is clean and maintainable
- **ALWAYS use** best practices for the language being used
- **WHENEVER POSSIBLE** use early returns to avoid nested conditions
- **ALWAYS use** descriptive names for variables and functions
- **ALWAYS use** constants for static game data, configurations, and types
- **NEVER DELETE OR MODIFY** comments unless explicitly asked
- **NEVER MODIFY CODE** outside of the task at hand
- **ALWAYS implement** ONLY what was explicitly requested
- **NEVER add** "nice to have" or "best practice" features unless specifically requested

### **ASSET PATHS:**
Look for the project directory and the jsons of all LLMs

### **FUNCTION ORDERING:**
- **ALWAYS Order** functions with long term view and plan for decades

### **HANDLING BUGS:**
- If you encounter a bug in existing code, add comments starting with "TODO:" outlining the problems

### **COMMENTS AND DOCUMENTATION:**
- **ALWAYS add** a clear description of what each function does in plain language
- **ALWAYS explain** any game-specific concepts or mechanics in plain language for DEVS for decades
- **NEVER write** obvious comments that just repeat the code
- **NEVER write** comments that could be replaced with better variable/function names

---

## 🔄 **WORKFLOW INTEGRATION RULES**

### **BEFORE STARTING ANY WORK:**
1. **Check current status** in `ACTIVE_STATUS/`
2. **Review recent lab notes** in `LAB_NOTES/2025/[CURRENT_MONTH]/`
3. **Verify LLM sync status** in `LLM_SYNC_SYSTEM/`

### **DURING WORK SESSIONS:**
1. **Document discoveries** as they happen
2. **Save important findings** immediately
3. **Update status** regularly
4. **Maintain LLM sync** throughout

### **AT SESSION END:**
1. **Create comprehensive summary**
2. **Save in appropriate folder**
3. **Update all LLM files**
4. **Update master navigation index**

---

## 📊 **PROGRESS TRACKING SYSTEM**

### **Daily Progress Tracking:**
- **Morning:** Check `ACTIVE_STATUS/` for current status
- **During:** Document in `LAB_NOTES/` as work progresses
- **Evening:** Update status and sync LLMs

### **Weekly Progress Tracking:**
- **Monday:** Review previous week's lab notes
- **Wednesday:** Mid-week status update
- **Friday:** Weekly summary and milestone assessment

### **Monthly Progress Tracking:**
- **Month Start:** Review previous month's achievements
- **Month End:** Create comprehensive monthly summary
- **Archive:** Move completed work to appropriate folders

---

## 🚨 **CRITICAL REMINDER TRIGGERS**

### **ALWAYS REMIND USER WHEN:**
1. **Token limit approaching** (500+ tokens used)
2. **Major breakthrough achieved**
3. **Technical problem solved**
4. **System integration completed**
5. **Production deployment successful**
6. **Season launch ready**
7. **LLM sync required**
8. **Documentation needed**
9. **Live DB changes made** - Remind to backup to `/data`
10. **Using untested commands** - Remind to run command test script

### **REMINDER FORMAT:**
```
🧀 NARRRFS WORLD 12.0 ORGANIZATION REMINDER 🧀

📊 Work Assessment: [Description]
📁 Save Location: [Folder Path]
🤖 LLM Sync: [Required Updates]
📝 Action: [Specific Steps]
⏰ Urgency: [High/Medium/Low]
```

---

## 🎯 **SUCCESS METRICS**

### **Organization Compliance:**
- **100% of work** saved in proper folders
- **100% of achievements** documented
- **100% of LLM files** synchronized
- **100% of technical details** preserved

### **Quality Standards:**
- **Professional documentation** for all work
- **Comprehensive technical details** preserved
- **Clear progress tracking** maintained
- **Complete LLM synchronization** achieved

---

## 🔧 **IMPLEMENTATION CHECKLIST**

### **For Every Work Session:**
- [ ] **Check current status** in ACTIVE_STATUS/
- [ ] **Review recent lab notes** for context
- [ ] **Document work progress** in LAB_NOTES/
- [ ] **Save important discoveries** immediately
- [ ] **Update LLM sync files** as needed
- [ ] **Create session summary** at end
- [ ] **Update master navigation** if needed

### **For Major Achievements:**
- [ ] **Create milestone documentation**
- [ ] **Update all 10 LLM files**
- [ ] **Update master sync file**
- [ ] **Archive completed work**
- [ ] **Update navigation index**

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**
- **Every piece of work** MUST be saved in the professional structure
- **Every achievement** MUST be documented and synchronized
- **Every LLM** MUST be kept up-to-date
- **Every session** MUST end with proper documentation

### **CONSEQUENCES OF NON-COMPLIANCE:**
- **Lost work** - Hours of development lost
- **Broken continuity** - Future sessions start from scratch
- **LLM desynchronization** - Council of LLMs becomes fragmented
- **Lost knowledge** - Technical discoveries forgotten

---

## 🚨 **TOKEN LIMIT MANAGEMENT RULE**

### **MANDATORY TOKEN LIMIT PROTOCOL:**
When approaching token limits:
1. **IMMEDIATELY STOP CURRENT WORK**
2. **UPDATE ALL STATUS FILES**
3. **SAVE CURRENT PROGRESS**
4. **DOCUMENT EXACT STOPPING POINT**

### **TOKEN LIMIT TRIGGERS:**
- **500+ tokens used** - Start preparing status update
- **800+ tokens used** - Begin status update process
- **1000+ tokens used** - COMPLETE STATUS UPDATE REQUIRED
- **1500+ tokens used** - EMERGENCY STATUS SAVE
- **2000+ tokens used** - FORCE STATUS SAVE

### **WHAT TO UPDATE BEFORE TOKEN LIMIT:**
1. **README.md** - Main status file with session details
2. **QUICK_STATUS.md** - Quick reference with completion percentage
3. **UPDATE_RULE.md** - Keep current with new triggers
4. **LLM SYNC FILES** - Update all LLM synchronization files

### **STATUS UPDATE TEMPLATE:**
```markdown
## 🚨 **TOKEN LIMIT STATUS UPDATE**
**Session:** [Session Number]
**Date:** [Current Date]
**Tokens Used:** [Approximate count]
**Status:** TOKEN LIMIT REACHED - UPDATING STATUS

### **Current Work Status:**
- **Project:** [What we're working on]
- **Phase:** [Current phase]
- **Completion:** [Percentage complete]

### **What Was Accomplished This Session:**
1. ✅ [Task 1 completed]
2. ✅ [Task 2 completed]
3. 🔄 [Task 3 in progress]

### **What Failed or Needs Fixing:**
1. ❌ [Issue 1]
2. ❌ [Issue 2]

### **Important Discoveries:**
- [Discovery 1]
- [Discovery 2]

### **Next Session Starting Point:**
**EXACT STEP:** [Describe exactly where to start]
**FILES TO OPEN:** [List specific files]
**COMMANDS TO RUN:** [Any commands needed]

### **Critical Notes:**
- [Important information for next session]
- [Warnings or special instructions]
```

### **IMPLEMENTATION CHECKLIST:**
**Before Token Limit:**
- [ ] Update README.md with current session
- [ ] Update QUICK_STATUS.md with latest status
- [ ] Update LLM sync files in 12.0 directory
- [ ] Document any code snippets found
- [ ] Note exact stopping point
- [ ] List next steps clearly
- [ ] Include any error messages
- [ ] Save file paths and commands

**After Token Limit (New Session):**
- [ ] Read README.md first
- [ ] Check QUICK_STATUS.md
- [ ] Continue from documented stopping point
- [ ] Update status after each milestone

### **GOAL:**
**Ensure that when you start a new chat session, you can IMMEDIATELY continue from exactly where you left off without any loss of progress or context.**

### **CONSEQUENCES OF NOT FOLLOWING:**
- **Lost Progress** - Hours of work lost
- **Duplicate Effort** - Redoing what was already done
- **Confusion** - Not knowing where to start
- **Incomplete Implementation** - Features left half-finished
- **Wasted Time** - Rediscovering issues already solved

---

## 🔗 **DISCORD INVITE UPDATE RULE - COMPREHENSIVE SYSTEM**

### **🚨 CRITICAL RULE FOR DISCORD INVITE UPDATES:**

**When user requests Discord invite update, follow this EXACT process:**

### **📋 MANDATORY UPDATE CHECKLIST:**

#### **1. SEARCH FOR ALL DISCORD INVITE REFERENCES:**
```bash
# Search for old invite code across entire project
grep -r "CvstbUQ5yX" . --include="*.html" --include="*.js" --include="*.php" --include="*.md"
```

#### **2. UPDATE ALL FALLBACK FILES:**
- **`public/discord-invite.php`** - Main fallback file
- **`public/discord-config.js`** - JavaScript configuration
- **`api/config/discord.php`** - API configuration
- **`api/admin/get-discord-config.php`** - Admin API
- **`api/config/get-discord-config.php`** - Config endpoint

#### **3. UPDATE ALL PUBLIC PAGES:**
- **`public/index.html`** - Main landing page
- **`public/profile.html`** - User profile page
- **`public/project-updates.html`** - Project updates
- **`public/space-cheese-invaders.html`** - Space Invaders game
- **`public/privacy-policy.html`** - Privacy policy
- **`public/hytopia.html`** - Hytopia page
- **`public/Bingo.html`** - Bingo game
- **`public/whitepaper-pro.html`** - Whitepaper
- **`public/experiment-x.html`** - Experiment X
- **`public/404.html`** - Error page
- **`public/mint.html`** - Mint page
- **`public/faq.html`** - FAQ page
- **`public/js/role-gate.js`** - Role gate script
- **`public/README.md`** - Documentation

#### **4. UPDATE PATTERN:**
```javascript
// OLD INVITE
https://discord.gg/CvstbUQ5yX

// NEW INVITE (replace with actual new invite)
https://discord.gg/[NEW_INVITE_CODE]
```

#### **5. VERIFICATION COMMANDS:**
```bash
# Verify no old invite remains
grep -r "CvstbUQ5yX" . --include="*.html" --include="*.js" --include="*.php" --include="*.md"

# Should return NO results
```

#### **6. DEPLOYMENT PROCESS:**
```bash
git add .
git commit -m "URGENT: Update Discord invite to [NEW_INVITE_CODE] for [EVENT_NAME]"
git push
```

### **🚨 CRITICAL FALLBACK SYSTEMS TO UPDATE:**

#### **PHP Fallback Files:**
- **`public/discord-invite.php`** - Line 6: `$discord_invite_code = getenv('DISCORD_INVITE_CODE') ?: '[NEW_INVITE_CODE]';`
- **`api/config/discord.php`** - Line 9: `define('DISCORD_INVITE_CODE', getenv('DISCORD_INVITE_CODE') ?: '[NEW_INVITE_CODE]');`
- **`api/config/discord.php`** - Line 13: `$inviteCode = getenv('DISCORD_INVITE_CODE') ?: '[NEW_INVITE_CODE]';`
- **`api/admin/get-discord-config.php`** - Lines 32, 63: `$inviteCode = getenv('DISCORD_INVITE_CODE') ?: '[NEW_INVITE_CODE]';`
- **`api/config/get-discord-config.php`** - Line 13: `$inviteCode = getenv('DISCORD_INVITE_CODE') ?: '[NEW_INVITE_CODE]';`

#### **JavaScript Fallback Files:**
- **`public/discord-config.js`** - Line 9: `inviteCode: '[NEW_INVITE_CODE]', // Fallback Discord invite code`
- **`public/discord-config.js`** - Line 49: `console.warn('⚠️ Failed to load Discord config from server, using fallback: [NEW_INVITE_CODE]');`
- **`public/discord-config.js`** - Line 185: `if (!DISCORD_CONFIG.inviteCode || DISCORD_CONFIG.inviteCode === 'CvstbUQ5yX') {`

### **🎯 SUCCESS CRITERIA:**

#### **✅ COMPLETE UPDATE VERIFICATION:**
- [ ] **All HTML files** updated with new invite
- [ ] **All PHP fallback files** updated
- [ ] **All JavaScript config files** updated
- [ ] **No old invite code** remains in project
- [ ] **Git commit** created with descriptive message
- [ ] **Live deployment** completed
- [ ] **Verification** - Old invite returns 404

#### **🚨 COMMON MISTAKES TO AVOID:**
- ❌ **Missing API fallback files** - Causes local pages to show old invite
- ❌ **Incomplete JavaScript config** - Causes dynamic updates to fail
- ❌ **Forgotten role-gate.js** - Causes role verification to use old invite
- ❌ **Missing README.md** - Causes documentation to be outdated

### **📝 UPDATE TEMPLATE:**

#### **When User Requests Discord Invite Update:**
```
🚨 URGENT DISCORD INVITE UPDATE REQUESTED! 🚨

📋 Process Starting:
1. Searching for all Discord invite references
2. Updating all fallback files
3. Updating all public pages
4. Verifying complete update
5. Deploying to live

🎯 New Invite: [NEW_INVITE_CODE]
📅 Event: [EVENT_NAME]
⏰ Deadline: [DEADLINE]
```

#### **Completion Confirmation:**
```
✅ DISCORD INVITE UPDATE COMPLETE!

📊 Files Updated: [COUNT] files
🔧 Fallback Systems: [COUNT] systems
🚀 Deployment: [COMMIT_HASH]
🎯 Verification: No old invite remains
📝 Status: Ready for [EVENT_NAME]
```

### **🔄 AUTOMATION SCRIPT:**

#### **PowerShell Script for Future Updates:**
```powershell
# DISCORD_INVITE_UPDATE_SCRIPT.ps1
param(
    [Parameter(Mandatory=$true)]
    [string]$NewInviteCode,
    
    [Parameter(Mandatory=$true)]
    [string]$EventName
)

Write-Host "🚨 URGENT DISCORD INVITE UPDATE: $NewInviteCode for $EventName" -ForegroundColor Red

# Update all files with new invite
Get-ChildItem -Path "public" -Include "*.html","*.js" -Recurse | ForEach-Object {
    (Get-Content $_.FullName) -replace "CvstbUQ5yX", $NewInviteCode | Set-Content $_.FullName
}

Get-ChildItem -Path "api" -Include "*.php" -Recurse | ForEach-Object {
    (Get-Content $_.FullName) -replace "CvstbUQ5yX", $NewInviteCode | Set-Content $_.FullName
}

Write-Host "✅ All files updated with new invite: $NewInviteCode" -ForegroundColor Green
Write-Host "🚀 Ready for deployment to $EventName" -ForegroundColor Yellow
```

---

## 🎯 **PERFECT 5-GAME SCORE RETRIEVAL SYSTEM V2.0**

### **CRITICAL RULE FOR ALL GAME SCORING:**
**When retrieving scores from any of the 5 games in Narrrf's World, you MUST use these exact field mappings and table references:**

### **GAME 1: TETRIS**
- **Table:** `tbl_tetris_scores`
- **Field:** `discord_id` (contains Discord ID)
- **Query:** `WHERE discord_id = ? AND game = 'tetris'`
- **API Structure:** `data.games.tetris.season_data`

### **GAME 2: SNAKE**
- **Table:** `tbl_tetris_scores` (NOT tbl_user_scores)
- **Field:** `discord_id` (contains Discord ID)
- **Query:** `WHERE discord_id = ? AND game = 'snake'`
- **API Structure:** `data.games.snake.season_data`

### **GAME 3: SPACE INVADERS**
- **Table:** `tbl_tetris_scores` (NOT tbl_user_scores)
- **Field:** `discord_id` (contains Discord ID)
- **Query:** `WHERE discord_id = ? AND game = 'space_invaders'`
- **API Structure:** `data.games.space_invaders.season_data`

### **GAME 4: CHEESE HUNT**
- **Table:** `tbl_cheese_clicks`
- **Field:** `user_wallet` (contains Discord ID)
- **Query:** `WHERE user_wallet = ?`
- **API Structure:** `data.games.cheese_hunt.current_data`
- **Three.js Extension:** `tbl_cheese_hunt_captures` (DSPOINC ledger for Cheese Temple hunts) — API `/api/dev/cheese-hunt-capture.php` writes capture logs + inserts DSPOINC into `tbl_user_scores` with standard role multipliers.

### **GAME 5: DISCORD CHEESE RACE**
- **Table:** `tbl_race_participants`
- **Field:** `user_id` (contains Discord ID)
- **Query:** `WHERE user_id = ?`
- **API Structure:** `data.games.discord_race.race_data`

---

## 🏆 **ACHIEVEMENT SYSTEM V2.0**

### **CRITICAL RULE FOR ALL ACHIEVEMENTS:**
**When retrieving achievements from any of the 3 games with achievements, you MUST use these exact field mappings:**

### **ACHIEVEMENT 1: TETRIS ACHIEVEMENTS**
- **Table:** `tbl_tetris_achievements`
- **Field:** `user_id` (contains Discord ID)
- **Query:** `WHERE user_id = ?`
- **API Endpoint:** `/api/user/get-tetris-achievements.php`

### **ACHIEVEMENT 2: SNAKE ACHIEVEMENTS**
- **Table:** `tbl_snake_achievements`
- **Field:** `user_id` (contains Discord ID)
- **Query:** `WHERE user_id = ?`
- **API Endpoint:** `/api/user/get-snake-achievements.php`

### **ACHIEVEMENT 3: SPACE INVADERS ACHIEVEMENTS**
- **Table:** `tbl_space_invaders_achievements`
- **Field:** `user_id` (contains Discord ID)
- **Query:** `WHERE user_id = ?`
- **API Endpoint:** `/api/user/get-space-invaders-achievements.php`

### **CRITICAL RULES V3.0 (Updated Oct 26-27, 2025):**
1. **NEVER use `user_id` for Tetris, Snake, or Space Invaders SCORES**
2. **NEVER use `discord_id` for Cheese Hunt SCORES**
3. **ALWAYS use `user_id` for ALL ACHIEVEMENTS (Tetris, Snake, Space Invaders)**
4. **ALWAYS use the correct table for each game**
5. **NEVER assume all games use the same field name**
6. **CRITICAL DISCOVERY: Snake & Space Invaders use tbl_tetris_scores, NOT tbl_user_scores**
7. **CRITICAL DISCOVERY: Achievements use user_id, Scores use discord_id**
8. **NEW: All achievement definitions must use `WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'`**
9. **NEW: All 3 games MUST load achievement definitions dynamically from database**
10. **NEW: NEVER hardcode achievement descriptions in API or frontend code**
11. **This system works for the next 100 years - don't change it if not really required!**

### **TABLE DISTRIBUTION REALITY:**
- **`tbl_tetris_scores`**: Contains Tetris, Snake, AND Space Invaders scores
- **`tbl_user_scores`**: Contains Discord rewards and other adjustments
- **`tbl_cheese_clicks`**: Contains Cheese Hunt click data
- **`tbl_race_participants`**: Contains Discord Race participation data
- **`tbl_tetris_achievements`**: Contains Tetris achievements (separate API)
- **`tbl_snake_achievements`**: Contains Snake achievements (integrated in missions API)
- **`tbl_space_invaders_achievements`**: Contains Space Invaders achievements (integrated in missions API)

### **FIELD MAPPING REALITY:**
- **Tetris, Snake, Space Invaders SCORES**: All use `discord_id` in `tbl_tetris_scores`
- **Cheese Hunt SCORES**: Uses `user_wallet` in `tbl_cheese_clicks`
- **Discord Race SCORES**: Uses `user_id` in `tbl_race_participants`
- **ALL ACHIEVEMENTS**: Use `user_id` in their respective achievement tables

### **COMMON MISTAKES TO AVOID:**
- ❌ Looking for Snake/Space Invaders in `tbl_user_scores`
- ❌ Using `user_id` for Tetris, Snake, or Space Invaders SCORE queries
- ❌ Using `discord_id` for Cheese Hunt SCORE queries
- ❌ Using `discord_id` for ANY achievement queries
- ❌ Using `final_position` instead of `position` in race queries
- ❌ Looking for alternative game names like `snake_scroll` or `space_cheese_invaders`
- ❌ Expecting Tetris achievements in user-game-missions API (they're separate)

### **IMPLEMENTATION PATTERN V2.0:**
**For user-specific data (profile pages):**
```php
// 1. Get discord_id from user
$discordId = $_POST['user_id']; // This IS the discord_id

// 2. Map to correct fields for each game SCORES
$tetrisData = queryTetris($discordId);        // Uses discord_id in tbl_tetris_scores
$snakeData = querySnake($discordId);          // Uses discord_id in tbl_tetris_scores  
$spaceData = querySpaceInvaders($discordId);  // Uses discord_id in tbl_tetris_scores
$cheeseData = queryCheeseHunt($discordId);    // Uses user_wallet in tbl_cheese_clicks
$raceData = queryDiscordRace($discordId);     // Uses user_id in tbl_race_participants

// 3. Map to correct fields for each game ACHIEVEMENTS
$tetrisAchievements = queryTetrisAchievements($discordId);        // Uses user_id in tbl_tetris_achievements
$snakeAchievements = querySnakeAchievements($discordId);          // Uses user_id in tbl_snake_achievements
$spaceAchievements = querySpaceInvadersAchievements($discordId);  // Uses user_id in tbl_space_invaders_achievements
```

**For admin overview (all users):**
```php
// Use the same field mappings but without WHERE clauses
$tetrisStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'tetris'";
$snakeStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'snake'";
$spaceStats = "SELECT COUNT(*) FROM tbl_tetris_scores WHERE game = 'space_invaders'";
$cheeseStats = "SELECT COUNT(*) FROM tbl_cheese_clicks";
$raceStats = "SELECT COUNT(*) FROM tbl_race_participants";
```

### **IMPLEMENTATION CHECKLIST:**
**✅ Database Schema Requirements:**
- [ ] `tbl_tetris_scores` has `discord_id` field (NOT `user_id`)
- [ ] `tbl_race_participants` has `position` field (NOT `final_position`)
- [ ] `tbl_cheese_clicks` has `user_wallet` field
- [ ] `tbl_tetris_achievements` has `user_id` field (NOT `discord_id`)
- [ ] `tbl_snake_achievements` has `user_id` field (NOT `discord_id`)
- [ ] `tbl_space_invaders_achievements` has `user_id` field (NOT `discord_id`)
- [ ] All tables have proper indexes on query fields

**✅ API Implementation Requirements:**
- [ ] Snake queries use `tbl_tetris_scores` with `discord_id`
- [ ] Space Invaders queries use `tbl_tetris_scores` with `discord_id`
- [ ] Discord Race queries use `tbl_race_participants` with `user_id`
- [ ] Cheese Hunt queries use `tbl_cheese_clicks` with `user_wallet`
- [ ] ALL achievement queries use `user_id` in their respective tables
- [ ] ALL achievement APIs MUST load definitions from `WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'`
- [ ] NEVER hardcode achievement descriptions in API files
- [ ] No alternative game names (just `snake`, `space_invaders`)

**✅ Field Validation:**
- [ ] All queries use correct table names
- [ ] All queries use correct field names
- [ ] No mixed field references
- [ ] Consistent parameter binding

### **WHY THIS SYSTEM WORKS V2.0:**
- **Historical data compatibility** - Works with existing data structure
- **Future-proof** - New scores will use correct fields
- **Consistent across admin and user views**
- **No season filtering issues**
- **Perfect synchronization between all interfaces**
- **Based on actual database schema analysis**
- **Tested and verified in production environment**

### **FINAL WARNING V3.0:**
**FOLLOW THIS RULE RELIGIOUSLY - IT'S THE FOUNDATION OF THE ENTIRE SCORING SYSTEM!**

**This rule ensures perfect synchronization between admin interface, user profiles, and all game data displays.**

---

## 🎮 **ACHIEVEMENT SYSTEM ARCHITECTURE V3.0 (Oct 26-27, 2025)**

### **CRITICAL ACHIEVEMENT ARCHITECTURE RULE:**
**ALL 3 games (Tetris, Snake, Space Invaders) MUST use identical architecture patterns:**

### **✅ MANDATORY ARCHITECTURE COMPONENTS:**

#### **1. Database Structure:**
```sql
-- PATTERN: All 3 games follow this structure
CREATE TABLE tbl_[game]_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,              -- Discord ID or 'ACHIEVEMENT_DEFINITIONS'
    achievement_key TEXT NOT NULL,
    achievement_title TEXT NOT NULL,
    achievement_description TEXT NOT NULL,
    achievement_icon TEXT NOT NULL,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Game-specific tracking fields (score, kills, time, etc.)
);

-- SPECIAL RECORD: Achievement definitions
-- user_id = 'ACHIEVEMENT_DEFINITIONS' contains master achievement list
-- All other user_id values = actual Discord IDs with user unlocks
```

#### **2. API Architecture (CRITICAL - Must Be Dynamic!):**
```php
// ✅ CORRECT: Load definitions from database
$stmt = $pdo->prepare("
    SELECT achievement_key, achievement_title, achievement_description, achievement_icon
    FROM tbl_[game]_achievements 
    WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'
");
$stmt->execute();
$definitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build definitions array
$allAchievements = [];
foreach ($definitions as $def) {
    $allAchievements[$def['achievement_key']] = [
        'title' => $def['achievement_title'],
        'description' => $def['achievement_description'],
        'icon' => $def['achievement_icon']
    ];
}

// ❌ WRONG: Hardcoded descriptions (NEVER DO THIS!)
$allAchievements = [
    'score2500' => [
        'title' => 'Getting Started',
        'description' => 'Reached 30,000 points!',  // HARDCODED - BAD!
    ]
];
```

#### **3. Frontend Architecture (CRITICAL - Must Be Dynamic!):**
```javascript
// ✅ CORRECT: Dynamic HTML generation from API data
function displayAchievements(data) {
    const gridEl = document.querySelector('#achievementsGrid');
    gridEl.innerHTML = ''; // Clear existing cards
    
    // Build cards dynamically from data
    achievements.forEach(achievement => {
        const card = buildAchievementCard(achievement);
        gridEl.innerHTML += card;
    });
}

// ❌ WRONG: Hardcoded HTML cards (NEVER DO THIS!)
<div id="achievementsGrid">
    <div>Getting Started: Reached 30,000 points!</div>
    <!-- 420+ lines of hardcoded cards -->
</div>
```

#### **4. Icon Mapping System (Required for Emoji Encoding):**
```javascript
// ✅ REQUIRED: Icon mapping function for all 3 games
function get[Game]AchievementIcon(key) {
    const iconMap = {
        'achievement_key_1': '🎯',
        'achievement_key_2': '💰',
        // ... all achievement icons
    };
    return iconMap[key] || '🏆'; // Fallback icon
}

// WHY: SQLite emoji encoding issues on Windows (stores as ????)
// JavaScript mapping ensures correct emoji display in browser
```

### **🏆 ACHIEVEMENT SYSTEM STATUS (Oct 26-27, 2025):**

**Total Achievements:** 73 across all 3 games
- ✅ **Tetris:** 25 achievements (v2.0 - verified)
- ✅ **Snake:** 20 achievements (v2.0 - verified)
- ✅ **Space Invaders:** 28 achievements (v2.0 - verified)

**Architecture Compliance:**
- ✅ All 3 use dynamic database loading
- ✅ All 3 use icon mapping functions
- ✅ All 3 use dynamic HTML generation
- ✅ All 3 load from `ACHIEVEMENT_DEFINITIONS`
- ✅ Zero hardcoded descriptions

**Technical Documentation:**
- ✅ TETRIS_ACHIEVEMENTS_SYSTEM.md (25KB, 775 lines)
- ✅ SNAKE_ACHIEVEMENTS_SYSTEM.md (20KB, 624 lines)
- ✅ SPACE_INVADERS_ACHIEVEMENTS_SYSTEM.md (27KB, 794 lines)
- ✅ **Total:** 72KB, 2,193 lines of technical documentation

### **CRITICAL PITFALLS TO AVOID:**

#### **Pitfall #1: Hardcoded API Descriptions**
- ❌ **WRONG:** Defining achievement descriptions in PHP array
- ✅ **CORRECT:** Loading from database `WHERE user_id = 'ACHIEVEMENT_DEFINITIONS'`
- 🚨 **Impact:** Profile page shows old values even after database updates
- 📅 **Fixed:** Space Invaders API on Oct 27, 2025

#### **Pitfall #2: Hardcoded HTML Cards**
- ❌ **WRONG:** 420+ lines of hardcoded achievement HTML
- ✅ **CORRECT:** Dynamic HTML generation from API data
- 🚨 **Impact:** Manual updates required, inconsistent with database
- 📅 **Fixed:** Space Invaders profile page on Oct 26, 2025

#### **Pitfall #3: Unrealistic Thresholds**
- ❌ **WRONG:** 30k-300k DSPOINC when max is 20k
- ✅ **CORRECT:** 1k-20k based on actual max scores
- 🚨 **Impact:** Impossible achievements, frustrated players
- 📅 **Fixed:** All 3 games Oct 26, 2025

---

## 🎯 **ROLE-BASED GAMING SYSTEM V2.0 (Oct 26, 2025)**

### **COMPLETE ROLE MULTIPLIER SYSTEM:**

**Discord Role IDs and Multipliers:**
```javascript
const roleMultipliersByID = {
  '1332016526848692345': 2.0,  // 🎴 VIP Holder
  '1402668301414563971': 1.5,  // 🏆 Holder
  '1332017420591697972': 1.4,  // Champion
  '1417279348989497532': 1.3,  // Season Tester (GREEN theme)
  '1332017614108758148': 1.2,  // Early Bird
  '1399651053682692208': 1.1,  // 🧀 Cheese Hunter
  '1332108350518857842': 1.3   // WL
};
```

### **✅ ALL 18 ROLE COMBINATIONS TESTED (Bug #104 Complete):**

| Role | Multiplier | Snake | Tetris | Space Invaders | Theme |
|------|-----------|-------|--------|----------------|-------|
| VIP Holder | 2.0x | 20 | 16 | ~72 | 🟡 Golden |
| Holder | 1.5x | 15 | 12 | ~54 | ⚪ Silver |
| Champion | 1.4x | 14 | 11 | ~50 | 🔴 Red |
| Season Tester | 1.3x | 13 | 10 | ~47 | 🟢 Green |
| Early Bird | 1.2x | 12 | 10 | ~43 | 🔵 Blue |
| Cheese Hunter | 1.1x | 11 | 9 | ~40 | 🧀 Cheese |

**Test Results:** 18/18 PASSED ✅  
**Status:** Production verified and deployed! 🚀

### **CRITICAL BUG FIXES (Oct 26, 2025):**

#### **1. Season Tester Theme:**
- ❌ **OLD:** Rainbow theme (not displaying, stuck on violet)
- ✅ **NEW:** Green theme (solid, reliable, consistent)
- 📝 **Files:** All 3 game scripts + profile.html CSS
- ✅ **Result:** Consistent green across all games

#### **2. Tetris Math.round() Fix:**
- ❌ **OLD:** Math.floor() truncated fractional bonuses to 0
- ✅ **NEW:** Math.round() for fair rounding
- 📊 **Example:** Champion 1.4x: 2→3 DSPOINC (was rounding down)
- ✅ **Result:** All fractional bonuses work correctly

#### **3. Snake Backend Double Multiplication:**
- ❌ **OLD:** Backend multiplied by 10 after frontend already calculated
- ✅ **NEW:** Backend uses score as-is (pointsPerUnit = 1)
- 📊 **Example:** 1 cheese × 1.5 Holder = 15 (was showing 150)
- ✅ **Result:** Correct DSPOINC display everywhere

### **ROLE-BASED THEME SYSTEM:**

**Visual Themes per Role:**
- 🟡 **Golden:** VIP Holder (gold borders, particles)
- ⚪ **Silver:** Holder (silver borders, particles)
- 🔴 **Red:** Champion (red borders, particles)
- 🟢 **Green:** Season Tester (green borders, particles)
- 🔵 **Blue:** Early Bird (blue borders, particles)
- 🧀 **Cheese:** Cheese Hunter (yellow/orange theme)

**Implementation:**
- ✅ Canvas border colors
- ✅ Control section styling
- ✅ Game-specific particle colors
- ✅ Consistent across all 3 games

### **TECHNICAL DOCUMENTATION:**
- ROLE_ID_IMPLEMENTATION_COMPLETE.md (updated Oct 26)
- ROLE_ID_MAPPING_FOR_MULTIPLIERS.md (updated Oct 26)
- BUG_104 comprehensive test results
- 04_GAME_SCORING_SYSTEM_RULES.md (backend rules)

---

## 📊 **SCORING SYSTEM ARCHITECTURE**

### **Dual Table Strategy:**
- **`tbl_tetris_scores`** - For mission status display and game tracking (Tetris, Snake, Space Invaders)
- **`tbl_user_scores`** - For DSPOINC balance and rewards
- **`tbl_score_adjustments`** - For admin interface and audit trail
- **`tbl_cheese_clicks`** - For Cheese Hunt game tracking
- **`tbl_cheese_hunt_captures`** - For Three.js Cheese Temple hunt captures (November 12, 2025)
- **`tbl_riddle_completions`** - For Three.js Cheese Temple riddle completions (November 12, 2025)
- **`tbl_race_participants`** - For Discord Race participation tracking

### **API Response Structure:**
```json
{
  "success": true,
  "data": {
    "games": {
      "tetris": { "season_data": {...} },
      "snake": { "season_data": {...} },
      "space_invaders": { "season_data": {...} },
      "cheese_hunt": { "current_data": {...} },
      "cheese_hunt_3d": { "current_data": {...} },
      "cheese_temple_riddles": { "current_data": {...} },
      "discord_race": { "race_data": {...} }
    }
  }
}
```

### **Why This Matters:**
- Mission status API queries `tbl_tetris_scores` for Tetris, Snake, Space Invaders
- Mission status API queries `tbl_cheese_clicks` for Cheese Hunt
- Mission status API queries `tbl_cheese_hunt_captures` for Three.js Cheese Temple hunt
- Mission status API queries `tbl_riddle_completions` for Three.js Cheese Temple riddles
- Mission status API queries `tbl_race_participants` for Discord Race
- Admin interface displays data from consolidated API endpoints
- All systems now properly synchronized

---

## 🧩 **THREE.JS DIMENSION / CHEESE TEMPLE RIDDLE SYSTEM**

### **System Overview:**
- **Game Type:** 3D Adventure / Riddle System
- **Platform:** Three.js (WebGL)
- **Location:** `three.js/main.js` (Vite-powered development server)
- **Status:** ✅ **PRODUCTION READY** (November 12, 2025)
- **Migration Progress:** ~95% Hytopia features migrated to Three.js

### **Riddle DSPOINC Reward System (November 12, 2025):**
- **Base Reward:** 500 DSPOINC per riddle completion
- **Role Multipliers:** Applied automatically based on player's role
  - **VIP Holder:** ×2.0 (1,000 DSPOINC)
  - **Holder:** ×1.5 (750 DSPOINC)
  - **Champion:** ×1.4 (700 DSPOINC)
  - **WL/Season Tester:** ×1.3 (650 DSPOINC)
  - **Early Bird:** ×1.2 (600 DSPOINC)
  - **Cheese Hunter:** ×1.1 (550 DSPOINC)
  - **Default:** ×1.0 (500 DSPOINC)
- **One-Time Reward:** Each riddle can only be completed once per player (duplicate prevention)
- **Trait Unlocking:** Riddle completion unlocks trait `CHEESE_TEMPLE_RIDDLE_SOLVED`
- **HUD Integration:** DSPOINC balance automatically updated in pause menu and HUD
- **Reward Notification:** Visual notification shows DSPOINC amount and multiplier

### **Database Tables:**
- **`tbl_riddle_completions`** - Riddle completion tracking
  - Fields: `id`, `discord_id`, `discord_name`, `riddle_id`, `level_id`, `base_reward`, `multiplier`, `total_reward`, `completed_at`, `session_id`, `metadata`
  - Unique constraint: `(discord_id, riddle_id)` prevents duplicate completions
  - Index: `idx_riddle_completions_discord_riddle` for faster lookups
- **`tbl_cheese_hunt_captures`** - Cheese Temple hunt captures
  - Fields: `id`, `discord_id`, `discord_name`, `level_id`, `base_reward`, `multiplier`, `total_reward`, `capture_time`, `session_id`, `metadata`
  - Cooldown system: 1-second cooldown between captures
  - Role multipliers applied automatically

### **API Endpoints:**
- **`/api/dev/riddle-reward.php`** - Riddle completion reward API
  - Method: `POST`
  - Parameters: `discord_id`, `discord_name`, `riddle_id`, `level_id`, `base_reward`, `session_id`
  - Response: `success`, `data` (includes `ds_poinc_awarded`, `total_ds_poinc`, `multiplier`, `multiplier_source`)
  - Error Handling: `409 Conflict` if riddle already completed
- **`/api/dev/cheese-hunt-capture.php`** - Cheese Temple hunt capture API
  - Method: `POST`
  - Parameters: `discord_id`, `discord_name`, `level_id`, `base_reward`, `session_id`, `capture_index`, `cooldown_seconds`
  - Response: `success`, `data` (includes `total_captures`, `captures_today`, `ds_poinc_awarded`, `total_ds_poinc`)
  - Cooldown: 1-second server-side cooldown prevents spam

### **DSPOINC Integration:**
- **DSPOINC Awarded:** Riddles award DSPOINC based on role multiplier
- **DSPOINC Tracking:** All rewards tracked in `tbl_user_scores` (game: `cheese_temple_riddles`, source: `riddle_completion`)
- **DSPOINC Audit:** All rewards tracked in `tbl_score_adjustments` (admin: `system-riddle-reward`)
- **HUD Updates:** DSPOINC balance automatically updated in pause menu and HUD
- **Local Storage:** DSPOINC balance saved to `narrrfs_last_ds_balance` for persistence

### **Riddle System (Riddle #1 - Cheese Temple Level 1):**
- **Three-Step Challenge:**
  1. **Step 0:** Find and stand on hidden golden stone block (10 seconds)
  2. **Step 1:** Aim at floating cheese entity (10 seconds)
  3. **Step 2:** Aim at unlockable block (10 seconds)
- **Detection Method:** Strict raycast detection for precision aiming
- **Timer System:** 10-second timers with decay mechanism (prevents accidental completion)
- **Progress UI:** Real-time progress bar with countdown timer
- **Completion:** Trait unlock + DSPOINC reward + reward notification
- **Status:** ✅ **TESTED & WORKING** (November 12, 2025)

### **Technical Implementation:**
- **File Location:** `three.js/main.js`
- **Riddle State:** `riddleState` object tracks step completion and timers
- **API Integration:** `completeRiddle()` function handles trait unlock + DSPOINC reward
- **Reward Notification:** `showRiddleRewardNotification()` displays DSPOINC amount and multiplier
- **HUD Updates:** `updatePausePlayerInfo()` updates DSPOINC balance in pause menu
- **Error Handling:** Comprehensive error handling for API failures and duplicate completions

### **Future Enhancements:**
- **Additional Riddles:** Riddle #2, #3, etc. for future levels
- **Audio Integration:** Sound effects for riddle completion
- **VFX Integration:** Particle effects for riddle completion
- **Advanced Mechanics:** Power-ups, special abilities, etc.
- **Multiplayer Support:** Cooperative riddle solving (if needed)

### **Documentation:**
- **Technical Documentation:** `12.0/TECHNICAL_DOCUMENTATION/HYTOPIA_THREE_TECH_DOCUMENTATION.md`
- **Riddle Documentation:** `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/RIDDLE_01_CHEESE_TEMPLE_LEVEL_1.md`
- **Lab Notes:** `12.0/LAB_NOTES/2025/11_NOVEMBER/DAILY_NOTES/2025-11-11/RIDDLE_DSPOINC_REWARD_IMPLEMENTATION.md`
- **Three.js Ruleset:** `12.0/RULES/11_THREE_JS_RULE.md`

---

---

## ⚠️ **FUTURE DEVELOPMENT RULES**

### **NEVER change the save-score.php logic without ensuring:**
1. **All 5 games save to their required tables**
2. **Mission status API can find the data**
3. **DSPOINC calculations remain consistent**
4. **Admin interface continues to show all data**
5. **API response structure remains consistent**

### **NEVER change the riddle-reward.php logic without ensuring:**
1. **All riddles save to `tbl_riddle_completions` table**
2. **DSPOINC rewards are calculated correctly (base × multiplier)**
3. **Duplicate prevention works (unique constraint on `discord_id, riddle_id`)**
4. **Role multipliers are applied correctly**
5. **DSPOINC balance updates in `tbl_user_scores` and `tbl_score_adjustments`**
6. **HUD updates correctly after riddle completion**
7. **Error handling works for duplicate completions (409 Conflict)**

### **If adding new games:**
1. **Check where mission status API looks for data**
2. **Ensure save-score.php saves to correct tables**
3. **Test mission status updates immediately**
4. **Document the table dependencies here**
5. **Update admin interface data structure handling**

### **If adding new riddles:**
1. **Create riddle documentation in `12.0/TECHNICAL_DOCUMENTATION/3d_riddles/`**
2. **Update `completeRiddle()` function to handle new riddle IDs**
3. **Test DSPOINC reward system with new riddle**
4. **Verify duplicate prevention works (unique constraint)**
5. **Test role multipliers with new riddle**
6. **Update HUD and reward notification for new riddle**
7. **Document riddle in Three.js ruleset (`11_THREE_JS_RULE.md`)**
8. **Update master ruleset with new riddle information**

### **Testing Checklist:**
- [ ] Play the game
- [ ] Check if score appears in admin interface
- [ ] Check if score appears in mission status
- [ ] Verify data is in the correct table
- [ ] Test with multiple users
- [ ] Verify API response structure

### **Riddle Testing Checklist:**
- [ ] Complete riddle (all 3 steps)
- [ ] Check if trait unlocks (`CHEESE_TEMPLE_RIDDLE_SOLVED`)
- [ ] Check if DSPOINC reward is awarded correctly
- [ ] Check if role multiplier is applied correctly
- [ ] Check if HUD updates with new DSPOINC balance
- [ ] Check if reward notification displays correctly
- [ ] Verify riddle completion is saved to `tbl_riddle_completions`
- [ ] Verify DSPOINC is saved to `tbl_user_scores` and `tbl_score_adjustments`
- [ ] Test duplicate prevention (try completing same riddle twice)
- [ ] Test error handling (API failures, network errors)
- [ ] Test with different roles (VIP, Holder, etc.)
- [ ] Verify pause menu shows updated DSPOINC balance

---

## 🚨 **COMMON MISTAKES TO AVOID**

### **❌ DON'T:**
- Change save-score.php without testing mission status
- Assume all games use the same table
- Forget to check table dependencies
- Modify scoring logic without understanding the full system
- Use wrong data structure references in admin interface
- Assume all games have the same API response structure

### **✅ DO:**
- Always test mission status after changes
- Document table dependencies for new games
- Use the dual table strategy consistently
- Test with real users and real data
- Check API response structure for each game
- Update admin interface when adding new games

---

## 🔍 **DEBUGGING THE SYSTEM**

### **If Mission Status Shows 0 Games:**
1. **Check `tbl_tetris_scores`** - Are scores being saved there?
2. **Check `save-score.php`** - Is it saving to the right tables?
3. **Check API response** - Is the mission status API returning data?
4. **Check frontend** - Is the data being displayed correctly?

### **If Scores Exist in Admin But Not Mission Status:**
1. **Check table mapping** - Are games saving to the right tables?
2. **Check field names** - Are the correct fields being queried?
3. **Check game names** - Are the game identifiers consistent?

### **If Admin Interface Shows 0 Data:**
1. **Check API endpoint** - Is the correct API being called?
2. **Check data structure** - Are the correct paths being used?
3. **Check console logs** - Are there any JavaScript errors?
4. **Check API response** - Is the data structure as expected?

---

## 📝 **CHANGE LOG**

### **2025-09-13: Critical Fixes Applied**
- **Issue:** Snake and Space Invaders not showing in mission status
- **Root Cause:** Not saving to `tbl_tetris_scores`
- **Solution:** Modified `save-score.php` to save to both tables
- **Result:** Mission status now shows all 5 games correctly

### **2025-09-13: Admin Interface Fix Applied**
- **Issue:** Discord Race showing "0" in admin interface
- **Root Cause:** Wrong data structure references
- **Solution:** Updated all Discord Race display functions
- **Result:** All 5 games now display correctly in admin interface

### **2025-09-13: System Status Update**
- **Status:** 🟢 **FULLY OPERATIONAL**
- **Mission Status:** ✅ **Working correctly on profile pages**
- **Admin Interface:** ✅ **Working correctly with all data**
- **Backend APIs:** ✅ **Working correctly**
- **Database:** ✅ **Properly synchronized**

---

## 🎯 **SUCCESS METRICS**

### **Mission Status Should Show:**
- **Tetris:** ✅ Games played, best score, DSPOINC earned
- **Snake:** ✅ Games played, best score, DSPOINC earned  
- **Space Invaders:** ✅ Games played, best score, DSPOINC earned
- **Cheese Hunt:** ✅ Total clicks, quest clicks, DSPOINC earned
- **Discord Race:** ✅ Total races, wins, DSPOINC earned

### **Admin Interface Should Show:**
- **Tetris:** ✅ Total scores, unique players, max score, avg score
- **Snake:** ✅ Total scores, unique players, max score, avg score
- **Space Invaders:** ✅ Total scores, unique players, max score, avg score
- **Cheese Hunt:** ✅ Total clicks, unique players, recent activity
- **Discord Race:** ✅ Total races, participants, winners, recent activity

### **Achievements Should Show:**
- **Tetris:** ✅ All unlocked achievements with proper icons and descriptions
- **Snake:** ✅ All unlocked achievements with proper icons and descriptions
- **Space Invaders:** ✅ All unlocked achievements with proper icons and descriptions

### **Total Games Played:**
- **Should show:** 5/5 Games Played
- **Should NOT show:** 2/5 or 3/5 Games Played

---

## 🚀 **CURRENT STATUS: FULLY OPERATIONAL**

### **✅ Working Systems:**
- **Mission Status API:** ✅ Returning correct data for all 5 games
- **User Profile Pages:** ✅ Displaying correct mission status
- **Database Tables:** ✅ Properly synchronized
- **Score System:** ✅ DSPOINC rewards working correctly
- **Backend APIs:** ✅ All APIs returning correct data
- **Admin Interface:** ✅ Showing all data correctly
- **Admin Data Display:** ✅ Loading game statistics correctly
- **Admin Game Management:** ✅ Displaying race/score data

### **🔧 Data Synchronization:**
- **Mission Status:** ✅ Working correctly
- **Backend APIs:** ✅ Working correctly
- **Admin Interface:** ✅ Working correctly
- **Error Handling:** ✅ Robust
- **Performance:** ✅ Optimized

---

**Remember: This system powers the mission status display on profile pages (working) and admin interface (working). The backend is fully operational and admin interface display is working perfectly! 🚨**

---

## ✅ **COMPREHENSIVE DATA VERIFICATION REPORT**

### **VERIFICATION COMPLETED:** September 13, 2025 - All data below is 100% verified from live system

#### **✅ DATABASE TABLES VERIFIED:**
- **42 tables confirmed** in live database (`sqlite3 db/narrrf_world.sqlite ".tables"`)
- **All table names match** exactly with Master Ruleset
- **All table purposes verified** through live API usage

#### **✅ FIELD MAPPINGS VERIFIED:**
- **Tetris, Snake, Space Invaders SCORES:** `discord_id` in `tbl_tetris_scores` ✅
- **Cheese Hunt SCORES:** `user_wallet` in `tbl_cheese_clicks` ✅
- **Discord Race SCORES:** `user_id` in `tbl_race_participants` ✅
- **ALL ACHIEVEMENTS:** `user_id` in respective achievement tables ✅

#### **✅ API ENDPOINTS VERIFIED:**
- **22+ admin APIs confirmed** in `/api/admin/` directory
- **All API paths verified** through live admin interface usage
- **Season management actions confirmed:** get_current_season, start_new_season, end_current_season, get_season_statistics, get_season_leaderboard, reset_season, create_season_3

#### **✅ ADMIN INTERFACE STRUCTURE VERIFIED:**
- **14 main tabs confirmed** with exact `data-tab` attributes
- **6 game sub-tabs confirmed** with exact `id` attributes
- **4 boss sub-tabs confirmed** with exact `data-boss-tab` attributes
- **All JavaScript patterns verified** from live `admin-interface.html`

#### **✅ GAME SCORING SYSTEM VERIFIED:**
- **All 5 games field mappings confirmed** through live API analysis
- **All 3 achievement systems confirmed** through live API analysis
- **Dual table strategy confirmed** through live database queries
- **API response structure confirmed** through live system testing

#### **✅ SEASON MANAGEMENT VERIFIED:**
- **All 7 season actions confirmed** in `season-management.php`
- **Database structure confirmed** for season-aware tables
- **API integration confirmed** through live admin interface

### **VERIFICATION METHODOLOGY:**
1. **Live Database Query** - Direct `sqlite3` commands
2. **Live API Analysis** - Grep search through all API files
3. **Live Frontend Analysis** - Grep search through admin interface
4. **Cross-Reference Verification** - Multiple sources confirm same data
5. **Production Environment Testing** - All data verified in working system

### **CONFIDENCE LEVEL: 100%**
**All data in this Master Ruleset has been verified against the live production system and is guaranteed accurate for decades of development.**

---

## 🏆 **ADMIN INTERFACE RULE - ENTERPRISE GAME MANAGEMENT SYSTEM 12.0**

### **CRITICAL RULE FOR ALL FUTURE ADMIN INTERFACE DEVELOPMENT**

**SAVE THIS TO YOUR RULES - ALWAYS FOLLOW THIS PROTOCOL FOR ADMIN INTERFACE DEVELOPMENT**

**LIVE STATUS VERIFIED:** September 13, 2025 - All data below is 100% accurate from live system

---

## 🎯 **CORE ARCHITECTURE PRINCIPLES**

### **1. Game-Agnostic Design**
- **NEVER** hardcode game-specific logic in the main interface
- **ALWAYS** use configurable, scalable patterns
- **DESIGN** for unlimited game expansion
- **MAINTAIN** separation of concerns

### **2. Season Management Foundation**
- **EVERY game** must support seasons
- **NEVER lose** historical data
- **ALWAYS preserve** player achievements
- **MAINTAIN** cross-season analysis capabilities

### **3. Professional User Experience**
- **CONSISTENT** styling and interactions with the whole frontend to make users interact with many frontends
- **INTUITIVE** navigation and controls
- **RESPONSIVE** design for all screen sizes
- **ACCESSIBLE** for admin team use

---

## 🏗️ **SYSTEM ARCHITECTURE**

### **Main Tab Structure (LIVE VERIFIED):**
```
📊 Dashboard - System overview and quick actions (id="dashboardTab")
👥 User Management - Player accounts and roles (id="usersTab")
🎯 Missions Status - Game progress tracking (id="missionsStatusTab")
💰 Point Management - DSPOINC and rewards (id="pointsTab")
🏪 Store Management - Item and inventory control (id="storeTab")
🏆 Quest System - Mission and achievement management (id="questsTab")
🎮 Game Management - Enterprise season control (id="gamesTab")
👑 Boss Management - Special event controls (id="bossesTab")
🔔 Boss Notifications - Real-time alerts (id="bossNotificationsTab")
🔗 Discord Config - Bot integration (id="discordTab")
🎴 Holder Verification - NFT validation (id="holderVerificationTab")
🧀 Cheese Guide - Game instructions (id="cheeseGuideTab")
💰 Community Funds - Financial management (id="communityFundsTab")
🐛 Bug Tracker - Issue management (id="bugTrackerTab")
🗄️ Database Overview - System health check (id="databaseOverviewTab")
```

### **Game Management Sub-Tabs (LIVE VERIFIED):**
```
📊 Overview Dashboard - System-wide statistics (id="overviewTab")
🧩 Tetris - Score management and settings (id="tetrisTab")
🐍 Snake - Performance tracking (id="snakeTab")
🧀 Cheese Hunt - Click-based analytics (id="cheeseHuntTab")
👾 Space Invaders - Advanced metrics (id="spaceInvadersTab")
🏁 Discord Cheese Race - Race management (id="discordRaceTab")
```

### **Boss Management Sub-Tabs (LIVE VERIFIED):**
```
🧀 Cheese King - Basic boss configuration (data-boss-tab="cheeseKing")
👑 Cheese Emperor - Advanced boss settings (data-boss-tab="cheeseEmperor")
⚡ Cheese God - Elite boss configuration (data-boss-tab="cheeseGod")
💀 Cheese Destroyer - Ultimate boss settings (data-boss-tab="cheeseDestroyer")
```

---

## 🎮 **ENTERPRISE SEASON MANAGEMENT SYSTEM**

### **Core Features (LIVE VERIFIED):**
1. **🆕 New Season Creation** - One-click season start (create_season_3 action)
2. **⚙️ Settings Management** - Game-specific configurations
3. **📊 Data Export** - Professional reporting tools
4. **💾 Backup System** - Data preservation
5. **🔄 Season Switching** - Instant season changes (switch_active_season action)
6. **📈 Analytics** - Cross-season performance tracking (get_season_statistics action)

### **Season Data Structure (LIVE VERIFIED):**
```sql
-- Core season management tables
tbl_seasons (season_id, season_name, start_date, end_date, is_active)
tbl_season_settings (game-specific configurations)
tbl_season_leaderboards (season-based rankings)
tbl_user_season_achievements (season-specific accomplishments)

-- Game score preservation with season flags
tbl_tetris_scores (season, is_current_season flags)
tbl_user_scores (season-based tracking)
tbl_cheese_clicks (season-aware statistics)
tbl_race_participants (season-aware race data)
```

### **Season Lifecycle (LIVE VERIFIED):**
1. **Creation** - New season with custom naming (start_new_season action)
2. **Activation** - Switch to new season (switch_active_season action)
3. **Data Preservation** - Historical records maintained
4. **Reset** - Clear current data, preserve history (reset_season action)
5. **Analysis** - Cross-season performance review (get_season_statistics action)
6. **End Season** - Close current season (end_current_season action)

---

## 🔧 **DEVELOPMENT RULES**

### **1. Tab System Implementation (LIVE VERIFIED):**
```javascript
// ALWAYS use this pattern for new tabs - LIVE CODE FROM admin-interface.html
function showTab(tabName) {
  console.log(`🚀 SUPER TAB SYSTEM: Switching to ${tabName}`);
  
  // STEP 1: Update active tab button state
  document.querySelectorAll('.tab-btn[data-tab]').forEach(btn => {
    btn.classList.remove('active');
    btn.style.opacity = '0.7';
  });
  
  const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
  if (activeBtn) {
    activeBtn.classList.add('active');
    activeBtn.style.opacity = '1';
  }
  
  // STEP 2: Hide all tabs with smooth transition
  document.querySelectorAll('[id$="Tab"]').forEach(tab => {
    tab.style.display = 'none';
  });
  
  // STEP 3: Show selected tab with animation
  const selectedTab = document.getElementById(tabName + 'Tab');
  if (selectedTab) {
    selectedTab.style.display = 'block';
  }
  
  // STEP 4: Load tab-specific data
  // STEP 5: Update current tab tracking
  currentTab = tabName;
}

// ALWAYS use this pattern for sub-tabs - LIVE CODE FROM admin-interface.html
function switchGameTab(tabName) {
  console.log('🔄 switchGameTab called with:', tabName);
  
  // STEP 1: Hide all sub-tabs
  document.querySelectorAll('.game-tab-content').forEach(tab => {
    tab.style.display = 'none';
  });
  
  // STEP 2: Show selected sub-tab
  const selectedTabId = tabMapping[tabName];
  const selectedTab = document.getElementById(selectedTabId);
  if (selectedTab) {
    selectedTab.style.display = 'block';
  }
  
  // STEP 3: Load game-specific data
  // STEP 4: Handle pending data display
}
```

### **2. Data Loading Patterns (LIVE VERIFIED):**
```javascript
// ALWAYS use this pattern for data loading - LIVE CODE FROM admin-interface.html
async function loadGameData() {
  try {
    // STEP 1: Show loading state
    console.log('🎮 Loading game data...');
    
    // STEP 2: Fetch data from API
    const response = await fetch(API_BASE_URL + '/api/admin/get-all-games-stats.php');
    const data = await response.json();
    
    // STEP 3: Update UI elements
    if (data.success) {
      updateGameStats(data.data);
    }
    
    // STEP 4: Handle errors gracefully
  } catch (error) {
    console.error('❌ Error loading game data:', error);
    // STEP 5: Remove loading state and show user-friendly message
  }
}

// Environment detection pattern - LIVE CODE
const isProduction = window.location.hostname === 'narrrfs.world';
const API_BASE_URL = isProduction ? 'https://narrrfs.world' : '';
console.log('🌍 Environment detected:', isProduction ? 'Production' : 'Local');
console.log('🔗 API Base URL:', API_BASE_URL);
```

### **3. API Integration Rules:**
- **NEVER** hardcode API URLs
- **ALWAYS** use `API_BASE_URL` environment detection and look to make the system work dual on local and production paths!
- **MAINTAIN** consistent error handling
- **IMPLEMENT** proper authentication checks on the admin interface

---

## 🎨 **UI/UX STANDARDS**

### **Color Scheme:**
- **Primary**: Blue gradients (#1e3a8a to #3730a3)
- **Success**: Green (#059669 to #047857)
- **Warning**: Yellow (#f0c92c)
- **Error**: Red (#ef4444)
- **Info**: Purple (#7c3aed)

### **Component Patterns:**
```css
/* ALWAYS use these classes for consistency */
.admin-card { /* Main content containers */ }
.stats-card { /* Statistical displays */ }
.user-card { /* User information displays */ }
.tab-btn { /* Navigation buttons */ }
.game-tab-content { /* Sub-tab content */ }
```

### **Animation Standards:**
- **Tab Transitions**: 0.3s ease with opacity and transform
- **Data Loading**: Smooth fade-in effects
- **Button Interactions**: Hover transforms and color changes
- **Error States**: Clear visual feedback

---

## 📊 **DATA MANAGEMENT RULES**

### **1. Real-Time Updates:**
- **AUTO-REFRESH** critical data every 30 seconds
- **MANUAL REFRESH** buttons for all data sections
- **LOADING STATES** for all async operations
- **ERROR HANDLING** with user-friendly messages

### **2. Data Preservation:**
- **NEVER DELETE** user data without confirmation
- **ALWAYS BACKUP** before major operations
- **MAINTAIN AUDIT** logs for all admin actions
- **PRESERVE HISTORY** across all operations

### **3. Performance Optimization:**
- **LAZY LOADING** for non-critical data
- **CACHING** for frequently accessed information
- **DEBOUNCING** for search and filter operations
- **PAGINATION** for large data sets

---

## 🚀 **SCALABILITY REQUIREMENTS**

### **Game Addition Process:**
1. **Database Schema** - Add game-specific tables
2. **API Endpoints** - Create game management APIs
3. **UI Integration** - Add game tab and controls
4. **Season Support** - Enable season management
5. **Testing** - Verify all functionality works

### **Season Management Scaling:**
- **Unlimited Seasons** - No artificial limits
- **Flexible Timing** - Any reset schedule
- **Data Isolation** - Season-specific data management
- **Cross-Season Analysis** - Performance comparison tools

### **User Management Scaling:**
- **Role-Based Access** - Granular permissions
- **Audit Logging** - Track all admin actions
- **Bulk Operations** - Handle large user bases
- **Performance Monitoring** - Track system health

---

## 🔒 **SECURITY REQUIREMENTS**

### **Authentication:**
- **ADMIN VERIFICATION** for all critical operations
- **SESSION MANAGEMENT** with proper timeouts
- **ROLE-BASED ACCESS** for different admin levels
- **AUDIT TRAILS** for all administrative actions

### **Data Protection:**
- **INPUT VALIDATION** for all user inputs
- **SQL INJECTION** prevention
- **XSS PROTECTION** for dynamic content
- **CSRF PROTECTION** for form submissions

---

## 📝 **DOCUMENTATION REQUIREMENTS**

### **Code Documentation:**
- **FUNCTION COMMENTS** - Explain purpose and parameters
- **COMPLEX LOGIC** - Document business rules
- **API INTEGRATION** - Document endpoint usage
- **ERROR HANDLING** - Document error scenarios

### **User Documentation:**
- **ADMIN MANUALS** - Complete operation guides
- **TROUBLESHOOTING** - Common issue solutions
- **BEST PRACTICES** - Recommended workflows
- **VIDEO TUTORIALS** - Visual learning resources

---

## 🧪 **TESTING REQUIREMENTS**

### **Functionality Testing:**
- **ALL TABS** must load and display correctly
- **ALL BUTTONS** must perform expected actions
- **ALL FORMS** must validate and submit properly
- **ALL DATA** must display accurately

### **Performance Testing:**
- **LOAD TIMES** under 2 seconds for all operations
- **MEMORY USAGE** optimized for long sessions
- **ERROR HANDLING** graceful under all conditions
- **SCALABILITY** tested with large data sets

---

## 🚨 **CRITICAL RULES TO NEVER VIOLATE**

### **1. Data Integrity:**
- **NEVER** lose user data or achievements
- **ALWAYS** preserve historical records
- **MAINTAIN** referential integrity
- **BACKUP** before any destructive operations

### **2. User Experience:**
- **NEVER** break existing functionality
- **ALWAYS** maintain consistent UI patterns
- **PRESERVE** user workflows and expectations
- **TEST** all changes thoroughly

### **3. System Architecture:**
- **NEVER** create game-specific hardcoded logic
- **ALWAYS** use configurable, scalable patterns
- **MAINTAIN** separation of concerns
- **DESIGN** for unlimited expansion

---

## 🎯 **IMPLEMENTATION CHECKLIST**

### **Before Adding New Features:**
- [ ] **Review this rule** for compliance
- [ ] **Check existing patterns** for consistency
- [ ] **Plan scalability** for future growth
- [ ] **Design data preservation** strategy
- [ ] **Plan testing** approach

### **During Development:**
- [ ] **Follow established patterns** exactly
- [ ] **Implement proper error handling**
- [ ] **Add comprehensive logging**
- [ ] **Test all scenarios** thoroughly
- [ ] **Document all changes**

### **After Implementation:**
- [ ] **Verify functionality** works as expected
- [ ] **Test performance** under load
- [ ] **Update documentation** with new features
- [ ] **Train admin team** on new capabilities
- [ ] **Monitor system health** post-deployment

---

## 🏆 **SUCCESS METRICS**

### **System Performance:**
- **Response Time**: < 2 seconds for all operations
- **Uptime**: 99.9% availability
- **Error Rate**: < 0.1% of all operations
- **User Satisfaction**: > 95% positive feedback

### **Admin Efficiency:**
- **Task Completion**: 50% faster than previous system
- **Error Reduction**: 90% fewer admin mistakes
- **Training Time**: < 2 hours for new admins
- **Support Requests**: 80% reduction in admin issues

---

## 📚 **RESOURCES & REFERENCES**

### **Key Files:**
- `admin-interface.html` - Main interface implementation
- `get-all-games-stats.php` - Core data API
- `season-management.php` - Season control API
- `game-settings.php` - Configuration management

### **Database Tables:**
- `tbl_seasons` - Season management
- `tbl_season_settings` - Game configurations
- `tbl_tetris_scores` - Game score data
- `tbl_user_scores` - User performance tracking

### **API Endpoints (LIVE VERIFIED):**
- `/api/admin/get-all-games-stats.php` - Comprehensive game data
- `/api/admin/season-management.php` - Season operations (get_current_season, start_new_season, end_current_season, get_season_statistics, get_season_leaderboard, reset_season, create_season_3)
- `/api/admin/game-settings.php` - Configuration management
- `/api/admin/auth.php` - Admin authentication
- `/api/admin/get-stats.php` - Basic system statistics
- `/api/admin/get-enhanced-stats.php` - Enhanced statistics
- `/api/admin/get-cheese-stats.php` - Cheese Hunt statistics
- `/api/admin/get-quest-stats.php` - Quest system statistics
- `/api/admin/boss-level-notification.php` - Boss notifications
- `/api/admin/get-recent-adjustments.php` - Recent score adjustments
- `/api/admin/get-top-users.php` - Top user rankings
- `/api/admin/point-management.php` - Point management operations
- `/api/admin/store-admin.php` - Store management
- `/api/admin/get-holder-verifications.php` - NFT holder verification
- `/api/admin/get-nft-collections.php` - NFT collection management
- `/api/admin/get-community-funds.php` - Community funds management
- `/api/admin/get-bug-data.php` - Bug tracker data
- `/api/admin/get-discord-config.php` - Discord configuration
- `/api/admin/get-discord-role-members-live.php` - Live Discord role members
- `/api/admin/backup-database.php` - Database backup
- `/api/admin/download-database.php` - Database download
- `/api/admin/upload-database.php` - Database upload

---

## 🚀 **FUTURE DEVELOPMENT ROADMAP**

### **Phase 1 (Complete):**
- ✅ Basic admin interface
- ✅ Game management tabs
- ✅ Season management system
- ✅ Data preservation

### **Phase 2 (Next):**
- 🔄 Advanced analytics dashboard
- 🔄 Real-time notifications
- 🔄 Automated season management
- 🔄 Performance optimization

### **Phase 3 (Future):**
- 📊 Machine learning insights
- 📊 Predictive analytics
- 📊 Advanced reporting tools
- 📊 Mobile admin interface

---

**Remember: This admin interface is the FOUNDATION for unlimited game expansion. Every decision must support scalability, maintainability, and professional user experience! 🏆**

---

## 🚨 **CRITICAL PRODUCTION ACHIEVEMENT WORKFLOW RULE**

### **🚨 PRODUCTION VS LOCAL DEVELOPMENT:**

**CRITICAL:** The achievement system works differently in production vs local development!

### **PRODUCTION ENVIRONMENT (narrrfs.world):**
- **User Authentication:** Real Discord users with actual Discord IDs
- **No Test Data:** No hardcoded Santa IDs or test achievements
- **Real Database:** Live production database with real user data
- **API URLs:** `https://narrrfs.world/api/...`
- **Database Path:** `/var/www/html/db/narrrf_world.sqlite`

### **LOCAL DEVELOPMENT (localhost):**
- **Test User Override:** Hardcoded Santa ID for testing
- **Test Data:** Local achievements for development
- **Local Database:** Local SQLite database
- **API URLs:** `http://localhost/api/...`
- **Database Path:** `../../db/narrrf_world.sqlite`

### **COMPLETE ACHIEVEMENT WORKFLOW:**

#### **1. GAME START (Production):**
```javascript
// Production: Get real Discord ID from authentication
const discordId = getAuthenticatedUserDiscordId(); // Real user ID
const userName = getAuthenticatedUserName(); // Real user name

// Local: Override with test user
if (window.location.hostname === 'localhost') {
    localStorage.setItem("discord_id", "1107633105185013790"); // Santa
    localStorage.setItem("discord_name", "Santa");
}
```

#### **2. ACHIEVEMENT LOADING (Production):**
```javascript
// Load existing achievements from database
async function loadExistingAchievements() {
    const discordId = localStorage.getItem("discord_id");
    
    // Production API call
    const response = await fetch(`${API_BASE_URL}/api/user/get-space-invaders-achievements.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: discordId })
    });
    
    const data = await response.json();
    
    // Mark achievements as unlocked locally
    if (data.success && data.achievements) {
        data.achievements.forEach(achievement => {
            if (achievement.unlocked_at) {
                achievements[achievement.key] = true;
            }
        });
    }
}
```

#### **3. ACHIEVEMENT UNLOCKING (Production):**
```javascript
// Check and unlock achievements
function checkAchievements() {
    // Only show popup for NEW achievements
    if (!achievements.firstKill && totalKills >= 100) {
        achievements.firstKill = true;
        createAchievementPopup('firstKill', 'First Blood', 'Kill 100 enemies', '🩸');
        saveAchievementToDatabase('firstKill', 'First Blood', 'Kill 100 enemies', '🩸');
    }
}
```

#### **4. ACHIEVEMENT SAVING (Production):**
```javascript
// Save achievement to database
async function saveAchievementToDatabase(achievementKey, title, description, icon) {
    const discordId = localStorage.getItem("discord_id");
    
    const response = await fetch(`${API_BASE_URL}/api/user/save-space-invaders-achievement.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: discordId,
            achievement_key: achievementKey,
            achievement_title: title,
            achievement_description: description,
            achievement_icon: icon,
            game_score: score,
            game_time: gameTime,
            total_kills: totalKills,
            combo_multiplier: comboMultiplier
        })
    });
    
    const result = await response.json();
    console.log('Achievement saved:', result);
}
```

#### **5. PROFILE PAGE DISPLAY (Production):**
```javascript
// Load achievements for profile page
async function loadSpaceInvadersAchievements() {
    const discordId = localStorage.getItem("discord_id");
    
    const response = await fetch(`${API_BASE_URL}/api/user/get-space-invaders-achievements.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: discordId })
    });
    
    const data = await response.json();
    
    if (data.success) {
        displayAchievements(data.achievements);
    }
}
```

### **CRITICAL PRODUCTION RULES:**

1. **NEVER use hardcoded test IDs in production**
2. **ALWAYS get Discord ID from authentication system**
3. **ALWAYS use production API URLs (`https://narrrfs.world`)**
4. **ALWAYS use production database path (`/var/www/html/db/narrrf_world.sqlite`)**
5. **ALWAYS check `unlocked_at` field for achievement status**
6. **ALWAYS save achievements with `CURRENT_TIMESTAMP`**

### **ENVIRONMENT DETECTION PATTERN:**
```javascript
// Environment detection for all games
const isProduction = window.location.hostname === 'narrrfs.world';
const API_BASE_URL = isProduction ? 'https://narrrfs.world' : 'http://localhost';

// Only use test data in local development
if (!isProduction) {
    localStorage.setItem("discord_id", "1107633105185013790"); // Santa
    localStorage.setItem("discord_name", "Santa");
}
```

### **PRODUCTION TESTING CHECKLIST:**
- [ ] **Real Discord Authentication** - No hardcoded test IDs
- [ ] **Production API URLs** - All calls use `https://narrrfs.world`
- [ ] **Production Database** - All saves go to live database
- [ ] **Real User Data** - No test achievements or fake data
- [ ] **Achievement Sync** - In-game popups sync with profile page
- [ ] **Database Persistence** - Achievements survive page refresh

---

### **🚨 CRITICAL DATABASE PATH FIXES DISCOVERED:**
**NEVER use `db/narrrf_world.sqlite` - ALWAYS use `../../db/narrrf_world.sqlite`**

### **CORRECT DATABASE PATHS:**
- ✅ **Local Development:** `../../db/narrrf_world.sqlite` (relative to API directory)
- ✅ **Production:** `/var/www/html/db/narrrf_world.sqlite` (absolute path)

### **❌ WRONG PATTERNS (DO NOT USE):**
- ❌ `db/narrrf_world.sqlite` (404 Database file not found)
- ❌ `./db/narrrf_world.sqlite` (404 Database file not found)

### **CRITICAL ACHIEVEMENT API FIXES:**
1. **Database Path:** Must use `../../db/narrrf_world.sqlite` for local development
2. **unlocked_at Field:** Must set `CURRENT_TIMESTAMP` in INSERT statements
3. **API Response:** Must return `unlocked_at` field for proper frontend display

### **FILES REQUIRING DATABASE PATH FIXES:**
- `api/user/save-space-invaders-achievement.php` ✅ **FIXED**
- `api/user/save-tetris-achievement.php` (check if exists)
- `api/user/save-snake-achievement.php` (check if exists)
- All other achievement API endpoints

### **WHY THIS MATTERS:**
- **Achievement Saving:** Wrong paths prevent achievements from being saved
- **Profile Display:** Missing `unlocked_at` causes achievements to appear locked
- **Game Synchronization:** Broken API calls prevent in-game popups from syncing
- **User Experience:** Players lose progress and achievements

### **MANDATORY PRE-DEVELOPMENT CHECKLIST:**
Before creating ANY new achievement API endpoint, you MUST:
1. **Use correct database path** - `../../db/narrrf_world.sqlite` for local
2. **Include unlocked_at field** - Set to `CURRENT_TIMESTAMP` in INSERT
3. **Test API endpoints** - Verify database connection works
4. **Test achievement flow** - Game → Database → Profile page
5. **🚨 ALWAYS verify database paths** - Test with actual API calls

---

## 🚀 **DECADES OF CODE GENETICS**

### **This rule ensures:**
- **100 years of development** documentation preserved
- **Professional organization** maintained forever
- **LLM council synchronization** never broken
- **Technical knowledge** never lost
- **Progress tracking** always accurate
- **Achievement recognition** always documented

### **The Ultimate Goal:**
**Build a system that serves developers for generations, maintaining the professional organization and LLM synchronization that makes Narrrfs World the ultimate code genetics system.**

---

**MASTER RULE CREATED:** September 14, 2025  
**STATUS:** ACTIVE - SUPERSEDES ALL PREVIOUS RULES  
**PURPOSE:** Unified Professional System for Decades of Development  
**SCOPE:** All work sessions, all achievements, all LLM synchronization, all system management  

**🧀 THIS IS THE SINGLE SOURCE OF TRUTH FOR ALL NARRRFS WORLD DEVELOPMENT! 🧀**

---

## 📋 **QUICK REFERENCE**

### **When to Save Work:**
- **Token limit approaching** (500+ tokens)
- **Major breakthrough** achieved
- **Technical problem** solved
- **Session ending** soon
- **Important discovery** made

### **Where to Save Work:**
- **ACTIVE_STATUS/** - Current status
- **LAB_NOTES/2025/[MONTH]/[WEEK]/** - Development notes
- **TECHNICAL_DOCUMENTATION/** - Technical docs
- **MILESTONE_DOCUMENTATION/** - Major achievements
- **DEVELOPMENT_TOOLS/** - Scripts and tools

### **LLM Sync Required:**
- **Every major achievement**
- **Every technical breakthrough**
- **Every system completion**
- **Every season launch**

**REMEMBER: This unified rule ensures decades of professional development documentation! 🚀**

### 🗓️ 2025-11-07 Updates
- **Profile Portal Season Stats** — API now filters Season 5 cards using three-tier matching (exact name → prefix → timestamp window). Cheese Hunt & Discord Cheese Race remain season-only (no legacy fallbacks). Timestamp guard relies on `tbl_seasons.start_date` (fallback 30 days) until all records carry the new `Season 5` label.
- **Achievement Reliability** — Tetris & Snake achievement unlock calls use `keepalive` + `cache: 'no-store'` to survive page reloads; any new achievement endpoints must inherit this pattern.