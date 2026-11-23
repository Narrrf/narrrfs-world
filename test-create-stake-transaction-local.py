"""Exercise the real create-stake endpoint against isolated SQLite fixtures.

The debit cases call the candidate owner transaction helper directly; one
contention case also uses a fixture SQL writer to control lock ordering.
No application or production database is opened.
"""
import json
import os
import pathlib
import shutil
import sqlite3
import subprocess
import tempfile
import time

ROOT = pathlib.Path(__file__).resolve().parent
PHP = pathlib.Path(r"C:\xampp-server\php\php.exe")
USER = "111111111111111111"


def setup(root):
    for directory in ("api/user", "api/config", "api/lib", "db"):
        (root / directory).mkdir(parents=True, exist_ok=True)
    for name in ("api/user/create-stake.php", "api/user/staking-contract-helpers.php", "api/config/database.php", "api/lib/owner-player-adjustments-v1.php"):
        shutil.copyfile(ROOT / name, root / name)
    path = root / "db/narrrf_world.sqlite"
    db = sqlite3.connect(path)
    db.executescript("""
      CREATE TABLE tbl_users(discord_id TEXT PRIMARY KEY);
      CREATE TABLE tbl_user_scores(user_id TEXT, score INTEGER,game TEXT,source TEXT,timestamp TEXT);
      CREATE TABLE tbl_seasons(season_name TEXT, is_active INTEGER);
      CREATE TABLE tbl_nft_ownership(ownership_id INTEGER, token_id TEXT, collection TEXT,
         is_verified INTEGER, verified_at TEXT, acquired_at TEXT, user_id TEXT);
      CREATE TABLE tbl_score_adjustments(user_id TEXT, admin_id TEXT, amount INTEGER,
         action TEXT, reason TEXT, timestamp TEXT);
      CREATE TABLE tbl_store_items(item_id INTEGER PRIMARY KEY,is_active INTEGER);
      CREATE TABLE tbl_user_inventory(user_id TEXT,item_id INTEGER,quantity INTEGER,acquired_at TEXT,UNIQUE(user_id,item_id));
      INSERT INTO tbl_users VALUES('111111111111111111'),('222222222222222222');
      INSERT INTO tbl_seasons VALUES('fixture', 1);
      INSERT INTO tbl_user_scores VALUES('111111111111111111', 1000,'test','fixture',CURRENT_TIMESTAMP);
    """)
    db.executescript((ROOT / 'db/migrations/20261123_owner_control_player_adjustments_v1.sql').read_text(encoding='utf-8'))
    db.commit()
    db.close()
    return path


def stake(root, amount):
    code = "$_SERVER['HTTP_HOST']='localhost';$_SERVER['REQUEST_METHOD']='GET';" + \
        "$_GET=['user_id'=>'" + USER + "','amount'=>'" + str(amount) + "','lock_duration_days'=>'14'];" + \
        "require 'api/user/create-stake.php';"
    return subprocess.Popen([str(PHP), "-r", code], cwd=root, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, text=True, encoding="utf-8", errors="replace")


def result(process):
    out, err = process.communicate(timeout=20)
    assert process.returncode == 0, (process.returncode, out, err)
    return json.loads(out)


def debit_process(root, amount, key):
    code = "require 'api/lib/owner-player-adjustments-v1.php';" + \
        "$db=new SQLite3('db/narrrf_world.sqlite');" + \
        "$p=['user_id'=>'" + USER + "','action'=>'remove','amount'=>" + str(amount) + \
        ",'reason'=>'fixture debit','idempotency_key'=>'" + key + "'];" + \
        "try{$r=owner_player_adjust($db,$p,'222222222222222222','points');echo json_encode(['ok'=>true]+$r);}" + \
        "catch(OwnerPlayerAdjustmentException $e){echo json_encode(['ok'=>false,'error'=>$e->publicCode]);}"
    return subprocess.Popen([str(PHP), '-r', code], cwd=root, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE, text=True, encoding='utf-8', errors='replace')


def debit(root, amount, key):
    return result(debit_process(root, amount, key))


