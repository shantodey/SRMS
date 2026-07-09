# Manage Results Feature - Installation Guide

## ✨ New Features Added (Teachers Only)

### 1. **Manage Results Section**
- View all uploaded results with powerful filtering
- Edit individual student marks
- Delete wrong results
- Search by student name or index number
- Filter by: Exam Type, Semester, Department, Subject

### 2. **Undo Last Upload Feature**
- Instantly rollback the last result upload
- Shows preview with count, timestamp, and exam details
- Prevents accidental data loss

---

## 📋 Installation Steps

### Step 1: Run Database Migration

You need to add new database columns and tables for this feature to work.

**Option A: Via phpMyAdmin**
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select your SRMS database
3. Click on "SQL" tab
4. Copy and paste the contents of `add_upload_tracking.sql`
5. Click "Go" to execute

**Option B: Via MySQL Command Line**
```bash
cd C:\xampp\htdocs\SRMS\admin
mysql -u root -p your_database_name < add_upload_tracking.sql
```

**Option C: Automatic (Tables created on first upload)**
- The `upload_history` table is created automatically on first result upload
- But you still need to add the `upload_id` column to `results` table manually

### Step 2: Verify Installation

After running the SQL, verify the changes:

```sql
-- Check if upload_id column exists in results table
DESC results;

-- Check if upload_history table exists
SHOW TABLES LIKE 'upload_history';
```

Expected Results:
- `results` table should have `upload_id` column (VARCHAR(50))
- `upload_history` table should exist

---

## 🎯 How to Use

### For Teachers:

1. **Upload Results** (as usual)
   - Go to "Add Results" section
   - Select exam details and upload Excel file
   - After success, "Undo Last Upload" button appears

2. **Undo Last Upload** (if needed)
   - Click "Undo Last Upload" button (appears after uploading)
   - Confirm the action
   - All results from that upload will be deleted
   - You can then re-upload with corrected data

3. **Manage Individual Results**
   - Go to "Manage Results" section (new menu item)
   - Use filters to find specific results
   - Click ✏️ to edit marks (grade auto-recalculates)
   - Click 🗑️ to delete a single result

---

## 🔒 Access Control

- **Teachers**: Full access to Manage Results and Undo features
- **Admin**: No access (admins don't need to edit exam scores)

---

## 📁 New Files Created

### JavaScript:
- `/js/manage_results.js` - Separate module for result management

### PHP APIs:
- `/admin/get_results.php` - Fetch results with filters
- `/admin/get_result.php` - Get single result details
- `/admin/update_result.php` - Edit result marks
- `/admin/delete_result.php` - Delete single result
- `/admin/undo_last_upload.php` - Rollback entire upload

### Database:
- `/admin/add_upload_tracking.sql` - Migration script

---

## 🐛 Troubleshooting

### Issue: "Undo Last Upload" button doesn't appear
**Solution**: Make sure you're logged in as a Teacher (not Admin)

### Issue: Error when uploading results
**Solution**: Run the SQL migration script first to add `upload_id` column

### Issue: Can't see "Manage Results" menu
**Solution**: This feature is only available for Teachers, not Admins

### Issue: Grade not updating after edit
**Solution**: Check your `grade_scale` table has proper percentage ranges

---

## 💡 Tips

1. **Always use Undo feature immediately** after noticing a mistake
2. **Undo only works for the most recent upload** in your session
3. **Edit feature** is for individual corrections only
4. **For bulk corrections**, use Undo + Re-upload method

---

## 📊 Database Schema Changes

```sql
-- results table (new column)
results
  ├── upload_id VARCHAR(50) NULL  [NEW]
  └── updated_at TIMESTAMP         [NEW]

-- upload_history table (new table)
upload_history
  ├── id VARCHAR(50) PRIMARY KEY
  ├── exam_type VARCHAR(50)
  ├── semester INT
  ├── department_id INT
  ├── subject_id INT (nullable)
  ├── records_count INT
  ├── status VARCHAR(20)
  ├── created_at TIMESTAMP
  └── updated_at TIMESTAMP
```

---

## ✅ Testing Checklist

After installation, test these scenarios:

- [ ] Upload a Class Test Excel file
- [ ] See "Undo Last Upload" button appear
- [ ] Go to "Manage Results" and see the uploaded results
- [ ] Edit one result's marks
- [ ] Delete one result
- [ ] Click "Undo Last Upload" to remove all results from that upload
- [ ] Upload again with corrected data
- [ ] Filter by Semester/Department/Subject

---

## 🎉 You're Done!

The Manage Results feature is now fully installed and ready to use!

For support or questions, check the code comments in:
- `js/manage_results.js`
- `admin/process_excel_upload.php`
