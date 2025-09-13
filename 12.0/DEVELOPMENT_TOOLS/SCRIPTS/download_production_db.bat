@echo off
REM 🔧 Database Download Script for Local Testing (Windows)
REM Downloads production database from Render to local environment

echo 🔧 Starting database download from Render...

REM Create backup directory if it doesn't exist
if not exist "db\backups" mkdir "db\backups"

REM Download database from Render
echo 📥 Downloading production database...
curl -o "db\backups\narrrf_world_production_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%.sqlite" "https://narrrfs-world.onrender.com/db/narrrf_world.sqlite"

REM Copy to main database location for local testing
echo 📋 Copying to local database location...
copy "db\backups\narrrf_world_production_*.sqlite" "db\narrrf_world.sqlite"

echo ✅ Database download complete!
echo 📁 Production database saved to: db\backups\
echo 📁 Local database updated: db\narrrf_world.sqlite
echo.
echo 🔧 You can now test locally with production data!
pause