def main():
    with tempfile.TemporaryDirectory(prefix="narrrfs-stake-fixture-") as temp:
        root = pathlib.Path(temp)
        path = setup(root)
        assert result(stake(root, 700))["success"] is True
        assert debit(root, 301, 'fixture_debit_key_01')['error'] == 'insufficient_available_balance'
        assert debit(root, 300, 'fixture_debit_key_02')['ok'] is True
        db = sqlite3.connect(path, timeout=10)
        assert db.execute("SELECT SUM(amount) FROM tbl_dspoinc_stakes WHERE status='active'").fetchone()[0] == 700
        db.close()
        print("PASS: stake first; only available funds can be debited")

    with tempfile.TemporaryDirectory(prefix="narrrfs-stake-fixture-") as temp:
        root = pathlib.Path(temp)
        path = setup(root)
        # Initialize the real staking schema with a successful 100-point stake.
        assert result(stake(root, 100))["success"] is True
        assert debit(root, 800, 'fixture_debit_key_03')['ok'] is True
        rejected = result(stake(root, 101))
        assert rejected["success"] is False and rejected["error"] == "Insufficient balance", rejected
        print("PASS: debit first; later stake rechecks available funds")

    with tempfile.TemporaryDirectory(prefix="narrrfs-stake-fixture-") as temp:
        root = pathlib.Path(temp)
        path = setup(root)
        assert result(stake(root, 100))["success"] is True
        db = sqlite3.connect(path, timeout=10)
        # Hold the writer lock before starting the real endpoint, then commit a
        # debit. The endpoint must wait and use the committed balance.
        db.execute("BEGIN IMMEDIATE")
        process = stake(root, 700)
        time.sleep(0.5)
        try:
            if process.poll() is not None:
                raise AssertionError(("stake did not wait for writer", result(process)))
            db.execute("INSERT INTO tbl_user_scores(user_id,score) VALUES(?,?)", (USER, -300))
            db.commit()
            rejected = result(process)
        finally:
            db.rollback()
            if process.poll() is None:
                process.kill()
                process.communicate()
            db.close()
        assert rejected["success"] is False and rejected["error"] == "Insufficient balance", rejected
        verify = sqlite3.connect(path)
        assert verify.execute("SELECT COUNT(*) FROM tbl_dspoinc_stakes").fetchone()[0] == 1
        verify.close()
        print("PASS: independent writer contention; stake rechecks after commit")

    with tempfile.TemporaryDirectory(prefix="narrrfs-stake-fixture-") as temp:
        root = pathlib.Path(temp)
        path = setup(root)
        assert result(stake(root, 100))["success"] is True
        db = sqlite3.connect(path, timeout=10)
        db.execute("BEGIN IMMEDIATE")
        a = stake(root, 600)
        b = debit_process(root, 600, 'fixture_race_key_0001')
        try:
            time.sleep(0.5)
            assert a.poll() is None and b.poll() is None, 'competitors did not wait at lock barrier'
            db.commit()
            stake_result, debit_result = result(a), result(b)
            assert int(stake_result['success']) + int(debit_result['ok']) == 1, (stake_result, debit_result)
            total=db.execute('SELECT SUM(score) FROM tbl_user_scores').fetchone()[0]
            frozen=db.execute("SELECT SUM(amount) FROM tbl_dspoinc_stakes WHERE status='active'").fetchone()[0]
            assert total >= frozen, (total, frozen)
            receipts=db.execute('SELECT COUNT(*) FROM tbl_owner_player_adjustment_receipts').fetchone()[0]
            assert receipts == int(debit_result['ok']), (receipts, debit_result)
        finally:
            db.rollback()
            for process in (a,b):
                if process.poll() is None: process.kill(); process.communicate()
            db.close()
        print('PASS: separate stake and real owner-debit processes race at writer barrier')

    with tempfile.TemporaryDirectory(prefix="narrrfs-stake-fixture-") as temp:
        root = pathlib.Path(temp)
        path = setup(root)
        assert result(stake(root, 100))["success"] is True
        db = sqlite3.connect(path, timeout=10)
        db.execute("BEGIN IMMEDIATE")
        process = stake(root, 200)
        try:
            failed = result(process)
            assert failed["success"] is False, failed
            assert db.execute("SELECT COUNT(*) FROM tbl_dspoinc_stakes").fetchone()[0] == 1
            assert db.execute("SELECT COUNT(*) FROM tbl_score_adjustments").fetchone()[0] == 1
        finally:
            db.rollback()
            db.close()
        print("PASS: writer lock timeout leaves stakes and audit unchanged")

    with tempfile.TemporaryDirectory(prefix="narrrfs-stake-fixture-") as temp:
        root = pathlib.Path(temp)
        path = setup(root)
        assert result(stake(root, 100))["success"] is True
        db = sqlite3.connect(path)
        db.execute("CREATE TRIGGER fail_stake BEFORE INSERT ON tbl_dspoinc_stakes BEGIN SELECT RAISE(ABORT,'fixture failure'); END")
        db.commit()
        failed = result(stake(root, 200))
        assert failed["success"] is False, failed
        assert db.execute("SELECT COUNT(*) FROM tbl_dspoinc_stakes").fetchone()[0] == 1
        assert db.execute("SELECT COUNT(*) FROM tbl_score_adjustments").fetchone()[0] == 1
        db.close()
        print("PASS: failed stake rolls back without partial rows")


if __name__ == "__main__":
    main()
