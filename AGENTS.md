# NARRRFS WORLD 13.0 — CODEX MASTER AGENT RULES

These instructions apply to the entire Narrrfs World repository unless a
future, explicitly approved, more-specific AGENTS.md states otherwise.

Every response begins:

✨ Following Global Rules ✨

---

# 1. PROJECT IDENTITY

Project:

Narrrfs World 13.0

Primary local repository:

C:\xampp-server\htdocs\narrrfs-world

Git Bash path:

/c/xampp-server/htdocs/narrrfs-world

Primary branch:

render-deploy

Local continuity source when the local agent workspace exists:

12.0/ACTIVE_STATUS/QUICK_STATUS.md

The directory 12.0/ is a local-only Narrrfs agent and continuity workspace.
It is intentionally excluded from GitHub and production deployment.

When available locally, inspect the newest relevant QUICK_STATUS entry and
verify it against current source/runtime evidence.

If the local 12.0 workspace is absent, do not treat that as an application or
runtime failure and do not invent continuity state. Continue from AGENTS.md
and verified current source, and request continuity evidence when required.

QUICK_STATUS records continuity only when locally available.

Current source, database, API, runtime, and deployment evidence remain
authoritative for the layer being investigated.

---

# 2. VERIFY SOURCE

Never assume files, functions, APIs, database fields, assets, runtime state,
configuration, deployment behavior, Discord state, or production state exist.

Verify in this order where applicable:

1. current user-provided evidence;
2. current repository source;
3. current terminal/runtime evidence;
4. newest QUICK_STATUS / LLM sync status;
5. verified API references / guides;
6. verified nearby implementation patterns.

Before editing, inspect:

- definitions;
- callers;
- routes;
- persistence;
- error paths;
- runtime boundaries;
- nearby verified patterns.

If evidence is missing:

STOP.

Request or inspect the exact source.

Never invent an implementation from memory or assumption.

---

# 3. SCOPE

Implement only the approved task.

Do not perform unrelated:

- fixes;
- refactors;
- cleanup;
- migrations;
- formatting;
- architecture changes;
- dependency changes;
- deployment changes;
- game changes.

Preserve existing behavior unless the approved scope explicitly changes it.

Report unrelated bugs separately.

---

# 4. REQUIRED WORKFLOW

Use this sequence:

Inspect
→ confirm authoritative source
→ explain the smallest change
→ backup when appropriate
→ apply the smallest guarded patch
→ syntax validation
→ focused behavior test
→ grep / focused diff inspection
→ runtime test when approved
→ restart/deploy only after explicit approval

Never restart or deploy before local checks pass.

After changes, report separately:

Changed
Unchanged
Passed
Unverified
Exact next step

---

# 5. CODE QUALITY

Use maintainable code with:

- descriptive names;
- focused functions;
- constants for important rules;
- verified existing helpers;
- explicit errors;
- narrow responsibilities.

Never:

- invent undocumented properties;
- duplicate existing helpers;
- silently swallow important failures;
- replace verified architecture with speculative abstractions.

New functions require a plain-language description.

Comments should explain:

- purpose;
- architecture;
- safety boundaries;
- game rules;
- long-term maintenance intent.

Write important comments for DEVS FOR DECADES.

Do not delete existing comments unless explicitly requested.

---

# 6. PROTECTED SYSTEMS

The following systems are protected.

No changes without explicit approval:

- Fight Recovery;
- DSPOINC;
- SPOINC;
- PVP escrow;
- PVP settlement;
- event burns;
- event refunds;
- champion rewards;
- token payout;
- airdrop;
- Genesis ownership;
- permanent Traits;
- permanent Abilities;
- Lab progression;
- Genetic Items;
- staking;
- Genesis Mouse Freezer;
- authentication;
- DB migrations;
- deployment configuration;
- unrelated games.

Temporary calculations must never mutate permanent:

- Genesis state;
- Lab state;
- ownership;
- staking;
- economy state.

---

# 7. DATABASE / ECONOMY SAFETY

Treat all writes involving balances, escrow, burns, refunds, rewards,
settlement, staking, inventory, recovery, ownership, or payouts as sensitive.

Before any DB write:

1. identify environment;
2. identify application path;
3. identify exact database path;
4. identify bot/runtime/API target;
5. state whether operation is read-only or write;
6. inspect schema;
7. inspect target rows;
8. inspect related protected rows;
9. use authoritative transaction API when available;
10. create or verify a backup when required;
11. use a narrowly guarded query;
12. obtain explicit approval;
13. execute;
14. run SELECT changes() when using SQLite direct writes;
15. verify final state.

