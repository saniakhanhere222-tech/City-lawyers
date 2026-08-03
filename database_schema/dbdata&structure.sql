-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2026 at 01:52 PM
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
-- Database: `legalflow`
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
(1, 'admin', '123', 'Administrator', 'admin@citylawyers.com', '2026-06-10 21:00:34');

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
(1, 6, 2, NULL, '2026-06-26', '15:00:00', 'pending', 'i want an urgent booking', '2026-06-12 21:13:47', 0, NULL, NULL, 0),
(2, 12, 2, NULL, '2026-06-18', '12:00:00', 'pending', 'Need consultation before hiring officially', '2026-06-12 21:16:47', 0, NULL, NULL, 0),
(3, 12, 2, NULL, '2026-07-31', '14:30:00', 'pending', '', '2026-06-12 21:23:00', 0, NULL, NULL, 1),
(5, 3, 2, NULL, '2026-06-18', '15:00:00', 'pending', '', '2026-06-12 21:33:32', 0, NULL, NULL, 0),
(6, 3, 2, NULL, '2026-06-24', '16:00:00', 'pending', '', '2026-06-12 21:35:15', 0, NULL, NULL, 0),
(7, 4, 2, NULL, '2026-06-18', '14:30:00', 'pending', '', '2026-06-12 21:40:09', 0, NULL, NULL, 0),
(9, 1, 2, NULL, '2026-07-04', '10:00:00', 'completed', 'require an urgent consultation', '2026-07-03 15:09:25', 0, NULL, NULL, 0),
(11, 1, 2, NULL, '2026-07-06', '11:00:00', 'completed', 'urgent', '2026-07-04 12:38:49', 0, NULL, NULL, 0),
(12, 6, 4, NULL, '2026-07-29', '13:30:00', 'pending', 'contact please', '2026-07-28 18:26:32', 0, NULL, NULL, 0),
(13, 6, 2, NULL, '2026-07-30', '15:00:00', 'pending', '', '2026-07-28 20:37:39', 0, NULL, NULL, 0),
(14, 1, 2, NULL, '2026-07-30', '13:30:00', 'confirmed', 'please contact its urgent', '2026-07-29 15:14:29', 0, NULL, NULL, 0),
(15, 1, 5, NULL, '2026-07-30', '16:00:00', 'pending', 'need a consultation appointment in aweek or next', '2026-07-29 15:20:23', 0, NULL, NULL, 0),
(16, 1, 4, NULL, '2026-07-30', '15:30:00', 'pending', 'hello lawyer. please confirm appointment', '2026-07-29 15:22:20', 0, NULL, NULL, 0),
(17, 1, 2, NULL, '2026-07-30', '11:00:00', 'pending', 'urgent', '2026-07-29 20:45:51', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon_class` varchar(100) NOT NULL DEFAULT 'fas fa-gavel',
  `status` enum('active','inactive') DEFAULT 'active',
  `order_by` int(3) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon_class`, `status`, `order_by`, `created_at`) VALUES
