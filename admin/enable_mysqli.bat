@echo off
echo Enabling mysqli extension in PHP CLI...
echo.
echo Current php.ini location:
php --ini
echo.
echo Please follow these manual steps:
echo.
echo 1. Open Notepad as Administrator
echo 2. Open file: C:\Program Files\php-8.4.13\php.ini
echo 3. Search for: ;extension=mysqli
echo 4. Remove the semicolon (;) to make it: extension=mysqli
echo 5. Save the file
echo 6. Run this command to verify: php -m ^| findstr mysqli
echo.
echo Alternatively, run this command in Administrator PowerShell:
echo (Get-Content "C:\Program Files\php-8.4.13\php.ini") -replace ';extension=mysqli', 'extension=mysqli' ^| Set-Content "C:\Program Files\php-8.4.13\php.ini"
echo.
pause
