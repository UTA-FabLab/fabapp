-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 09, 2018 at 02:31 PM
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

CREATE TABLE `accounts` (
  `a_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(140) NOT NULL,
  `balance` decimal(6,2) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`a_id`, `name`, `description`, `balance`, `operator`) VALUES
(1, 'Outstanding', 'Outstanding charge, Payment is due', '0.50', '1000129288'),
(2, 'CSGold', 'CSGold Account', '0.65', '1000129288'),
(3, 'FabLab', 'FabLab\'s in-House Charge Account', '0.25', '1000129288'),
(4, 'Library', 'General Library Account', '0.00', '1000129288');

-- --------------------------------------------------------

--
-- Table structure for table `acct_charge`
--

CREATE TABLE `acct_charge` (
  `ac_id` int(11) NOT NULL,
  `a_id` int(11) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `ac_date` datetime NOT NULL,
  `operator` varchar(10) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `ac_notes` text,
  `recon_date` datetime DEFAULT NULL,
  `recon_id` varchar(10) DEFAULT NULL
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
(5, 'Virgin Mobile', 'number@vmobl.com'),
(6, 'Prject Fi', 'number@msg.fi.google.com');

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
(1, '0001', 'Y', 'Bandsaw', '00:00:00', '0.00000', 20, NULL, '', '', NULL),
(2, '0002', 'Y', 'Bench Grinder', '00:00:00', '0.00000', 18, NULL, '', '', NULL),
(3, '0003', 'Y', 'Brother Embroider', '00:00:00', '0.00000', 11, NULL, '', '', NULL),
(4, '0004', 'Y', 'Plasma Cutter', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(6, '0006', 'Y', 'Compound Miter Saw', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(7, '0007', 'Y', 'Disc/Belt Sander Grizzly', '00:00:00', '0.00000', 18, NULL, '', '', NULL),
(48, '0048', 'Y', 'Drill Press Ryobi', '00:00:00', '0.00000', 19, NULL, '', '', NULL),
(9, '0009', 'Y', 'Janome Serger #1', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(10, '0010', 'Y', 'Janome Serger #2', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(11, '0011', 'Y', 'Janome Sewing #1', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(12, '0012', 'Y', 'Janome Sewing #2', '00:00:00', '1.00000', 10, NULL, '', '', NULL),
(13, '0013', 'Y', 'Airbrush station', '00:00:00', '0.00000', 14, NULL, '', '', NULL),
(14, '0014', 'Y', 'Drill Press Powermatic', '00:00:00', '0.00000', 19, NULL, '', '', NULL),
(15, '0015', 'Y', 'Scroll Saw', '00:00:00', '0.00000', 20, NULL, '', '', NULL),
(16, '0016', 'Y', 'Sherline CNC Mill', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(17, '0017', 'Y', 'Sherline CNC Lathe', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(18, '0018', 'Y', 'Shopbot Handi-bot', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(19, '0019', 'Y', 'Shopbot PRS-Alpha ', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
(20, '0020', 'Y', 'Sawstop Table Saw', '00:00:00', '0.00000', 3, NULL, '', '', NULL),
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
(38, '0038', 'Y', 'Kiln Glass', '00:00:00', '0.00000', 16, NULL, '', '', NULL),
(39, '0039', 'Y', 'Kiln Ceramics', '00:00:00', '0.00000', 16, NULL, '', '', NULL),
(46, '0046', 'Y', 'Kiln Mix Use', '00:00:00', '0.00000', 16, NULL, '', '', NULL),
(41, '0041', 'Y', 'Roland Vinyl Cutter', '00:00:00', '0.00000', 5, NULL, '', '', NULL),
(42, '0042', 'Y', 'Electronics Station', '00:00:00', '0.00000', 6, NULL, '', '', NULL),
(43, '0043', 'Y', 'uPrint SEplus', '00:00:00', '0.00000', 7, NULL, '', '', NULL),
(44, '0044', 'Y', 'Oculus Rift', '00:00:00', '0.00000', 13, NULL, '', '', NULL),
(45, '0045', 'Y', 'Screeny McScreen Press', '00:00:00', '0.00000', 17, NULL, '', '', NULL),
(47, '0047', 'Y', 'Disc/Belt Sander - Central Machinery', '00:00:00', '0.00000', 18, NULL, '', '', NULL),
(49, '0049', 'Y', 'SandBlaster', '00:00:00', '0.00000', 3, NULL, '', '', NULL);

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
  `storable` enum('Y','N') NOT NULL DEFAULT 'N',
  `juiceboxManaged` enum('Y','N') NOT NULL DEFAULT 'N',
  `thermalPrinterNum` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `device_group`
--

INSERT INTO `device_group` (`dg_id`, `dg_name`, `dg_parent`, `dg_desc`, `payFirst`, `selectMatsFirst`, `storable`, `juiceboxManaged`, `thermalPrinterNum`) VALUES
(1, '3d', NULL, '(Generic 3D Printer)', 'N', 'Y', 'Y', 'N', 1),
(2, 'poly', 1, 'PolyPrinter', 'N', 'Y', 'Y', 'N', 1),
(3, 'shop', NULL, 'Shop Room', 'N', 'N', 'N', 'Y', 0),
(4, 'laser', NULL, 'Laser Cutter', 'N', 'Y', 'N', 'N', 0),
(5, 'vinyl', NULL, 'Vinyl Cutter', 'N', 'Y', 'N', 'N', 0),
(6, 'e_station', NULL, 'Electronics Station', 'N', 'N', 'N', 'N', 0),
(7, 'uprint', 1, 'Stratus uPrint', 'Y', 'Y', 'Y', 'N', 0),
(8, 'delta', 1, 'Delta 3D Printer', 'N', 'Y', 'Y', 'N', 1),
(9, 'scan', NULL, '3D Scan', 'N', 'N', 'N', 'N', 0),
(10, 'sew', NULL, 'Sewing Station', 'N', 'N', 'N', 'N', 0),
(11, 'embroidery', NULL, 'Embroidery Machines', 'N', 'N', 'N', 'N', 0),
(12, 'mill', NULL, 'CNC Mill', 'N', 'Y', 'N', 'N', 0),
(13, 'vr', NULL, 'VR Equipment', 'N', 'N', 'N', 'N', 0),
(14, 'air_brush', NULL, 'Air Brush Station', 'N', 'N', 'N', 'N', 0),
(15, 'NFPrinter', 1, 'Ninja Flex 3D Printer', 'N', 'Y', 'Y', 'Y', 0),
(16, 'kiln', NULL, 'Electric Kilns', 'N', 'N', 'N', 'N', 0),
(17, 'screen', NULL, 'Silk Screen', 'N', 'Y', 'N', 'N', 0),
(18, 'sandGrind', 3, 'Sanders & Grinders', 'N', 'N', 'N', 'Y', 0),
(19, 'drills', 3, 'Drill Presses', 'N', 'N', 'N', 'Y', 0),
(20, 'linear_Saw', 3, 'Linear Saws', 'N', 'N', 'N', 'Y', 0);

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
(1, 2, 13),
(2, 2, 14),
(3, 2, 15),
(4, 2, 16),
(5, 2, 17),
(6, 2, 18),
(7, 2, 19),
(8, 4, 2),
(9, 4, 5),
(10, 4, 8),
(11, 4, 9),
(12, 4, 10),
(13, 4, 11),
(14, 4, 12),
(15, 5, 20),
(16, 5, 21),
(17, 5, 22),
(18, 5, 23),
(19, 5, 24),
(20, 5, 25),
(21, 5, 26),
(22, 2, 29),
(23, 8, 28),
(24, 11, 52),
(25, 16, 4),
(26, 7, 27),
(27, 7, 32),
(28, 12, 1),
(29, 12, 2),
(30, 12, 11),
(31, 2, 33),
(32, 2, 34),
(33, 2, 35),
(34, 2, 36),
(35, 2, 37),
(36, 2, 38),
(37, 2, 39),
(38, 2, 40),
(39, 2, 41),
(40, 2, 42),
(41, 5, 43),
(42, 5, 44),
(43, 5, 45),
(44, 5, 46),
(45, 5, 48),
(46, 5, 30),
(47, 5, 31),
(48, 4, 53),
(49, 15, 54),
(50, 15, 55),
(51, 15, 56),
(52, 5, 57),
(53, 5, 58),
(54, 2, 59),
(55, 2, 60),
(56, 2, 61),
(57, 2, 62),
(58, 2, 63),
(59, 2, 64),
(60, 5, 65),
(61, 5, 66),
(62, 5, 67),
(63, 15, 69),
(64, 15, 70),
(65, 15, 71),
(66, 17, 68);

-- --------------------------------------------------------

--
-- Table structure for table `error`
--

CREATE TABLE `error` (
  `e_id` int(11) NOT NULL,
  `e_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page` varchar(100) NOT NULL,
  `msg` text NOT NULL,
  `staff_id` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `m_id` int(11) NOT NULL,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(8,4) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
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
(2, '12421415533', '1000000001'),
(3, '20422415733', '1000000002'),
(4, '769918733', '1000000003'),
(5, '10818417333', '1000000004'),
(6, '1728117533', '1000000005'),
(7, '17819818469', '1000000006'),
(8, '2518618469', '1000000007'),
(9, '1212215733', '1000000008'),
(10, '15817918469', '1000000009'),
(11, '132137203252', '1000000010');

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
(4, 'Community Member', 'Non-Student', '10.00'),
(7, 'Service', 'Service technicians that need to work on FabLab Equipment', '0.00'),
(8, 'FabLabian', 'Student Worker', '0.00'),
(9, 'SuperFabLabian', 'Student Worker Supervisor ', '0.00'),
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
(7, 'limit', '180', '(seconds) 3 minutes before auto-logout'),
(8, 'limit_long', '600', '(seconds) 10 minutes before auto-logout'),
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
(21, 'paySite_name', 'CS Gold', '3rd party pay site'),
(22, 'interdepartmental', 'Library Interdepartmental', ''),
(23, 'currency', 'fas fa-dollar-sign', 'Icon as Defined by Font Awesome'),
(24, 'LvlOfStaff', '8', 'First role level ID of staff.'),
(25, 'minRoleTrainer', '10', 'Minimum Role Level of Trainer, below this value you can not issue a training.'),
(26, 'editTrans', '9', ' Role level required to edit a Transaction'),
(27, 'api_key', 'HDVmyqkZB5vsPQGAKwpLtPPQ8Pauy5DMVWsefcBVsbzv9AQnrJFhyAuqBhLCL9r8AFxtDAgjc7Qjf8bdL9eaAXd7VnejU7DHw', 'Temp fix to secure FLUD script'),
(28, 'acct3', '9', 'At what level can a user use the house account.'),
(29, 'acct4', '9', 'At what level can a user use the generic department account.'),
(30, 'dateFormat', 'M d, Y g:i a', 'format the date using Php\'s date() function.'),
(31, 'timezone', 'America/Chicago', 'Set Local Time Zone'),
(32, 'timeInterval', '.25', 'Minimum time unit of an hour.'),
(33, 'editRole', '10', 'Level of Staff Required to edit RoleID'),
(34, 'editRfid', '10', 'Level of Staff Required to edit RFID'),
(35, 'lastRfid', '624918469', 'This is the last RFID that was scanned by the JuiceBox.'),
(36, 'ShareAccts', '10', 'Role level required to Share their Accounts for any purpose');

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


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `u_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `r_id` int(11) NOT NULL,
  `exp_date` datetime DEFAULT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `adj_date` datetime DEFAULT NULL,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `operator`, `r_id`, `exp_date`, `icon`, `adj_date`, `notes`) VALUES
