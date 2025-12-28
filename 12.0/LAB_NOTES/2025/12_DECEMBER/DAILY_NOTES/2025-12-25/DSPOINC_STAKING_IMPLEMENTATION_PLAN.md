# 🧊 DSPOINC Staking System - Implementation Plan

**Created:** December 25, 2025  
**Status:** ✅ **PHASE 1 COMPLETE - PHASE 2 IN PROGRESS**  
**Purpose:** Complete implementation plan for DSPOINC staking/freezing system with dedicated page  
**Phase 1 Complete:** December 25, 2025 - Database & APIs ready

---

## 🎯 **SYSTEM OVERVIEW**

### **Core Concept:**
Users can "freeze" (stake) a defined amount of DSPOINC for a selected time period. After the freeze period ends, users receive their original DSPOINC back PLUS a reward bonus based on the freeze duration.

### **Architecture Pattern:**
**Follows the same pattern as game pages (Tetris, Snake, Space Invaders):**
- **Profile Page:** Shows summary/overview card with key stats (clickable)
- **Dedicated Page:** `stake-lab.html` - Full staking interface with all features
- **Navigation:** Click card on profile → Opens dedicated staking page
- **Styling:** Matches `get-roles.html` and `profile.html` design patterns
- **Theme:** Ice/Blue gradient (🧊 staking theme)
- **Authentication:** Discord login required (same pattern as profile.html)

**Key Benefits:**
- ✅ Clean separation of concerns (summary vs full interface)
- ✅ Consistent with existing game page architecture
- ✅ Better UX (dedicated space for complex features)
- ✅ Easier maintenance (separate files)
- ✅ Mobile-friendly (full page optimized for mobile)

### **Key Features:**
- ✅ Freeze defined amount of DSPOINC
- ✅ Select freeze duration (1, 3, 6, 12, 24, 36 months)
- ✅ Transaction tracking and audit trail
- ✅ Reward calculation based on duration
- ✅ Summary card on profile page (links to full page)
- ✅ Dedicated `stake-lab.html` page with full interface
- ✅ Balance visibility (available vs frozen)

---

## 📊 **DATABASE SCHEMA**

### **New Table: `tbl_dspoinc_stakes`**

```sql
CREATE TABLE tbl_dspoinc_stakes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,                    -- Discord ID
    amount INTEGER NOT NULL,                  -- Amount frozen (DSPOINC)
    freeze_duration_months INTEGER NOT NULL,  -- 1, 3, 6, 12, 24, 36
    reward_rate REAL NOT NULL,                -- Reward percentage (e.g., 0.05 for 5%)
    expected_reward INTEGER NOT NULL,          -- Calculated reward amount
    frozen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unfreeze_at DATETIME NOT NULL,             -- Calculated unfreeze date
    status TEXT DEFAULT 'active',              -- 'active', 'completed', 'cancelled'
    completed_at DATETIME,                     -- When stake was completed
    reward_paid INTEGER DEFAULT 0,            -- Actual reward paid (for tracking)
    transaction_id TEXT,                       -- Reference to tbl_wallet_transactions
    metadata TEXT,                              -- JSON for additional data
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_user_status (user_id, status),
    INDEX idx_unfreeze_at (unfreeze_at, status),
    INDEX idx_user_active (user_id, status, unfreeze_at)
);
```

### **Reward Rate Configuration:**

| Duration (Months) | Reward Rate | Example (1000 DSPOINC) |
|-------------------|-------------|------------------------|
| 1 month           | 2% (0.02)   | 20 DSPOINC reward      |
| 3 months          | 5% (0.05)   | 50 DSPOINC reward      |
| 6 months          | 10% (0.10)  | 100 DSPOINC reward     |
| 12 months         | 20% (0.20)  | 200 DSPOINC reward     |
| 24 months         | 45% (0.45)  | 450 DSPOINC reward     |
| 36 months         | 75% (0.75)  | 750 DSPOINC reward     |

**Note:** Reward rates are configurable and can be adjusted via admin interface.

---

## 🔧 **API ENDPOINTS**

### **1. Create Stake: `/api/user/create-stake.php`**

**Method:** POST  
**Purpose:** Freeze DSPOINC for selected duration

