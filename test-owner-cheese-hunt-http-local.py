"""End-to-end local HTTP verification for Owner Control Cheese Hunt V1 + public gameplay capture."""
import json
import os
import pathlib
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parent
PHP = r'C:\xampp-server\php\php.exe'
OWNER_ID = '222222222222222222'
PLAYER_ID = '111111111111111111'
OTHER_ID = '333333333333333333'

FILES = [
    'api/config/admin-authorization.php',
    'api/config/admin-route-policies.php',
    'api/config/admin-session.php',
    'api/config/database.php',
    'api/config/session.php',
    'api/lib/owner-cheese-hunt-v1.php',
    'api/owner-control/v1/cheese-hunt/overview.php',
    'api/owner-control/v1/cheese-hunt/csrf.php',
    'api/owner-control/v1/cheese-hunt/mission.php',
    'api/owner-control/v1/cheese-hunt/claim-review.php',
    'api/user/cheese-hunt/capture.php',
    'api/user/get-cheese-hunt-stats.php',
    'api/get-active-cheese-hunt.php',
    'owner-control/v1/index.php',
    'owner-control/v1/quest-hunt-overview.php',
    'owner-control/v1/cheese-hunt.php',
    'public/index.html',
]

with tempfile.TemporaryDirectory(prefix='cheese-hunt-http-fixture-') as tmp:
    root = pathlib.Path(tmp)
    (root / 'db').mkdir(parents=True, exist_ok=True)
    sessions = root / 'sessions'
    sessions.mkdir()

    db_path = root / 'db/narrrf_world.sqlite'
    db = sqlite3.connect(db_path)
    db.executescript(f"""
        CREATE TABLE tbl_users (discord_id TEXT PRIMARY KEY, username TEXT);
        CREATE TABLE tbl_user_scores (user_id TEXT, score INTEGER, game TEXT, source TEXT, timestamp TEXT);
        CREATE TABLE tbl_cheese_clicks (user_wallet TEXT, egg_id TEXT, timestamp TEXT, quest_id INTEGER);
        INSERT INTO tbl_users VALUES ('{PLAYER_ID}', 'playerone'), ('{OWNER_ID}', 'owner'), ('{OTHER_ID}', 'otheruser');
        INSERT INTO tbl_cheese_clicks VALUES ('{PLAYER_ID}', 'cheese-egg', CURRENT_TIMESTAMP, NULL);
    """)
    db.executescript((ROOT / 'db/migrations/20261124_owner_control_cheese_hunt_v1.sql').read_text(encoding='utf-8'))
    db.commit()
    db.close()

    for rel in FILES:
        dest = root / rel
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(ROOT / rel, dest)

    for sid, uid in [('ownerfixture', OWNER_ID), ('playerfixture', PLAYER_ID), ('otherfixture', OTHER_ID)]:
        code = (
            f"session_id('{sid}');session_start();$n=time();"
            f"$_SESSION=['discord_id'=>'{uid}','auth_provenance'=>'discord_oauth_v1',"
            f"'narrrfs_admin_auth_issued_at'=>$n,'narrrfs_admin_auth_last_seen_at'=>$n,"
            f"'narrrfs_admin_auth_expires_at'=>$n+43200];session_write_close();"
        )
        subprocess.run([PHP, '-d', 'session.save_path=' + str(sessions), '-r', code], check=True, capture_output=True)

    sock = socket.socket()
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
    sock.close()

    env = dict(
        os.environ,
        NARRRFS_ADMIN_DISCORD_IDS=OWNER_ID,
        NARRRFS_OWNER_SHELL_ENABLED='1',
        NARRRFS_OWNER_PLAYER_ADJUSTMENT_UI_ENABLED='1',
        NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_UI_ENABLED='1',
        NARRRFS_OWNER_QUEST_HUNT_OVERVIEW_READ_ENABLED='1',
        NARRRFS_OWNER_CHEESE_HUNT_UI_ENABLED='1',
        NARRRFS_OWNER_CHEESE_HUNT_READ_ENABLED='1',
        NARRRFS_OWNER_CHEESE_HUNT_WRITE_ENABLED='1',
        NARRRFS_OWNER_CHEESE_HUNT_CLAIM_REVIEW_ENABLED='1',
        NARRRFS_CHEESE_HUNT_PLAYER_ENABLED='1',
    )

    server = subprocess.Popen(
        [PHP, '-d', 'session.save_path=' + str(sessions), '-S', f'127.0.0.1:{port}', '-t', str(root)],
        env=env,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    def call(path, body=None, sid=None, token=None, origin='http://localhost'):
        headers = {'Origin': origin}
        data = None
        if body is not None:
            headers['Content-Type'] = 'application/json'
            data = json.dumps(body).encode('utf-8')
        if sid:
            headers['Cookie'] = 'PHPSESSID=' + sid
        if token:
            headers['X-CSRF-Token'] = token
        req = urllib.request.Request(f'http://127.0.0.1:{port}' + path, data=data, headers=headers)
        try:
            with urllib.request.urlopen(req, timeout=5) as resp:
                return resp.status, resp.read().decode('utf-8')
        except urllib.error.HTTPError as e:
            return e.code, e.read().decode('utf-8')

    try:
        for _ in range(50):
            try:
                socket.create_connection(('127.0.0.1', port), 0.1).close()
                break
            except OSError:
                time.sleep(0.1)

        # 1. Auth boundaries on Owner Control V2 pages and read API
        for path in ('/owner-control/v1/index.php', '/owner-control/v1/cheese-hunt.php', '/api/owner-control/v1/cheese-hunt/overview.php'):
            assert call(path)[0] == 401, path
            assert call(path, sid='otherfixture')[0] == 403, path
            st, body = call(path, sid='ownerfixture')
            assert st == 200, (path, st, body)
            if path == '/owner-control/v1/index.php':
                assert '/owner-control/v1/cheese-hunt.php' in body
                assert '/owner-control/v1/player-adjustments.php' in body

        # 2. Cross-scope CSRF rejected & create + activate mission
        _, csrf_rev_body = call('/api/owner-control/v1/cheese-hunt/csrf.php', {'action': 'review'}, 'ownerfixture')
        review_token = json.loads(csrf_rev_body)['csrf_token']
        create_payload = {
            'action': 'create',
            'idempotency_key': 'http_mission_create_0001',
            'mission': {
                'description': 'Owner HTTP test hunt',
                'reward_amount': 250,
                'required_clicks': 2,
                'movement_pattern': 'scatter_zone',
                'movement_speed': 'fast',
                'hidden_areas': True,
                'winner_message': 'Caught all test cheeses!',
                'role_id': '1399651053682692208',
                'expires_at': None,
            },
        }
        assert call('/api/owner-control/v1/cheese-hunt/mission.php', create_payload, 'ownerfixture', review_token)[0] == 403

        st, csrf_mis_body = call('/api/owner-control/v1/cheese-hunt/csrf.php', {'action': 'mission'}, 'ownerfixture')
        assert st == 200
        mission_token = json.loads(csrf_mis_body)['csrf_token']

        st, body = call('/api/owner-control/v1/cheese-hunt/mission.php', create_payload, 'ownerfixture', mission_token)
        assert st == 200 and json.loads(body)['result']['mission_id'] == 1, (st, body)
        # Consumed CSRF token cannot be replayed
        assert call('/api/owner-control/v1/cheese-hunt/mission.php', create_payload, 'ownerfixture', mission_token)[0] == 403
        # Fresh token + same idempotency_key replays safely; modified payload gets 409
        _, csrf2 = call('/api/owner-control/v1/cheese-hunt/csrf.php', {'action': 'mission'}, 'ownerfixture')
        st, body = call('/api/owner-control/v1/cheese-hunt/mission.php', create_payload, 'ownerfixture', json.loads(csrf2)['csrf_token'])
        assert st == 200 and json.loads(body)['result']['replayed'] is True

        _, csrf3 = call('/api/owner-control/v1/cheese-hunt/csrf.php', {'action': 'mission'}, 'ownerfixture')
        changed = json.loads(json.dumps(create_payload))
        changed['mission']['reward_amount'] = 999
        assert call('/api/owner-control/v1/cheese-hunt/mission.php', changed, 'ownerfixture', json.loads(csrf3)['csrf_token'])[0] == 409

        # Activate mission 1
        _, csrf_act = call('/api/owner-control/v1/cheese-hunt/csrf.php', {'action': 'mission'}, 'ownerfixture')
        st, body = call(
            '/api/owner-control/v1/cheese-hunt/mission.php',
            {'action': 'activate', 'idempotency_key': 'http_mission_activate_001', 'mission': {'mission_id': 1}},
            'ownerfixture',
            json.loads(csrf_act)['csrf_token'],
        )
        assert st == 200 and json.loads(body)['result']['mission_id'] == 1

        # 3. Public active quest & stats endpoints
        st, active_body = call('/api/get-active-cheese-hunt.php')
        active_json = json.loads(active_body)
        assert st == 200 and active_json['quest']['source'] == 'owner_cheese_hunt_v1'
        assert active_json['quest']['cheese_config']['required_clicks'] == 2

        st, anon_stats = call('/api/user/get-cheese-hunt-stats.php')
        assert st == 200 and json.loads(anon_stats)['authenticated'] is False

        # 4. Player capture boundaries & completion
        assert call('/api/user/cheese-hunt/capture.php', {'idempotency_key': 'player_cap_key_0001'})[0] == 401
        assert call('/api/user/cheese-hunt/capture.php', {'idempotency_key': 'player_cap_key_0001'}, 'playerfixture', origin='http://evil.invalid')[0] == 403

        st, cap1 = call('/api/user/cheese-hunt/capture.php', {'idempotency_key': 'player_cap_key_0001'}, 'playerfixture')
        assert st == 200 and json.loads(cap1)['result']['click_count'] == 1

        # Advance cooldown in disposable test DB to avoid slow sleep
        db = sqlite3.connect(db_path)
        db.execute("UPDATE tbl_owner_cheese_hunt_progress SET last_capture_at = last_capture_at - 5")
        db.commit()
        db.close()

        st, cap2 = call('/api/user/cheese-hunt/capture.php', {'idempotency_key': 'player_cap_key_0002'}, 'playerfixture')
        cap2_json = json.loads(cap2)
        assert st == 200 and cap2_json['result']['claim_pending'] is True and cap2_json['result']['claim_id'] == 1
        assert call('/api/user/cheese-hunt/capture.php', {'idempotency_key': 'player_cap_key_0003'}, 'playerfixture')[0] == 409

        st, player_stats = call('/api/user/get-cheese-hunt-stats.php', sid='playerfixture')
        ps = json.loads(player_stats)
        assert st == 200 and ps['authenticated'] is True and ps['total_clicks'] == 3 and ps['quest_clicks'] == 2 and ps['quest_completed'] is True and ps['claim_status'] == 'pending', ps

        # 5. Owner claim approval -> DSPOINC credit + role delivery outbox
        _, csrf_rev2 = call('/api/owner-control/v1/cheese-hunt/csrf.php', {'action': 'review'}, 'ownerfixture')
        st, rev_body = call(
            '/api/owner-control/v1/cheese-hunt/claim-review.php',
            {'claim_id': 1, 'action': 'approve', 'reason': 'Verified HTTP catch', 'idempotency_key': 'http_review_claim1_001'},
            'ownerfixture',
            json.loads(csrf_rev2)['csrf_token'],
        )
        rev_json = json.loads(rev_body)
        assert st == 200 and rev_json['result']['credited_amount'] == 250 and rev_json['result']['role_delivery'] == 'pending'

        st, ov_body = call('/api/owner-control/v1/cheese-hunt/overview.php', sid='ownerfixture')
        ov_json = json.loads(ov_body)
        assert st == 200 and len(ov_json['pending_claims']) == 0 and ov_json['recent_claims'][0]['role_delivery_status'] == 'pending'

        db = sqlite3.connect(db_path)
        assert db.execute("SELECT COALESCE(SUM(score),0) FROM tbl_user_scores WHERE user_id=? AND source='owner_cheese_hunt'", (PLAYER_ID,)).fetchone()[0] == 250
        assert db.execute("SELECT status, role_id FROM tbl_owner_cheese_hunt_role_delivery WHERE claim_id=1").fetchone() == ('pending', '1399651053682692208')
        db.close()

        print('PASS: end-to-end Cheese Hunt V1 HTTP auth, mission lifecycle, player capture, stats, DSPOINC credit, and role outbox')
    finally:
        server.terminate()
        server.wait(timeout=5)
