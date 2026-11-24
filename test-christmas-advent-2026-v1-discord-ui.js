'use strict';

/**
 * Disposable-only Discord #lootwinners outbox & Profile UI regression test (Cases 25, 26, 27, 28).
 * Never sends real Discord messages; uses a mocked Discord channel & disposable PHP server.
 */

const assert = require('assert');
const { execFileSync, spawn } = require('child_process');
const fs = require('fs');
const net = require('net');
const os = require('os');
const path = require('path');

const ROOT = __dirname;
const PHP = 'C:\\xampp-server\\php\\php.exe';
const NODE = process.execPath;
const BOT_DIR = 'C:\\xampp-server\\htdocs\\narrrfs-world\\discord';
const WORKER_MOD = path.join(BOT_DIR, 'utils', 'christmas-advent-lootwinners.js');
const BOT_INDEX = path.join(BOT_DIR, 'index.js');

const {
  CHRISTMAS_LOOTWINNERS_CHANNEL_ID,
  sanitizeBonusItemName,
  buildChristmasAdventWinnerEmbed,
  processChristmasAdventOutboxOnce
} = require(WORKER_MOD);

function check(cond, msg) {
  assert(cond, 'FAIL: ' + msg);
  console.log('PASS:', msg);
}

function getFreePort() {
  return new Promise((resolve, reject) => {
    const srv = net.createServer();
    srv.listen(0, '127.0.0.1', () => {
      const p = srv.address().port;
      srv.close(() => resolve(p));
    });
    srv.on('error', reject);
  });
}

