-- 👤 User Info (login/session base)
CREATE TABLE IF NOT EXISTS tbl_users (
  discord_id TEXT PRIMARY KEY,
  username TEXT NOT NULL,
  avatar_url TEXT
);

-- 🧀 User-to-Discord Role Mapping (e.g. VIP Holder, Founder)
CREATE TABLE IF NOT EXISTS tbl_user_roles (
  user_id TEXT,
  role_name TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_name),
  FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);

-- 🌱 Trait System (e.g. Golden Cheese Hunter, Curious Mind)
CREATE TABLE IF NOT EXISTS tbl_user_traits (
  user_id TEXT,
  trait TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, trait),
  FOREIGN KEY (user_id) REFERENCES tbl_users(discord_id)
);

-- 🧪 Puzzle Interaction Log (e.g. Egg clicks)
CREATE TABLE IF NOT EXISTS tbl_cheese_clicks (
  user_wallet TEXT,
  egg_id TEXT,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 🎁 Rewards (unlocked by traits)
CREATE TABLE IF NOT EXISTS tbl_rewards (
  reward_id TEXT PRIMARY KEY,
  reward_name TEXT,
  unlock_trait TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