**Request:**
```json
{
  "amount": 1000,
  "freeze_duration_months": 6
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "stake_id": 123,
    "user_id": "discord_id",
    "amount": 1000,
    "freeze_duration_months": 6,
    "reward_rate": 0.10,
    "expected_reward": 100,
    "frozen_at": "2025-12-25 10:00:00",
    "unfreeze_at": "2026-06-25 10:00:00",
    "status": "active",
    "transaction_id": "txn_abc123"
  }
}
```

**Logic:**
1. Verify user has sufficient balance (`SUM(score) FROM tbl_user_scores WHERE user_id = ?`)
2. Check available balance (total - frozen amount)
3. Calculate reward based on duration
4. Create stake record in `tbl_dspoinc_stakes`
5. Create negative transaction in `tbl_user_scores` (frozen amount)
6. Create transaction record in `tbl_wallet_transactions`
7. Create audit entry in `tbl_score_adjustments`

**Error Cases:**
- Insufficient balance (400 Bad Request)
- Invalid duration (400 Bad Request)
- Amount too low (minimum 100 DSPOINC)
- Amount too high (maximum based on available balance)

---

### **2. Get User Stakes: `/api/user/get-stakes.php`**

**Method:** POST  
**Purpose:** Get all stakes for logged-in user

**Request:**
```json
{
  "status": "active"  // Optional: 'active', 'completed', 'all'
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "active_stakes": [
      {
        "stake_id": 123,
        "amount": 1000,
        "freeze_duration_months": 6,
        "reward_rate": 0.10,
        "expected_reward": 100,
        "frozen_at": "2025-12-25 10:00:00",
        "unfreeze_at": "2026-06-25 10:00:00",
        "days_remaining": 182,
        "status": "active"
      }
    ],
    "completed_stakes": [...],
    "total_frozen": 5000,
    "total_available": 10000,
    "total_balance": 15000
  }
}
```

**Logic:**
1. Get all stakes for user from `tbl_dspoinc_stakes`
2. Calculate days remaining for active stakes
3. Calculate total frozen amount
4. Calculate available balance (total - frozen)
5. Return organized data

---

### **3. Complete Stake (Auto-Process): `/api/user/complete-stake.php`**

**Method:** POST (Admin/Cron)  
**Purpose:** Process completed stakes and pay rewards

**Request:**
```json
{
  "stake_id": 123  // Optional: process specific stake, or process all due stakes
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "stakes_processed": 5,
    "total_rewards_paid": 500,
    "processed_stakes": [123, 124, 125, 126, 127]
  }
}
```

**Logic:**
1. Find all stakes where `unfreeze_at <= NOW()` and `status = 'active'`
2. For each stake:
   - Calculate actual reward (may differ if rates changed)
   - Add original amount + reward to `tbl_user_scores`
   - Update stake status to 'completed'
   - Create transaction records
   - Create audit entries
3. Return summary

**Note:** This should be called by cron job or admin interface daily.

---

### **4. Get Staking Stats: `/api/user/get-staking-stats.php`**

**Method:** POST  
**Purpose:** Get staking statistics for user

**Response:**
```json
{
  "success": true,
  "data": {
    "total_balance": 15000,
    "available_balance": 10000,
    "frozen_balance": 5000,
    "active_stakes_count": 3,
    "completed_stakes_count": 5,
    "total_rewards_earned": 2500,
    "pending_rewards": 500
  }
}
```

---

## 🎨 **FRONTEND IMPLEMENTATION**

### **Architecture: Two-Page System (Like Game Pages)**

**Pattern:** Follows same architecture as Tetris, Snake, Space Invaders
- **Profile Page:** Summary card with key stats → Links to full page
- **Dedicated Page:** `stake-lab.html` → Complete staking interface

---

### **1. PROFILE PAGE - Summary Card (Overview Only)**

**Location:** After DSPOINC section, before Trophy Shelf (same area as game cards)

**Summary Card Design (Clickable - Links to stake-lab.html):**
```
┌─────────────────────────────────────┐
│ 🧊 DSPOINC Staking                   │
│ [Click to Open Stake Lab →]          │
├─────────────────────────────────────┤
│ Total Balance: 15,000 DSPOINC       │
│ Available: 10,000 DSPOINC            │
│ Frozen: 5,000 DSPOINC                │
│ Active Stakes: 3                      │
│ Pending Rewards: 500 DSPOINC         │
│                                      │
│ [View All Stakes →]                   │
└─────────────────────────────────────┘
```

