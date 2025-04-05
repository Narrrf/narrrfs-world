CREATE TABLE tbl_users (
  wallet_address TEXT PRIMARY KEY,
  role TEXT,
  staking_balance INT,
  nft_count INT
);

CREATE TABLE tbl_traits (
  wallet_address TEXT,
  trait TEXT,
  PRIMARY KEY (wallet_address, trait)
);

CREATE TABLE tbl_rewards (
  wallet_address TEXT,
  reward_amount INT,
  last_updated TIMESTAMP
);

CREATE TABLE tbl_cheese_clicks (
  user_wallet TEXT,
  egg_id TEXT,
  timestamp TIMESTAMP
);
