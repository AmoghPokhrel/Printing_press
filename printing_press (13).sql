-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 09:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `printing_press`
--

-- --------------------------------------------------------

--
-- Table structure for table `additional_field_types`
--

CREATE TABLE `additional_field_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `additional_field_types`
--

INSERT INTO `additional_field_types` (`id`, `name`, `type`, `created_at`) VALUES
(1, 'Text Input', 'text', '2025-04-30 06:59:53'),
(2, 'Text Area', 'textarea', '2025-04-30 06:59:53'),
(3, 'Image Upload', 'image', '2025-04-30 06:59:53'),
(4, 'Date', 'date', '2025-04-30 06:59:53'),
(5, 'Time', 'time', '2025-04-30 06:59:53'),
(6, 'Email', 'email', '2025-04-30 06:59:53'),
(7, 'URL', 'url', '2025-04-30 06:59:53'),
(8, 'Phone Number', 'tel', '2025-04-30 06:59:53');

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_28`
--

CREATE TABLE `additional_info_28` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `request_type` enum('modification','custom') NOT NULL DEFAULT 'custom',
  `template_modification_id` int(11) DEFAULT NULL,
  `brides_name` varchar(255) DEFAULT NULL,
  `grooms_name` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_29`
--

CREATE TABLE `additional_info_29` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `org_name` varchar(255) DEFAULT NULL,
  `org_location` varchar(255) DEFAULT NULL,
  `request_type` enum('modification','custom') NOT NULL DEFAULT 'custom',
  `template_modification_id` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `additional_info_29`
--

INSERT INTO `additional_info_29` (`id`, `user_id`, `request_id`, `created_at`, `org_name`, `org_location`, `request_type`, `template_modification_id`) VALUES
(17, 25, 64, '2025-05-20 17:36:50', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(19, 25, 65, '2025-05-27 16:15:57', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(20, 25, 68, '2025-05-31 18:35:11', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(21, 25, 69, '2025-05-31 18:39:26', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(22, 25, 70, '2025-05-31 21:18:23', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(23, 25, 71, '2025-06-01 06:42:05', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(24, 25, 74, '2025-06-01 07:13:03', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(26, 27, NULL, '2025-06-02 17:13:42', 'Himalaya Darshan College', 'Biratnagar 10', 'modification', 48),
(27, 27, NULL, '2025-06-02 17:14:31', 'Birat Medical', 'Biratnagar 10', 'modification', 49),
(28, 27, 76, '2025-06-02 19:09:26', 'Himalaya Darshan College', 'Biratnagar 10', 'custom', NULL),
(29, 27, NULL, '2025-06-02 19:13:12', 'Himalaya Darshan College', 'Biratnagar 10', 'modification', 54),
(31, 25, NULL, '2025-06-02 19:16:01', 'Himalaya Darshan College', 'Biratnagar 10', 'modification', 56),
(32, 25, NULL, '2025-06-02 19:18:04', 'Himalaya Darshan College', 'Biratnagar 10', 'modification', 57);

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_30`
--

CREATE TABLE `additional_info_30` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_name` varchar(255) DEFAULT NULL,
  `org_location` varchar(255) DEFAULT NULL,
  `org_name` varchar(255) DEFAULT NULL,
  `request_type` enum('modification','custom') NOT NULL DEFAULT 'custom',
  `template_modification_id` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `additional_info_30`
--

INSERT INTO `additional_info_30` (`id`, `user_id`, `request_id`, `created_at`, `event_name`, `org_location`, `org_name`, `request_type`, `template_modification_id`) VALUES
(11, 25, 67, '2025-05-31 18:32:12', 'holi', 'Biratnagar', 'Himalaya Darshan College', 'custom', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_33`
--

CREATE TABLE `additional_info_33` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `template_modification_id` int(11) DEFAULT NULL,
  `request_type` enum('modification','custom') NOT NULL DEFAULT 'custom',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `org_name` varchar(255) DEFAULT NULL,
  `org_location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_34`
--

CREATE TABLE `additional_info_34` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `template_modification_id` int(11) DEFAULT NULL,
  `request_type` enum('custom','modification') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_38`
--

CREATE TABLE `additional_info_38` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `template_modification_id` int(11) DEFAULT NULL,
  `request_type` enum('custom','modification') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `printing_design` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_39`
--

CREATE TABLE `additional_info_39` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `template_modification_id` int(11) DEFAULT NULL,
  `request_type` enum('custom','modification') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `org_name` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `additional_info_39`
--

INSERT INTO `additional_info_39` (`id`, `user_id`, `request_id`, `template_modification_id`, `request_type`, `created_at`, `org_name`) VALUES
(8, 25, 73, NULL, 'custom', '2025-06-01 07:10:06', 'Himalaya Darshan College'),
(9, 27, 77, NULL, 'custom', '2025-06-02 19:30:14', 'Himalaya Darshan College');

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_40`
--

CREATE TABLE `additional_info_40` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `template_modification_id` int(11) DEFAULT NULL,
  `request_type` enum('custom','modification') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) DEFAULT NULL,
  `roll_number` date DEFAULT NULL,
  `dob` date DEFAULT NULL
) ;

--
-- Dumping data for table `additional_info_40`
--

INSERT INTO `additional_info_40` (`id`, `user_id`, `request_id`, `template_modification_id`, `request_type`, `created_at`, `name`, `roll_number`, `dob`) VALUES
(1, 27, 75, NULL, 'custom', '2025-06-02 16:55:44', 'Amogh Pokhrel', '2025-06-13', '2009-05-02');

-- --------------------------------------------------------

--
-- Table structure for table `additional_info_fields`
--

CREATE TABLE `additional_info_fields` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `field_type_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `additional_info_fields`
--

INSERT INTO `additional_info_fields` (`id`, `category_id`, `field_type_id`, `field_name`, `field_label`, `is_required`, `display_order`, `created_at`, `user_id`) VALUES
(19, 29, 1, 'org_name', 'Organization Name', 1, 0, '2025-05-01 16:49:45', NULL),
(20, 29, 1, 'org_location', 'Organization Location', 1, 0, '2025-05-01 17:13:48', NULL),
(21, 30, 1, 'event_name', 'Event Name', 1, 0, '2025-05-01 17:20:21', NULL),
(22, 30, 2, 'org_location', 'Organization Location', 1, 0, '2025-05-01 17:20:44', NULL),
(23, 30, 2, 'org_name', 'Organization Name', 1, 0, '2025-05-01 17:20:57', NULL),
(34, 39, 1, 'org_name', 'Organization Name', 0, 0, '2025-05-12 16:48:44', 20),
(35, 28, 1, 'brides_name', 'Brides Name', 1, 0, '2025-06-01 05:59:55', 20),
(36, 28, 1, 'grooms_name', 'Grooms Name', 1, 0, '2025-06-01 06:00:17', 20),
(37, 40, 1, 'name', 'Name', 1, 0, '2025-06-02 16:47:21', 20),
(38, 40, 1, 'roll_number', 'Roll Number', 1, 0, '2025-06-02 16:47:51', 20),
(39, 40, 4, 'dob', 'Date Of Birth', 1, 0, '2025-06-02 16:48:11', 20);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `availability` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `user_id`, `availability`) VALUES
(3, 20, 'active'),
(4, 21, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `uid`, `created_at`, `updated_at`) VALUES
(8, 25, '2025-06-01 06:55:27', '2025-06-01 06:55:27'),
(9, 27, '2025-06-02 17:13:09', '2025-06-02 17:13:09');

-- --------------------------------------------------------

--
-- Table structure for table `cart_item_line`
--

CREATE TABLE `cart_item_line` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `unique_id` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `template_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `custom_request_id` int(11) DEFAULT NULL,
  `final_design` varchar(255) DEFAULT NULL,
  `req_type` enum('custom','modify') DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('active','purchased') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item_line`
--

