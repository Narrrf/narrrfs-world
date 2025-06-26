import sqlite3
import csv

CSV_PATH = r"C:\xampp-server\htdocs\narrrfs-world\score download\Narrrf's World Leaderboard - 2206.csv"
SQLITE_PATH = r"C:\xampp-server\htdocs\narrrfs-world\narrrf_world.sqlite"

with open(CSV_PATH, encoding='utf-8') as csvfile:
    reader = csv.DictReader(csvfile)
    rows = [(row['User ID'], int(row['Points Balance'])) for row in reader if row['User ID'] and row['Points Balance'].isdigit()]

conn = sqlite3.connect(SQLITE_PATH)
cur = conn.cursor()

for user_id, score in rows:
    cur.execute(
        "INSERT OR REPLACE INTO tbl_user_scores (user_id, score, game, source) VALUES (?, ?, ?, ?)",
        (user_id, score, 'discord-import', 'import-2025-06-22')
    )

conn.commit()
conn.close()
print("Import complete (no pandas).")
