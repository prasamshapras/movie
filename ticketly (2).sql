-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 08:33 AM
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
-- Database: `ticketly`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `password`, `name`, `role`) VALUES
(1, 'admin@ticketly.com', '$2y$10$XOkx0g2BZWVK81rxuV8DKu0bmYjHtZm52MtzD3OedXVhzC4sDWdcu', 'System Admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `showtime_id` int(11) NOT NULL,
  `seat_label` varchar(10) NOT NULL,
  `amount` decimal(8,2) DEFAULT NULL,
  `status` enum('Confirmed','Cancelled','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` varchar(20) DEFAULT 'Unpaid',
  `payment_ref` varchar(100) DEFAULT NULL,
  `movie_id` int(11) DEFAULT NULL,
  `transaction_uuid` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `showtime_id`, `seat_label`, `amount`, `status`, `created_at`, `payment_status`, `payment_ref`, `movie_id`, `transaction_uuid`) VALUES
(50, 19, 37, 'A1', 200.00, 'Cancelled', '2026-05-26 11:11:03', 'failed', NULL, NULL, '260526-3d30871191-c818ef'),
(51, 19, 36, 'B7', 200.00, 'Confirmed', '2026-05-26 11:29:38', 'paid', 'MANUAL-51', NULL, '260526-ef1846abc5-236b5c'),
(52, 20, 49, 'A2', 300.00, 'Confirmed', '2026-05-26 11:33:56', 'paid', 'LOCALTEST1779795236', 25, NULL),
(53, 20, 34, 'B3', 200.00, 'Cancelled', '2026-05-26 11:34:47', 'failed', NULL, 25, NULL),
(54, 20, 34, 'A2', 200.00, 'Confirmed', '2026-05-26 11:35:35', 'paid', 'LOCALTEST1779795335', 25, NULL),
(55, 20, 34, 'A3', 200.00, 'Confirmed', '2026-05-26 11:35:35', 'paid', 'MANUAL-55', 25, NULL),
(56, 20, 34, 'A4', 200.00, 'Confirmed', '2026-05-26 11:35:35', 'paid', 'MANUAL-56', 25, NULL),
(57, 19, 34, 'A5', 200.00, 'Cancelled', '2026-05-26 11:46:35', 'failed', NULL, 25, NULL),
(58, 19, 34, 'A5', 200.00, 'Cancelled', '2026-05-26 11:46:51', 'failed', NULL, 25, NULL),
(59, 19, 37, 'A1', 200.00, 'Cancelled', '2026-05-26 12:01:23', 'failed', NULL, 28, NULL),
(60, 19, 47, 'A1', 300.00, 'Confirmed', '2026-05-26 12:02:17', 'paid', 'LOCALTEST1779796937', 21, NULL),
(61, 19, 47, 'C4', 300.00, 'Confirmed', '2026-05-26 12:02:17', 'paid', 'LOCALTEST1779796937', 21, NULL),
(62, 19, 47, 'A2', 300.00, 'Cancelled', '2026-05-26 12:06:05', 'failed', NULL, 21, NULL),
(63, 20, 47, 'A3', 300.00, 'Cancelled', '2026-05-26 12:09:52', 'failed', NULL, 21, NULL),
(64, 20, 47, 'A2', 300.00, 'Cancelled', '2026-05-26 12:17:50', 'failed', NULL, 21, NULL),
(65, 20, 30, 'A1', 200.00, 'Cancelled', '2026-05-26 12:21:03', 'failed', NULL, 17, NULL),
(66, 20, 30, 'A1', 200.00, 'Cancelled', '2026-05-26 12:28:07', 'failed', NULL, 17, NULL),
(67, 20, 30, 'A1', 200.00, 'Cancelled', '2026-05-26 12:29:18', 'failed', NULL, 17, NULL),
(68, 19, 30, 'A1', 200.00, 'Confirmed', '2026-05-26 13:11:28', 'paid', 'LOCALTEST1779801088', 17, NULL),
(69, 20, 30, 'A2', 200.00, 'Cancelled', '2026-05-26 13:11:59', 'failed', NULL, 17, NULL),
(70, 20, 30, 'A2', 200.00, 'Cancelled', '2026-05-26 13:12:19', 'failed', NULL, 17, NULL),
(71, 19, 37, 'A2', 200.00, 'Cancelled', '2026-05-26 13:14:29', 'failed', NULL, 28, NULL),
(72, 19, 36, 'A1', 200.00, 'Cancelled', '2026-05-26 13:15:16', 'failed', NULL, 27, NULL),
(73, 19, 36, 'A1', 200.00, 'Cancelled', '2026-05-26 13:17:42', 'failed', NULL, 27, NULL),
(74, 21, 30, 'B1', 200.00, 'Cancelled', '2026-05-26 13:26:54', 'failed', NULL, 17, NULL),
(75, 19, 34, 'A1', 200.00, 'Cancelled', '2026-05-26 13:39:16', 'failed', NULL, 25, NULL),
(76, 19, 33, 'A2', 200.00, 'Cancelled', '2026-05-26 13:43:46', 'failed', NULL, 22, NULL),
(77, 21, 37, 'A1', 200.00, 'Cancelled', '2026-05-26 13:48:02', 'failed', NULL, 28, NULL),
(78, 19, 33, 'D1', 200.00, 'Pending', '2026-05-26 13:56:30', 'Unpaid', NULL, 22, NULL),
(79, 21, 32, 'A1', 200.00, 'Confirmed', '2026-05-26 13:57:37', 'paid', 'LOCALTEST1779803857', 21, NULL),
(81, 21, 30, 'B1', 200.00, 'Confirmed', '2026-05-26 13:58:33', 'paid', 'LOCALTEST1779803913', 17, NULL),
(82, 21, 37, 'A2', 200.00, 'Cancelled', '2026-05-26 14:04:16', 'failed', NULL, 28, NULL),
(83, 21, 37, 'A3', 200.00, 'Confirmed', '2026-05-26 14:04:28', 'paid', 'LOCALTEST1779804268', 28, NULL),
(84, 21, 37, 'D9', 200.00, 'Cancelled', '2026-05-26 14:05:20', 'failed', NULL, 28, NULL),
(85, 21, 37, 'D10', 200.00, 'Cancelled', '2026-05-26 14:05:20', 'failed', NULL, 28, NULL),
(86, 21, 37, 'B1', 200.00, 'Confirmed', '2026-05-26 14:06:05', 'paid', 'LOCALTEST1779804365', 28, NULL),
(87, 21, 37, 'B2', 200.00, 'Cancelled', '2026-05-26 14:06:05', 'Cancelled', 'LOCALTEST1779804365', 28, NULL),
(88, 21, 37, 'A1', 200.00, 'Cancelled', '2026-05-26 14:09:38', 'paid', 'LOCALTEST1779804578', 28, NULL),
(89, 21, 37, 'A2', 200.00, 'Cancelled', '2026-05-26 14:13:23', 'failed', NULL, 28, NULL),
(90, 21, 37, 'A4', 200.00, 'Cancelled', '2026-05-26 14:13:33', 'paid', 'LOCALTEST1779804813', 28, NULL),
(91, 19, 37, 'A2', 200.00, 'Cancelled', '2026-05-26 15:34:03', 'failed', NULL, 28, NULL),
(92, 19, 37, 'A5', 200.00, 'Confirmed', '2026-05-26 15:34:11', 'paid', 'LOCALTEST1779809651', 28, NULL),
(93, 19, 46, 'C5', 300.00, 'Cancelled', '2026-06-07 14:34:14', 'Cancelled', NULL, 20, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `email`, `phone`, `gender`, `age`, `password`, `created_at`, `role`) VALUES
(2, 'Sandhya', 'sandhya@gmail.com', '0000000', NULL, NULL, '$2y$10$05jx5FANX/YGktCmXQY34.eDWpsoeNxOYfFOORspU3XL6J2b9V.HW', '2025-08-12 17:11:35', NULL),
(3, 'nayan chor', 'nayandhaka@gmail.com', '9812345678', NULL, NULL, '$2y$10$mMCtzmGIYzGBRcAYKwh6SOc7Dhbrtu1gvHC2MhvbArtSL4eZOAYpC', '2025-08-19 11:34:34', NULL),
(4, 'Bishal', 'bishal123@gmail.com', '1234567890', NULL, NULL, '$2y$10$kIl2zI44IZcnljh0fZkWBu0/6lQJYLJi/Eh8mJJCFaXTLylxTzlga', '2025-08-25 01:57:29', NULL),
(5, 'test', 'test12@gmail.com', '9808837046', NULL, NULL, '$2y$10$IvwZa0ns/9bfeW7Ezb7G2eIIf2J5ZNUNQ8sOEAjqzgGs2ctdzSzqq', '2025-08-31 02:26:38', 'normal'),
(6, 'hari', 'hari1@gmail.com', '9812345678', NULL, NULL, '$2y$10$bAeectumf6KDKSQrXdU0N.hJQAoiq2vpjG5tAn1LlAGz1B2l9W87G', '2025-09-02 01:10:07', 'normal'),
(7, 'test', 'test123@gmail.com', '9812345678', NULL, NULL, '$2y$10$dosqM/aoIyVP/7JLcKCv6ueTUl8R03w6HfFUdQYcL/qSsDdx19GAm', '2025-09-05 06:54:00', 'normal'),
(8, 'Shyam', 'shyam@gmail.com', '9812345678', NULL, NULL, '$2y$10$Ojl23Qi3yVHw3He.KSemqOIJk6y1tal61kgpmfo5FeW3b/M40tECi', '2025-09-05 09:50:31', 'normal'),
(9, 'devi Sapkota', 'devi123@gmail.com', '9812345678', NULL, NULL, '$2y$10$kLQP3E8PUNn5s9fpEsdsXeitfyZJcLUcEH5XKtsfJ4JoQeDUjk74S', '2025-09-07 08:42:29', 'normal'),
(10, 'Ram', 'ram1234@gmail.com', '9812345678', NULL, NULL, '$2y$10$FDYLuo3vwFWEQ7sBQP4wWOtPY4AwmFqPnuSvvvM/1VAzjggIxv/2K', '2025-09-07 09:34:17', 'admin'),
(11, 'Paban', 'pabankandel12@gmail.com', '9812345678', NULL, NULL, '$2y$10$eZO1gMwF16qB/32XnMBZpOGL4dIQYPZhSsjdacMUamYHn1j5Catgi', '2025-09-07 10:00:10', 'normal'),
(12, 'User', 'user123@gmail.com', '9812345678', NULL, NULL, '$2y$10$BoVDeZ5fbVwpHpxZUgtcDOhUG8fjuEODnkQASVdge9cYM/c16a.1q', '2025-09-09 11:53:21', 'normal'),
(13, 'Suman', 'suman1234@gmail.com', '9808837047', NULL, NULL, '$2y$10$fI9RyWrGEuY6NMl4PVl8uucVyycoWGNZw2O.3mkQnHvowXtptdlh.', '2025-09-09 12:00:04', 'normal'),
(14, 'Aarati', 'aarati123@gmail.com', '9804827716', NULL, NULL, '$2y$10$u/vVK24lk.052QAV5VIcd.ZeSTHoJj.U0wm55BQraOkJDtgI7dYPK', '2025-10-30 06:01:18', 'normal'),
(15, 'Ram', 'ram@gmail.com', '9804827716', NULL, NULL, '$2y$10$TkOdBRUoWc/pMdY8UEH5suweZRlOA6KCjP9DEWz4f4MGaVX5qnd1e', '2026-03-30 15:02:39', 'normal'),
(16, 'Ram Poudel', 'ram12345@gmail.com', '9804827716', NULL, NULL, '$2y$10$phD/jIsgi7fW9mpmkUePDewG4WhFg4wsv658ElqvYaZkPkdf/1Nja', '2026-04-04 08:59:01', 'normal'),
(17, 'Prasamsha Pokharel', 'jaa.vandina.hai@gmail.com', '9844445561', NULL, NULL, '$2y$10$4/CFQE9tqdeWWlg4ZlZHNuk/RyXnCo.vINqIhPK5w/g.H5qJW3EJ6', '2026-04-28 07:43:24', 'normal'),
(18, 'Gita', 'gita@gmail.com', '9804827711', NULL, NULL, '$2y$10$WFpgXJ9riy8B2mD3jjywkOtiWK0g0riFG9h61.kp7KHdZkyEpBMsG', '2026-05-02 13:20:33', 'normal'),
(19, 'sita', 'sita1234@gmail.com', '9800000000', NULL, NULL, '$2y$10$BNd17IbZff7sGiD6CNV2b.8FDYVW9hVep9HFC1oia98L40FQHNVEe', '2026-05-25 05:48:54', 'normal'),
(20, 'Maya', 'maya1234@gmail.com', '9804827777', 'Female', 16, '$2y$10$ErIYqXwosNCI/zkRiUxGJO1L8fYRORC6otKNtTAhiIhCoZ2PP2zBq', '2026-05-25 06:58:08', 'normal'),
(21, 'dhya', 'dhya1234@gmail.com', '9844445562', 'Female', 25, '$2y$10$cw31q8TnAOwxnQ9mUGa4IeaOegNXNoP1z4f3NyDxamD.892SilqEa', '2026-05-26 13:25:34', 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `movie_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bookings_count` int(11) DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'Now Showing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`movie_id`, `title`, `description`, `genre`, `language`, `duration`, `poster`, `release_date`, `created_at`, `bookings_count`, `status`) VALUES
(17, 'La la Land', 'La La Land is a 2016 romantic musical film written and directed by Damien Chazelle. It tells the story of Mia (Emma Stone), an aspiring actress, and Sebastian (Ryan Gosling), a dedicated jazz pianist, as they pursue their dreams in Los Angeles. The film explores their blossoming love affair, the challenges of balancing their careers, and the ultimate compromises they must make in pursuit of their individual goals.', 'Romance, Drama', 'English', 68, 'assets/uploads/1780845738_1780408143_1755606009_lalaland.jpg', '2026-12-09', '2025-08-19 12:20:09', 0, 'now_showing'),
(20, 'Crimson Shadows', 'A retired detective is forced back into action when a series of brutal murders link to a case he failed to solve years ago. As the clock ticks, he must face his darkest fears before the killer strikes again.', 'Action, Thriller, Mystery', 'English', 120, 'assets/uploads/1780845694_1757168519_Crimson Shadows.jpg', '2026-01-26', '2025-09-06 14:21:59', 0, 'now_showing'),
(21, 'Eternal Horizon', 'In the year 1989, Earth faces a global energy crisis. A team of astronauts is sent on a daring interstellar mission to explore a newly discovered wormhole near Saturn. As they journey beyond the stars, they uncover secrets that could either save humanity or end it forever. Packed with breathtaking visuals, suspense, and emotional depth, Eternal Horizon takes audiences on an unforgettable ride across space and time.', 'Sci-Fi, Adventure, Thriller', 'English', 192, 'assets/uploads/1780845658_1757168944_eternal horizon.jpg', '2026-02-23', '2025-09-06 14:29:04', 0, 'now_showing'),
(22, 'Neon Skies', 'In a futuristic city ruled by corrupt corporations, a skilled hacker discovers a secret AI program capable of rewriting reality itself. With enemies on every side, he must decide whether to use it for freedom or chaos', 'Sci-Fi, Action, Cyberpunk', 'English', 129, 'assets/uploads/1780845637_1757169117_Neon Skies.jpg', '2026-02-02', '2025-09-06 14:31:57', 0, 'now_showing'),
(25, 'Kabir Singh', 'A passionate but self-destructive love story of a surgeon who struggles with obsession and heartbreak.', 'Romance/Drama', 'Hindi', 140, 'assets/uploads/1780845479_1757417609_kabir singh.jpg', '2025-09-21', '2025-09-09 11:33:29', 0, 'now_showing'),
(26, 'A Star is Born', 'A seasoned musician discovers and falls in love with a struggling artist while battling his own demons.', 'Romance/Musical Drama', 'English', 120, 'assets/uploads/1780845331_1757418049_A Star is Born.jpg', '2027-02-03', '2025-09-09 11:40:49', 0, 'now_showing'),
(27, 'Andhadhun', 'A blind pianist becomes entangled in a series of mysterious crimes and dangerous situations.', 'Thriller/Crime', 'Hindi', 170, 'assets/uploads/1780845309_1757418361_andhadhun.jpg', '2026-05-03', '2025-09-09 11:46:01', 0, 'now_showing'),
(28, 'Gone Girl', 'A husband becomes the prime suspect when his wife suddenly goes missing, unveiling dark secrets.', 'Action, Thriller, Mystery', 'English', 130, 'assets/uploads/1780844992_1757418555_Gone Girl.jpg', '2025-03-10', '2025-09-09 11:49:15', 0, 'now_showing'),
(79, 'The Midnight Room', 'A group of friends stay in a hotel room where terrifying events happen every midnight.', 'Horror', 'English', 116, 'assets/uploads/1780850888_download (1).jpg', '2026-06-07', '2026-06-07 16:46:58', 0, 'now_showing'),
(80, 'Shraapit Haveli', 'A family enters an abandoned mansion and discovers a curse that has been waiting for years.', 'Horror', 'Hindi', 119, 'assets/uploads/1780851106_download (3).jpg', '2026-06-07', '2026-06-07 16:46:58', 0, 'Now Showing');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('Success','Failed','Pending') DEFAULT 'Pending',
  `transaction_ref` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `payment_method`, `payment_status`, `transaction_ref`, `created_at`) VALUES
(26, 50, 200.00, 'eSewa', 'Failed', '260526-3d30871191-c818ef', '2026-05-26 11:11:03'),
(27, 51, 200.00, 'eSewa', 'Pending', '260526-ef1846abc5-236b5c', '2026-05-26 11:29:38'),
(28, 52, 300.00, 'eSewa', 'Success', 'LOCALTEST1779795236', '2026-05-26 11:33:56'),
(29, 53, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 11:34:47'),
(30, 54, 600.00, 'eSewa', 'Success', 'LOCALTEST1779795335', '2026-05-26 11:35:35'),
(31, 57, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 11:46:35'),
(32, 58, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 11:46:51'),
(33, 59, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:01:23'),
(34, 60, 600.00, 'eSewa', 'Success', 'LOCALTEST1779796937', '2026-05-26 12:02:17'),
(35, 62, 300.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:06:05'),
(36, 63, 300.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:09:52'),
(37, 64, 300.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:17:50'),
(38, 65, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:21:03'),
(39, 66, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:28:07'),
(40, 67, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 12:29:18'),
(41, 68, 200.00, 'eSewa', 'Success', 'LOCALTEST1779801088', '2026-05-26 13:11:28'),
(42, 69, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:11:59'),
(43, 70, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:12:19'),
(44, 71, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:14:29'),
(45, 72, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:15:16'),
(46, 73, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:17:42'),
(47, 74, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:26:54'),
(48, 75, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:39:16'),
(49, 76, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 13:43:46'),
(50, 77, 200.00, 'eSewa', 'Pending', NULL, '2026-05-26 13:48:02'),
(51, 78, 200.00, 'eSewa', 'Pending', NULL, '2026-05-26 13:56:30'),
(52, 79, 200.00, 'eSewa', 'Success', 'LOCALTEST1779803857', '2026-05-26 13:57:37'),
(54, 81, 200.00, 'eSewa', 'Success', 'LOCALTEST1779803913', '2026-05-26 13:58:33'),
(55, 82, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 14:04:16'),
(56, 83, 200.00, 'eSewa', 'Success', 'LOCALTEST1779804268', '2026-05-26 14:04:28'),
(57, 84, 400.00, 'eSewa', 'Failed', NULL, '2026-05-26 14:05:20'),
(58, 86, 400.00, 'eSewa', 'Success', 'LOCALTEST1779804365', '2026-05-26 14:06:05'),
(59, 88, 200.00, 'eSewa', 'Success', 'LOCALTEST1779804578', '2026-05-26 14:09:38'),
(60, 89, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 14:13:23'),
(61, 90, 200.00, 'eSewa', 'Success', 'LOCALTEST1779804813', '2026-05-26 14:13:33'),
(62, 91, 200.00, 'eSewa', 'Failed', NULL, '2026-05-26 15:34:03'),
(63, 92, 200.00, 'eSewa', 'Success', 'LOCALTEST1779809651', '2026-05-26 15:34:11'),
(64, 93, 300.00, 'eSewa', 'Pending', NULL, '2026-06-07 14:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `seat_id` int(11) NOT NULL,
  `showtime_id` int(11) NOT NULL,
  `seat_label` varchar(10) NOT NULL,
  `status` enum('available','reserved','booked') DEFAULT 'available',
  `reserved_until` datetime DEFAULT NULL,
  `reserved_by_customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`seat_id`, `showtime_id`, `seat_label`, `status`, `reserved_until`, `reserved_by_customer_id`) VALUES
(1361, 30, 'A1', 'booked', NULL, NULL),
(1362, 45, 'A1', 'available', NULL, NULL),
(1363, 31, 'A1', 'available', NULL, NULL),
(1364, 46, 'A1', 'available', NULL, NULL),
(1365, 32, 'A1', 'booked', NULL, NULL),
(1366, 47, 'A1', 'booked', NULL, NULL),
(1367, 33, 'A1', 'available', NULL, NULL),
(1368, 48, 'A1', 'available', NULL, NULL),
(1369, 34, 'A1', 'available', NULL, NULL),
(1370, 49, 'A1', 'available', NULL, NULL),
(1371, 35, 'A1', 'available', NULL, NULL),
(1372, 50, 'A1', 'available', NULL, NULL),
(1373, 36, 'A1', 'available', NULL, NULL),
(1374, 51, 'A1', 'available', NULL, NULL),
(1375, 37, 'A1', 'booked', NULL, NULL),
(1376, 52, 'A1', 'available', NULL, NULL),
(1377, 30, 'A2', 'available', NULL, NULL),
(1378, 45, 'A2', 'available', NULL, NULL),
(1379, 31, 'A2', 'available', NULL, NULL),
(1380, 46, 'A2', 'available', NULL, NULL),
(1381, 32, 'A2', 'available', NULL, NULL),
(1382, 47, 'A2', 'available', NULL, 20),
(1383, 33, 'A2', 'available', NULL, NULL),
(1384, 48, 'A2', 'available', NULL, NULL),
(1385, 34, 'A2', 'booked', NULL, 20),
(1386, 49, 'A2', 'booked', NULL, 20),
(1387, 35, 'A2', 'available', NULL, NULL),
(1388, 50, 'A2', 'available', NULL, NULL),
(1389, 36, 'A2', 'available', NULL, NULL),
(1390, 51, 'A2', 'available', NULL, NULL),
(1391, 37, 'A2', 'available', NULL, NULL),
(1392, 52, 'A2', 'available', NULL, NULL),
(1393, 30, 'A3', 'available', NULL, NULL),
(1394, 45, 'A3', 'available', NULL, NULL),
(1395, 31, 'A3', 'available', NULL, NULL),
(1396, 46, 'A3', 'available', NULL, NULL),
(1397, 32, 'A3', 'available', NULL, NULL),
(1398, 47, 'A3', 'available', NULL, 20),
(1399, 33, 'A3', 'available', NULL, NULL),
(1400, 48, 'A3', 'available', NULL, NULL),
(1401, 34, 'A3', 'booked', NULL, NULL),
(1402, 49, 'A3', 'available', NULL, NULL),
(1403, 35, 'A3', 'available', NULL, NULL),
(1404, 50, 'A3', 'available', NULL, NULL),
(1405, 36, 'A3', 'available', NULL, NULL),
(1406, 51, 'A3', 'available', NULL, NULL),
(1407, 37, 'A3', 'booked', NULL, NULL),
(1408, 52, 'A3', 'available', NULL, NULL),
(1409, 30, 'A4', 'available', NULL, NULL),
(1410, 45, 'A4', 'available', NULL, NULL),
(1411, 31, 'A4', 'available', NULL, NULL),
(1412, 46, 'A4', 'available', NULL, NULL),
(1413, 32, 'A4', 'available', NULL, NULL),
(1414, 47, 'A4', 'available', NULL, NULL),
(1415, 33, 'A4', 'available', NULL, NULL),
(1416, 48, 'A4', 'available', NULL, NULL),
(1417, 34, 'A4', 'booked', NULL, NULL),
(1418, 49, 'A4', 'available', NULL, NULL),
(1419, 35, 'A4', 'available', NULL, NULL),
(1420, 50, 'A4', 'available', NULL, NULL),
(1421, 36, 'A4', 'available', NULL, NULL),
(1422, 51, 'A4', 'available', NULL, NULL),
(1423, 37, 'A4', 'booked', NULL, NULL),
(1424, 52, 'A4', 'available', NULL, NULL),
(1425, 30, 'A5', 'available', NULL, NULL),
(1426, 45, 'A5', 'available', NULL, NULL),
(1427, 31, 'A5', 'available', NULL, NULL),
(1428, 46, 'A5', 'available', NULL, NULL),
(1429, 32, 'A5', 'available', NULL, NULL),
(1430, 47, 'A5', 'available', NULL, NULL),
(1431, 33, 'A5', 'available', NULL, NULL),
(1432, 48, 'A5', 'available', NULL, NULL),
(1433, 34, 'A5', 'available', NULL, 19),
(1434, 49, 'A5', 'available', NULL, NULL),
(1435, 35, 'A5', 'available', NULL, NULL),
(1436, 50, 'A5', 'available', NULL, NULL),
(1437, 36, 'A5', 'available', NULL, NULL),
(1438, 51, 'A5', 'available', NULL, NULL),
(1439, 37, 'A5', 'booked', NULL, NULL),
(1440, 52, 'A5', 'available', NULL, NULL),
(1441, 30, 'A6', 'available', NULL, NULL),
(1442, 45, 'A6', 'available', NULL, NULL),
(1443, 31, 'A6', 'available', NULL, NULL),
(1444, 46, 'A6', 'available', NULL, NULL),
(1445, 32, 'A6', 'available', NULL, NULL),
(1446, 47, 'A6', 'available', NULL, NULL),
(1447, 33, 'A6', 'available', NULL, NULL),
(1448, 48, 'A6', 'available', NULL, NULL),
(1449, 34, 'A6', 'available', NULL, NULL),
(1450, 49, 'A6', 'available', NULL, NULL),
(1451, 35, 'A6', 'available', NULL, NULL),
(1452, 50, 'A6', 'available', NULL, NULL),
(1453, 36, 'A6', 'available', NULL, NULL),
(1454, 51, 'A6', 'available', NULL, NULL),
(1455, 37, 'A6', 'available', NULL, NULL),
(1456, 52, 'A6', 'available', NULL, NULL),
(1457, 30, 'A7', 'available', NULL, NULL),
(1458, 45, 'A7', 'available', NULL, NULL),
(1459, 31, 'A7', 'available', NULL, NULL),
(1460, 46, 'A7', 'available', NULL, NULL),
(1461, 32, 'A7', 'available', NULL, NULL),
(1462, 47, 'A7', 'available', NULL, NULL),
(1463, 33, 'A7', 'available', NULL, NULL),
(1464, 48, 'A7', 'available', NULL, NULL),
(1465, 34, 'A7', 'available', NULL, NULL),
(1466, 49, 'A7', 'available', NULL, NULL),
(1467, 35, 'A7', 'available', NULL, NULL),
(1468, 50, 'A7', 'available', NULL, NULL),
(1469, 36, 'A7', 'available', NULL, NULL),
(1470, 51, 'A7', 'available', NULL, NULL),
(1471, 37, 'A7', 'available', NULL, NULL),
(1472, 52, 'A7', 'available', NULL, NULL),
(1473, 30, 'A8', 'available', NULL, NULL),
(1474, 45, 'A8', 'available', NULL, NULL),
(1475, 31, 'A8', 'available', NULL, NULL),
(1476, 46, 'A8', 'available', NULL, NULL),
(1477, 32, 'A8', 'available', NULL, NULL),
(1478, 47, 'A8', 'available', NULL, NULL),
(1479, 33, 'A8', 'available', NULL, NULL),
(1480, 48, 'A8', 'available', NULL, NULL),
(1481, 34, 'A8', 'available', NULL, NULL),
(1482, 49, 'A8', 'available', NULL, NULL),
(1483, 35, 'A8', 'available', NULL, NULL),
(1484, 50, 'A8', 'available', NULL, NULL),
(1485, 36, 'A8', 'available', NULL, NULL),
(1486, 51, 'A8', 'available', NULL, NULL),
(1487, 37, 'A8', 'available', NULL, NULL),
(1488, 52, 'A8', 'available', NULL, NULL),
(1489, 30, 'A9', 'available', NULL, NULL),
(1490, 45, 'A9', 'available', NULL, NULL),
(1491, 31, 'A9', 'available', NULL, NULL),
(1492, 46, 'A9', 'available', NULL, NULL),
(1493, 32, 'A9', 'available', NULL, NULL),
(1494, 47, 'A9', 'available', NULL, NULL),
(1495, 33, 'A9', 'available', NULL, NULL),
(1496, 48, 'A9', 'available', NULL, NULL),
(1497, 34, 'A9', 'available', NULL, NULL),
(1498, 49, 'A9', 'available', NULL, NULL),
(1499, 35, 'A9', 'available', NULL, NULL),
(1500, 50, 'A9', 'available', NULL, NULL),
(1501, 36, 'A9', 'available', NULL, NULL),
(1502, 51, 'A9', 'available', NULL, NULL),
(1503, 37, 'A9', 'available', NULL, NULL),
(1504, 52, 'A9', 'available', NULL, NULL),
(1505, 30, 'A10', 'available', NULL, NULL),
(1506, 45, 'A10', 'available', NULL, NULL),
(1507, 31, 'A10', 'available', NULL, NULL),
(1508, 46, 'A10', 'available', NULL, NULL),
(1509, 32, 'A10', 'available', NULL, NULL),
(1510, 47, 'A10', 'available', NULL, NULL),
(1511, 33, 'A10', 'available', NULL, NULL),
(1512, 48, 'A10', 'available', NULL, NULL),
(1513, 34, 'A10', 'available', NULL, NULL),
(1514, 49, 'A10', 'available', NULL, NULL),
(1515, 35, 'A10', 'available', NULL, NULL),
(1516, 50, 'A10', 'available', NULL, NULL),
(1517, 36, 'A10', 'available', NULL, NULL),
(1518, 51, 'A10', 'available', NULL, NULL),
(1519, 37, 'A10', 'available', NULL, NULL),
(1520, 52, 'A10', 'available', NULL, NULL),
(1521, 30, 'B1', 'booked', NULL, NULL),
(1522, 45, 'B1', 'available', NULL, NULL),
(1523, 31, 'B1', 'available', NULL, NULL),
(1524, 46, 'B1', 'available', NULL, NULL),
(1525, 32, 'B1', 'available', NULL, NULL),
(1526, 47, 'B1', 'available', NULL, NULL),
(1527, 33, 'B1', 'available', NULL, NULL),
(1528, 48, 'B1', 'available', NULL, NULL),
(1529, 34, 'B1', 'available', NULL, NULL),
(1530, 49, 'B1', 'available', NULL, NULL),
(1531, 35, 'B1', 'available', NULL, NULL),
(1532, 50, 'B1', 'available', NULL, NULL),
(1533, 36, 'B1', 'available', NULL, NULL),
(1534, 51, 'B1', 'available', NULL, NULL),
(1535, 37, 'B1', 'booked', NULL, NULL),
(1536, 52, 'B1', 'available', NULL, NULL),
(1537, 30, 'B2', 'available', NULL, NULL),
(1538, 45, 'B2', 'available', NULL, NULL),
(1539, 31, 'B2', 'available', NULL, NULL),
(1540, 46, 'B2', 'available', NULL, NULL),
(1541, 32, 'B2', 'available', NULL, NULL),
(1542, 47, 'B2', 'available', NULL, NULL),
(1543, 33, 'B2', 'available', NULL, NULL),
(1544, 48, 'B2', 'available', NULL, NULL),
(1545, 34, 'B2', 'available', NULL, NULL),
(1546, 49, 'B2', 'available', NULL, NULL),
(1547, 35, 'B2', 'available', NULL, NULL),
(1548, 50, 'B2', 'available', NULL, NULL),
(1549, 36, 'B2', 'available', NULL, NULL),
(1550, 51, 'B2', 'available', NULL, NULL),
(1551, 37, 'B2', 'available', NULL, NULL),
(1552, 52, 'B2', 'available', NULL, NULL),
(1553, 30, 'B3', 'available', NULL, NULL),
(1554, 45, 'B3', 'available', NULL, NULL),
(1555, 31, 'B3', 'available', NULL, NULL),
(1556, 46, 'B3', 'available', NULL, NULL),
(1557, 32, 'B3', 'available', NULL, NULL),
(1558, 47, 'B3', 'available', NULL, NULL),
(1559, 33, 'B3', 'available', NULL, NULL),
(1560, 48, 'B3', 'available', NULL, NULL),
(1561, 34, 'B3', 'available', NULL, 20),
(1562, 49, 'B3', 'available', NULL, NULL),
(1563, 35, 'B3', 'available', NULL, NULL),
(1564, 50, 'B3', 'available', NULL, NULL),
(1565, 36, 'B3', 'available', NULL, NULL),
(1566, 51, 'B3', 'available', NULL, NULL),
(1567, 37, 'B3', 'available', NULL, NULL),
(1568, 52, 'B3', 'available', NULL, NULL),
(1569, 30, 'B4', 'available', NULL, NULL),
(1570, 45, 'B4', 'available', NULL, NULL),
(1571, 31, 'B4', 'available', NULL, NULL),
(1572, 46, 'B4', 'available', NULL, NULL),
(1573, 32, 'B4', 'available', NULL, NULL),
(1574, 47, 'B4', 'available', NULL, NULL),
(1575, 33, 'B4', 'available', NULL, NULL),
(1576, 48, 'B4', 'available', NULL, NULL),
(1577, 34, 'B4', 'available', NULL, NULL),
(1578, 49, 'B4', 'available', NULL, NULL),
(1579, 35, 'B4', 'available', NULL, NULL),
(1580, 50, 'B4', 'available', NULL, NULL),
(1581, 36, 'B4', 'available', NULL, NULL),
(1582, 51, 'B4', 'available', NULL, NULL),
(1583, 37, 'B4', 'available', NULL, NULL),
(1584, 52, 'B4', 'available', NULL, NULL),
(1585, 30, 'B5', 'available', NULL, NULL),
(1586, 45, 'B5', 'available', NULL, NULL),
(1587, 31, 'B5', 'available', NULL, NULL),
(1588, 46, 'B5', 'available', NULL, NULL),
(1589, 32, 'B5', 'available', NULL, NULL),
(1590, 47, 'B5', 'available', NULL, NULL),
(1591, 33, 'B5', 'available', NULL, NULL),
(1592, 48, 'B5', 'available', NULL, NULL),
(1593, 34, 'B5', 'available', NULL, NULL),
(1594, 49, 'B5', 'available', NULL, NULL),
(1595, 35, 'B5', 'available', NULL, NULL),
(1596, 50, 'B5', 'available', NULL, NULL),
(1597, 36, 'B5', 'available', NULL, NULL),
(1598, 51, 'B5', 'available', NULL, NULL),
(1599, 37, 'B5', 'available', NULL, NULL),
(1600, 52, 'B5', 'available', NULL, NULL),
(1601, 30, 'B6', 'available', NULL, NULL),
(1602, 45, 'B6', 'available', NULL, NULL),
(1603, 31, 'B6', 'available', NULL, NULL),
(1604, 46, 'B6', 'available', NULL, NULL),
(1605, 32, 'B6', 'available', NULL, NULL),
(1606, 47, 'B6', 'available', NULL, NULL),
(1607, 33, 'B6', 'available', NULL, NULL),
(1608, 48, 'B6', 'available', NULL, NULL),
(1609, 34, 'B6', 'available', NULL, NULL),
(1610, 49, 'B6', 'available', NULL, NULL),
(1611, 35, 'B6', 'available', NULL, NULL),
(1612, 50, 'B6', 'available', NULL, NULL),
(1613, 36, 'B6', 'available', NULL, NULL),
(1614, 51, 'B6', 'available', NULL, NULL),
(1615, 37, 'B6', 'available', NULL, NULL),
(1616, 52, 'B6', 'available', NULL, NULL),
(1617, 30, 'B7', 'available', NULL, NULL),
(1618, 45, 'B7', 'available', NULL, NULL),
(1619, 31, 'B7', 'available', NULL, NULL),
(1620, 46, 'B7', 'available', NULL, NULL),
(1621, 32, 'B7', 'available', NULL, NULL),
(1622, 47, 'B7', 'available', NULL, NULL),
(1623, 33, 'B7', 'available', NULL, NULL),
(1624, 48, 'B7', 'available', NULL, NULL),
(1625, 34, 'B7', 'available', NULL, NULL),
(1626, 49, 'B7', 'available', NULL, NULL),
(1627, 35, 'B7', 'available', NULL, NULL),
(1628, 50, 'B7', 'available', NULL, NULL),
(1629, 36, 'B7', 'booked', NULL, NULL),
(1630, 51, 'B7', 'available', NULL, NULL),
(1631, 37, 'B7', 'available', NULL, NULL),
(1632, 52, 'B7', 'available', NULL, NULL),
(1633, 30, 'B8', 'available', NULL, NULL),
(1634, 45, 'B8', 'available', NULL, NULL),
(1635, 31, 'B8', 'available', NULL, NULL),
(1636, 46, 'B8', 'available', NULL, NULL),
(1637, 32, 'B8', 'available', NULL, NULL),
(1638, 47, 'B8', 'available', NULL, NULL),
(1639, 33, 'B8', 'available', NULL, NULL),
(1640, 48, 'B8', 'available', NULL, NULL),
(1641, 34, 'B8', 'available', NULL, NULL),
(1642, 49, 'B8', 'available', NULL, NULL),
(1643, 35, 'B8', 'available', NULL, NULL),
(1644, 50, 'B8', 'available', NULL, NULL),
(1645, 36, 'B8', 'available', NULL, NULL),
(1646, 51, 'B8', 'available', NULL, NULL),
(1647, 37, 'B8', 'available', NULL, NULL),
(1648, 52, 'B8', 'available', NULL, NULL),
(1649, 30, 'B9', 'available', NULL, NULL),
(1650, 45, 'B9', 'available', NULL, NULL),
(1651, 31, 'B9', 'available', NULL, NULL),
(1652, 46, 'B9', 'available', NULL, NULL),
(1653, 32, 'B9', 'available', NULL, NULL),
(1654, 47, 'B9', 'available', NULL, NULL),
(1655, 33, 'B9', 'available', NULL, NULL),
(1656, 48, 'B9', 'available', NULL, NULL),
(1657, 34, 'B9', 'available', NULL, NULL),
(1658, 49, 'B9', 'available', NULL, NULL),
(1659, 35, 'B9', 'available', NULL, NULL),
(1660, 50, 'B9', 'available', NULL, NULL),
(1661, 36, 'B9', 'available', NULL, NULL),
(1662, 51, 'B9', 'available', NULL, NULL),
(1663, 37, 'B9', 'available', NULL, NULL),
(1664, 52, 'B9', 'available', NULL, NULL),
(1665, 30, 'B10', 'available', NULL, NULL),
(1666, 45, 'B10', 'available', NULL, NULL),
(1667, 31, 'B10', 'available', NULL, NULL),
(1668, 46, 'B10', 'available', NULL, NULL),
(1669, 32, 'B10', 'available', NULL, NULL),
(1670, 47, 'B10', 'available', NULL, NULL),
(1671, 33, 'B10', 'available', NULL, NULL),
(1672, 48, 'B10', 'available', NULL, NULL),
(1673, 34, 'B10', 'available', NULL, NULL),
(1674, 49, 'B10', 'available', NULL, NULL),
(1675, 35, 'B10', 'available', NULL, NULL),
(1676, 50, 'B10', 'available', NULL, NULL),
(1677, 36, 'B10', 'available', NULL, NULL),
(1678, 51, 'B10', 'available', NULL, NULL),
(1679, 37, 'B10', 'available', NULL, NULL),
(1680, 52, 'B10', 'available', NULL, NULL),
(1681, 30, 'C1', 'available', NULL, NULL),
(1682, 45, 'C1', 'available', NULL, NULL),
(1683, 31, 'C1', 'available', NULL, NULL),
(1684, 46, 'C1', 'available', NULL, NULL),
(1685, 32, 'C1', 'available', NULL, NULL),
(1686, 47, 'C1', 'available', NULL, NULL),
(1687, 33, 'C1', 'available', NULL, NULL),
(1688, 48, 'C1', 'available', NULL, NULL),
(1689, 34, 'C1', 'available', NULL, NULL),
(1690, 49, 'C1', 'available', NULL, NULL),
(1691, 35, 'C1', 'available', NULL, NULL),
(1692, 50, 'C1', 'available', NULL, NULL),
(1693, 36, 'C1', 'available', NULL, NULL),
(1694, 51, 'C1', 'available', NULL, NULL),
(1695, 37, 'C1', 'available', NULL, NULL),
(1696, 52, 'C1', 'available', NULL, NULL),
(1697, 30, 'C2', 'available', NULL, NULL),
(1698, 45, 'C2', 'available', NULL, NULL),
(1699, 31, 'C2', 'available', NULL, NULL),
(1700, 46, 'C2', 'available', NULL, NULL),
(1701, 32, 'C2', 'available', NULL, NULL),
(1702, 47, 'C2', 'available', NULL, NULL),
(1703, 33, 'C2', 'available', NULL, NULL),
(1704, 48, 'C2', 'available', NULL, NULL),
(1705, 34, 'C2', 'available', NULL, NULL),
(1706, 49, 'C2', 'available', NULL, NULL),
(1707, 35, 'C2', 'available', NULL, NULL),
(1708, 50, 'C2', 'available', NULL, NULL),
(1709, 36, 'C2', 'available', NULL, NULL),
(1710, 51, 'C2', 'available', NULL, NULL),
(1711, 37, 'C2', 'available', NULL, NULL),
(1712, 52, 'C2', 'available', NULL, NULL),
(1713, 30, 'C3', 'available', NULL, NULL),
(1714, 45, 'C3', 'available', NULL, NULL),
(1715, 31, 'C3', 'available', NULL, NULL),
(1716, 46, 'C3', 'available', NULL, NULL),
(1717, 32, 'C3', 'available', NULL, NULL),
(1718, 47, 'C3', 'available', NULL, NULL),
(1719, 33, 'C3', 'available', NULL, NULL),
(1720, 48, 'C3', 'available', NULL, NULL),
(1721, 34, 'C3', 'available', NULL, NULL),
(1722, 49, 'C3', 'available', NULL, NULL),
(1723, 35, 'C3', 'available', NULL, NULL),
(1724, 50, 'C3', 'available', NULL, NULL),
(1725, 36, 'C3', 'available', NULL, NULL),
(1726, 51, 'C3', 'available', NULL, NULL),
(1727, 37, 'C3', 'available', NULL, NULL),
(1728, 52, 'C3', 'available', NULL, NULL),
(1729, 30, 'C4', 'available', NULL, NULL),
(1730, 45, 'C4', 'available', NULL, NULL),
(1731, 31, 'C4', 'available', NULL, NULL),
(1732, 46, 'C4', 'available', NULL, NULL),
(1733, 32, 'C4', 'available', NULL, NULL),
(1734, 47, 'C4', 'booked', NULL, NULL),
(1735, 33, 'C4', 'available', NULL, NULL),
(1736, 48, 'C4', 'available', NULL, NULL),
(1737, 34, 'C4', 'available', NULL, NULL),
(1738, 49, 'C4', 'available', NULL, NULL),
(1739, 35, 'C4', 'available', NULL, NULL),
(1740, 50, 'C4', 'available', NULL, NULL),
(1741, 36, 'C4', 'available', NULL, NULL),
(1742, 51, 'C4', 'available', NULL, NULL),
(1743, 37, 'C4', 'available', NULL, NULL),
(1744, 52, 'C4', 'available', NULL, NULL),
(1745, 30, 'C5', 'available', NULL, NULL),
(1746, 45, 'C5', 'available', NULL, NULL),
(1747, 31, 'C5', 'available', NULL, NULL),
(1748, 46, 'C5', 'available', NULL, NULL),
(1749, 32, 'C5', 'available', NULL, NULL),
(1750, 47, 'C5', 'available', NULL, NULL),
(1751, 33, 'C5', 'available', NULL, NULL),
(1752, 48, 'C5', 'available', NULL, NULL),
(1753, 34, 'C5', 'available', NULL, NULL),
(1754, 49, 'C5', 'available', NULL, NULL),
(1755, 35, 'C5', 'available', NULL, NULL),
(1756, 50, 'C5', 'available', NULL, NULL),
(1757, 36, 'C5', 'available', NULL, NULL),
(1758, 51, 'C5', 'available', NULL, NULL),
(1759, 37, 'C5', 'available', NULL, NULL),
(1760, 52, 'C5', 'available', NULL, NULL),
(1761, 30, 'C6', 'available', NULL, NULL),
(1762, 45, 'C6', 'available', NULL, NULL),
(1763, 31, 'C6', 'available', NULL, NULL),
(1764, 46, 'C6', 'available', NULL, NULL),
(1765, 32, 'C6', 'available', NULL, NULL),
(1766, 47, 'C6', 'available', NULL, NULL),
(1767, 33, 'C6', 'available', NULL, NULL),
(1768, 48, 'C6', 'available', NULL, NULL),
(1769, 34, 'C6', 'available', NULL, NULL),
(1770, 49, 'C6', 'available', NULL, NULL),
(1771, 35, 'C6', 'available', NULL, NULL),
(1772, 50, 'C6', 'available', NULL, NULL),
(1773, 36, 'C6', 'available', NULL, NULL),
(1774, 51, 'C6', 'available', NULL, NULL),
(1775, 37, 'C6', 'available', NULL, NULL),
(1776, 52, 'C6', 'available', NULL, NULL),
(1777, 30, 'C7', 'available', NULL, NULL),
(1778, 45, 'C7', 'available', NULL, NULL),
(1779, 31, 'C7', 'available', NULL, NULL),
(1780, 46, 'C7', 'available', NULL, NULL),
(1781, 32, 'C7', 'available', NULL, NULL),
(1782, 47, 'C7', 'available', NULL, NULL),
(1783, 33, 'C7', 'available', NULL, NULL),
(1784, 48, 'C7', 'available', NULL, NULL),
(1785, 34, 'C7', 'available', NULL, NULL),
(1786, 49, 'C7', 'available', NULL, NULL),
(1787, 35, 'C7', 'available', NULL, NULL),
(1788, 50, 'C7', 'available', NULL, NULL),
(1789, 36, 'C7', 'available', NULL, NULL),
(1790, 51, 'C7', 'available', NULL, NULL),
(1791, 37, 'C7', 'available', NULL, NULL),
(1792, 52, 'C7', 'available', NULL, NULL),
(1793, 30, 'C8', 'available', NULL, NULL),
(1794, 45, 'C8', 'available', NULL, NULL),
(1795, 31, 'C8', 'available', NULL, NULL),
(1796, 46, 'C8', 'available', NULL, NULL),
(1797, 32, 'C8', 'available', NULL, NULL),
(1798, 47, 'C8', 'available', NULL, NULL),
(1799, 33, 'C8', 'available', NULL, NULL),
(1800, 48, 'C8', 'available', NULL, NULL),
(1801, 34, 'C8', 'available', NULL, NULL),
(1802, 49, 'C8', 'available', NULL, NULL),
(1803, 35, 'C8', 'available', NULL, NULL),
(1804, 50, 'C8', 'available', NULL, NULL),
(1805, 36, 'C8', 'available', NULL, NULL),
(1806, 51, 'C8', 'available', NULL, NULL),
(1807, 37, 'C8', 'available', NULL, NULL),
(1808, 52, 'C8', 'available', NULL, NULL),
(1809, 30, 'C9', 'available', NULL, NULL),
(1810, 45, 'C9', 'available', NULL, NULL),
(1811, 31, 'C9', 'available', NULL, NULL),
(1812, 46, 'C9', 'available', NULL, NULL),
(1813, 32, 'C9', 'available', NULL, NULL),
(1814, 47, 'C9', 'available', NULL, NULL),
(1815, 33, 'C9', 'available', NULL, NULL),
(1816, 48, 'C9', 'available', NULL, NULL),
(1817, 34, 'C9', 'available', NULL, NULL),
(1818, 49, 'C9', 'available', NULL, NULL),
(1819, 35, 'C9', 'available', NULL, NULL),
(1820, 50, 'C9', 'available', NULL, NULL),
(1821, 36, 'C9', 'available', NULL, NULL),
(1822, 51, 'C9', 'available', NULL, NULL),
(1823, 37, 'C9', 'available', NULL, NULL),
(1824, 52, 'C9', 'available', NULL, NULL),
(1825, 30, 'C10', 'available', NULL, NULL),
(1826, 45, 'C10', 'available', NULL, NULL),
(1827, 31, 'C10', 'available', NULL, NULL),
(1828, 46, 'C10', 'available', NULL, NULL),
(1829, 32, 'C10', 'available', NULL, NULL),
(1830, 47, 'C10', 'available', NULL, NULL),
(1831, 33, 'C10', 'available', NULL, NULL),
(1832, 48, 'C10', 'available', NULL, NULL),
(1833, 34, 'C10', 'available', NULL, NULL),
(1834, 49, 'C10', 'available', NULL, NULL),
(1835, 35, 'C10', 'available', NULL, NULL),
(1836, 50, 'C10', 'available', NULL, NULL),
(1837, 36, 'C10', 'available', NULL, NULL),
(1838, 51, 'C10', 'available', NULL, NULL),
(1839, 37, 'C10', 'available', NULL, NULL),
(1840, 52, 'C10', 'available', NULL, NULL),
(1841, 30, 'D1', 'available', NULL, NULL),
(1842, 45, 'D1', 'available', NULL, NULL),
(1843, 31, 'D1', 'available', NULL, NULL),
(1844, 46, 'D1', 'available', NULL, NULL),
(1845, 32, 'D1', 'available', NULL, NULL),
(1846, 47, 'D1', 'available', NULL, NULL),
(1847, 33, 'D1', 'available', NULL, NULL),
(1848, 48, 'D1', 'available', NULL, NULL),
(1849, 34, 'D1', 'available', NULL, NULL),
(1850, 49, 'D1', 'available', NULL, NULL),
(1851, 35, 'D1', 'available', NULL, NULL),
(1852, 50, 'D1', 'available', NULL, NULL),
(1853, 36, 'D1', 'available', NULL, NULL),
(1854, 51, 'D1', 'available', NULL, NULL),
(1855, 37, 'D1', 'available', NULL, NULL),
(1856, 52, 'D1', 'available', NULL, NULL),
(1857, 30, 'D2', 'available', NULL, NULL),
(1858, 45, 'D2', 'available', NULL, NULL),
(1859, 31, 'D2', 'available', NULL, NULL),
(1860, 46, 'D2', 'available', NULL, NULL),
(1861, 32, 'D2', 'available', NULL, NULL),
(1862, 47, 'D2', 'available', NULL, NULL),
(1863, 33, 'D2', 'available', NULL, NULL),
(1864, 48, 'D2', 'available', NULL, NULL),
(1865, 34, 'D2', 'available', NULL, NULL),
(1866, 49, 'D2', 'available', NULL, NULL),
(1867, 35, 'D2', 'available', NULL, NULL),
(1868, 50, 'D2', 'available', NULL, NULL),
(1869, 36, 'D2', 'available', NULL, NULL),
(1870, 51, 'D2', 'available', NULL, NULL),
(1871, 37, 'D2', 'available', NULL, NULL),
(1872, 52, 'D2', 'available', NULL, NULL),
(1873, 30, 'D3', 'available', NULL, NULL),
(1874, 45, 'D3', 'available', NULL, NULL),
(1875, 31, 'D3', 'available', NULL, NULL),
(1876, 46, 'D3', 'available', NULL, NULL),
(1877, 32, 'D3', 'available', NULL, NULL),
(1878, 47, 'D3', 'available', NULL, NULL),
(1879, 33, 'D3', 'available', NULL, NULL),
(1880, 48, 'D3', 'available', NULL, NULL),
(1881, 34, 'D3', 'available', NULL, NULL),
(1882, 49, 'D3', 'available', NULL, NULL),
(1883, 35, 'D3', 'available', NULL, NULL),
(1884, 50, 'D3', 'available', NULL, NULL),
(1885, 36, 'D3', 'available', NULL, NULL),
(1886, 51, 'D3', 'available', NULL, NULL),
(1887, 37, 'D3', 'available', NULL, NULL),
(1888, 52, 'D3', 'available', NULL, NULL),
(1889, 30, 'D4', 'available', NULL, NULL),
(1890, 45, 'D4', 'available', NULL, NULL),
(1891, 31, 'D4', 'available', NULL, NULL),
(1892, 46, 'D4', 'available', NULL, NULL),
(1893, 32, 'D4', 'available', NULL, NULL),
(1894, 47, 'D4', 'available', NULL, NULL),
(1895, 33, 'D4', 'available', NULL, NULL),
(1896, 48, 'D4', 'available', NULL, NULL),
(1897, 34, 'D4', 'available', NULL, NULL),
(1898, 49, 'D4', 'available', NULL, NULL),
(1899, 35, 'D4', 'available', NULL, NULL),
(1900, 50, 'D4', 'available', NULL, NULL),
(1901, 36, 'D4', 'available', NULL, NULL),
(1902, 51, 'D4', 'available', NULL, NULL),
(1903, 37, 'D4', 'available', NULL, NULL),
(1904, 52, 'D4', 'available', NULL, NULL),
(1905, 30, 'D5', 'available', NULL, NULL),
(1906, 45, 'D5', 'available', NULL, NULL),
(1907, 31, 'D5', 'available', NULL, NULL),
(1908, 46, 'D5', 'available', NULL, NULL),
(1909, 32, 'D5', 'available', NULL, NULL),
(1910, 47, 'D5', 'available', NULL, NULL),
(1911, 33, 'D5', 'available', NULL, NULL),
(1912, 48, 'D5', 'available', NULL, NULL),
(1913, 34, 'D5', 'available', NULL, NULL),
(1914, 49, 'D5', 'available', NULL, NULL),
(1915, 35, 'D5', 'available', NULL, NULL),
(1916, 50, 'D5', 'available', NULL, NULL),
(1917, 36, 'D5', 'available', NULL, NULL),
(1918, 51, 'D5', 'available', NULL, NULL),
(1919, 37, 'D5', 'available', NULL, NULL),
(1920, 52, 'D5', 'available', NULL, NULL),
(1921, 30, 'D6', 'available', NULL, NULL),
(1922, 45, 'D6', 'available', NULL, NULL),
(1923, 31, 'D6', 'available', NULL, NULL),
(1924, 46, 'D6', 'available', NULL, NULL),
(1925, 32, 'D6', 'available', NULL, NULL),
(1926, 47, 'D6', 'available', NULL, NULL),
(1927, 33, 'D6', 'available', NULL, NULL),
(1928, 48, 'D6', 'available', NULL, NULL),
(1929, 34, 'D6', 'available', NULL, NULL),
(1930, 49, 'D6', 'available', NULL, NULL),
(1931, 35, 'D6', 'available', NULL, NULL),
(1932, 50, 'D6', 'available', NULL, NULL),
(1933, 36, 'D6', 'available', NULL, NULL),
(1934, 51, 'D6', 'available', NULL, NULL),
(1935, 37, 'D6', 'available', NULL, NULL),
(1936, 52, 'D6', 'available', NULL, NULL),
(1937, 30, 'D7', 'available', NULL, NULL),
(1938, 45, 'D7', 'available', NULL, NULL),
(1939, 31, 'D7', 'available', NULL, NULL),
(1940, 46, 'D7', 'available', NULL, NULL),
(1941, 32, 'D7', 'available', NULL, NULL),
(1942, 47, 'D7', 'available', NULL, NULL),
(1943, 33, 'D7', 'available', NULL, NULL),
(1944, 48, 'D7', 'available', NULL, NULL),
(1945, 34, 'D7', 'available', NULL, NULL),
(1946, 49, 'D7', 'available', NULL, NULL),
(1947, 35, 'D7', 'available', NULL, NULL),
(1948, 50, 'D7', 'available', NULL, NULL),
(1949, 36, 'D7', 'available', NULL, NULL),
(1950, 51, 'D7', 'available', NULL, NULL),
(1951, 37, 'D7', 'available', NULL, NULL),
(1952, 52, 'D7', 'available', NULL, NULL),
(1953, 30, 'D8', 'available', NULL, NULL),
(1954, 45, 'D8', 'available', NULL, NULL),
(1955, 31, 'D8', 'available', NULL, NULL),
(1956, 46, 'D8', 'available', NULL, NULL),
(1957, 32, 'D8', 'available', NULL, NULL),
(1958, 47, 'D8', 'available', NULL, NULL),
(1959, 33, 'D8', 'available', NULL, NULL),
(1960, 48, 'D8', 'available', NULL, NULL),
(1961, 34, 'D8', 'available', NULL, NULL),
(1962, 49, 'D8', 'available', NULL, NULL),
(1963, 35, 'D8', 'available', NULL, NULL),
(1964, 50, 'D8', 'available', NULL, NULL),
(1965, 36, 'D8', 'available', NULL, NULL),
(1966, 51, 'D8', 'available', NULL, NULL),
(1967, 37, 'D8', 'available', NULL, NULL),
(1968, 52, 'D8', 'available', NULL, NULL),
(1969, 30, 'D9', 'available', NULL, NULL),
(1970, 45, 'D9', 'available', NULL, NULL),
(1971, 31, 'D9', 'available', NULL, NULL),
(1972, 46, 'D9', 'available', NULL, NULL),
(1973, 32, 'D9', 'available', NULL, NULL),
(1974, 47, 'D9', 'available', NULL, NULL),
(1975, 33, 'D9', 'available', NULL, NULL),
(1976, 48, 'D9', 'available', NULL, NULL),
(1977, 34, 'D9', 'available', NULL, NULL),
(1978, 49, 'D9', 'available', NULL, NULL),
(1979, 35, 'D9', 'available', NULL, NULL),
(1980, 50, 'D9', 'available', NULL, NULL),
(1981, 36, 'D9', 'available', NULL, NULL),
(1982, 51, 'D9', 'available', NULL, NULL),
(1983, 37, 'D9', 'available', NULL, NULL),
(1984, 52, 'D9', 'available', NULL, NULL),
(1985, 30, 'D10', 'available', NULL, NULL),
(1986, 45, 'D10', 'available', NULL, NULL),
(1987, 31, 'D10', 'available', NULL, NULL),
(1988, 46, 'D10', 'available', NULL, NULL),
(1989, 32, 'D10', 'available', NULL, NULL),
(1990, 47, 'D10', 'available', NULL, NULL),
(1991, 33, 'D10', 'available', NULL, NULL),
(1992, 48, 'D10', 'available', NULL, NULL),
(1993, 34, 'D10', 'available', NULL, NULL),
(1994, 49, 'D10', 'available', NULL, NULL),
(1995, 35, 'D10', 'available', NULL, NULL),
(1996, 50, 'D10', 'available', NULL, NULL),
(1997, 36, 'D10', 'available', NULL, NULL),
(1998, 51, 'D10', 'available', NULL, NULL),
(1999, 37, 'D10', 'available', NULL, NULL),
(2000, 52, 'D10', 'available', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `showtimes`
--

CREATE TABLE `showtimes` (
  `showtime_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `show_date` date NOT NULL,
  `show_time` time NOT NULL,
  `screen` varchar(50) DEFAULT NULL,
  `base_price` decimal(8,2) DEFAULT 250.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `showtimes`
--

INSERT INTO `showtimes` (`showtime_id`, `movie_id`, `show_date`, `show_time`, `screen`, `base_price`) VALUES
(30, 17, '2026-06-12', '10:00:00', 'Screen 1', 200.00),
(31, 20, '2026-06-15', '10:00:00', 'Screen 1', 200.00),
(32, 21, '2026-06-16', '10:00:00', 'Screen 1', 200.00),
(33, 22, '2026-06-17', '10:00:00', 'Screen 1', 200.00),
(34, 25, '2026-06-20', '10:00:00', 'Screen 1', 200.00),
(35, 26, '2026-06-21', '10:00:00', 'Screen 1', 200.00),
(36, 27, '2026-06-22', '10:00:00', 'Screen 1', 200.00),
(37, 28, '2026-06-23', '10:00:00', 'Screen 1', 200.00),
(45, 17, '2026-06-13', '18:30:00', 'Screen 2', 300.00),
(46, 20, '2026-06-16', '18:30:00', 'Screen 2', 300.00),
(47, 21, '2026-06-17', '18:30:00', 'Screen 2', 300.00),
(48, 22, '2026-06-18', '18:30:00', 'Screen 2', 300.00),
(49, 25, '2026-06-21', '18:30:00', 'Screen 2', 300.00),
(50, 26, '2026-06-22', '18:30:00', 'Screen 2', 300.00),
(51, 27, '2026-06-23', '18:30:00', 'Screen 2', 300.00),
(52, 28, '2026-06-24', '18:30:00', 'Screen 2', 300.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `showtime_id` (`showtime_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`movie_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`seat_id`),
  ADD KEY `showtime_id` (`showtime_id`);

--
-- Indexes for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`showtime_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `movie_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `seat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2408;

--
-- AUTO_INCREMENT for table `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `showtime_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`showtime_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`showtime_id`) ON DELETE CASCADE;

--
-- Constraints for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
