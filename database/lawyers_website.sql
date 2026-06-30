-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 10:55 PM
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
-- Database: `lawyers_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `name`, `email`, `created_at`) VALUES
(2, 'admin', 'admin123', 'Super Admin', 'admin@lawyers.com', '2026-05-19 12:20:34');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `lawyer_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `booking_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_rescheduled` tinyint(1) DEFAULT 0,
  `original_date` date DEFAULT NULL,
  `original_time` time DEFAULT NULL,
  `reschedule_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `lawyer_id`, `customer_id`, `slot_id`, `appointment_date`, `appointment_time`, `status`, `booking_message`, `created_at`, `is_rescheduled`, `original_date`, `original_time`, `reschedule_count`) VALUES
(1, 1, 1, 1, '2026-05-25', '10:30:00', 'confirmed', 'Need help with bail application', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(2, 2, 2, 3, '2026-05-26', '11:00:00', 'pending', 'Divorce consultation', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(3, 3, 3, 5, '2026-05-27', '15:30:00', 'confirmed', 'Contract review needed', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(4, 1, 4, 2, '2026-05-28', '14:00:00', 'completed', 'Criminal case consultation', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(5, 4, 5, 7, '2026-05-29', '11:30:00', 'cancelled', 'Property registration query', '2026-05-21 09:17:46', 1, '2026-05-28', '11:00:00', 1),
(6, 5, 6, 9, '2026-05-30', '14:30:00', 'confirmed', 'Cyber crime complaint', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(7, 6, 7, 11, '2026-05-31', '10:00:00', 'pending', 'Family mediation needed', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(8, 7, 8, 13, '2026-06-01', '11:00:00', 'confirmed', 'Corporate compliance advice', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(9, 8, 9, 15, '2026-06-02', '09:30:00', 'completed', 'Property title verification', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(10, 9, 10, 17, '2026-06-03', '12:00:00', 'confirmed', 'Criminal appeal consultation', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(11, 10, 1, 19, '2026-06-04', '10:00:00', 'pending', 'Child custody case', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(12, 11, 2, 21, '2026-06-05', '11:30:00', 'confirmed', 'Merger agreement review', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(13, 12, 3, 23, '2026-06-06', '15:00:00', 'completed', 'Land dispute consultation', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(14, 2, 4, 4, '2026-06-07', '10:30:00', 'confirmed', 'Divorce filing assistance', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(15, 3, 5, 6, '2026-06-08', '11:00:00', 'pending', 'Contract drafting required', '2026-05-21 09:17:46', 0, NULL, NULL, 0),
(31, 11, 16, NULL, '2026-05-27', '11:00:00', 'pending', 'its urgent', '2026-05-21 10:22:20', 0, NULL, NULL, 0),
(32, 11, 16, NULL, '2026-05-30', '11:00:00', 'pending', '', '2026-05-21 10:27:46', 0, NULL, NULL, 0),
(33, 13, 16, NULL, '2026-05-25', '13:30:00', 'confirmed', '', '2026-05-21 10:39:18', 0, NULL, NULL, 0),
(34, 11, 2, NULL, '2026-06-17', '10:30:00', 'pending', '', '2026-06-12 21:26:12', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `reg_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `reg_date`, `status`) VALUES
(1, 'John Doe', 'john@example.com', 'password123', '9876543210', '123 Main St', 'Mumbai', '2025-01-15', 'active'),
(2, 'Sarah Wilson', 'sarah@example.com', 'password123', '9876543211', '456 Park Ave', 'Delhi', '2025-02-20', 'active'),
(3, 'Michael Brown', 'michael@example.com', 'password123', '9876543212', '789 Lake Rd', 'Bangalore', '2025-03-10', 'active'),
(4, 'Emily Davis', 'emily@example.com', 'password123', '9876543213', '321 Hill St', 'Chennai', '2025-04-05', 'active'),
(5, 'David Miller', 'david@example.com', 'password123', '9876543214', '654 Oak Ln', 'Kolkata', '2025-05-01', 'inactive'),
(6, 'Priya Kapoor', 'priya.k@example.com', 'password123', '9876543215', '123 Rose Garden', 'Pune', '2025-05-10', 'active'),
(7, 'Rahul Mehta', 'rahul.m@example.com', 'password123', '9876543216', '456 Green Street', 'Ahmedabad', '2025-05-15', 'active'),
(8, 'Anita Sharma', 'anita.s@example.com', 'password123', '9876543217', '789 Blue Lagoon', 'Hyderabad', '2025-05-20', 'active'),
(9, 'Vikram Singh', 'vikram.s@example.com', 'password123', '9876543218', '321 Golden Avenue', 'Jaipur', '2025-05-25', 'active'),
(10, 'Neha Gupta', 'neha.g@example.com', 'password123', '9876543219', '654 Silver Street', 'Lucknow', '2025-06-01', 'active'),
(16, 'sk', 'sk@gmail.com', '$2y$10$7Tn4CEa30ksOQNZK8rxQSODUeoBeSXL2bRYxZcKndL6EnW9bm7.H6', NULL, NULL, NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_content`
--

CREATE TABLE `homepage_content` (
  `id` int(11) NOT NULL,
  `section_type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `order_by` int(3) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homepage_content`
--

INSERT INTO `homepage_content` (`id`, `section_type`, `title`, `description`, `image`, `order_by`, `status`, `updated_by`, `updated_at`) VALUES
(1, 'hero', 'Welcome to Lawyers Portal', 'Find the best lawyers for your legal needs', NULL, 1, 1, NULL, '2026-05-14 11:28:55'),
(2, 'featured_services', 'Our Legal Services', 'Expert lawyers at your service', NULL, 2, 1, NULL, '2026-05-14 11:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `lawyers`
--

CREATE TABLE `lawyers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `specialization` varchar(50) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'male',
  `experience` int(3) DEFAULT NULL,
  `fees` decimal(10,2) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avg_rating` decimal(2,1) DEFAULT 0.0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_featured` tinyint(1) DEFAULT 0,
  `core_specialization` text DEFAULT NULL,
  `academic_credentials` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lawyers`
--

INSERT INTO `lawyers` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `specialization`, `gender`, `experience`, `fees`, `profile_pic`, `bio`, `avg_rating`, `status`, `created_at`, `is_featured`, `core_specialization`, `academic_credentials`) VALUES
(1, 'Adv. Rajesh Sharma', 'rajesh@lawfirm.com', 'password123', '9988776655', '101 Legal Chambers', 'Mumbai', 'Criminal Law', 'male', 15, 5000.00, 'male_lawyer_1.jpg', 'Senior criminal lawyer with 15+ years experience in high court.', 4.5, 'approved', '2026-05-21 09:14:11', 1, 'Criminal Defense, Bail Applications', 'LL.M. Mumbai University'),
(2, 'Adv. Priya Mehta', 'priya@lawfirm.com', 'password123', '9988776644', '202 Justice Tower', 'Delhi', 'Family Law', 'female', 10, 4000.00, 'female_lawyer_1.jpg', 'Expert in divorce, child custody, and domestic violence cases.', 4.8, 'approved', '2026-05-21 09:14:11', 1, 'Divorce, Child Custody', 'LL.B. Delhi University'),
(3, 'Adv. Amit Singh', 'amit@lawfirm.com', 'password123', '9988776633', '303 Legal Hub', 'Bangalore', 'Corporate Law', 'male', 8, 6000.00, 'male_lawyer_2.jpg', 'Corporate lawyer specializing in contracts and mergers.', 4.2, 'approved', '2026-05-21 09:14:11', 0, 'Contract Drafting, M&A', 'LL.M. NLSIU Bangalore'),
(4, 'Adv. Neha Gupta', 'neha@lawfirm.com', 'password123', '9988776622', '404 Law House', 'Chennai', 'Property Law', 'female', 12, 4500.00, 'female_lawyer_2.jpg', 'Property disputes, registration, and land acquisition expert.', 4.6, 'approved', '2026-05-21 09:14:11', 1, 'Property Disputes, Land Acquisition', 'LL.B. Madras University'),
(5, 'Adv. Vikram Patel', 'vikram@lawfirm.com', 'password123', '9988776611', '505 Legal Eagle', 'Ahmedabad', 'Criminal Law', 'male', 6, 3500.00, 'male_lawyer_3.jpg', 'Young criminal lawyer specializing in cyber crimes.', 4.0, 'pending', '2026-05-21 09:14:11', 0, 'Cyber Crime, Criminal Defense', 'LL.B. Gujarat University'),
(6, 'Adv. Sneha Reddy', 'sneha@lawfirm.com', 'password123', '9988776600', '606 Legal Heights', 'Hyderabad', 'Family Law', 'female', 9, 4200.00, 'female_lawyer_3.jpg', 'Expert in family disputes, mediation, and settlement agreements.', 4.7, 'approved', '2026-05-21 09:14:11', 1, 'Mediation, Family Disputes', 'LL.M. NALSAR Hyderabad'),
(7, 'Adv. Arjun Nair', 'arjun@lawfirm.com', 'password123', '9988776599', '707 Law Plaza', 'Kochi', 'Corporate Law', 'male', 11, 5500.00, 'male_lawyer_4.jpg', 'Specializes in corporate compliance and business litigation.', 4.4, 'approved', '2026-05-21 09:14:11', 0, 'Corporate Compliance, Business Litigation', 'LL.B. NUJS Kolkata'),
(8, 'Adv. Divya Desai', 'divya@lawfirm.com', 'password123', '9988776588', '808 Legal Avenue', 'Ahmedabad', 'Property Law', 'female', 7, 3800.00, 'female_lawyer_4.jpg', 'Property registration, title verification, and dispute resolution.', 4.3, 'approved', '2026-05-21 09:14:11', 0, 'Title Verification, Property Disputes', 'LL.B. Gujarat University'),
(9, 'Adv. Rohan Verma', 'rohan@lawfirm.com', 'password123', '9988776577', '909 Law Chamber', 'Lucknow', 'Criminal Law', 'male', 13, 4800.00, 'male_lawyer_5.jpg', 'Senior criminal lawyer specializing in trial court litigation.', 4.6, 'approved', '2026-05-21 09:14:11', 1, 'Trial Court Litigation, Criminal Appeals', 'LL.M. Lucknow University'),
(10, 'Adv. Anjali Kapoor', 'anjali@lawfirm.com', 'password123', '9988776566', '1010 Legal Circle', 'Jaipur', 'Family Law', 'female', 5, 3200.00, 'female_lawyer_5.jpg', 'Focuses on child custody, guardianship, and adoption cases.', 4.1, 'approved', '2026-05-21 09:14:11', 0, 'Child Custody, Adoption', 'LL.B. Rajasthan University'),
(11, 'Adv. Karthik Iyer', 'karthik@lawfirm.com', 'password123', '9988776555', '1111 Law Square', 'Pune', 'Corporate Law', 'male', 14, 6500.00, 'male_lawyer_6.jpg', 'Expert in mergers, acquisitions, and intellectual property law.', 4.9, 'approved', '2026-05-21 09:14:11', 1, 'Mergers & Acquisitions, IP Law', 'LL.M. ILS Pune'),
(12, 'Adv. Meera Nair', 'meera@lawfirm.com', 'password123', '9988776544', '1212 Legal Tower', 'Thiruvananthapuram', 'Property Law', 'female', 10, 4300.00, 'female_lawyer_6.jpg', 'Land acquisition, real estate disputes, and property inheritance.', 4.5, 'pending', '2026-05-21 09:14:11', 0, 'Land Acquisition, Real Estate Disputes', 'LL.M. Kerala University'),
(13, 'imrankhan', 'imrankhan@gmail.com', '$2y$10$QRbOMEzHai1Ia1M3ngLrbuh4Pm5pp0MYiH19ObJ39h3bG3DGD74xW', '03112272744', NULL, 'karachi', 'Criminal', 'male', 10, 10000.00, 'lawyer6.jpg', 'experience lawyers with vast experience in handling criminal cases proficient and successful ', 0.0, 'approved', '2026-05-21 10:30:50', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('customer','lawyer','admin') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'customer', 'Appointment Confirmed', 'Your appointment with Adv. Rajesh Sharma is confirmed for May 25, 2026.', 0, '2026-05-21 09:19:33'),
(2, 2, 'customer', 'Payment Reminder', 'Please complete payment before your appointment.', 0, '2026-05-21 09:19:33'),
(3, 1, 'lawyer', 'New Appointment', 'You have a new appointment with John Doe.', 1, '2026-05-21 09:19:33'),
(4, 2, 'lawyer', 'Appointment Status', 'Your appointment with Sarah Wilson is pending confirmation.', 0, '2026-05-21 09:19:33'),
(5, 1, 'admin', 'New Lawyer Registration', 'Adv. Vikram Patel has registered and pending approval.', 0, '2026-05-21 09:19:33'),
(6, 6, 'customer', 'Appointment Confirmed', 'Your appointment with Adv. Sneha Reddy is confirmed.', 0, '2026-05-21 09:19:33'),
(7, 7, 'customer', 'Appointment Reminder', 'Reminder: You have an appointment tomorrow.', 0, '2026-05-21 09:19:33'),
(8, 3, 'lawyer', 'New Appointment', 'You have a new appointment with Michael Brown.', 0, '2026-05-21 09:19:33'),
(9, 8, 'customer', 'Payment Received', 'Your payment of ₹5500 has been received.', 1, '2026-05-21 09:19:33'),
(10, 4, 'lawyer', 'Review Received', 'You received a new 5-star review!', 0, '2026-05-21 09:19:33'),
(11, 9, 'customer', 'Appointment Completed', 'Your appointment with Adv. Divya Desai is completed.', 0, '2026-05-21 09:19:33'),
(12, 5, 'lawyer', 'Cancellation Notice', 'An appointment has been cancelled.', 1, '2026-05-21 09:19:33'),
(13, 10, 'customer', 'Appointment Confirmed', 'Your appointment with Adv. Rohan Verma is confirmed.', 0, '2026-05-21 09:19:33'),
(14, 11, 'lawyer', 'New Appointment', 'You have a new appointment with Sarah Wilson.', 0, '2026-05-21 09:19:33'),
(15, 12, 'lawyer', 'Payment Alert', 'Payment of ₹4300 received for appointment #13.', 0, '2026-05-21 09:19:33'),
(20, 11, 'lawyer', 'New Appointment Request', 'Customer sk requested appointment on 27 May 2026 at 11:00 AM', 0, '2026-05-21 10:22:20'),
(21, 11, 'lawyer', 'New Appointment Request', 'Customer sk requested appointment on 30 May 2026 at 11:00 AM', 0, '2026-05-21 10:27:46'),
(22, 13, 'lawyer', 'Profile Approved', 'Dear imrankhan, your lawyer profile has been approved. You can now login and start accepting appointments.', 0, '2026-05-21 10:33:53'),
(23, 13, 'lawyer', 'New Appointment Request', 'Customer sk requested appointment on 25 May 2026 at 01:30 PM', 0, '2026-05-21 10:39:18'),
(24, 11, 'lawyer', 'New Appointment Request', 'Customer sania requested appointment on 17 Jun 2026 at 10:30 AM', 0, '2026-06-12 21:26:12');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `appointment_id`, `amount`, `payment_method`, `status`, `transaction_id`, `payment_date`) VALUES
(1, 1, 5000.00, 'online', 'paid', 'TXN1001', '2026-05-21 09:18:08'),
(2, 2, 4000.00, 'cash', 'pending', NULL, '2026-05-21 09:18:08'),
(3, 3, 6000.00, 'online', 'paid', 'TXN1002', '2026-05-21 09:18:08'),
(4, 4, 5000.00, 'cash', 'paid', NULL, '2026-05-21 09:18:08'),
(5, 5, 4500.00, 'online', 'refunded', 'TXN1003', '2026-05-21 09:18:08'),
(6, 6, 3500.00, 'online', 'paid', 'TXN1004', '2026-05-21 09:18:08'),
(7, 7, 4200.00, 'cash', 'pending', NULL, '2026-05-21 09:18:08'),
(8, 8, 5500.00, 'online', 'paid', 'TXN1005', '2026-05-21 09:18:08'),
(9, 9, 3800.00, 'cash', 'paid', NULL, '2026-05-21 09:18:08'),
(10, 10, 4800.00, 'online', 'paid', 'TXN1006', '2026-05-21 09:18:08'),
(11, 11, 3200.00, 'cash', 'pending', NULL, '2026-05-21 09:18:08'),
(12, 12, 6500.00, 'online', 'paid', 'TXN1007', '2026-05-21 09:18:08'),
(13, 13, 4300.00, 'online', 'paid', 'TXN1008', '2026-05-21 09:18:08'),
(14, 14, 4000.00, 'cash', 'paid', NULL, '2026-05-21 09:18:08'),
(15, 15, 6000.00, 'online', 'pending', 'TXN1009', '2026-05-21 09:18:08');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `lawyer_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `lawyer_id`, `customer_id`, `appointment_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 1, 1, 5, 'Excellent lawyer! Got my bail approved quickly.', '2026-05-21 09:18:44'),
(2, 2, 2, 2, 4, 'Good advice on divorce case.', '2026-05-21 09:18:44'),
(3, 3, 3, 3, 5, 'Very professional and detailed contract review.', '2026-05-21 09:18:44'),
(4, 1, 4, 4, 4, 'Knowledgeable but took time to respond.', '2026-05-21 09:18:44'),
(5, 4, 5, 5, 3, 'Cancelled but was polite.', '2026-05-21 09:18:44'),
(6, 5, 6, 6, 5, 'Helped me with cyber crime case efficiently.', '2026-05-21 09:18:44'),
(7, 6, 7, 7, 4, 'Good mediator, resolved family dispute.', '2026-05-21 09:18:44'),
(8, 7, 8, 8, 5, 'Excellent corporate advice!', '2026-05-21 09:18:44'),
(9, 8, 9, 9, 4, 'Property verification done smoothly.', '2026-05-21 09:18:44'),
(10, 9, 10, 10, 5, 'Best criminal lawyer in town!', '2026-05-21 09:18:44'),
(11, 11, 2, 12, 5, 'Karthik is amazing at M&A deals.', '2026-05-21 09:18:44'),
(12, 12, 3, 13, 4, 'Good knowledge of property laws.', '2026-05-21 09:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `slots`
--

CREATE TABLE `slots` (
  `id` int(11) NOT NULL,
  `lawyer_id` int(11) NOT NULL,
  `day_of_week` varchar(10) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slots`
--

INSERT INTO `slots` (`id`, `lawyer_id`, `day_of_week`, `start_time`, `end_time`, `is_available`, `created_at`) VALUES
(1, 1, 'Monday', '10:00:00', '13:00:00', 1, '2026-05-21 09:17:11'),
(2, 1, 'Wednesday', '14:00:00', '17:00:00', 1, '2026-05-21 09:17:11'),
(3, 2, 'Tuesday', '11:00:00', '14:00:00', 1, '2026-05-21 09:17:11'),
(4, 2, 'Friday', '10:00:00', '13:00:00', 1, '2026-05-21 09:17:11'),
(5, 3, 'Monday', '15:00:00', '18:00:00', 1, '2026-05-21 09:17:11'),
(6, 3, 'Thursday', '10:00:00', '13:00:00', 1, '2026-05-21 09:17:11'),
(7, 4, 'Wednesday', '11:00:00', '15:00:00', 1, '2026-05-21 09:17:11'),
(8, 4, 'Saturday', '10:00:00', '13:00:00', 1, '2026-05-21 09:17:11'),
(9, 5, 'Tuesday', '14:00:00', '17:00:00', 1, '2026-05-21 09:17:11'),
(10, 5, 'Friday', '11:00:00', '14:00:00', 1, '2026-05-21 09:17:11'),
(11, 6, 'Monday', '09:00:00', '12:00:00', 1, '2026-05-21 09:17:11'),
(12, 6, 'Thursday', '14:00:00', '17:00:00', 1, '2026-05-21 09:17:11'),
(13, 7, 'Tuesday', '10:00:00', '13:00:00', 1, '2026-05-21 09:17:11'),
(14, 7, 'Friday', '15:00:00', '18:00:00', 1, '2026-05-21 09:17:11'),
(15, 8, 'Wednesday', '09:00:00', '12:00:00', 1, '2026-05-21 09:17:11'),
(16, 8, 'Saturday', '14:00:00', '17:00:00', 1, '2026-05-21 09:17:11'),
(17, 9, 'Monday', '11:00:00', '14:00:00', 1, '2026-05-21 09:17:11'),
(18, 9, 'Thursday', '15:00:00', '18:00:00', 1, '2026-05-21 09:17:11'),
(19, 10, 'Tuesday', '09:00:00', '12:00:00', 1, '2026-05-21 09:17:11'),
(20, 10, 'Friday', '13:00:00', '16:00:00', 1, '2026-05-21 09:17:11'),
(21, 11, 'Wednesday', '10:00:00', '13:00:00', 1, '2026-05-21 09:17:11'),
(22, 11, 'Saturday', '11:00:00', '14:00:00', 1, '2026-05-21 09:17:11'),
(23, 12, 'Monday', '14:00:00', '17:00:00', 1, '2026-05-21 09:17:11'),
(24, 12, 'Thursday', '09:00:00', '12:00:00', 1, '2026-05-21 09:17:11'),
(25, 13, 'Monday', '13:00:00', '20:00:00', 1, '2026-05-21 10:38:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lawyer_id` (`lawyer_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `slot_id` (`slot_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `homepage_content`
--
ALTER TABLE `homepage_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `lawyers`
--
ALTER TABLE `lawyers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lawyer_id` (`lawyer_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `slots`
--
ALTER TABLE `slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lawyer_id` (`lawyer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `homepage_content`
--
ALTER TABLE `homepage_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lawyers`
--
ALTER TABLE `lawyers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `slots`
--
ALTER TABLE `slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `homepage_content`
--
ALTER TABLE `homepage_content`
  ADD CONSTRAINT `homepage_content_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `slots`
--
ALTER TABLE `slots`
  ADD CONSTRAINT `slots_ibfk_1` FOREIGN KEY (`lawyer_id`) REFERENCES `lawyers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
