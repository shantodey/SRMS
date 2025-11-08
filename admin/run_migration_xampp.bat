@echo off
REM Run migration using XAMPP's PHP instead of system PHP
REM XAMPP's PHP already has mysqli enabled

echo ========================================
echo EXAM LAYER MIGRATION (Using XAMPP PHP)
echo ========================================
echo.

REM Check if XAMPP PHP exists
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: XAMPP PHP not found at C:\xampp\php\php.exe
    echo.
    echo Please ensure XAMPP is installed at C:\xampp
    echo.
    pause
    exit /b 1
)

echo Using XAMPP PHP: C:\xampp\php\php.exe
echo.

REM Step 1: Backup Database
echo ========================================
echo STEP 0: Backup Database
echo ========================================
echo.
C:\xampp\php\php.exe backup_database.php
if errorlevel 1 (
    echo.
    echo ERROR: Backup failed!
    echo.
    pause
    exit /b 1
)

echo.
echo Backup completed successfully!
echo.
pause

REM Step 2: Run Migration
echo.
echo ========================================
echo STEP 1-3: Run Migration
echo ========================================
echo.
echo Starting migration...
echo.

C:\xampp\php\php.exe run_exam_migration.php

echo.
echo ========================================
echo Migration Complete
echo ========================================
echo.
echo Please check the output above for any errors.
echo.
pause
