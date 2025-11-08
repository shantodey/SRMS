-- Add total_marks column to results table
-- This stores the maximum marks for each result (needed for percentage calculation)

-- Check if total_marks column exists, if not add it
ALTER TABLE results
ADD COLUMN IF NOT EXISTS total_marks DECIMAL(10,2) NOT NULL DEFAULT 100 AFTER marks_obtained;

-- Update instruction: After running this, the results table will have:
-- id, student_id, exam_id, subject_id, marks_obtained, total_marks, grade, semester, upload_id, created_at, updated_at
