# Isolated HTTP + multi-process concurrency + Apache containment suite for Christmas Advent V1.
import concurrent.futures
import contextlib
import hashlib
import http.cookiejar
import json
import os
from pathlib import Path
import re
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import threading
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parent
PHP = r'C:\xampp-server\php\php.exe'
OWNER = '328601656659017732'
PLAYER = '100000000000000001'
NONOWNER = '222222222222222222'
passes = 0


def check(cond, desc):
    global passes
    if not cond:
        raise AssertionError('FAIL: ' + desc)
    passes += 1
    print('PASS:', desc)


def new_client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def req(opener, port, path, method='GET', body=None, headers=None):
    r = urllib.request.Request(f'http://127.0.0.1:{port}{path}', data=body, method=method, headers=headers or {})
    try:
        with opener.open(r, timeout=8) as resp:
            return resp.status, resp.read()
    except urllib.error.HTTPError as e:
        return e.code, e.read()


# 1. Verify Apache containment allowlist & manifest SHA-256 first.
conf = (ROOT / 'deploy/apache/narrrfs-emergency-containment.conf').read_text(encoding='utf-8')
m = re.search(r'<LocationMatch "([^"]*api/owner-control[^"]*)">', conf)
check(m is not None, 'Apache Owner Control LocationMatch regex present')
loc_regex = re.compile(m.group(1))
for allowed_path in [
    '/api/owner-control/v1/christmas-draft.php',
    '/api/owner-control/v1/christmas-policy.php',
    '/api/owner-control/v1/christmas-policy-csrf.php',
    '/api/owner-control/v1/christmas-policy-stage.php',
    '/api/owner-control/v1/christmas-policy-approve.php',
]:
    check(loc_regex.search(allowed_path) is None, f'Apache containment allows {allowed_path}')
for denied_path in ['/api/owner-control/v1/unknown.php', '/api/owner-control/v1/christmas-policy.php/extra']:
    check(loc_regex.search(denied_path) is not None, f'Apache containment blocks {denied_path}')

# Check manifest SHA-256 against canonical LF file bytes.
mig_bytes = (ROOT / 'db/migrations/20261124_christmas_advent_2026_v1.sql').read_bytes().replace(b'\r\n', b'\n')
mig_sha = hashlib.sha256(mig_bytes).hexdigest()
manifest_lines = (ROOT / 'db/migrations/manifest-v1.txt').read_text(encoding='utf-8').splitlines()
check(
    f'christmas-advent-2026-reward-engine-v1-20261124|20261124_christmas_advent_2026_v1.sql|{mig_sha}' in manifest_lines,
    'Manifest registers exact LF SHA-256 of 20261124_christmas_advent_2026_v1.sql'
)

