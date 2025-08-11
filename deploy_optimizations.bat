@echo off
REM Deployment script for IRS optimizations

echo =============================================
echo IRS Optimization Deployment Script
echo =============================================
echo.

echo [*] Backing up current files...
set "backup_dir=C:\wamp64\www\IRS\backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
mkdir "%backup_dir%"
xcopy /E /Y "C:\wamp64\www\IRS\*.php" "%backup_dir%\"
xcopy /E /Y "C:\wamp64\www\IRS\.htaccess" "%backup_dir%\"

echo [*] Applying database optimizations...
mysql -u root -p1234 mis < "C:\wamp64\www\IRS\database\optimize_queries.sql"

if %ERRORLEVEL% NEQ 0 (
    echo [!] Error applying database optimizations
    exit /b 1
)

echo [*] Setting up optimized files...

:: Create logs directory if it doesn't exist
if not exist "C:\wamp64\www\IRS\logs" mkdir "C:\wamp64\www\IRS\logs"

:: Create assets directory if it doesn't exist
if not exist "C:\wamp64\www\IRS\assets" mkdir "C:\wamp64\www\IRS\assets"
if not exist "C:\wamp64\www\IRS\assets\js" mkdir "C:\wamp64\www\IRS\assets\js"
if not exist "C:\wamp64\www\IRS\assets\css" mkdir "C:\wamp64\www\IRS\assets\css"
if not exist "C:\wamp64\www\IRS\assets\img" mkdir "C:\wamp64\www\IRS\assets\img"

:: Create includes directory if it doesn't exist
if not exist "C:\wamp64\www\IRS\includes" mkdir "C:\wamp64\www\IRS\includes"

:: Create config directory if it doesn't exist
if not exist "C:\wamp64\www\IRS\config" mkdir "C:\wamp64\www\IRS\config"

echo [*] Setting file permissions...
icacls "C:\wamp64\www\IRS\logs" /grant "IUSR:(OI)(CI)F" /T
icacls "C:\wamp64\www\IRS\assets" /grant "IUSR:(OI)(CI)F" /T

echo [*] Restarting Apache to apply changes...
net stop wampapache64
net start wampapache64

if %ERRORLEVEL% NEQ 0 (
    echo [!] Error restarting Apache
    echo [*] Trying alternative method...
    net stop Apache2.4
    net start Apache2.4
    
    if %ERRORLEVEL% NEQ 0 (
        echo [!] Could not restart Apache. Please restart it manually.
    )
)

echo.
echo =============================================
echo [✓] Deployment complete!
echo =============================================
echo.
echo [*] Backup created at: %backup_dir%
echo [*] Test the optimizations by visiting:
echo     http://localhost/IRS/performance_test.php
echo.
echo [*] To apply the optimized index page:
echo     1. Backup your current index.php
echo     2. Rename optimized_index.php to index.php
echo     3. Test thoroughly in a staging environment first
echo.
pause