(1, 'Family Law', 'fas fa-home', 'inactive', 1, '2026-07-29 18:39:59'),
(2, 'Divorce', 'fas fa-heart-crack', 'active', 1, '2026-07-29 19:11:12'),
(3, 'Criminal', 'fas fa-scale-balanced', 'active', 2, '2026-07-29 19:11:12'),
(4, 'Affidavit', 'fas fa-file-signature', 'active', 3, '2026-07-29 19:11:12'),
(5, 'Civil', 'fas fa-landmark', 'active', 4, '2026-07-29 19:11:12');

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
(1, 'Test Customer', 'sk@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03001234567', 'Test Address', 'Karachi', '2026-06-10', 'active'),
(2, 'sania', 'sania@gmail.com', '$2y$10$xExUEuzXzmbucKT121Q.ceHV3zoZR7Z9Yn6PoXwPhIAFTPYOIKFIS', '03112272744', 'gulshan e maymar w-5 karachi', 'karachi', '2026-06-10', 'active'),
(3, 'imran khan', 'imrankhan@gmail.com', '$2y$10$9DUUw0mbEMN5A.VlzBy0/u0IQIedFMMrzsS5lU.I/BDDqFUiso0gW', '03112272744', 'bani-gala peshawar near sky rise mountains', 'karachi', '2026-06-11', 'active'),
(4, 'Shahzaib Khan', 'khan@gmail.com', '$2y$10$z1gr.sFNcsGDuC6hEinsbOm4/Cqf9zM0C5.8EZWgp07jE64Ba2mJW', '03112272744', 'gulshan e maymar w-5 karachi', 'lahore', '2026-06-11', 'active'),
(5, 'duafidahussain', 'duafidahussain@gmail.com', '$2y$10$pQmZqsWgiaCSeDAVO80EVOShZ6ii6lkieBAaY9KKL6xhYDJdjFjse', '03112272744', 'gulshan e maymar w-5 karachi', 'karachi', '2026-07-01', 'active');

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
(1, 'Imran khan', 'khan@gmail.com', '$2y$10$eIO11.k288d7tlirRNvtz.SI1pPSQcbfSaVopoeQe9i6aGLOaYyAq', '03112272744', NULL, 'karachi', 'Divorce', 'male', 10, 10000.00, '1785339216_6a6a1d50935fa.jpeg', 'expert in criminal and affifdavit trials', 4.0, 'approved', '2026-06-10 21:43:39', 0, 'Criminal Defense, Bail, Appeals', 'LL.B (Punjab University), LL.M (UK)'),
(2, 'Ali ahmed', 'aliahmedkhan@gmail.com', '$2y$10$dD3BGbhpsX2kfWFTq9BuruLfKB7Y5SRDl/2DcaItYY5lz6BcTHy1e', '03112272744', NULL, 'multan', 'Divorce', 'male', 6, 7000.00, 'lawyer4.jpg', 'dedicated lawyer expert in divorce and martial cases vast experience', 0.0, 'approved', '2026-06-10 22:09:34', 0, 'Family Law, Divorce Mediation, Child Custody', 'LL.B (LCWU), Diploma in Family Law'),
(3, 'Adv. Ahmad Raza', 'ahmad.raza@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03001234567', '123 Main St', 'Karachi', 'Criminal', 'male', 12, 8000.00, 'malelawyer1.jpg', 'Experienced criminal defense lawyer with over a decade of courtroom success.', 4.8, 'approved', '2026-06-12 15:47:19', 0, 'Affidavit Drafting, Notarization, Legal Documentation', 'LL.B (BZU)'),
(4, 'Adv. Bilal Ahmed', 'bilal.ahmed@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03011234567', '456 Liberty Rd', 'Lahore', 'Divorce', 'male', 8, 7000.00, 'malelawyer2.jpg', 'Specializes in family and divorce law, known for compassionate yet assertive representation.', 4.6, 'approved', '2026-06-12 15:47:19', 0, 'Civil Litigation, Property Disputes, Contract Law', 'LL.B (Punjab University), LL.M (Civil Law)'),
(5, 'Adv. Danish Khan', 'danish.khan@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03021234567', '789 Iqbal Ave', 'Islamabad', 'Affidavit', 'male', 5, 5000.00, 'malelawyer3.jpg', 'Efficient in drafting affidavits, powers of attorney, and notarized documents.', 4.5, 'approved', '2026-06-12 15:47:19', 1, 'Criminal Defense, Bail Applications', 'LL.B (GCU)'),
(6, 'Adv. Farhan Ali', 'farhan.ali@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03031234567', '101 Jinnah Blvd', 'Rawalpindi', 'Civil', 'male', 15, 10000.00, 'malelawyer4.jpg', 'Senior civil litigator handling property, contract, and tort disputes.', 4.9, 'approved', '2026-06-12 15:47:19', 1, 'Divorce, Alimony, Custody Battles', 'LL.B (Punjab University), Mediation Certification'),
(7, 'Adv. Hamza Malik', 'hamza.malik@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03041234567', '202 Faisal St', 'Multan', 'Criminal', 'male', 6, 6000.00, 'malelawyer5.jpg', 'Young but highly skilled criminal lawyer with strong track record in bail cases.', 4.4, 'approved', '2026-06-12 15:47:19', 0, 'Affidavit Preparation, Legal Oaths', 'LL.B (University of Peshawar)'),
(8, 'Adv. Imran Hashmi', 'imran.hashmi@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03051234567', '303 Garden Town', 'Peshawar', 'Divorce', 'male', 9, 7500.00, 'malelawyer6.jpg', 'Provides mediation and representation in complex divorce and custody cases.', 4.7, 'approved', '2026-06-12 15:47:19', 0, 'Civil Suits, Property Rights, Contract Drafting', 'LL.B (Kinnaird), LL.M (Civil)'),
(9, 'Adv. Junaid Siddiqui', 'junaid.siddiqui@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03061234567', '404 Shahrah-e-Islam', 'Quetta', 'Affidavit', 'male', 4, 4500.00, 'malelawyer7.jpg', 'Affordable and efficient affidavit services for court and official use.', 4.2, 'approved', '2026-06-12 15:47:19', 0, 'Criminal Defense, Women\'s Safety Law, Bail', 'LL.B (LCWU), Criminal Law Diploma'),
(10, 'Adv. Ayesha Tariq', 'ayesha.tariq@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03101234567', '505 Clifton', 'Karachi', 'Civil', 'female', 10, 9000.00, 'femalelawyer8.jpg', 'Expert in civil litigation with a focus on women’s property rights.', 4.8, 'approved', '2026-06-12 15:47:19', 0, 'Divorce Mediation, Family Disputes, Maintenance', 'LL.B (Punjab University), LL.M (Family Law)'),
(11, 'Adv. Bushra Javed', 'bushra.javed@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03111234567', '606 DHA Phase 2', 'Lahore', 'Criminal', 'female', 7, 6500.00, 'femalelawyer9.jpg', 'Criminal defense lawyer known for strong cross-examination skills.', 4.6, 'approved', '2026-06-12 15:47:19', 1, 'Affidavit Drafting, Legal Declarations', 'LL.B (Sargodha Uni)'),
(12, 'Adv. Durr-e-Shehwar', 'durr.shehwar@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03121234567', '707 G-10 Markaz', 'Islamabad', 'Divorce', 'female', 11, 8500.00, 'femalelawyer10.jpg', 'Sensitive and strategic divorce lawyer, handles high-conflict separations.', 4.9, 'approved', '2026-06-12 15:47:19', 0, 'Criminal Appeals, Juvenile Justice', 'LL.B (GCU), Criminology Certificate'),
(13, 'Adv. Fatima Zafar', 'fatima.zafar@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03131234567', '808 Saddar', 'Rawalpindi', 'Affidavit', 'female', 3, 4000.00, 'femalelawyer11.jpg', 'Fast and accurate affidavit and legal document drafting.', 4.3, 'approved', '2026-06-12 15:47:19', 0, 'Property Disputes, Contract Enforcement', 'LL.B (BZU), Civil Law Diploma'),
(14, 'Adv. Hira Tariq', 'hira.tariq@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03141234567', '909 Cantt', 'Multan', 'Criminal', 'female', 6, 5500.00, 'femalelawyer12.jpg', 'Committed criminal lawyer with expertise in juvenile justice.', 4.5, 'approved', '2026-06-12 15:47:19', 1, 'Divorce, Child Custody, Dowry Cases', 'LL.B (Kinnaird), Family Law Diploma'),
(15, 'Adv. Iqra Noor', 'iqra.noor@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03151234567', '1010 University Rd', 'Peshawar', 'Civil', 'female', 8, 7000.00, 'femalelawyer13.jpg', 'Handles civil suits, contract disputes, and land revenue cases.', 4.7, 'approved', '2026-06-12 15:47:19', 0, NULL, NULL),
(16, 'Adv. Komal Rizwan', 'komal.rizwan@legalfirm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03161234567', '1111 Satellite Town', 'Quetta', 'Divorce', 'female', 5, 5000.00, 'femalelawyer14.jpg', 'Empathetic divorce lawyer offering mediation and legal aid.', 4.4, 'approved', '2026-06-12 15:47:19', 0, NULL, NULL),
(17, 'Imrankhanniazi', 'imrankhanniazi@gail.com', '$2y$10$7N.vFmti19PoTqrOn6O5h.bMi5whdICAnfWe2Ti0ITuLhKxY5xu32', '03112272744', NULL, 'karachi', 'Civil', 'male', 10, 10000.00, '—Pngtree—user profile avatar_13369988.png', 'Expert in civil matters, also an advisory or consultant on affidavit affairs', 0.0, 'approved', '2026-06-12 16:15:01', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('customer','lawyer') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_type` enum('customer','lawyer') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `appointment_id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`, `message`, `is_read`, `created_at`) VALUES
(1, 11, 2, 'customer', 1, 'lawyer', 'urgent', 1, '2026-07-06 14:42:11'),
(2, 11, 2, 'customer', 1, 'lawyer', 'hello lawywer', 1, '2026-07-06 14:50:34'),
(3, 11, 2, 'customer', 1, 'lawyer', 'when is my slot', 1, '2026-07-06 14:50:54'),
(4, 11, 2, 'customer', 1, 'lawyer', 'hello', 1, '2026-07-06 15:33:16'),
(5, 11, 2, 'customer', 1, 'lawyer', 'please reply', 1, '2026-07-06 16:35:34'),
(6, 11, 1, 'lawyer', 2, 'customer', 'your booking is scheduled on this  sat 11 am sharp', 0, '2026-07-06 18:58:49'),
(7, 11, 1, 'lawyer', 2, 'customer', 'hello', 0, '2026-07-06 19:17:00'),
(8, 11, 1, 'lawyer', 2, 'customer', 'please reply', 0, '2026-07-06 19:24:24'),
(9, 9, 2, 'customer', 1, 'lawyer', 'require an urgent consultation', 1, '2026-07-06 22:45:13'),
(10, 3, 2, 'customer', 12, 'lawyer', 'hello lawyer', 0, '2026-07-27 18:50:30'),
(11, 3, 2, 'customer', 12, 'lawyer', 'please confirm my appointment asap. Thankyou!', 0, '2026-07-28 21:05:30'),
(12, 15, 5, 'customer', 1, 'lawyer', 'need a consultation appointment in aweek or next', 1, '2026-07-29 16:58:09'),
(13, 15, 1, 'lawyer', 5, 'customer', 'confirm you today Thanks for your patience!', 0, '2026-07-29 16:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('customer','lawyer','admin') NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_type`, `type`, `title`, `message`, `link`, `icon`, `is_read`, `created_at`) VALUES
(1, 1, 'lawyer', 'general', 'Profile Approved', 'Dear Imran khan, your lawyer profile has been approved. You can now login and start accepting appointments.', NULL, NULL, 1, '2026-07-03 14:54:25'),
(2, 1, 'lawyer', 'general', 'New Appointment Request', 'Customer sania requested appointment on 04 Jul 2026 at 10:00 AM', NULL, NULL, 1, '2026-07-03 15:09:25'),
(3, 2, 'customer', 'confirmed', 'Appointment Confirmed', 'Your appointment with Adv. Imran khan has been confirmed.', 'my-appointments.php', 'fa-check-circle', 1, '2026-07-03 16:37:27'),
(4, 2, 'customer', 'review_request', 'Review Your Lawyer', 'Your appointment with Adv. Imran khan has been completed. How was your experience?', 'review.php?appointment_id=9', 'fa-star', 1, '2026-07-03 16:37:55'),
(5, 12, 'lawyer', 'cancelled', 'Appointment Cancelled', 'Customer sania has cancelled their appointment.', 'appointments.php', 'fa-times-circle', 0, '2026-07-03 20:16:27'),
(6, 1, 'lawyer', 'new_request', 'New Appointment Request', 'Customer sania requested appointment on 04 Jul 2026 at 11:30 AM', 'appointments.php', 'fa-clock', 1, '2026-07-03 20:55:01'),
(7, 1, 'lawyer', 'cancelled', 'Appointment Cancelled', 'Customer sania has cancelled their appointment.', 'appointments.php', 'fa-times-circle', 1, '2026-07-03 20:55:50'),
(8, 1, 'lawyer', 'new_request', 'New Appointment Request', 'Customer sania requested appointment on 06 Jul 2026 at 11:00 AM', 'appointments.php', 'fa-clock', 0, '2026-07-04 12:38:49'),
(9, 2, 'customer', 'confirmed', 'Appointment Confirmed', 'Your appointment with Adv. Imran khan has been confirmed.', 'my-appointments.php', 'fa-check-circle', 0, '2026-07-04 12:40:21'),
(10, 2, 'customer', 'review_request', 'Review Your Lawyer', 'Your appointment with Adv. Imran khan has been completed. How was your experience?', 'review.php?appointment_id=11', 'fa-star', 0, '2026-07-04 12:43:16'),
(11, 12, 'lawyer', 'rescheduled', 'Appointment Rescheduled', 'Customer sania rescheduled to 31 Jul 2026 at 02:30 PM', 'appointments.php', 'fa-calendar-alt', 0, '2026-07-23 11:47:05'),
(12, 6, 'lawyer', 'new_request', 'New Appointment Request', 'Customer Shahzaib Khan requested appointment on 29 Jul 2026 at 01:30 PM', 'appointments.php', 'fa-clock', 0, '2026-07-28 18:26:32'),
(13, 6, 'lawyer', 'new_request', 'New Appointment Request', 'Customer sania requested appointment on 30 Jul 2026 at 03:00 PM', 'appointments.php', 'fa-clock', 0, '2026-07-28 20:37:39'),
(14, 1, 'lawyer', 'new_request', 'New Appointment Request', 'Customer sania requested appointment on 30 Jul 2026 at 01:30 PM', 'appointments.php', 'fa-clock', 0, '2026-07-29 15:14:29'),
(15, 1, 'lawyer', 'new_request', 'New Appointment Request', 'Customer duafidahussain requested appointment on 30 Jul 2026 at 04:00 PM', 'appointments.php', 'fa-clock', 0, '2026-07-29 15:20:23'),
(16, 1, 'lawyer', 'new_request', 'New Appointment Request', 'Customer Shahzaib Khan requested appointment on 30 Jul 2026 at 03:30 PM', 'appointments.php', 'fa-clock', 0, '2026-07-29 15:22:20'),
(17, 2, 'customer', 'confirmed', 'Appointment Confirmed', 'Your appointment with Adv. Imran khan has been confirmed.', 'my-appointments.php', 'fa-check-circle', 0, '2026-07-29 16:34:10'),
(18, 1, 'lawyer', 'new_request', 'New Appointment Request', 'Customer sania requested appointment on 30 Jul 2026 at 11:00 AM', 'appointments.php', 'fa-clock', 0, '2026-07-29 20:45:51');

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
(1, 1, 2, 9, 4, 'excellent service. understands and communicates well .helpful and nice gentleman', '2026-07-03 17:12:33');

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
(4, 1, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(5, 1, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(6, 1, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(7, 1, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(8, 1, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(9, 1, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(10, 2, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(11, 2, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(12, 2, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(13, 2, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(14, 2, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(15, 2, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(16, 3, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(17, 3, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(18, 3, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(19, 3, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(20, 3, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(21, 3, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(22, 4, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(23, 4, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(24, 4, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(25, 4, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(26, 4, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(27, 4, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(28, 5, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(29, 5, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(30, 5, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(31, 5, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(32, 5, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(33, 5, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(34, 6, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(35, 6, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(36, 6, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(37, 6, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(38, 6, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(39, 6, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(40, 7, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(41, 7, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(42, 7, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(43, 7, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(44, 7, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(45, 7, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(46, 8, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(47, 8, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(48, 8, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(49, 8, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(50, 8, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(51, 8, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(52, 9, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(53, 9, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(54, 9, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(55, 9, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(56, 9, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(57, 9, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(58, 10, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(59, 10, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(60, 10, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(61, 10, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(62, 10, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(63, 10, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(64, 11, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(65, 11, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(66, 11, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(67, 11, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(68, 11, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(69, 11, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(70, 12, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(71, 12, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(72, 12, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(73, 12, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(74, 12, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(75, 12, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(76, 13, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(77, 13, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(78, 13, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(79, 13, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(80, 13, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(81, 13, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(82, 14, 'Monday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(83, 14, 'Tuesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(84, 14, 'Wednesday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(85, 14, 'Thursday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(86, 14, 'Friday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51'),
(87, 14, 'Saturday', '10:00:00', '17:00:00', 1, '2026-06-12 21:12:51');

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `is_read` (`is_read`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `homepage_content`
--
ALTER TABLE `homepage_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lawyers`
--
ALTER TABLE `lawyers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `slots`
--
ALTER TABLE `slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

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
