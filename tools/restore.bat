@echo off
setlocal
REM ============================================================
REM  KHOI PHUC GOLDEN GATE TU BAN SAO LUU
REM  Cach dung:  restore.bat "C:\goldengate_backups\goldengate_20261021_0300.sql"
REM  CANH BAO: GHI DE TOAN BO du lieu hien tai bang du lieu trong ban sao luu.
REM ============================================================
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_USER=root
set DB_PASS=
REM ============================================================

if "%~1"=="" (
  echo Cach dung: restore.bat "duong_dan_file_sao_luu.sql"
  exit /b 1
)
if not exist "%~1" (
  echo [LOI] Khong tim thay file: %~1
  exit /b 1
)
if not exist "%MYSQL_BIN%\mysql.exe" (
  echo [LOI] Khong tim thay mysql.exe trong "%MYSQL_BIN%". Hay sua dong MYSQL_BIN.
  exit /b 1
)

echo File se khoi phuc: %~1
echo CANH BAO: toan bo du lieu hien tai se bi GHI DE. Hay chay backup.bat truoc neu chua sao luu.
set /p OK=Go YES (viet hoa) de tiep tuc, phim khac de huy: 
if not "%OK%"=="YES" (
  echo Da huy.
  exit /b 0
)

set PASSARG=
if not "%DB_PASS%"=="" set PASSARG=-p%DB_PASS%

"%MYSQL_BIN%\mysql.exe" -u %DB_USER% %PASSARG% --default-character-set=utf8mb4 < "%~1"
if errorlevel 1 (
  echo [LOI] Khoi phuc that bai.
  exit /b 1
)
echo [OK] Da khoi phuc du lieu. Neu can, giai nen uploads_*.zip vao %~dp0..\assets\uploads de khoi phuc anh mon an.
exit /b 0
