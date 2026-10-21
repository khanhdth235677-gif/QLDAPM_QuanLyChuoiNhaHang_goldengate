@echo off
setlocal
REM ============================================================
REM  SAO LUU GOLDEN GATE: co so du lieu + anh mon an
REM  Chay bang cach nhap dup chuot, hoac dat lich tu dong bang schedule_backup.bat
REM  CHINH CAC DONG "set" BEN DUOI CHO DUNG MAY CUA BAN
REM ============================================================
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NAME=goldengate_db
set DB_USER=root
set DB_PASS=
set APP_DIR=C:\xampp\htdocs\goldengate
set BACKUP_DIR=C:\goldengate_backups
set KEEP_DAYS=30
REM ============================================================

if not exist "%MYSQL_BIN%\mysqldump.exe" (
  echo [LOI] Khong tim thay mysqldump.exe trong "%MYSQL_BIN%". Hay sua dong MYSQL_BIN.
  exit /b 1
)
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmm"') do set STAMP=%%i

set PASSARG=
if not "%DB_PASS%"=="" set PASSARG=-p%DB_PASS%

echo Dang sao luu co so du lieu %DB_NAME% ...
"%MYSQL_BIN%\mysqldump.exe" -u %DB_USER% %PASSARG% --databases %DB_NAME% --routines --single-transaction --default-character-set=utf8mb4 --result-file="%BACKUP_DIR%\goldengate_%STAMP%.sql"
if errorlevel 1 (
  echo [LOI] Sao luu co so du lieu that bai. Kiem tra MySQL da chay chua, va DB_USER / DB_PASS.
  if exist "%BACKUP_DIR%\goldengate_%STAMP%.sql" del "%BACKUP_DIR%\goldengate_%STAMP%.sql"
  exit /b 1
)

if exist "%APP_DIR%\assets\uploads" (
  echo Dang sao luu anh mon an ...
  powershell -NoProfile -Command "Compress-Archive -Path '%APP_DIR%\assets\uploads\*' -DestinationPath '%BACKUP_DIR%\uploads_%STAMP%.zip' -Force"
)

REM Xoa ban sao luu cu hon KEEP_DAYS ngay
forfiles /p "%BACKUP_DIR%" /m goldengate_*.sql /d -%KEEP_DAYS% /c "cmd /c del @path" >nul 2>nul
forfiles /p "%BACKUP_DIR%" /m uploads_*.zip /d -%KEEP_DAYS% /c "cmd /c del @path" >nul 2>nul

echo.
echo [OK] Da sao luu vao %BACKUP_DIR%
echo      goldengate_%STAMP%.sql  (co so du lieu)
echo      uploads_%STAMP%.zip     (anh mon an)
exit /b 0