**Card Styling:** Match game card pattern (like Tetris/Snake cards)
- Gradient background (ice/blue theme)
- Hover effects (scale, shadow)
- Clickable link to `stake-lab.html`
- Icon: 🧊 (ice cube)

**Code Pattern (Profile.html):**
```html
<a href="stake-lab.html" class="group bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl p-4 border-2 border-blue-400 hover:border-yellow-400 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/50 cursor-pointer">
  <div class="text-3xl mb-2 group-hover:animate-bounce">🧊</div>
  <h3 class="text-sm font-bold mb-1">DSPOINC Staking</h3>
  <p class="text-xs text-blue-200">Freeze & Earn Rewards</p>
  <div class="mt-2 text-xs">
    <p>Available: <span id="staking-available-balance">-</span> DSPOINC</p>
    <p>Frozen: <span id="staking-frozen-balance">-</span> DSPOINC</p>
  </div>
</a>
```

---

### **2. STAKE-LAB.HTML - Dedicated Full Page**

**File:** `public/stake-lab.html`  
**Styling:** Matches `get-roles.html` and `profile.html` design patterns  
**Theme:** Ice/Blue gradient (🧊 staking theme)

#### **Page Structure:**

**Header Section:**
- Navigation bar (same as profile.html)
- Page title: "🧊 Stake Lab - Freeze & Earn DSPOINC"
- Subtitle: "Lock your DSPOINC and earn rewards over time"

**Main Content Sections:**

#### **A. Balance Overview Dashboard:**
```
┌─────────────────────────────────────┐
│ 🧊 Your DSPOINC Balance              │
├─────────────────────────────────────┤
│ Total Balance: 15,000 DSPOINC       │
│ Available: 10,000 DSPOINC            │
│ Frozen: 5,000 DSPOINC                │
│ Pending Rewards: 500 DSPOINC         │
└─────────────────────────────────────┘
```

#### **B. Create New Stake Section:**
```
┌─────────────────────────────────────┐
│ 🧊 Freeze DSPOINC                    │
├─────────────────────────────────────┤
│ Amount: [Input: 1000] DSPOINC        │
│ Duration: [Dropdown: 1, 3, 6, 12...] │
│                                      │
│ Expected Reward: 100 DSPOINC (10%)   │
│ Unfreeze Date: June 25, 2026         │
│ Total Return: 1,100 DSPOINC           │
│                                      │
│ [Button: Freeze DSPOINC]             │
└─────────────────────────────────────┘
```

#### **C. Active Stakes List:**
```
┌─────────────────────────────────────┐
│ 🧊 Active Stakes (3)                 │
├─────────────────────────────────────┤
│ Stake #123                           │
│ Amount: 1,000 DSPOINC                │
│ Duration: 6 months                   │
│ Reward: 100 DSPOINC (10%)            │
│ Unfreeze: June 25, 2026              │
│ Days Remaining: 182                  │
│ [Progress Bar: ████████░░ 60%]        │
│                                      │
│ [Cancel Stake] (if allowed)          │
└─────────────────────────────────────┘
```

#### **D. Completed Stakes History:**
```
┌─────────────────────────────────────┐
│ ✅ Completed Stakes (5)              │
├─────────────────────────────────────┤
│ Stake #100                           │
│ Amount: 500 DSPOINC (3 months)       │
│ Reward Earned: 25 DSPOINC            │
│ Completed: Dec 20, 2025              │
│ Total Received: 525 DSPOINC          │
└─────────────────────────────────────┘
```

#### **E. Staking Information/Help:**
```
┌─────────────────────────────────────┐
│ 📚 How Staking Works                 │
├─────────────────────────────────────┤
│ • Freeze DSPOINC for selected period │
│ • Earn rewards based on duration     │
│ • Longer periods = higher rewards    │
│ • Rewards paid automatically        │
│ • Cannot unfreeze early (locked)     │
└─────────────────────────────────────┘
```

**Page Features:**
- Discord login required (same pattern as profile.html)
- Real-time balance updates
- Interactive stake creation form
- Visual progress bars for active stakes
- Countdown timers for unfreeze dates
- Responsive design (mobile-friendly)
- Loading states and error handling

---

## 🔄 **TRANSACTION FLOW**

### **When User Freezes DSPOINC:**

