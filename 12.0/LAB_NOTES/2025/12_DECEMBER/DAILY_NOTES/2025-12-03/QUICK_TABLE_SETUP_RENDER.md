# 🚀 QUICK SETUP - CREATE TABLES ON RENDER

**Bot Status:** ✅ **RUNNING**  
**Next Step:** Create database tables

---

## 📋 **SIMPLE 3-STEP PROCESS**

### **Step 1: Open Render Shell**
- Go to Render dashboard
- Open Shell for your web service

### **Step 2: Run This Command**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite << 'EOF'
CREATE TABLE IF NOT EXISTS tbl_cheese_rumbles (
    rumble_id TEXT PRIMARY KEY,
    creator_id TEXT NOT NULL,
    creator_name TEXT NOT NULL,
    channel_id TEXT NOT NULL,
    message_id TEXT,
    status TEXT NOT NULL DEFAULT 'waiting',
    max_players INTEGER DEFAULT 10,
    duration INTEGER DEFAULT 300,
    dspoinc_reward INTEGER DEFAULT 5000,
    role_reward TEXT,
    tag_role TEXT,
    auto_start INTEGER DEFAULT 0,
    comment TEXT,
    current_round INTEGER DEFAULT 0,
    players_alive INTEGER DEFAULT 0,
    events_log TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    finished_at DATETIME,
    ended_at DATETIME,
    winner_id TEXT,
    winner_name TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tbl_rumble_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rumble_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    username TEXT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'alive',
    kills INTEGER DEFAULT 0,
    eliminated_by TEXT,
    elimination_reason TEXT,
    eliminated_in_round INTEGER,
    final_position INTEGER,
    dspoinc_earned INTEGER DEFAULT 0,
    season TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rumble_id) REFERENCES tbl_cheese_rumbles(rumble_id)
);

CREATE INDEX IF NOT EXISTS idx_rumble_status ON tbl_cheese_rumbles(status);
CREATE INDEX IF NOT EXISTS idx_rumble_creator ON tbl_cheese_rumbles(creator_id);
CREATE INDEX IF NOT EXISTS idx_rumble_participants_rumble ON tbl_rumble_participants(rumble_id);
CREATE INDEX IF NOT EXISTS idx_rumble_participants_user ON tbl_rumble_participants(user_id);
CREATE INDEX IF NOT EXISTS idx_rumble_participants_status ON tbl_rumble_participants(status);
EOF
```

### **Step 3: Verify Tables Created**
```bash
sqlite3 /var/www/html/db/narrrf_world.sqlite ".tables" | grep rumble
```

**Expected output:**
```
tbl_cheese_rumbles
tbl_rumble_participants
```

---

## ✅ **THAT'S IT!**

After creating tables, your bot should be ready to use Cheese Rumble!

**Test command in Discord:**
```
/cheese-rumble create players:10 rounds:5 reward:5000
```

---

## 📝 **ALTERNATIVE: Copy SQL File**

If you prefer, you can also:

1. **Copy SQL file to Render** (via git push or file upload)
2. **Execute from file:**
   ```bash
   sqlite3 /var/www/html/db/narrrf_world.sqlite < /var/www/html/db/migrations/create_cheese_rumble_tables.sql
   ```

---

**Status:** ✅ **BOT RUNNING** - Just create tables and you're done! 🚀

