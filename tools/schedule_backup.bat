@echo off
REM ============================================================
REM  DAT LICH SAO LUU TU DONG MOI NGAY LUC 03:00
REM  Phai chay bang "Run as administrator" (chuot phai > Run as administrator)
REM  Xoa lich:  schtasks /Delete /TN "GoldenGate Backup" /F
REM ============================================================
schtasks /Create /SC DAILY /ST 03:00 /TN "GoldenGate Backup" /TR "\"%~dp0backup.bat\"" /F
if errorlevel 1 (
  echo [LOI] Khong dat duoc lich. Hay chay file nay bang Run as administrator.
  exit /b 1
)
echo [OK] Da dat lich sao luu moi ngay luc 03:00. May tinh phai bat nguon luc do.
echo Kiem tra: mo "Task Scheduler" va tim muc "GoldenGate Backup".
exit /b 0
