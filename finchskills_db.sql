-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 13, 2026 at 10:15 PM
-- Server version: 8.0.46-37
-- PHP Version: 8.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `a1788ef6_fintchskillnew_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@finchskills.com', '$2y$10$/2Ny.fGQmmM2cS0LYeVRHOD/aLTIQifLH1N3mOn0YdCaT/WtC.Et6', '2026-09-09 00:24:35');

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enrollment_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `father_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aadhaar` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `education` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percentage` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passing_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `board_university` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post_office` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pincode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `marksheet10` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marksheet12` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aadhaar_card` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `fee_total` decimal(10,2) DEFAULT '0.00',
  `seat_fee_adjusted` decimal(10,2) DEFAULT '0.00',
  `fee_paid` decimal(10,2) DEFAULT '0.00',
  `fee_pending` decimal(10,2) DEFAULT '0.00',
  `payment_plan` enum('full','installment') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('Pending Payment','Partial Paid','Fully Paid') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending Payment',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admissions`
--

INSERT INTO `admissions` (`id`, `student_id`, `enrollment_no`, `name`, `father_name`, `email`, `mobile`, `aadhaar`, `dob`, `gender`, `course`, `education`, `percentage`, `passing_year`, `board_university`, `state`, `city`, `post_office`, `pincode`, `address`, `marksheet10`, `marksheet12`, `aadhaar_card`, `photo`, `status`, `fee_total`, `seat_fee_adjusted`, `fee_paid`, `fee_pending`, `payment_plan`, `payment_status`, `is_read`, `created_at`) VALUES
(5, 'FS-2026-7521', NULL, 'Akshit Agrawal', 'Ram Kishor Agrawal', 'akshitakshitagarwal1@gmail.com', '9460447060', '699684535770', '2004-10-01', 'Male', 'Certification Course in Travel Management', 'Graduate', '7.60', '2025', 'JECRC University', 'Rajasthan', 'Jaipur', 'Gandhi Nagar Sub Post Office', '302015', 'C-1,Chikitsalaya Marg ,Bapu Nagar', 'm10_1791364440_6ac60d58215e1.pdf', 'm12_1791364440_6ac60d5821d06.pdf', 'aadh_1791364440_6ac60d5821f0f.pdf', 'photo_1791364440_6ac60d5822092.jpg', 'Approved', 0.00, 0.00, 0.00, 0.00, NULL, 'Pending Payment', 1, '2026-10-07 09:14:00'),
(6, 'FS-2026-7521', 'ENR-2026-01006', 'Akshit Agrawal', 'Ram Kishor Agrawal', 'akshitakshitagarwal1@gmail.com', '9460447060', '699684535770', '2004-10-01', 'Male', 'Certification Course in Travel Management', 'Graduate', '7.60', '2025', 'JECRC University', 'Rajasthan', 'Jaipur', 'Gandhi Nagar Sub Post Office', '302015', 'C-1,Chikitsalaya Marg ,Bapu Nagar', 'm10_1791365881_6ac612f97856c.pdf', 'm12_1791365881_6ac612f9787b3.pdf', 'aadh_1791365881_6ac612f97896f.pdf', 'photo_1791365881_6ac612f978ab9.jpg', 'Approved', 22500.00, 2750.00, 9875.00, 9875.00, 'installment', 'Partial Paid', 1, '2026-10-07 09:38:01'),
(12, 'FS-2026-6630', NULL, 'Mayank sati', 'Rajender sati', 'themayanksati@gmail.com', '7505364505', '305598210185', '2006-09-20', 'Male', 'Certification Course in Travel Management', 'Diploma Holder', '8.23', '2026', 'Uttrakhand Institute of Technical and Professional Education Ramnagar', 'Uttarakhand', 'Ramnagar', 'Ramnagar', '244715', 'kyari bandobasti', 'm10_1791437473_6ac72aa1b03c4.jpeg', 'm12_1791437473_6ac72aa1b09ae.jpeg', 'aadh_1791437473_6ac72aa1b0a99.pdf', 'photo_1791437473_6ac72aa1b0b1c.jpeg', 'Rejected', 0.00, 0.00, 0.00, 0.00, NULL, 'Pending Payment', 1, '2026-10-08 05:31:13'),
(13, 'FS-2026-2444', NULL, 'Taskeen Firdous', 'Neyaz ahmad', 'taskeenf980@gmail.com', '9325468191', '966743671219', '2004-10-30', 'Female', 'Certification Course in Travel Management', 'Graduate', '8.39', '2026', 'GH Raisoni University Amravati', 'Maharashtra', 'Nagpur', 'Kamptee', '441002', 'Behind hockey building lakad ganj kamptee (street no. 2)', 'm10_1791447872_6ac75340ce235.jpeg', 'm12_1791447872_6ac75340ce382.jpeg', 'aadh_1791447872_6ac75340ce418.jpeg', 'photo_1791447872_6ac75340ce490.jpeg', 'Rejected', 0.00, 0.00, 0.00, 0.00, NULL, 'Pending Payment', 1, '2026-10-08 08:24:32'),
(14, 'FS-2026-4924', NULL, 'Aaqib Shaikh', 'Musheer Shaikh', 'aaqibalamshaikh@gmail.com', '9326118669', '916241091295', '2002-06-15', 'Male', 'Certification Course in Travel Management', 'Graduate', '7.77', '2026', 'Mumbai University', 'Maharashtra', 'Mumbai', 'Kurla', '400043', 'Road no 3, plot no 20, shivaji nagar, govandi Mumbai, Maharashtra', 'm10_1791458712_6ac77d98a2b0d.jpg', 'm12_1791458712_6ac77d98a31e3.jpg', 'aadh_1791458712_6ac77d98a3bd9.jpg', 'uploads/students/std_1791443438_184.png', 'Pending', 0.00, 0.00, 0.00, 0.00, NULL, 'Pending Payment', 1, '2026-10-08 11:25:12'),
(15, 'FS-2026-9901', NULL, 'Biplaw kumar Dutta', 'Shyamal chandra Dutta', 'biplawkumardutta@gmail.com', '9334978916', '597998354518', '1996-02-03', 'Male', 'Certification Course in Travel Management', 'Graduate', '69.50', '2018', 'Tilkamanjhi University Bhagalpur', 'Jharkhand', 'Sahebganj', 'Borio', '816120', 'Bengali tola Borio sahibganj jharkhand', 'm10_1791532262_6ac89ce6e26e2.pdf', 'm12_1791532262_6ac89ce6e29dd.pdf', 'aadh_1791532262_6ac89ce6e2a95.jpg', 'photo_1791532262_6ac89ce6e2b17.jpg', 'Pending', 0.00, 0.00, 0.00, 0.00, NULL, 'Pending Payment', 1, '2026-10-09 07:51:02'),
(16, 'FS-2026-3786', 'ENR-2026-01016', 'Nikhil singh', 'shyam', 'nikhilrura5@gmail.com', '7317788940', '559687401325', '2026-10-11', 'Male', 'Certificate Course in Travel & Air Ticketing', 'Diploma Holder', '75', '2023', 'up', 'Uttar Pradesh', 'Noida', 'rura', '201307', 'Mamura, Noida, Dadri, Gautam Buddha Nagar, Uttar Pradesh, 201307, India', 'm10_1791699585_6acb2a81ec71a.pdf', 'm12_1791699585_6acb2a81ec8c5.pdf', 'aadh_1791699585_6acb2a81ec963.jpeg', 'uploads/students/std_1791699160_686.jpeg', 'Approved', 31500.00, 2750.00, 25600.00, 0.00, 'full', 'Fully Paid', 1, '2026-10-11 06:20:48'),
(17, 'FS-2026-4574', 'ENR-2026-01017', 'madhav', 'Ram', 'iconicfounder55@gmail.com', '8344335663', '878867668877', '2026-10-14', 'Male', 'Certification Course in Travel Management', '12th Pass (Pursuing)', '65', '2024', 'UP Board', 'Madhya Pradesh', 'Gwalior', 'vdkjksa', '474006', 'MN-130, Singhpur Children Vidhyalay ke samne, Gird, Po- Murar, Dist - Gwalior, Madhya Pradesh - 474006', 'm10_1791900698_6ace3c1a2e8b0.jpg', 'm12_1791900698_6ace3c1a2ec95.jpg', 'aadh_1791900698_6ace3c1a2f13c.jpg', 'uploads/students/std_1791900463_368.jpg', 'Approved', 22500.00, 2750.00, 9875.00, 9875.00, 'installment', 'Partial Paid', 1, '2026-10-13 14:12:58');

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('New','In Progress','Resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'New',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_inquiries`
--

INSERT INTO `contact_inquiries` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `is_read`, `created_at`) VALUES
(1, 'Bakar Berete', 'seorankingtech@gmail.com', '1201201200', 'Re: finchskills.com - SEO', 'Hey team finchskills.com,\r\n\r\nI was going through your website & I personally see a lot of potential in your website & business.\r\n\r\nWe help businesses rank higher on Google while also increasing their presence on AI-powered search platforms like ChatGPT, Gemini, Claude, and Perplexity.\r\n\r\nOur SEO + AEO + GEO strategies help brands get discovered wherever customers search for answers.\r\n\r\nWould you like me to send you our proposal and pricing?\r\n\r\nCheers,\r\nBakar Berete | Founder & Project Head', 'New', 1, '2026-09-30 23:23:38'),
(2, 'Jeokelam', 'igwo6078@hotmail.com', '88752961964', 'I promised.', 'Hi, this is Jeniffer. I am sending you my intimate photos as I promised. https://tinyurl.com/4vyb7wy2', 'New', 1, '2026-10-02 02:36:11'),
(3, 'Jeokelam', 'i2zcz5o9@yahoo.com', '81654622531', 'I promised.', 'Hi, this is Anna. I am sending you my intimate photos as I promised. https://tinyurl.com/3zwhvebd', 'New', 1, '2026-10-03 09:31:43'),
(4, 'Bakar Berete', 'mapmybiz.mkt@gmail.com', '443767196', 'Helping New Websites Grow with SEO', 'Hey team finchskills.com,\r\n\r\nI\'d love to discuss how we can grow your online visibility.\r\n\r\nWe help businesses rank higher on Google while also increasing their presence on AI-powered search platforms like ChatGPT, Gemini, Claude, and Perplexity.\r\n\r\nOur SEO + AEO + GEO strategies help brands get discovered wherever customers search for answers.\r\n\r\nMay I send you a quote & price list?\r\n\r\nCheers,\r\nBakar Berete | Founder & Project Head', 'New', 1, '2026-10-03 11:18:03'),
(5, 'Jeokelam', 'j7swo3y7@yahoo.com', '83527863488', 'I promised.', 'Hi, this is Jenny. I am sending you my intimate photos as I promised. https://tinyurl.com/yt5ummp7', 'New', 1, '2026-10-04 18:56:01'),
(6, 'Forest Lyon', 'domains@search-finchskills.com', '3420315753', '', 'Hello\r\n\r\nList finchskills.com in Google\'s Search Index and have it be displayed in online search results!\r\n\r\nRegister finchskills.com here: domainsubmit.pro', 'New', 1, '2026-10-04 22:30:00'),
(7, 'Rutuja Dandge', 'rutujadandge115@gmail.com', '9767289060', 'Travels and tourism course', 'Fees and all information', 'New', 1, '2026-10-05 07:46:01'),
(8, 'Harshal Khairnar', '', '8380859156', 'Quick Callback: Certification Course in Travel Management', 'Course: Certification Course in Travel Management\nStudent Name: Harshal Khairnar\nPhone: 8380859156\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-06 09:07:19'),
(9, 'Akshit', '', '9460447090', 'Quick Callback: Certification Course in Travel Management', 'Course: Certification Course in Travel Management\nStudent Name: Akshit\nPhone: 9460447090\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-07 06:13:02'),
(10, 'Mayuri', '', '8767998763', 'Quick Callback: Certification Course in Travel Management', 'Course: Certification Course in Travel Management\nStudent Name: Mayuri\nPhone: 8767998763\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-07 07:21:55'),
(11, 'Durgesh Vijay Shinde', '', '8485845778', 'Quick Callback: Certification Course in Travel Management', 'Course: Certification Course in Travel Management\nStudent Name: Durgesh Vijay Shinde\nPhone: 8485845778\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-07 07:24:15'),
(12, 'Ambuj Mishra', '', '7651844254', 'Quick Callback: Professional Course in Airport Management', 'Course: Professional Course in Airport Management\nStudent Name: Ambuj Mishra\nPhone: 7651844254\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-07 09:38:41'),
(13, 'Ambuj Mishra', '', '7651844254', 'Quick Callback: Professional Course in Airport Management', 'Course: Professional Course in Airport Management\nStudent Name: Ambuj Mishra\nPhone: 7651844254\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-07 09:39:11'),
(14, 'Ambuj Mishra', '', '7651844254', 'Quick Callback: Certificate Course in Travel & Air Ticketing', 'Course: Certificate Course in Travel & Air Ticketing\nStudent Name: Ambuj Mishra\nPhone: 7651844254\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-07 09:40:22'),
(15, 'Anshika Tripathi', '', '9893396323', 'Quick Callback: Certification Course in Travel Management', 'Course: Certification Course in Travel Management\nStudent Name: Anshika Tripathi\nPhone: 9893396323\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-08 07:53:26'),
(16, 'NARYTHY954489NERTHRTYHR', 'green03045@notlettersmail.com', '88864171363', 'TORTYT954489TIRTYTH', 'MERTYHR954489MARETRYTR', 'New', 1, '2026-10-09 23:13:43'),
(17, 'NARTYTRYUT954489NEYHRTGE', 'green03045@notlettersmail.com', '89662214886', 'TOTYJTRT954489TIGFHFGER', 'METYUTYJ954489MARTHHDF', 'New', 1, '2026-10-10 19:34:25'),
(18, 'Bakar Berete', 'mapmybiz.mkt@gmail.com', '1201201200', 'Re: finchskills.com - SEO', 'Hey team finchskills.com,\r\n\r\nI\'d love to discuss how we can grow your online visibility.\r\n\r\nWe help businesses rank higher on Google while also increasing their presence on AI-powered search platforms like ChatGPT, Gemini, Claude, and Perplexity.\r\n\r\nOur SEO + AEO + GEO strategies help brands get discovered wherever customers search for answers.\r\n\r\nWould you like me to send you our proposal and pricing?\r\n\r\nCheers,\r\nBakar Berete | Founder & Project Head', 'New', 1, '2026-10-12 19:35:46'),
(19, 'Kaira chichiria', '', '7621004489', 'Quick Callback: Certificate Course in Travel & Air Ticketing', 'Course: Certificate Course in Travel & Air Ticketing\nStudent Name: Kaira chichiria\nPhone: 7621004489\nSource: Course Detail Page Quick Callback Box', 'New', 1, '2026-10-13 11:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `duration` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '6 Months',
  `study_mode` varchar(60) COLLATE utf8mb4_general_ci DEFAULT 'Online / Offline',
  `modules` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '6 Modules',
  `fee` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `short_desc` text COLLATE utf8mb4_general_ci,
  `full_desc` longtext COLLATE utf8mb4_general_ci,
  `what_you_learn` longtext COLLATE utf8mb4_general_ci,
  `how_we_teach` longtext COLLATE utf8mb4_general_ci,
  `career_roles` text COLLATE utf8mb4_general_ci,
  `eligibility` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '10+2 / Any Graduate',
  `certification` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Industry Recognized Certification',
  `is_featured` tinyint(1) DEFAULT '1',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `category_id`, `title`, `slug`, `duration`, `study_mode`, `modules`, `fee`, `image`, `short_desc`, `full_desc`, `what_you_learn`, `how_we_teach`, `career_roles`, `eligibility`, `certification`, `is_featured`, `status`, `created_at`) VALUES