1. **User Action:**
   - User enters amount and selects duration
   - Clicks "Freeze DSPOINC" button

2. **Frontend Validation:**
   - Check available balance
   - Validate amount (min 100, max available)
   - Validate duration selection

3. **API Call:**
   - POST to `/api/user/create-stake.php`
   - Send amount and duration

4. **Backend Processing:**
   - Verify user balance
   - Calculate reward
   - Create stake record
   - Create negative entry in `tbl_user_scores`:
     ```sql
     INSERT INTO tbl_user_scores (user_id, score, game, source, season)
     VALUES (?, -1000, 'staking', 'freeze', 'Season 6');
     ```
   - Create transaction record
   - Create audit entry in `tbl_score_adjustments`

5. **Response:**
   - Return stake details
   - Update UI with new stake
   - Refresh balance display

### **When Stake Completes (Auto-Process):**

1. **Cron Job / Admin Trigger:**
   - Daily check for due stakes
   - Call `/api/user/complete-stake.php`

2. **Backend Processing:**
   - Find all due stakes
   - For each stake:
     - Add original amount + reward to `tbl_user_scores`:
       ```sql
       INSERT INTO tbl_user_scores (user_id, score, game, source, season)
       VALUES (?, 1100, 'staking', 'unfreeze_reward', 'Season 6');
       ```
     - Update stake status
     - Create transaction records
     - Create audit entries

3. **Notification (Future):**
   - Discord notification when stake completes
   - Email notification (if implemented)

---

## 📋 **INTEGRATION WITH EXISTING SYSTEM**

### **Balance Calculation Update:**

**Current:**
```php
// api/user/profile.php
$dspoincStmt = $db->prepare("SELECT SUM(score) FROM tbl_user_scores WHERE user_id = ?");
$total_dspoinc = $dspoincResult[0] ?? 0;
```

**Updated (with staking):**
```php
// Total balance (includes frozen)
$total_dspoinc = $dspoincResult[0] ?? 0;

// Available balance (excludes frozen)
$frozenStmt = $db->prepare("
    SELECT COALESCE(SUM(amount), 0) 
    FROM tbl_dspoinc_stakes 
    WHERE user_id = ? AND status = 'active'
");
$frozenStmt->bindValue(1, $user_id, SQLITE3_TEXT);
$frozenResult = $frozenStmt->execute()->fetchArray(SQLITE3_NUM);
$frozen_balance = $frozenResult[0] ?? 0;

$available_balance = $total_dspoinc - $frozen_balance;
```

### **Transaction Tracking:**

**Use Existing Tables:**
- `tbl_user_scores` - Balance entries (negative for freeze, positive for unfreeze+reward)
- `tbl_wallet_transactions` - Transaction records
- `tbl_score_adjustments` - Audit trail

**Transaction Types:**
- `source: 'freeze'` - When DSPOINC is frozen
- `source: 'unfreeze_reward'` - When stake completes and reward is paid

---

## 🎯 **REWARD CALCULATION SYSTEM**

### **Reward Formula:**
```
Reward = Amount × Reward Rate
Total Return = Amount + Reward
```

### **Example Calculations:**

**1 Month Stake (2%):**
- Amount: 1,000 DSPOINC
- Reward: 1,000 × 0.02 = 20 DSPOINC
- Total Return: 1,020 DSPOINC

**6 Month Stake (10%):**
- Amount: 5,000 DSPOINC
- Reward: 5,000 × 0.10 = 500 DSPOINC
- Total Return: 5,500 DSPOINC

**36 Month Stake (75%):**
- Amount: 10,000 DSPOINC
- Reward: 10,000 × 0.75 = 7,500 DSPOINC
- Total Return: 17,500 DSPOINC

### **Reward Rate Configuration:**

**Stored in:** `tbl_game_settings` or new `tbl_staking_config` table

**Admin Configurable:**
- Reward rates per duration
- Minimum stake amount
- Maximum stake amount
- Maximum total frozen per user

---

## 🚨 **CRITICAL RULES & VALIDATIONS**

### **Validation Rules:**

1. **Minimum Stake:** 100 DSPOINC (configurable)
2. **Maximum Stake:** Available balance only (cannot freeze more than available)
3. **Maximum Total Frozen:** No limit (or configurable limit per user)
4. **Duration Selection:** Must be one of: 1, 3, 6, 12, 24, 36 months
5. **Balance Check:** Must verify available balance before freezing
6. **Transaction Atomicity:** All database operations must be in transaction

