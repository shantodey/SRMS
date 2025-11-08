-- phpMyAdmin SQL Dump
-- version 5.2.0
-- Database: `mawts`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Initial admin data
--

INSERT INTO `admin` (`email`, `password`) VALUES
('admin@srms.edu', '$2y$10$dQmJ5.ImLmKdQO9L5C/Mxe5VJ0mD5g8ExNwx4KPK9LwPywDpXwZFi'); -- password is 'admin123'

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Initial departments data
--

INSERT INTO `departments` (`name`, `code`) VALUES
('Computer Science Engineering', 'CSE'),
('Electrical & Electronic Engineering', 'EEE'),
('Mechanical Engineering', 'ME');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Initial batches data
--

INSERT INTO `batches` (`name`, `year`) VALUES
('Batch 2025', 2025),
('Batch 2024', 2024),
('Batch 2023', 2023);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `index_no` varchar(20) NOT NULL UNIQUE,
  `board_roll` varchar(20) NOT NULL UNIQUE,
  `roll_no` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL UNIQUE,
  `subject_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `total_marks` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Initial subjects data
--

INSERT INTO `subjects` (`subject_code`, `subject_name`, `department_id`, `semester`, `total_marks`) VALUES
('CSE101', 'Programming I', 1, 1, 100),
('CSE102', 'Data Structures', 1, 1, 100),
('MATH101', 'Discrete Mathematics', 1, 1, 100),
('ENG101', 'English Composition', 1, 1, 100),
('PHY101', 'Physics I', 1, 1, 100);

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `grade` varchar(2) NOT NULL,
  `semester` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `grade_scale`
--

CREATE TABLE `grade_scale` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `grade` varchar(2) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Initial grade scale data
--

INSERT INTO `grade_scale` (`grade`, `min_percentage`) VALUES
('A+', 80.00),
('A', 70.00),
('A-', 60.00),
('B', 50.00),
('C', 40.00),
('D', 33.00),
('F', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `title` text NOT NULL,
  `content` text,
  `status` enum('published','draft') NOT NULL DEFAULT 'draft',
  `publish_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `admin`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

ALTER TABLE `students` ADD INDEX `idx_index_no` (`index_no`);
ALTER TABLE `students` ADD INDEX `idx_board_roll` (`board_roll`);
ALTER TABLE `results` ADD INDEX `idx_student_subject` (`student_id`, `subject_id`);
ALTER TABLE `subjects` ADD INDEX `idx_dept_sem` (`department_id`, `semester`);
ALTER TABLE `notices` ADD INDEX `idx_status_date` (`status`, `publish_date`);

COMMIT;
