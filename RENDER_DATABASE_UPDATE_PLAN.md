# Render Database Update Plan

## Current Live Status
- **Active Season:** Season 4 - The Ultimate Cheese Challenge  
- **Data Distribution:**
  - Tetris: 36 scores in Season 4, 35 in Season 3
  - Snake: 136 scores in Season 4, 347 in Season 3
  - Space Invaders: 207 scores in Season 4, 139 in Season 3

## Target Local Status  
- **Active Season:** Season 3 - The Ultimate Cheese Challenge
- **Season 3 Start Date:** 2025-09-16 00:00:00
- **All current data:** Consolidated into Season 3

## Steps to Execute on Render

### 1. Backup Live Database
```bash
cp /var/www/html/db/narrrf_world.sqlite /data/narrrf_world.sqlite
```

### 2. Upload SQL Script
```bash
echo "-- SQL commands here" > /tmp/update.sql
```

### 3. Execute Database Updates
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite < /tmp/update.sql
```

### 4. Verify Changes
```bash
echo "SELECT season_name, is_active FROM tbl_seasons;" | sqlite3 /var/www/html/db/narrrf_world.sqlite
```

### 5. Test API Response
```bash
curl -s https://narrrfs.world/api/dev/get-leaderboard.php | grep current_season
```

## Expected Results After Update
- Season 3 becomes active
- All Season 4 data moves to Season 3  
- Data from before 2025-09-16 moves to season_2
- Live site displays same leaderboard as local

## Rollback Plan
If issues occur:
```bash
cp /data/narrrf_world.sqlite /var/www/html/db/narrrf_world.sqlite
```