Read-only investigations must remain read-only.

Never run broad destructive SQL.

Never manually alter protected economy/recovery rows when a verified
authoritative transaction path exists.

---

# 8. LOCAL VS LIVE

Always distinguish these environments:

LOCAL WEBSITE FILES
C:\xampp-server\htdocs\narrrfs-world

LOCAL XAMPP DATABASE
db/narrrf_world.sqlite

LOCAL DISCORD BOT
discord/

LOCAL AIRDROP SERVICE
airdrop-service/

DOWNLOADED LIVE DATABASE
A snapshot only.
It is not the current live database.

PRODUCTION WEBSITE / API
Render
/var/www/html

PRODUCTION LIVE DATABASE
/var/www/html/db/narrrf_world.sqlite

PERSISTENT RENDER DATABASE COPY
/data/narrrf_world.sqlite

A local bot may call live APIs.

A website deployment does not prove the local Discord bot was restarted.

A downloaded production DB is only a snapshot.

Never assume local and live match.

Before runtime or DB work, state:

Environment
Application path
Database path
Bot runtime
API target
Read-only or write

---

# 9. INSPECTION RULES

Prefer focused inspection commands:

grep
rg
sed
git diff
git status
git log
schema inspection
targeted SQL SELECT

Use cat only for small files/scripts.

Keep terminal output focused and truncated where useful.

Never infer code hidden outside the inspected range.

After changes inspect a focused Git diff for:

- unrelated edits;
- deleted comments;
- duplicate helpers;
- unexpected SQL changes;
- unexpected API changes;
- wrong paths;
- protected-system changes;
- formatting explosions.

---

# 10. PATCHING WORKFLOW

For large files or several edits, prefer a guarded Python patch script.

Recommended workflow:

inspect anchors
→ backup
→ create patch script
→ exact replacements
→ reject missing anchors
→ reject duplicate anchors
→ refuse unsafe reapply
→ run patch
→ grep markers
→ syntax
→ focused diff

Patch scripts must verify:

- target path;
- expected match count;
- backup;
- encoding;
- preserved comments;
- changed systems;
- unchanged protected systems.

Do not use blind replacements for:

- routes;
- SQL;
- statuses;
- custom Discord IDs;
- economy actions;
- recovery fields;
- settlement behavior.

---

# 11. VALIDATION

JavaScript / TypeScript:

node --check path/to/file.js

Also validate the nearest router / entry file where applicable.

PHP on this Windows XAMPP environment:

/c/xampp-server/php/php.exe -l path/to/file.php

HTML:

Inspect or extract changed inline scripts and validate JavaScript syntax.
Verify changed IDs and handlers.

Test changed behavior first.

Then test the nearest unchanged path.

Report validation separately:

Syntax passed
Focused inspection passed
Isolated test passed
Local runtime passed
Downloaded-live-DB inspection passed
Live DB passed
Production UI passed
Production Discord passed

Syntax alone never proves runtime or production success.

---

# 12. ERROR HANDLING

When something fails, explain:

- cause;
- system layer;
- changed state;
- unchanged state;
- DB impact;
- economy impact;
- runtime impact;
- retry safety;
- exact next diagnostic step.

Preserve exact:

- errors;
- IDs;
- actions;
- HTTP status;
- signatures;
- audit context.

Do not hide important failures behind broad fallbacks.

---

# 13. QUICK STATUS / HANDOVER

QUICK_STATUS is the cross-agent continuity source.

Canonical path:

12.0/ACTIVE_STATUS/QUICK_STATUS.md

Read the newest relevant entry before repeating old investigation.

After major approved work, prepare a QUICK_STATUS block containing:

Agent / version
Scope
Files / functions
DB inspected / changed
Backups
Tests
Live status
Known issues
Protected systems unchanged
Exact next step
Standby

Include exact when relevant:

IDs
tables
paths
functions
backup names
branch
API endpoint
hashes
runtime state

Never write vague status such as:

"fixed"
"working"
"live"

without matching evidence.

---

# 14. MOUSEFIGHT LAYERS

Keep these layers separate:

Discord presentation
activeMouseFights runtime
fight persistence
participants
PVP escrow
event burns / refunds
settlement
Fight Recovery
Genesis / Lab snapshots
Genetic Items
token payout
season archive

One layer does not prove another.

Examples:

removed Discord message != DB cancelled

cancelled fight != refund complete

runtime miss != DB row absent

participant alive != active reservation

syntax passed != economy/recovery correct

Always identify the affected layer first.

---

# 15. MOUSEFIGHT INVESTIGATION

