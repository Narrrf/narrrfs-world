# Samuzi Template Cleanup + Theming Handoff

## What has already been cleaned

### 1) Main customer pages are now English-only
- `index.html`
- `about.html`
- `projects.html`
- `contact.html`
- `legal.html`

Removed old German mains:
- `ueber-uns.html`
- `referenzen.html`
- `kontakt.html`
- `impressum.html`

### 2) Runtime config keys switched to English page set
Used page keys now:
- `index`
- `projects`
- `about`
- `contact`
- `legal`

Updated in:
- `admin/settings.json`
- `api/get-bubble-settings.php`
- `admin/get-settings.php`
- `admin/save-settings.php` (background update map)

### 3) Email stack simplified to neutral starter
Kept minimal, reusable baseline:
- `api/send-email.php`
- `api/email-api-service.php`
- `api/smtp-helper.php`

No customer-specific SMTP logic remains in core flow.

### 4) Admin/runtime references partially cleaned
- `admin/projects.html` switched to English page link (`../projects.html`)
- `admin/projects.html` core actions/messages are now English (load/save/delete flows)
- `robots.txt` updated to domain placeholder sitemap

### 5) Sensitive/legacy docs + logs removed
Deleted old customer docs and logs containing legacy branding/email traces:
- `DEPLOYMENT_CHECKLIST.md`
- `EMAIL_CONFIGURATION_GUIDE.md`
- `EMAIL_FIX_SUMMARY.md`
- `FORMS_VERIFICATION_REPORT.md`
- `GIT_COMMIT_MESSAGE.md`
- `GIT_PUSH_COMMANDS.md`
- `KUNDEN_ZUSAMMENFASSUNG.md`
- `NO_SMTP_SOLUTION.md`
- `RESEND_SETUP_COMPLETE.md`
- `api/admin_log.txt`
- `api/email_log.txt`
- `api/submitted_emails.txt`

---

## What the theming agent should do next

## Priority A — Visual Theming (main task)
Apply Samuzi brand visuals consistently across:
- `index.html`, `about.html`, `projects.html`, `contact.html`, `legal.html`
- `assets/shared-styles.css`

Focus:
- color system
- typography
- spacing scale
- button/inputs/cards style
- header/footer consistency
- mobile polish

## Priority B — Admin UI polish (optional but recommended)
`admin/projects.html` still contains some German form labels/options (field names and feature labels).
Translate remaining labels/options to English and align with Samuzi tone.

## Priority C — Content placeholders
Replace “coming soon” blocks with structured placeholder sections:
- Hero
- Project showcase cards
- About mission/roadmap
- Contact CTA + social links
- Legal skeleton

## Priority D — Final pre-deploy checks
1. Ensure no dead links in nav
2. Confirm all assets load from correct relative paths
3. Set real sitemap domain in `robots.txt`
4. Fill real email config in `admin/settings.json` or environment variables

---

## Important constraints for theming work
- Keep API endpoints and admin project/photo workflow intact.
- Do not remove:
  - `admin/projects.php`
  - `admin/upload-photos.php`
  - `api/get-projects.php`
  - `api/get-slider-photos.php`
- Preserve file names and route structure unless explicitly requested.

---

## Quick status
Template is now **clean enough for production theming pass**.
Next agent can focus primarily on design/theming and content structuring.

## Deployment-ready checklist (for next agent)
- [ ] Replace `https://example.com/sitemap.xml` in `robots.txt` with final domain
- [ ] Set real email recipient/sender settings in `admin/settings.json` or env
- [ ] Verify project CRUD still works after theme changes
- [ ] Verify slider/photos load correctly on `index.html` and `projects.html`
