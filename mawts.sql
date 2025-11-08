-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2025 at 05:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mawts`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT 'Administrator',
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(4, 'Administrator', 'shanto@gmail.com', '$2y$10$o8ifh8hWIjyiQhwtA.8VHul3c9v/aJ0UmPipl4Mu6SXBSts/ZkB4S', '2025-10-11 16:14:21');

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `name`, `year`, `created_at`) VALUES
(6, '20Th', 2024, '2025-10-10 16:34:18'),
(8, '21Th', 2025, '2025-10-11 04:37:25'),
(10, '19th', 19, '2025-10-14 14:20:31'),
(12, 'Batch 21th', 21, '2025-10-27 14:21:30'),
(13, 'Batch 20th', 20, '2025-10-27 17:09:35');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'Computer science and technology', '85', '2025-10-09 15:34:27'),
(3, 'Mechanical Engineering', 'ME', '2025-10-09 15:34:27'),
(4, 'Electronics Technology', '68', '2025-10-11 04:42:31'),
(5, ' Automobile Technology', '72', '2025-10-11 04:42:53'),
(6, ' Civil Technology', '64', '2025-10-11 04:43:12');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `exam_type` enum('Final','Midterm','ClassTest','Assignment','Quiz') NOT NULL DEFAULT 'Final',
  `exam_number` int(11) DEFAULT NULL COMMENT 'For ClassTest/Assignment (1st, 2nd, 3rd)',
  `title` varchar(200) NOT NULL,
  `semester` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL COMMENT 'NULL for Final/Midterm',
  `total_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `exam_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_type`, `exam_number`, `title`, `semester`, `department_id`, `subject_id`, `total_marks`, `exam_date`, `created_at`, `updated_at`) VALUES
(1, 'Final', NULL, 'Legacy Final Exam - Semester 6', 6, 1, NULL, 100.00, '2025-10-14', '2025-10-26 15:36:29', '2025-10-26 15:36:29'),
(2, 'ClassTest', 1, 'CT-1 - Computer networking - Sem 6', 6, 1, 6, 10.00, '2025-10-26', '2025-10-26 15:38:22', '2025-10-26 15:38:22'),
(3, 'ClassTest', 2, 'CT-2 - Computer networking - Sem 6', 6, 1, 6, 10.00, '2025-10-26', '2025-10-26 15:47:46', '2025-10-26 15:47:46'),
(4, 'Midterm', NULL, 'Midterm - Semester 6', 6, 1, NULL, 30.00, '2025-10-29', '2025-10-29 17:10:30', '2025-10-29 17:10:30');

-- --------------------------------------------------------

--
-- Table structure for table `grade_scale`
--