### **Security Rules:**

1. **User Verification:** Only logged-in user can freeze their own DSPOINC
2. **Balance Verification:** Always check balance from `tbl_user_scores` (not cached)
3. **Transaction Locking:** Prevent double-freezing (check for pending transactions)
4. **Audit Trail:** All actions logged in `tbl_score_adjustments`

### **Error Handling:**

1. **Insufficient Balance:** Clear error message with available balance
2. **Invalid Amount:** Validation error with min/max limits
3. **Database Errors:** Rollback transaction, return error
4. **Concurrent Requests:** Handle race conditions with proper locking

---

## 📁 **FILE STRUCTURE**

### **New Files:**

1. **API Endpoints:**
   - `api/user/create-stake.php` - Create new stake
   - `api/user/get-stakes.php` - Get user stakes
   - `api/user/complete-stake.php` - Process completed stakes
   - `api/user/get-staking-stats.php` - Get staking statistics

2. **Frontend Pages:**
   - `public/stake-lab.html` - **NEW DEDICATED PAGE** (full staking interface)
   - `public/js/staking-system.js` - Staking UI logic (used by stake-lab.html)
   - Update `public/profile.html` - Add staking summary card (links to stake-lab.html)

3. **Database:**
   - Migration script: `db/migrations/add_staking_tables.sql`

4. **Documentation:**
   - `12.0/TECHNICAL_DOCUMENTATION/DSPOINC_STAKING_SYSTEM.md`

### **File Organization:**

**Follows Same Pattern as Game Pages:**
- `profile.html` → Shows summary card → Links to `stake-lab.html`
- `stake-lab.html` → Full staking interface (like `tetris.html`, `snake.html`)
- `staking-system.js` → JavaScript logic (like `tetris-scroll.js`, `snake-scroll.js`)

---

## 🔄 **IMPLEMENTATION PHASES**

### **Phase 1: Database & API (Backend)**
- [ ] Create `tbl_dspoinc_stakes` table
- [ ] Create `create-stake.php` API
- [ ] Create `get-stakes.php` API
- [ ] Create `get-staking-stats.php` API
- [ ] Update balance calculation in `profile.php`
- [ ] Test all API endpoints

### **Phase 2: Frontend UI**
- [ ] Add staking summary card to `profile.html` (overview only, links to stake-lab.html)
- [ ] Create `stake-lab.html` page (dedicated full page, matches get-roles.html styling)
- [ ] Create `staking-system.js` for UI logic
- [ ] Implement balance dashboard on stake-lab.html
- [ ] Implement create stake form on stake-lab.html
- [ ] Implement active stakes list on stake-lab.html
- [ ] Implement completed stakes history on stake-lab.html
- [ ] Add loading states and error handling
- [ ] Add Discord login check (same pattern as profile.html)

### **Phase 3: Auto-Processing**
- [ ] Create `complete-stake.php` API
- [ ] Set up cron job or admin trigger
- [ ] Test stake completion flow
- [ ] Add notification system (optional)

### **Phase 4: Admin Interface**
- [ ] Add staking management to admin interface
- [ ] Add reward rate configuration
- [ ] Add stake monitoring dashboard
- [ ] Add manual stake completion option

### **Phase 5: Testing & Documentation**
- [ ] Test all scenarios
- [ ] Test edge cases
- [ ] Update technical documentation
- [ ] Update rules
- [ ] Create user guide

---

## 🎨 **UI/UX DESIGN PATTERNS**

### **Page Styling (stake-lab.html):**

**Follow `get-roles.html` and `profile.html` Patterns:**

1. **Header/Navigation:**
   - Same navigation bar as profile.html
   - Sticky header with backdrop blur
   - Links: Home, Profile, Get Roles, etc.

2. **Page Theme:**
   - **Color Scheme:** Ice/Blue gradient (🧊 theme)
   - **Background:** Gradient from slate-900 via blue-900 to slate-900
   - **Accent Colors:** Blue-400, Cyan-400, Ice-blue tones
   - **Border Colors:** Blue-400/50, Cyan-400/50

3. **Card Design:**
   - Match card pattern from get-roles.html
   - Backdrop blur effect
   - Border with hover effects
   - Smooth transitions

