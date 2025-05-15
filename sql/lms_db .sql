-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 08:36 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `description`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 4, 'Class Test 1', 'Solve the following question:\r\n\r\n1. What is the difference between white hat and black hat hackers?\r\n\r\n2. Name the five phases of ethical hacking.\r\n\r\n3. Why is legal permission essential for ethical hacking?\r\n\r\n4. What is the role of reconnaissance in ethical hacking?', '2025-05-15 00:00:00', '2025-05-14 14:53:46', '2025-05-14 14:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_url` varchar(255) NOT NULL,
  `issued_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `course_id`, `certificate_url`, `issued_on`) VALUES
(1, 5, 4, 'certificates/cert_5_4_5-4-B0F435.pdf', '2025-05-15 00:45:08');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `instructor_id`, `title`, `description`, `thumbnail_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Modern Web Development', 'Learn HTML, CSS, JavaScript, and modern frameworks to build stunning websites.', 'images/webdev.jpg', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(2, 2, 'Data Science Bootcamp', 'Master data analysis, visualization, and machine learning with Python.', 'images/datascience.jpg', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(3, 1, 'UI/UX Design Essentials', 'Design beautiful and user-friendly interfaces with Figma and Adobe XD.', 'images/uiux.jpg', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(4, 6, 'Ethical Hacking', 'Learn the art of offensive security to uncover cyber threats and vulnerabilities before the cybercriminals do. Badges you can earn in this course. The digital landscape is evolving at an unprecedented rate and cyber threats lurk around every corner.', 'uploads/course_thumbnails/6824908702e4d.jpg', '2025-05-14 12:45:59', '2025-05-14 12:45:59'),
(5, 7, 'Learn Python', 'this is basic python', 'uploads/course_thumbnails/682586936c81d.jpg', '2025-05-15 06:15:47', '2025-05-15 06:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `completed` tinyint(1) DEFAULT 0,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `progress`, `completed`, `enrolled_at`) VALUES
(1, 1, 1, '80.00', 0, '2025-05-14 06:00:13'),
(2, 2, 2, '100.00', 1, '2025-05-14 06:00:13'),
(3, 1, 2, '60.00', 0, '2025-05-14 06:00:13'),
(4, 2, 1, '100.00', 1, '2025-05-14 06:00:13'),
(5, 5, 4, '100.00', 1, '2025-05-14 13:26:52'),
(6, 5, 1, '0.00', 0, '2025-05-14 13:26:56'),
(8, 5, 5, '0.00', 0, '2025-05-15 06:17:13');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_url` varchar(255) DEFAULT NULL,
  `content_type` enum('video','text') NOT NULL,
  `lesson_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title`, `content_url`, `content_type`, `lesson_order`, `created_at`, `updated_at`) VALUES
(1, 4, 'Introduction to Ethical Hacking', '📘 Learning Objectives\nBy the end of this lesson, you will be able to:\n\nUnderstand what ethical hacking is and why it is important.\n\nDistinguish between different types of hackers.\n\nUnderstand the phases of ethical hacking.\n\nRecognize legal and et', 'text', 1, '2025-05-14 14:15:42', '2025-05-14 15:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `student_id`, `lesson_id`, `completed`, `completed_at`) VALUES
(1, 5, 1, 1, '2025-05-14 15:43:12');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `title`, `description`, `created_at`) VALUES
(1, 4, 'Quiz 1', 'There are 3 questions of 6 marks.', '2025-05-14 15:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_answer` varchar(255) NOT NULL,
  `points` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `quiz_id`, `question`, `options`, `correct_answer`, `points`) VALUES
