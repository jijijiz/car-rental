-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2025 at 06:46 PM
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
-- Database: `car_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 17, 'User Login', 'User Admin logged in successfully from IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 14:36:13'),
(2, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-15 15:38:17 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 14:38:17'),
(3, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-15 15:38:48 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 14:38:48'),
(4, 17, 'Add Car', 'Added new car: testing testing testing (RM11.00/day)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 14:54:49'),
(5, 17, 'Delete Car', 'Deleted car ID 7:   ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 15:01:11'),
(6, 17, 'Edit Car', 'Updated car ID 6:    (RM0.00/day)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 15:02:07'),
(7, 17, 'Edit Car', 'Updated car ID 6:    (RM0.00/day)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 15:02:14'),
(8, 17, 'Delete Car', 'Deleted car ID 6:   ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 15:02:26'),
(9, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-15 17:07:43 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 16:07:43'),
(10, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-15 17:11:27 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 16:11:27'),
(11, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-15 17:17:36 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-15 16:17:36'),
(12, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-16 19:09:19 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-16 18:09:19'),
(13, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-02-27 11:56:53 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-02-27 10:56:53'),
(14, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-03-02 11:01:15 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 10:01:15'),
(15, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-03-02 11:01:52 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 10:01:52'),
(16, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-03-02 11:03:42 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 10:03:42'),
(17, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-03-02 11:03:54 | IP: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 10:03:54'),
(18, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-03-02 11:10:59 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 10:10:59'),
(19, 17, 'User Login', 'User Details - Name: Admin | Email: admin@admin.com | Role: admin | Login Time: 2025-03-02 11:29:36 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 10:29:36'),
(20, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 12:17:43 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 11:17:43'),
(21, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 12:18:08 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 11:18:08'),
(22, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 12:19:43 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 11:19:43'),
(23, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 12:19:52 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 11:19:52'),
(24, 28, 'User Login', 'User Details - Name: test1234 | Email: test1234@test.com | Role: user | Login Time: 2025-03-02 13:04:58 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 12:04:58'),
(25, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 13:51:59 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 12:51:59'),
(26, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 13:59:05 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 12:59:05'),
(27, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 14:44:09 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 13:44:09'),
(28, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 14:57:27 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 13:57:27'),
(29, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-02 15:21:35 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 14:21:35'),
(30, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-03 00:19:22 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 16:19:22'),
(31, 9, 'cash_payment_selected', 'Cash payment selected for order #7', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 16:27:18'),
(32, 9, 'cash_payment_selected', 'Cash payment selected for order #39', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 16:34:45'),
(33, 9, 'cash_payment_selected', '现金支付已选择，订单号: #40', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-02 16:52:22'),
(34, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-03 17:39:46 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-03 16:39:46'),
(35, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-03 17:40:40 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-03 16:40:40'),
(36, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-03 17:50:35 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-03 16:50:35'),
(37, 9, 'Google Login', 'User Details - Name: jianzhi wong | Email: wongjianzhi0@gmail.com | Role: user | Login Time: 2025-03-03 17:51:17 | IP: 127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', '2025-03-03 16:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `admin_comments`
--

CREATE TABLE `admin_comments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_comments`
--

INSERT INTO `admin_comments` (`id`, `order_id`, `admin_id`, `comment`, `created_at`) VALUES
(1, 7, 17, 'testing', '2025-02-15 14:07:22');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `seats` int(11) NOT NULL,
  `transmission` enum('automatic','manual') NOT NULL,
  `fuel_type` enum('petrol','diesel','electric','hybrid') NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('available','maintenance','rented') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `name`, `brand`, `model`, `year`, `color`, `seats`, `transmission`, `fuel_type`, `price_per_day`, `description`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Toyota Camry', 'Toyota', 'Camry', 2022, 'Silver', 5, 'automatic', 'petrol', 10.00, 'Comfortable sedan with excellent fuel efficiency', 'uploads/cars/67af7f0c5f9b6.webp', 'available', '2025-02-11 10:33:02', '2025-03-03 16:47:27'),
(2, 'Honda Civic', 'Honda', 'Civic', 2023, 'Black', 5, 'automatic', 'petrol', 10.00, 'Sporty compact car with modern features', 'uploads/cars/67af7f00bc37e.jpg', 'available', '2025-02-11 10:33:02', '2025-03-03 16:47:35'),
(3, 'Tesla Model 3', 'Tesla', 'Model 3', 2023, 'White', 5, 'automatic', 'electric', 10.00, 'High-performance electric vehicle', 'uploads/cars/67af7dad829bb.jpg', 'available', '2025-02-11 10:33:02', '2025-03-03 16:47:41');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `car_id`, `rating`, `content`, `created_at`) VALUES
(21, 20, 1, 3, 'Decent car for the price. Some wear and tear visible but nothing major. Good fuel economy but could use better maintenance.', '2024-01-18 08:40:00'),
(22, 21, 2, 5, 'Outstanding experience! The car exceeded my expectations. Very powerful and comfortable. The GPS system was very helpful during our trip.', '2024-01-19 03:25:00'),
(23, 22, 2, 4, 'Really enjoyed driving this car. Great handling and performance. The pickup and return process was smooth and efficient.', '2024-01-20 05:50:00'),
(24, 23, 3, 2, 'The car was okay but had some issues with the air conditioning. Customer service was good but the car needs maintenance.', '2024-01-21 07:10:00'),
(25, 24, 3, 5, 'Perfect family car for our vacation! Spacious, comfortable, and great on gas. The kids loved it and we had plenty of room for luggage.', '2024-01-22 04:45:00'),
(26, 20, 3, 4, 'Stylish and modern car. Very clean and well-maintained. The bluetooth connectivity was a great feature for our long drives.', '2024-01-23 01:30:00'),
(27, 21, 1, 5, 'Excellent sports car! The performance was incredible and it was so much fun to drive. Everything was perfect from start to finish.', '2024-01-24 06:15:00'),
(28, 22, 1, 3, 'Good car for city driving. Easy to park and maneuver. Fuel efficiency was decent but could be better.', '2024-01-25 08:20:00'),
(29, 23, 2, 4, 'Luxury at its finest! The interior was spotless and the ride was smooth. A bit pricey but worth it for special occasions.', '2024-01-26 02:45:00'),
(30, 24, 1, 5, 'Great SUV for our family trip! Plenty of space, comfortable seats, and excellent safety features. Will definitely rent again.', '2024-01-27 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `last_attempt` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','completed','cancelled') DEFAULT 'pending',
  `payment_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `car_id`, `start_date`, `end_date`, `total_price`, `status`, `payment_id`, `created_at`, `payment_method`, `payment_notes`, `paid_at`, `updated_at`) VALUES