INSERT INTO `cart_item_line` (`id`, `cart_id`, `unique_id`, `quantity`, `created_at`, `template_id`, `request_id`, `custom_request_id`, `final_design`, `req_type`, `price`, `status`) VALUES
(115, 8, 'custom_683bf95f1e97e5.99313050', 1, '2025-06-01 06:55:27', NULL, NULL, 71, 'final/final_683bf8564c2df3.97827874_1190ac3dcb8444123fe4a38caf56ccb3.jpg', 'custom', 67.00, ''),
(116, 8, 'custom_683c13477fc415.35317311', 1, '2025-06-01 08:45:59', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, ''),
(117, 8, 'custom_683c136e435d42.23680218', 1, '2025-06-01 08:46:38', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, ''),
(119, 8, 'custom_683c17217eae49.12202199', 1, '2025-06-01 09:02:25', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, ''),
(120, 8, 'custom_683c181ea012c7.54601863', 1, '2025-06-01 09:06:38', NULL, NULL, 73, '683c18068d2f9_77a0b99def2483fe7a88ff63c33179e4.jpg', 'custom', 44.00, ''),
(121, 8, 'custom_683c6ba74cd1b9.38511296', 1, '2025-06-01 15:03:03', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, ''),
(122, 8, 'custom_683c6cb7049922.00628609', 1, '2025-06-01 15:07:35', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, ''),
(123, 8, 'custom_683c6d63d3c3c2.91716909', 1, '2025-06-01 15:10:27', NULL, NULL, 73, '683c18068d2f9_77a0b99def2483fe7a88ff63c33179e4.jpg', 'custom', 44.00, ''),
(124, 8, 'template_683d1d51d10bb4.61869286', 33, '2025-06-02 03:41:07', 17, 46, NULL, '1748829323_683d048b490ba.jpg', 'modify', 25.00, ''),
(125, 8, 'template_683da65681ddd9.03586073', 33, '2025-06-02 13:26:49', 17, 46, NULL, '1748829323_683d048b490ba.jpg', 'modify', 25.00, ''),
(127, 8, 'template_683da735621984.78950484', 33, '2025-06-02 13:29:27', 17, 46, NULL, '1748829323_683d048b490ba.jpg', 'modify', 25.00, ''),
(128, 8, 'custom_683da73c828591.29221685', 1, '2025-06-02 13:29:32', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, ''),
(129, 9, 'custom_683ddba5a990c4.12973741', 1, '2025-06-02 17:13:09', NULL, NULL, 75, '683ddb8d9d641_istockphoto-691286862-612x612.jpg', 'custom', 47.00, 'active'),
(130, 9, 'template_683df683991230.55233083', 1, '2025-06-02 19:07:51', 28, 49, NULL, '1748890715_683df45b954fb.png', 'modify', 4.00, 'active'),
(131, 8, 'template_683dfa8be33416.91809135', 11, '2025-06-02 19:25:04', 27, 57, NULL, '1748892269_683dfa6dd558f.png', 'modify', 3.00, ''),
(132, 8, 'custom_683dfb59d6fc82.96104217', 1, '2025-06-02 19:28:25', NULL, NULL, 74, '683c12cd84181_Screenshot 2025-06-01 115320.png', 'custom', 34.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `c_id` int(11) NOT NULL,
  `c_Name` varchar(255) NOT NULL,
  `c_discription` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`c_id`, `c_Name`, `c_discription`, `created_at`, `admin_id`) VALUES
(28, 'Wedding Card', 'This is a weeding card', '2025-05-01 15:07:29', NULL),
(29, 'Business Card', 'This is Business card.', '2025-05-01 15:19:24', NULL),
(30, 'Flex', 'This is Flex.', '2025-05-01 15:19:47', NULL),
(39, 'Poster', 'this is poster', '2025-05-12 16:47:54', 3),
(40, 'ID Card', 'This is ID Card', '2025-06-02 16:46:41', 3);

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

