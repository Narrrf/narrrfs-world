# 🧀 Narrrfs World — Community Manager Onboarding (System + Partners)

**Created:** 2026-01-10  
**Audience:** New Community Manager (Operations + Partnerships)  
**Purpose:** A complete, non-dev onboarding reference for “what Narrrfs World is”, how the ecosystem works, and how to manage the Partner Portal + existing contract partners.  
**Scope:** Website + Games + DSPOINC + Discord + Admin (“Nerd Lab”) + Partners

---

## 🎯 What Narrrfs World *is* (one paragraph)

Narrrfs World is a **web-based gaming ecosystem** where players log in with **Discord**, play multiple games, and earn **DSPOINC** (our universal in-game currency). The ecosystem includes **7 integrated games**, a **Discord bot** (community ops + games + economy), an **admin interface (“Nerd Lab”)** for full operations control, a **store + inventory**, **achievements**, **quests**, **season management**, **NFT holder verification**, and a **Partner Portal** showing our collaborations.

---

## 🧭 Core pillars (what we stand for)

- **Community-first gaming**: Discord is the “home base” for identity, roles, events, and support.
- **Unified progression**: One account → one DSPOINC balance → shared progression across games.
- **Fair competition**: Season system + leaderboards + clear rules.
- **Professional operations**: Admin tooling (“Nerd Lab”), audit trails, bug tracking, and docs-first continuity.
- **Partnership network**: Partner Portal + cross-community value (listing, promotions, holder perks where applicable).

---

## 🎮 The 7 Games (high-level)

1. **Tetris** (`public/tetris.html`)  
2. **Snake** (`public/snake.html`)  
3. **Space Invaders** (`public/space-cheese-invaders.html`)  
4. **Cheese Hunt** (embedded + click-based mechanics; visible across core site)  
5. **Discord Race** (Discord bot-driven event game)  
6. **Cheese Rumble** (Discord bot-driven battle royale event game)  
7. **3D Riddle Game** (`public/3d-riddle.html`) + Three.js assets in `public/three.js/`

**Player home base:** `public/profile.html` (mission status, DSPOINC, achievements, store, etc.)

---

## 💰 DSPOINC (economy basics)

- **DSPOINC = universal in-game currency** across the ecosystem.
- Players earn DSPOINC from gameplay, quests, events, and special systems (riddles/chests in 3D).
- DSPOINC is visible on the **profile page** and in Discord via bot commands.
- **Staking** exists (“freeze DSPOINC” for rewards): `public/stake-lab.html` (community-facing) and related admin tooling.

---

## 🤖 Discord: roles, bot, and daily operations

### Identity + Access
- Discord account is the primary identity.
- Roles are used for:
  - Community tiers (VIP/Holder/etc.)
  - Access gates
  - Multipliers / benefits (where configured)

### Discord Bot (operational backbone)
The bot is the “ops runtime” for:
- Balances / profiles / inventory / store access
- Event games (Discord Race, Cheese Rumble)
- Giveaways, bug tracker workflows, Twitter mission workflows (as configured)

**Reference:** `12.0/YEAR_END_2025/DISCORD_BOT_COMPLETE_TECHNICAL.md`  
(You don’t need to code it, but it’s the canonical “what exists” inventory.)

---

## 🖥️ Nerd Lab (Admin Interface): what you’ll use most

**Admin interface page:** `public/admin-interface.html`  

### What it is
The Nerd Lab is the centralized operations panel for:
- User support (balances, roles, inventory)
- DSPOINC adjustments and audit trail
- Store items and purchases
- Quests / missions
- Season management + leaderboards
- Discord configuration (where applicable)
- Bug tracker and operational dashboards
- Partner management (Partner Portal entries)

**Reference:** `12.0/YEAR_END_2025/ADMIN_INTERFACE_COMPLETE_TECHNICAL.md`

### Safe operating mindset (non-dev)
- Use the admin tools for **support + operations**.
- If something looks like it could “mass reset” data, escalate before doing it.
- Prefer documenting actions (who/what/why) for any manual DSPOINC changes.

---

## 🌐 Website pages you’ll reference often (community-facing)

- **Home / funnel:** `public/index.html`
- **Profile (player hub):** `public/profile.html`
- **Partners:** `public/partners.html`
- **Get Roles:** `public/get-roles.html`
- **Stake Lab:** `public/stake-lab.html`
- **Bug report:** `public/bug-report.html`
- **Bug tracker (collab view):** `public/bug-tracker-collab.html`
- **Updates / comms:** `public/project-updates.html`

**Reference:** `12.0/YEAR_END_2025/FRONTEND_WEBSITE_COMPLETE_TECHNICAL.md`

---

## 🤝 Partner Portal (your new responsibility)

### What “Partners” means in our system
The Partner Portal is a **database-driven** partners page used for:
- Showcasing collaborations publicly (`partners.html`)
- Keeping partner links + brand assets current (logo/banner/gallery)
- Supporting partner outreach and ongoing relationship management