CREATE TABLE `grade_scale` (
  `id` int(11) NOT NULL,
  `grade` varchar(2) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_scale`
--

INSERT INTO `grade_scale` (`id`, `grade`, `min_percentage`, `created_at`) VALUES
(1, 'A+', 80.00, '2025-10-09 15:34:27'),
(2, 'A', 70.00, '2025-10-09 15:34:27'),
(3, 'A-', 60.00, '2025-10-09 15:34:27'),
(4, 'B', 50.00, '2025-10-09 15:34:27'),
(5, 'C', 40.00, '2025-10-09 15:34:27'),
(6, 'D', 33.00, '2025-10-09 15:34:27'),
(7, 'F', 0.00, '2025-10-09 15:34:27'),
(19, 'A+', 80.00, '2025-10-26 15:36:29'),
(20, 'A', 75.00, '2025-10-26 15:36:29'),
(21, 'A-', 70.00, '2025-10-26 15:36:29'),
(22, 'B+', 65.00, '2025-10-26 15:36:29'),
(23, 'B', 60.00, '2025-10-26 15:36:29'),
(24, 'B-', 55.00, '2025-10-26 15:36:29'),
(25, 'C+', 50.00, '2025-10-26 15:36:29'),
(26, 'C', 45.00, '2025-10-26 15:36:29'),
(27, 'C-', 40.00, '2025-10-26 15:36:29'),
(28, 'D', 33.00, '2025-10-26 15:36:29'),
(29, 'F', 0.00, '2025-10-26 15:36:29');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `content` text DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'draft',
  `publisher_type` enum('admin','teacher') NOT NULL DEFAULT 'admin',
  `publisher_id` int(11) DEFAULT NULL,
  `publisher_name` varchar(255) DEFAULT NULL,
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `publish_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `creator_type` enum('admin','teacher') DEFAULT 'admin',
  `creator_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `status`, `publisher_type`, `publisher_id`, `publisher_name`, `priority`, `publish_date`, `expiry_date`, `created_by`, `creator_type`, `creator_id`, `created_at`) VALUES
(6, 'project ', 'This project is Under development please patiently wait', 'published', 'admin', 4, 'admin@srms.edu', 'normal', '2025-10-12', NULL, 4, 'admin', 4, '2025-10-12 14:23:27'),
(11, 'class test', 'kal chapter 2 valo moto pora asba ', 'published', 'admin', NULL, 'Administrator', 'normal', '2025-10-14', NULL, NULL, 'teacher', 1, '2025-10-14 13:33:28'),
(13, 'Takla Maderchod', 'আমরা Mawts আমরা সবাই বোকাচোদা তাই তোমাদেরকে কোন অনুষ্ঠান পূজা কিংবা কোন বড় কোন ইভেন্টে যেখানে কারিগরি শিক্ষা বোর্ড বন্ধ দেয় সেখানেও আমরা বন্ধ দেই না তোমাদেরকে পারলে আমাদের বাল ছিড়ো', 'published', 'admin', NULL, 'Administrator', 'normal', '2025-10-20', NULL, NULL, 'admin', 4, '2025-10-20 14:25:10'),
(16, 'sdfsdf', 'sdf', 'published', '', 1, 'Joy Devnath', 'normal', '2025-10-20', NULL, NULL, 'admin', NULL, '2025-10-20 20:06:11'),
(17, 'CST department head', 'আমি কম্পিউটার ডিপার্টমেন্ট হেড নাম আমার মামুন আমি করি আরাচোদা কাম যার কারণে পোলাপাইন করে না আমাকে সম্মান', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-20', NULL, NULL, 'admin', NULL, '2025-10-20 20:28:11'),
(18, 'asdfd', 'sdfsdfsdf', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-21', NULL, NULL, 'admin', NULL, '2025-10-21 13:24:25'),
(19, 'asdfasd', 'sdfsd', 'published', '', 4, 'Administrator', 'normal', '2025-10-28', NULL, NULL, 'admin', NULL, '2025-10-28 18:07:23'),
(20, 'বিভাগীয় সার্ভার রক্ষণাবেক্ষণ কার্যক্রম', 'কম্পিউটার সায়েন্স বিভাগের সার্ভার রক্ষণাবেক্ষণ ও ডেটা সিঙ্ক্রোনাইজেশন প্রক্রিয়া পরীক্ষামূলকভাবে সম্পন্ন হচ্ছে। অনলাইন অ্যাক্সেসে সাময়িক বিলম্ব হতে পারে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:07:57'),
(21, 'ল্যাব নেটওয়ার্ক সংযোগ পরীক্ষা', 'শিক্ষণ কার্যক্রমের মানোন্নয়নের অংশ হিসেবে ল্যাব নেটওয়ার্ক সংযোগের গতি ও স্থিতিশীলতা যাচাই করা হচ্ছে। ব্যবহারকারীদের সহযোগিতা প্রত্যাশিত।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:08:10'),
(22, 'প্রকল্প ব্যবস্থাপনা সিস্টেম আপডেট', 'ডেভেলপমেন্ট বিভাগের চলমান প্রকল্পগুলোর জন্য নতুন সফটওয়্যার ব্যবস্থাপনা সিস্টেম পরীক্ষা করা হচ্ছে। ফলাফল বিশ্লেষণ শেষে স্থায়ীভাবে কার্যকর করা হবে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:08:27'),
(23, 'ল্যাব অটোমেশন প্রক্রিয়া মূল্যায়ন', 'বিভাগের ল্যাব পরিচালনা সহজীকরণের লক্ষ্যে অটোমেশন মডিউল যুক্ত করা হয়েছে, যা পরীক্ষামূলকভাবে পর্যবেক্ষণে রয়েছে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:08:39'),
(24, 'বৈদ্যুতিক নিরাপত্তা পর্যালোচনা বিজ্ঞপ্তি', 'ল্যাব ভবনের বিদ্যুৎ সরবরাহ ও সুরক্ষা ব্যবস্থার কার্যকারিতা যাচাইয়ের জন্য পরীক্ষামূলক মূল্যায়ন চলছে। প্রয়োজন অনুযায়ী সাময়িক বিচ্ছিন্নতা ঘটতে পারে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:08:54'),
(25, 'ইন্সট্রুমেন্ট ক্যালিব্রেশন পরীক্ষা', 'ইলেকট্রিক্যাল মেশিন ল্যাবে ব্যবহৃত যন্ত্রপাতির ক্যালিব্রেশন নির্ভুলতা যাচাইয়ের জন্য রুটিন টেস্ট পরিচালিত হচ্ছে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:09:19'),
(26, 'মেশিন রক্ষণাবেক্ষণ কার্যক্রম', 'মেকানিক্যাল ওয়ার্কশপে ব্যবহৃত মেশিনগুলোর পারফরম্যান্স টেস্ট ও রক্ষণাবেক্ষণ কার্যক্রম চলমান। নিরাপত্তা নির্দেশনা অনুসরণ করা আবশ্যক।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:09:34'),
(27, 'থার্মাল ইঞ্জিন টেস্টিং বিজ্ঞপ্তি', 'বিভাগের পরীক্ষাগারে থার্মাল ইঞ্জিন সিস্টেমের দক্ষতা যাচাইয়ের জন্য পরীক্ষামূলক অপারেশন চালানো হচ্ছে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:09:47'),
(28, 'বিল্ডিং মেটেরিয়াল পরীক্ষা বিজ্ঞপ্তি', 'সিভিল বিভাগের ল্যাবে নির্মাণ উপকরণের গুণগতমান পরীক্ষার কার্যক্রম চলছে। তথ্যগুলো বিভাগীয় রিপোর্টে সংরক্ষিত হবে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:10:03'),
(29, 'যানবাহন ইঞ্জিন পারফরম্যান্স পরীক্ষা', 'অটোমোবাইল বিভাগের ওয়ার্কশপে ইঞ্জিন পারফরম্যান্স ও জ্বালানি দক্ষতা যাচাইয়ের উদ্দেশ্যে পরীক্ষামূলক কার্যক্রম সম্পন্ন হচ্ছে।', 'published', '', 2, 'mamun Rashid', 'normal', '2025-10-30', NULL, NULL, 'admin', NULL, '2025-10-30 15:10:17');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `upload_id` varchar(50) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `total_marks` int(11) DEFAULT 100,
  `grade` varchar(2) NOT NULL,
  `semester` int(11) NOT NULL,
  `exam_type` enum('Final','Midterm','Assignment','Quiz') DEFAULT 'Final',
  `exam_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `exam_id`, `upload_id`, `student_id`, `subject_id`, `marks_obtained`, `percentage`, `total_marks`, `grade`, `semester`, `exam_type`, `exam_date`, `created_at`) VALUES