(3, '1000000001', 1, NULL, NULL, NULL, ''),
(4, '1000000002', 2, NULL, 'far fa-hand-peace', NULL, ''),
(5, '1000000003', 3, NULL, NULL, NULL, ''),
(6, '1000000004', 4, NULL, NULL, NULL, ''),
(7, '1000000007', 7, NULL, NULL, NULL, ''),
(8, '1000000008', 8, NULL, 'fas fa-graduation-cap', NULL, ''),
(9, '1000000009', 9, NULL, 'fab fa-grav fa-spin', NULL, ''),
(10, '1000000010', 10, NULL, 'fas fa-university', NULL, '');

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
-- Indexes for table `error`
--
ALTER TABLE `error`
  ADD PRIMARY KEY (`e_id`);

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
  MODIFY `ac_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
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
  MODIFY `ca_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `citation`
--
ALTER TABLE `citation`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;
--
-- AUTO_INCREMENT for table `device_group`
--
ALTER TABLE `device_group`
  MODIFY `dg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `device_materials`
--
ALTER TABLE `device_materials`
  MODIFY `dm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;
--
-- AUTO_INCREMENT for table `error`
--
ALTER TABLE `error`
  MODIFY `e_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `m_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;
--
-- AUTO_INCREMENT for table `mats_used`
--
ALTER TABLE `mats_used`
  MODIFY `mu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `objbox`
--
ALTER TABLE `objbox`
  MODIFY `o_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
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
  MODIFY `rf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `time_clock`
--
ALTER TABLE `time_clock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `trainingmodule`
--
ALTER TABLE `trainingmodule`
  MODIFY `tm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
