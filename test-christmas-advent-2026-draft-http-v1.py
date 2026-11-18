# Isolated HTTP execution of the actual Christmas draft endpoint.
import contextlib
import http.cookiejar
import json
import os
from pathlib import Path
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parent
PHP = r'C:\xampp-server\php\php.exe'
ENDPOINT = '/api/owner-control/v1/christmas-draft.php'
OWNER = '111111111111111111'
passes = 0

def check(condition, description):
    global passes
    if not condition:
        raise AssertionError(description)
    passes += 1
    print('PASS:', description)

def new_client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

with tempfile.TemporaryDirectory(prefix='christmas26-http-') as directory:
    base = Path(directory)
    (base / 'api/owner-control/v1').mkdir(parents=True)
    (base / 'api/lib').mkdir(parents=True)
    (base / 'db').mkdir()
    (base / 'sessions').mkdir()
    shutil.copytree(ROOT / 'api/config', base / 'api/config')
    for name in ['christmas-advent-2026.php', 'christmas-advent-2026-draft-preflight-v1.php',
                 'christmas-advent-2026-draft-preflight-v2.php', 'christmas-advent-2026-draft-storage-v1.php']:
        shutil.copy2(ROOT / 'api/lib' / name, base / 'api/lib' / name)
    for name in ['christmas-draft.php', 'csrf.php']:
        shutil.copy2(ROOT / 'api/owner-control/v1' / name, base / 'api/owner-control/v1' / name)
    dbpath = base / 'db/narrrf_world.sqlite'
    db = sqlite3.connect(dbpath)
    db.executescript('CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY, is_active INTEGER); INSERT INTO tbl_store_items VALUES(33,1),(34,0);')
    db.executescript((ROOT / 'db/migrations/create_christmas_advent_2026_draft_revisions_v1.sql').read_text(encoding='utf-8'))
    db.close()
    # Test-only route lives in the temporary copy, never in the application.
    (base / 'login.php').write_text("""<?php
require __DIR__.'/api/config/admin-session.php';
require_once __DIR__.'/api/config/session.php';
$id=isset($_GET['nonowner'])?'222222222222222222':'111111111111111111';
narrrfs_establish_discord_oauth_session($_SESSION,['id'=>$id],static fn()=>session_regenerate_id(true));
echo 'ok';
""", encoding='utf-8')
    (base / 'expire.php').write_text("<?php require __DIR__.'/api/config/admin-session.php'; require_once __DIR__.'/api/config/session.php'; $_SESSION[NARRRFS_ADMIN_CSRF_ISSUED_AT_SESSION_KEY]=time()-NARRRFS_ADMIN_CSRF_TTL_SECONDS-1; echo 'ok';", encoding='utf-8')
    env = os.environ.copy()
    env.update({'NARRRFS_OWNER_CHRISTMAS_DRAFT_READ_ENABLED':'1', 'NARRRFS_OWNER_CHRISTMAS_DRAFT_WRITE_ENABLED':'1',
                'NARRRFS_OWNER_CSRF_ENABLED':'1', 'NARRRFS_ADMIN_DISCORD_IDS':OWNER})
    with contextlib.closing(socket.socket()) as sock:
        sock.bind(('127.0.0.1',0))
        port = sock.getsockname()[1]
    server = subprocess.Popen([PHP, '-d', 'display_errors=0', '-d', 'session.save_path='+str(base / 'sessions'),
                               '-S', '127.0.0.1:'+str(port), '-t', str(base)],
                              stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env=env,
                              creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    def request(opener, path, method='GET', body=None, headers=None):
        req = urllib.request.Request('http://127.0.0.1:'+str(port)+path, data=body, method=method, headers=headers or {})
        try:
            with opener.open(req, timeout=5) as response:
                return response.status, response.read()
        except urllib.error.HTTPError as error:
            return error.code, error.read()
    try:
        owner, anonymous, nonowner = new_client(), new_client(), new_client()
        for attempt in range(60):
            try:
                request(owner, '/login.php')
                break
            except urllib.error.URLError:
                time.sleep(0.1)
        else:
            raise RuntimeError('disposable server did not start')
        check(request(nonowner, '/login.php?nonowner=1')[0] == 200, 'fixture sessions created')
        check(request(anonymous, ENDPOINT)[0] == 401, 'anonymous GET rejected')
        status, raw = request(nonowner, ENDPOINT); print('non-owner response:', status, raw); check(status == 403, 'non-owner GET rejected')
        status, raw = request(owner, ENDPOINT)
        check(status == 200 and json.loads(raw)['result'] is None, 'actual endpoint owner GET returns empty draft')
        def token():
            status, raw = request(owner, '/api/owner-control/v1/csrf.php')
            assert status == 200, (status,raw)
            return json.loads(raw)['csrf_token']
        def headers(csrf):
            return {'Content-Type':'application/json', 'Origin':'http://localhost', 'X-CSRF-Token':csrf}
        days = [{'day_number':i,'dspoinc_min':50000 if i==24 else 1000,
                 'dspoinc_max':250000 if i==24 else 10000,'max_daily_claims':10,
                 'bonus':{'enabled':False,'item_ids':[],'chance_basis_points':0,
                          'max_winners':0,'quantity_per_win':1}} for i in range(1,25)]
        draft = {'schema_version':'christmas_advent_2026_draft_v2','event_key':'christmas_advent_2026',
                 'max_bonus_wins_per_player':3,'first_bonus_guarantee':{'enabled':False},'days':days}
        payload = json.dumps({'revision':0,'draft':draft}).encode()
        check(request(owner, ENDPOINT, 'PUT', b'{}', headers(token()))[0] == 405, 'wrong method rejected')
        csrf = token()
        check(request(owner, ENDPOINT, 'POST', payload, {'Content-Type':'text/plain','Origin':'http://localhost','X-CSRF-Token':csrf})[0] == 415, 'wrong content type rejected')
        check(request(owner, ENDPOINT, 'POST', payload, {'Content-Type':'application/json','X-CSRF-Token':csrf})[0] == 403, 'missing origin rejected')
        check(request(owner, ENDPOINT, 'POST', payload, {'Content-Type':'application/json','Origin':'https://invalid.example','X-CSRF-Token':csrf})[0] == 403, 'foreign origin rejected')
        check(request(owner, ENDPOINT, 'POST', payload, {'Content-Type':'application/json','Origin':'http://localhost'})[0] == 403, 'missing CSRF rejected')
        check(request(owner, ENDPOINT, 'POST', payload, headers('0'*64))[0] == 403, 'invalid CSRF rejected')
        expired = token()
        check(request(owner, '/expire.php')[0] == 200, 'fixture expired token prepared')
        status, _ = request(owner, ENDPOINT, 'POST', payload, headers(expired))
        check(status == 403, 'expired CSRF rejected with HTTP 403')

        used = token()
        check(request(owner, ENDPOINT, 'POST', b'{', headers(used))[0] == 400, 'malformed JSON rejected')
        check(request(owner, ENDPOINT, 'POST', payload, headers(used))[0] == 403, 'consumed CSRF cannot be reused')
        check(request(owner, ENDPOINT, 'POST', b'{}', headers(token()))[0] == 400, 'missing draft fields rejected')

        status, raw = request(owner, ENDPOINT, 'POST', b' '*65537, headers(token())); print('oversize response:', status, raw); check(status == 413, 'oversized body rejected')
        status, raw = request(owner, ENDPOINT, 'POST', payload, headers(token()))
        check(status == 200 and json.loads(raw)['result']['revision'] == 1, 'owner POST saves revision 1')
        status, raw = request(owner, ENDPOINT)
        check(status == 200 and json.loads(raw)['result']['draft'] == draft, 'owner GET reloads exact saved draft')
        check(request(owner, ENDPOINT, 'POST', payload, headers(token()))[0] == 409, 'stale revision returns 409')
        bad = dict(draft)
        bad['days'] = days[:]
        bad['days'][0] = dict(days[0], dspoinc_min=999)
        status, raw = request(owner, ENDPOINT, 'POST', json.dumps({'revision':1,'draft':bad}).encode(), headers(token()))
        check(status == 400 and 'invalid_dspoinc_range_day_1' in json.loads(raw)['details'], 'invalid range has bounded detail')
        inactive = json.loads(json.dumps(draft))
        inactive['days'][0]['bonus'] = {'enabled':True,'item_ids':[34],'chance_basis_points':100,'max_winners':1,'quantity_per_win':1}
        check(request(owner, ENDPOINT, 'POST', json.dumps({'revision':1,'draft':inactive}).encode(), headers(token()))[0] == 400, 'inactive Store candidate rejected')
        db = sqlite3.connect(dbpath)
        db.execute("CREATE TRIGGER reject_revision BEFORE INSERT ON tbl_christmas_advent_2026_draft_revisions BEGIN SELECT RAISE(ABORT, 'fixture failure'); END")
        db.commit(); db.close()
        check(request(owner, ENDPOINT, 'POST', json.dumps({'revision':1,'draft':draft}).encode(), headers(token()))[0] == 503, 'database write failure returns unavailable')
        status, raw = request(owner, ENDPOINT)
        check(status == 200 and json.loads(raw)['result']['revision'] == 1 and json.loads(raw)['result']['draft'] == draft, 'write failure leaves previous revision intact')
        db = sqlite3.connect(dbpath)
        check(db.execute('SELECT COUNT(*) FROM tbl_christmas_advent_2026_draft_revisions').fetchone()[0] == 1, 'failed HTTP requests create no extra revision')
        db.close()
        db = sqlite3.connect(dbpath); db.execute('DROP TRIGGER reject_revision'); db.commit(); db.close()
        server.terminate(); server.wait(timeout=10)
        for read_on, write_on in [(0,0),(1,0),(0,1),(1,1)]:
            env['NARRRFS_OWNER_CHRISTMAS_DRAFT_READ_ENABLED'] = str(read_on)
            env['NARRRFS_OWNER_CHRISTMAS_DRAFT_WRITE_ENABLED'] = str(write_on)
            with contextlib.closing(socket.socket()) as sock:
                sock.bind(('127.0.0.1',0)); port = sock.getsockname()[1]
            server = subprocess.Popen([PHP,'-d','display_errors=0','-d','session.save_path='+str(base/'sessions'),'-S','127.0.0.1:'+str(port),'-t',str(base)],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,env=env,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
            gate_owner = new_client()
            for attempt in range(60):
                try: request(gate_owner,'/login.php'); break
                except urllib.error.URLError: time.sleep(.1)
            else: raise RuntimeError('gate fixture did not start')
            read_status, _ = request(gate_owner, ENDPOINT)
            csrf_status, csrf_body = request(gate_owner, '/api/owner-control/v1/csrf.php')
            assert csrf_status == 200
            gate_token = json.loads(csrf_body)['csrf_token']
            write_status, _ = request(gate_owner, ENDPOINT, 'POST', payload, headers(gate_token))
            print(f'GATES read={read_on} write={write_on}: GET={read_status} POST={write_status}')
            check(read_status == (200 if read_on else 404), 'read gate independently enforced')
            check(write_status == (409 if write_on else 404), 'write gate independently enforced')
            db = sqlite3.connect(dbpath); count = db.execute('SELECT COUNT(*) FROM tbl_christmas_advent_2026_draft_revisions').fetchone()[0]; db.close()
            check(count == 1, 'gate probes create no revisions')
            server.terminate(); server.wait(timeout=10)
        print('HTTP startup: php -d session.save_path=<temporary sessions> -S 127.0.0.1:<ephemeral port> -t <temporary copy>')
        print('HTTP checks passed:', passes)
    finally:
        server.terminate()
        server.wait(timeout=10)