with tempfile.TemporaryDirectory(prefix='xmas26-http-') as tmp:
    base = Path(tmp)
    (base / 'api/owner-control/v1').mkdir(parents=True)
    (base / 'api/user').mkdir(parents=True)
    (base / 'api/discord').mkdir(parents=True)
    (base / 'api/lib').mkdir(parents=True)
    (base / 'db').mkdir()
    (base / 'sessions').mkdir()

    shutil.copytree(ROOT / 'api/config', base / 'api/config')
    for lib in [
        'christmas-advent-2026.php',
        'christmas-advent-2026-draft-preflight-v1.php',
        'christmas-advent-2026-draft-preflight-v2.php',
        'christmas-advent-2026-draft-storage-v1.php',
        'christmas-advent-2026-policy-v1.php',
        'christmas-advent-2026-claim.php',
        'christmas-advent-2026-outbox-v1.php',
    ]:
        shutil.copy2(ROOT / 'api/lib' / lib, base / 'api/lib' / lib)
    for ep in [
        'csrf.php', 'christmas-draft.php', 'christmas-policy.php',
        'christmas-policy-csrf.php', 'christmas-policy-stage.php', 'christmas-policy-approve.php',
    ]:
        shutil.copy2(ROOT / 'api/owner-control/v1' / ep, base / 'api/owner-control/v1' / ep)
    for ep in ['christmas-advent-2026.php', 'christmas-advent-2026-claim.php', 'christmas-advent-2026-claim-context.php']:
        shutil.copy2(ROOT / 'api/user' / ep, base / 'api/user' / ep)
    shutil.copy2(ROOT / 'api/discord/christmas-advent-2026-outbox-v1.php', base / 'api/discord/christmas-advent-2026-outbox-v1.php')

    dbpath = base / 'db/narrrf_world.sqlite'
    conn = sqlite3.connect(dbpath)
    conn.executescript("""
        CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT NOT NULL);
        CREATE TABLE tbl_user_scores (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT, game TEXT, score INTEGER, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, source TEXT);
        CREATE TABLE tbl_score_adjustments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, admin_id TEXT NOT NULL, amount INTEGER NOT NULL, action TEXT NOT NULL CHECK(action IN ('add','remove','set')), reason TEXT NOT NULL, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE tbl_store_items (item_id INTEGER PRIMARY KEY, item_name TEXT NOT NULL, description TEXT DEFAULT '', price INTEGER DEFAULT 1000, image_url TEXT DEFAULT '', is_active INTEGER DEFAULT 1, quantity INTEGER DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE tbl_user_inventory (inventory_id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, item_id INTEGER NOT NULL, quantity INTEGER DEFAULT 1, acquired_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, item_id));
        CREATE TABLE tbl_discord_claim_notifications (notification_id INTEGER PRIMARY KEY AUTOINCREMENT, claim_id INTEGER NOT NULL, notification_type TEXT NOT NULL, discord_channel_id TEXT, discord_message_id TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(claim_id, notification_type));
        INSERT INTO tbl_store_items (item_id, item_name, description, is_active, quantity) VALUES (34, 'Blue Elixir', '-18 hours', 1, 10), (10, 'Golden Rascals NFT.', 'Solana NFT', 1, 2);
    """)
    for uid in [OWNER, PLAYER, NONOWNER] + [f'1000000000000000{i:02d}' for i in range(2, 10)]:
        conn.execute('INSERT INTO tbl_users VALUES (?, ?)', (uid, f'u_{uid[-4:]}'))
    conn.executescript((ROOT / 'db/migrations/create_christmas_advent_2026_claims_v1.sql').read_text(encoding='utf-8'))
    conn.executescript((ROOT / 'db/migrations/create_christmas_advent_2026_draft_revisions_v1.sql').read_text(encoding='utf-8'))
    conn.executescript((ROOT / 'db/migrations/20261124_christmas_advent_2026_v1.sql').read_text(encoding='utf-8'))
    conn.commit()
    conn.close()

    # Disposable test-only session helper (never part of repo).
    (base / 'login.php').write_text("""<?php
require __DIR__.'/api/config/admin-session.php';
require_once __DIR__.'/api/config/session.php';
$id = (string)($_GET['id'] ?? '');
narrrfs_establish_discord_oauth_session($_SESSION, ['id' => $id], static fn() => session_regenerate_id(true));
echo 'ok';
""", encoding='utf-8')
    (base / 'claim_csrf.php').write_text("""<?php
require __DIR__.'/api/config/admin-session.php';
require_once __DIR__.'/api/lib/christmas-advent-2026-claim.php';
$tok = christmas26_claim_issue_csrf($_SESSION);
session_write_close();
echo json_encode(['csrf_token' => $tok]);
""", encoding='utf-8')

    # Phase A: Default-OFF verification (all gates absent -> 404 everywhere).
    with contextlib.closing(socket.socket()) as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]

    env_off = os.environ.copy()
    for k in list(env_off):
        if k.startswith('NARRRFS_OWNER_CHRISTMAS_') or k.startswith('NARRRFS_CHRISTMAS_ADVENT_'):
            env_off.pop(k, None)

    srv = subprocess.Popen(
        [PHP, '-d', 'display_errors=0', '-d', f'session.save_path={base / "sessions"}', '-S', f'127.0.0.1:{port}', '-t', str(base)],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=env_off,
        creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0),
    )
    try:
        c = new_client()
        for _ in range(60):
            try:
                if req(c, port, f'/login.php?id={OWNER}')[0] == 200:
                    break
            except urllib.error.URLError:
                time.sleep(0.1)
        check(req(c, port, '/api/owner-control/v1/christmas-policy.php')[0] == 404, 'Default-OFF: christmas-policy.php returns 404')
        check(req(c, port, '/api/owner-control/v1/christmas-policy-stage.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'http://localhost'})[0] == 404, 'Default-OFF: christmas-policy-stage.php returns 404')
        check(req(c, port, '/api/owner-control/v1/christmas-policy-approve.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'http://localhost'})[0] == 404, 'Default-OFF: christmas-policy-approve.php returns 404')
        check(req(c, port, '/api/user/christmas-advent-2026-claim-context.php')[0] == 404, 'Default-OFF: claim-context returns 404')
        check(req(c, port, '/api/user/christmas-advent-2026-claim.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'http://localhost'})[0] == 404, 'Default-OFF: claim endpoint returns 404')
        check(req(c, port, '/api/discord/christmas-advent-2026-outbox-v1.php', 'POST', b'{}', {'Content-Type': 'application/json'})[0] == 404, 'Default-OFF: discord outbox endpoint returns 404')
    finally:
        srv.terminate()
        srv.wait(timeout=10)

    # Phase B: Enabled gates -> security, Owner Draft->Candidate->Approved flow, and Claim security checks.
    env_on = os.environ.copy()
    env_on.update({
        'NARRRFS_ADMIN_DISCORD_IDS': OWNER,
        'NARRRFS_OWNER_CSRF_ENABLED': '1',
        'NARRRFS_OWNER_CHRISTMAS_DRAFT_READ_ENABLED': '1',
        'NARRRFS_OWNER_CHRISTMAS_DRAFT_WRITE_ENABLED': '1',
        'NARRRFS_OWNER_CHRISTMAS_POLICY_READ_ENABLED': '1',
        'NARRRFS_OWNER_CHRISTMAS_POLICY_WRITE_ENABLED': '1',
        'NARRRFS_OWNER_CHRISTMAS_POLICY_APPROVE_ENABLED': '1',
        'NARRRFS_CHRISTMAS_ADVENT_2026_STATUS_ENABLED': '1',
        'NARRRFS_CHRISTMAS_ADVENT_2026_PAYOUT_ENABLED': '1',
        'NARRRFS_CHRISTMAS_ADVENT_2026_POLICY_VERSION': 'christmas_advent_2026_v1',
    })
    with contextlib.closing(socket.socket()) as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]

    srv = subprocess.Popen(
        [PHP, '-d', 'display_errors=0', '-d', f'session.save_path={base / "sessions"}', '-S', f'127.0.0.1:{port}', '-t', str(base)],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=env_on,
        creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0),
    )
    try:
        owner = new_client()
        player = new_client()
        nonowner = new_client()
        anon = new_client()

        for _ in range(60):
            try:
                if req(owner, port, f'/login.php?id={OWNER}')[0] == 200:
                    break
            except urllib.error.URLError:
                time.sleep(0.1)
        req(player, port, f'/login.php?id={PLAYER}')
        req(nonowner, port, f'/login.php?id={NONOWNER}')

        # Case 23a: Owner policy endpoints reject anonymous & non-owner.
        check(req(anon, port, '/api/owner-control/v1/christmas-policy.php')[0] == 401, 'Case 23: anonymous policy GET -> 401')
        check(req(nonowner, port, '/api/owner-control/v1/christmas-policy.php')[0] == 403, 'Case 23: non-owner policy GET -> 403')

        # Save Draft revision 1 via owner HTTP with max_bonus_wins_per_player=24 & #54 1 SPOINC voucher.
        days = [{
            'day_number': d,
            'dspoinc_min': 50000 if d == 24 else 1000,
            'dspoinc_max': 250000 if d == 24 else (9000 if d == 1 else 10000),
            'max_daily_claims': 100,
            'bonus': {'enabled': d in (1, 2), 'item_ids': [34] if d == 1 else ([10] if d == 2 else []), 'chance_basis_points': 5000 if d in (1, 2) else 0, 'max_winners': 10 if d in (1, 2) else 0, 'quantity_per_win': 1}
        } for d in range(1, 25)]
        draft = {'schema_version': 'christmas_advent_2026_draft_v2', 'event_key': 'christmas_advent_2026', 'max_bonus_wins_per_player': 24, 'first_bonus_guarantee': {'enabled': False}, 'days': days}

        _, raw_csrf = req(owner, port, '/api/owner-control/v1/csrf.php')
        draft_csrf = json.loads(raw_csrf)['csrf_token']
        st, body = req(owner, port, '/api/owner-control/v1/christmas-draft.php', 'POST', json.dumps({'revision': 0, 'draft': draft}).encode(), {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': draft_csrf})
        check(st == 200 and json.loads(body)['result']['revision'] == 1, 'Owner HTTP saved draft revision 1')

        # Stage Candidate via HTTP + scoped CSRF.
        st, cbody = req(owner, port, '/api/owner-control/v1/christmas-policy-csrf.php', 'POST', b'{"action":"stage"}', {'Content-Type': 'application/json', 'Origin': 'http://localhost'})
        check(st == 200, 'Issued scoped stage CSRF')
        stage_csrf = json.loads(cbody)['csrf_token']
        st, sbody = req(owner, port, '/api/owner-control/v1/christmas-policy-stage.php', 'POST', json.dumps({'draft_revision': 1, 'policy_version': 'christmas_advent_2026_v1'}).encode(), {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': stage_csrf})
        stage_res = json.loads(sbody)
        check(st == 200 and stage_res['result']['status'] == 'candidate', 'Owner HTTP staged release candidate')

        # Wrong CSRF scope rejected if re-using stage token or invalid token on approve.
        fp = stage_res['result']['policy_fingerprint']
        check(req(owner, port, '/api/owner-control/v1/christmas-policy-approve.php', 'POST', json.dumps({'policy_version': 'christmas_advent_2026_v1', 'policy_fingerprint': fp}).encode(), {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': '0'*64})[0] == 403, 'Bad CSRF rejected on policy approve')

        # Approve Immutable Policy via HTTP + scoped CSRF.
        _, abody = req(owner, port, '/api/owner-control/v1/christmas-policy-csrf.php', 'POST', b'{"action":"approve"}', {'Content-Type': 'application/json', 'Origin': 'http://localhost'})
        approve_csrf = json.loads(abody)['csrf_token']
        st, app_res = req(owner, port, '/api/owner-control/v1/christmas-policy-approve.php', 'POST', json.dumps({'policy_version': 'christmas_advent_2026_v1', 'policy_fingerprint': fp}).encode(), {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': approve_csrf})
        check(st == 200 and json.loads(app_res)['result']['status'] == 'approved', 'Owner HTTP approved immutable policy')

        # Case 23b: Player claim endpoint & context endpoint security checks.
        check(req(anon, port, '/api/user/christmas-advent-2026-claim-context.php')[0] == 401, 'Case 23: anonymous claim-context -> 401')
        check(req(anon, port, '/api/user/christmas-advent-2026-claim.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'http://localhost'})[0] == 401, 'Case 23: anonymous claim POST -> 401')
        _, tok_raw = req(player, port, '/claim_csrf.php')
        ptok = json.loads(tok_raw)['csrf_token']
        check(req(player, port, '/api/user/christmas-advent-2026-claim.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'https://evil.example', 'X-CSRF-Token': ptok})[0] == 403, 'Case 23: invalid Origin -> 403')
        check(req(player, port, '/api/user/christmas-advent-2026-claim.php', 'POST', b'{"amount":999999}', {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': ptok})[0] == 400, 'Case 23: client-supplied amount rejected with 400')
        check(req(player, port, '/api/user/christmas-advent-2026-claim.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': 'bad'})[0] == 403, 'Case 23: invalid CSRF rejected with 403')
        _, tok_raw = req(player, port, '/claim_csrf.php')
        ptok2 = json.loads(tok_raw)['csrf_token']
        st, resp_raw = req(player, port, '/api/user/christmas-advent-2026-claim.php', 'POST', b'{}', {'Content-Type': 'application/json', 'Origin': 'http://localhost', 'X-CSRF-Token': ptok2})
        check(st == 409, f'Pre-event clock returns 409 event_not_started via real HTTP endpoint (got {st}: {resp_raw})')
    finally:
        srv.terminate()
        srv.wait(timeout=10)

    # Case 13: Real multi-process SQLite BEGIN IMMEDIATE contention race at final claim slot & final stock unit.
    conn = sqlite3.connect(dbpath)
    conn.execute("UPDATE tbl_store_items SET quantity = 1 WHERE item_id = 34")
    conn.commit()
    conn.close()

    # Build strict final-1-slot policy version v2 (max_daily_claims = 1, 100% chance Blue Elixir #34 with quantity=1).
    race_script = base / 'race_setup.php'
    race_script.write_text("""<?php
require_once __DIR__ . '/api/lib/christmas-advent-2026-claim.php';
$db = new SQLite3(__DIR__ . '/db/narrrf_world.sqlite', SQLITE3_OPEN_READWRITE);
$draft = christmas26_draft_load_v1($db)['draft'];
$draft['days'][0]['max_daily_claims'] = 1;
$draft['days'][0]['bonus'] = ['enabled' => true, 'item_ids' => [34], 'chance_basis_points' => 10000, 'max_winners' => 1, 'quantity_per_win' => 1];
$saved = christmas26_draft_save_v1($db, $draft, 1, '328601656659017732');
$cand = christmas26_stage_policy_candidate($db, $saved['revision'], 'christmas_advent_2026_v2', '328601656659017732');
christmas26_approve_policy($db, 'christmas_advent_2026_v2', $cand['policy_fingerprint'], '328601656659017732', true);
echo 'OK';
""", encoding='utf-8')
    r_setup = subprocess.run([PHP, str(race_script)], capture_output=True, text=True, check=True)
    check(r_setup.stdout.strip() == 'OK', 'Case 13 setup final-1-slot policy v2')

    # Spawn 6 simultaneous PHP processes racing at the exact same barrier for the single Dec 1 claim + #34 Blue Elixir!
    worker_script = base / 'race_worker.php'
    worker_script.write_text("""<?php
require_once __DIR__ . '/api/lib/christmas-advent-2026-claim.php';
$uid = $argv[1];
$goAt = (float)$argv[2];
while (microtime(true) < $goAt) { usleep(1000); }
$db = new SQLite3(__DIR__ . '/db/narrrf_world.sqlite', SQLITE3_OPEN_READWRITE);
try {
    $res = christmas26_claim_execute_v1($db, $uid, new DateTimeImmutable('2026-12-01T12:00:00+01:00'), 'active');
    echo 'WIN:' . $res['claim_id'] . ':' . ($res['bonus_awarded'] ? '1' : '0') . ':' . $res['policy_version'];
} catch (Christmas26ClaimException $e) {
    echo 'ERR:' . $e->publicCode;
}
""", encoding='utf-8')

    target_ts = time.time() + 0.6
    uids = [PLAYER] + [f'1000000000000000{i:02d}' for i in range(2, 7)]
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
        futs = [pool.submit(subprocess.run, [PHP, str(worker_script), u, f'{target_ts:.6f}'], capture_output=True, text=True) for u in uids]
        results = [f.result().stdout.strip() for f in futs]

    wins = [x for x in results if x.startswith('WIN:')]
    cap_errs = [x for x in results if x == 'ERR:daily_claim_limit_reached']
    check(len(wins) == 1 and wins[0].endswith(':1:christmas_advent_2026_v2') and len(cap_errs) == 5, f'Case 13: 6-process barrier race produces 1 winner on active v2 + 5 cap rejections ({results})')

    conn = sqlite3.connect(dbpath)
    c_count = conn.execute('SELECT COUNT(*) FROM tbl_christmas_advent_2026_claims').fetchone()[0]
    stock_left = conn.execute('SELECT quantity FROM tbl_store_items WHERE item_id=34').fetchone()[0]
    inv_total = conn.execute('SELECT COALESCE(SUM(quantity),0) FROM tbl_user_inventory WHERE item_id=34').fetchone()[0]
    integ = conn.execute('PRAGMA integrity_check').fetchone()[0]
    conn.close()
    check(c_count == 1 and stock_left == 1 and inv_total == 1 and integ == 'ok', 'Case 13 & 30: exactly 1 claim, 1 inventory unit delivered, catalog stock intact, integrity ok')

print(f'ALL HTTP & CONCURRENCY CHECKS PASSED ({passes} assertions)')
