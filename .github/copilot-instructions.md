# Narrrfs World VS Code Rules (Source: `12.0/RULES`)

This repository uses the rules in `12.0/RULES` as the primary operating standard.

## Rule hierarchy (must follow)

1. `12.0/RULES/01_MASTER_RULESET.md` (single source of truth)
2. `12.0/RULES/08_CRITICAL_CODE_PRESERVATION_RULE.md` (never break working code)
3. `12.0/RULES/20_TECHNICAL_DOCUMENTATION_SYNC_RULE.md` (rules ↔ technical docs sync)
4. `12.0/RULES/00_RULES_INDEX.md` (index and scope of all rule files)

## Mandatory workflow for every task

1. Read `01_MASTER_RULESET.md` before coding.
2. Confirm target files and avoid unrelated modifications.
3. Preserve existing working behavior unless explicitly asked to change it.
4. For API/database/path work, validate local vs production rules before editing.
5. Update documentation/status files when making meaningful changes.

## Critical guardrails

- Do not delete/replace working code blocks without explicit instruction.
- Do not create duplicate APIs when existing endpoints can be extended.
- Respect local vs production path rules:
  - Local examples: `/public/...`, local DB in project `db/`
  - Production examples: `/var/www/html/...`, live DB `/var/www/html/db/narrrf_world.sqlite`
- Keep changes minimal, scoped, and reversible.

## Technical documentation check

Before implementation details, cross-check:

- `12.0/YEAR_END_2025/TECHNICAL_COMPLETE_2025_MASTER_INDEX.md`

If rules and implementation docs differ, flag and resolve before proceeding.