### Where the Partner Portal data comes from
- **Frontend page:** `public/partners.html`
- **Public API:** `api/user/get-partners.php`
  - Returns active partners from **`tbl_partners`**
- **Admin management API:** `api/admin/partner-management.php`
  - Used by the Partners tools inside `public/admin-interface.html`

### Local vs Production URLs (important)
- **Local:** `http://localhost/public/partners.html`
- **Production:** `https://narrrfs.world/partners.html`

### Partner images (important for uploads + 404 prevention)
Partners page is environment-aware:
- **Local images URL prefix:** `/public/img/partners/...`
- **Production images URL prefix:** `/img/partners/...` (NO `/public/` on prod)

### Internal partner ops docs you should use
- **Asset/Status tracker:** `12.0/COLLABORATIONS_PARTNERS/PARTNER_ASSET_TRACKER.md`
- **Outreach templates:** `12.0/TECHNICAL_DOCUMENTATION/PARTNER_INVITATION_TEMPLATE.md`
- **Partner Portal system background:** `12.0/LAB_NOTES/2025/10_OCTOBER/DAILY_NOTES/2025-10-28/PARTNER_PORTAL_SYSTEM_COMPLETE.md`

---

## 🧾 Contract partners (currently live on `partners.html`)

**Source of truth:** `tbl_partners` → `api/user/get-partners.php` → `public/partners.html`  
**Count:** 14 active partners (as of 2026-01-10)

| # | Partner Name | Partner Type | Partner Slug |
|---:|---|---|---|
| 1 | Golden Baboons | Community Partner | `golden-baboons` |
| 2 | Bear or Bull | Community Partner | `bear-or-bull` |
| 3 | Mad Skulz NFT | Community Partner | `Mad Skulz` |
| 4 | Gensuki | Community Partner | `gensuki` |
| 5 | Samuzi NFT | Community Partner | `Samuzi NFT` |
| 6 | Boundless Designs | Community Partner | `boundless8design` |
| 7 | Fox Goblin | Community Partner | `fox-goblin` |
| 8 | Artenova | NFT Project | `Artenova` |
| 9 | Jayk's Stake House | Gaming Community | `jayk-s-stake-house-2` |
| 10 | The Realm Kin's | Gaming Community | `realkins` |
| 11 | Web Builder161 Group - | Community Partner | `builder161` |
| 12 | Luxury Poker Club | Gaming Community | `luxurypoker` |
| 13 | KEKIUS MAXIMUS | Community Partner | `kekius maximus` |
| 14 | Reactor Motors | Gaming Community | `reactormotors` |

**Notes:**
- Some `partner_slug` values currently contain spaces/case. Treat them as **identifiers** and don’t rename without coordinating (renames can break any direct linking and internal references).

---

## ✅ Partner management checklist (what you do)

### When onboarding a partner (or refreshing an existing one)
- **Collect**:
  - Logo (square)
  - Banner (wide)
  - Short description (~50 words)
  - Long description (~200 words)
  - Links (Discord + Twitter/X + website)
  - Optional: gallery images (2–5), YouTube link, additional info
  - Permission confirmation for using brand assets + copy
- **Update** via Nerd Lab → Partners (or escalate if admin access needed)
- **Verify**:
  - `partners.html` shows correct logo/banner
  - All links work
  - Partner card copy is correct and professional

### Weekly partner hygiene
- Click every partner’s links and verify:
  - Discord invite still valid
  - Twitter handle correct
  - Website still correct
- Verify images load (no broken logos/banners)
- Track what’s missing in: `12.0/COLLABORATIONS_PARTNERS/PARTNER_ASSET_TRACKER.md`

---

## 🧯 Operational triage (how to react fast)

### If a player reports “my DSPOINC is wrong”
- Ask for Discord ID / username
- Check profile “Recent Score Changes” and/or Nerd Lab user details
- If manual correction is needed, document:
  - user, amount, reason, timestamp, who approved

### If a partner reports “our logo/link is wrong”
- Confirm which partner + what should be corrected
- Update partner entry (or collect missing assets)
- Verify fix on `partners.html` in the correct environment (local vs production)

### If a bug is reported
- Route to bug-report / bug-tracker flow
- Ensure reproduction steps are captured and prioritized

---

## 📚 Canonical references (don’t reinvent wheels)

- **Press + ecosystem overview (non-technical):** `12.0/YEAR_END_2025/PRESS_HANDOVER_2025_COMPLETE.md`
- **Frontend map:** `12.0/YEAR_END_2025/FRONTEND_WEBSITE_COMPLETE_TECHNICAL.md`
- **Admin/Nerd Lab:** `12.0/YEAR_END_2025/ADMIN_INTERFACE_COMPLETE_TECHNICAL.md`
- **Discord bot:** `12.0/YEAR_END_2025/DISCORD_BOT_COMPLETE_TECHNICAL.md`
- **Master doc index:** `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md`