CREATE TABLE `colors` (
  `id` int(11) NOT NULL,
  `color_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `color_name`) VALUES
(2, 'Black and White'),
(4, 'Custom Color'),
(1, 'Full Color'),
(3, 'Grayscale');

-- --------------------------------------------------------

--
-- Table structure for table `custom_template_requests`
--

CREATE TABLE `custom_template_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `media_type_id` int(11) NOT NULL,
  `size` varchar(50) NOT NULL,
  `orientation` enum('Portrait','Landscape') NOT NULL,
  `color_scheme` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_range` varchar(50) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `reference_image` varchar(255) DEFAULT NULL,
  `status` enum('Pending','In Progress','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `final_design` varchar(255) DEFAULT NULL,
  `preferred_staff_id` int(11) DEFAULT NULL,
  `preferred_color` varchar(7) DEFAULT '#000000',
  `secondary_color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_template_requests`
--

INSERT INTO `custom_template_requests` (`id`, `user_id`, `category_id`, `media_type_id`, `size`, `orientation`, `color_scheme`, `quantity`, `price_range`, `additional_notes`, `reference_image`, `status`, `assigned_staff_id`, `created_at`, `updated_at`, `final_design`, `preferred_staff_id`, `preferred_color`, `secondary_color`) VALUES
(73, 25, 39, 10, '2 inch x 2 inch', 'Portrait', 'Black and White', 1, 'Under Rs 50', 'pleaseee', 'custom_683bfcc685c811.11337660_2e50c028b3cf0827caa254294517e753.jpg', 'Completed', 8, '2025-06-01 03:24:58', '2025-06-01 09:06:14', 'final/683c18068d2f9_77a0b99def2483fe7a88ff63c33179e4.jpg', NULL, '#000000', '#ffffff'),
(74, 25, 29, 8, '2 inches x 2 inches', 'Landscape', 'Black and White', 323, 'Under Rs 50', 'yess pleasee', 'custom_683bfd755e4955.49549501_Screenshot 2025-06-01 115320.png', 'Completed', 7, '2025-06-01 03:27:53', '2025-06-01 08:43:57', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', NULL, '#000000', '#ffffff'),
(75, 27, 40, 10, '2 inches x 3 inches', 'Landscape', 'Custom Color', 1, 'Under Rs 50', '', 'custom_683dd76bb950f6.50301463_istockphoto-691286862-612x612.jpg', 'Completed', 13, '2025-06-02 13:10:07', '2025-06-02 17:12:45', 'final/683ddb8d9d641_istockphoto-691286862-612x612.jpg', 13, '#0fa3ff', '#dbf3ff'),
(77, 27, 39, 8, '2 inch x 2 inch', 'Landscape', 'Black and White', 1, 'Under Rs 50', '', 'custom_683dfbc348d0d0.89292722_50a0959a5fe9dbdb3bd88e2ecda74ec5.jpg', 'Completed', 13, '2025-06-02 15:45:11', '2025-06-02 19:32:09', 'final/683dfc39d5b20_50a0959a5fe9dbdb3bd88e2ecda74ec5.jpg', 13, '#000000', '#ffffff');

-- --------------------------------------------------------

--
-- Table structure for table `designer`
--

CREATE TABLE `designer` (
  `id` int(11) NOT NULL,
  `expertise` varchar(255) NOT NULL,
  `portfolio_link` varchar(255) DEFAULT NULL,
  `years_experience` int(11) NOT NULL CHECK (`years_experience` >= 0),
  `software_skills` varchar(255) NOT NULL,
  `certifications` text DEFAULT NULL,
  `availability` enum('Full-time','Part-time','Freelance') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designer`
--

INSERT INTO `designer` (`id`, `expertise`, `portfolio_link`, `years_experience`, `software_skills`, `certifications`, `availability`, `created_at`, `updated_at`, `staff_id`) VALUES
(14, 'Designing', 'https://www.youtube.com/watch?v=Y5jCSqMy1DI', 6, 'Figma', 'figma course Nepal', 'Full-time', '2025-05-01 12:23:30', '2025-05-01 12:23:30', 7),
(15, 'Designing', 'https://www.youtube.com/watch?v=Y5jCSq', 3, 'Figma', 'figma course India', 'Full-time', '2025-05-01 12:42:04', '2025-05-01 12:42:04', 8),
(16, 'Designing', 'https://www.youtube.com/watch?v=Y5jCSqMy1DI', 5, 'Figma', 'figma course Nep', 'Part-time', '2025-06-02 03:26:36', '2025-06-02 03:26:36', 13);

-- --------------------------------------------------------

--
-- Table structure for table `design_revisions`
--

CREATE TABLE `design_revisions` (
  `id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `revision_number` int(11) DEFAULT NULL,
  `is_satisfied` tinyint(1) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `staff_comment` text DEFAULT NULL,
  `final_design` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `design_revisions`
--

INSERT INTO `design_revisions` (`id`, `request_id`, `revision_number`, `is_satisfied`, `feedback`, `staff_comment`, `final_design`, `price`, `created_at`) VALUES
(54, 74, 1, 1, 'Customer is satisfied with the design.', NULL, '683c12cd84181_Screenshot 2025-06-01 115320.png', 34.00, '2025-06-01 08:43:57'),
(55, 73, 1, 1, 'Customer is satisfied with the design.', NULL, '683c18068d2f9_77a0b99def2483fe7a88ff63c33179e4.jpg', 44.00, '2025-06-01 09:06:14'),
(56, 75, 1, 1, 'Customer is satisfied with the design.', NULL, '683ddab1acbae_istockphoto-691286862-612x612.jpg', 47.00, '2025-06-02 17:09:05'),
(57, 75, 2, 1, 'Customer is satisfied with the design.', NULL, '683ddb3c0d520_istockphoto-691286862-612x612.jpg', 47.00, '2025-06-02 17:11:24'),
(58, 75, 3, 1, 'Customer is satisfied with the design.', NULL, '683ddb8d9d641_istockphoto-691286862-612x612.jpg', 47.00, '2025-06-02 17:12:45'),
(59, 77, 1, NULL, NULL, NULL, '683dfc39d5b20_50a0959a5fe9dbdb3bd88e2ecda74ec5.jpg', 33.00, '2025-06-02 19:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `media_type`
--

CREATE TABLE `media_type` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_type`
--

INSERT INTO `media_type` (`id`, `name`, `created_at`, `admin_id`) VALUES
(8, 'Paper', '2025-05-01 15:23:23', NULL),
(9, 'Flex', '2025-05-01 15:23:28', NULL),
(10, 'Hard Plastic', '2025-05-01 15:23:52', NULL),
(12, 'Grafite', '2025-05-08 13:50:26', 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `reference_id`, `reference_type`, `is_read`, `created_at`) VALUES
(40, 20, 'New Custom Template Request', 'A new custom template request has been submitted for Business Card', 'custom_request', 71, 'custom_template', 1, '2025-06-01 06:42:01'),
(41, 21, 'New Custom Template Request', 'A new custom template request has been submitted for Business Card', 'custom_request', 71, 'custom_template', 0, '2025-06-01 06:42:01'),
(43, 23, 'New Custom Request Assignment', 'You have been assigned a new custom request for Business Card', 'custom_request', 71, 'custom_template', 1, '2025-06-01 06:42:54'),
(44, 25, 'Custom Request Completed', 'Your custom request for Business Card has been completed. The final design is ready for review.', 'custom_request', 71, 'custom_template', 1, '2025-06-01 06:51:02'),
(45, 23, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #71)', 'custom_design_feedback', 71, 'custom_template', 1, '2025-06-01 06:53:27'),
(46, 24, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #71)', 'custom_design_feedback', 71, 'custom_template', 0, '2025-06-01 06:53:27'),
(51, 37, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #71)', 'custom_design_feedback', 71, 'custom_template', 0, '2025-06-01 06:53:27'),
(52, 20, 'New Order Received', 'A new order #44 has been placed', 'order', 44, 'order', 1, '2025-06-01 06:55:35'),
(53, 21, 'New Order Received', 'A new order #44 has been placed', 'order', 44, 'order', 0, '2025-06-01 06:55:35'),
(54, 25, 'Order Status Updated', 'Your order #44 status has been updated to ready', 'order', 44, 'order', 1, '2025-06-01 06:56:01'),
(55, 25, 'Order Status Updated', 'Your order #44 status has been updated to completed', 'order', 44, 'order', 1, '2025-06-01 06:58:48'),
(56, 23, 'Design Revision Requested', 'Customer Swagat Shrestha has requested revisions for Business Card (Request #71). Feedback: pleaseee', 'custom_design_feedback', 71, 'custom_template', 1, '2025-06-01 07:01:22'),
(57, 24, 'Design Revision Requested', 'Customer Swagat Shrestha has requested revisions for Business Card (Request #71). Feedback: pleaseee', 'custom_design_feedback', 71, 'custom_template', 0, '2025-06-01 07:01:22'),
(62, 37, 'Design Revision Requested', 'Customer Swagat Shrestha has requested revisions for Business Card (Request #71). Feedback: pleaseee', 'custom_design_feedback', 71, 'custom_template', 0, '2025-06-01 07:01:22'),
(63, 25, 'Custom Request Completed', 'Your custom request for Business Card has been completed. The final design is ready for review.', 'custom_request', 71, 'custom_template', 1, '2025-06-01 07:02:53'),
(64, 23, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #71)', 'custom_design_feedback', 71, 'custom_template', 1, '2025-06-01 07:03:14'),
(65, 24, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #71)', 'custom_design_feedback', 71, 'custom_template', 0, '2025-06-01 07:03:14'),
(70, 37, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #71)', 'custom_design_feedback', 71, 'custom_template', 0, '2025-06-01 07:03:14'),
(71, 20, 'New Custom Template Request', 'A new custom template request has been submitted for Poster', 'custom_request', 72, 'custom_template', 0, '2025-06-01 07:09:05'),
(72, 21, 'New Custom Template Request', 'A new custom template request has been submitted for Poster', 'custom_request', 72, 'custom_template', 0, '2025-06-01 07:09:05'),
(74, 20, 'New Custom Template Request', 'A new custom template request has been submitted for Poster', 'custom_request', 73, 'custom_template', 0, '2025-06-01 07:09:58'),
(75, 21, 'New Custom Template Request', 'A new custom template request has been submitted for Poster', 'custom_request', 73, 'custom_template', 0, '2025-06-01 07:09:58'),
(77, 20, 'New Custom Template Request', 'A new custom template request has been submitted for Business Card', 'custom_request', 74, 'custom_template', 1, '2025-06-01 07:12:53'),
(78, 21, 'New Custom Template Request', 'A new custom template request has been submitted for Business Card', 'custom_request', 74, 'custom_template', 0, '2025-06-01 07:12:53'),
(80, 23, 'New Custom Request Assignment', 'You have been assigned a new custom request for Business Card', 'custom_request', 74, 'custom_template', 1, '2025-06-01 07:42:04'),
(81, 24, 'New Custom Request Assignment', 'You have been assigned a new custom request for Poster', 'custom_request', 73, 'custom_template', 0, '2025-06-01 07:42:17'),
(82, 25, 'Custom Request Completed', 'Your custom request for Business Card has been completed. The final design is ready for review.', 'custom_request', 74, 'custom_template', 1, '2025-06-01 08:43:57'),
(83, 23, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #74)', 'custom_design_feedback', 74, 'custom_template', 1, '2025-06-01 08:45:50'),
(84, 24, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #74)', 'custom_design_feedback', 74, 'custom_template', 1, '2025-06-01 08:45:50'),
(89, 37, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Business Card (Request #74)', 'custom_design_feedback', 74, 'custom_template', 0, '2025-06-01 08:45:50'),
(90, 20, 'New Order Received', 'A new order #45 has been placed', 'order', 45, 'order', 0, '2025-06-01 08:46:24'),
(91, 21, 'New Order Received', 'A new order #45 has been placed', 'order', 45, 'order', 0, '2025-06-01 08:46:24'),
(92, 20, 'New Order Received', 'A new order #46 has been placed', 'order', 46, 'order', 1, '2025-06-01 08:47:03'),
(93, 21, 'New Order Received', 'A new order #46 has been placed', 'order', 46, 'order', 0, '2025-06-01 08:47:03'),
(94, 25, 'Order Status Updated', 'Your order #46 status has been updated to ready', 'order', 46, 'order', 1, '2025-06-01 08:48:47'),
(95, 25, 'Order Status Updated', 'Your order #46 status has been updated to ready', 'order', 46, 'order', 1, '2025-06-01 08:49:10'),
(96, 25, 'Order Status Updated', 'Your order #46 status has been updated to completed', 'order', 46, 'order', 1, '2025-06-01 08:51:47'),
(97, 25, 'Custom Request Completed', 'Your custom request for Poster has been completed. The final design is ready for review.', 'custom_request', 73, 'custom_template', 1, '2025-06-01 09:06:14'),
(98, 23, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Poster (Request #73)', 'custom_design_feedback', 73, 'custom_template', 1, '2025-06-01 09:06:35'),
(99, 24, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Poster (Request #73)', 'custom_design_feedback', 73, 'custom_template', 0, '2025-06-01 09:06:35'),
(104, 37, 'Design Approved ✓', 'Customer Swagat Shrestha has approved the design for Poster (Request #73)', 'custom_design_feedback', 73, 'custom_template', 0, '2025-06-01 09:06:35'),
(105, 20, 'New Order Received', 'A new order #47 has been placed', 'order', 47, 'order', 0, '2025-06-01 14:58:19'),
(106, 21, 'New Order Received', 'A new order #47 has been placed', 'order', 47, 'order', 0, '2025-06-01 14:58:19'),
(107, 20, 'New Order Received', 'A new order #48 has been placed', 'order', 48, 'order', 1, '2025-06-01 15:04:14'),
(108, 21, 'New Order Received', 'A new order #48 has been placed', 'order', 48, 'order', 0, '2025-06-01 15:04:14'),
(109, 20, 'New Order Received', 'A new order #49 has been placed', 'order', 49, 'order', 1, '2025-06-01 15:10:16'),
(110, 21, 'New Order Received', 'A new order #49 has been placed', 'order', 49, 'order', 0, '2025-06-01 15:10:16'),
(111, 20, 'New Order Received', 'A new order #50 has been placed', 'order', 50, 'order', 1, '2025-06-01 15:21:51'),
(112, 21, 'New Order Received', 'A new order #50 has been placed', 'order', 50, 'order', 0, '2025-06-01 15:21:51'),
(113, 23, 'New Template Modification Request', 'A new modification request has been submitted for template \'Indian Marriage\'', 'template_status', 46, 'template_finishing', 1, '2025-06-01 16:20:42'),
(114, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to Completed', 'template_status', 46, 'template_finishing', 0, '2025-06-01 16:21:20'),
(115, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to In Progress', 'template_status', 46, 'template_finishing', 0, '2025-06-01 16:48:35'),
(116, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to Completed', 'template_status', 46, 'template_finishing', 0, '2025-06-01 17:25:52'),
(117, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to In Progress', 'template_status', 46, 'template_finishing', 0, '2025-06-01 17:41:08'),
(118, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to Completed', 'template_status', 46, 'template_finishing', 1, '2025-06-01 17:41:12'),
(119, 23, 'Template Satisfaction Updated', 'Customer marked template \'Indian Marriage\' as Not Satisfied', 'template_status', 46, 'template_finishing', 1, '2025-06-01 18:21:13'),
(120, 25, 'Final Design Uploaded', 'The final design for your template modification request \'Indian Marriage\' has been uploaded', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:44:09'),
(121, 25, 'Final Design Uploaded', 'The final design for your template modification request \'Indian Marriage\' has been uploaded', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:44:24'),
(122, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to In Progress', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:49:24'),
(123, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to Completed', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:49:27'),
(124, 23, 'Design Approved ✓', 'Customer has approved the design for template \'Indian Marriage\'', 'template_status', 46, 'template_finishing', 1, '2025-06-02 01:50:45'),
(125, 23, 'Design Revision Requested', 'Customer has requested revisions for template \'Indian Marriage\'. The status has been updated to In Progress.', 'template_status', 46, 'template_finishing', 1, '2025-06-02 01:50:49'),
(126, 23, 'Design Revision Requested', 'Customer has requested revisions for template \'Indian Marriage\'. The status has been updated to In Progress.', 'template_status', 46, 'template_finishing', 1, '2025-06-02 01:50:53'),
(127, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to Completed', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:53:27'),
(128, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to In Progress', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:55:14'),
(129, 25, 'Template Status Updated', 'Your template modification request for \'Indian Marriage\' has been updated to Completed', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:55:15'),
(130, 25, 'Final Design Uploaded', 'The final design for your template modification request \'Indian Marriage\' has been uploaded', 'template_status', 46, 'template_finishing', 0, '2025-06-02 01:55:23'),
(131, 23, 'Design Approved ✓', 'Customer has approved the design for template \'Indian Marriage\'', 'template_status', 46, 'template_finishing', 1, '2025-06-02 01:55:59'),
(132, 37, 'New Template Modification Request', 'A new modification request has been submitted for template \'Information Card\'', 'template_status', 47, 'template_finishing', 0, '2025-06-02 02:02:04'),
(133, 25, 'Order Status Updated', 'Your order #47 status has been updated to ready', 'order', 47, 'order', 0, '2025-06-02 02:52:13'),
(134, 25, 'Order Status Updated', 'Your order #47 status has been updated to ready', 'order', 47, 'order', 0, '2025-06-02 02:54:27'),
(135, 25, 'Order Status Updated', 'Your order #48 status has been updated to ready', 'order', 48, 'order', 0, '2025-06-02 03:24:11'),
(136, 25, 'Order Status Updated', 'Your order #48 status has been updated to pending', 'order', 48, 'order', 0, '2025-06-02 03:24:13'),
(137, 20, 'New Order Received', 'A new order #51 has been placed', 'order', 51, 'order', 1, '2025-06-02 03:41:15'),
(138, 21, 'New Order Received', 'A new order #51 has been placed', 'order', 51, 'order', 0, '2025-06-02 03:41:15'),
(139, 25, 'Order Status Updated', 'Your order #51 status has been updated to ready', 'order', 51, 'order', 0, '2025-06-02 03:42:06'),
(140, 25, 'Order Status Updated', 'Your order #51 status has been updated to ready', 'order', 51, 'order', 1, '2025-06-02 04:48:36'),
(141, 25, 'Order Placed Successfully', 'Your order #55 has been placed successfully. You can track your order status in Your Orders.', 'order_placed', 55, 'order', 1, '2025-06-02 13:41:17'),
(142, 20, 'New Order Received', 'A new order #55 has been placed and is waiting for processing.', 'new_order', 55, 'order', 1, '2025-06-02 13:41:17'),
(143, 25, 'Order Status Updated', 'Your order #54 has been updated to Ready', 'order_status', 54, 'order', 1, '2025-06-02 13:46:51'),
(144, 25, 'Order Status Updated', 'Your order #55 has been updated to Completed', 'order_status', 55, 'order', 0, '2025-06-02 13:54:16'),
(145, 25, 'Order Status Updated', 'Your order #54 has been updated to Completed', 'order_status', 54, 'order', 1, '2025-06-02 13:55:15'),
(146, 20, 'New Custom Template Request', 'A new custom template request has been submitted for ID Card', 'custom_request', 75, 'custom_template', 0, '2025-06-02 16:55:07'),
(147, 21, 'New Custom Template Request', 'A new custom template request has been submitted for ID Card', 'custom_request', 75, 'custom_template', 0, '2025-06-02 16:55:07'),
(149, 38, 'New Custom Request Assignment', 'You have been assigned a new custom request for ID Card', 'custom_request', 75, 'custom_template', 1, '2025-06-02 17:03:22'),
(150, 27, 'Custom Request Status Update', 'Your custom request for ID Card has been updated to: In Progress', 'custom_request', 75, 'custom_template', 0, '2025-06-02 17:05:00'),
(151, 27, 'Custom Request Status Update', 'Your custom request for ID Card has been updated to: In Progress', 'custom_request', 75, 'custom_template', 0, '2025-06-02 17:08:14'),
(152, 27, 'Custom Request Completed', 'Your custom request for ID Card has been completed. The final design is ready for review.', 'custom_request', 75, 'custom_template', 0, '2025-06-02 17:09:05'),
(153, 23, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: Make it attracrtive', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:10:11'),
(154, 24, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: Make it attracrtive', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:10:11'),
(155, 37, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: Make it attracrtive', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:10:11'),
(156, 38, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: Make it attracrtive', 'custom_design_feedback', 75, 'custom_template', 1, '2025-06-02 17:10:11'),
(157, 27, 'Custom Request Completed', 'Your custom request for ID Card has been completed. The final design is ready for review.', 'custom_request', 75, 'custom_template', 1, '2025-06-02 17:11:24'),
(158, 23, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: yes a bit more', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:12:11'),
(159, 24, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: yes a bit more', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:12:11'),
(160, 37, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: yes a bit more', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:12:11'),
(161, 38, 'Design Revision Requested', 'Customer Keshav Kumar Singh has requested revisions for ID Card (Request #75). Feedback: yes a bit more', 'custom_design_feedback', 75, 'custom_template', 1, '2025-06-02 17:12:11'),
(162, 27, 'Custom Request Completed', 'Your custom request for ID Card has been completed. The final design is ready for review.', 'custom_request', 75, 'custom_template', 1, '2025-06-02 17:12:45'),
(163, 23, 'Design Approved ✓', 'Customer Keshav Kumar Singh has approved the design for ID Card (Request #75)', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:13:03'),
(164, 24, 'Design Approved ✓', 'Customer Keshav Kumar Singh has approved the design for ID Card (Request #75)', 'custom_design_feedback', 75, 'custom_template', 1, '2025-06-02 17:13:03'),
(165, 37, 'Design Approved ✓', 'Customer Keshav Kumar Singh has approved the design for ID Card (Request #75)', 'custom_design_feedback', 75, 'custom_template', 0, '2025-06-02 17:13:03'),
(166, 38, 'Design Approved ✓', 'Customer Keshav Kumar Singh has approved the design for ID Card (Request #75)', 'custom_design_feedback', 75, 'custom_template', 1, '2025-06-02 17:13:03'),
(167, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Completed', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:39:28'),
(168, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Pending', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:39:35'),
(169, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to In Progress', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:39:38'),
(170, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Completed', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:39:40'),
(171, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to In Progress', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:41:38'),
(172, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Completed', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:41:40'),
(173, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to In Progress', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:41:54'),
(174, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Completed', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:41:56'),
(175, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to In Progress', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:42:03'),
(176, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Completed', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:42:05'),
(177, 27, 'Final Design Uploaded', 'The final design for your template modification request \'Professional Business Card\' has been uploaded', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:51:06'),
(178, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to In Progress', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:58:14'),
(179, 27, 'Template Status Updated', 'Your template modification request for \'Professional Business Card\' has been updated to Completed', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:58:21'),
(180, 27, 'Final Design Uploaded', 'The final design for your template modification request \'Professional Business Card\' has been uploaded', 'template_status', 49, 'template_finishing', 0, '2025-06-02 18:58:35'),
(181, 24, 'Design Approved ✓', 'Customer has approved the design for template \'Professional Business Card\'', 'template_status', 49, 'template_finishing', 0, '2025-06-02 19:05:49'),
(182, 20, 'New Custom Template Request', 'A new custom template request has been submitted for Business Card', 'custom_request', 76, 'custom_template', 0, '2025-06-02 19:09:22'),
(183, 21, 'New Custom Template Request', 'A new custom template request has been submitted for Business Card', 'custom_request', 76, 'custom_template', 0, '2025-06-02 19:09:22'),
(185, 23, 'New Template Modification Request', 'You have received a new template modification request for template \'Professional Card \'', 'template_modification', 54, 'template_modification', 0, '2025-06-02 19:13:08'),
(186, 23, 'New Template Modification Request', 'You have received a new template modification request for template \'Professional Card \'', 'template_modification', 55, 'template_modification', 0, '2025-06-02 19:14:06'),
(187, 23, 'New Template Modification Request', 'You have received a new template modification request for template \'Professional Card \'', 'template_modification', 56, 'template_modification', 0, '2025-06-02 19:15:58'),
(188, 23, 'New Template Modification Request', 'You have received a new template modification request for template \'Professional Card \'', 'template_modification', 57, 'template_modification', 0, '2025-06-02 19:18:01'),
(189, 25, 'Template Status Updated', 'Your template modification request for \'Professional Card \' has been updated to In Progress', 'template_status', 57, 'template_finishing', 0, '2025-06-02 19:24:08'),
(190, 25, 'Template Status Updated', 'Your template modification request for \'Professional Card \' has been updated to Completed', 'template_status', 57, 'template_finishing', 0, '2025-06-02 19:24:11'),
(191, 25, 'Final Design Uploaded', 'The final design for your template modification request \'Professional Card \' has been uploaded', 'template_status', 57, 'template_finishing', 1, '2025-06-02 19:24:29'),
(192, 23, 'Design Approved ✓', 'Customer has approved the design for template \'Professional Card \'', 'template_status', 57, 'template_finishing', 0, '2025-06-02 19:24:58'),
(193, 25, 'Order Placed Successfully', 'Your order #59 has been placed successfully. You can track your order status in Your Orders.', 'order_placed', 59, 'order', 0, '2025-06-02 19:28:04'),
(194, 20, 'New Order Received', 'A new order #59 has been placed and is waiting for processing.', 'new_order', 59, 'order', 0, '2025-06-02 19:28:04'),
(195, 20, 'New Custom Template Request', 'A new custom template request has been submitted for Poster', 'custom_request', 77, 'custom_template', 1, '2025-06-02 19:30:11'),
(196, 21, 'New Custom Template Request', 'A new custom template request has been submitted for Poster', 'custom_request', 77, 'custom_template', 0, '2025-06-02 19:30:11'),
(198, 38, 'New Custom Request Assignment', 'You have been assigned a new custom request for Poster', 'custom_request', 77, 'custom_template', 1, '2025-06-02 19:31:07'),
(199, 27, 'Custom Request Status Update', 'Your custom request for Poster has been updated to: In Progress', 'custom_request', 77, 'custom_template', 0, '2025-06-02 19:31:37'),
(200, 27, 'Custom Request Completed', 'Your custom request for Poster has been completed. The final design is ready for review.', 'custom_request', 77, 'custom_template', 0, '2025-06-02 19:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`id`, `uid`, `created_at`, `order_date`) VALUES
(45, 25, '2025-06-01 08:46:24', '2025-06-01 08:46:24'),
(46, 25, '2025-06-01 08:47:03', '2025-06-01 08:47:03'),
(47, 25, '2025-06-01 14:58:19', '2025-06-01 14:58:19'),
(48, 25, '2025-06-01 15:04:14', '2025-06-01 15:04:14'),
(49, 25, '2025-06-01 15:10:16', '2025-06-01 15:10:16'),
(50, 25, '2025-06-01 15:21:51', '2025-06-01 15:21:51'),
(51, 25, '2025-06-02 03:41:15', '2025-06-02 03:41:15'),
(52, 25, '2025-06-02 13:28:08', '2025-06-02 13:28:08'),
(53, 25, '2025-06-02 13:35:53', '2025-06-02 13:35:53'),
(54, 25, '2025-06-02 13:39:50', '2025-06-02 13:39:50'),
(55, 25, '2025-06-02 13:41:17', '2025-06-02 13:41:17'),
(56, 25, '2025-06-02 19:25:11', '2025-06-02 19:25:11'),
(57, 25, '2025-06-02 19:25:37', '2025-06-02 19:25:37'),
(58, 25, '2025-06-02 19:26:32', '2025-06-02 19:26:32'),
(59, 25, '2025-06-02 19:28:04', '2025-06-02 19:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `order_handling`
--

CREATE TABLE `order_handling` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `changed_status` varchar(32) NOT NULL,
  `handled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_handling`
--

INSERT INTO `order_handling` (`id`, `admin_id`, `order_id`, `changed_status`, `handled_at`) VALUES
(41, 3, 46, 'ready', '2025-06-01 08:48:47'),
(42, 3, 46, 'ready', '2025-06-01 08:49:10'),
(43, 3, 46, 'completed', '2025-06-01 08:51:47'),
(44, 3, 47, 'ready', '2025-06-02 02:52:13'),
(45, 3, 47, 'ready', '2025-06-02 02:54:27'),
(46, 3, 48, 'ready', '2025-06-02 03:24:11'),
(47, 3, 48, 'pending', '2025-06-02 03:24:13'),
(48, 3, 51, 'ready', '2025-06-02 03:42:06'),
(49, 3, 51, 'ready', '2025-06-02 04:48:36'),
(50, 3, 51, 'ready', '2025-06-02 08:09:38'),
(51, 3, 51, 'ready', '2025-06-02 08:17:52'),
(52, 3, 51, 'ready', '2025-06-02 08:20:46'),
(53, 3, 51, 'ready', '2025-06-02 08:25:00'),
(54, 3, 51, 'ready', '2025-06-02 08:29:13'),
(55, 3, 51, 'completed', '2025-06-02 08:29:59'),
(56, 3, 48, 'ready', '2025-06-02 08:33:52'),
(57, 3, 48, 'completed', '2025-06-02 08:34:05'),
(58, 3, 50, 'ready', '2025-06-02 08:35:55'),
(59, 3, 49, 'ready', '2025-06-02 08:37:56'),
(60, 3, 55, 'ready', '2025-06-02 13:44:30'),
(61, 3, 54, 'ready', '2025-06-02 13:46:51'),
(62, 3, 55, 'completed', '2025-06-02 13:54:16'),
(63, 3, 54, 'completed', '2025-06-02 13:55:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_item_line`
--

CREATE TABLE `order_item_line` (
  `id` int(11) NOT NULL,
  `oid` int(11) NOT NULL,
  `ca_it_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `template_image` varchar(255) DEFAULT NULL,
  `custom_notes` text DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `feedback` int(11) DEFAULT NULL CHECK (`feedback` >= 1 and `feedback` <= 5),
  `status` enum('pending','ready','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item_line`
--

INSERT INTO `order_item_line` (`id`, `oid`, `ca_it_id`, `quantity`, `unit_price`, `template_name`, `template_image`, `custom_notes`, `total_price`, `feedback`, `status`, `created_at`, `updated_at`) VALUES
(55, 45, 116, 1, 34.00, 'Custom Design', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', 'yess pleasee', 34.00, NULL, 'pending', '2025-06-01 08:46:24', '2025-06-01 08:46:24'),
(56, 46, 117, 1, 34.00, 'Custom Design', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', 'yess pleasee', 34.00, NULL, 'completed', '2025-06-01 08:47:03', '2025-06-01 08:51:47'),
(57, 47, 119, 1, 34.00, 'Custom Design', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', 'yess pleasee', 34.00, NULL, 'ready', '2025-06-01 14:58:19', '2025-06-02 02:52:13'),
(58, 47, 120, 1, 44.00, 'Custom Design', 'final/683c18068d2f9_77a0b99def2483fe7a88ff63c33179e4.jpg', 'pleaseee', 44.00, NULL, 'ready', '2025-06-01 14:58:19', '2025-06-02 02:52:13'),
(59, 48, 121, 1, 34.00, 'Custom Design', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', 'yess pleasee', 34.00, NULL, 'completed', '2025-06-01 15:04:14', '2025-06-02 08:34:05'),
(60, 49, 122, 1, 34.00, 'Custom Design', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', 'yess pleasee', 34.00, NULL, 'ready', '2025-06-01 15:10:16', '2025-06-02 08:37:56'),
(61, 50, 123, 1, 44.00, 'Custom Design', 'final/683c18068d2f9_77a0b99def2483fe7a88ff63c33179e4.jpg', 'pleaseee', 44.00, NULL, 'ready', '2025-06-01 15:21:51', '2025-06-02 08:35:55'),
(62, 51, 124, 33, 25.00, 'Indian Marriage', '1748759075_sl_021822_48620_14.jpg', '', 825.00, NULL, 'completed', '2025-06-02 03:41:15', '2025-06-02 08:29:59'),
(63, 52, 125, 33, 25.00, 'Indian Marriage', '1748759075_sl_021822_48620_14.jpg', '', 825.00, NULL, 'pending', '2025-06-02 13:28:08', '2025-06-02 13:28:08'),
(64, 53, 128, 1, 34.00, 'Custom Design', 'final/683c12cd84181_Screenshot 2025-06-01 115320.png', 'yess pleasee', 34.00, NULL, 'pending', '2025-06-02 13:35:53', '2025-06-02 13:35:53'),
(65, 54, 127, 33, 25.00, 'Indian Marriage', '1748759075_sl_021822_48620_14.jpg', '', 825.00, NULL, 'completed', '2025-06-02 13:39:50', '2025-06-02 13:55:14'),
(66, 55, 127, 33, 25.00, 'Indian Marriage', '1748759075_sl_021822_48620_14.jpg', '', 825.00, NULL, 'completed', '2025-06-02 13:41:17', '2025-06-02 13:54:16'),
(67, 56, 131, 11, 3.00, 'Professional Card ', '1748759711_Screenshot 2025-06-01 115357.png', '', 33.00, NULL, 'pending', '2025-06-02 19:25:11', '2025-06-02 19:25:11'),
(68, 57, 131, 11, 3.00, 'Professional Card ', '1748759711_Screenshot 2025-06-01 115357.png', '', 33.00, NULL, 'pending', '2025-06-02 19:25:37', '2025-06-02 19:25:37'),
(69, 58, 131, 11, 3.00, 'Professional Card ', '1748759711_Screenshot 2025-06-01 115357.png', '', 33.00, NULL, 'pending', '2025-06-02 19:26:32', '2025-06-02 19:26:32'),
(70, 59, 131, 11, 3.00, 'Professional Card ', '1748759711_Screenshot 2025-06-01 115357.png', '', 33.00, NULL, 'pending', '2025-06-02 19:28:04', '2025-06-02 19:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'eSewa',
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_item_id`, `amount`, `payment_method`, `transaction_id`, `status`, `payment_date`, `created_at`) VALUES
(9, 56, 34.00, 'eSewa', '000ATMC', 'completed', '2025-06-01 08:51:09', '2025-06-01 08:51:09'),
(27, 62, 825.00, 'cash', 'R0001', 'completed', '2025-06-02 08:29:59', '2025-06-02 08:29:59'),
(33, 59, 35.00, 'cash', 'R0002', 'completed', '2025-06-02 08:34:05', '2025-06-02 08:34:05'),
(34, 66, 825.00, 'esewa', '000AU5P', 'completed', '2025-06-02 13:47:58', '2025-06-02 13:47:58'),
(35, 65, 825.00, 'cash', 'R0004', 'completed', '2025-06-02 13:55:14', '2025-06-02 13:55:14');

-- --------------------------------------------------------

--
-- Table structure for table `payment_tracking`
--

CREATE TABLE `payment_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `transaction_uuid` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_tracking`
--

INSERT INTO `payment_tracking` (`id`, `order_id`, `transaction_uuid`, `amount`, `status`, `created_at`) VALUES
(93, 44, 'a3281d19-e2ca-4dc0-95aa-6a4fa7a1671d', 67.00, 'pending', '2025-06-01 06:57:15'),
(94, 44, '07e335d1-7a5d-4695-81e1-ddbae8592395', 67.00, 'pending', '2025-06-01 06:57:15'),
(95, 44, '7be2a59c-3676-453b-9730-2e5733364a58', 67.00, 'pending', '2025-06-01 06:57:15'),
(96, 44, '0ef5ab58-71d6-4080-b2d4-333db021cb3f', 67.00, 'pending', '2025-06-01 06:57:15'),
(97, 44, 'b2a5d167-d078-46b3-a576-6c26507f93f7', 67.00, 'pending', '2025-06-01 06:57:15'),
(98, 46, '00538eb4-2a13-49ea-8d99-b00b91a84f55', 34.00, 'pending', '2025-06-01 08:49:25'),
(99, 46, '26c4c3b5-4961-445b-b644-a95c5995164d', 34.00, 'pending', '2025-06-01 08:50:23'),
(100, 46, '02699366-305d-4a5b-b89e-df23d0dff812', 34.00, 'pending', '2025-06-01 08:50:25'),
(101, 46, '7afedb9c-77ad-4815-92ab-3cd46975a131', 34.00, 'pending', '2025-06-01 08:50:25'),
(102, 46, '18435e41-b0eb-4ad1-b4f9-d79f826db547', 34.00, 'pending', '2025-06-01 08:50:25'),
(103, 46, '7231debc-4bb3-402e-9a84-4bbdac854137', 34.00, 'pending', '2025-06-01 08:50:25'),
(104, 46, '21b3dbd4-4e31-4d24-8c7c-20b816cc902b', 34.00, 'pending', '2025-06-01 08:50:25'),
(105, 46, '4cb563c1-0b3b-4255-be20-33e08ab61d91', 34.00, 'pending', '2025-06-01 08:50:26'),
(106, 46, '509aac5d-0b5e-4547-a48f-8d5c04f223bb', 34.00, 'pending', '2025-06-01 08:50:26'),
(107, 46, '7d4aac86-83a1-491b-bb25-d7d5ffc26618', 34.00, 'pending', '2025-06-01 08:50:26'),
(108, 50, 'ce4e78f7-4559-4391-90c9-003a24112ba1', 44.00, 'pending', '2025-06-02 08:55:00'),
(109, 49, 'af07d45f-9b31-48a7-ad36-12620592de3c', 34.00, 'pending', '2025-06-02 08:55:00'),
(110, 50, 'b5a07cb1-8078-4934-be8f-6e287d249efd', 44.00, 'pending', '2025-06-02 08:56:37'),
(111, 49, 'c2cd68c5-43dd-4ea1-8b46-87b92d83c1a5', 34.00, 'pending', '2025-06-02 08:56:37'),
(112, 50, '6ad2f019-72f8-4144-abbe-a4f0f166a352', 44.00, 'pending', '2025-06-02 09:17:53'),
(113, 49, '5a93666d-4636-4b4f-9b9e-362a6920973d', 34.00, 'pending', '2025-06-02 09:17:53'),
(114, 55, '7b63c0c9-4292-4262-9151-fe7890bdeeb9', 825.00, 'pending', '2025-06-02 13:44:51'),
(115, 55, '1965621b-4a1f-4b10-a8c9-512a02ac249a', 825.00, 'pending', '2025-06-02 13:47:04'),
(116, 54, '20398ebb-502a-4950-8ed1-fdd1bb606dd5', 825.00, 'pending', '2025-06-02 13:47:04'),
(117, 54, '2da26796-daad-4949-912f-382b36d9ba19', 825.00, 'pending', '2025-06-02 13:47:58');

-- --------------------------------------------------------

--
-- Table structure for table `preferences`
--

CREATE TABLE `preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preferred_category` varchar(255) DEFAULT NULL,
  `preferred_color_scheme` varchar(100) DEFAULT NULL,
  `preferred_media_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `preferences`
--

INSERT INTO `preferences` (`id`, `user_id`, `preferred_category`, `preferred_color_scheme`, `preferred_media_type`, `created_at`, `updated_at`) VALUES
(1, 25, 'Business Card', 'Custom', 'Paper', '2025-05-13 16:36:53', '2025-05-13 16:36:53'),
(3, 27, 'Poster', 'Black and White', 'Paper', '2025-05-13 16:44:34', '2025-05-13 16:44:34');

-- --------------------------------------------------------

--
-- Table structure for table `receipt_numbers`
--

CREATE TABLE `receipt_numbers` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_numbers`
--

INSERT INTO `receipt_numbers` (`id`, `order_id`, `receipt_number`, `created_at`) VALUES
(12, 51, 'R0001', '2025-06-02 08:17:52'),
(13, 51, 'R0001', '2025-06-02 08:20:46'),
(14, 51, 'R0001', '2025-06-02 08:25:00'),
(15, 51, 'R0001', '2025-06-02 08:29:13'),
(16, 51, 'R0001', '2025-06-02 08:29:59'),
(22, 48, 'R0002', '2025-06-02 08:34:05'),
(23, 54, 'R0004', '2025-06-02 13:55:14');

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

CREATE TABLE `sizes` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `size_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`id`, `category_id`, `size_name`, `created_at`, `user_id`) VALUES
(1, 29, '3 inches x 2 inches', '2025-05-02 20:35:48', NULL),
(2, 29, '2 inches x 2 inches', '2025-05-02 20:37:41', NULL),
(3, 28, '5.8 inches x 8.2 inches', '2025-05-02 20:38:40', NULL),
(4, 30, '6 ft x 4 ft', '2025-05-02 20:41:02', NULL),
(9, 29, '6 inch x 2 inches', '2025-05-08 13:30:30', 20),
(11, 39, '2 inch x 2 inch', '2025-05-12 16:49:07', NULL),
(12, 40, '2 inches x 3 inches', '2025-06-02 16:48:43', NULL),
(13, 40, '3 inches x 2 inches', '2025-06-02 16:48:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `availability` enum('active','inactive') NOT NULL DEFAULT 'active',
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `availability`, `admin_id`) VALUES
(7, 23, 'active', NULL),
(8, 24, 'active', NULL),
(12, 37, 'active', 3),
(13, 38, 'active', 3);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `subscription_type` enum('free','premium') NOT NULL DEFAULT 'free',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `subscription_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_limits`
--

CREATE TABLE `subscription_limits` (
  `limit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `custom_design_count` int(11) NOT NULL DEFAULT 0,
  `template_modification_count` int(11) NOT NULL DEFAULT 0,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `monthly_limit` int(11) NOT NULL DEFAULT 10,
  `last_reset_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payments`
--

CREATE TABLE `subscription_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'esewa',
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin`
--

CREATE TABLE `super_admin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admin`
--

INSERT INTO `super_admin` (`id`, `user_id`, `created_at`) VALUES
(2, 19, '2025-05-01 10:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `c_id` int(11) NOT NULL,
  `media_type_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `color_scheme` varchar(50) NOT NULL DEFAULT 'Custom',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`id`, `name`, `cost`, `c_id`, `media_type_id`, `staff_id`, `color_scheme`, `created_at`, `image_path`, `status`) VALUES
(17, 'Indian Marriage', 25.00, 28, 8, 7, 'Custom', '2025-06-01 06:24:35', '1748759075_sl_021822_48620_14.jpg', 'active'),
(18, 'Western Wedding', 30.00, 28, 8, 8, 'Custom', '2025-06-01 06:25:42', '1748759142_20792200.jpg', 'active'),
(19, 'Marriage', 27.00, 28, 8, 12, 'Custom', '2025-06-01 06:26:11', '1748759171_5875217.jpg', 'active'),
(20, 'College Flex', 44.00, 30, 9, 7, 'Custom', '2025-06-01 06:27:16', '1748759236_Screenshot 2025-06-01 115912.png', 'active'),
(21, 'School Flex', 40.00, 30, 9, 8, 'Custom', '2025-06-01 06:27:55', '1748759275_Screenshot 2025-06-01 115828.png', 'active'),
(22, 'Kinder Garden', 35.00, 30, 9, 12, 'Custom', '2025-06-01 06:28:23', '1748759303_Screenshot 2025-06-01 115851.png', 'active'),
(23, 'PUBG Event', 30.00, 39, 12, 7, 'Black and White', '2025-06-01 06:29:40', '1748759380_Screenshot 2025-02-25 232201.png', 'active'),
(24, 'Motivational Poster', 30.00, 39, 10, 8, 'Grayscale', '2025-06-01 06:30:58', '1748759458_2e50c028b3cf0827caa254294517e753.jpg', 'active'),
(25, 'College Poster', 25.00, 39, 8, 12, 'Custom', '2025-06-01 06:32:40', '1748759560_9e3b8887836ecf662329575bfee233ed.jpg', 'active'),
(26, 'Admission Open', 34.00, 39, 10, 12, 'Grayscale', '2025-06-01 06:33:28', '1748759608_77a0b99def2483fe7a88ff63c33179e4.jpg', 'active'),
(27, 'Professional Card ', 3.00, 29, 8, 7, 'Black and White', '2025-06-01 06:35:11', '1748759711_Screenshot 2025-06-01 115357.png', 'active'),
(28, 'Professional Business Card', 4.00, 29, 8, 8, 'Custom', '2025-06-01 06:36:20', '1748759780_Screenshot 2025-06-01 115320.png', 'active'),
(29, 'Information Card', 4.00, 29, 8, 12, 'Custom', '2025-06-01 06:37:24', '1748759844_Screenshot 2025-06-01 115254.png', 'active'),
(30, 'College ID', 45.00, 40, 10, 13, 'Custom', '2025-06-02 16:52:57', '1748883177_1600w-7uvsBu_IpiQ.webp', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `template_modifications`
--

CREATE TABLE `template_modifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `media_type_id` int(11) NOT NULL,
  `size` varchar(50) NOT NULL,
  `orientation` varchar(50) NOT NULL,
  `color_scheme` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `additional_notes` text DEFAULT NULL,
  `reference_image` varchar(255) DEFAULT NULL,
  `final_design` varchar(255) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Rejected') NOT NULL DEFAULT 'Pending',
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `satisfaction_status` enum('Not Rated','Satisfied','Not Satisfied') NOT NULL DEFAULT 'Not Rated',
  `preferred_color` varchar(7) DEFAULT '#000000',
  `secondary_color` varchar(7) DEFAULT '#ffffff',
  `status_updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_modifications`
--

INSERT INTO `template_modifications` (`id`, `user_id`, `staff_id`, `template_id`, `category_id`, `media_type_id`, `size`, `orientation`, `color_scheme`, `quantity`, `additional_notes`, `reference_image`, `final_design`, `status`, `feedback`, `created_at`, `satisfaction_status`, `preferred_color`, `secondary_color`, `status_updated_at`) VALUES
(48, 27, 12, 29, 29, 8, '2 inches x 2 inches', 'Landscape', 'Black and White', 1, '', '', NULL, 'Pending', NULL, '2025-06-02 17:13:36', 'Not Rated', '#000000', '#ffffff', '2025-06-02 22:58:36'),
(49, 27, 8, 28, 29, 8, '2 inches x 2 inches', 'Landscape', 'Black and White', 1, '', '', '1748890715_683df45b954fb.png', 'Completed', NULL, '2025-06-02 17:14:27', 'Satisfied', '#000000', '#ffffff', '2025-06-03 00:43:35'),
(54, 27, 7, 27, 29, 8, '2 inches x 2 inches', 'Portrait', 'Black and White', 2, '', '', NULL, 'Pending', NULL, '2025-06-02 19:13:08', 'Not Rated', '#000000', '#ffffff', '2025-06-03 00:58:08'),
(56, 25, 7, 27, 29, 8, '2 inches x 2 inches', 'Landscape', 'Black and White', 1, '', '', NULL, 'Pending', NULL, '2025-06-02 19:15:58', 'Not Rated', '#000000', '#ffffff', '2025-06-03 01:00:58'),
(57, 25, 7, 27, 29, 8, '2 inches x 2 inches', 'Landscape', 'Black and White', 11, '', '', '1748892269_683dfa6dd558f.png', 'Completed', NULL, '2025-06-02 19:18:01', 'Satisfied', '#000000', '#ffffff', '2025-06-03 01:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(500) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('Admin','Staff','Customer','Super Admin') NOT NULL,
  `staff_role` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `DOB` date DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `phone`, `address`, `role`, `staff_role`, `created_at`, `updated_at`, `DOB`, `otp`, `otp_expiry`, `reset_token`, `reset_token_expiry`, `gender`) VALUES
(19, 'Shikshant Jung Karki', 'ck@gmail.com', '$2y$10$XKv.DAwJHENJrIXOEzOMxePNqIemomJo7smbanC8GjOviuEPwz8rS', '9846591279', 'Biratnagar 6', 'Super Admin', NULL, '2025-05-01 10:44:39', '2025-05-04 18:05:55', '2009-10-05', NULL, NULL, NULL, NULL, 'male'),
(20, 'Amogh Pokhrel', 'admin@gmail.com', '$2y$10$VRdpilb.bi.FdYfzOXwo7u2V4EkSB9tzSoTdxJg/4SM.eg3IKG/76', '9876543210', 'Biratnagar', 'Admin', NULL, '2025-05-01 11:23:44', '2025-05-07 04:12:52', '2025-05-04', NULL, NULL, NULL, NULL, 'male'),
(21, 'Samipya Ghimire', 'samipya@gmail.com', '$2y$10$wbTnExWJVIEuE9VnKQIRy.zlXnecMmdZU6doV3/56nMn12CyuUTWq', '9876543211', 'Biratnagar', 'Admin', NULL, '2025-05-01 11:49:39', '2025-05-07 04:13:54', '2011-06-04', NULL, NULL, NULL, NULL, 'male'),
(23, 'Raman Raj Giri', 'raman@gmail.com', '$2y$10$XeLCVBsbAp.Zeh1xIV2Q0u.66j4zk5tVdPDwlkev04MgBAX.gF7zG', '9800965020', 'Biratnagar', 'Staff', 'Designer', '2025-05-01 12:07:34', '2025-05-31 13:32:54', '2025-05-08', NULL, NULL, NULL, NULL, 'male'),
(24, 'Saksham Shrestha', 'saksham@gmail.com', '$2y$10$IXaYSTczuHaVTFezXx1fOupKdAqVwaUQpf5wfebDhvOQPUTsDWUJm', '9816387777', 'Biratnagar', 'Staff', 'Designer', '2025-05-01 12:40:51', '2025-05-31 13:32:57', '2025-05-04', NULL, NULL, NULL, NULL, 'male'),
(25, 'Swagat Shrestha', 'swagat@gmail.com', '$2y$10$a.wUm6zltUUDgegzlHmOHOCuOoY488eZ8/R9YFFCBAAWHIIhpJMoG', '9876555678', 'Biratnagar', 'Customer', NULL, '2025-05-01 12:43:56', '2025-05-01 12:43:56', '2023-07-06', NULL, NULL, NULL, NULL, NULL),
(27, 'Keshav Kumar Singh', 'keshav@gmail.com', '$2y$10$dh94/.GMCYPdPD2bArVGYeOCR/l2.w4Ak.WilrLu.vvIos06kBMR2', '9876598765', 'Biratnagar', 'Customer', NULL, '2025-05-02 12:29:30', '2025-05-02 12:29:30', '2019-03-07', NULL, NULL, NULL, NULL, NULL),
(37, 'Zenish Neupane', 'zeni@gmail.com', '$2y$10$X16lixtFy/dATd.LipLcfeKDBzokEvMwPJCpejs0BdT15yObSApOS', '9816389797', 'Biratnagar', 'Staff', 'Designer', '2025-05-08 12:35:09', '2025-05-08 12:35:09', '2025-05-08', NULL, NULL, NULL, NULL, 'male'),
(38, 'Rabin Chaudhari', 'rabin@gmail.com', '$2y$10$tkV4ArLNNkF3CoB/HAAQH.0KsFIeSrp/sAxalPSa8.LfLPynihalG', '9876545678', 'Biratnagar', 'Staff', 'Designer', '2025-06-02 02:40:27', '2025-06-02 03:57:52', '2025-06-10', NULL, NULL, NULL, NULL, 'male');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'Super Admin' THEN
        INSERT INTO super_admin (user_id) VALUES (NEW.id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_user_role_remove` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF OLD.role = 'Super Admin' AND NEW.role != 'Super Admin' THEN
        DELETE FROM super_admin WHERE user_id = NEW.id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_user_role_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'Super Admin' AND (OLD.role != 'Super Admin' OR OLD.role IS NULL) THEN
        INSERT INTO super_admin (user_id) VALUES (NEW.id);
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `additional_field_types`
--
ALTER TABLE `additional_field_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `additional_info_28`
--
ALTER TABLE `additional_info_28`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `fk_additional_info_28_template_modifications` (`template_modification_id`);

--
-- Indexes for table `additional_info_29`
--
ALTER TABLE `additional_info_29`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `fk_additional_info_29_template_modifications` (`template_modification_id`);

--
-- Indexes for table `additional_info_30`
--
ALTER TABLE `additional_info_30`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `fk_additional_info_30_template_modifications` (`template_modification_id`);

--
-- Indexes for table `additional_info_33`
--
ALTER TABLE `additional_info_33`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `template_modification_id` (`template_modification_id`);

--
-- Indexes for table `additional_info_34`
--
ALTER TABLE `additional_info_34`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `template_modification_id` (`template_modification_id`);

--
-- Indexes for table `additional_info_38`
--
ALTER TABLE `additional_info_38`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `template_modification_id` (`template_modification_id`);

--
-- Indexes for table `additional_info_39`
--
ALTER TABLE `additional_info_39`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `template_modification_id` (`template_modification_id`);

--
-- Indexes for table `additional_info_40`
--
ALTER TABLE `additional_info_40`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `template_modification_id` (`template_modification_id`);

--
-- Indexes for table `additional_info_fields`
--
ALTER TABLE `additional_info_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `field_type_id` (`field_type_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `cart_item_line`
--
ALTER TABLE `cart_item_line`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cart_item_line_cart` (`cart_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`c_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `color_name` (`color_name`);

--
-- Indexes for table `custom_template_requests`
--
ALTER TABLE `custom_template_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `media_type_id` (`media_type_id`),
  ADD KEY `assigned_staff_id` (`assigned_staff_id`),
  ADD KEY `preferred_staff_id` (`preferred_staff_id`);

--
-- Indexes for table `designer`
--
ALTER TABLE `designer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_designer_staff_id` (`staff_id`);

--
-- Indexes for table `design_revisions`
--
ALTER TABLE `design_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `media_type`
--
ALTER TABLE `media_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `order_handling`
--
ALTER TABLE `order_handling`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `fk_order_handling_order` (`order_id`);

--
-- Indexes for table `order_item_line`
--
ALTER TABLE `order_item_line`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ca_it_id` (`ca_it_id`),
  ADD KEY `idx_oid` (`oid`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_item_id` (`order_item_id`);

--
-- Indexes for table `payment_tracking`
--
ALTER TABLE `payment_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_uuid` (`transaction_uuid`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `preferences`
--
ALTER TABLE `preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `receipt_numbers`
--
ALTER TABLE `receipt_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subscription_limits`
--
ALTER TABLE `subscription_limits`
  ADD PRIMARY KEY (`limit_id`),
  ADD KEY `idx_last_reset_date` (`last_reset_date`),
  ADD KEY `idx_user_limits` (`user_id`,`custom_design_count`,`template_modification_count`,`download_count`);

--
-- Indexes for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `super_admin`
--
ALTER TABLE `super_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `c_id` (`c_id`),
  ADD KEY `media_type_id` (`media_type_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `template_modifications`
--
ALTER TABLE `template_modifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `media_type_id` (`media_type_id`);

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
-- AUTO_INCREMENT for table `additional_field_types`
--
ALTER TABLE `additional_field_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `additional_info_28`
--
ALTER TABLE `additional_info_28`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_29`
--
ALTER TABLE `additional_info_29`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_30`
--
ALTER TABLE `additional_info_30`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_33`
--
ALTER TABLE `additional_info_33`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `additional_info_34`
--
ALTER TABLE `additional_info_34`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_38`
--
ALTER TABLE `additional_info_38`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_39`
--
ALTER TABLE `additional_info_39`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_40`
--
ALTER TABLE `additional_info_40`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_info_fields`
--
ALTER TABLE `additional_info_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cart_item_line`
--
ALTER TABLE `cart_item_line`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `custom_template_requests`
--
ALTER TABLE `custom_template_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `designer`
--
ALTER TABLE `designer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `design_revisions`
--
ALTER TABLE `design_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `media_type`
--
ALTER TABLE `media_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `order_handling`
--
ALTER TABLE `order_handling`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `order_item_line`
--
ALTER TABLE `order_item_line`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `payment_tracking`
--
ALTER TABLE `payment_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `preferences`
--
ALTER TABLE `preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `receipt_numbers`
--
ALTER TABLE `receipt_numbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subscription_limits`
--
ALTER TABLE `subscription_limits`
  MODIFY `limit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `super_admin`
--
ALTER TABLE `super_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `template_modifications`
--
ALTER TABLE `template_modifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `additional_info_28`
--
ALTER TABLE `additional_info_28`
  ADD CONSTRAINT `fk_additional_info_28_modifications` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_additional_info_28_requests` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_29`
--
ALTER TABLE `additional_info_29`
  ADD CONSTRAINT `additional_info_29_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_additional_info_29_modifications` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_30`
--
ALTER TABLE `additional_info_30`
  ADD CONSTRAINT `additional_info_30_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_additional_info_30_modifications` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_33`
--
ALTER TABLE `additional_info_33`
  ADD CONSTRAINT `additional_info_33_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `additional_info_33_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `additional_info_33_ibfk_3` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_34`
--
ALTER TABLE `additional_info_34`
  ADD CONSTRAINT `additional_info_34_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `additional_info_34_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `additional_info_34_ibfk_3` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_38`
--
ALTER TABLE `additional_info_38`
  ADD CONSTRAINT `additional_info_38_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `additional_info_38_ibfk_2` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_39`
--
ALTER TABLE `additional_info_39`
  ADD CONSTRAINT `additional_info_39_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `additional_info_39_ibfk_2` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_40`
--
ALTER TABLE `additional_info_40`
  ADD CONSTRAINT `additional_info_40_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `additional_info_40_ibfk_2` FOREIGN KEY (`template_modification_id`) REFERENCES `template_modifications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `additional_info_fields`
--
ALTER TABLE `additional_info_fields`
  ADD CONSTRAINT `additional_info_fields_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`c_id`),
  ADD CONSTRAINT `additional_info_fields_ibfk_2` FOREIGN KEY (`field_type_id`) REFERENCES `additional_field_types` (`id`),
  ADD CONSTRAINT `additional_info_fields_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_item_line`
--
ALTER TABLE `cart_item_line`
  ADD CONSTRAINT `fk_cart_item_line_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `category_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `custom_template_requests`
--
ALTER TABLE `custom_template_requests`
  ADD CONSTRAINT `custom_template_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `custom_template_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`c_id`),
  ADD CONSTRAINT `custom_template_requests_ibfk_3` FOREIGN KEY (`media_type_id`) REFERENCES `media_type` (`id`),
  ADD CONSTRAINT `custom_template_requests_ibfk_4` FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff` (`id`),
  ADD CONSTRAINT `custom_template_requests_ibfk_5` FOREIGN KEY (`preferred_staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `designer`
--
ALTER TABLE `designer`
  ADD CONSTRAINT `fk_designer_staff_id` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `design_revisions`
--
ALTER TABLE `design_revisions`
  ADD CONSTRAINT `design_revisions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `custom_template_requests` (`id`);

--
-- Constraints for table `media_type`
--
ALTER TABLE `media_type`
  ADD CONSTRAINT `media_type_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_handling`
--
ALTER TABLE `order_handling`
  ADD CONSTRAINT `fk_order_handling_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_item_line`
--
ALTER TABLE `order_item_line`
  ADD CONSTRAINT `order_item_line_ibfk_1` FOREIGN KEY (`oid`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_line_ibfk_2` FOREIGN KEY (`ca_it_id`) REFERENCES `cart_item_line` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_item_line` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `preferences`
--
ALTER TABLE `preferences`
  ADD CONSTRAINT `preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `receipt_numbers`
--
ALTER TABLE `receipt_numbers`
  ADD CONSTRAINT `receipt_numbers_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sizes`
--
ALTER TABLE `sizes`
  ADD CONSTRAINT `sizes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`c_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sizes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_limits`
--
ALTER TABLE `subscription_limits`
  ADD CONSTRAINT `subscription_limits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD CONSTRAINT `subscription_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `super_admin`
--
ALTER TABLE `super_admin`
  ADD CONSTRAINT `super_admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `templates_ibfk_1` FOREIGN KEY (`c_id`) REFERENCES `category` (`c_id`),
  ADD CONSTRAINT `templates_ibfk_2` FOREIGN KEY (`media_type_id`) REFERENCES `media_type` (`id`),
  ADD CONSTRAINT `templates_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `template_modifications`
--
ALTER TABLE `template_modifications`
  ADD CONSTRAINT `template_modifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `template_modifications_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  ADD CONSTRAINT `template_modifications_ibfk_3` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`),
  ADD CONSTRAINT `template_modifications_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `category` (`c_id`),
  ADD CONSTRAINT `template_modifications_ibfk_5` FOREIGN KEY (`media_type_id`) REFERENCES `media_type` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
