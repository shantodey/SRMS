-- ==========================================
-- FINAL FIX: Recreate total_marks column with correct type
-- ==========================================

-- Step 1: Drop the column if it exists (to start fresh)
ALTER TABLE results DROP COLUMN IF EXISTS total_marks;

-- Step 2: Add it back with the CORRECT type - DECIMAL(10,2)
ALTER TABLE results
ADD COLUMN total_marks DECIMAL(10,2) NOT NULL DEFAULT 100.00
AFTER marks_obtained;

-- Step 3: Verify the column type
SHOW COLUMNS FROM results LIKE 'total_marks';

-- Step 4: Show a sample of data (should all be 100.00 default now)
SELECT id, marks_obtained, total_marks
FROM results
ORDER BY id DESC
LIMIT 5;

-- ==========================================
-- After running this, RE-UPLOAD your Excel files!
-- The old data will have default 100, new uploads will be correct.
-- ==========================================