(1, 1, 'Which of the following best describes a white hat hacker?', '[\"A hacker who breaks into systems to steal information\",\"A hacker who helps organizations improve their security\",\"A hacker who uses pre-built scripts without understanding them\",\"A hacker who hacks to protest against political issues\"]', 'A hacker who helps organizations improve their security', 1),
(2, 1, 'What is the first phase of ethical hacking?', '[\"Gaining Access\",\"Scanning\",\"Reconnaissance\",\"Reporting\"]', 'Reconnaissance', 1),
(3, 1, 'Which of these tools is primarily used for network scanning?', '[\"Wireshark\",\"Burp Suite\",\"Nmap\",\"Metasploit\"]', 'Nmap', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_results`
--

CREATE TABLE `quiz_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `attempt` int(11) NOT NULL DEFAULT 1,
  `score` decimal(5,2) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_results`
--

INSERT INTO `quiz_results` (`id`, `student_id`, `quiz_id`, `attempt`, `score`, `completed_at`) VALUES
(1, 5, 1, 1, '66.67', '2025-05-14 16:34:22');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `review` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `student_id`, `course_id`, `review`, `created_at`) VALUES
(1, 3, 1, 'The course was well-structured and the instructor was very knowledgeable. Highly recommended!', '2025-05-14 10:49:26'),
(2, 4, 2, 'I learned so much and the platform is very user-friendly.', '2025-05-14 10:49:26'),
(3, 3, 2, 'Great introduction to data science, with lots of practical examples.', '2025-05-14 10:49:26'),
(4, 4, 3, 'The design course helped me land my first freelance project!', '2025-05-14 10:49:26'),
(5, 5, 4, 'dkngfajhdoire', '2025-05-15 12:32:15');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `text_content` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `student_id`, `assignment_id`, `file_url`, `text_content`, `submitted_at`, `grade`, `feedback`) VALUES
(1, 5, 1, NULL, '1. What is the difference between white hat and black hat hackers?\r\nWhite Hat Hackers are ethical hackers who are authorized to test and improve the security of systems. They work legally and with permission from the organization.\r\n\r\nBlack Hat Hackers, on the other hand, are malicious hackers who break into systems illegally for personal gain, such as stealing data or causing harm.\r\n\r\n2. Name the five phases of ethical hacking.\r\nThe five core phases of ethical hacking are:\r\n\r\nReconnaissance (Footprinting) – Gathering preliminary information about the target.\r\n\r\nScanning – Identifying live hosts, open ports, and vulnerabilities.\r\n\r\nGaining Access – Exploiting vulnerabilities to enter the system.\r\n\r\nMaintaining Access – Staying inside the system (usually not done in ethical tests unless required).\r\n\r\nClearing Tracks and Reporting – Ethical hackers don’t erase evidence; instead, they report findings.\r\n\r\nIn some models, the Reporting phase is considered the sixth and final phase.\r\n\r\n3. Why is legal permission essential for ethical hacking?\r\nLegal permission is essential because:\r\n\r\nUnauthorized access is illegal, even if done with good intent.\r\n\r\nIt protects the hacker and the organization from legal consequences.\r\n\r\nEthical hacking should always follow contracts and non-disclosure agreements (NDAs) to ensure confidentiality and legality.\r\n\r\nWithout permission, even testing could be treated as cybercrime.\r\n\r\n4. What is the role of reconnaissance in ethical hacking?\r\nReconnaissance is the first and foundational phase where the hacker collects as much information as possible about the target.\r\n\r\nThis includes IP addresses, domain names, system details, and even employee information.\r\n\r\nIt can be passive (e.g., public websites, WHOIS) or active (e.g., ping sweeps).\r\n\r\nThe goal is to understand the target’s infrastructure to plan further penetration efforts effectively.', '2025-05-14 16:15:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('student','instructor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `profile_photo`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Tanpia Tasnim', 'tanpia@lms.com', '$2y$10$abcdefghijklmnopqrstuv', 'images/tanpia.jpg', 'instructor', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(2, 'Feroza Naznin', 'feroza@lms.com', '$2y$10$abcdefghijklmnopqrstuv', 'images/feroza.jpg', 'instructor', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(3, 'Md. Robiul Islam', 'robiul@student.com', '$2y$10$abcdefghijklmnopqrstuv', 'images/robiul.jpg', 'student', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(4, 'Ashrafun Nahar Arifa', 'arifa@student.com', '$2y$10$abcdefghijklmnopqrstuv', 'images/arifa.jpg', 'student', '2025-05-14 04:49:26', '2025-05-14 04:49:26'),
(5, 'Md. Robiul Islam', 'md.robiulislam.qcsc@gmail.com', '$2y$10$n3F4TGrJlF50p9EHK6AdVezvH5SeS9X/wlNlM3X.n.4EmsPM3hb92', 'uploads/profile_photos/profile_5_1747224159.jpg', 'student', '2025-05-14 07:37:24', '2025-05-14 12:02:39'),
(6, 'Ashrafun Nahar Arifa', 'ashrafunnahararifa@gmail.com', '$2y$10$lN/oZ2UnISL3N9qc5WtD6OpeHbZbqrr3/fP2q5VH061nnkc6t.SAe', 'uploads/profile_photos/profile_6_1747228236.jpg', 'instructor', '2025-05-14 12:27:03', '2025-05-14 13:10:36'),
(7, 'robi', 'robi@gmail.com', '$2y$10$X9MpAOVyE1Nx8W8B5H.3OOcDihNNxuFrAmStZRHxeQn9xnVqZJd.2', NULL, 'instructor', '2025-05-15 06:13:50', '2025-05-15 06:13:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lesson_progress` (`student_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quiz_results`
--
ALTER TABLE `quiz_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_results`
--
ALTER TABLE `quiz_results`
  ADD CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
