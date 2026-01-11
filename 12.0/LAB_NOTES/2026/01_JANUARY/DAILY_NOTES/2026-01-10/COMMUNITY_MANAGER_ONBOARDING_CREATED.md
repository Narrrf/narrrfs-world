# ✅ Community Manager Onboarding Doc Created (System + Partners)

**Date:** 2026-01-10  
**Purpose:** Create a single “complete overview” onboarding document for the new Community Manager, including Partner Portal ownership and the current 14 contract partners shown on `partners.html`.

---

## 📌 Primary Onboarding Document (Year End 2025 folder)

**File:** `12.0/YEAR_END_2025/COMMUNITY_MANAGER_ONBOARDING_2026.md`

**Includes:**
- Ecosystem overview (7 games + DSPOINC + Discord + Nerd Lab + website)
- Operational guidance (support triage, bug flow)
- Partner Portal architecture + where data lives (`tbl_partners` → `api/user/get-partners.php` → `public/partners.html`)
- **Contract partners list (14 active partners)** pulled from local `get-partners.php` API
- Links to canonical Year End docs for deeper reference

---

## 🤝 Partner Portal References

- Public page: `public/partners.html`
- Public API: `api/user/get-partners.php`
- Admin API: `api/admin/partner-management.php`
- Tracker: `12.0/COLLABORATIONS_PARTNERS/PARTNER_ASSET_TRACKER.md`
- Outreach templates: `12.0/TECHNICAL_DOCUMENTATION/PARTNER_INVITATION_TEMPLATE.md`

---

## ✅ Notes

- Partners are **DB-driven** (not hardcoded in `partners.html`).
- Image paths are environment-aware:
  - Local uses `/public/img/partners/...`
  - Production uses `/img/partners/...` (NO `/public/`)

