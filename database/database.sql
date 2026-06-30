-- -- phpMyAdmin SQL Dump
-- -- version 5.2.1
-- -- https://www.phpmyadmin.net/
-- --
-- -- Host: 127.0.0.1
-- -- Generation Time: May 21, 2026 at 10:54 AM
-- -- Server version: 10.4.32-MariaDB
-- -- PHP Version: 8.2.12

-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- START TRANSACTION;
-- SET time_zone = "+00:00";


-- /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
-- /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
-- /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
-- /*!40101 SET NAMES utf8mb4 */;

-- --
-- -- Database: `lawyers_website`
-- --

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `admins`
-- --

-- CREATE TABLE `admins` (
--   `id` int(11) NOT NULL,
--   `username` varchar(50) NOT NULL,
--   `password` varchar(255) NOT NULL,
--   `name` varchar(100) NOT NULL,
--   `email` varchar(100) NOT NULL,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `appointments`
-- --

-- CREATE TABLE `appointments` (
--   `id` int(11) NOT NULL,
--   `lawyer_id` int(11) NOT NULL,
--   `customer_id` int(11) NOT NULL,
--   `slot_id` int(11) DEFAULT NULL,
--   `appointment_date` date NOT NULL,
--   `appointment_time` time NOT NULL,
--   `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
--   `booking_message` text DEFAULT NULL,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
--   `is_rescheduled` tinyint(1) DEFAULT 0,
--   `original_date` date DEFAULT NULL,
--   `original_time` time DEFAULT NULL,
--   `reschedule_count` int(11) DEFAULT 0
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `customers`
-- --

-- CREATE TABLE `customers` (
--   `id` int(11) NOT NULL,
--   `name` varchar(100) NOT NULL,
--   `email` varchar(100) NOT NULL,
--   `password` varchar(255) NOT NULL,
--   `phone` varchar(20) DEFAULT NULL,
--   `address` text DEFAULT NULL,
--   `city` varchar(50) DEFAULT NULL,
--   `reg_date` date DEFAULT NULL,
--   `status` enum('active','inactive') DEFAULT 'active'
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `homepage_content`
-- --

-- CREATE TABLE `homepage_content` (
--   `id` int(11) NOT NULL,
--   `section_type` varchar(50) NOT NULL,
--   `title` varchar(200) NOT NULL,
--   `description` text DEFAULT NULL,
--   `image` varchar(255) DEFAULT NULL,
--   `order_by` int(3) DEFAULT 0,
--   `status` tinyint(1) DEFAULT 1,
--   `updated_by` int(11) DEFAULT NULL,
--   `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `lawyers`
-- --

-- CREATE TABLE `lawyers` (
--   `id` int(11) NOT NULL,
--   `name` varchar(100) NOT NULL,
--   `email` varchar(100) NOT NULL,
--   `password` varchar(255) NOT NULL,
--   `phone` varchar(20) DEFAULT NULL,
--   `address` text DEFAULT NULL,
--   `city` varchar(50) DEFAULT NULL,
--   `specialization` varchar(50) DEFAULT NULL,
--   `gender` enum('male','female','other') DEFAULT 'male',
--   `experience` int(3) DEFAULT NULL,
--   `fees` decimal(10,2) DEFAULT NULL,
--   `profile_pic` varchar(255) DEFAULT NULL,
--   `bio` text DEFAULT NULL,
--   `avg_rating` decimal(2,1) DEFAULT 0.0,
--   `status` enum('pending','approved','rejected') DEFAULT 'pending',
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
--   `is_featured` tinyint(1) DEFAULT 0,
--   `core_specialization` text DEFAULT NULL,
--   `academic_credentials` text DEFAULT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `notifications`
-- --

-- CREATE TABLE `notifications` (
--   `id` int(11) NOT NULL,
--   `user_id` int(11) NOT NULL,
--   `user_type` enum('customer','lawyer','admin') NOT NULL,
--   `title` varchar(200) NOT NULL,
--   `message` text NOT NULL,
--   `is_read` tinyint(1) DEFAULT 0,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `payments`
-- --

-- CREATE TABLE `payments` (
--   `id` int(11) NOT NULL,
--   `appointment_id` int(11) NOT NULL,
--   `amount` decimal(10,2) NOT NULL,
--   `payment_method` varchar(50) DEFAULT 'cash',
--   `status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
--   `transaction_id` varchar(100) DEFAULT NULL,
--   `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `reviews`
-- --

-- CREATE TABLE `reviews` (
--   `id` int(11) NOT NULL,
--   `lawyer_id` int(11) NOT NULL,
--   `customer_id` int(11) NOT NULL,
--   `appointment_id` int(11) DEFAULT NULL,
--   `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
--   `comment` text DEFAULT NULL,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -- --------------------------------------------------------

-- --
-- -- Table structure for table `slots`
-- --

-- CREATE TABLE `slots` (
--   `id` int(11) NOT NULL,
--   `lawyer_id` int(11) NOT NULL,
--   `day_of_week` varchar(10) NOT NULL,
--   `start_time` time NOT NULL,
--   `end_time` time NOT NULL,
--   `is_available` tinyint(1) DEFAULT 1,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --
-- -- Indexes for dumped tables
-- --

-- --
-- -- Indexes for table `admins`
-- --
-- ALTER TABLE `admins`
--   ADD PRIMARY KEY (`id`);

-- --
-- -- Indexes for table `appointments`
-- --
-- ALTER TABLE `appointments`
--   ADD PRIMARY KEY (`id`),
--   ADD KEY `lawyer_id` (`lawyer_id`),
--   ADD KEY `customer_id` (`customer_id`),
--   ADD KEY `slot_id` (`slot_id`);

-- --
-- -- Indexes for table `customers`
-- --
-- ALTER TABLE `customers`
--   ADD PRIMARY KEY (`id`),
--   ADD UNIQUE KEY `email` (`email`);

-- --
-- -- Indexes for table `homepage_content`
-- --
-- ALTER TABLE `homepage_content`
--   ADD PRIMARY KEY (`id`),
--   ADD KEY `updated_by` (`updated_by`);

-- --
-- -- Indexes for table `lawyers`
-- --
-- ALTER TABLE `lawyers`
--   ADD PRIMARY KEY (`id`),
--   ADD UNIQUE KEY `email` (`email`);

-- --
-- -- Indexes for table `notifications`
-- --
-- ALTER TABLE `notifications`
--   ADD PRIMARY KEY (`id`);

-- --
-- -- Indexes for table `payments`
-- --
-- ALTER TABLE `payments`
--   ADD PRIMARY KEY (`id`),
--   ADD KEY `appointment_id` (`appointment_id`);

-- --
-- -- Indexes for table `reviews`
-- --
-- ALTER TABLE `reviews`
--   ADD PRIMARY KEY (`id`),
--   ADD KEY `lawyer_id` (`lawyer_id`),
--   ADD KEY `customer_id` (`customer_id`),
--   ADD KEY `appointment_id` (`appointment_id`);

-- --
-- -- Indexes for table `slots`
-- --
-- ALTER TABLE `slots`
--   ADD PRIMARY KEY (`id`),
--   ADD KEY `lawyer_id` (`lawyer_id`);

-- --
-- -- AUTO_INCREMENT for dumped tables
-- --

-- --
-- -- AUTO_INCREMENT for table `admins`
-- --
-- ALTER TABLE `admins`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `appointments`
-- --
-- ALTER TABLE `appointments`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `customers`
-- --
-- ALTER TABLE `customers`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `homepage_content`
-- --
-- ALTER TABLE `homepage_content`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `lawyers`
-- --
-- ALTER TABLE `lawyers`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `notifications`
-- --
-- ALTER TABLE `notifications`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `payments`
-- --
-- ALTER TABLE `payments`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `reviews`
-- --
-- ALTER TABLE `reviews`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- AUTO_INCREMENT for table `slots`
-- --
-- ALTER TABLE `slots`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --
-- -- Constraints for dumped tables
-- --

-- --
-- -- Constraints for table `appointments`
-- --
-- ALTER TABLE `appointments`
--   ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`) ON DELETE SET NULL;

-- --
-- -- Constraints for table `homepage_content`
-- --
-- ALTER TABLE `homepage_content`
--   ADD CONSTRAINT `homepage_content_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- --
-- -- Constraints for table `payments`
-- --
-- ALTER TABLE `payments`
--   ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

-- --
-- -- Constraints for table `reviews`
-- --
-- ALTER TABLE `reviews`
--   ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

-- --
-- -- Constraints for table `slots`
-- --
-- ALTER TABLE `slots`
--   ADD CONSTRAINT `slots_ibfk_1` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`id`) ON DELETE CASCADE;
-- COMMIT;

-- /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
-- /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
-- /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