(7, 9, 2, '2025-02-24 00:00:00', '2025-02-28 00:00:00', 359.96, '', NULL, '2025-02-13 01:47:05', 'cash', 'Pending cash payment on arrival', NULL, '2025-03-02 16:43:05'),
(23, 9, 1, '2024-02-15 10:00:00', '2024-02-18 10:00:00', 299.97, 'completed', NULL, '2024-02-14 00:30:00', 'credit_card', 'Payment completed via credit card', NULL, '2025-03-02 16:43:05'),
(24, 18, 2, '2024-02-16 14:00:00', '2024-02-20 14:00:00', 359.96, 'completed', NULL, '2024-02-15 01:45:00', 'credit_card', 'Payment completed via credit card', NULL, '2025-03-02 16:43:05'),
(25, 19, 3, '2024-02-17 09:00:00', '2024-02-19 09:00:00', 299.98, 'completed', NULL, '2024-02-16 03:20:00', 'debit_card', 'Payment completed via debit card', NULL, '2025-03-02 16:43:05'),
(26, 20, 1, '2024-02-18 15:00:00', '2024-02-21 15:00:00', 299.97, 'completed', NULL, '2024-02-17 05:15:00', 'credit_card', 'Payment completed via credit card', NULL, '2025-03-02 16:43:05'),
(27, 21, 2, '2024-02-19 11:00:00', '2024-02-22 11:00:00', 269.97, 'completed', NULL, '2024-02-18 02:30:00', 'paypal', 'Payment completed via PayPal', NULL, '2025-03-02 16:43:05'),
(29, 23, 1, '2024-02-21 12:00:00', '2024-02-24 12:00:00', 299.97, 'completed', NULL, '2024-02-20 06:20:00', 'debit_card', 'Payment completed via debit card', NULL, '2025-03-02 16:43:05'),
(31, 9, 3, '2024-02-23 16:00:00', '2024-02-26 16:00:00', 449.97, 'completed', NULL, '2024-02-22 07:45:00', 'paypal', 'Payment completed via PayPal', NULL, '2025-03-02 16:43:05'),
(32, 18, 1, '2024-02-24 14:00:00', '2024-02-27 14:00:00', 299.97, 'completed', NULL, '2024-02-23 04:20:00', 'credit_card', 'Payment completed via credit card', NULL, '2025-03-02 16:43:05'),
(33, 19, 2, '2024-02-25 11:00:00', '2024-02-28 11:00:00', 269.97, 'paid', NULL, '2024-02-24 02:15:00', 'credit_card', 'Payment completed, awaiting pickup', NULL, '2025-03-02 16:43:05'),
(38, 9, 3, '2025-03-26 00:00:00', '2025-03-28 00:00:00', 299.98, 'cancelled', NULL, '2025-03-02 13:53:50', 'cash_on_arrival', 'Pending cash payment on arrival', NULL, '2025-03-02 16:43:05'),
(46, 9, 1, '2025-03-04 00:00:00', '2025-03-06 00:00:00', 20.00, 'pending', NULL, '2025-03-03 17:04:28', 'cash', 'Pending cash payment on arrival', NULL, '2025-03-03 17:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `order_id`, `user_id`, `car_id`, `rating`, `comment`, `created_at`) VALUES
(21, 7, 9, 2, 5, 'The Honda Civic was perfect for my trip! Very sporty and fuel efficient. The modern features made driving enjoyable.', '0000-00-00 00:00:00'),
(22, 23, 19, 3, 5, 'Amazing experience with the Tesla Model 3! The electric car was super quiet and fun to drive. All the high-tech features worked perfectly.', '2024-02-20 01:15:00'),
(23, 24, 20, 1, 4, 'Reliable and comfortable Camry. Good value for money. The car was clean and well-maintained. Would rent again.', '2024-02-22 08:40:00'),
(24, 25, 21, 2, 5, 'The Civic exceeded my expectations! Great fuel economy and smooth ride. The modern features made the journey much more enjoyable.', '2024-02-23 03:25:00'),
(25, 26, 22, 3, 5, 'Tesla Model 3 is a game changer! The acceleration is incredible and the autopilot feature is fascinating. Worth every penny.', '2024-02-24 05:50:00'),
(26, 27, 23, 1, 4, 'Solid choice with the Camry. Spacious interior and smooth ride. The staff was very professional and helpful.', '2024-02-25 07:10:00'),
(28, 29, 9, 3, 5, 'Second time renting the Tesla Model 3 and still impressed! The range was more than enough for our trip. Charging was convenient.', '2024-02-27 01:30:00'),
(30, 31, 19, 2, 5, 'Great handling with the Civic! The car felt very responsive and the fuel efficiency was excellent.', '2024-02-29 03:20:00'),
(31, 32, 20, 3, 5, 'The Tesla Model 3 was the highlight of our trip. Amazing technology and performance. Will definitely rent again!', '2024-03-01 05:30:00'),
(32, 33, 21, 1, 4, 'Very satisfied with the Camry rental. Clean, comfortable, and reliable. Perfect for family use.', '2024-03-02 07:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `oauth_provider` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_code_expiry` datetime DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(10) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `google_id`, `reset_token`, `reset_expiry`, `failed_attempts`, `locked_until`, `oauth_provider`, `name`, `reset_code`, `reset_code_expiry`, `phone`, `role`, `created_at`, `status`) VALUES
(9, 'wongjianzhi0@gmail.com', '$2y$10$RtoamS0cdUYd4OhcCeMvgupI5dThqe90z/4Z1cqtEjwVcnTWdP/Ku', NULL, NULL, NULL, 0, NULL, NULL, 'test20', NULL, NULL, 'test', 'user', '2025-02-12 12:39:08', 1),
(17, 'admin@admin.com', '$2y$10$RvLNv0HVSvfRsDPZLs.Cgeu9I6S2yZUBcFx2bZJFlbXc6AfIWkGIi', NULL, NULL, NULL, 0, NULL, NULL, 'Admin', NULL, NULL, NULL, 'admin', '2025-02-12 12:39:08', 1),
(18, 'test1@test.com', '$2y$10$rGoKHmcd0tKzB.wepOj6Vep771mkKZj8gwuk5P/5ZgdxSB/PDOujy', NULL, NULL, NULL, 0, NULL, NULL, 'test1', NULL, NULL, NULL, 'user', '2025-02-13 01:39:14', 1),
(19, 'test2@test.com', '$2y$10$BBQB8Gg9EhynGHPlWmJZneJyMolFp0WnAKvDfBdaPLBzhzi2GEWGm', NULL, NULL, NULL, 0, NULL, NULL, 'test2', NULL, NULL, NULL, 'user', '2025-02-13 01:46:03', 1),
(20, 'john.doe@example.com', '$2y$10$RtoamS0cdUYd4OhcCeMvgupI5dThqe90z/4Z1cqtEjwVcnTWdP/Ku', NULL, NULL, NULL, 0, NULL, NULL, 'John Doe', NULL, NULL, NULL, 'user', '2025-02-14 02:00:00', 1),
(21, 'emma.wilson@example.com', '$2y$10$RtoamS0cdUYd4OhcCeMvgupI5dThqe90z/4Z1cqtEjwVcnTWdP/Ku', NULL, NULL, NULL, 0, NULL, NULL, 'Emma Wilson', NULL, NULL, NULL, 'user', '2025-02-14 02:30:00', 1),
(22, 'michael.brown@example.com', '$2y$10$RtoamS0cdUYd4OhcCeMvgupI5dThqe90z/4Z1cqtEjwVcnTWdP/Ku', NULL, NULL, NULL, 0, NULL, NULL, 'Michael Brown', NULL, NULL, NULL, 'user', '2025-02-14 03:00:00', 1),
(23, 'sarah.parker@example.com', '$2y$10$RtoamS0cdUYd4OhcCeMvgupI5dThqe90z/4Z1cqtEjwVcnTWdP/Ku', NULL, NULL, NULL, 0, NULL, NULL, 'Sarah Parker', NULL, NULL, NULL, 'user', '2025-02-14 03:30:00', 1),
(24, 'david.miller@example.com', '$2y$10$RtoamS0cdUYd4OhcCeMvgupI5dThqe90z/4Z1cqtEjwVcnTWdP/Ku', NULL, NULL, NULL, 0, NULL, NULL, 'David Miller', NULL, NULL, NULL, 'user', '2025-02-14 04:00:00', 1),
(25, 'test11@test.com', '$2y$10$gvpnAbQuji2saamUM1osvu6/pOFkfpO9tXmATIqwW2NEiTjJv692S', NULL, NULL, NULL, 0, NULL, NULL, 'test11', NULL, NULL, NULL, 'user', '2025-02-15 15:49:57', 1),
(26, 'test12@test.com', '$2y$10$R5sboKmuv3Y6nDVLr5J7yObyzlr7HlQR07ymjup/IKo6sp18mfn1.', NULL, NULL, NULL, 0, NULL, NULL, 'test12', NULL, NULL, NULL, 'user', '2025-02-15 15:56:23', 1),
(27, 'test13@test.com', '$2y$10$SFIsCbrrRukrJEN.fAtI0eZbV7sKl2XQRdunPu61ZigTOzRwD6m.y', NULL, NULL, NULL, 0, NULL, NULL, 'test13', NULL, NULL, NULL, 'user', '2025-02-15 16:04:03', 1),
(28, 'test1234@test.com', '$2y$10$/l9lsucYbUZazriGE0zL3uyGtqr4UFhhF8NG9RB.fODao94ixQpmm', NULL, NULL, NULL, 0, NULL, NULL, 'test1234', NULL, NULL, '11111111', 'user', '2025-03-02 12:04:42', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_comments`
--

CREATE TABLE `user_comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_comments`
--

INSERT INTO `user_comments` (`id`, `user_id`, `comment`, `created_at`) VALUES
(1, 9, 'testing', '2025-02-11 14:22:08'),
(2, 9, '<script>alert(\"Reflected-XSS Attack!\")</script>', '2025-02-13 00:50:57'),
(3, 9, '<script>alert(\"Reflected-XSS Attack!\")</script>', '2025-02-13 01:18:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_messages`
--

CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_messages`
--

INSERT INTO `user_messages` (`id`, `user_id`, `subject`, `message`, `created_at`, `status`) VALUES
(1, 9, 'test', 'testing', '2025-03-02 22:26:10', 'sent'),
(2, 9, 'test', 'testing', '2025-03-02 22:27:10', 'sent');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_comments`
--
ALTER TABLE `admin_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- Indexes for table `user_comments`
--
ALTER TABLE `user_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `admin_comments`
--
ALTER TABLE `admin_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `user_comments`
--
ALTER TABLE `user_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_messages`
--
ALTER TABLE `user_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `admin_comments`
--
ALTER TABLE `admin_comments`
  ADD CONSTRAINT `admin_comments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_comments_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_comments`
--
ALTER TABLE `user_comments`
  ADD CONSTRAINT `user_comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD CONSTRAINT `user_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
