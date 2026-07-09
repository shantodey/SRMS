# Enable mysqli extension in PHP CLI
# Run this script as Administrator

$phpIniPath = "C:\Program Files\php-8.4.13\php.ini"

Write-Host "Enabling mysqli extension for PHP CLI..." -ForegroundColor Cyan
Write-Host ""

# Check if running as admin
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "To run as Administrator:" -ForegroundColor Yellow
    Write-Host "1. Right-click PowerShell" -ForegroundColor Yellow
    Write-Host "2. Select 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host "3. Navigate to: cd C:\xampp\htdocs\SRMS\admin" -ForegroundColor Yellow
    Write-Host "4. Run: .\enable_mysqli.ps1" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

# Backup php.ini
Write-Host "Creating backup..." -ForegroundColor Yellow
$backupPath = "$phpIniPath.backup_" + (Get-Date -Format "yyyyMMdd_HHmmss")
Copy-Item -Path $phpIniPath -Destination $backupPath
Write-Host "Backup created at: $backupPath" -ForegroundColor Green
Write-Host ""

# Enable mysqli
Write-Host "Enabling mysqli extension..." -ForegroundColor Yellow
(Get-Content $phpIniPath) -replace ';extension=mysqli', 'extension=mysqli' | Set-Content $phpIniPath
Write-Host "mysqli extension enabled!" -ForegroundColor Green
Write-Host ""

# Verify
Write-Host "Verifying installation..." -ForegroundColor Yellow
$mysqlLoaded = php -m | Select-String "mysqli"

if ($mysqlLoaded) {
    Write-Host "SUCCESS! mysqli is now enabled." -ForegroundColor Green
    Write-Host ""
    Write-Host "You can now run:" -ForegroundColor Cyan
    Write-Host "  php backup_database.php" -ForegroundColor White
    Write-Host "  php run_exam_migration.php" -ForegroundColor White
} else {
    Write-Host "WARNING: mysqli not found in loaded modules." -ForegroundColor Red
    Write-Host "Please check if php_mysqli.dll exists in the ext folder." -ForegroundColor Yellow
}

Write-Host ""
pause
