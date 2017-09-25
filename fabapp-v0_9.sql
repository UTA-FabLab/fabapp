-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2017 at 09:11 PM
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
CREATE DATABASE IF NOT EXISTS `fabapp-v0.9` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `fabapp-v0.9`;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

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

CREATE TABLE `acct_charge` (
  `ac_id` int(11) NOT NULL,
  `a_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `ac_date` datetime NOT NULL,
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

CREATE TABLE `authrecipients` (
  `ar_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `auth_accts`
--

CREATE TABLE `auth_accts` (
  `aa_id` int(11) NOT NULL,
  `a_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `valid` enum('Y','N') NOT NULL DEFAULT 'Y',
  `aa_date` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `carrier`
--

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

CREATE TABLE `citation` (
  `c_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `c_date` datetime NOT NULL,
  `c_notes` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `d_id` int(11) NOT NULL,
  `device_id` varchar(4) NOT NULL,
  `public_view` enum('Y','N') NOT NULL DEFAULT 'N',
  `device_desc` varchar(255) NOT NULL,
  `d_duration` time NOT NULL DEFAULT '00:00:00',
  `base_price` decimal(7,5) NOT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  `device_key` varchar(128) NOT NULL,
  `salt_key` varchar(64) NOT NULL,
  `exp_key` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`d_id`, `device_id`, `public_view`, `device_desc`, `d_duration`, `base_price`, `dg_id`, `url`, `device_key`, `salt_key`, `exp_key`) VALUES
(1, '0001', 'N', 'Bandsaw', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(2, '0002', 'N', 'Bench Grinder', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(3, '0003', 'Y', 'Brother Embroider', '00:00:00', '0.00000', 11, NULL, '', '', NULL),
(4, '0004', 'Y', 'CNC Plasma Cutter', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(5, '0005', 'N', 'Commercial Blender', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(6, '0006', 'N', 'Compound Miter Saw', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(7, '0007', 'N', 'Disc Sander', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(8, '0008', 'N', 'EDM Machine', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(9, '0009', 'Y', 'Janome Serger #1', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(10, '0010', 'Y', 'Janome Serger #2', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(11, '0011', 'Y', 'Janome Sewing #1', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(12, '0012', 'Y', 'Janome Sewing #2', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(13, '0013', 'N', 'Airbrush station', '00:00:00', '0.00000', 14, NULL, '', '', NULL),
(14, '0014', 'N', 'Machinist\'s Drill Press', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(15, '0015', 'N', 'Scroll saw', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(16, '0016', 'N', 'Sherline CNC Mill', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(17, '0017', 'N', 'Sherline CNC Lathe', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(18, '0018', 'N', 'Shopbot Handi-bot', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(19, '0019', 'N', 'Shopbot PRS-Alpha ', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(20, '0020', 'N', 'Sawstop Table Saw', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(21, '0021', 'Y', 'Polyprinter #1', '00:00:00', '0.00000', 2, 'polyprinter-1.uta.edu', '', '', NULL),
(22, '0022', 'Y', 'Polyprinter #2', '00:00:00', '0.00000', 2, 'polyprinter-2.uta.edu', '', '', NULL),
(23, '0023', 'Y', 'Polyprinter #3', '00:00:00', '0.00000', 2, 'polyprinter-3.uta.edu', '', '', NULL),
(24, '0024', 'Y', 'Polyprinter #4', '00:00:00', '0.00000', 2, 'polyprinter-4.uta.edu', '', '', NULL),
(25, '0025', 'Y', 'Polyprinter #5', '00:00:00', '0.00000', 2, 'polyprinter-5.uta.edu', '', '', NULL),
(26, '0026', 'Y', 'Polyprinter #6', '00:00:00', '0.00000', 2, 'polyprinter-6.uta.edu', '', '', NULL),
(27, '0027', 'Y', 'Polyprinter #7', '00:00:00', '0.00000', 2, 'polyprinter-7.uta.edu', '', '', NULL),
(28, '0028', 'Y', 'Polyprinter #8', '00:00:00', '0.00000', 2, 'polyprinter-8.uta.edu', '', '', NULL),
(29, '0029', 'Y', 'Polyprinter #9', '00:00:00', '0.00000', 2, 'polyprinter-9.uta.edu', '', '', NULL),
(30, '0030', 'Y', 'Polyprinter #10', '00:00:00', '0.00000', 15, 'polyprinter-10.uta.edu', '', '', NULL),
(31, '0031', 'Y', 'Orion Delta', '00:00:00', '0.00000', 8, 'lib-od3d.uta.edu', '', '', NULL),
(32, '0032', 'Y', 'Rostock MAX', '00:00:00', '0.00000', 8, 'rostockmax.uta.edu', '', '', NULL),
(33, '0033', 'Y', 'Kossel Pro ', '00:00:00', '0.00000', 8, 'kossel.uta.edu', '', '', NULL),
(34, '0034', 'Y', 'Epilog Laser', '01:00:00', '0.00000', 4, NULL, '', '', NULL),
(35, '0035', 'Y', 'Boss Laser', '01:00:00', '0.00000', 4, NULL, '', '', NULL),
(36, '0036', 'Y', 'Roland CNC Mill', '00:00:00', '0.00000', 12, NULL, '', '', NULL),
(37, '0037', 'Y', '3D Scanner Station', '00:00:00', '0.00000', 9, NULL, '', '', NULL),
(38, '0038', 'N', 'Glass Kiln', '00:00:00', '0.00000', 16, NULL, '', '', NULL),
(39, '0039', 'N', 'Ceramics Kiln', '00:00:00', '0.00000', 16, NULL, '', '', NULL),
(40, '0040', 'N', 'Mcor paper 3d printer', '00:00:00', '0.00000', NULL, NULL, '', '', NULL),
(41, '0041', 'Y', 'Roland Vinyl Cutter', '00:00:00', '0.00000', 5, NULL, '', '', NULL),
(42, '0042', 'Y', 'Electronics Station', '00:00:00', '0.00000', 6, NULL, '', '', NULL),
(43, '0043', 'Y', 'uPrint SEplus', '00:00:00', '0.00000', 7, NULL, '', '', NULL),
(44, '0044', 'Y', 'Oculus Rift', '00:00:00', '0.00000', 13, NULL, '', '', NULL),
(45, '0045', 'Y', 'Screeny McScreen Press', '00:00:00', '0.00000', 17, NULL, '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_group`
--

CREATE TABLE `device_group` (
  `dg_id` int(11) NOT NULL,
  `dg_name` varchar(10) NOT NULL,
  `dg_parent` int(11) DEFAULT NULL,
  `dg_desc` varchar(50) NOT NULL,
  `payFirst` enum('Y','N') NOT NULL DEFAULT 'N',
  `selectMatsFirst` enum('Y','N') NOT NULL DEFAULT 'N',
  `storable` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `device_group`
--

INSERT INTO `device_group` (`dg_id`, `dg_name`, `dg_parent`, `dg_desc`, `payFirst`, `selectMatsFirst`, `storable`) VALUES
(1, '3d', NULL, '(Generic 3D Printer)', 'N', 'Y', 'Y'),
(2, 'poly', 1, 'PolyPrinter', 'N', 'Y', 'Y'),
(3, 'shop', NULL, 'Shop Room', 'N', 'N', 'N'),
(4, 'laser', NULL, 'Laser Cutter', 'N', 'Y', 'N'),
(5, 'vinyl', NULL, 'Vinyl Cutter', 'N', 'N', 'N'),
(6, 'e_station', NULL, 'Electronics Station', 'N', 'N', 'N'),
(7, 'uprint', 1, 'Stratus uPrint', 'Y', 'Y', 'Y'),
(8, 'delta', 1, 'Delta 3D Printer', 'N', 'Y', 'Y'),
(9, 'scan', NULL, '3D Scan', 'N', 'N', 'N'),
(10, 'sew', NULL, 'Sewing Station', 'N', 'N', 'N'),
(11, 'embroidery', NULL, 'Embroidery Machines', 'N', 'N', 'N'),
(12, 'mill', NULL, 'CNC Mill', 'N', 'Y', 'N'),
(13, 'vr', NULL, 'VR Equipment', 'N', 'N', 'N'),
(14, 'air_brush', NULL, 'Air Brush Station', 'N', 'N', 'N'),
(15, 'NFPrinter', 1, 'Ninja Flex 3D Printer', 'N', 'Y', 'Y'),
(16, 'kiln', NULL, 'Electric Kilns', 'N', 'N', 'N'),
(17, 'screen', NULL, 'Silk Screen', 'N', 'N', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `device_materials`
--

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
(27, 11, 52),
(28, 16, 4),
(29, 7, 27),
(30, 7, 32),
(31, 12, 1),
(32, 12, 2),
(33, 12, 11),
(34, 2, 33),
(35, 2, 34),
(36, 2, 35),
(37, 2, 36),
(38, 2, 37),
(39, 2, 38),
(40, 2, 39),
(41, 2, 40),
(42, 2, 41),
(43, 2, 42),
(44, 5, 43),
(45, 5, 44),
(46, 5, 45),
(47, 5, 46),
(48, 5, 48),
(49, 5, 30),
(50, 5, 31),
(51, 4, 53),
(52, 15, 54),
(53, 15, 55),
(54, 15, 56),
(55, 5, 57),
(56, 5, 58),
(57, 2, 59),
(58, 2, 60),
(59, 2, 61),
(60, 2, 62),
(61, 2, 63),
(62, 2, 64),
(63, 5, 65),
(64, 5, 66),
(65, 5, 67),
(66, 15, 69),
(67, 15, 70),
(68, 15, 71),
(69, 17, 72),
(70, 17, 73),
(71, 17, 74),
(72, 17, 75),
(73, 17, 76);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `m_id` int(11) NOT NULL,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(6,2) DEFAULT NULL,
  `unit` varchar(10) NOT NULL,
  `color_hex` varchar(6) DEFAULT NULL,
  `measurable` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`m_id`, `m_name`, `m_parent`, `price`, `unit`, `color_hex`, `measurable`) VALUES
(1, 'ABS (Generic)', NULL, '0.0000', 'gram(s)', NULL, 'N'),
(2, 'Acrylic', NULL, '0.0000', '', NULL, 'N'),
(3, 'Cotton', NULL, '0.0000', '', NULL, 'N'),
(4, 'Glass', NULL, '0.0000', '', NULL, 'N'),
(5, 'Leather', NULL, '0.0000', '', NULL, 'N'),
(6, 'PLA', NULL, '0.0500', 'gram(s)', NULL, 'N'),
(7, 'Vinyl (Generic)', NULL, '0.2500', 'inch(es)', NULL, 'N'),
(8, 'Wood', NULL, '0.0000', '', NULL, 'N'),
(9, 'Basswood', NULL, '0.0000', '', NULL, 'N'),
(10, 'Plywood', NULL, '0.0000', '', NULL, 'N'),
(11, 'MDF', NULL, '0.0000', '', NULL, 'N'),
(12, 'Other', NULL, NULL, '', NULL, 'N'),
(13, 'ABS Black', 1, '0.0500', 'gram(s)', '000000', 'Y'),
(14, 'ABS Blue', 1, '0.0500', 'gram(s)', '0047BB', 'Y'),
(15, 'ABS Green', 1, '0.0500', 'gram(s)', '00BF6F', 'Y'),
(16, 'ABS Orange', 1, '0.0500', 'gram(s)', 'fe5000', 'Y'),
(17, 'ABS Red', 1, '0.0500', 'gram(s)', 'D22630', 'Y'),
(18, 'ABS Purple', 1, '0.0500', 'gram(s)', '440099', 'Y'),
(19, 'ABS Yellow', 1, '0.0500', 'gram(s)', 'FFE900', 'Y'),
(20, 'Vinyl Black', 7, '0.2500', 'inch(es)', '000000', 'Y'),
(21, 'Vinyl Blue Royal', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(22, 'Vinyl Green', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(23, 'Vinyl Orange', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(24, 'Vinyl Flat Red', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(25, 'Vinyl Violet', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(26, 'Vinyl Yellow', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(27, 'uPrint Material', NULL, '8.1935', 'inch<sup>3</sup>', 'fdffe2', 'Y'),
(28, 'Learner Supplied Filament', NULL, '0.0000', '', NULL, 'Y'),
(29, 'ABS White', 1, '0.0500', 'gram(s)', 'ffffff', 'Y'),
(30, 'Vinyl White', 7, '0.2500', 'inch(es)', 'ffffff', 'Y'),
(31, 'Transfer Tape', NULL, '0.1000', 'inch(es)', NULL, 'Y'),
(32, 'uPrint Support', NULL, '8.1935', 'inch<sup>3</sup>', NULL, 'Y'),
(33, 'ABS Bronze', 1, '0.0500', 'gram(s)', 'A09200', 'Y'),
(35, 'ABS Pink', 1, '0.0500', 'gram(s)', 'FF3EB5', 'Y'),
(36, 'ABS Mint', 1, '0.0500', 'gram(s)', '88DBDF', 'Y'),
(37, 'ABS Glow in the dark', 1, '0.0500', 'gram(s)', 'D0DEBB', 'Y'),
(38, 'ABS Trans Orange', 1, '0.0500', 'gram(s)', 'FCC89B', 'Y'),
(39, 'ABS Trans Red', 1, '0.0500', 'gram(s)', 'DF4661', 'Y'),
(40, 'ABS Trans White', 1, '0.0500', 'gram(s)', 'D9D9D6', 'Y'),
(41, 'ABS Trans Green', 1, '0.0500', 'gram(s)', 'A0DAB3', 'Y'),
(42, 'ABS Gold', 1, '0.0500', 'gram(s)', 'CFB500', 'Y'),
(43, 'Vinyl Blue Ocean', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(44, 'Vinyl Glossy Red', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(45, 'Vinyl Pink', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(46, 'Vinyl Turquoise', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(48, 'Vinyl Silver', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(49, 'uPrint Bed New', NULL, '0.0000', '', NULL, 'N'),
(50, 'uPrint Bed Partly_Used', NULL, '0.0000', '', NULL, 'N'),
(51, 'Delrin Sheet', NULL, '0.0000', '', NULL, 'N'),
(52, 'Thread', NULL, '0.2347', 'stitch', NULL, 'Y'),
(53, 'Paper-stock (chipboard)', NULL, NULL, '', NULL, 'N'),
(54, 'NinjaFlex (Generic)', NULL, '0.1500', 'gram(s)', NULL, 'N'),
(55, 'NinjaFlex Black', 54, '0.1500', 'gram(s)', '000000', 'Y'),
(56, 'NinjaFlex White', 54, '0.1500', 'gram(s)', 'ffffff', 'Y'),
(57, 'Vinyl Coral', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(58, 'Vinyl *Scraps', 7, '0.0000', 'inch(es)', NULL, 'Y'),
(59, 'ABS Lime', 1, '0.0500', 'gram(s)', 'c2e189', 'Y'),
(60, 'ABS Copper', 1, '0.0500', 'gram(s)', '7C4D3A', 'Y'),
(61, 'ABS Silver', 1, '0.0500', 'gram(s)', '9EA2A2', 'Y'),
(62, 'ABS Trans Black', 1, '0.0500', 'gram(s)', '919D9D', 'Y'),
(63, 'ABS Trans Blue', 1, '0.0500', 'gram(s)', 'C8D8EB', 'Y'),
(64, 'ABS Trans Yellow', 1, '0.0500', 'gram(s)', 'ECD898', 'Y'),
(65, 'Vinyl Mint', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(66, 'Vinyl Lime Green', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(67, 'Vinyl Gold', 7, '0.2500', 'inch(es)', NULL, 'Y'),
(68, 'Screen Ink(Generic)', NULL, '0.0500', 'gram(s)', NULL, 'Y'),
(69, 'NinjaFlex Water', 54, '0.1500', 'gram(s)', NULL, 'Y'),
(70, 'NinjaFlex Lava', 54, '0.1500', 'gram(s)', NULL, 'Y'),
(71, 'NinjaFlex Sapphire', 54, '0.1500', 'gram(s)', NULL, 'Y'),
(72, 'Comet White', 68, '0.0500', 'gram(s)', 'ffffff', 'Y'),
(73, 'Pitch Black', 68, '0.0500', 'gram(s)', '000000', 'Y'),
(74, 'Neptune Blue', 68, '0.0500', 'gram(s)', '0011ff', 'Y'),
(75, 'Mars Red', 68, '0.0500', 'gram(s)', 'ff0000', 'Y'),
(76, 'Starburst Yellow', 68, '0.0500', 'gram(s)', 'faff00', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `mats_used`
--

CREATE TABLE `mats_used` (
  `mu_id` int(11) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `m_id` int(11) NOT NULL,
  `unit_used` decimal(7,2) DEFAULT NULL,
  `mu_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
(78, 83, 68, NULL, '2017-04-03 07:32:36', 10, '1000000010', NULL),
(79, 85, 27, '-0.49', '2017-04-03 08:01:38', 20, '1000000010', ''),
(80, 85, 32, '-0.66', '2017-04-03 08:01:38', 20, '1000000010', ''),
(81, 86, 14, '-13.00', '2017-04-29 01:27:37', 14, '1000000010', 'This is a test3.'),
(82, 87, 59, '-99.00', '2017-04-10 07:04:19', 12, '1000000010', 'Kill this test print.'),
(83, 88, 14, NULL, '2017-08-16 14:22:05', 10, '1000000010', NULL),
(84, 89, 42, NULL, '2017-08-16 14:22:42', 10, '1000000010', NULL),
(85, 90, 64, NULL, '2017-08-16 14:23:12', 10, '1000000010', NULL),
(86, 91, 59, NULL, '2017-08-16 14:23:39', 10, '1000000010', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `objbox`
--

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
(8, '2017-04-03 11:54:44', NULL, '5D', NULL, 81, '1000000010'),
(12, '2017-04-28 20:27:37', NULL, '4F', NULL, 86, '1000000010');

-- --------------------------------------------------------

--
-- Table structure for table `purpose`
--

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

CREATE TABLE `queue` (
  `q_id` int(11) NOT NULL,
  `d_id` varchar(4) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `CODE` varchar(10) NOT NULL,
  `created` datetime NOT NULL,
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

CREATE TABLE `rfid` (
  `rf_id` int(11) NOT NULL,
  `rfid_no` varchar(64) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `rfid`
--

INSERT INTO `rfid` (`rf_id`, `rfid_no`, `operator`) VALUES
(7, '20422415733', '1000000002'),
(8, '769918733', '1000000003'),
(9, '10818417333', '1000000004'),
(10, '1728117533', '1000000005'),
(11, '17819818469', '1000000006'),
(12, '1212215733', '1000000008'),
(13, '15817918469', '1000000009'),
(14, '132137203252', '1000000010');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

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
(9, 'LeadFabLabian', 'Student Lead', '0.00'),
(10, 'Admin', 'Administration', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `service_call`
--

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
(4, 'box_number', '4', 'Number of Box used for object storage'),
(5, 'letter', '7', 'Number of Rows in each Box'),
(6, 'grace_period', '300', 'Grace period allotted to each Ticket(sec)'),
(7, 'limit', '300', '(seconds) 5 minutes before auto-logout'),
(8, 'limit_long', '6000', '(seconds) 100 minutes before auto-logout'),
(9, 'maxHold', '14', '# of Days for Holding Period for 3D prints'),
(10, 'serving', '0', 'Now serving number such and such'),
(11, 'Lserving', '0', 'Now serving number such and such'),
(12, 'sNext', '0', 'Last Number Issued for 3D Printing'),
(13, 'lNext', '0', 'Last Number Issued for Laser'),
(14, 'forgotten', 'webapps.uta.edu/oit/selfservice/', 'UTA\'s Password Reset'),
(15, 'check_expire', 'N', 'Do we deny users if they have an expired membership. Expected Values (Y,N)'),
(16, 'ip_range_1', '/^127\\.0\\.0\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(17, 'ip_range_2', '/^127\\.0\\.0\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(18, 'inspectPrint', 'Once a print has been picked up & paid for we can not issue a refund.', 'Disclosure for picking up a 3D Print'),
(19, 'site_name', 'FabApp', 'Name of site owner'),
(20, 'paySite', 'https://csgoldweb.uta.edu/admin/quicktran/main.php', '3rd party Pay System. (CsGold)'),
(21, 'paySite_name', 'CsGold', '3rd party pay site'),
(22, 'interdepartmental', 'Library Interdepartmental', ''),
(23, 'currency', 'dollar', 'Icon as Defined by Font Awesome'),
(24, 'LvlOfStaff', '8', 'First role level ID of staff.'),
(25, 'minRoleTrainer', '10', 'Minimum Role Level of Trainer, below this value you can not issue a training.'),
(26, 'editTrans', '9', ' Role level required to edit a Transaction'),
(27, 'api_key', 'opensaysame', 'Temp fix to secure FLUD script'),
(28, 'acct3', '8', 'At what level can a user use the house account.'),
(29, 'acct4', '8', 'At what level can a user use the generic department account.'),
(30, 'dateFormat', 'M d, Y g:i a', 'format the date using Php\'s date() function.'),
(31, 'timezone', 'America/Chicago', 'Set Local Time Zone'),
(32, 'timeInterval', '.25', 'Minimum time unit of an hour.');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

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
(15, 'Cancelled'),
(20, 'Charge to Accounts'),
(21, 'FabLab Account'),
(22, 'Library Account');

-- --------------------------------------------------------

--
-- Table structure for table `time_clock`
--

CREATE TABLE `time_clock` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `tm_enroll`
--

CREATE TABLE `tm_enroll` (
  `tm_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `completed` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `current` enum('Y','N') NOT NULL,
  `altered_date` datetime DEFAULT NULL,
  `altered_notes` text,
  `altered_by` varchar(10) DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tm_enroll`
--

INSERT INTO `tm_enroll` (`tm_id`, `operator`, `completed`, `staff_id`, `current`, `altered_date`, `altered_notes`, `altered_by`, `expiration_date`) VALUES
(2, '1000000000', '2017-08-17 07:26:54', '1000000009', 'Y', NULL, NULL, NULL, NULL),
(12, '1000000000', '2017-08-18 11:42:33', '1000000010', 'N', NULL, NULL, NULL, NULL),
(2, '1000000001', '2017-09-05 15:48:46', '1000000010', 'Y', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trainingmodule`
--

CREATE TABLE `trainingmodule` (
  `tm_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `tm_desc` text,
  `duration` time NOT NULL,
  `d_id` int(11) DEFAULT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `tm_required` enum('Y','N') NOT NULL DEFAULT 'N',
  `file_name` varchar(100) DEFAULT NULL,
  `file_bin` mediumblob,
  `class_size` int(11) NOT NULL,
  `tm_stamp` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `trainingmodule`
--

INSERT INTO `trainingmodule` (`tm_id`, `title`, `tm_desc`, `duration`, `d_id`, `dg_id`, `tm_required`, `file_name`, `file_bin`, `class_size`, `tm_stamp`) VALUES
(1, 'CNC Room', 'General Training for initial access into the CNC Room. Additional training is require to use the power tools located inside of this facility.', '00:00:00', NULL, 3, 'Y', NULL, NULL, 4, '2017-07-20 12:54:36'),
(2, 'Bandsaw Training', 'Required Training for use of the Bandsaw. Covers safety, basic mechanics, and basic methods.', '01:15:00', 1, NULL, 'N', NULL, NULL, 4, '2017-08-18 10:54:59'),
(3, 'Grinding Basics', 'Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, Required Training for the Bench Top Grinder. Covers basic safety standards, approved materials, maintenance requirements, ', '01:15:00', 2, NULL, 'Y', NULL, NULL, 4, '2017-07-20 12:54:36'),
(4, 'CNC Plasma Cutter Training', 'Learn the how to use the power of plasma to cut through the toughest of materials', '02:00:00', 4, NULL, 'Y', NULL, NULL, 4, '2017-07-20 12:54:36'),
(5, 'Sliding Compound Miter Saw Training', 'Learn safe operating procedures, how to setup miter saw for various cutting operations, and to recognize dangerous situations and their potential ramifications...', '01:45:00', 6, NULL, 'Y', NULL, NULL, 4, '2017-08-17 21:33:24'),
(6, 'Test Device Trainings', 'asdfsdf', '02:00:00', NULL, 50, 'N', NULL, NULL, 4, '2017-07-20 12:54:36'),
(13, 'test2', 'asdfsdf', '00:15:00', 2, NULL, 'Y', NULL, NULL, 1, '2017-07-20 12:54:36'),
(12, 'Benchy 6', 'A deeper dive into what to grind and how to do it.', '01:45:00', 2, NULL, 'Y', NULL, NULL, 6, '2017-07-20 13:01:50'),
(14, 'Test 3', 'asdfsdf', '01:00:00', 2, NULL, 'Y', NULL, NULL, 1, '2017-07-20 12:54:36'),
(15, 'Test 4', 'asdfsdf', '00:30:00', 2, NULL, 'N', NULL, NULL, 3, '2017-07-20 12:54:36'),
(16, 'Test 5', 'asdfsdf', '03:30:00', 2, NULL, 'Y', NULL, NULL, 1, '2017-07-20 12:54:36'),
(17, '345345', 'asdfgdfgd', '01:00:00', 2, NULL, 'N', NULL, NULL, 2, '2017-07-20 12:54:36'),
(18, '345345', 'adsfgdfg', '01:00:00', 2, NULL, 'Y', NULL, NULL, 1, '2017-07-20 12:54:36'),
(19, '34534534', 'dfgdfg', '01:00:00', 2, NULL, 'Y', NULL, NULL, 1, '2017-07-20 12:55:12'),
(20, 'Painting with Air', 'STUFF', '02:00:00', 13, NULL, 'Y', NULL, NULL, 3, '2017-09-05 15:52:13');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

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
(88, 21, '1123215555', '02:00:00', '2017-08-16 09:22:05', NULL, NULL, 10, 1, '1000000010'),
(86, 21, '1000000021', '03:00:00', '2017-04-03 11:55:32', '2017-04-03 12:43:43', '00:48:11', 14, 1, '1000000010'),
(87, 22, '1000000022', '02:00:00', '2017-04-10 01:57:32', '2017-04-10 02:04:19', '00:06:47', 12, 1, '1000000010'),
(89, 22, '1000000001', '05:00:00', '2017-08-16 09:22:42', NULL, NULL, 10, 4, '1000000010'),
(90, 23, '5000000000', '04:20:00', '2017-08-16 09:23:12', NULL, NULL, 10, 2, '1000000010'),
(91, 26, '6000000000', '06:20:00', '2017-08-16 09:23:39', NULL, NULL, 10, 2, '1000000010');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `u_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `r_id` int(11) NOT NULL,
  `exp_date` datetime DEFAULT NULL,
  `icon` varchar(20) DEFAULT NULL,
  `adj_date` datetime DEFAULT NULL,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `operator`, `r_id`, `exp_date`, `icon`, `adj_date`, `notes`) VALUES
(1, '1000000001', 1, NULL, NULL, NULL, ''),
(2, '1000000002', 2, NULL, NULL, NULL, ''),
(3, '1000000003', 3, NULL, NULL, NULL, ''),
(4, '1000000004', 4, NULL, NULL, NULL, ''),
(5, '1000000007', 7, NULL, NULL, NULL, ''),
(6, '1000000008', 8, NULL, NULL, NULL, ''),
(7, '1000000009', 9, NULL, 'bicycle', NULL, ''),
(8, '1000000010', 10, NULL, 'institution', NULL, '');

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
  MODIFY `dm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;
--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `m_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;
--
-- AUTO_INCREMENT for table `mats_used`
--
ALTER TABLE `mats_used`
  MODIFY `mu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;
--
-- AUTO_INCREMENT for table `objbox`
--
ALTER TABLE `objbox`
  MODIFY `o_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
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
  MODIFY `rf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
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
  MODIFY `tm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