(async () => {
  // 1. JS syntax checks + Reward Chamber integrity check (Case 28).
  execFileSync(NODE, ['--check', path.join(ROOT, 'public', 'js', 'christmas-advent-2026.js')], { stdio: 'inherit' });
  check(true, 'Node syntax check passed for public/js/christmas-advent-2026.js');
  execFileSync(NODE, ['--check', WORKER_MOD], { stdio: 'inherit' });
  check(true, 'Node syntax check passed for discord/utils/christmas-advent-lootwinners.js');
  execFileSync(NODE, ['--check', BOT_INDEX], { stdio: 'inherit' });
  check(true, 'Node syntax check passed for discord/index.js');

  const botSrc = fs.readFileSync(BOT_INDEX, 'utf8');
  check(
    botSrc.includes('function buildRewardBoxPublicEmbed(') &&
    botSrc.includes('async function sendRewardBoxOpenPublicLog(') &&
    botSrc.includes("const REWARD_BOX_PUBLIC_CHANNEL_ID = '1498450478327926836'"),
    'Case 28: Existing Reward Chamber #lootwinners integration remains intact'
  );
  check(CHRISTMAS_LOOTWINNERS_CHANNEL_ID === '1498450478327926836', 'Exact #lootwinners channel ID verified');

  // Embed sanitization test.
  const evilName = 'Blue Elixir @everyone `<@&12345>` **hack**';
  const cleanName = sanitizeBonusItemName(evilName);
  check(!cleanName.includes('@everyone') && !cleanName.includes('<@&') && cleanName.includes('Blue Elixir'), 'Sanitizes @everyone and role mentions');

  const embedBoth = buildChristmasAdventWinnerEmbed({
    claim_id: 42,
    user_id: '328601656659017732',
    day_number: 1,
    awarded_dspoinc: 7426,
    bonus_awarded: true,
    bonus_item_name: 'Blue Elixir',
    bonus_quantity: 1,
    claimed_at: '2026-12-01 12:00:00'
  }).toJSON();
  check(
    embedBoth.title === '🎄 CHRISTMAS ADVENT WINNER! 🎄' &&
    embedBoth.description.includes('<@328601656659017732>') &&
    embedBoth.description.includes('December 1') &&
    embedBoth.description.includes('7,426') &&
    embedBoth.description.includes('🎁 Bonus Prize: 1x Blue Elixir') &&
    embedBoth.footer.text === 'Narrrfs World Christmas Advent 2026 · Claim #42',
    'Case 25a: Embed format matches owner spec when DSPOINC + Store Item delivered'
  );

  const embedOnly = buildChristmasAdventWinnerEmbed({
    claim_id: 43,
    user_id: '328601656659017732',
    day_number: 1,
    awarded_dspoinc: 3180,
    bonus_awarded: false,
    bonus_item_name: null,
    bonus_quantity: 0,
    claimed_at: '2026-12-01 12:05:00'
  }).toJSON();
  check(
    embedOnly.description.includes('3,180') &&
    embedOnly.description.includes('🎁 Bonus Prize: No bonus item this time') &&
    embedOnly.description.includes('Come back tomorrow for another door!'),
    'Case 25b: Embed format matches owner spec when DSPOINC-only'
  );

  // 2. End-to-end disposable outbox HTTP + mocked Discord delivery/duplicate/outage test.
  const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'xmas26-discord-'));
  let srvProc = null;
  try {
    fs.mkdirSync(path.join(tmpDir, 'api', 'config'), { recursive: true });
    fs.mkdirSync(path.join(tmpDir, 'api', 'lib'), { recursive: true });
    fs.mkdirSync(path.join(tmpDir, 'api', 'discord'), { recursive: true });
    fs.mkdirSync(path.join(tmpDir, 'db'), { recursive: true });

    fs.cpSync(path.join(ROOT, 'api', 'config'), path.join(tmpDir, 'api', 'config'), { recursive: true });
    for (const f of fs.readdirSync(path.join(ROOT, 'api', 'lib')).filter((x) => x.startsWith('christmas-advent-'))) {
      fs.copyFileSync(path.join(ROOT, 'api', 'lib', f), path.join(tmpDir, 'api', 'lib', f));
    }
    fs.copyFileSync(
      path.join(ROOT, 'api', 'discord', 'christmas-advent-2026-outbox-v1.php'),
      path.join(tmpDir, 'api', 'discord', 'christmas-advent-2026-outbox-v1.php')
    );

    const seedPhp = path.join(tmpDir, 'seed.php');
    fs.writeFileSync(seedPhp, `<?php
require_once __DIR__ . '/api/lib/christmas-advent-2026-claim.php';
$db = new SQLite3(__DIR__ . '/db/narrrf_world.sqlite', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
$db->enableExceptions(true);
$db->exec('PRAGMA foreign_keys = ON');
$db->exec('CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT NOT NULL)');
$db->exec("CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, game TEXT, score INTEGER, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, source TEXT)");
$db->exec("CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, admin_id TEXT NOT NULL, amount INTEGER NOT NULL, action TEXT NOT NULL, reason TEXT NOT NULL)");
$db->exec("CREATE TABLE tbl_store_items (item_id INTEGER PRIMARY KEY, item_name TEXT NOT NULL, description TEXT DEFAULT '', price INTEGER DEFAULT 1000, image_url TEXT DEFAULT '', is_active INTEGER DEFAULT 1, quantity INTEGER DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE tbl_user_inventory (inventory_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, item_id INTEGER NOT NULL, quantity INTEGER DEFAULT 1, acquired_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, item_id))");
$db->exec("CREATE TABLE tbl_discord_claim_notifications (notification_id INTEGER PRIMARY KEY AUTOINCREMENT, claim_id INTEGER NOT NULL, notification_type TEXT NOT NULL, discord_channel_id TEXT, discord_message_id TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(claim_id, notification_type))");
$db->exec(file_get_contents(${JSON.stringify(path.join(ROOT, 'db', 'migrations', 'create_christmas_advent_2026_claims_v1.sql'))}));
$db->exec(file_get_contents(${JSON.stringify(path.join(ROOT, 'db', 'migrations', 'create_christmas_advent_2026_draft_revisions_v1.sql'))}));
$db->exec(file_get_contents(${JSON.stringify(path.join(ROOT, 'db', 'migrations', '20261124_christmas_advent_2026_v1.sql'))}));
$db->exec("INSERT INTO tbl_users VALUES ('100000000000000001', 'alice'), ('100000000000000002', 'bob')");
$db->exec("INSERT INTO tbl_store_items VALUES (34, 'Blue Elixir', '-18h', 250000, '', 1, 10, CURRENT_TIMESTAMP)");
$days = [];
for ($d = 1; $d <= 24; $d++) {
  $days[] = ['day_number'=>$d,'dspoinc_min'=>$d===24?50000:1000,'dspoinc_max'=>$d===24?250000:10000,'max_daily_claims'=>100,'bonus'=>['enabled'=>$d===1,'item_ids'=>$d===1?[34]:[],'chance_basis_points'=>$d===1?5000:0,'max_winners'=>$d===1?10:0,'quantity_per_win'=>1]];
}
$draft = ['schema_version'=>CHRISTMAS26_DRAFT_SCHEMA_V2,'event_key'=>CHRISTMAS26_EVENT_KEY,'max_bonus_wins_per_player'=>3,'first_bonus_guarantee'=>['enabled'=>false],'days'=>$days];
$s = christmas26_draft_save_v1($db, $draft, 0, '328601656659017732');
$c = christmas26_stage_policy_candidate($db, $s['revision'], 'christmas_advent_2026_v1', '328601656659017732');
christmas26_approve_policy($db, 'christmas_advent_2026_v1', $c['policy_fingerprint'], '328601656659017732');
christmas26_claim_execute_v1($db, '100000000000000001', new DateTimeImmutable('2026-12-01T12:00:00+01:00'), 'christmas_advent_2026_v1', null, static fn(int $a, int $b): int => ($a===1 && $b===10000) ? 100 : 7426);
christmas26_claim_execute_v1($db, '100000000000000002', new DateTimeImmutable('2026-12-01T12:01:00+01:00'), 'christmas_advent_2026_v1', null, static fn(int $a, int $b): int => ($a===1 && $b===10000) ? 9000 : 3180);
echo 'SEED_OK';
`);
    const seedOut = execFileSync(PHP, [seedPhp], { encoding: 'utf8' });
    check(seedOut.trim() === 'SEED_OK', 'Seeded disposable DB with committed claims + pending outbox');

    const port = await getFreePort();
    const secret = 'test_secret_christmas_advent_2026_lootwinners_123456';
    const env = { ...process.env, NARRRFS_CHRISTMAS_ADVENT_2026_OUTBOX_ENABLED: '1', NARRRFS_CHRISTMAS_ADVENT_2026_OUTBOX_SECRET: secret };
    srvProc = spawn(PHP, ['-d', 'display_errors=0', '-S', `127.0.0.1:${port}`, '-t', tmpDir], { env, stdio: 'ignore' });

    const outboxUrl = `http://127.0.0.1:${port}/api/discord/christmas-advent-2026-outbox-v1.php`;
    for (let i = 0; i < 50; i++) {
      try {
        const probe = await fetch(outboxUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
        if (probe.status === 401) break;
      } catch {}
      await new Promise((r) => setTimeout(r, 100));
    }

    // Reject wrong bearer.
    const unauth = await fetch(outboxUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: 'Bearer wrong_secret_wrong_secret_wrong_secret' }, body: '{"action":"claim_batch"}' });
    check(unauth.status === 401, 'Outbox API rejects wrong Bearer secret (401)');

    // Mock Discord outage first (Case 27): channel.send throws 503 -> retried, financial claim remains settled!
    const postedMessages = [];
    let outage = true;
    let nextMsgId = 900000000000000001n;
    const mockChannel = {
      id: '1498450478327926836',
      messages: {
        async fetch() {
          return postedMessages;
        }
      },
      async send(payload) {
        if (outage) {
          const e = new Error('Discord upstream 503');
          e.status = 503;
          throw e;
        }
        const msgId = String(nextMsgId++);
        const msg = { id: msgId, embeds: payload.embeds, payload };
        postedMessages.push(msg);
        return msg;
      }
    };
    const mockClient = { channels: { async fetch(id) { return id === '1498450478327926836' ? mockChannel : null; } } };

    const resOutage = await processChristmasAdventOutboxOnce({ client: mockClient, outboxUrl, secret, logger: { log() {}, warn() {} } });
    check(resOutage.processed === 2 && resOutage.sent === 0 && resOutage.retried === 2 && postedMessages.length === 0, 'Case 27: Discord outage safely calls mark_retry without sending or failing');

    // Reset next_attempt_at via direct disposable SQLite helper to simulate backoff expiry after recovery.
    execFileSync(PHP, ['-r', `$d=new SQLite3(${JSON.stringify(path.join(tmpDir, 'db', 'narrrf_world.sqlite'))}); $d->exec("UPDATE tbl_christmas_advent_2026_notify_outbox SET next_attempt_at = NULL");`]);
    outage = false;

    const resOk = await processChristmasAdventOutboxOnce({ client: mockClient, outboxUrl, secret, logger: { log() {}, warn() {} } });
    check(resOk.processed === 2 && resOk.sent === 2 && postedMessages.length === 2, 'Case 25: Announces both committed claims once Discord recovers');
    check(
      postedMessages[0].payload.allowedMentions.users.length === 1 &&
      postedMessages[0].payload.allowedMentions.parse.length === 0,
      'Allowed mentions strictly scoped to single claim user only'
    );

    // Case 26a: Second poll suppresses duplicates (0 pending rows).
    const resDup = await processChristmasAdventOutboxOnce({ client: mockClient, outboxUrl, secret, logger: { log() {}, warn() {} } });
    check(resDup.processed === 0 && postedMessages.length === 2, 'Case 26a: Duplicate re-poll produces zero duplicate Discord messages');

    // Case 26b: Crash-between-2xx-and-mark_sent lease reclaim check -> scans recent footer & marks sent WITHOUT re-sending!
    execFileSync(PHP, ['-r', `
      $d = new SQLite3(${JSON.stringify(path.join(tmpDir, 'db', 'narrrf_world.sqlite'))});
      $d->exec("UPDATE tbl_christmas_advent_2026_notify_outbox SET status='processing', claim_token='deaddeaddeaddeaddeaddeaddeaddeaddeaddeaddeaddead', lease_expires_at='2020-01-01 00:00:00', sent_at=NULL, discord_message_id=NULL WHERE claim_id=1");
    `]);
    const resReclaim = await processChristmasAdventOutboxOnce({ client: mockClient, outboxUrl, secret, logger: { log() {}, warn() {} } });
    check(resReclaim.processed === 1 && resReclaim.sent === 1 && postedMessages.length === 2, 'Case 26b: Reclaimed lease finds existing footer in channel and marks sent without double-posting');

    console.log('ALL DISCORD & UI TESTS PASSED');
  } finally {
    if (srvProc) srvProc.kill();
    fs.rmSync(tmpDir, { recursive: true, force: true });
  }
})();
