"""Run the tracked migration runner against a temporary, seeded ledger."""
import hashlib
import os
import pathlib
import shutil
import sqlite3
import subprocess
import tempfile

root = pathlib.Path(__file__).resolve().parent
bash = r'C:\Program Files\Git\bin\bash.exe'
runner = root / 'scripts/apply-migration-manifest.sh'
entries = [line.split('|') for line in (root / 'db/migrations/manifest-v1.txt').read_text().splitlines() if line and not line.startswith('#')]
with tempfile.TemporaryDirectory(prefix='owner-migration-test-') as temp:
    folder = pathlib.Path(temp)
    manifest = folder / 'manifest.txt'
    for migration_id, name, checksum in entries:
        data = (root / 'db/migrations' / name).read_bytes().replace(b'\r\n', b'\n')
        assert hashlib.sha256(data).hexdigest() == checksum, name
        (folder / name).write_bytes(data)
    manifest.write_text('\n'.join('|'.join(e) for e in entries) + '\n', encoding='utf-8')
    dbfile = folder / 'fixture.sqlite'
    db = sqlite3.connect(dbfile)
    db.execute('CREATE TABLE tbl_schema_migrations (migration_id TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)')
    db.executemany('INSERT INTO tbl_schema_migrations(migration_id,checksum) VALUES (?,?)', [(e[0], e[2]) for e in entries[:-1]])
    db.commit(); db.close()
    env = dict(os.environ, NARRRFS_SQLITE_BIN='C:/sqlite/sqlite3.exe')
    def run():
        return subprocess.run([bash, str(runner), str(dbfile), str(manifest)], env=env, capture_output=True, text=True)
    first = run(); assert first.returncode == 0, (first.stdout, first.stderr)
    second = run(); assert second.returncode == 0, (second.stdout, second.stderr)
    db = sqlite3.connect(dbfile)
    assert db.execute('SELECT checksum FROM tbl_schema_migrations WHERE migration_id=?', (entries[-1][0],)).fetchall() == [(entries[-1][2],)]
    assert db.execute("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tbl_owner_player_adjustment_audit'").fetchone()[0] == 1
    fail_sql = b'CREATE TABLE must_rollback(id INTEGER);\nINSERT INTO missing_fixture_table VALUES (1);\n'
    (folder / 'failure.sql').write_bytes(fail_sql)
    with manifest.open('a', encoding='utf-8') as out: out.write('disposable-failure-v1|failure.sql|' + hashlib.sha256(fail_sql).hexdigest() + '\n')
    failed = run(); assert failed.returncode != 0, (failed.stdout, failed.stderr)
    assert db.execute("SELECT count(*) FROM sqlite_master WHERE name='must_rollback'").fetchone()[0] == 0
    assert db.execute("SELECT count(*) FROM tbl_schema_migrations WHERE migration_id='disposable-failure-v1'").fetchone()[0] == 0
    db.close()
    print('PASS: real manifest runner applies candidate after seeded prior ledger, verifies checksum, reruns idempotently, and rolls back failing fixture')
