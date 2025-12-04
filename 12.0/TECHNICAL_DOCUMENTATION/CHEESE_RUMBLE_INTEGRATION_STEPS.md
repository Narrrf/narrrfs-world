# 🧀 CHEESE RUMBLE - COMPLETE INTEGRATION STEPS

**Created:** December 4, 2025  
**Purpose:** Step-by-step guide for integrating Cheese Rumble as 6th game  
**Status:** 🔄 **IMPLEMENTATION IN PROGRESS**

---

## ✅ **STEP 1: Fix DSPOINC Recording (COMPLETED)**

**Changes Made:**
- ✅ Modified `discord/commands/cheese-rumble.js` to ALWAYS INSERT new rows
- ✅ Removed UPDATE logic that was causing missing records
- ✅ Both winner and first-out rewards now properly insert to `tbl_user_scores`

**Files Changed:**
- `discord/commands/cheese-rumble.js` (lines 1768-1781, 1531-1547)

---

## 🔄 **STEP 2: Add to user-game-missions.php API**

### **2.1: Add Response Structure**

Add after Discord Race section (around line 206):
```php
'cheese_rumble' => [
    'total_rumbles' => 0,
    'wins' => 0,
    'podiums' => 0,
    'best_position' => null,
    'dspoinc_earned' => 0,
    'last_played' => null
],
```

### **2.2: Add Query Logic**

Add after Discord Race query (after line 555):
```php
// 6. CHEESE RUMBLE STATS (using user_id from tbl_rumble_participants)
try {
    error_log("🔍 CHEESE RUMBLE DEBUG: Querying for user $discordId");
    
    // Query for Cheese Rumble stats (all-time first, then season)
    $rumbleData = null;
    $rumbleSeasonFilters = [
        ['condition' => 'cr.created_at >= ? AND cr.created_at < ?', 'label' => 'date_range']
    ];

    foreach ($rumbleSeasonFilters as $filter) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_rumbles,
                COUNT(CASE WHEN rp.status = 'winner' OR rp.final_position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN rp.final_position <= 3 AND rp.final_position IS NOT NULL THEN 1 END) as podiums,
                MIN(rp.final_position) as best_position,
                SUM(COALESCE(rp.dspoinc_earned, 0)) as total_dspoinc_earned,
                MAX(COALESCE(rp.joined_at, rp.updated_at, rp.finished_at)) as last_played
            FROM tbl_rumble_participants rp
            JOIN tbl_cheese_rumbles cr ON rp.rumble_id = cr.rumble_id
            WHERE rp.user_id = ?
            AND (
                " . $filter['condition'] . "
                OR cr.created_at >= ?
            )
        ");
        $stmt->execute([
            $discordId,
            $currentSeasonStart,
            $currentSeasonEnd,
            $currentSeasonStart
        ]);
        $rumbleData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rumbleData && (int)$rumbleData['total_rumbles'] > 0) {
            error_log("✅ CHEESE RUMBLE: season match for $discordId: " . $rumbleData['total_rumbles']);
            break;
        }
    }
    
    // Fallback: all-time stats
    if (!$rumbleData || (int)$rumbleData['total_rumbles'] === 0) {
        $fallbackStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_rumbles,
                COUNT(CASE WHEN status = 'winner' OR final_position = 1 THEN 1 END) as wins,
                COUNT(CASE WHEN final_position <= 3 AND final_position IS NOT NULL THEN 1 END) as podiums,
                MIN(final_position) as best_position,
                SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned,
                MAX(COALESCE(joined_at, updated_at, finished_at)) as last_played
            FROM tbl_rumble_participants 
            WHERE user_id = ?
        ");
        $fallbackStmt->execute([$discordId]);
        $rumbleData = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($rumbleData && (int)$rumbleData['total_rumbles'] > 0) {
        $response['cheese_rumble']['total_rumbles'] = (int)$rumbleData['total_rumbles'];
        $response['cheese_rumble']['wins'] = (int)$rumbleData['wins'];
        $response['cheese_rumble']['podiums'] = (int)$rumbleData['podiums'];
        $response['cheese_rumble']['best_position'] = $rumbleData['best_position'] ? (int)$rumbleData['best_position'] : null;
        $response['cheese_rumble']['dspoinc_earned'] = (int)$rumbleData['total_dspoinc_earned'];
        $response['cheese_rumble']['last_played'] = $rumbleData['last_played'];
        error_log("✅ Cheese Rumble stats found for user $discordId: " . $rumbleData['total_rumbles'] . " rumbles");
    }
} catch (Exception $e) {
    error_log("Cheese Rumble query error: " . $e->getMessage());
}
```

### **2.3: Update Overall Calculations**

Update lines 558-571:
```php
// Calculate total DSPOINC from all games
$response['overall']['total_dspoinc'] = 
    $response['tetris']['dspoinc_earned'] + 
    $response['snake']['dspoinc_earned'] + 
    $response['space_invaders']['dspoinc_earned'] + 
    $response['cheese_hunt']['dspoinc_earned'] + 
    $response['discord_race']['dspoinc_earned'] +
    $response['cheese_rumble']['dspoinc_earned'];

// Count how many games the user has played
$gamesPlayed = 0;
if ($response['tetris']['total_games'] > 0) $gamesPlayed++;
if ($response['snake']['total_games'] > 0) $gamesPlayed++;
if ($response['space_invaders']['total_games'] > 0) $gamesPlayed++;
if ($response['cheese_hunt']['total_clicks'] > 0) $gamesPlayed++;
if ($response['discord_race']['total_races'] > 0) $gamesPlayed++;
if ($response['cheese_rumble']['total_rumbles'] > 0) $gamesPlayed++; // NEW

$response['overall']['games_played'] = $gamesPlayed;
```

---

## 🔄 **STEP 3: Add to profile.html**

### **3.1: Find Game Display Sections**

Search for where Discord Race is displayed and add Cheese Rumble after it.

### **3.2: Add All-Time Statistics Card**

Add after Discord Race card in "All-Time Statistics" section.

### **3.3: Add Current Season Statistics Card**

Add after Discord Race card in "Current Season Statistics" section.

### **3.4: Update JavaScript**

Add Cheese Rumble to the game stats loading logic.

---

## 🔄 **STEP 4: Add to Admin Interface**

### **4.1: Add to get-all-games-stats.php**

Add Cheese Rumble statistics to admin API.

### **4.2: Add Game Management Tab**

Add Cheese Rumble tab similar to other games.

---

**Implementation Order:**
1. ✅ Step 1: Fix DSPOINC Recording
2. 🔄 Step 2: API Integration (in progress)
3. ⏳ Step 3: Profile Page Integration
4. ⏳ Step 4: Admin Interface Integration

---

**Last Updated:** December 4, 2025  
**Status:** 🔄 **STEP 2 IN PROGRESS**