4. **Button Styling:**
   - Match existing button styles from profile.html
   - Gradient backgrounds
   - Hover effects (scale, shadow)
   - Icon + text labels

5. **Responsive Design:**
   - Mobile-friendly layout
   - Responsive grid system
   - Touch-friendly buttons

### **Visual Elements:**

- **Page Icon:** 🧊 (ice cube) - Primary icon throughout
- **Progress Bars:** Show time remaining for active stakes (animated)
- **Reward Badge:** Highlight expected rewards (gold/yellow accent)
- **Countdown Timer:** Days/hours remaining for active stakes
- **Balance Cards:** Large, prominent display (like DSPOINC section)
- **Stake Cards:** Individual cards for each active stake

### **Page Layout Structure:**

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Same meta tags as get-roles.html -->
  <!-- Tailwind CSS -->
  <!-- Discord config -->
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
  <!-- Navigation Bar (same as profile.html) -->
  <!-- Hero Section: "🧊 Stake Lab" -->
  <!-- Balance Dashboard Section -->
  <!-- Create Stake Section -->
  <!-- Active Stakes Section -->
  <!-- Completed Stakes Section -->
  <!-- Information/Help Section -->
  <!-- Footer -->
  <script src="js/staking-system.js"></script>
</body>
</html>
```

### **Profile Page Card Integration:**

**Location in profile.html:** After DSPOINC section, in game cards area

**Card Code Pattern:**
```html
<!-- Staking Card (matches Tetris/Snake card pattern) -->
<a href="stake-lab.html" class="group bg-gradient-to-br from-blue-600 to-cyan-600 rounded-xl p-4 border-2 border-blue-400 hover:border-yellow-400 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/50 cursor-pointer">
  <div class="text-3xl mb-2 group-hover:animate-bounce">🧊</div>
  <h3 class="text-sm font-bold mb-1">DSPOINC Staking</h3>
  <p class="text-xs text-blue-200">Freeze & Earn Rewards</p>
  <div class="mt-2 text-xs space-y-1">
    <p>Available: <span id="staking-available-balance">-</span> DSPOINC</p>
    <p>Frozen: <span id="staking-frozen-balance">-</span> DSPOINC</p>
    <p>Active: <span id="staking-active-count">-</span> stakes</p>
  </div>
