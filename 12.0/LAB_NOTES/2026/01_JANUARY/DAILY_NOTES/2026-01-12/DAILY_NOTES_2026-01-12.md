# 🧀 DAILY NOTES — 2026-01-12

**Date:** 2026-01-12  
**Status:** ✅ Stable session (collision/debug regression rolled back)  
**Session Theme:** Level 5 → Level 6 stability + HUD standardization + security review kickoff

---

## ✅ What was completed

### **Level 5 → Level 6 transition stability**
- Level 5 “Quick Mode” confirmed (1 wave / 5 monsters) to keep portal flow testable.
- Completion screen clickability + pause behavior aligned with other levels.
- Level 6 visibility after warp stabilized (no stuck pause overlay).
- Level 6 chest `chest_011` spawn stabilized (spawns in front of player) + correct Y + collision verified.

### **Riddle HUD standardization**
- Standardized riddle HUD across Levels 1–6 verified as working.

### **Rollback: Level 5 collision + Debug Helpers toggle**
- Attempted Level 5 collision improvements (walls + glyphs) and an Options menu toggle for Debug Helpers panel.
- Regression: starting game to selected level (ex: Level 5) broke and effectively fell back to Level 1.
- Resolution: reverted those changes and pushed a commit to restore stable start/warp selection.
- Reference: `LEVEL5_QUICK_MODE_PORTAL_WARP_FIX_2026-01-12.md` (Addendum).

---

## 🔒 Security review kickoff (team findings)

**Initial high-risk endpoints identified:**
- `api/admin/download-database.php` (appears to allow downloading the LIVE DB)
- `api/admin/upload-database.php` (contains a hardcoded secret bypass for auth)
- `api/discord/db-access.php` (arbitrary SQL endpoint; verify token handling + logging)

**NEW CRITICAL FINDING: Discord Role ID Exposure**
- **Issue:** `api/auth/sync-role.php` returns `role_ids` array to browsers
- **Impact:** Role IDs exposed to all logged-in users, makes role mention spam easier
- **Phase 1 (COMPLETE):** Discord server settings — All roles/pings disabled (user action)
- **Phase 2 (IN PROGRESS):** Code changes — Remove `role_ids` from API, switch all clients to role names
- **Files affected:** sync-role.php, profile.html, tetris/snake/space-invaders scripts, three.js/main.js
- **Reference:** `SECURITY_REVIEW_TEAM_FLAGS_2026-01-12.md` (updated), `PHASE2_ROLE_ID_REMOVAL_PLAN_2026-01-12.md` (implementation plan)

---

## 📁 Files involved today (high signal)

- `public/three.js/main.js` (Level 5/6 stability work; collision/debug-toggle attempt rolled back)
- `12.0/ACTIVE_STATUS/QUICK_STATUS.md` (updated with rollback note)
- `12.0/ACTIVE_STATUS/DAILY_STATUS_2026-01-12.md` (status snapshot)
- `12.0/SECURITY/STAKING_API_SECURITY_AUDIT_2025-12-29.md` (reference audit)

---

## ✅ Next steps

1. Decide whether to **fix** the newly identified security issues now (recommended) or document only.
2. If fixing Level 5 collision again later: reintroduce using **safe initialization ordering** so it cannot impact game start flow.

