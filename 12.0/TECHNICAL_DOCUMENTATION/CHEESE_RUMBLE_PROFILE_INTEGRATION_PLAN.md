# 🧀 CHEESE RUMBLE - PROFILE PAGE & ADMIN INTEGRATION PLAN

**Created:** December 4, 2025  
**Purpose:** Complete integration of Cheese Rumble as 6th game on profile page and admin interface  
**Status:** 🔄 **IN PROGRESS**

---

## 🎯 **IMPLEMENTATION PLAN**

### **Phase 1: Fix DSPOINC Recording ✅**
- [x] Fix `tbl_user_scores` INSERT logic (always insert new row)
- [x] Ensure DSPOINC shows in score adjustments

### **Phase 2: API Integration**
- [ ] Add Cheese Rumble to `user-game-missions.php` API
- [ ] Query `tbl_rumble_participants` for stats
- [ ] Include in overall DSPOINC calculation
- [ ] Update games_played count (5/5 → 6/6)

### **Phase 3: Profile Page Integration**
- [ ] Add Cheese Rumble card to All-Time Statistics section
- [ ] Add Cheese Rumble card to Current Season Statistics section
- [ ] Update JavaScript to display stats
- [ ] Match styling with other 5 games

### **Phase 4: Admin Interface Integration**
- [ ] Add Cheese Rumble tab to Game Management
- [ ] Display rumble statistics
- [ ] Show active/waiting/finished rumbles
- [ ] Match admin interface styling

---

## 📊 **STATISTICS TO DISPLAY**

### **All-Time Statistics:**
- **Total Rumbles:** Count of all rumbles participated
- **Wins:** Count where `status = 'winner'` or `final_position = 1`
- **Podium Finishes:** Count where `final_position <= 3`
- **Best Position:** MIN(`final_position`)
- **DSPOINC Earned:** SUM(`dspoinc_earned`) from `tbl_rumble_participants`

### **Current Season Statistics:**
- Same stats but filtered by current season
- Show "✔ Active" or "❌ Not Played" status
- Display last played date

---

## 🔧 **TECHNICAL REQUIREMENTS**

### **Database Queries Needed:**

**All-Time Stats:**
```sql
SELECT 
    COUNT(*) as total_rumbles,
    COUNT(CASE WHEN status = 'winner' OR final_position = 1 THEN 1 END) as wins,
    COUNT(CASE WHEN final_position <= 3 THEN 1 END) as podiums,
    MIN(final_position) as best_position,
    SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned,
    MAX(COALESCE(joined_at, updated_at, finished_at)) as last_played
FROM tbl_rumble_participants 
WHERE user_id = ?
```

**Season Stats:**
```sql
SELECT 
    COUNT(*) as total_rumbles,
    COUNT(CASE WHEN status = 'winner' OR final_position = 1 THEN 1 END) as wins,
    COUNT(CASE WHEN final_position <= 3 THEN 1 END) as podiums,
    MIN(final_position) as best_position,
    SUM(COALESCE(dspoinc_earned, 0)) as total_dspoinc_earned,
    MAX(COALESCE(joined_at, updated_at, finished_at)) as last_played
FROM tbl_rumble_participants rp
JOIN tbl_cheese_rumbles cr ON rp.rumble_id = cr.rumble_id
WHERE rp.user_id = ?
AND (
    cr.created_at >= ? 
    AND cr.created_at < ?
)
```

### **API Response Structure:**
```json
{
  "cheese_rumble": {
    "total_rumbles": 0,
    "wins": 0,
    "podiums": 0,
    "best_position": null,
    "dspoinc_earned": 0,
    "last_played": null
  }
}
```

---

## 📝 **FILE CHANGES REQUIRED**

### **1. API Files:**
- `api/user/user-game-missions.php` - Add Cheese Rumble stats section

### **2. Frontend Files:**
- `public/profile.html` - Add Cheese Rumble display cards

### **3. Admin Files:**
- `public/admin-interface.html` - Add Cheese Rumble tab
- `api/admin/get-all-games-stats.php` - Add Cheese Rumble stats

### **4. Discord Bot:**
- `discord/commands/cheese-rumble.js` - Fix DSPOINC recording

---

**Last Updated:** December 4, 2025  
**Status:** 🔄 **IMPLEMENTATION STARTING**  
**Next Steps:** Fix DSPOINC recording, then API integration

