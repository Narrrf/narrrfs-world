# 🧀 Narrrfs World — Community Manager Pitch (Mobile-Friendly)

**Created:** 2026-01-10  
**Audience:** Community Manager  
**Goal:** Make it easy to read on phone + easy to forward.

---

## 📌 Quick table of contents

- [What Narrrfs World is](#-what-narrrfs-world-is)
- [The 7 games](#-the-7-games)
- [DSPOINC economy](#-dspoinc-economy)
- [Discord + community ops](#-discord--community-ops)
- [Nerd Lab (admin)](#-nerd-lab-admin)
- [NFTs + holder layer](#-nfts--holder-layer)
- [Partners + Partner Portal](#-partners--partner-portal)
- [What you run (daily/weekly/season)](#-what-you-run-dailyweeklyseason)
- [Ready-to-use messaging](#-ready-to-use-messaging)
- [Where to send people](#-where-to-send-people)

---

## 🎯 What Narrrfs World is

Narrrfs World is a **Discord-linked web gaming ecosystem**.
It is not “one game”.

Players:
- log in with **Discord**
- play multiple games
- earn **DSPOINC** (our universal in-game currency)
- progress across the ecosystem with one identity

Core systems:
- **7 integrated games**
- **DSPOINC economy** (earn/spend; staking exists)
- **Discord bot** (events + ops + economy touchpoints)
- **Nerd Lab admin tools** (support + operations)
- **Partner Portal** (public partner page, DB-driven)
- **NFT/holder verification layer** (optional benefits layer)

One idea to remember:
**One identity → many games → one currency → one community.**

---

## 🎮 The 7 games

Web games (site):
- **Tetris** (`public/tetris.html`)
- **Snake** (`public/snake.html`)
- **Space Invaders** (`public/space-cheese-invaders.html`)
- **Cheese Hunt** (click/quest-based mechanics across the site)

Discord-native event games:
- **Discord Race**
- **Cheese Rumble**

3D web game (live):
- **3D Riddle Game — The Cheese Temple** (`public/3d-riddle.html`)
- Built on **Three.js** (`public/three.js/`)
- Direction: exploration + riddles + chests + bosses/events over time

Player home base (everything connects here):
- **Profile hub:** `public/profile.html`

---

## 💰 DSPOINC economy

DSPOINC is the ecosystem’s universal reward currency.

Where players see it:
- on `public/profile.html`
- through Discord bot workflows/commands
- as rewards from games + events
- as rewards from 3D systems (riddles/chests)

Why it matters:
- makes “playing different games” feel connected
- powers store/inventory systems
- enables community incentives + events

Staking exists:
- `public/stake-lab.html` (community-facing)

---

## 🤖 Discord + community ops

Discord is infrastructure for:
- identity
- roles/tiers
- events
- support triage

Roles are used for:
- community tiers (VIP/Holder/etc.)
- access gates (where configured)
- benefits (where configured)

Discord bot = operational runtime for:
- event games (Race/Rumble)
- player-facing info workflows (balance/stats style)
- ops systems (giveaways/bug workflows, depending on configuration)

Reference inventory:
- `12.0/YEAR_END_2025/DISCORD_BOT_COMPLETE_TECHNICAL.md`

---

## 🧠 Nerd Lab (admin)

Nerd Lab is the staff control center for:
- user support (balances/roles/inventory)
- DSPOINC adjustments + audit trail mindset
- store + quests
- season controls + leaderboards
- dashboards + bug tracker views
- partner management (Partner Portal entries)

Admin UI:
- `public/admin-interface.html`

Canonical reference:
- `12.0/YEAR_END_2025/ADMIN_INTERFACE_COMPLETE_TECHNICAL.md`

Operating safety:
- if it looks like “mass reset / mass edit” → escalate before acting
- document manual DSPOINC changes (who/what/why)

---

## 🎴 NFTs + holder layer

We have an **NFT holder verification** layer.

How to frame it safely:
- core ecosystem is **free to play**
- NFTs are an **optional** benefits layer
- focus on utility, recognition, and community value

Where it connects:
- Discord roles (tier/recognition)
- perks/bonuses where configured
- partner activations (optional perks)

---

## 🤝 Partners + Partner Portal

Partner Portal purpose:
- public showcase of collaborations
- keep partner links + assets accurate
- support ongoing relationship ops + co-marketing

Source of truth:
- public page: `public/partners.html`
- API: `api/user/get-partners.php` (from `tbl_partners`)
- admin management: `api/admin/partner-management.php` (via Nerd Lab)

Current state:
- **14 active partners** live on `partners.html` (as of 2026-01-10)
- exact list snapshot lives in:
  `12.0/YEAR_END_2025/COMMUNITY_MANAGER_ONBOARDING_2026.md`

Weekly hygiene:
- click-test every partner link
- verify logos/banners load
- track missing assets:
  `12.0/COLLABORATIONS_PARTNERS/PARTNER_ASSET_TRACKER.md`

---

## 🧯 What you run (daily/weekly/season)

Daily:
- keep Discord safe (scams, impersonation, tone)
- route DSPOINC/account issues to staff workflow
- push bug reports to include steps + screenshots + device/browser

Weekly:
- run/coordinate events (Race/Rumble/giveaways as configured)
- partner hygiene (links/assets)
- collect community feedback (fun, broken, confusing)

Per season / monthly:
- season messaging (rules, fairness, timelines)
- highlight recap (winners, moments, growth)
- moderation review (what rules/tools need improvement)

---

## 🧩 Ready-to-use messaging

One sentence (public):
Narrrfs World is a Discord-linked web gaming ecosystem with 7 games,
one DSPOINC economy, community-first events, a live 3D riddle world,
and an active partner network.

Two sentences (partners):
We run a multi-game ecosystem where players earn DSPOINC and engage
through Discord roles, events, and progression.
Partners get a public portal listing plus optional co-events/perks.

Three bullets (mods):
- keep the space safe + scam-free
- route bugs/support/partners to the right place
- protect the vibe while keeping events fun and fair

---

## 🌐 Where to send people

- Home: `public/index.html`
- Player hub: `public/profile.html`
- Partners: `public/partners.html`
- Get Roles: `public/get-roles.html`
- Stake: `public/stake-lab.html`
- Bug report: `public/bug-report.html`
- Updates: `public/project-updates.html`

---

## 🔗 Deep references (if needed)

- Onboarding (system + partners):
  `12.0/YEAR_END_2025/COMMUNITY_MANAGER_ONBOARDING_2026.md`
- Press handover:
  `12.0/YEAR_END_2025/PRESS_HANDOVER_2025_COMPLETE.md`
- Master index:
  `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md`