(1, 1, 'Professional Course in Airport Management', 'airport-management-course', '3 Months', 'Online', '6 Modules', '₹29,500', 'img/airport-management-course.png', 'Equip yourself with essential skills in airport terminal operations, passenger services, security compliance, and airline coordination for successful careers in global aviation.', 'The Professional Course in Airport Management at Finchskills Institute is designed to give you a complete, practical understanding of modern airline and airport terminal operations. From check-in counters and baggage systems to airside safety and crisis handling, this course ensures you are completely job-ready for leading domestic and international airlines.', 'Airport Terminal Architecture & Passenger Flow\nPassenger Check-in Procedures & Boarding Management\nBaggage Handling, Screening & Reconciliation\nAviation Security (AVSEC), DGR & Safety Protocols\nAirline Customer Service Excellence & Conflict Handling\nFlight Dispatch Basics & Airport Emergency Procedures', 'Live Airport Mock Terminal Roleplays\nAirline Recruiter Interview Preparation\nInternational Grooming, Etiquette & Soft Skills\nDirect Industrial Visits & Expert Airline Masterclasses', 'Airport Ground Staff, Terminal Executive, Passenger Service Agent, Guest Relations Officer, Flight Dispatch Assistant', '10+2 / Any Graduate (Age 18-27)', 'Professional Airport Operations Diploma', 1, 'active', '2026-09-20 12:30:28'),
(2, 3, 'Certificate Course in Travel & Air Ticketing', 'certificate-course-in-travel-air-ticketing', '3 Months', 'Online', '3 Modules', '₹31,500', 'img/Certificate-Course.webp', 'Master Global Distribution Systems (GDS like Amadeus/Galileo), airfare calculations, PNR creation, and flight ticket issuance for instant airline hiring.', 'Become an expert airline ticketing professional by mastering industry-standard Global Distribution Systems (GDS) including Amadeus and Galileo. Learn flight searches, PNR creation, fare construction, mileage rules, airfare calculation, reissuance, cancellation, and refund management demanded by commercial airlines, online travel portals (OTAs), and corporate travel desks.\n\nThis practical hands-on training opens high-paying careers in airline reservation centers and multinational travel corporations.', 'GDS Architecture, Terminal Commands, Navigation & Passenger PNR Generation\nAirfare Construction, Mileage Rules, Currency Conversion & E-Ticket Issuance\nTicket Reissuance, Date Changes, Cancellation, Refunds & Ancillary Services', 'Daily Hands-on Live Terminal Practice on Certified GDS Software\nReal-Time Airfare Calculation & Multi-City Itinerary Drills\nLive Ticket Booking, Exchange & Cancellation Case Studies\nDirect Industry Placement Drives with Commercial Airlines & Top OTAs', 'Air Ticketing Executive, GDS Reservation Specialist, Airline Customer Service Officer, Corporate Travel Consultant, Fare & Tariff Analyst', '12th Pass or Graduate with basic computer typing knowledge.', 'Professional Certificate in GDS Air Ticketing & Global Reservations', 1, 'active', '2026-09-20 12:30:28'),
(3, 2, 'Diploma in Hospitality & Guest Relations', 'hospitality-guest-relations-diploma', '6 Months', 'Offline', '6 Modules', '₹51,500', 'uploads/courses/course_1790638227_671.webp', 'Develop 5-star hospitality standards with comprehensive training in front office operations, guest service excellence, luxury dining etiquette, and accommodation management.', 'Step into the glamorous world of luxury hotels, 5-star resorts, and corporate concierge services. This diploma covers front desk systems, guest conflict resolution, VIP protocol management, and banquet operations.', 'Front Office Hierarchy & Property Management Systems (PMS)\r\nLuxury Guest Relations & VIP Protocol Management\r\nFood & Beverage Service Principles & Banqueting\r\nHousekeeping Operations & Room Inventory Control\r\nHigh-Impact Communication & Multilingual Basics\r\nComplaint Handling & Service Recovery Strategies', '5-Star Hotel Mock Reception & Room Setup\r\nFine Dining Table Etiquette & Professional Grooming\r\nMock Interviews by Hotel HR Managers\r\nIndustry Internship & Placement Guarantee Support', 'Front Desk Executive, Guest Relations Associate, Concierge Specialist, F&B Supervisor, VIP Lounge Host', '10+2 / Higher Secondary / Graduate', 'Professional Diploma in Hospitality Management', 1, 'active', '2026-09-20 12:30:28'),
(4, 4, 'Professional Course in Cruise Management', 'cruise-ship-operations-course', '6 Months', 'Offline', '6 Modules', '₹48,000', 'img/Cruise-Management-course.jpg', 'Prepare for rewarding careers on international cruise lines with specialized training in shipboard hospitality, guest relations, safety protocols, and onboard crew operations.', 'Prepare for high-earning, tax-free international careers aboard luxury cruise liners, mega-ships, and international yachts. This comprehensive program covers shipboard guest relations, international maritime safety conventions (STCW guidelines), luxury dining operations, housekeeping management, and crew life protocols.\n\nOur instructors guide you through international cruise recruitment drives, visa medical standards, and maritime interview clearance.', 'Cruise Liner Operations, Deck Hierarchy & Maritime Terminology\nShipboard Guest Services, Concierge & Front Desk Operations\nInternational Maritime Safety Awareness & Emergency Procedures\nFine Dining & Food & Beverage Service Aboard Luxury Cruise Lines\nCross-Cultural Passenger Hospitality & Conflict Resolution\nSTCW Basic Safety Guidelines, US Visa (C1/D) Preparation & Crew Etiquette', 'Cruise Ship Mock Dining & Cabin Service Practical Drills\nMaritime Safety & Emergency Protocol Roleplay Simulations\nCross-Cultural Communication & International Etiquette Workshops\nDirect Interview Grooming for Global Cruise Line Recruitment Drives', 'Cruise Hospitality Associate, Shipboard Guest Relations Officer, F&B Service Attendant, Stateroom Host, Cruise Activity Coordinator', '10+2 (12th Pass) or Hotel/Aviation Graduate. Age 18-30. Valid passport preferred.', 'Professional Diploma in International Cruise Ship Operations', 1, 'active', '2026-09-20 12:30:28'),
(5, 5, 'Professional Course in Air Hostess', 'professional-course-in-air-hostess', '11 Months', 'Offline', '9 Modules', '₹98,500', 'img/airhostess-course.png', 'Prepare for a successful career in aviation. Develop professional grooming, in-flight safety protocols, hospitality etiquette, and communication skills required to excel as cabin crew.', 'Prepare for a successful career in domestic and international commercial aviation with our premier Air Hostess & Cabin Crew training program. Develop professional grooming standards, in-flight passenger safety protocols, international hospitality etiquette, emergency situation management, and multilingual customer communication skills required to excel as elite cabin crew.\n\nOur curriculum is taught by former flight purser instructors and senior airline trainers, giving you hands-on exposure to simulated aircraft cabin environments.', 'Aviation Industry Overview & Commercial Aircraft Familiarization\nIn-Flight Safety Standards & Emergency Evacuation Protocols\nPassenger Service Excellence & First Aid Medical Training\nAviation English, Announcement Voice & International Communication\nIn-Flight Food, Wine & Luxury Beverage Service Standards\nExecutive Personality Grooming, Skin Care, Makeup & Hair Masterclass\nCrew Resource Management (CRM), Air Safety Regulations & Security\nAviation First Aid, In-Flight Medical Emergencies & CPR Certification\nAirline Interview Preparation, Group Discussion & Boarding Simulation', 'Hands-on Cabin Mock-Up & Real-Life Emergency Drill Simulations\nProfessional Uniform, Hair, Makeup & High-Standard Grooming Workshops\nCustomer Conflict Resolution & De-escalation Roleplay Scenarios\nOne-on-One Airline Mock HR & GD Technical Rounds with Ex-Cabin Crew', 'Air Hostess, Cabin Crew, Flight Attendant, VIP Corporate Flight Purser, In-Flight Services Associate', '10+2 (12th Pass) in any stream. Age 17-27 years. Pleasing personality & clear speech.', 'Certified Professional in Air Hostess & Cabin Crew Operations', 1, 'active', '2026-09-20 12:51:58'),
(6, 6, 'Professional Course in Ground Staff & Hospitality', 'ground-staff-hospitality-course', '3 Months', 'Online', '6 Modules', '₹32,500', 'img/Hospitality-course.png', 'Gain comprehensive training in airport terminal operations, passenger check-in handling, ramp coordination, security protocols, and luxury hospitality management.', 'Gain comprehensive practical training in airport terminal operations, departure control systems, passenger check-in handling, ramp coordination, baggage management, aviation security regulations, and high-touch hospitality. This course is specially tailored for aspirants aiming for prestigious airport roles across domestic and international airports.\n\nStudents receive practical instruction on real check-in terminals, customer service psychology, and ground handling coordination.', 'Airport Terminal Architecture & Passenger Flow Management\nDeparture Control Systems (DCS) & Boarding Gate Coordination\nBaggage Handling, Tagging & Lost and Found Systems (WorldTracer)\nAviation Security Protocols, DGR (Dangerous Goods Regulations) & Safety\nRamp Safety, Turnaround Coordination & Marshalling Basics\nVIP Lounge Hospitality & Special Assistance (PRM) Passenger Care', 'Simulated Airport Terminal & Boarding Gate Practical Drills\nLive Passenger Check-in Software & Baggage Tagging Training\nEmergency De-escalation & Passenger Assistance Roleplays\nMock Airport Interviews with Leading Domestic & Global Ground Handlers', 'Airport Ground Staff, Passenger Service Agent, Boarding Gate Officer, Customer Service Associate, Ramp Coordinator, VIP Airport Lounge Host', '12th Pass or Any Graduate from a recognized board/university.', 'Professional Diploma in Airport Ground Operations & Hospitality', 1, 'active', '2026-09-20 12:51:58'),
(7, 3, 'Professional Course in Travel & Tourism', 'professional-course-in-travel-tourism', '12 Months', 'Offline', '9 Modules', '₹89,000', 'img/Diploma-travell.webp', 'Build a strong foundation in international tourism trends, tour itinerary planning, visa assistance, and customer relationship expertise for global travel agencies.', 'Build a strong foundation in international tourism trends, outbound & inbound tour itinerary planning, visa documentation, global geography, customer relationship management, and digital travel marketing. Learn how world-class travel agencies, Destination Management Companies (DMCs), and online travel portals (OTAs) operate.\n\nGain practical proficiency in designing customized international tour packages, luxury cruise bookings, and corporate business travel arrangements.', 'World Geography, Time Zones & Major Global Tourism Hotspots\nInbound & Outbound Tour Package Design & Costing\nInternational Visa Application Documentation & Consular Formalities\nHotel Contracting, Airport Transfers & Sightseeing Logistics\nCustomer Relationship Management for Travel Consultants\nDigital Travel Portals (OTAs), Online Booking Tools & Marketing\nGlobal Airline Ticketing & GDS (Amadeus/Galileo) Essentials\nMICE Tourism, Luxury Cruises & Corporate Travel Management\nTravel Agency Business Operations, Crisis Management & Industry Laws', 'Live Itinerary Creation, Package Budgeting & Costing Projects\nCase Studies on Popular International Tourism Circuits\nRoleplay on Client Travel Consultations, Negotiation & Objection Handling\nIndustry Visits to Leading Tour Operators & Travel Management Companies', 'Tour Operations Executive, Travel Consultant, Holiday Package Planner, Visa Documentation Specialist, Destination Expert, Corporate Travel Desk Officer', '12th Pass or Graduate in any stream with basic computer knowledge.', 'Certified Professional in Global Travel & Tourism Operations', 1, 'active', '2026-09-20 12:51:58'),
(8, 7, 'Foundation Course in Hotel Management', 'foundation-course-in-hotel-management', '6 Months', 'Offline', '6 Modules', '₹55,000', 'img/Hotel-Management-course.webp', 'Learn the fundamentals of hotel operations, front-office systems, housekeeping standards, food & beverage service, and guest relations for 5-star hotels and resorts.', 'Master the fundamentals of world-class hotel operations across four core hospitality pillars: Front Office Management, Food & Beverage Service, Housekeeping Operations, and Guest Relations. This intensive 6-month foundation program prepares students for immediate hiring across leading 5-star hotel chains, luxury resorts, and high-end banquet establishments.\n\nYou will master international etiquette, table service standards, front desk reservation systems, and professional hospitality communication.', 'Front Office Operations, Guest Check-in & Hotel PMS Software\nFood & Beverage Service Etiquette, Banquet Operations & Table Layouts\nHousekeeping Standards, Room Preparation, Linen & Quality Audits\nGuest Relations, Concierge Assistance & VIP Protocol Handling\nBeverage Fundamentals, Service Hygiene & Sanitation Practices\nHospitality Spoken English, Grooming & Customer Care Excellence', 'Hands-on Fine Dining Table Setting & F&B Service Lab Sessions\nLive Hotel Front Desk Simulation & Reservation Software Roleplay\nHousekeeping Room Inspection Drills & Hospitality Cleanliness Audits\n5-Star Hotel Exposure Visits & Interactive Masterclasses with General Managers', 'Front Office Executive, Guest Relations Associate, F&B Service Steward, Banquet Host, Housekeeping Supervisor, Resort Concierge', '10th Pass or 12th Pass in any discipline.', 'Foundation Certificate in Hotel Operations & Hospitality Management', 1, 'active', '2026-09-20 12:51:58'),
(9, 8, 'Professional Course in Personality Development', 'professional-course-in-personality-development', '3 Months', 'Online', '6 Modules', '₹18,500', 'img/personality-development-course.webp', 'Elevate your career presence with corporate etiquette, spoken English fluency, body language refinement, and rigorous mock interview simulations.', 'Transform your professional presence, boost self-confidence, and master high-impact executive communication. Specially designed for job aspirants preparing for competitive airline, hospitality, and corporate interviews, this course refines spoken English fluency, body language, corporate dining etiquette, active listening, and interview clearing strategies.\n\nStudents undergo intensive video-recorded mock interview panels and personalized voice-and-accent refinement sessions.', 'Spoken English Fluency, Pronunciation & Accent Neutralization\nProfessional Body Language, Posture, Eye Contact & Hand Gestures\nCorporate Dressing Standards, Wardrobe Planning, Makeup & Personal Grooming\nPublic Speaking Confidence, Overcoming Hesitation & Stage Fear\nGroup Discussion (GD) Strategies, Moderation & Leadership Presence\nHR Interview Clearing Techniques, Behavioral Answers & Resume Optimization', 'Daily Video-Recorded Speaking & Body Language Review Sessions\nExtensive Group Discussions, Debates & Impromptu Speech Drills\nProfessional Personal Grooming & Executive Styling Workshops\nSimulated HR & Technical Panel Mock Interviews with Constructive Feedback', 'Customer Service Executive, Corporate PR Associate, Front Desk Coordinator, Airline Customer Care Officer, Corporate Host', 'Open to all students (10th, 12th, or Graduates) seeking career transformation.', 'Executive Certificate in Corporate Personality & Communication', 1, 'active', '2026-09-20 12:51:58'),
(10, 3, 'Certification Course in Travel Management', 'certification-course-in-travel-management', '2 Months', 'Online', '3 Modules', '22500', 'img/Travel-Tourism-course.jpg', 'Comprehensive online certification covering global travel operations, itinerary planning, customer relations, and travel agency management.', 'The Certification Course in Travel Management at Finchskills Institute is a focused 2-month fast-track online program designed to equip students with practical knowledge of corporate travel operations, outbound and inbound tour management, airline booking fundamentals, client relationship handling, and modern travel industry software. Ideal for students and professionals looking to launch or advance their careers in destination management companies, travel agencies, and corporate travel desks.', 'Principles of Travel Management & Global Tourism Geography\r\nCustom Tour Package Designing, Costing & Vendor Negotiations\r\nCustomer Service Excellence, Travel Documentation & Online Booking Portals', 'Interactive Online Masterclasses\r\nCase Studies on Real-World Travel Itineraries\r\nPractical Assignments on Travel Booking Tools\r\nDirect Career Counselling & Resume Optimization', 'Travel Management Consultant, Tour Coordinator, Holiday Package Executive, Corporate Travel Assistant, Client Relations Officer', '10+2 / Any Graduate / Working Professionals', 'Certificate in Professional Travel Management', 1, 'active', '2026-09-26 03:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(60) COLLATE utf8mb4_general_ci DEFAULT 'fa-graduation-cap',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_categories`
--

INSERT INTO `course_categories` (`id`, `name`, `slug`, `icon`, `status`, `created_at`) VALUES
(1, 'Aviation Management', 'aviation-management', 'fa-plane-departure', 'active', '2026-09-20 12:30:28'),
(2, 'Hospitality & Hotel', 'hospitality-hotel', 'fa-hotel', 'active', '2026-09-20 12:30:28'),
(3, 'Travel & Tourism', 'travel-tourism', 'fa-earth-americas', 'active', '2026-09-20 12:30:28'),
(4, 'Cruise Management', 'cruise-management', 'fa-ship', 'active', '2026-09-20 12:30:28'),
(5, 'Aviation & Cabin Crew', 'aviation-cabin-crew', 'fa-plane-departure', 'active', '2026-09-20 12:51:58'),
(6, 'Airport Operations', 'airport-operations', 'fa-id-badge', 'active', '2026-09-20 12:51:58'),
(7, 'Hotel Management', 'hotel-management', 'fa-hotel', 'active', '2026-09-20 12:51:58'),
(8, 'Grooming & Personality', 'grooming-personality', 'fa-user-tie', 'active', '2026-09-20 12:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `fee_submissions`
--

CREATE TABLE `fee_submissions` (
  `id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Verified','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_submissions`
--

INSERT INTO `fee_submissions` (`id`, `name`, `contact`, `email`, `fee_type`, `purpose`, `course`, `receipt`, `signature`, `status`, `is_read`, `created_at`) VALUES
(9, 'Akshit Agrawal', '9460447060', 'akshitakshitagarwal1@gmail.com', 'Registration Fee', 'Seat Reservation (Razorpay Online)', 'Certification Course in Travel Management', 'Razorpay ID: pay_TkxzCNyZIqwLLC', NULL, 'Verified', 1, '2026-10-07 09:20:20'),
(10, 'Akshit Agrawal', '9460447060', 'akshitakshitagarwal1@gmail.com', 'Course Admission Fee', 'Course Fee Part 1 / Down Payment - Enr: ENR-2026-01006', 'Certification Course in Travel Management', 'Razorpay: pay_TkyItQnUtwKfio | Receipt: REC-74F997E7 | Enr: ENR-2026-01006', NULL, 'Verified', 1, '2026-10-07 09:38:58'),
(11, 'Durgesh Vijay Shinde', '8485845778', 'shindedurgesh81@gmail.com', 'Registration Fee', 'Seat Reservation (Razorpay Online)', 'Certification Course in Travel Management', 'Razorpay ID: pay_TkzH5Jofiuokyu', NULL, 'Verified', 1, '2026-10-07 10:36:03'),
(12, 'Aaqib Shaikh', '9326118669', 'aaqibalamshaikh@gmail.com', 'Registration Fee', 'Seat Reservation (Razorpay Online)', 'Certification Course in Travel Management', 'Razorpay ID: pay_TlP7ZcX36DscuC', NULL, 'Verified', 1, '2026-10-08 11:53:13'),
(13, 'Nikhil singh', '7317788940', 'nikhilrura5@gmail.com', 'Registration Fee', 'Seat Reservation (Razorpay Online)', 'Certificate Course in Travel & Air Ticketing', 'Razorpay ID: pay_TmUwNH79gjRzUS | REC-1880F97D', NULL, 'Verified', 1, '2026-10-11 06:13:58'),
(14, 'Nikhil singh', '7317788940', 'nikhilrura5@gmail.com', 'Course Admission Fee', 'Full Course Fee (Paid in Full) - Enr: ENR-2026-01016', 'Certificate Course in Travel & Air Ticketing', 'Razorpay: pay_TmV3wFfTxnmgQy | Receipt: REC-F98105CB | Enr: ENR-2026-01016', NULL, 'Verified', 1, '2026-10-11 06:20:48'),
(15, 'madhav', '8347647757', 'orioninaht@gmail.com', 'Registration Fee', 'Seat Reservation (Razorpay Online)', 'Certification Course in Travel Management', 'Razorpay ID: pay_TmVXcVFJHr35pT | REC-41B9BAC8', NULL, 'Verified', 1, '2026-10-11 06:48:54'),
(16, 'madhav', '8344335663', 'iconicfounder55@gmail.com', 'Registration Fee', 'Seat Reservation (Razorpay Online)', 'Certification Course in Travel Management', 'Razorpay ID: pay_TnQ70799b12W5n | REC-51C5C6BE', NULL, 'Verified', 1, '2026-10-13 14:09:13'),
(17, 'madhav', '8344335663', 'iconicfounder55@gmail.com', 'Course Admission Fee', 'Course Fee Part 1 / Down Payment - Enr: ENR-2026-01017', 'Certification Course in Travel Management', 'Razorpay: pay_TnQAwgHeayl9bf | Receipt: REC-FDC49933 | Enr: ENR-2026-01017', NULL, 'Verified', 1, '2026-10-13 14:12:58');

-- --------------------------------------------------------

--
-- Table structure for table `site_pages`
--

CREATE TABLE `site_pages` (
  `id` int NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `is_system` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_pages`
--

INSERT INTO `site_pages` (`id`, `slug`, `title`, `subtitle`, `content`, `meta_title`, `meta_description`, `status`, `is_system`, `updated_at`, `created_at`) VALUES
(1, 'terms-conditions', 'Terms and Conditions', 'Please read these terms carefully before enrolling or using our services', '<h2>1. Acceptance of Terms</h2>\n<p>Welcome to <strong>Finchskills Institute</strong>. By registering on our website, applying for our certification or diploma programs, submitting admission forms, or accessing our candidate portal, you agree to comply with and be bound by the following terms and conditions.</p>\n\n<h2>2. Eligibility & Admission Policy</h2>\n<p>Admissions to training programs offered by Finchskills Institute are subject to candidate eligibility verification, seat availability, and timely clearance of prescribed registration/admission fees. The Institute reserves the right to accept or reject any application based on documentation validity.</p>\n\n<h2>3. Fee Payment & Installment Policy</h2>\n<p>Course fees may be paid in full (with applicable full-payment discount) or through authorized part-wise installment plans. Installment dues must be cleared on or before their respective scheduled due dates. Delays in installment payments may lead to temporary suspension of class access and certificate processing.</p>\n\n<h2>4. Attendance & Code of Conduct</h2>\n<p>Students must maintain a minimum of 75% attendance throughout the course duration to be eligible for practical training modules, placement assistance, and final certification assessments. Any form of academic dishonesty, misconduct, or disruptive behavior will result in disciplinary action.</p>\n\n<h2>5. Placement Assistance Disclaimer</h2>\n<p>Finchskills Institute offers dedicated placement assistance, interview preparation, and corporate networking. While we make every endeavor to connect eligible candidates with leading employers in aviation, travel, and hospitality, final selection depends on the candidate\'s performance in interviews.</p>\n\n<h2>6. Intellectual Property</h2>\n<p>All study materials, lecture recordings, curriculum notes, and portal assets provided by Finchskills Institute remain the exclusive intellectual property of the Institute and may not be redistributed without prior written consent.</p>\n\n<h2>7. Contact Information</h2>\n<p>For any questions regarding these terms, please contact us at <a href=\"mailto:info@finchskills.com\">info@finchskills.com</a> or call +91 96503 86711.</p>', 'Terms and Conditions - Finchskills Institute', 'Official terms and conditions governing admission, code of conduct, and academic training at Finchskills Institute.', 'active', 1, '2026-09-22 17:18:03', '2026-09-22 17:18:03'),
(2, 'privacy-policy', 'Privacy Policy', 'How we collect, use, protect, and handle your personal information', '<h2>1. Information We Collect</h2>\n<p>When you register, apply for courses, or submit inquiries on our portal, we may collect personal identifiable information including your full name, email address, contact number, date of birth, educational qualifications, identification documents (such as Aadhaar), and payment transaction details.</p>\n\n<h2>2. How We Use Your Information</h2>\n<p>We use the collected information for the following legitimate purposes:</p>\n<ul>\n    <li>Processing course admissions, candidate registrations, and seat reservations.</li>\n    <li>Sending admission confirmation emails, fee receipts, and installment schedule reminders.</li>\n    <li>Managing candidate portal accounts and student progress tracking.</li>\n    <li>Communicating important academic updates, class schedules, and placement opportunities.</li>\n    <li>Complying with regulatory, auditing, and legal statutory obligations.</li>\n</ul>\n\n<h2>3. Data Protection & Security</h2>\n<p>We implement industry-standard security measures including SSL 256-bit encryption, restricted database access, and secure payment processing via PCI-DSS compliant payment gateways (such as Razorpay). We never store raw credit/debit card numbers or CVVs on our servers.</p>\n\n<h2>4. Third-Party Sharing</h2>\n<p>Finchskills Institute does not sell, trade, or rent candidate personal information to third parties. Information may only be shared with authorized academic accreditation bodies, background verification partners for placement, or payment gateway providers solely for service delivery.</p>\n\n<h2>5. Cookies & Analytics</h2>\n<p>Our website may use cookies and web analytics to improve browsing experience, analyze site traffic, and optimize our learning portal interfaces.</p>\n\n<h2>6. Your Rights & Inquiries</h2>\n<p>You may request correction of your profile data or ask questions regarding our privacy practices by writing to <a href=\"mailto:privacy@finchskills.com\">privacy@finchskills.com</a>.</p>', 'Privacy Policy - Finchskills Institute', 'Learn how Finchskills Institute collects, stores, and protects candidate personal and educational data.', 'active', 1, '2026-09-22 17:18:03', '2026-09-22 17:18:03'),
(3, 'refund-policy', 'Refund & Cancellation Policy', 'Guidelines regarding course enrollment cancellations and fee refunds', '<h2>1. Registration &amp; Seat Reservation Fee</h2><p>Initial seat reservation and candidate registration fees are generally non-refundable as they cover provisional seat allocation, portal license setup, and administrative onboarding costs.</p><h2>2. Course Admission Fee Refund Guidelines</h2><p>Requests for course fee refunds must be submitted in writing to the Accounts Department within the following timeframes:</p><ul><li><strong>Before Batch Commencement (2 days prior):</strong> Eligible for an 50% refund of the total fee paid (excluding administrative and processing fees).</li><li><strong>Within 2 Days of Batch Start:</strong> Eligible for up to 30% refund subject to management review.</li><li><strong>After 3 Days of Batch Commencement:</strong> No refunds will be issued once training classes and learning resources have been accessed.</li></ul><h2>3. Mode of Refund Processing</h2><p>Approved refunds are processed via the original payment method (bank account / payment gateway) within <strong>7 to 10 working days</strong> from the date of official written approval.</p><h2>4. Batch Transfers &amp; Rescheduling</h2><p>If a candidate cannot continue in their allocated batch due to medical or unavoidable circumstances, they may request a batch transfer or freeze their enrolment for up to 6 months without paying the course fee again.</p><h2>5. Contact for Refund Requests</h2><p>Please send all refund-related formal requests with payment receipts and student ID to <a href=\"mailto:accounts@finchskills.com\" rel=\"noopener noreferrer\" target=\"_blank\">accounts@finchskills.com</a>.</p>', 'Refund & Cancellation Policy - Finchskills Institute', 'Official fee refund and course cancellation policy of Finchskills Institute.', 'active', 1, '2026-10-08 04:56:52', '2026-09-22 17:18:03'),
(4, 'shipping-policy', 'Shipping & Delivery Policy', 'Details regarding digital training access, study kit delivery, and certificate dispatch', '<h2>1. Digital Access Delivery</h2><p>Upon successful registration and fee verification, candidate portal credentials, course syllabus, and lecture timetables are delivered instantly via email and portal dashboard.</p><h2>2. Physical Study &amp; Training Material</h2><p>For classroom and hybrid batches where physical kits, books, or uniforms are included, items are handed over at the institute centre during the orientation session or dispatched via speed courier within <strong>5 to 7 business days</strong>.</p><h2>3. Certificate &amp; Diploma Dispatch</h2><p>Official course completion certificates and diplomas are awarded during convocation or dispatched via registered post / courier to the candidate\'s verified residential address within <strong>15 days</strong> of final assessment clearance.</p><h2>4. Tracking &amp; Support</h2><p>For any queries related to material or certificate delivery, please contact our support desk at <a href=\"mailto:support@finchskills.com\" rel=\"noopener noreferrer\" target=\"_blank\">support@finchskills.com</a>.</p>', 'Shipping & Delivery Policy - Finchskills Institute', 'Delivery terms for study materials, portal access, and physical diploma certificates.', 'active', 1, '2026-10-08 04:58:31', '2026-09-22 17:18:03'),
(5, 'disclaimer', 'Disclaimer', 'General educational and informational disclaimer', '<h2>1. General Information</h2><p>The information provided on the Finchskills Institute website is for general educational, skill development, and career guidance purposes. While we endeavor to keep all information up to date and correct, course structures and curriculum may evolve to match industry standards.</p><h2>2. Placement Assistance</h2><p>Placement assistance provided by Finchskills Institute refers to career counseling, interview scheduling, resume building, and recruiter networking. We do not sell or guarantee government or private employment contracts.</p><h2>3. External Links</h2><p>Our website may contain links to external third-party websites for reference. Finchskills Institute has no control over the nature, content, and availability of those external sites.</p>', 'Disclaimer - Finchskills Institute', 'General educational and placement disclaimer of Finchskills Institute.', 'active', 1, '2026-10-08 04:59:14', '2026-09-22 17:18:03');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('address', 'Orbit Plaza, Crossing Republik, NH-24, Ghaziabad, Uttar Pradesh, 201016', '2026-09-09 00:24:35'),
('email_1', 'hello@finchskills.com', '2026-09-09 00:24:35'),
('email_2', 'admission@finchskills.com', '2026-09-09 00:24:35'),
('facebook_url', '', '2026-10-01 05:21:54'),
('footer_logo', 'img/footer-logo.png', '2026-09-09 00:24:35'),
('full_payment_discount_percent', '10', '2026-09-22 16:58:12'),
('instagram_url', 'https://instagram.com/', '2026-09-09 00:24:35'),
('last_seat_cron_run', '2026-10-13 17:44:42', '2026-10-13 12:14:42'),
('linkedin_url', '', '2026-10-01 05:21:54'),
('map_iframe', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3501.9501409758395!2d77.43262847457278!3d28.63125638414035!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390cee300c363997%3A0xe53a3aaacf648c83!2sOrbit%20plaza%2C%20Crossings%20Republik%2C%20Ghaziabad%2C%20Uttar%20Pradesh%20201016!5e0!3m2!1sen!2sin!4v1784362846035!5m2!1sen!2sin', '2026-09-09 00:24:35'),
('phone_1', '+919650386711', '2026-09-09 00:24:35'),
('phone_2', '', '2026-10-01 05:23:36'),
('razorpay_company_name', 'Finchskills Institute', '2026-09-26 03:17:48'),
('razorpay_enabled', '1', '2026-09-21 02:09:02'),
('razorpay_live_key_id', 'rzp_live_TgX3xjZ7Fn5Ule', '2026-09-30 01:43:02'),
('razorpay_live_key_secret', '7QLHxskBbll596RZ7qFcAUlt', '2026-09-30 01:43:02'),
('razorpay_mode', 'live', '2026-10-13 14:14:36'),
('razorpay_test_key_id', 'rzp_test_Tldpk2BO12Wkf9', '2026-10-11 06:07:47'),
('razorpay_test_key_secret', 'N3ELG67v1VK1Dl6WfcbmrlFY', '2026-10-11 06:07:47'),
('seat_booking_fee', '2750', '2026-10-13 14:04:15'),
('seat_validity_days', '7', '2026-09-28 11:04:05'),
('site_favicon', 'img/favicon.png', '2026-09-09 00:24:35'),
('site_logo', 'img/logo.png', '2026-09-09 00:24:35'),
('smtp_enabled', '1', '2026-09-21 02:28:42'),
('smtp_from_email', 'hello@finchskills.com', '2026-09-21 02:37:35'),
('smtp_from_name', 'Finchskills Institute', '2026-09-21 02:28:42'),
('smtp_host', 'mail.finchskills.com', '2026-09-21 02:37:35'),
('smtp_password', 'Finc@1234$', '2026-09-21 02:37:35'),
('smtp_port', '465', '2026-09-21 02:37:35'),
('smtp_reply_to', 'hello@finchskills.com', '2026-09-21 02:28:42'),
('smtp_secure', 'ssl', '2026-09-21 02:37:35'),
('smtp_username', 'support@finchskills.com', '2026-09-21 02:37:35'),
('timing_mon_fri', 'Monday - Friday: 10:00 AM - 05:00 PM', '2026-09-09 00:24:35'),
('timing_sat', 'Saturday: 10:00 AM - 02:00 PM', '2026-09-09 00:24:35'),
('twitter_url', 'https://twitter.com/', '2026-09-09 00:24:35'),
('whatsapp_number', '919650386711', '2026-09-09 00:24:35'),
('youtube_url', '', '2026-10-01 05:21:54');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aadhaar` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `seat_status` enum('Pending','Reserved','Confirmed','Cancelled','Expired') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `booked_course_id` int DEFAULT NULL,
  `booked_course` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('Unpaid','Paid','Skipped') COLLATE utf8mb4_unicode_ci DEFAULT 'Unpaid',
  `payment_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT '0.00',
  `payment_date` datetime DEFAULT NULL,
  `seat_valid_until` datetime DEFAULT NULL,
  `seat_reminder_day2_at` datetime DEFAULT NULL,
  `seat_reminder_day4_at` datetime DEFAULT NULL,
  `seat_reminder_final_at` datetime DEFAULT NULL,
  `seat_cancelled_at` datetime DEFAULT NULL,
  `seat_reactivated_at` datetime DEFAULT NULL,
  `seat_reactivated_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admission_id` int DEFAULT NULL,
  `reset_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note for existing databases:
-- ALTER TABLE `students` ADD COLUMN `registration_id` VARCHAR(50) NULL AFTER `student_id`;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `phone`, `aadhaar`, `photo`, `password`, `otp_code`, `otp_expires_at`, `is_verified`, `seat_status`, `booked_course_id`, `booked_course`, `payment_status`, `payment_id`, `payment_amount`, `payment_date`, `seat_valid_until`, `seat_reminder_day2_at`, `seat_reminder_day4_at`, `seat_reminder_final_at`, `seat_cancelled_at`, `seat_reactivated_at`, `seat_reactivated_by`, `admission_id`, `reset_token`, `reset_expires_at`, `created_at`) VALUES
(8, 'FS-2026-6755', 'Neha Verma', 'nehaverma182002@gmail.com', '8700272939', '545555655565', 'uploads/students/std_1790755918_259.jpg', '$2y$10$KW8UZr5XNqp/Gz1jh2VlyuHqiP1sx3PJ74.NBtMsM4votFdYdvmVK', NULL, '2026-09-30 08:26:58', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-30 08:11:58'),
(9, 'FS-2026-8015', 'Rahul', 'vk851370@gmail.com', '9265256545', '875643686368', 'uploads/students/std_1790786356_153.jpg', '$2y$10$BDW4oN7Gk1Z/pavVaUidYOUYaIhmxTA3o4q9HyCrEUrvVYE0PyJ4S', NULL, '2026-09-30 16:54:16', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-30 16:39:14'),
(10, 'FS-2026-9351', 'Shabanam Singh', 'shabanamsingh28082004@gmail.com', '9279908018', '332566783348', 'uploads/students/std_1790852487_284.jpg', '$2y$10$ligT8DFga4SF5pU0tswXvOgjz2WkqlNL3YoaMTlwaWrSnHz2HW0Ie', '571808', '2026-10-01 11:16:27', 0, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-01 11:01:27'),
(11, 'FS-2026-7521', 'Akshit Agrawal', 'akshitakshitagarwal1@gmail.com', '9460447060', '699684535770', 'uploads/students/std_1791364212_140.jpg', '$2y$10$pxYXfdo7JIXWHtKkxsfmfuevASEL2HLgphh56XKYA7B1TgIEeSxiq', NULL, '2026-10-07 09:25:12', 1, 'Confirmed', 10, 'Certification Course in Travel Management', 'Paid', 'pay_TkyItQnUtwKfio', 999.00, '2026-10-07 15:08:58', '2026-10-14 09:20:20', NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, NULL, '2026-10-07 09:10:12'),
(12, 'FS-2026-2471', 'Durgesh Vijay Shinde', 'shindedurgesh81@gmail.com', '8485845778', '251817286692', 'uploads/students/std_1791366522_148.jpg', '$2y$10$IBqawt75ahNh9L6aeYpNyuMAQHVcEkyKMsGhlc1Tue/T.i8Oq9eNm', NULL, '2026-10-07 10:03:42', 1, 'Confirmed', 10, 'Certification Course in Travel Management', 'Paid', 'pay_TkzH5Jofiuokyu', 2750.00, '2026-10-07 16:06:03', '2026-10-14 10:36:03', NULL, NULL, NULL, NULL, NULL, NULL, 10, NULL, NULL, '2026-10-07 09:48:42'),
(13, 'FS-2026-5800', 'Asmita Arun Ovhal', 'ovhal.asmita11@gmail.com', '8459744610', '690520113505', 'uploads/students/std_1791371992_688.jpg', '$2y$10$EIVjs8PC8FY5UJlqtCVaEe9moz.QbNyoPaZnJgjnkeZ7FWIiYUFfe', NULL, '2026-10-07 11:34:52', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-07 11:19:52'),
(14, 'FS-2026-5599', 'Nikita Patel', 'shilpisingh7398@gmail.com', '8765545489', '867656454877', 'uploads/students/std_1791378841_803.png', '$2y$10$vWOmicSBh8oWqV5EW8pRY.CaL/IKQDNSdA2moUh93Mr74Q1JCwi1S', NULL, '2026-10-07 13:29:01', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, NULL, '2026-10-07 13:14:01'),
(15, 'FS-2026-3206', 'Mayank sati', 'tehmayanksati@gmail.com', '7505364505', '305598210185', 'uploads/students/std_1791436899_847.jpeg', '$2y$10$JgbGLM6RYD0ikDYQzhAGJeHkAtvb2A2sUAaJJjGUtpxG.QeG3oYly', '177823', '2026-10-08 05:36:39', 0, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-08 05:21:39'),
(16, 'FS-2026-6630', 'Mayank sati', 'themayanksati@gmail.com', '7505364505', '305598210185', 'uploads/students/std_1791436978_425.jpeg', '$2y$10$VBbKSq9Zh/ei.ir5Eubh5OYusG888WnmfXQD0/Vh77clZilFcWqaq', NULL, '2026-10-08 05:37:58', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, NULL, NULL, '2026-10-08 05:22:58'),
(17, 'FS-2026-4924', 'Aaqib Shaikh', 'aaqibalamshaikh@gmail.com', '9326118669', '916241091295', 'uploads/students/std_1791443438_184.png', '$2y$10$OTvOi8IqqZZFBVXYs4y7iOI0tpyvYwhpOYThT21I6WRoiOwXJNweu', NULL, '2026-10-08 07:25:38', 1, 'Confirmed', 10, 'Certification Course in Travel Management', 'Paid', 'pay_TlP7ZcX36DscuC', 2750.00, '2026-10-08 17:23:13', '2026-10-15 11:53:13', NULL, NULL, NULL, NULL, NULL, NULL, 14, NULL, NULL, '2026-10-08 07:10:38'),
(18, 'FS-2026-2444', 'Taskeen Firdous', 'taskeenf980@gmail.com', '9325468191', '966743671219', 'uploads/students/std_1791447170_245.jpeg', '$2y$10$meP4KWpM6PPK9L5O9qFNj.MLjWZGUVrxi.tLz10Ff4azxP4CtF8VS', NULL, '2026-10-08 08:27:50', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 13, NULL, NULL, '2026-10-08 08:12:49'),
(19, 'FS-2026-9901', 'Biplaw kumar Dutta', 'biplawkumardutta@gmail.com', '9334978916', '597998354518', 'uploads/students/std_1791532022_300.jpg', '$2y$10$BiL/iyBWGlgwL3TC8tpyDuSowlkY/4A8Nbj.7vkChL8Mbs7Mp1sKa', NULL, '2026-10-09 08:02:02', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, NULL, '2026-10-09 07:47:02'),
(20, 'FS-2026-9477', 'Mahak pandey', 'mahakp672@gmail.com', '8755857938', '873970254769', 'uploads/students/std_1791536509_626.jpg', '$2y$10$N3DJ7/7aJBJeTDjJsmAWeunkGmJs8gsMFsRv78llJ.drC0yTjaLB.', NULL, '2026-10-09 09:16:49', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-09 09:01:49'),
(21, 'FS-2026-6851', 'Rahul', 'niranjansinghkhurja36@gmail.com', '8454654549', '726266363663', 'uploads/students/std_1791540127_766.jpg', '$2y$10$V7D6q9YJQwiu2adOfGrqyOgvczKJEDl99dpPYe3SuBubKLc8FsTn2', NULL, '2026-10-09 10:17:07', 1, 'Pending', NULL, NULL, 'Unpaid', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-09 10:02:07'),
(22, 'FS-2026-3786', 'Nikhil singh', 'nikhilrura5@gmail.com', '7317788940', '559687401325', 'uploads/students/std_1791699160_686.jpeg', '$2y$10$fl1R7VmK.z3tNjp6r/.g3.TyxyT7OaV3QOBAtLIUnH8N6AMO9DcmC', NULL, NULL, 1, 'Confirmed', 2, 'Certificate Course in Travel & Air Ticketing', 'Paid', 'pay_TmUwNH79gjRzUS', 2750.00, '2026-10-11 11:43:58', '2026-10-18 06:13:58', NULL, NULL, NULL, NULL, NULL, NULL, 16, NULL, NULL, '2026-10-11 06:13:58'),
(23, 'FS-2026-3902', 'madhav', 'orioninaht@gmail.com', '8347647757', '987788766556', 'uploads/students/std_1791701207_796.jpg', '$2y$10$B1UaSDeqjkBji283v4.Eg.gnbKRUwEMa0bEKSCFztSIVSR5u9n4s6', NULL, NULL, 1, 'Confirmed', 10, 'Certification Course in Travel Management', 'Paid', 'pay_TmVXcVFJHr35pT', 2750.00, '2026-10-11 12:18:54', '2026-10-18 06:48:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-11 06:48:54'),
(24, 'FS-2026-4574', 'madhav', 'iconicfounder55@gmail.com', '8344335663', '878867668877', 'uploads/students/std_1791900463_368.jpg', '$2y$10$OOkJ6Y67nBXvzyWvcmIBqOJna29Gu8Ei0gxb5mQoKhPsrAXlALQRe', NULL, NULL, 1, 'Confirmed', 10, 'Certification Course in Travel Management', 'Paid', 'pay_TnQ70799b12W5n', 2750.00, '2026-10-13 19:39:13', '2026-10-20 14:09:13', NULL, NULL, NULL, NULL, NULL, NULL, 17, NULL, NULL, '2026-10-13 14:09:13');

-- --------------------------------------------------------

--
-- Table structure for table `student_installments`
--

CREATE TABLE `student_installments` (
  `id` int NOT NULL,
  `student_db_id` int DEFAULT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admission_id` int NOT NULL,
  `enrollment_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_course_fee` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `seat_fee_adjusted` decimal(10,2) DEFAULT '0.00',
  `final_payable` decimal(10,2) NOT NULL,
  `payment_type` enum('full','installment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `total_parts` int NOT NULL DEFAULT '1',
  `installment_no` int NOT NULL DEFAULT '1',
  `installment_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1st Installment (Admission Fee)',
  `installment_amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `status` enum('Paid','Pending','Overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_installments`
--

INSERT INTO `student_installments` (`id`, `student_db_id`, `student_id`, `admission_id`, `enrollment_no`, `course_title`, `total_course_fee`, `discount_amount`, `seat_fee_adjusted`, `final_payable`, `payment_type`, `total_parts`, `installment_no`, `installment_title`, `installment_amount`, `due_date`, `paid_date`, `status`, `payment_id`, `receipt_no`, `reminder_sent`, `created_at`) VALUES
(11, 11, 'FS-2026-7521', 6, 'ENR-2026-01006', 'Certification Course in Travel Management', 22500.00, 0.00, 2750.00, 19750.00, 'installment', 2, 1, '1st Installment (Payable at Admission)', 9875.00, '2026-10-07', '2026-10-07 09:38:58', 'Paid', 'pay_TkyItQnUtwKfio', 'REC-74F997E7', 0, '2026-10-07 09:38:58'),
(12, 11, 'FS-2026-7521', 6, 'ENR-2026-01006', 'Certification Course in Travel Management', 22500.00, 0.00, 2750.00, 19750.00, 'installment', 2, 2, '2th Monthly Installment', 9875.00, '2026-11-07', NULL, 'Pending', NULL, NULL, 0, '2026-10-07 09:38:58'),
(13, 22, 'FS-2026-3786', 16, 'ENR-2026-01016', 'Certificate Course in Travel & Air Ticketing', 31500.00, 3150.00, 2750.00, 25600.00, 'full', 1, 1, 'Full Course Fee Payment', 25600.00, '2026-10-11', '2026-10-11 11:50:48', 'Paid', 'pay_TmV3wFfTxnmgQy', 'REC-F98105CB', 0, '2026-10-11 06:20:48'),
(14, 24, 'FS-2026-4574', 17, 'ENR-2026-01017', 'Certification Course in Travel Management', 22500.00, 0.00, 2750.00, 19750.00, 'installment', 2, 1, '1st Installment (Payable at Admission)', 9875.00, '2026-10-13', '2026-10-13 14:12:58', 'Paid', 'pay_TnQAwgHeayl9bf', 'REC-FDC49933', 0, '2026-10-13 14:12:58'),
(15, 24, 'FS-2026-4574', 17, 'ENR-2026-01017', 'Certification Course in Travel Management', 22500.00, 0.00, 2750.00, 19750.00, 'installment', 2, 2, '2th Monthly Installment', 9875.00, '2026-11-13', NULL, 'Pending', NULL, NULL, 0, '2026-10-13 14:12:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enrollment_no` (`enrollment_no`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `fee_submissions`
--
ALTER TABLE `fee_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_pages`
--
ALTER TABLE `site_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `student_installments`
--
ALTER TABLE `student_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `admission_id` (`admission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `fee_submissions`
--
ALTER TABLE `fee_submissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `site_pages`
--
ALTER TABLE `site_pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `student_installments`
--
ALTER TABLE `student_installments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
