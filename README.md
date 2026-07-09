# SRMS - Student Result Management System

SRMS is a web-based student result management system built for schools and colleges to simplify result publishing, student lookup, and administrative workflows. The project provides a public-facing result search experience for students while offering teachers and administrators tools to manage notices, results, departments, batches, exams, and student records.

## Live Project
- Local preview: https://srms.page.gd
- Production URL: Add your deployed URL here

## Screenshot
> A clean project screenshot will be added here once a public preview or hosted version is available.

## Technologies Used
- PHP 8+
- MySQL / MariaDB
- Bootstrap 5
- JavaScript
- Composer
- PHPSpreadsheet

## Core Features
- Student result search by name or board roll number
- Public notices and announcements for students
- Teacher and admin authentication
- Admin dashboard for managing students, teachers, departments, batches, exams, and results
- Bulk Excel result upload and result processing
- Result export and PDF download support
- Responsive UI for desktop and mobile use

## Dependencies
- phpoffice/phpspreadsheet

## Local Setup
1. Install XAMPP, WAMP, or LAMP with Apache, MySQL, and PHP.
2. Place the project in your web server root directory, such as htdocs/SRMS.
3. Start Apache and MySQL.
4. Create a MySQL database and update the credentials in [config/database.php](config/database.php).
5. Import the SQL structure from [CHECK_DB_STRUCTURE.sql](CHECK_DB_STRUCTURE.sql) and any relevant scripts in [admin](admin/) if your environment requires them.
6. Install PHP dependencies:
   ```bash
   composer install
   ```
7. Open the project in your browser:
   ```text
   http://localhost/SRMS
   ```
8. Log in through the teacher/admin portal to start using the system.

## Configuration
Update [config/database.php](config/database.php) with your local database host, username, password, and database name before running the application.

## Project Resources
- [Homepage](index.php)
- [About page](about.php)
- [Admin dashboard](admin.php)
- [Database and migration scripts](admin/)

## Notes
If you deploy this project publicly, replace the placeholder live URL with your actual production domain and consider adding a real screenshot for better presentation.