Valid fight statuses include:

waiting
active
finished
cancelled

Relevant tables may include:

tbl_mousefights
tbl_mousefight_participants
tbl_mousefight_rounds
tbl_mousefight_dspoinc_stakes
tbl_mousefight_dspoinc_pvp_settlements
tbl_mousefight_dspoinc_burns
tbl_mousefight_mouse_cooldowns

Reservation investigation requires verifying:

token ID
collection
user ID
participant row
parent fight status

Only waiting/active parent fights block active reservation.

Do not delete historical participant rows merely because participant status
remains alive.

PVP cancellation and Event cancellation use different economy paths.

Before stale cleanup inspect where relevant:

wager / buy-in
stakes
settlements
burns
refunds
rounds
winner
cooldowns
message IDs
runtime presence

Never use one generic cleanup query for unrelated MouseFight states.

---

# 16. MOUSEFIGHT CONTROLS / RECOVERY

Before changing Discord controls verify:

builder
custom ID
router
parser
full fight ID
permissions
duplicate-click protection
message cleanup

Ownership rules:

Decline Challenge
= challenged player

Cancel Challenge
= creator or moderator

Cancel Event
= creator or moderator

Economy-sensitive actions require a fight-ID lock.

Acquire lock before async protected work.

Reject duplicate execution.

Release lock after success or failure.

Restore controls when practical after failure.

Runtime locks never replace:

API idempotency
database transactions
authoritative backend settlement

Fight Recovery is protected.

Waiting, declined, or cancelled-before-combat fights must not create
completed-match recovery unless verified rules explicitly require it.

Never alter cooldowns without explicit approval.

---

# 17. PUBLIC MOUSEFIGHT MESSAGES

Never announce a confirmed fight before authoritative backend creation succeeds.

When a Discord message must exist first, use the safe sequence:

neutral preparing message
→ disabled controls
→ authoritative API transaction
→ success: final lobby
→ failure: close/remove preparation message

On backend failure:

do not register runtime state;
do not imply escrow succeeded;
do not imply combat started;
return the exact backend error.

---

# 18. CHEESE ENGINE BOUNDARIES

Keep these concepts separate:

permanent state
temporary state
presentation
persistence
economy
recovery
season
archive

Temporary fight/session values must use isolated or cloned temporary data.

Document permanent/temporary boundaries.

Temporary values must never mutate permanent:

Genesis
Lab
ownership
staking
economy

unless an explicitly approved authoritative operation requires it.

---

# 19. EVIDENCE STANDARD

Use the narrowest accurate status.

Examples:

syntax verified
local runtime verified
downloaded-live snapshot inspected
live API verified
live DB verified
production Discord verified
refund verified
Recovery verified

Never claim:

live
deployed
fixed
working
settled
refunded
recovered

without evidence from that exact layer.

When uncertain:

STOP
→ inspect source / DB / runtime
→ protect economy / recovery
→ request missing evidence
→ never imagine implementation

---

# 20. AGENT OWNERSHIP MODEL

Narrrfs World uses specialist agent roles.

A specialist role is an ownership / expertise contract.

It does not mean a separate operating-system process is always running.

Before substantial work:

1. identify the affected system;
2. identify the appropriate Narrrfs specialist;
3. read the newest QUICK_STATUS entries for that system;
4. stay inside that specialist's approved boundaries.

The detailed specialist registry belongs in:

12.0/ACTIVE_STATUS/AGENT_REGISTRY.md

Do not duplicate the full changing agent registry inside AGENTS.md.

---

# 21. REPOSITORY STRUCTURE

public/

Website pages, browser JavaScript, public UI, and assets such as:

img/
sounds/
textures/

api/

PHP endpoints grouped by responsibility, including:

admin/
user/
discord/
partner/

discord/

Local Node.js Discord bot.

Command handlers are primarily in:

discord/commands/

three.js/

Separate Vite / Three.js game project with its own dependencies and assets.

db/

SQLite databases and local database state.

scripts/
tools/

Maintenance and focused developer utilities.

12.0/

LOCAL-ONLY Narrrfs agent and continuity workspace.

It is intentionally excluded from GitHub and production deployment.

When present locally it may contain:

- QUICK_STATUS continuity;
- specialist routing;
- system/environment maps;
- agent handovers;
- investigations;
- historical Cursor/LLM working material;
- technical notes and archives.

Its absence from GitHub, Render, or another clone is expected and is not an
application/runtime failure.

Never add 12.0 back to Git unless explicitly approved.

Standalone regression scripts may include root-level:

test-*.php
test-*.js
test-*.py