(3, NULL, NULL, 18, 6, 48.00, 80.00, 60, 'A+', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(4, NULL, NULL, 19, 6, 35.00, 58.33, 60, 'B', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(5, NULL, NULL, 20, 6, 52.00, 86.67, 60, 'A+', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(6, NULL, NULL, 21, 6, 41.00, 68.33, 60, 'A-', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(7, NULL, NULL, 22, 6, 27.00, 45.00, 60, 'C', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(8, NULL, NULL, 23, 6, 56.00, 93.33, 60, 'A+', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(9, NULL, NULL, 24, 6, 39.00, 65.00, 60, 'A-', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(10, NULL, NULL, 25, 6, 44.00, 73.33, 60, 'A', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(11, NULL, NULL, 26, 6, 31.00, 51.67, 60, 'B', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(12, NULL, NULL, 27, 6, 59.00, 98.33, 60, 'A+', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(13, NULL, NULL, 28, 6, 46.00, 76.67, 60, 'A', 6, 'Final', '2025-10-14', '2025-10-14 14:52:20'),
(14, NULL, NULL, 18, 7, 55.00, 61.11, 90, 'A-', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(15, NULL, NULL, 19, 7, 60.00, 66.67, 90, 'A-', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(16, NULL, NULL, 20, 7, 43.00, 47.78, 90, 'C', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(17, NULL, NULL, 21, 7, 75.00, 83.33, 90, 'A+', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(18, NULL, NULL, 22, 7, 65.00, 72.22, 90, 'A', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(19, NULL, NULL, 23, 7, 49.00, 54.44, 90, 'B', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(20, NULL, NULL, 24, 7, 50.00, 55.56, 90, 'B', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(21, NULL, NULL, 25, 7, 60.00, 66.67, 90, 'A-', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(22, NULL, NULL, 26, 7, 45.00, 50.00, 90, 'B', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(23, NULL, NULL, 27, 7, 76.00, 84.44, 90, 'A+', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(24, NULL, NULL, 28, 7, 64.00, 71.11, 90, 'A', 6, 'Final', '2025-10-21', '2025-10-21 13:30:57'),
(25, NULL, NULL, 18, 8, 44.00, 73.33, 60, 'A', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(26, NULL, NULL, 19, 8, 40.00, 66.67, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(27, NULL, NULL, 20, 8, 38.00, 63.33, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(28, NULL, NULL, 21, 8, 39.00, 65.00, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(29, NULL, NULL, 22, 8, 40.00, 66.67, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(30, NULL, NULL, 23, 8, 39.00, 65.00, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(31, NULL, NULL, 24, 8, 31.00, 51.67, 60, 'B', 6, 'Final', '2025-10-22', '2025-10-23 02:37:38'),
(32, NULL, NULL, 25, 8, 37.00, 61.67, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:39'),
(33, NULL, NULL, 26, 8, 39.00, 65.00, 60, 'A-', 6, 'Final', '2025-10-22', '2025-10-23 02:37:39'),
(34, NULL, NULL, 27, 8, 45.00, 75.00, 60, 'A', 6, 'Final', '2025-10-22', '2025-10-23 02:37:39'),
(35, NULL, NULL, 28, 8, 46.00, 76.67, 60, 'A', 6, 'Final', '2025-10-22', '2025-10-23 02:37:39'),
(58, 1, 'upload_690253f1946e82.77650453', 18, 8, 44.00, NULL, 60, 'A', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(59, 1, 'upload_690253f1946e82.77650453', 19, 8, 40.00, NULL, 60, 'B+', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(60, 1, 'upload_690253f1946e82.77650453', 20, 8, 38.00, NULL, 60, 'A-', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(61, 1, 'upload_690253f1946e82.77650453', 21, 8, 39.00, NULL, 60, 'B+', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(62, 1, 'upload_690253f1946e82.77650453', 22, 8, 40.00, NULL, 60, 'B+', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(63, 1, 'upload_690253f1946e82.77650453', 23, 8, 39.00, NULL, 60, 'B+', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(64, 1, 'upload_690253f1946e82.77650453', 24, 8, 31.00, NULL, 60, 'B', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(65, 1, 'upload_690253f1946e82.77650453', 25, 8, 37.00, NULL, 60, 'A-', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(66, 1, 'upload_690253f1946e82.77650453', 26, 8, 39.00, NULL, 60, 'B+', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(67, 1, 'upload_690253f1946e82.77650453', 27, 8, 45.00, NULL, 60, 'A', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(68, 1, 'upload_690253f1946e82.77650453', 28, 8, 46.00, NULL, 60, 'A', 6, 'Final', NULL, '2025-10-29 17:50:41'),
(69, 2, 'upload_69025408e1cb00.15147294', 18, 6, 28.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(70, 2, 'upload_69025408e1cb00.15147294', 19, 6, 25.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(71, 2, 'upload_69025408e1cb00.15147294', 20, 6, 27.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(72, 2, 'upload_69025408e1cb00.15147294', 21, 6, 22.00, NULL, 30, 'A', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(73, 2, 'upload_69025408e1cb00.15147294', 22, 6, 29.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(74, 2, 'upload_69025408e1cb00.15147294', 23, 6, 24.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(75, 2, 'upload_69025408e1cb00.15147294', 24, 6, 26.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(76, 2, 'upload_69025408e1cb00.15147294', 25, 6, 23.00, NULL, 30, 'A', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(77, 2, 'upload_69025408e1cb00.15147294', 26, 6, 24.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(78, 2, 'upload_69025408e1cb00.15147294', 27, 6, 27.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04'),
(79, 2, 'upload_69025408e1cb00.15147294', 28, 6, 26.00, NULL, 30, 'A+', 6, 'Final', NULL, '2025-10-29 17:51:04');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `index_no` varchar(20) NOT NULL,
  `board_roll` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `roll_no` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `status` enum('active','graduated','dropped') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `index_no`, `board_roll`, `email`, `phone`, `photo`, `roll_no`, `student_name`, `department_id`, `batch_id`, `semester`, `status`, `created_at`, `updated_at`) VALUES
(18, 'CST-22-M1917', '759278', NULL, NULL, NULL, '', 'Patia Dio', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-20 14:07:41'),
(19, 'CST-22-M1920', '759279', NULL, NULL, NULL, '', 'Md.Abdur Rahman', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(20, 'CST-22-M1925', '759283', NULL, NULL, NULL, '', 'Saima Akter', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(21, 'CST-22-M1910', '759284', NULL, NULL, NULL, '', 'Tamanna Naznin', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(22, 'CST-22-M1913', '759285', NULL, NULL, NULL, '', 'Sonjit Chiran', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(23, 'CST-22-M1911', '759287', NULL, NULL, NULL, '', 'Md.Mahamudur Rahman', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(24, 'CST-22-M1914', '759291', NULL, NULL, NULL, '', 'Shanto Chandra Dey', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(25, 'CST-22-M1921', '759293', NULL, NULL, NULL, '', 'Mong Chaing Sing Marma', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(26, 'CST-22-M1902', '759296', NULL, NULL, NULL, '', 'Benedicta Kha Kha', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(27, 'CST-22-M1916', '759302', NULL, NULL, NULL, '', 'Kazi Muntasir Hossain', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(28, 'CST-22-M1901', '759305', NULL, NULL, NULL, '', 'Jorge Martin D Silva', 1, 10, 6, 'active', '2025-10-14 14:39:00', '2025-10-14 14:39:00'),
(139, 'CsT-23-M2045', '853907', NULL, NULL, NULL, '', 'Konika Tripura (CR)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(140, 'CsT-23-M2040', '853908', NULL, NULL, NULL, '', 'Ripa  Marak (MR)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(141, 'CsT-23-M2044', '853909', NULL, NULL, NULL, '', 'Catherina Konok D\' Rozario (BR)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(142, 'CsT-23-M2039', '853911', NULL, NULL, NULL, '', 'Christopher Mac Dcosta', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(143, 'CsT-23-M2032', '853913', NULL, NULL, NULL, '', 'Shayon Kunda', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(144, 'CsT-23-M2024', '853915', NULL, NULL, NULL, '', 'Md. Asiqur Rahman', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(145, 'CsT-23-M2008', '853917', NULL, NULL, NULL, '', 'Md. Ibrahim Khalil', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(146, 'CsT-23-M2023', '853918', NULL, NULL, NULL, '', 'Md Ashrafuzzaman Rafi', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(147, 'CsT-23-M2017', '853921', NULL, NULL, NULL, '', 'Mohammed Anaful Haque', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(148, 'CsT-23-M2047', '853923', NULL, NULL, NULL, '', 'Akash Tudu (RR)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(149, 'CsT-23-M2025', '853924', NULL, NULL, NULL, '', 'A. K. M. Abtahi Labib', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(150, 'CsT-23-M2053', '853925', NULL, NULL, NULL, '', 'Naim Al Umor', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(151, 'CsT-23-M2041', '853926', NULL, NULL, NULL, '', 'Claudio Hasda (Dr)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(152, 'CsT-23-M2029', '853928', NULL, NULL, NULL, '', 'Md. Adibur Rahman', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(153, 'CsT-23-M2050', '853931', NULL, NULL, NULL, '', 'Ashraful Islam Abir', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(154, 'CsT-23-M2018', '853932', NULL, NULL, NULL, '', 'Tanjim Billah', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(155, 'CsT-23-M2027', '853934', NULL, NULL, NULL, '', 'Md. Shafin Islam', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(156, 'CsT-23-M2051', '853935', NULL, NULL, NULL, '', 'Md. Hasibur Rahman Yead', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(157, 'CsT-23-M2030', '853936', NULL, NULL, NULL, '', 'Syed Walid', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(158, 'CsT-23-M2019', '853937', NULL, NULL, NULL, '', 'Md. Zakir Hossen', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(159, 'CsT-23-M2006', '853940', NULL, NULL, NULL, '', 'Rejwan Ahmed', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(160, 'CsT-23-M2048', '853941', NULL, NULL, NULL, '', 'Erial Lalhim Pui  Bawm', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(161, 'CsT-23-M2009', '853943', NULL, NULL, NULL, '', 'Akash Biswas', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(162, 'CsT-23-M2012', '853944', NULL, NULL, NULL, '', 'Fayzul Baki Akanda', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(163, 'CsT-23-M2020', '853946', NULL, NULL, NULL, '', 'Anurag Islam', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(164, 'CsT-23-M2031', '853947', NULL, NULL, NULL, '', 'Pritilata Tripura', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(165, 'CsT-23-M2026', '853948', NULL, NULL, NULL, '', 'Shuvo Howlader', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(166, 'CsT-23-M2043', '853949', NULL, NULL, NULL, '', 'Babu Pohshwet (SR)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(167, 'CsT-23-M2010', '853951', NULL, NULL, NULL, '', 'Mobinul Hasan Siam', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(168, 'CsT-23-M2034', '853952', NULL, NULL, NULL, '', 'Arman Hossan Pial', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(169, 'CsT-23-M2042', '853953', NULL, NULL, NULL, '', 'Dipika Soren (Dr)', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(170, 'CsT-23-M2049', '853954', NULL, NULL, NULL, '', 'Aftab Uddin', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(171, 'CsT-23-M2001', '853955', NULL, NULL, NULL, '', 'Jui Halder', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(172, 'CsT-22-M1905', '7592277', NULL, NULL, NULL, '', 'Sourov Kumar Sarkar', 1, 13, 4, 'active', '2025-10-27 17:20:02', '2025-10-27 17:20:02'),
(173, 'CT-24-M2102', '242542', NULL, NULL, NULL, '', 'Afridi Ahmed Shahed', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(174, 'CT-24-M2103', '242534', NULL, NULL, NULL, '', 'Md. Salman Farshi', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(175, 'CT-24-M2104', '242554', NULL, NULL, NULL, '', 'Md. Ifty Dewan', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(176, 'CT-24-M2105', '242564', NULL, NULL, NULL, '', 'Shirajul Islam Shuvo', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(177, 'CT-24-M2106', '242528', NULL, NULL, NULL, '', 'Al Faysal', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(178, 'CT-24-M2107', '242558', NULL, NULL, NULL, '', 'Avishek Baruri', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(179, 'CT-24-M2108', '242543', NULL, NULL, NULL, '', 'Shahariar Shuvo', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(180, 'CT-24-M2109', '242549', NULL, NULL, NULL, '', 'Md. Tahsin Ahnaf Anondo', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(181, 'CT-24-M2110', '242531', NULL, NULL, NULL, '', 'Shahadat Hosen Shaon', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(182, 'CT-24-M2112', '242525', NULL, NULL, NULL, '', 'Shrabon Kumar Pk.', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(183, 'CT-24-M2113', '242529', NULL, NULL, NULL, '', 'Ringo Bala', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(184, 'CT-24-M2114', '242539', NULL, NULL, NULL, '', 'Nokthai Tripura', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(185, 'CT-24-M2117', '242567', NULL, NULL, NULL, '', 'Md. Tasnim Mahi', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(186, 'CT-24-M2118', '242541', NULL, NULL, NULL, '', 'Md. Rezown Karim Alif', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(187, 'CT-24-M2119', '242556', NULL, NULL, NULL, '', 'Masudur Rahman Sarkar', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(188, 'CT-24-M2121', '242553', NULL, NULL, NULL, '', 'M Abdullah Nur', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(189, 'CT-24-M2122', '242526', NULL, NULL, NULL, '', 'Asfik Alam Ifty', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(190, 'CT-24-M2123', '242563', NULL, NULL, NULL, '', 'Md. Labib Khan Joy', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(191, 'CT-24-M2124', '242537', NULL, NULL, NULL, '', 'Subas Besra', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(192, 'CT-24-M2125', '270841', NULL, NULL, NULL, '', 'Md. Rafi Hossain', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(193, 'CT-24-M2127', '242552', NULL, NULL, NULL, '', 'Md. Omor Ali Shuvo', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(194, 'CT-24-M2129', '242545', NULL, NULL, NULL, '', 'Hosen Imam Forkan', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(195, 'CT-24-M2131', '242533', NULL, NULL, NULL, '', 'Joy Mojumdar Fahim', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(196, 'CT-24-M2132', '242547', NULL, NULL, NULL, '', 'Syed Mobasshir Ibrahim', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(197, 'CT-24-M2133', '242546', NULL, NULL, NULL, '', 'Md. Shahriar Sifat', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(198, 'CT-24-M2135', '242561', NULL, NULL, NULL, '', 'Md. Ashraful Islam', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(199, 'CT-24-M2136', '242562', NULL, NULL, NULL, '', 'Md. Sourov', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(200, 'CT-24-M2137', '270840', NULL, NULL, NULL, '', 'Md Abu Talib', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(201, 'CT-24-M2140', '242544', NULL, NULL, NULL, '', 'Md. Tahsin Islam Himel', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(202, 'CT-24-M2142', '242566', NULL, NULL, NULL, '', 'Refath Ahoshan', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(203, 'CT-24-M2143', '242559', NULL, NULL, NULL, '', 'Sabikun Rahim', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(204, 'CT-24-M2144', '242550', NULL, NULL, NULL, '', 'Sechi Thengna Bela', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(205, 'CT-24-M2145', '242560', NULL, NULL, NULL, '', 'Eliza Gloria Costa (DR)', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(206, 'CT-24-M2146', '242536', NULL, NULL, NULL, '', 'Rittika Jimima Halder (BR)', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(207, 'CT-24-M2147', '242535', NULL, NULL, NULL, '', 'Nilaxi Tariang (SR)', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(208, 'CT-24-M2148', '242551', NULL, NULL, NULL, '', 'Sumona Kisku (RR)', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(209, 'CT-24-M2149', '242548', NULL, NULL, NULL, '', 'Johikim Patowary (KR)', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17'),
(210, 'CT-24-M2150', '242527', NULL, NULL, NULL, '', 'Joya Toppo', 6, 12, 2, 'active', '2025-10-29 17:56:17', '2025-10-29 17:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `total_marks` int(11) NOT NULL DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `department_id`, `semester`, `total_marks`, `created_at`) VALUES
(6, '28562', 'Computer networking', 1, 6, 90, '2025-10-14 14:52:20'),
(7, '28542', 'Data structure and algorithm', 1, 6, 90, '2025-10-21 13:30:57'),
(8, '28565', 'Surveillance SecuritybSystem', 1, 6, 60, '2025-10-23 02:37:38'),
(11, '28561', 'Database Management System', 1, 6, 90, '2025-10-29 13:18:23'),
(12, '25711', 'Bangla-I', 1, 1, 60, '2025-10-30 16:02:42'),
(13, '25712', 'English-I', 1, 1, 60, '2025-10-30 16:03:45'),
(14, '25911', 'Mathematics -I', 1, 1, 90, '2025-10-30 16:04:15'),
(15, 'PHYSICS -I', '25912', 1, 1, 90, '2025-10-30 16:04:38'),
(16, '25912', 'Physics -I', 3, 1, 90, '2025-10-30 16:04:55');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `first_name`, `last_name`, `email`, `password`, `profile_picture`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Joy', 'Devnath', 'joy@gmail.com', '$2y$10$uQWb0UIpyNdWSZI1Z/vcMOyPUD5YUsJoh9IROVFDsgGIQbmeUStsy', NULL, 'active', '2025-10-12 18:19:13', '2025-10-20 16:09:12'),
(2, 'mamun', 'Rashid', 'mamun@gmail.com', '$2y$10$witNnW1..unxJ.oPL3d1NO3lUpVy1UJjSxGs6/bWSAW0R3S8hsaYS', 'teacher_68ebfaee46b41_1760295662.JPG', 'active', '2025-10-12 19:01:02', '2025-10-30 15:44:38');

-- --------------------------------------------------------

--
-- Table structure for table `upload_history`
--

CREATE TABLE `upload_history` (
  `id` varchar(50) NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `semester` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `records_count` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upload_history`
--

INSERT INTO `upload_history` (`id`, `exam_type`, `semester`, `department_id`, `subject_id`, `records_count`, `status`, `created_at`, `updated_at`) VALUES
('upload_68fe402449a7c0.03796578', 'Midterm', 6, 1, NULL, 0, 'active', '2025-10-26 15:37:08', '2025-10-26 15:37:08'),
('upload_68fe406ef290e8.72027998', 'ClassTest', 6, 1, NULL, 11, 'undone', '2025-10-26 15:38:23', '2025-10-26 15:39:32'),
('upload_68fe427ccaa8e5.81718027', 'Midterm', 6, 1, NULL, 0, 'active', '2025-10-26 15:47:08', '2025-10-26 15:47:08'),
('upload_68fe42a282f011.33597244', 'ClassTest', 6, 1, NULL, 11, 'active', '2025-10-26 15:47:46', '2025-10-26 15:47:46'),
('upload_68ffaf3ae264a6.28034727', 'Assignment', 4, 1, NULL, 0, 'active', '2025-10-27 17:43:22', '2025-10-27 17:43:22'),
('upload_69024a86e81342.63256808', 'Midterm', 6, 1, NULL, 0, 'active', '2025-10-29 17:10:30', '2025-10-29 17:10:30'),
('upload_69024b2adad557.77837809', 'Midterm', 6, 1, NULL, 0, 'active', '2025-10-29 17:13:14', '2025-10-29 17:13:14'),
('upload_69024b4385ca84.20870811', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:13:39', '2025-10-29 17:13:39'),
('upload_69024bceba4143.18320335', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:15:58', '2025-10-29 17:15:58'),
('upload_69024cc814c102.19152732', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:20:08', '2025-10-29 17:20:08'),
('upload_69024cdd439c05.16417607', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:20:29', '2025-10-29 17:20:29'),
('upload_69024d5b9f1222.85879870', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:22:35', '2025-10-29 17:22:35'),
('upload_69024d8b55c6e1.63003246', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:23:23', '2025-10-29 17:23:23'),
('upload_69024e054e5e74.63404486', 'ClassTest', 6, 1, NULL, 0, 'active', '2025-10-29 17:25:25', '2025-10-29 17:25:25'),
('upload_69024fe4906148.69733026', 'Midterm', 6, 1, NULL, 0, 'active', '2025-10-29 17:33:24', '2025-10-29 17:33:24'),
('upload_690250940559e5.07496559', 'Final', 6, 1, NULL, 0, 'active', '2025-10-29 17:36:20', '2025-10-29 17:36:20'),
('upload_690250c4516045.04834758', 'Final', 6, 1, NULL, 0, 'active', '2025-10-29 17:37:08', '2025-10-29 17:37:08'),
('upload_690253f1946e82.77650453', 'Final', 6, 1, NULL, 11, 'active', '2025-10-29 17:50:41', '2025-10-29 17:50:41'),
('upload_69025408e1cb00.15147294', 'ClassTest', 6, 1, NULL, 11, 'active', '2025-10-29 17:51:04', '2025-10-29 17:51:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_type` (`exam_type`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_exam_type_semester` (`exam_type`,`semester`),
  ADD KEY `fk_exams_department` (`department_id`),
  ADD KEY `fk_exams_subject` (`subject_id`);

--
-- Indexes for table `grade_scale`
--
ALTER TABLE `grade_scale`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status_date` (`status`,`publish_date`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_student_subject` (`student_id`,`subject_id`),
  ADD KEY `idx_percentage` (`percentage`),
  ADD KEY `idx_semester_student` (`semester`,`student_id`),
  ADD KEY `idx_exam_id` (`exam_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `index_no` (`index_no`),
  ADD UNIQUE KEY `board_roll` (`board_roll`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_index_no` (`index_no`),
  ADD KEY `idx_board_roll` (`board_roll`),
  ADD KEY `idx_universal_search` (`student_name`,`index_no`,`board_roll`,`roll_no`),
  ADD KEY `idx_student_name` (`student_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_batch_dept` (`batch_id`,`department_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_dept_sem` (`department_id`,`semester`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD KEY `status_index` (`status`);

--
-- Indexes for table `upload_history`
--
ALTER TABLE `upload_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `grade_scale`
--
ALTER TABLE `grade_scale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `fk_exams_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exams_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `fk_results_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
