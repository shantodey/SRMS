-- Run this in phpMyAdmin to see the EXACT column order
DESCRIBE results;

-- Also show any triggers that might be interfering
SHOW TRIGGERS LIKE 'results';

-- Show any views
SHOW CREATE VIEW student_result;
