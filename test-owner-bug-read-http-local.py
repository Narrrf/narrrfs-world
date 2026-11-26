"""Disposable HTTP checks; never opens the XAMPP database."""
import json, os, shutil, socket, sqlite3, subprocess, tempfile, time, urllib.request, urllib.error
from pathlib import Path

ROOT = Path(__file__).resolve().parent
PHP = r'C:\xampp-server\php\php.exe'
OWNER = '111111111111111111'

with tempfile.TemporaryDirectory(prefix='bug-read-test-') as tmp:
    root = Path(tmp)
    for folder in ('api/config', 'api/owner-control/v1', 'api/lib', 'db', 'sessions'):
        (root / folder).mkdir(parents=True)
    for name in ('admin-authorization.php', 'admin-session.php', 'admin-route-policies.php', 'session.php', 'database.php'):
        shutil.copy2(ROOT / 'api/config' / name, root / 'api/config' / name)
    shutil.copy2(ROOT / 'api/owner-control/v1/bug-tracker.php', root / 'api/owner-control/v1/bug-tracker.php')
    for name in ('csrf.php','bug-tracker-update.php'):
        shutil.copy2(ROOT / 'api/owner-control/v1' / name,root / 'api/owner-control/v1' / name)
    for name in ('owner-bug-tracker-update-v1.php','owner-bug-tracker-bulk-v1.php'):
        shutil.copy2(ROOT / 'api/lib' / name,root / 'api/lib' / name)
    db = sqlite3.connect(root / 'db/narrrf_world.sqlite')
    db.executescript("""CREATE TABLE tbl_bug_categories(category_id INTEGER,category_name TEXT,color_code TEXT,icon TEXT);
CREATE TABLE tbl_bug_priorities(priority_id INTEGER,priority_name TEXT,priority_level INTEGER,color_code TEXT,icon TEXT);
CREATE TABLE tbl_bug_statuses(status_id INTEGER,status_name TEXT,color_code TEXT,icon TEXT,is_active INTEGER,is_resolved INTEGER);
CREATE TABLE tbl_bug_reports(bug_id INTEGER PRIMARY KEY,owner_control_version INTEGER,discord_username TEXT,title TEXT,category_id INTEGER,priority_id INTEGER,status_id INTEGER,assigned_to TEXT,created_at TEXT,updated_at TEXT,resolved_at TEXT,description TEXT DEFAULT 'Issue',resolution_notes TEXT);
CREATE TABLE tbl_bug_status_history(history_id INTEGER PRIMARY KEY,bug_id INTEGER,old_status_id INTEGER,new_status_id INTEGER,changed_by TEXT,changed_at TEXT,change_reason TEXT);
CREATE TABLE tbl_bug_assignments(assignment_id INTEGER PRIMARY KEY,bug_id INTEGER,assigned_to TEXT,assigned_by TEXT,assigned_at TEXT,unassigned_at TEXT,assignment_notes TEXT);
CREATE TABLE tbl_owner_control_bug_update_receipts(receipt_id INTEGER PRIMARY KEY,actor_subject TEXT,idempotency_key_hash TEXT,request_hash TEXT,bug_id INTEGER,expected_version INTEGER,result_version INTEGER,result_json TEXT,expires_at TEXT,UNIQUE(actor_subject,idempotency_key_hash));
INSERT INTO tbl_bug_categories VALUES(1,'Game','',''); INSERT INTO tbl_bug_priorities VALUES(7,'High',1,'',''),(1,'Low',2,'','');
INSERT INTO tbl_bug_statuses VALUES(1,'Reported','','',1,0),(2,'Done','','',1,1);
INSERT INTO tbl_bug_reports(bug_id,owner_control_version,discord_username,title,category_id,priority_id,status_id,assigned_to,created_at,updated_at,resolved_at) VALUES(1,1,'Alice','Cheese crash',1,7,1,'Dev','2026-01-01','2026-01-02',NULL),(2,1,'Bob','Mouse bug',1,1,2,NULL,'2026-01-03','2026-01-04','2026-01-04'),(3,1,'Alice','Mouse stuck',1,1,1,'Dev','2026-01-05','2026-01-06',NULL);""")
    db.commit(); db.close()
    # Test-only synthetic OAuth provenance, never included in the application.
    (root / 'login.php').write_text("<?php require __DIR__.'/api/config/admin-session.php'; narrrfs_establish_discord_oauth_session($_SESSION,['id'=>$_GET['id']],static fn()=>session_regenerate_id(true)); echo session_id();", encoding='utf-8')
    sock=socket.socket(); sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]; sock.close()
    env=os.environ.copy();env.update({'NARRRFS_ADMIN_DISCORD_IDS':OWNER,'NARRRFS_OWNER_BUG_TRACKER_READ_ENABLED':'1','NARRRFS_OWNER_BUG_TRACKER_UPDATE_ENABLED':'1','NARRRFS_OWNER_CSRF_ENABLED':'1'})
    proc=subprocess.Popen([PHP,'-d',f'session.save_path={root / "sessions"}','-S',f'127.0.0.1:{port}','-t',str(root)],env=env,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    def get(path, cookie=None):
        request=urllib.request.Request(f'http://127.0.0.1:{port}'+path,headers={'Cookie':cookie} if cookie else {})
        try:
            with urllib.request.urlopen(request,timeout=5) as response: return response.status,response.read().decode(),response.headers
        except urllib.error.HTTPError as error: return error.code,error.read().decode(),error.headers
    try:
        for _ in range(50):
            try: get('/'); break
            except OSError: time.sleep(.1)
        path='/api/owner-control/v1/bug-tracker.php'
        assert get(path)[0]==401
        _,session,_=get('/login.php?id='+OWNER); cookie='PHPSESSID='+session
        _,session,_=get('/login.php?id=222222222222222222'); other='PHPSESSID='+session
        response=get(path,other); assert response[0]==403,(response[0],response[1])
        def ids(query):
            code,body,_=get(path+'?'+query,cookie); assert code==200,(code,body); data=json.loads(body);return [x['bug_id'] for x in data['bugs']],data['pagination']['filtered_total']
        assert ids('search=Mouse&status=1&assignee=Dev')==([3],1)
        assert ids('assignee=__unassigned__')==([2],1)
        assert ids('priority=7')==([1],1)
        assert ids('search=absent')==([],0)
        assert ids('sort=oldest')==([1,2,3],3)
        assert ids('sort=priority')==([1,3,2],3)
        assert ids('')==([3,1,2],3)
        assert get(path+'?sort=bad',cookie)[0]==400
        csrf=json.loads(get('/api/owner-control/v1/csrf.php',cookie)[1])['csrf_token']
        payload=json.dumps({'bulk':True,'cases':[{'bug_id':1,'expected_version':1},{'bug_id':3,'expected_version':1}],'action':'assign','value':'Dev2','idempotency_key':'http-bulk-test-key-000001'}).encode()
        req=urllib.request.Request(f'http://127.0.0.1:{port}/api/owner-control/v1/bug-tracker-update.php',data=payload,method='POST',headers={'Cookie':cookie,'Content-Type':'application/json','Origin':'http://localhost','X-CSRF-Token':csrf})
        with urllib.request.urlopen(req,timeout=5) as response: assert json.loads(response.read())['success'] is True
        assert ids('assignee=Dev2')==([3,1],2)
        print('PASS disposable HTTP owner/anonymous/non-owner, combinations, empty, sorting and invalid sort')
    finally:
        proc.terminate();proc.wait(timeout=5)
