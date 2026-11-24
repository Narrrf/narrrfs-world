"""Run the tracked migration runner against a temporary, seeded ledger for Cheese Hunt V1."""
import hashlib
import os
import pathlib
import sqlite3
import subprocess
import tempfile

root = pathlib.Path(__file__).resolve().parent
bash = r'C:\Program Files\Git\bin\bash.exe'
runner = root / 'scripts/apply-migration-manifest.sh'
entries = [
    line.split('|')
    for line in (root / 'db/migrations/manifest-v1.txt').read_text(encoding='utf-8').splitlines()
    if line and not line.startswith('#')
]
assert entries[-1][0] == 'owner-control-cheese-hunt-v1-20261124', entries[-1]

with tempfile.TemporaryDirectory(prefix='cheese-hunt-migration-test-') as temp:
    folder = pathlib.Path(temp)
    manifest = folder / 'manifest.txt'
    for migration_id, name, checksum in entries:
        data = (root / 'db/migrations' / name).read_bytes().replace(b'\r\n', b'\n')
        assert hashlib.sha256(data).hexdigest() == checksum, name
        (folder / name).write_bytes(data)
    manifest.write_text('\n'.join('|'.join(e) for e in entries) + '\n', encoding='utf-8')

    dbfile = folder / 'fixture.sqlite'
    db = sqlite3.connect(dbfile)
    db.execute(
        'CREATE TABLE tbl_schema_migrations (migration_id TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)'
    )
    db.executemany(
        'INSERT INTO tbl_schema_migrations(migration_id,checksum) VALUES (?,?)',
        [(e[0], e[2]) for e in entries[:-1]],
    )
    db.commit()
    db.close()

    env = dict(os.environ, NARRRFS_SQLITE_BIN='C:/sqlite/sqlite3.exe')

    def run():
        return subprocess.run([bash, str(runner), str(dbfile), str(manifest)], env=env, capture_output=True, text=True)

    first = run()
    assert first.returncode == 0, (first.stdout, first.stderr)
    second = run()
    assert second.returncode == 0, (second.stdout, second.stderr)

    db = sqlite3.connect(dbfile)
    assert db.execute(
        'SELECT checksum FROM tbl_schema_migrations WHERE migration_id=?', (entries[-1][0],)
    ).fetchall() == [(entries[-1][2],)]

    expected_tables = {
        'tbl_owner_cheese_hunt_missions',
        'tbl_owner_cheese_hunt_progress',
        'tbl_owner_cheese_hunt_claims',
        'tbl_owner_cheese_hunt_audit',
        'tbl_owner_cheese_hunt_receipts',
        'tbl_owner_cheese_hunt_role_delivery',
    }
    found_tables = {
        row[0]
        for row in db.execute(
            "SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'tbl_owner_cheese_hunt_%'"
        ).fetchall()
    }
    assert found_tables == expected_tables, (found_tables, expected_tables)

    fail_sql = b'CREATE TABLE must_rollback(id INTEGER);\nINSERT INTO missing_fixture_table VALUES (1);\n'
    (folder / 'failure.sql').write_bytes(fail_sql)
    with manifest.open('a', encoding='utf-8') as out:
        out.write('disposable-failure-v1|failure.sql|' + hashlib.sha256(fail_sql).hexdigest() + '\n')

    failed = run()
    assert failed.returncode != 0, (failed.stdout, failed.stderr)
    assert db.execute("SELECT count(*) FROM sqlite_master WHERE name='must_rollback'").fetchone()[0] == 0
    assert db.execute("SELECT count(*) FROM tbl_schema_migrations WHERE migration_id='disposable-failure-v1'").fetchone()[0] == 0
    db.close()

print('PASS: real manifest runner applies Cheese Hunt V1 migration, verifies checksum, reruns idempotently, and rolls back failure')
