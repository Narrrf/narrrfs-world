-- Draft planning only; separate approval required before applying anywhere.
BEGIN IMMEDIATE;
CREATE TABLE tbl_christmas_advent_2026_draft_revisions (
 revision INTEGER PRIMARY KEY CHECK(revision > 0),
 event_key TEXT NOT NULL CHECK(event_key='christmas_advent_2026'),
 draft_json TEXT NOT NULL CHECK(length(draft_json) BETWEEN 2 AND 65536),
 fingerprint TEXT NOT NULL CHECK(length(fingerprint)=64),
 actor_user_id TEXT NOT NULL,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
COMMIT;