</a>
```

**JavaScript to Load Summary Data:**
```javascript
// Load staking summary for profile page card
async function loadStakingSummary() {
  try {
    const response = await fetch(`${API_BASE_URL}/api/user/get-staking-stats.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await response.json();
    if (data.success) {
      document.getElementById('staking-available-balance').textContent = 
        formatNumber(data.data.available_balance);
      document.getElementById('staking-frozen-balance').textContent = 
        formatNumber(data.data.frozen_balance);
      document.getElementById('staking-active-count').textContent = 
        data.data.active_stakes_count || 0;
    }
  } catch (error) {
    console.error('Error loading staking summary:', error);
  }
}
```

---

## 📊 **EXAMPLE USER FLOW**

### **Scenario: User Freezes 1,000 DSPOINC for 6 Months**

1. **User Views Profile:**
   - Sees total balance: 15,000 DSPOINC
   - Sees staking summary card showing:
     - Available: 15,000 DSPOINC
     - Frozen: 0 DSPOINC
     - Active: 0 stakes
   - Clicks on staking card

2. **User Opens Stake Lab Page:**
   - Redirected to `stake-lab.html`
   - Sees full staking interface
   - Sees balance dashboard with all details
   - Sees create stake form

3. **User Creates Stake:**
   - Enters amount: 1,000 DSPOINC
   - Selects duration: 6 months
   - Sees expected reward: 100 DSPOINC (10%)
   - Sees unfreeze date: June 25, 2026
   - Clicks "Freeze DSPOINC" button

4. **System Processes:**
   - Validates balance (15,000 >= 1,000) ✅
   - Creates stake record in database
   - Deducts 1,000 from available balance
   - Creates transaction records
   - Returns success response

5. **User Sees Updated Interface:**
   - Balance dashboard updates:
     - Total balance: 15,000 DSPOINC (unchanged)
     - Available balance: 14,000 DSPOINC (reduced)
     - Frozen balance: 1,000 DSPOINC (new)
   - New active stake appears in list
   - Shows countdown timer: 182 days remaining
   - Shows progress bar: 0% (just started)

6. **User Returns to Profile:**
   - Staking summary card updates:
     - Available: 14,000 DSPOINC
     - Frozen: 1,000 DSPOINC
     - Active: 1 stake

7. **After 6 Months:**
   - System auto-processes stake (cron/admin)
   - User receives: 1,000 (original) + 100 (reward) = 1,100 DSPOINC
   - Stake moves to "Completed" section on stake-lab.html
   - Balance updates automatically
   - User can view completed stake in history

---

## 🔐 **SECURITY CONSIDERATIONS**

1. **Session Verification:** All APIs require valid Discord session
2. **User ID Verification:** Users can only access their own stakes
3. **Balance Verification:** Always verify from database (not frontend)
4. **Transaction Locking:** Prevent concurrent stake creation
5. **Input Validation:** Sanitize all user inputs
6. **SQL Injection Prevention:** Use prepared statements
7. **Rate Limiting:** Prevent spam stake creation

---

## 📝 **NOTES FOR IMPLEMENTATION**

### **Balance Display Logic:**

**Available Balance = Total Balance - Frozen Balance**

**Where:**
- Total Balance = `SUM(score) FROM tbl_user_scores WHERE user_id = ?`
- Frozen Balance = `SUM(amount) FROM tbl_dspoinc_stakes WHERE user_id = ? AND status = 'active'`

### **Transaction Records:**

**When Freezing:**
- `tbl_user_scores`: Negative entry (e.g., -1000)
- `tbl_wallet_transactions`: Record freeze transaction
- `tbl_score_adjustments`: Audit entry with reason "DSPOINC frozen for staking"

**When Unfreezing:**
- `tbl_user_scores`: Positive entry (e.g., +1100 = original + reward)
- `tbl_wallet_transactions`: Record unfreeze transaction
- `tbl_score_adjustments`: Audit entry with reason "Stake completed: 1000 DSPOINC + 100 reward"

### **Reward Rate Storage:**

**Option 1:** Hardcoded in API (simple, but not flexible)
**Option 2:** Store in `tbl_game_settings` (flexible, admin-configurable)
**Option 3:** New `tbl_staking_config` table (most flexible)

**Recommendation:** Start with Option 1, migrate to Option 2/3 later.

---

## ✅ **SUCCESS CRITERIA**

1. ✅ Users can freeze DSPOINC for selected durations
2. ✅ Balance correctly shows available vs frozen
3. ✅ Active stakes display with countdown
4. ✅ Completed stakes show in history
5. ✅ Rewards calculated and paid correctly
6. ✅ All transactions tracked and auditable
7. ✅ UI matches existing profile page design
8. ✅ System handles edge cases gracefully

---

## 🚀 **NEXT STEPS**

1. **Review this plan** with team
2. **Confirm reward rates** and configuration
3. **Create database migration** script
4. **Implement Phase 1** (Database & API)
5. **Test API endpoints** thoroughly
6. **Implement Phase 2** (Frontend UI)
7. **Test complete flow** end-to-end
8. **Deploy to production**

---

---

## 📋 **PLAN SUMMARY**

### **Architecture Decision:**
✅ **Dedicated Page Approach** (Like Game Pages)
- Profile page: Summary card only (overview stats)
- Dedicated page: `stake-lab.html` (full interface)
- Pattern: Matches Tetris, Snake, Space Invaders architecture

### **Key Files:**
1. **New Page:** `public/stake-lab.html` - Full staking interface
2. **New Script:** `public/js/staking-system.js` - Staking logic
3. **Updated:** `public/profile.html` - Add summary card
4. **New APIs:** 4 endpoints in `api/user/` directory
5. **New Table:** `tbl_dspoinc_stakes` - Stake records

### **Reward Rates (Proposed):**
- 1 month: 2%
- 3 months: 5%
- 6 months: 10%
- 12 months: 20%
- 24 months: 45%
- 36 months: 75%

### **Implementation Order:**
1. Phase 1: Database & API (Backend)
2. Phase 2: Frontend UI (stake-lab.html + profile card)
3. Phase 3: Auto-Processing (Cron/Admin)
4. Phase 4: Admin Interface
5. Phase 5: Testing & Documentation

---

**Plan Created:** December 25, 2025  
**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Architecture:** ✅ Dedicated page approach confirmed  
**Next:** Review and confirm reward rates, then begin Phase 1

