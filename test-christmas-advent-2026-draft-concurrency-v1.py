# File-backed, independent-process Christmas draft revision race.
import json
import os
from pathlib import Path
import sqlite3
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parent
PHP = Path(r'C:\xampp-server\php\php.exe')
days = [{'day_number': i, 'dspoinc_min': 50000 if i == 24 else 1000,
         'dspoinc_max': 250000 if i == 24 else 10000, 'max_daily_claims': 10,
         'bonus': {'enabled': False, 'item_ids': [], 'chance_basis_points': 0,
                   'max_winners': 0, 'quantity_per_win': 1}} for i in range(1,25)]
draft = {'schema_version':'christmas_advent_2026_draft_v2','event_key':'christmas_advent_2026',
         'max_bonus_wins_per_player':3,'first_bonus_guarantee':{'enabled':False},'days':days}
with tempfile.TemporaryDirectory(prefix='christmas26-race-') as tmp:
    temp = Path(tmp)
    dbpath = temp/'fixture.sqlite'
    with sqlite3.connect(dbpath) as db:
        db.executescript('CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY,is_active INTEGER);')
        db.executescript((ROOT/'db/migrations/create_christmas_advent_2026_draft_revisions_v1.sql').read_text(encoding='utf-8'))
    db.close()
    (temp/'draft.json').write_text(json.dumps(draft),encoding='utf-8')
    worker = temp/'worker.php'
    worker.write_text('''<?php
require $argv[1];
$db=new SQLite3($argv[2],SQLITE3_OPEN_READWRITE); $db->enableExceptions(true); $db->busyTimeout(10000);
while (!file_exists($argv[4])) { usleep(1000); }
try { $r=christmas26_draft_save_v1($db,json_decode(file_get_contents($argv[3]),true),0,'111111111111111111'); echo 'saved:'.$r['revision']; }
catch (Throwable $e) { echo $e->getMessage(); }
''',encoding='utf-8')
    args=[str(PHP),str(worker),str(ROOT/'api/lib/christmas-advent-2026-draft-storage-v1.php'),str(dbpath),str(temp/'draft.json'),str(temp/'start')]
    processes=[subprocess.Popen(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True) for _ in range(4)]
    (temp/'start').write_text('go')
    results=[]
    for proc in processes:
        out,err=proc.communicate(timeout=20)
        if proc.returncode: raise AssertionError((proc.returncode,err))
        results.append(out)
    assert results.count('saved:1')==1, results
    assert results.count('draft_revision_conflict')==3, results
    with sqlite3.connect(dbpath) as db:
        rows=db.execute('SELECT revision,draft_json,fingerprint FROM tbl_christmas_advent_2026_draft_revisions').fetchall()
        assert len(rows)==1 and rows[0][0]==1
        import hashlib
        assert hashlib.sha256(rows[0][1].encode()).hexdigest()==rows[0][2]
        assert db.execute('PRAGMA integrity_check').fetchone()[0]=='ok'
    db.close()
    print('PASS: 4 independent PHP processes: one saved, three conflicts')
    print('PASS: one complete revision, valid fingerprint and integrity_check=ok')