Never assume a path exists solely because it is documented here.
Verify it before using it.

---

# 22. BUILD / DEVELOPMENT COMMANDS

Run from repository root unless verified otherwise.

Root dependencies:

npm install

Discord bot dependencies:

npm --prefix discord install

Discord bot start:

npm --prefix discord start

Do not start/restart the live-connected local bot without explicit approval.

Three.js:

npm --prefix three.js install
npm --prefix three.js run dev
npm --prefix three.js run build
npm --prefix three.js run preview

PHP syntax:

/c/xampp-server/php/php.exe -l path/to/file.php

JavaScript syntax:

node --check path/to/file.js

Formatting when appropriate:

npx prettier --check path/to/file.js

Do not run broad formatting across unrelated files.

---

# 23. TESTING

There is no assumption of one universal root test runner.

Use the nearest verified feature-specific tests.

Inspect any test script before execution.

Mutation tests should use disposable data / disposable databases where possible.

Do not point mutation tests at live economy, recovery, staking, inventory, or
ownership data unless the action is explicitly approved.

Browser/UI work requires focused browser validation when possible.

Runtime proof must be reported separately from syntax proof.

---

# 24. GIT / COMMITS

Primary working branch is expected to be:

render-deploy

Verify the current branch before work.

Do not assume the repository is clean.

Before editing:

git status

After editing:

git diff -- <affected files>

Use focused commits.

Do not include unrelated files.

Do not push without explicit approval.

Do not deploy without explicit approval.

Do not restart runtime services without explicit approval.

---

# 25. SECURITY / CONFIGURATION

Keep credentials out of commits.

Never expose or commit:

tokens
private keys
secrets
real environment credentials
sensitive database snapshots
temporary audit output

Use env.example only as a configuration reference where verified.

Do not print secrets into terminal output, documentation, patches, or chat.

---

# 26. FINAL RESPONSE STANDARD

Every response begins:

✨ Following Global Rules ✨

For technical work, report:

Environment
Scope
Source authority
Changed
Unchanged
Validation
Live status
Protected systems
Known issues
Exact next step

Never overstate evidence.

Optimize for:

correctness
verification
small patches
auditability
continuity
protected economy
protected Recovery
long-term maintainability

---

# SIGN-IN CONTRACT

When first entering this repository, before making changes, report:

Name / role / version
Project
Current branch
Working directory
Local continuity QUICK_STATUS path when available
Relevant specialist
Affected system layer
Protected systems involved
Current evidence level

Then state:

I accept these rules.
Accepted. Synchronized. Standby: Active.

---

# 27. CODEX NARRRFS STARTUP PROTOCOL

At the beginning of every new Narrrfs World technical thread:

1. Read the repository-root AGENTS.md.
2. Check whether the local 12.0/ agent workspace exists.
3. If 12.0/ exists locally:
   - read 12.0/ACTIVE_STATUS/AGENT_REGISTRY.md when present;
   - read 12.0/ACTIVE_STATUS/SYSTEM_MAP.md when present;
   - inspect the newest relevant sections of
     12.0/ACTIVE_STATUS/QUICK_STATUS.md when present.
4. If 12.0/ or one of those continuity files is absent:
   - do not treat the absence as an application/runtime failure;
   - do not invent the missing continuity state;
   - continue from AGENTS.md and verified current source;
   - request missing continuity evidence when the task requires it.
5. Run:
   git branch --show-current
   git status --short
6. Identify:
   - affected system;
   - affected layer;
   - primary specialist;
   - supporting specialists where required;
   - protected systems;
   - environment;
   - application path;
   - database path;
   - bot runtime;
   - API target;
   - read-only or write;
   - current source authority.
7. Inspect current source before proposing implementation.
8. Do not modify files merely as part of synchronization.
9. Do not restart, deploy, push, or perform DB writes merely as part of synchronization.
10. Never add the local 12.0/ workspace back to Git unless explicitly approved.
11. If required evidence is missing, stop and inspect instead of assuming.

QUICK_STATUS is large and historical.

Do not blindly load the complete QUICK_STATUS for every task.

Prefer:

- the newest entries at the top;
- targeted searches for the affected specialist/system;
- exact dated sections;
- exact IDs, functions, files, or incidents relevant to the task.

Older QUICK_STATUS entries provide history, not automatic current truth.

A new Narrrfs technical thread should begin by reporting:

Name / role / version:
Project:
Branch:
Working directory:
Primary specialist:
Affected layer:
Protected systems:
Environment:
Source authority:
Evidence level:

Then state:

I accept these rules.
Accepted. Synchronized. Standby: Active.
