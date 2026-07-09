# Fix: mysqli Extension Not Found

## The Problem

You're getting this error:
```
PHP Fatal error: Uncaught Error: Class "mysqli" not found in C:\xampp\htdocs\SRMS\config\database.php:9
```

**Cause**: The mysqli PHP extension is not enabled in your **PHP CLI** (command-line) installation.

---

## Solution: 3 Options

Choose **ONE** of the following options:

---

## ✅ **Option 1: Use XAMPP's PHP (EASIEST - RECOMMENDED)**

XAMPP's PHP already has mysqli enabled. Just use it instead of the system PHP.

### Steps:

1. **Open PowerShell or Command Prompt** (no admin needed)

2. **Navigate to admin folder**:
   ```cmd
   cd C:\xampp\htdocs\SRMS\admin
   ```

3. **Run the migration using XAMPP's PHP**:
   ```cmd
   run_migration_xampp.bat
   ```

   **OR** manually:
   ```cmd
   C:\xampp\php\php.exe backup_database.php
   C:\xampp\php\php.exe run_exam_migration.php
   ```

✅ **Done!** The migration will now work.

---

## Option 2: Enable mysqli in System PHP (PowerShell - AUTOMATIC)

This automatically enables mysqli in your system PHP installation.

### Steps:

1. **Right-click PowerShell** → **Run as Administrator**

2. **Allow script execution** (if needed):
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   ```

3. **Navigate to admin folder**:
   ```powershell
   cd C:\xampp\htdocs\SRMS\admin
   ```

4. **Run the fix script**:
   ```powershell
   .\enable_mysqli.ps1
   ```

5. **Verify**:
   ```powershell
   php -m | findstr mysqli
   ```
   Should output: `mysqli`

6. **Now run migration**:
   ```powershell
   php backup_database.php
   php run_exam_migration.php
   ```

---

## Option 3: Enable mysqli Manually

This manually edits the php.ini file.

### Steps:

1. **Find php.ini location**:
   ```cmd
   php --ini
   ```
   Output will show: `Loaded Configuration File: C:\Program Files\php-8.4.13\php.ini`

2. **Open Notepad as Administrator**:
   - Press `Win + S`
   - Type "Notepad"
   - Right-click → **Run as Administrator**

3. **Open php.ini**:
   - File → Open
   - Navigate to: `C:\Program Files\php-8.4.13\php.ini`
   - Click "Open"

4. **Find and Edit**:
   - Press `Ctrl + F` to search
   - Search for: `;extension=mysqli`
   - **Remove the semicolon** (`;`) to make it: `extension=mysqli`
   - There are 2 occurrences (around lines 900 and 930), change **both**

5. **Save**:
   - File → Save
   - Close Notepad

6. **Verify**:
   ```cmd
   php -m | findstr mysqli
   ```
   Should output: `mysqli`

7. **Run migration**:
   ```cmd
   cd C:\xampp\htdocs\SRMS\admin
   php backup_database.php
   php run_exam_migration.php
   ```

---

## Verification

After applying any option above, verify mysqli is loaded:

```cmd
php -m | findstr mysqli
```

**Expected output**:
```
mysqli
```

If you see `mysqli` in the output, you're good to go!

---

## If Still Not Working

### Check Extension Directory

1. **Find extension_dir**:
   ```cmd
   php -i | findstr extension_dir
   ```

2. **Verify php_mysqli.dll exists**:
   ```cmd
   dir "C:\Program Files\php-8.4.13\ext\php_mysqli.dll"
   ```

   If file **does NOT exist**, you need to:
   - Download PHP from: https://windows.php.net/download/
   - Choose "Thread Safe" ZIP
   - Extract `ext\php_mysqli.dll` to your PHP ext folder

### Still Having Issues?

**Just use Option 1** (XAMPP's PHP) - it's the easiest and most reliable!

---

## Quick Reference

### Use XAMPP PHP for all commands:

```cmd
# Instead of:
php backup_database.php

# Use:
C:\xampp\php\php.exe backup_database.php
```

### Or create an alias (PowerShell):

```powershell
# Add to your PowerShell profile
Set-Alias php "C:\xampp\php\php.exe"
```

Then you can use `php` normally.

---

## Summary

**Recommended**: Use **Option 1** (run_migration_xampp.bat)
- No admin rights needed
- No configuration changes
- Works immediately

**Alternative**: Use **Option 2** (PowerShell script)
- Automatic
- Fixes system PHP permanently
- Requires admin rights

**Manual**: Use **Option 3** (edit php.ini)
- Full control
- Fixes system PHP permanently
- Requires admin rights

---

## Next Steps After Fix

Once mysqli is working:

1. ✅ Run backup:
   ```cmd
   C:\xampp\php\php.exe backup_database.php
   ```

2. ✅ Run migration:
   ```cmd
   C:\xampp\php\php.exe run_exam_migration.php
   ```

3. ✅ Test the system:
   - Visit: `http://localhost/SRMS/admin/manage_exams.php`
   - Create a test exam
   - Download template and upload results

---

**Good luck!** 🚀
