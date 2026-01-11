# 🛡️ Moderator Onboarding (Handy + Mobile)

> **Note:** No single “moderator onboarding” master doc was found in `12.0/` by filename/phrase.  
> This onboarding is a **clean, shareable moderator guide** aligned with current systems (Jan 2026) and the new Community Manager ownership of partners.

---

## Handy version (copy/paste)

## 🎯 Role of a Narrrfs World Moderator
- Keep community spaces safe, friendly, and productive
- Help route questions to the right place (support vs bugs vs partners)
- Protect the brand: no scams, no fake links, no impersonation
- Support events (Discord Race / Cheese Rumble / giveaways) with calm coordination

---

## 🧭 Quick map of the ecosystem (so you can answer questions)
- **Website:** `narrrfs.world`
- **Player hub:** `profile.html` (DSPOINC, stats, missions, store)
- **Games:** 7 integrated games (web + Discord-native + 3D)
- **DSPOINC:** universal in-game currency (earn + spend; staking exists)
- **Discord bot:** commands + events + economy touchpoints
- **Nerd Lab admin:** staff-only ops panel
- **Partners:** `partners.html` (public partner portal)

---

## 🧯 Triage playbook (what to do when something happens)

### 1) Player support (common)
**Problem:** “My DSPOINC is wrong / missing / didn’t update”
- Collect: Discord username + (if safe) Discord ID
- Ask what they did (game, time, screenshot if possible)
- Route to Community Manager / staff for Nerd Lab verification

### 2) Bug reports
**Problem:** “Game broke / page not loading / weird error”
- Route to: `bug-report.html` (or the Discord bug channel)
- Require: reproduction steps + device/browser + screenshot/clip

### 3) Security / scam / fake links
- Remove the message (or ask staff to)
- Warn/timeout/ban depending on severity
- Post the correct official link if needed

### 4) Partner issues
**Problem:** “Our logo/link/info is wrong on partners page”
- Confirm the partner name + what needs updating
- Route to Community Manager (partner owner)
- Source of truth is DB-driven: `partners.html` → `api/user/get-partners.php` → `tbl_partners`
- Track missing assets in: `12.0/COLLABORATIONS_PARTNERS/PARTNER_ASSET_TRACKER.md`

---

## ✅ Moderator “daily checklist”
- Watch for scam links + impersonation
- Keep event channels clean during active events
- Encourage bug reports with reproduction steps
- Route partner questions to Community Manager

---

## Mobile version (ultra-short)

Moderator job: keep Discord safe + route issues.
- DSPOINC/account issues → Community Manager/staff
- Bugs → `bug-report.html` + steps/screenshot
- Partner issues (`partners.html`) → Community Manager + asset tracker

