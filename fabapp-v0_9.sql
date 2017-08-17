-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2017 at 03:58 AM
-- Server version: 5.7.14
-- PHP Version: 5.6.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fabapp-v0.9`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `a_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `acct` varchar(50) NOT NULL,
  `balance` decimal(6,2) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`a_id`, `name`, `acct`, `balance`, `operator`) VALUES
(1, 'Outstanding', 'Outstanding charge, Payment required', '0.00', '1000129288'),
(2, 'CSGold', 'CSGold Account', '0.00', '1000129288'),
(3, 'FabLab', 'FabLab\'s in-House Charge Account', '0.00', '1000129288'),
(4, 'Library UES', '1000ABC', '0.00', '1000129288');

-- --------------------------------------------------------

--
-- Table structure for table `acct_charge`
--

DROP TABLE IF EXISTS `acct_charge`;
CREATE TABLE `acct_charge` (
  `ac_id` int(11) NOT NULL,
  `a_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `ac_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `operator` varchar(10) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `recon_date` datetime DEFAULT NULL,
  `recon_id` varchar(10) DEFAULT NULL,
  `ac_notes` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `authrecipients`
--

DROP TABLE IF EXISTS `authrecipients`;
CREATE TABLE `authrecipients` (
  `ar_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `authrecipients`
--

INSERT INTO `authrecipients` (`ar_id`, `trans_id`, `operator`) VALUES
(1, 80, '1000000210'),
(2, 86, '1000129288');

-- --------------------------------------------------------

--
-- Table structure for table `auth_accts`
--

DROP TABLE IF EXISTS `auth_accts`;
CREATE TABLE `auth_accts` (
  `aa_id` int(11) NOT NULL,
  `a_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `valid` enum('Y','N') NOT NULL DEFAULT 'Y',
  `aa_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff_id` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `auth_accts`
--

INSERT INTO `auth_accts` (`aa_id`, `a_id`, `operator`, `valid`, `aa_date`, `staff_id`) VALUES
(1, 3, '1000129288', 'Y', '2017-02-13 18:03:49', '1000129288');

-- --------------------------------------------------------

--
-- Table structure for table `carrier`
--

DROP TABLE IF EXISTS `carrier`;
CREATE TABLE `carrier` (
  `ca_id` int(11) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `email` varchar(110) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `carrier`
--

INSERT INTO `carrier` (`ca_id`, `provider`, `email`) VALUES
(1, 'AT&T', 'number@txt.att.net'),
(2, 'Verizon', 'number@vtext.com'),
(3, 'T-Mobile', 'number@tmomail.net'),
(4, 'Sprint', 'number@messaging.sprintpcs.com'),
(5, 'Virgin Mobile', 'number@vmobl.com');

-- --------------------------------------------------------

--
-- Table structure for table `citation`
--

DROP TABLE IF EXISTS `citation`;
CREATE TABLE `citation` (
  `c_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `c_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `d_id` int(11) NOT NULL,
  `device_id` varchar(4) NOT NULL,
  `public_view` enum('Y','N') NOT NULL DEFAULT 'N',
  `device_desc` varchar(255) NOT NULL,
  `d_duration` time NOT NULL DEFAULT '00:00:00',
  `base_price` decimal(7,5) NOT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  `device_key` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`d_id`, `device_id`, `public_view`, `device_desc`, `d_duration`, `base_price`, `dg_id`, `url`, `device_key`) VALUES
(1, '0001', 'N', 'Bandsaw', '00:00:00', '0.00000', 3, NULL, ''),
(2, '0002', 'N', 'Bench Grinder', '00:00:00', '0.00000', 3, NULL, ''),
(3, '0003', 'Y', 'Brother Embroider', '00:00:00', '0.00000', 16, NULL, ''),
(4, '0004', 'Y', 'CNC Plasma cutter', '00:00:00', '0.00000', 3, NULL, ''),
(5, '0005', 'N', 'commercial blender', '00:00:00', '0.00000', 3, NULL, ''),
(6, '0006', 'N', 'Compound sliding miter saw', '00:00:00', '0.00000', 3, NULL, ''),
(7, '0007', 'N', 'Disc sander', '00:00:00', '0.00000', 3, NULL, ''),
(8, '0008', 'N', 'EDM Machine', '00:00:00', '0.00000', 3, NULL, ''),
(9, '0009', 'Y', 'Janome 990D Serger #1', '00:00:00', '1.00000', 10, NULL, ''),
(10, '0010', 'Y', 'Janome 990D Serger #2', '00:00:00', '1.00000', 10, NULL, ''),
(11, '0011', 'Y', 'Janome Sewing #1', '00:00:00', '1.00000', 10, NULL, ''),
(12, '0012', 'Y', 'Janome Sewing #2', '00:00:00', '1.00000', 10, NULL, ''),
(13, '0013', 'N', 'Airbrush station', '00:00:00', '0.00000', 14, NULL, ''),
(14, '0014', 'N', 'Machinist\'s drill press', '00:00:00', '0.00000', 3, NULL, ''),
(15, '0015', 'N', 'Scroll saw', '00:00:00', '0.00000', 3, NULL, ''),
(16, '0016', 'N', 'Sherline Desktop CNC Mill', '00:00:00', '0.00000', 3, NULL, ''),
(17, '0017', 'N', 'Sherline Desktop CNC Lathe', '00:00:00', '0.00000', 3, NULL, ''),
(18, '0018', 'N', 'Shopbot Handi-bot', '00:00:00', '0.00000', 3, NULL, ''),
(19, '0019', 'N', 'Shopbot PRS-Alpha ', '00:00:00', '0.00000', 3, NULL, ''),
(20, '0020', 'N', 'Sawstop Table Saw', '00:00:00', '0.00000', 3, NULL, ''),
(21, '0021', 'Y', 'Polyprinter #1', '00:00:00', '0.00000', 2, 'polyprinter-1.uta.edu', ''),
(22, '0022', 'Y', 'Polyprinter #2', '00:00:00', '0.00000', 2, 'polyprinter-2.uta.edu', ''),
(23, '0023', 'Y', 'Polyprinter #3', '00:00:00', '0.00000', 2, 'polyprinter-3.uta.edu', ''),
(24, '0024', 'Y', 'Polyprinter #4', '00:00:00', '0.00000', 2, 'polyprinter-4.uta.edu', ''),
(25, '0025', 'Y', 'Polyprinter #5', '00:00:00', '0.00000', 2, 'polyprinter-5.uta.edu', ''),
(26, '0026', 'Y', 'Polyprinter #6', '00:00:00', '0.00000', 2, 'polyprinter-6.uta.edu', ''),
(27, '0027', 'Y', 'Polyprinter #7', '00:00:00', '0.00000', 2, 'polyprinter-7.uta.edu', ''),
(28, '0028', 'Y', 'Polyprinter #8', '00:00:00', '0.00000', 2, 'polyprinter-8.uta.edu', ''),
(29, '0029', 'Y', 'Polyprinter #9', '00:00:00', '0.00000', 2, 'polyprinter-9.uta.edu', ''),
(30, '0030', 'Y', 'Polyprinter #10', '00:00:00', '0.00000', 15, 'polyprinter-10.uta.edu', ''),
(31, '0031', 'Y', 'Orion Delta', '00:00:00', '0.00000', 8, 'lib-od3d.uta.edu', ''),
(32, '0032', 'Y', 'Rostock MAX', '00:00:00', '0.00000', 8, 'rostockmax.uta.edu', ''),
(33, '0033', 'Y', 'Kossel Pro ', '00:00:00', '0.00000', 8, 'kossel.uta.edu', ''),
(34, '0034', 'Y', 'Epilog Laser', '01:00:00', '0.00000', 4, NULL, ''),
(35, '0035', 'Y', 'Boss Laser', '01:00:00', '0.00000', 4, NULL, ''),
(36, '0036', 'Y', 'CNC Mini Mill', '00:00:00', '0.00000', 12, NULL, ''),
(37, '0037', 'Y', '3D Scanner station', '00:00:00', '0.00000', 9, NULL, ''),
(38, '0038', 'N', 'Glass kiln', '00:00:00', '0.00000', 11, NULL, ''),
(39, '0039', 'N', 'Ceramics kiln', '00:00:00', '0.00000', 11, NULL, ''),
(40, '0040', 'N', 'Mcor paper 3d printer', '00:00:00', '0.00000', NULL, NULL, ''),
(41, '0041', 'Y', 'Roland Vinyl Cutter', '00:00:00', '0.00000', 5, NULL, ''),
(42, '0042', 'Y', 'Electronics Station', '00:00:00', '0.00000', 6, NULL, ''),
(43, '0043', 'Y', 'uPrint SEplus', '00:00:00', '0.00000', 7, NULL, ''),
(44, '0044', 'Y', 'Oculus Rift', '00:00:00', '0.00000', 13, NULL, ''),
(45, '0045', 'N', 'CNC Room', '00:00:00', '0.00000', NULL, NULL, ''),
(46, '0065', 'Y', 'Screeny McScreen Press', '00:00:00', '0.00000', 17, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `device_group`
--

DROP TABLE IF EXISTS `device_group`;
CREATE TABLE `device_group` (
  `dg_id` int(11) NOT NULL,
  `dg_name` varchar(10) NOT NULL,
  `dg_parent` int(11) DEFAULT NULL,
  `dg_desc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `device_group`
--

INSERT INTO `device_group` (`dg_id`, `dg_name`, `dg_parent`, `dg_desc`) VALUES
(1, '3d', NULL, '(Generic 3D Printer)'),
(2, 'poly', 1, 'Poly Printer'),
(3, 'shop', NULL, 'Shop Room'),
(4, 'laser', NULL, 'Laser Cutter'),
(5, 'vinyl', NULL, 'Vinyl Cutter'),
(6, 'e_station', NULL, 'Electronics Station'),
(7, 'uprint', 1, 'Stratus uPrint'),
(8, 'delta', 1, 'Delta 3d Printer'),
(9, 'scan', NULL, '3D Scan'),
(10, 'sew', NULL, 'Sewing Station'),
(11, 'kiln', NULL, 'Electric Kilns'),
(12, 'mill', NULL, 'CNC Mill'),
(13, 'vr', NULL, 'VR Equipment'),
(14, 'air_brush', NULL, 'Air Brush Station'),
(15, 'NFPrinter', 1, 'Ninja Flex 3D Printer'),
(16, 'embroidery', NULL, 'Embroidery Machines'),
(17, 'screen', NULL, 'Silk Screen');

-- --------------------------------------------------------

--
-- Table structure for table `device_materials`
--

DROP TABLE IF EXISTS `device_materials`;
CREATE TABLE `device_materials` (
  `dm_id` int(11) NOT NULL,
  `dg_id` int(11) NOT NULL,
  `m_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `device_materials`
--

INSERT INTO `device_materials` (`dm_id`, `dg_id`, `m_id`) VALUES
(1, 2, 1),
(2, 2, 13),
(3, 2, 14),
(4, 2, 15),
(6, 2, 16),
(7, 2, 17),
(8, 2, 18),
(9, 2, 19),
(10, 4, 2),
(11, 4, 5),
(12, 4, 8),
(13, 4, 9),
(14, 4, 10),
(15, 4, 11),
(16, 4, 12),
(17, 5, 7),
(18, 5, 20),
(19, 5, 21),
(20, 5, 22),
(21, 5, 23),
(22, 5, 24),
(23, 5, 25),
(24, 5, 26),
(25, 2, 29),
(26, 8, 28),
(27, 10, 52),
(28, 11, 4),
(29, 7, 27),
(30, 7, 32),
(34, 12, 1),
(35, 12, 2),
(36, 12, 11),
(39, 2, 33),
(40, 2, 34),
(41, 2, 35),
(42, 2, 36),
(43, 2, 37),
(44, 2, 38),
(45, 2, 39),
(46, 2, 40),
(47, 2, 41),
(48, 2, 42),
(49, 5, 43),
(50, 5, 44),
(51, 5, 45),
(52, 5, 46),
(53, 5, 48),
(54, 5, 30),
(55, 5, 31),
(56, 4, 53),
(57, 15, 54),
(58, 15, 55),
(59, 15, 56),
(60, 5, 57),
(61, 5, 58),
(62, 2, 59),
(63, 2, 60),
(64, 2, 61),
(65, 2, 62),
(66, 2, 63),
(67, 2, 64),
(68, 5, 65),
(69, 5, 66),
(70, 5, 67),
(71, 17, 68);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `m_id` int(11) NOT NULL,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT NULL,
  `unit` varchar(10) NOT NULL,
  `color_hex` varchar(6) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`m_id`, `m_name`, `m_parent`, `price`, `unit`, `color_hex`) VALUES
(1, 'ABS (Generic)', NULL, '0.05', 'gram(s)', NULL),
(2, 'Acrylic', NULL, '0.00', '', NULL),
(3, 'Cotton', NULL, '0.00', '', NULL),
(4, 'Glass', NULL, '0.00', '', NULL),
(5, 'Leather', NULL, '0.00', '', NULL),
(6, 'PLA', NULL, '0.05', 'gram(s)', NULL),
(7, 'Vinyl (Generic)', NULL, '0.25', 'inch(es)', NULL),
(8, 'Wood', NULL, '0.00', '', NULL),
(9, 'Basswood', NULL, '0.00', '', NULL),
(10, 'Plywood', NULL, '0.00', '', NULL),
(11, 'MDF', NULL, '0.00', '', NULL),
(12, 'Other', NULL, NULL, '', NULL),
(13, 'ABS Black', 1, '0.05', 'gram(s)', '000000'),
(14, 'ABS Blue', 1, '0.05', 'gram(s)', '0047BB'),
(15, 'ABS Green', 1, '0.05', 'gram(s)', '00BF6F'),
(16, 'ABS Orange', 1, '0.05', 'gram(s)', 'fe5000'),
(17, 'ABS Red', 1, '0.05', 'gram(s)', 'D22630'),
(18, 'ABS Purple', 1, '0.05', 'gram(s)', '440099'),
(19, 'ABS Yellow', 1, '0.05', 'gram(s)', 'FFE900'),
(20, 'Vinyl Black', 7, '0.25', 'inch(es)', '000000'),
(21, 'Vinyl Blue Royal', 7, '0.25', 'inch(es)', NULL),
(22, 'Vinyl Green', 7, '0.25', 'inch(es)', NULL),
(23, 'Vinyl Orange', 7, '0.25', 'inch(es)', NULL),
(24, 'Vinyl Flat Red', 7, '0.25', 'inch(es)', NULL),
(25, 'Vinyl Violet', 7, '0.25', 'inch(es)', NULL),
(26, 'Vinyl Yellow', 7, '0.25', 'inch(es)', NULL),
(27, 'uPrint Material', NULL, '0.50', 'gram(s)', NULL),
(28, 'Learner Supplied Filament', NULL, '0.00', '', NULL),
(29, 'ABS White', 1, '0.05', 'gram(s)', 'ffffff'),
(30, 'Vinyl White', 7, '0.25', 'inch(es)', 'ffffff'),
(31, 'Transfer Tape', NULL, '0.10', 'inch(es)', NULL),
(32, 'uPrint Support', NULL, '0.50', 'gram(s)', NULL),
(33, 'ABS Bronze', 1, '0.05', 'gram(s)', 'A09200'),
(44, 'Vinyl Glossy Red', 7, '0.25', 'inch(es)', NULL),
(35, 'ABS Pink', 1, '0.05', 'gram(s)', 'FF3EB5'),
(36, 'ABS Mint', 1, '0.05', 'gram(s)', '88DBDF'),
(37, 'ABS Glow in the dark', 1, '0.05', 'gram(s)', 'D0DEBB'),
(38, 'ABS Trans Orange', 1, '0.05', 'gram(s)', 'FCC89B'),
(39, 'ABS Trans Red', 1, '0.05', 'gram(s)', 'DF4661'),
(40, 'ABS Trans White', 1, '0.05', 'gram(s)', 'D9D9D6'),
(41, 'ABS Trans Green', 1, '0.05', 'gram(s)', 'A0DAB3'),
(42, 'ABS Gold', 1, '0.05', 'gram(s)', 'CFB500'),
(43, 'Vinyl Blue Ocean', 7, '0.25', 'inch(es)', NULL),
(45, 'Vinyl Pink', 7, '0.25', 'inch(es)', NULL),
(46, 'Vinyl Turquoise', 7, '0.25', 'inch(es)', NULL),
(48, 'Vinyl Silver', 7, '0.25', 'inch(es)', NULL),
(49, 'uPrint Bed New', NULL, '0.00', '', NULL),
(50, 'uPrint Bed Partly_Used', NULL, '0.00', '', NULL),
(51, 'Delrin Sheet', NULL, '0.00', '', NULL),
(52, 'Thread', NULL, '1.00', '1000', NULL),
(53, 'Paper-stock (chipboard)', NULL, NULL, '', NULL),
(54, 'NinjaFlex (Generic)', NULL, '0.15', 'gram(s)', NULL),
(55, 'NinjaFlex Black', 54, '0.15', 'gram(s)', '000000'),
(56, 'NinjaFlex White', 54, '0.15', 'gram(s)', 'ffffff'),
(57, 'Vinyl Coral', 7, '0.25', 'inch(es)', NULL),
(58, 'Vinyl *Scraps', 7, '0.00', 'inch(es)', NULL),
(59, 'ABS Lime', 1, '0.05', 'gram(s)', 'c2e189'),
(60, 'ABS Copper', 1, '0.05', 'gram(s)', '7C4D3A'),
(61, 'ABS Silver', 1, '0.05', 'gram(s)', '9EA2A2'),
(62, 'ABS Trans Black', 1, '0.05', 'gram(s)', '919D9D'),
(63, 'ABS Trans Blue', 1, '0.05', 'gram(s)', 'C8D8EB'),
(64, 'ABS Trans Yellow', 1, '0.05', 'gram(s)', 'ECD898'),
(65, 'Vinyl Mint', 7, '0.25', 'inch(es)', NULL),
(66, 'Vinyl Lime Green', 7, '0.25', 'inch(es)', NULL),
(67, 'Vinyl Gold', 7, '0.25', 'inch(es)', NULL),
(68, 'Screen Ink(Generic)', NULL, '0.05', 'gram(s)', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mats_used`
--

DROP TABLE IF EXISTS `mats_used`;
CREATE TABLE `mats_used` (
  `mu_id` int(11) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `m_id` int(11) NOT NULL,
  `unit_used` decimal(7,2) DEFAULT NULL,
  `mu_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_id` int(4) NOT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  `mu_notes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `mats_used`
--

INSERT INTO `mats_used` (`mu_id`, `trans_id`, `m_id`, `unit_used`, `mu_date`, `status_id`, `staff_id`, `mu_notes`) VALUES
(21, 43, 27, '-1.00', '2017-02-06 05:02:44', 10, '1000000009', ''),
(22, 43, 32, '-1.00', '2017-02-06 05:02:44', 10, '1000000009', ''),
(23, NULL, 13, '10000.00', '2017-02-06 06:58:05', 8, '1000129288', 'replenish inventory'),
(24, 51, 1, NULL, '2017-02-06 08:43:16', 10, '1000000009', NULL),
(25, 52, 1, NULL, '2017-02-06 08:45:03', 10, '1000000009', NULL),
(26, 54, 13, NULL, '2017-02-06 09:33:40', 10, '1000000009', NULL),
(27, 55, 13, '-5.00', '2017-02-09 06:34:49', 14, '1000000009', '|file.stl|body'),
(28, 56, 9, NULL, '2017-02-06 09:37:57', 14, '1000000009', NULL),
(29, 59, 52, NULL, '2017-02-06 10:44:45', 10, '1000000009', NULL),
(30, 60, 5, NULL, '2017-02-06 19:22:04', 14, '1000000009', NULL),
(31, 62, 12, NULL, '2017-02-06 23:14:37', 14, '1000000009', NULL),
(32, 63, 9, NULL, '2017-02-06 23:39:00', 14, '1000000009', NULL),
(33, 64, 9, NULL, '2017-02-06 23:40:59', 14, '1000000009', NULL),
(34, 65, 9, NULL, '2017-02-06 23:44:27', 14, '1000000009', NULL),
(35, 66, 5, NULL, '2017-02-06 23:47:30', 14, '1000000009', NULL),
(36, 67, 2, NULL, '2017-02-06 23:50:29', 14, '1000000009', NULL),
(37, 68, 2, NULL, '2017-02-06 23:52:13', 14, '1000000009', NULL),
(38, 69, 9, NULL, '2017-02-06 23:56:11', 14, '1000000009', NULL),
(39, 70, 2, NULL, '2017-02-07 00:06:09', 14, '1000000009', NULL),
(40, 71, 9, NULL, '2017-02-07 00:06:34', 14, '1000000009', NULL),
(41, 72, 9, NULL, '2017-02-07 13:05:23', 14, '1000000009', NULL),
(42, 73, 12, NULL, '2017-02-07 13:18:15', 13, '1000000008', NULL),
(44, 55, 13, '-5.00', '2017-02-09 06:34:49', 12, '1000000009', '|file2.stl|sdfasdfasdfasdfasdf'),
(45, 74, 62, '-50.00', '2017-02-09 07:01:22', 20, '1000000009', ''),
(46, 75, 14, '-25.00', '2017-02-09 09:23:48', 14, '1000000009', '|Test.stl|Test Github recovery'),
(47, 76, 37, '-5.00', '2017-02-09 20:53:38', 14, '1000000009', '|file_name.stl|Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'),
(48, 77, 9, NULL, '2017-02-10 16:15:15', 14, '1000000009', NULL),
(49, 78, 59, '-5.00', '2017-02-10 16:22:33', 14, '1000000009', ''),
(50, 79, 9, NULL, '2017-02-12 10:13:26', 10, '1000000009', NULL),
(51, 80, 42, '-5.00', '2017-02-13 18:20:56', 14, '1000000008', ''),
(54, NULL, 14, '850.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(55, NULL, 15, '1278.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(56, NULL, 16, '750.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(57, NULL, 17, '153.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(58, NULL, 18, '458.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(59, NULL, 19, '1164.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(60, NULL, 30, '1843.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(61, NULL, 35, '1115.00', '2017-02-18 21:40:50', 8, '1000129288', NULL),
(62, NULL, 36, '402.00', '2017-02-18 22:28:23', 8, '1000129288', NULL),
(63, NULL, 37, '883.00', '2017-02-18 22:38:42', 8, '1000129288', NULL),
(64, NULL, 38, '1142.00', '2017-02-18 22:38:42', 8, '1000129288', NULL),
(65, NULL, 39, '883.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(66, NULL, 40, '1142.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(67, NULL, 41, '214.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(68, NULL, 42, '354.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(69, NULL, 59, '830.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(70, NULL, 60, '1128.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(71, NULL, 61, '940.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(72, NULL, 62, '1373.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(73, NULL, 63, '670.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(74, NULL, 64, '1431.00', '2017-02-18 22:47:26', 8, '1000129288', NULL),
(75, NULL, 29, '1845.00', '2017-02-20 18:55:59', 8, '1000129288', NULL),
(76, NULL, 33, '460.00', '2017-02-20 18:55:59', 8, '1000129288', NULL),
(77, 81, 42, '-5.00', '2017-04-03 16:54:44', 14, '1000000010', ''),
(78, 83, 68, NULL, '2017-04-03 07:32:36', 10, '1000000010', NULL),
(79, 85, 27, '-0.49', '2017-04-03 08:01:38', 20, '1000000010', ''),
(80, 85, 32, '-0.66', '2017-04-03 08:01:38', 20, '1000000010', ''),
(81, 86, 14, '-5.00', '2017-04-07 18:55:13', 14, '1000000010', 'This is a test3.'),
(82, 87, 59, '-99.00', '2017-04-10 07:04:19', 12, '1000000010', 'Kill this test print.');

-- --------------------------------------------------------

--
-- Table structure for table `objbox`
--

DROP TABLE IF EXISTS `objbox`;
CREATE TABLE `objbox` (
  `o_id` int(11) NOT NULL,
  `o_start` datetime NOT NULL,
  `o_end` datetime DEFAULT NULL,
  `address` varchar(10) NOT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `objbox`
--

INSERT INTO `objbox` (`o_id`, `o_start`, `o_end`, `address`, `operator`, `trans_id`, `staff_id`) VALUES
(1, '2017-02-09 00:34:49', NULL, '3E', NULL, 55, '1000000009'),
(2, '2017-02-09 01:01:22', NULL, '2D', NULL, 74, '1000000009'),
(3, '2017-02-09 03:23:48', NULL, '3A', NULL, 75, '1000000009'),
(4, '2017-02-09 14:53:38', NULL, '3F', NULL, 76, '1000000006'),
(5, '2017-02-10 10:22:33', NULL, '4D', NULL, 78, '1000000009'),
(6, '2017-02-13 12:20:56', NULL, '2B', NULL, 80, '1000000010'),
(8, '2017-04-03 11:54:44', NULL, '5D', NULL, 81, '1000000010');

-- --------------------------------------------------------

--
-- Table structure for table `purpose`
--

DROP TABLE IF EXISTS `purpose`;
CREATE TABLE `purpose` (
  `p_id` int(11) NOT NULL,
  `p_title` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `purpose`
--

INSERT INTO `purpose` (`p_id`, `p_title`) VALUES
(1, 'Curricular Research'),
(2, 'Extra Curricular Research'),
(3, 'Non-Academic'),
(4, 'Service-Call');

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

DROP TABLE IF EXISTS `queue`;
CREATE TABLE `queue` (
  `q_id` int(11) NOT NULL,
  `d_id` varchar(4) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `CODE` varchar(10) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `q_start` datetime DEFAULT NULL,
  `duration` time NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `ca_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reply`
--

DROP TABLE IF EXISTS `reply`;
CREATE TABLE `reply` (
  `sr_id` int(11) NOT NULL,
  `sc_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `sr_notes` text NOT NULL,
  `sr_time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rfid`
--

DROP TABLE IF EXISTS `rfid`;
CREATE TABLE `rfid` (
  `rf_id` int(11) NOT NULL,
  `rfid_no` varchar(64) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `rfid`
--

INSERT INTO `rfid` (`rf_id`, `rfid_no`, `operator`) VALUES
(3, '1000556', '1000129288'),
(4, '68177218252', '1001142661'),
(5, '1507918569', '1001142662'),
(6, '1817518569', '1001142663');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `r_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `lvl_desc` varchar(255) NOT NULL,
  `r_rate` decimal(9,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`r_id`, `title`, `lvl_desc`, `r_rate`) VALUES
(1, 'Visitor', 'Non-member lvl', '0.00'),
(2, 'Learner', 'Student Level Membership', '0.00'),
(3, 'Learner-RFID', 'Learner\'s with RFID access', '2.00'),
(4, 'Community Member', 'Non-Student, 4 Month Membership', '10.00'),
(7, 'Service', 'Service technicians that need to work on FabLab Equipment', '0.00'),
(8, 'FabLabian', 'Student Worker', '0.00'),
(9, 'SuperFabLabian', 'Student Worker Supervisor ', '0.00'),
(10, 'Admin', 'Administration', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `service_call`
--

DROP TABLE IF EXISTS `service_call`;
CREATE TABLE `service_call` (
  `sc_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `d_id` int(11) NOT NULL,
  `sl_id` int(11) NOT NULL,
  `solved` enum('Y','N') NOT NULL DEFAULT 'N',
  `sc_notes` text NOT NULL,
  `sc_time` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `service_lvl`
--

DROP TABLE IF EXISTS `service_lvl`;
CREATE TABLE `service_lvl` (
  `sl_id` int(11) NOT NULL,
  `msg` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `service_lvl`
--

INSERT INTO `service_lvl` (`sl_id`, `msg`) VALUES
(1, 'Maintenance'),
(5, 'Issue'),
(7, 'Out For OutReach'),
(10, 'NonOperating');

-- --------------------------------------------------------

--
-- Table structure for table `site_variables`
--

DROP TABLE IF EXISTS `site_variables`;
CREATE TABLE `site_variables` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `value` varchar(100) NOT NULL,
  `notes` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `site_variables`
--

INSERT INTO `site_variables` (`id`, `name`, `value`, `notes`) VALUES
(1, 'uprint_conv', '16.387', 'inches^3 to grams'),
(2, 'minTime', '1', 'Minimum hour charge for a device'),
(3, 'LaserMax', '1', 'Maximum Time Limit for Laser Cutter'),
(4, 'box_number', '5', 'Number of Box used for object storage'),
(5, 'letter', '8', 'Number of Rows in each Box. Letters A-Z (max value 26)'),
(6, 'grace_period', '300', 'Grace period allotted to each Ticket(sec)'),
(7, 'limit', '300', '(seconds) 5 minutes before auto-logout'),
(8, 'limit_long', '6000', '(seconds) 10 minutes before auto-logout'),
(9, 'maxHold', '336:00:00', '2 Week Holding Period for 3D prints'),
(10, 'serving', '10', 'Now serving number such and such'),
(11, 'forgotten', 'webapps.uta.edu/oit/selfservice/', 'UTA\'s Password Reset'),
(12, 'check_expire', 'N', 'Do we deny users if they have an expired membership. Expected Values (Y,N)'),
(13, 'ip_range_1', '/^127\\.0\\.0\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(14, 'ip_range_2', '/^127\\.0\\.0\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(15, 'inspectPrint', 'Once a print has been picked up & paid for we can not issue a refund.', 'Disclosure for picking up a 3D Print'),
(16, 'site_name', 'FabApp', 'Name of site owner'),
(17, 'paySite', 'https://csgoldweb.uta.edu/admin/quicktran/main.php', '3rd party Pay System. (CsGold)'),
(18, 'paySite_name', 'CsGold', '3rd party pay site'),
(19, 'interdepartmental', 'Library Interdepartmental', ''),
(20, 'currency', 'dollar', 'Icon as Defined by Font Awesome');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `status_id` int(11) NOT NULL,
  `msg` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `msg`) VALUES
(1, 'Denied'),
(2, 'Training Required'),
(3, 'Membership Expired'),
(8, 'Mats Received '),
(9, 'Mats Removed'),
(10, 'In Use'),
(11, 'Moveable'),
(12, 'Failed'),
(13, 'Missprocessed'),
(14, 'Completed'),
(20, 'Charge to Accounts'),
(21, 'FabLab Account'),
(22, 'Library Account'),
(15, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `time_clock`
--

DROP TABLE IF EXISTS `time_clock`;
CREATE TABLE `time_clock` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `time_clock`
--

INSERT INTO `time_clock` (`id`, `staff_id`, `start_time`, `end_time`, `duration`) VALUES
(1, '1000129288', '2017-02-16 10:46:12', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tm_enroll`
--

DROP TABLE IF EXISTS `tm_enroll`;
CREATE TABLE `tm_enroll` (
  `tm_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `completed` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tm_enroll`
--

INSERT INTO `tm_enroll` (`tm_id`, `operator`, `completed`, `staff_id`) VALUES
(1, '1000000002', '2017-01-30 12:00:00', '1000129288'),
(4, '1000000002', '2017-01-30 12:00:00', '1000129288');

-- --------------------------------------------------------

--
-- Table structure for table `trainingmodule`
--

DROP TABLE IF EXISTS `trainingmodule`;
CREATE TABLE `trainingmodule` (
  `tm_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `tm_desc` text,
  `duration` time NOT NULL,
  `d_id` int(11) DEFAULT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `tm_required` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `trainingmodule`
--

INSERT INTO `trainingmodule` (`tm_id`, `title`, `tm_desc`, `duration`, `d_id`, `dg_id`, `tm_required`) VALUES
(1, 'CNC Room', 'General Training for initial access into the CNC Room. Additional training is require to use the power tools located inside of this facility.', '00:00:00', NULL, 3, 'Y'),
(2, 'Bandsaw Training', 'Required Training for use of the Bandsaw. Covers safety, basic mechanics, and basic methods.', '01:00:00', 1, NULL, 'N'),
(3, 'Bench Grinder', 'Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, ', '01:00:00', 2, NULL, 'N'),
(4, 'CNC Plasma Cutter Training', 'Learn the how to use the power of plasma to cut through the toughest of materials', '02:00:00', 4, NULL, 'Y'),
(5, 'Sliding Compound Miter Saw Training', 'Learn safe operating procedures, how to setup miter saw for various cutting operations, and to recognize dangerous situations and their potential ramifications.', '01:30:00', 6, NULL, 'N'),
(6, 'Test Device Training', NULL, '00:00:00', 22, NULL, 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `trans_id` int(11) NOT NULL,
  `d_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `est_time` time DEFAULT NULL,
  `t_start` datetime NOT NULL,
  `t_end` datetime DEFAULT NULL,
  `duration` time DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `p_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`trans_id`, `d_id`, `operator`, `est_time`, `t_start`, `t_end`, `duration`, `status_id`, `p_id`, `staff_id`) VALUES
(76, 21, '1000000000', '05:00:00', '2017-02-09 14:53:08', '2017-02-09 14:53:38', '00:00:30', 14, 1, '1000000009'),
(77, 34, '1000000000', '01:00:00', '2017-02-10 10:15:15', '2017-02-10 10:17:26', '00:02:11', 14, 1, '1000000009'),
(75, 21, '1000000021', '04:00:00', '2017-02-09 03:23:16', '2017-02-09 03:23:48', '00:00:32', 14, 1, '1000000009'),
(73, 34, '1000000034', '01:00:00', '2017-02-07 07:18:16', '2017-02-07 07:18:20', '00:00:04', 14, 2, '1000000009'),
(74, 21, '1000000000', '10:00:00', '2017-02-09 01:00:59', '2017-02-09 01:01:22', '00:00:23', 20, 1, '1000000009'),
(72, 34, '1000000000', '01:00:00', '2017-02-07 07:05:23', '2017-02-07 07:05:28', '00:00:05', 14, 3, '1000000009'),
(71, 35, '1000000001', '01:00:00', '2017-02-06 18:06:34', '2017-02-13 12:35:36', '162:29:02', 14, 1, '1000000001'),
(70, 35, '1000000000', '01:00:00', '2017-02-06 18:06:09', '2017-02-06 18:06:13', '00:00:04', 14, 1, '1000000009'),
(69, 35, '1000000000', '01:00:00', '2017-02-06 17:56:11', '2017-02-06 17:56:15', '00:00:04', 14, 2, '1000000009'),
(68, 35, '1000000000', '01:00:00', '2017-02-06 17:52:13', '2017-02-06 17:52:18', '00:00:05', 14, 1, '1000000009'),
(67, 35, '1000000000', '01:00:00', '2017-02-06 17:50:29', '2017-02-06 17:50:55', '00:00:26', 14, 1, '1000000009'),
(66, 35, '1000000000', '01:00:00', '2017-02-06 17:47:30', '2017-02-06 17:47:35', '00:00:05', 14, 1, '1000000009'),
(65, 35, '1000000000', '01:00:00', '2017-02-06 17:44:27', '2017-02-06 17:44:31', '00:00:04', 14, 1, '1000000009'),
(64, 35, '1000000000', '01:00:00', '2017-02-06 17:40:59', '2017-02-06 17:41:03', '00:00:04', 14, 1, '1000000009'),
(63, 35, '1000000000', '01:00:00', '2017-02-06 17:39:00', '2017-02-06 17:39:07', '00:00:07', 14, 1, '1000000009'),
(62, 35, '1000000035', '01:00:00', '2017-02-06 17:14:37', '2017-02-06 17:14:41', '00:00:04', 14, 1, '1000000009'),
(61, 4, '1000000004', '01:00:00', '2017-02-06 16:48:31', '2017-02-06 16:57:54', '00:09:23', 14, 1, '1000000009'),
(60, 34, '1000000000', '01:00:00', '2017-02-06 13:22:04', '2017-02-06 16:40:23', '03:18:19', 14, 1, '1000000009'),
(59, 11, '1000000000', '01:00:00', '2017-02-06 04:44:45', '2017-02-06 11:11:45', '06:27:00', 14, 3, '1000000009'),
(58, 43, '1000000000', '01:00:00', '2017-02-06 04:43:06', '2017-04-03 00:00:00', '00:00:02', 14, 1, '1000000009'),
(57, 41, '1000000001', '01:00:00', '2017-04-02 04:31:34', '2017-04-03 02:21:53', '21:50:19', 14, 1, '1000000009'),
(44, 41, '1000000000', '01:00:00', '2017-02-06 01:33:13', '2017-02-06 01:37:57', '00:04:44', 14, 1, '1000000009'),
(45, 41, '1000000000', '01:00:00', '2017-02-06 01:40:09', '2017-02-06 01:40:29', '00:00:20', 14, 1, '1000000009'),
(46, 36, '1000000000', '01:00:00', '2017-02-06 02:27:01', '2017-02-06 02:27:34', '00:00:33', 14, 1, '1000000009'),
(47, 36, '1000000000', '01:00:00', '2017-02-06 02:28:35', '2017-02-06 02:37:29', '00:08:54', 14, 1, '1000000009'),
(48, 36, '1000000000', '01:00:00', '2017-02-06 02:37:38', '2017-02-06 02:38:28', '00:00:50', 14, 1, '1000000009'),
(49, 36, '1000000000', '01:00:00', '2017-02-06 02:38:39', '2017-02-06 02:41:30', '00:02:51', 14, 1, '1000000009'),
(50, 36, '1000000000', '01:00:00', '2017-02-06 02:41:41', '2017-02-06 02:43:04', '00:01:23', 14, 1, '1000000009'),
(51, 36, '1000000000', '01:00:00', '2017-02-06 02:43:16', '2017-02-06 02:44:52', '00:01:36', 14, 1, '1000000009'),
(52, 36, '1000000000', '01:00:00', '2017-02-06 02:45:03', '2017-02-06 02:49:54', '00:04:51', 14, 1, '1000000009'),
(53, 44, '1000000000', '01:00:00', '2017-02-06 02:52:38', '2017-02-06 02:52:48', '00:00:10', 14, 3, '1000000009'),
(54, 21, '1000000000', '01:00:00', '2017-02-06 03:33:40', '2017-02-06 03:34:07', '00:00:27', 14, 1, '1000000009'),
(55, 21, '1000000000', '01:00:00', '2017-02-06 03:34:59', '2017-02-09 00:33:09', '23:58:10', 14, 1, '1000000009'),
(56, 35, '1000000035', '01:00:00', '2017-02-06 03:37:57', '2017-02-06 11:34:42', '07:56:45', 14, 1, '1000000009'),
(78, 21, '1000000000', '01:00:00', '2017-02-10 10:17:44', '2017-02-10 10:22:33', '00:04:49', 14, 1, '1000000009'),
(79, 34, '1000000001', '01:00:00', '2017-02-12 04:13:26', '2017-02-13 11:54:47', '31:41:21', 14, 3, '1000000001'),
(80, 21, '1000000021', '01:05:00', '2017-02-13 11:26:31', '2017-02-13 12:19:37', '00:53:06', 14, 1, '1000000010'),
(81, 21, '1000000021', '01:00:00', '2017-04-03 03:46:57', '2017-04-03 11:43:49', '07:56:52', 14, 2, '1000000010'),
(82, 37, '1000000037', '03:00:00', '2017-04-03 02:15:21', '2017-04-10 02:23:52', '168:08:31', 14, 1, '1000000010'),
(83, 46, '1000000046', '03:00:00', '2017-04-03 02:32:36', NULL, NULL, 10, 2, '1000000010'),
(85, 43, '1000000043', '01:00:00', '2017-04-03 03:01:37', NULL, NULL, 10, 3, '1000000010'),
(86, 21, '1000000021', '03:00:00', '2017-04-03 11:55:32', '2017-04-03 12:43:43', '00:48:11', 11, 1, '1000000010'),
(87, 22, '1000000022', '02:00:00', '2017-04-10 01:57:32', '2017-04-10 02:04:19', '00:06:47', 12, 1, '1000000010');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `u_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `r_id` int(11) NOT NULL,
  `exp_date` datetime DEFAULT NULL,
  `icon` varchar(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `operator`, `r_id`, `exp_date`, `icon`) VALUES
(1, '1000000009', 9, '2017-05-31 23:59:59', 'bicycle'),
(2, '1000000002', 2, NULL, NULL),
(3, '1000000004', 4, NULL, NULL),
(4, '1000000007', 7, NULL, 'wrench'),
(5, '1000000008', 8, NULL, NULL),
(6, '1000000010', 10, NULL, 'institution'),
(7, '1000000001', 1, NULL, NULL),
(8, '1000129288', 9, '2017-05-31 23:59:59', 'bicycle'),
(9, '1001142661', 8, NULL, NULL),
(10, '1001142662', 8, NULL, NULL),
(11, '1001142663', 8, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `acct_charge`
--
ALTER TABLE `acct_charge`
  ADD PRIMARY KEY (`ac_id`);

--
-- Indexes for table `authrecipients`
--
ALTER TABLE `authrecipients`
  ADD PRIMARY KEY (`ar_id`),
  ADD KEY `trans_id` (`trans_id`);

--
-- Indexes for table `auth_accts`
--
ALTER TABLE `auth_accts`
  ADD PRIMARY KEY (`aa_id`);

--
-- Indexes for table `carrier`
--
ALTER TABLE `carrier`
  ADD PRIMARY KEY (`ca_id`);

--
-- Indexes for table `citation`
--
ALTER TABLE `citation`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`d_id`),
  ADD KEY `devices_index_device_id` (`device_id`);

--
-- Indexes for table `device_group`
--
ALTER TABLE `device_group`
  ADD PRIMARY KEY (`dg_id`),
  ADD UNIQUE KEY `dg_name` (`dg_name`);

--
-- Indexes for table `device_materials`
--
ALTER TABLE `device_materials`
  ADD PRIMARY KEY (`dm_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`m_id`);

--
-- Indexes for table `mats_used`
--
ALTER TABLE `mats_used`
  ADD PRIMARY KEY (`mu_id`);

--
-- Indexes for table `objbox`
--
ALTER TABLE `objbox`
  ADD PRIMARY KEY (`o_id`),
  ADD KEY `trans_id` (`trans_id`);

--
-- Indexes for table `purpose`
--
ALTER TABLE `purpose`
  ADD PRIMARY KEY (`p_id`);

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`q_id`),
  ADD KEY `que_index_device_id` (`d_id`),
  ADD KEY `que_index_operator` (`operator`);

--
-- Indexes for table `reply`
--
ALTER TABLE `reply`
  ADD PRIMARY KEY (`sr_id`);

--
-- Indexes for table `rfid`
--
ALTER TABLE `rfid`
  ADD PRIMARY KEY (`rf_id`),
  ADD UNIQUE KEY `rfid_no` (`rfid_no`),
  ADD KEY `rfid_index_operator` (`operator`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`r_id`);

--
-- Indexes for table `service_call`
--
ALTER TABLE `service_call`
  ADD PRIMARY KEY (`sc_id`);

--
-- Indexes for table `service_lvl`
--
ALTER TABLE `service_lvl`
  ADD PRIMARY KEY (`sl_id`);

--
-- Indexes for table `site_variables`
--
ALTER TABLE `site_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `time_clock`
--
ALTER TABLE `time_clock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tm_enroll`
--
ALTER TABLE `tm_enroll`
  ADD PRIMARY KEY (`tm_id`,`operator`),
  ADD KEY `tm_enroll_index_operator` (`operator`);

--
-- Indexes for table `trainingmodule`
--
ALTER TABLE `trainingmodule`
  ADD PRIMARY KEY (`tm_id`),
  ADD KEY `device_id` (`d_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`trans_id`),
  ADD KEY `device_id` (`d_id`),
  ADD KEY `transactions_index_operator` (`operator`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `operator` (`operator`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `a_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `acct_charge`
--
ALTER TABLE `acct_charge`
  MODIFY `ac_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `authrecipients`
--
ALTER TABLE `authrecipients`
  MODIFY `ar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `auth_accts`
--
ALTER TABLE `auth_accts`
  MODIFY `aa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `carrier`
--
ALTER TABLE `carrier`
  MODIFY `ca_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `citation`
--
ALTER TABLE `citation`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
--
-- AUTO_INCREMENT for table `device_group`
--
ALTER TABLE `device_group`
  MODIFY `dg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT for table `device_materials`
--
ALTER TABLE `device_materials`
  MODIFY `dm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `m_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;
--
-- AUTO_INCREMENT for table `mats_used`
--
ALTER TABLE `mats_used`
  MODIFY `mu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;
--
-- AUTO_INCREMENT for table `objbox`
--
ALTER TABLE `objbox`
  MODIFY `o_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `purpose`
--
ALTER TABLE `purpose`
  MODIFY `p_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `q_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `reply`
--
ALTER TABLE `reply`
  MODIFY `sr_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `rfid`
--
ALTER TABLE `rfid`
  MODIFY `rf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `service_call`
--
ALTER TABLE `service_call`
  MODIFY `sc_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `service_lvl`
--
ALTER TABLE `service_lvl`
  MODIFY `sl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `site_variables`
--
ALTER TABLE `site_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `time_clock`
--
ALTER TABLE `time_clock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `trainingmodule`
--
ALTER TABLE `trainingmodule`
  MODIFY `tm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
