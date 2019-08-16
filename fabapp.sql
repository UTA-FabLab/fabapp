-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Aug 16, 2019 at 11:22 AM
-- Server version: 5.7.27-0ubuntu0.16.04.1-log
-- PHP Version: 5.6.40-5+ubuntu16.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fabapp`
--
CREATE DATABASE IF NOT EXISTS `fabapp` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `fabapp`;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `a_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(140) NOT NULL,
  `balance` decimal(9,2) DEFAULT NULL,
  `operator` varchar(10) NOT NULL,
  `role_access` varchar(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `acct_charge`
--

DROP TABLE IF EXISTS `acct_charge`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `authrecipients`
--

DROP TABLE IF EXISTS `authrecipients`;
CREATE TABLE `authrecipients` (
  `ar_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `aa_date` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `carrier`
--

DROP TABLE IF EXISTS `carrier`;
CREATE TABLE `carrier` (
  `c_id` int(11) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `email` varchar(110) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `carrier`
--

INSERT INTO `carrier` (`c_id`, `provider`, `email`) VALUES
(1, 'AT&T', 'number@txt.att.net'),
(2, 'Verizon', 'number@vtext.com'),
(3, 'T-Mobile', 'number@tmomail.net'),
(4, 'Sprint', 'number@messaging.sprintpcs.com'),
(5, 'Virgin Mobile', 'number@vmobl.com'),
(6, 'Project Fi', 'number@msg.fi.google.com');

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
  `c_date` datetime NOT NULL,
  `c_notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `time_limit` time DEFAULT NULL,
  `base_price` decimal(7,5) NOT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`d_id`, `device_id`, `public_view`, `device_desc`, `time_limit`, `base_price`, `dg_id`, `url`) VALUES
(1, '0001', 'Y', 'Bandsaw', '00:00:00', '0.00000', 20, NULL),
(2, '0002', 'Y', 'Bench Grinder', '00:00:00', '0.00000', 18, NULL),
(3, '0003', 'Y', 'Brother Embroider PR-655', '00:00:00', '1.00000', 11, NULL),
(4, '0004', 'Y', 'Plasma Cutter', '00:00:00', '0.00000', 3, NULL),
(6, '0006', 'Y', 'Compound Miter Saw', '00:00:00', '0.00000', 3, NULL),
(7, '0007', 'Y', 'Disc/Belt Sander Grizzly', '00:00:00', '0.00000', 18, NULL),
(9, '0009', 'Y', 'Janome Serger #1', '00:00:00', '1.00000', 10, NULL),
(10, '0010', 'Y', 'Janome Serger #2', '00:00:00', '1.00000', 10, NULL),
(11, '0011', 'Y', 'Janome Sewing #1', '00:00:00', '1.00000', 10, NULL),
(12, '0012', 'Y', 'Janome Sewing #2', '00:00:00', '1.00000', 10, NULL),
(67, '0066', 'Y', 'Heat Press', '00:00:00', '0.00000', 24, NULL),
(14, '0014', 'Y', 'Drill Press Powermatic', '00:00:00', '0.00000', 19, NULL),
(15, '0015', 'Y', 'Scroll Saw', '00:00:00', '0.00000', 20, NULL),
(16, '0016', 'Y', 'Sherline CNC Mill', '00:00:00', '0.00000', 3, NULL),
(17, '0017', 'Y', 'Sherline CNC Lathe', '00:00:00', '0.00000', 3, NULL),
(18, '0018', 'Y', 'Shopbot Handi-bot', '00:00:00', '0.00000', 3, NULL),
(19, '0019', 'Y', 'Shopbot PRS-Alpha ', '00:00:00', '0.00000', 3, NULL),
(20, '0020', 'Y', 'Sawstop Table Saw', '00:00:00', '0.00000', 3, NULL),
(21, '0021', 'Y', 'Polyprinter #1', '00:00:00', '0.00000', 2, 'polyprinter-1.uta.edu'),
(22, '0022', 'Y', 'Polyprinter #2', '00:00:00', '0.00000', 2, 'polyprinter-2.uta.edu'),
(23, '0023', 'Y', 'Polyprinter #3', '00:00:00', '0.00000', 2, 'polyprinter-3.uta.edu'),
(24, '0024', 'Y', 'Polyprinter #4', '00:00:00', '0.00000', 2, 'polyprinter-4.uta.edu'),
(25, '0025', 'Y', 'Polyprinter #5', '00:00:00', '0.00000', 2, 'polyprinter-5.uta.edu'),
(26, '0026', 'Y', 'Polyprinter #6', '00:00:00', '0.00000', 2, 'polyprinter-6.uta.edu'),
(27, '0027', 'Y', 'Polyprinter #7', '00:00:00', '0.00000', 2, 'polyprinter-7.uta.edu'),
(28, '0028', 'Y', 'Polyprinter #8', '00:00:00', '0.00000', 2, 'polyprinter-8.uta.edu'),
(29, '0029', 'Y', 'Polyprinter #9', '00:00:00', '0.00000', 2, 'polyprinter-9.uta.edu'),
(30, '0030', 'Y', 'Polyprinter #10', '00:00:00', '0.00000', 15, 'polyprinter-10.uta.edu'),
(31, '0031', 'Y', 'PrintrBot Play', '00:00:00', '0.00000', 8, 'printrbotplay.uta.edu'),
(32, '0032', 'Y', 'Prusa #1', '00:00:00', '0.00000', 8, 'prusa1.uta.edu'),
(33, '0033', 'Y', 'PrintrBot Simple Metal', '00:00:00', '0.00000', 8, 'printrbotsimplemetal.uta.edu'),
(34, '0034', 'Y', 'Epilog Laser', '01:00:00', '0.00000', 4, NULL),
(35, '0035', 'Y', 'Boss Laser', '01:00:00', '0.00000', 4, NULL),
(36, '0036', 'Y', 'Roland CNC Mill', '00:00:00', '0.00000', 12, NULL),
(37, '0037', 'Y', '3D Scanner Station', '00:00:00', '0.00000', 9, NULL),
(38, '0038', 'Y', 'Kiln Glass', '00:00:00', '0.00000', 16, NULL),
(39, '0039', 'Y', 'Kiln Ceramics', '00:00:00', '0.00000', 16, NULL),
(46, '0046', 'Y', 'Kiln Mix Use', '00:00:00', '0.00000', 16, NULL),
(41, '0041', 'Y', 'Roland Vinyl Cutter', '00:00:00', '0.00000', 5, NULL),
(42, '0042', 'Y', 'Electronics Station', '00:00:00', '0.00000', 6, NULL),
(43, '0043', 'Y', 'uPrint SEplus', '00:00:00', '0.00000', 7, NULL),
(44, '0044', 'Y', 'Oculus Rift', '00:00:00', '0.00000', 13, NULL),
(45, '0045', 'Y', 'Screeny McScreen Press', '00:00:00', '0.00000', 17, NULL),
(66, '', 'N', 'FabApp', '00:00:00', '0.00000', 23, NULL),
(48, '0048', 'Y', 'Drill Press Ryobi', '00:00:00', '0.00000', 19, NULL),
(49, '0049', 'Y', 'SandBlaster', '00:00:00', '0.00000', 3, NULL),
(50, '0050', 'Y', 'Jigsaw', '00:00:00', '0.00000', 20, NULL),
(51, '0051', 'Y', 'Brother SE-400 #1', '00:00:00', '1.00000', 11, NULL),
(52, '0052', 'Y', 'Brother SE-400 #2', '00:00:00', '1.00000', 11, NULL),
(53, '0053', 'Y', 'Silhouette Cameo Cutter', '00:00:00', '0.00000', 5, NULL),
(5, '0005', 'Y', 'Paper Making', '00:00:00', '0.00000', 21, NULL),
(54, '0054', 'Y', 'Prusa #2', '00:00:00', '0.00000', 8, 'prusa2.uta.edu'),
(55, '0055', 'Y', 'ProtoMat PCB Mill', '00:00:00', '0.00000', 22, NULL),
(56, '0056', 'Y', 'Solder Iron #1', '00:00:00', '0.00000', 6, NULL),
(57, '0057', 'Y', 'Solder Iron #2', '00:00:00', '0.00000', 6, NULL),
(58, '0058', 'Y', 'Solder Iron #3', '00:00:00', '0.00000', 6, NULL),
(59, '0059', 'Y', 'Solder Iron #4', '00:00:00', '0.00000', 6, NULL),
(60, '0060', 'Y', 'Solder Iron #5', '00:00:00', '0.00000', 6, NULL),
(61, '0061', 'Y', 'Solder Iron #6', '00:00:00', '0.00000', 6, NULL),
(62, '0062', 'Y', 'Solder Iron #7', '00:00:00', '0.00000', 6, NULL),
(63, '0063', 'Y', 'Solder Iron #8', '00:00:00', '0.00000', 6, NULL),
(64, '0064', 'Y', 'Solder Iron #9', '00:00:00', '0.00000', 6, NULL),
(65, '0065', 'Y', 'Solder Iron #10', '00:00:00', '0.00000', 6, NULL),
(68, '0068', 'N', 'Sheet Goods', '00:00:00', '0.00000', 23, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `device_group`
--

DROP TABLE IF EXISTS `device_group`;
CREATE TABLE `device_group` (
  `dg_id` int(11) NOT NULL,
  `dg_name` varchar(10) NOT NULL,
  `dg_parent` int(11) DEFAULT NULL,
  `dg_desc` varchar(50) NOT NULL,
  `payFirst` enum('Y','N') NOT NULL DEFAULT 'N',
  `selectMatsFirst` enum('Y','N') NOT NULL DEFAULT 'N',
  `storable` enum('Y','N') NOT NULL DEFAULT 'N',
  `juiceboxManaged` enum('Y','N') NOT NULL DEFAULT 'N',
  `thermalPrinterNum` int(11) NOT NULL,
  `granular_wait` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `device_group`
--

INSERT INTO `device_group` (`dg_id`, `dg_name`, `dg_parent`, `dg_desc`, `payFirst`, `selectMatsFirst`, `storable`, `juiceboxManaged`, `thermalPrinterNum`, `granular_wait`) VALUES
(1, '3d', NULL, '(Generic 3D Printer)', 'N', 'Y', 'Y', 'N', 0, 'Y'),
(2, 'poly', 1, 'PolyPrinter', 'N', 'Y', 'Y', 'N', 0, 'N'),
(3, 'shop', NULL, 'Shop Room', 'N', 'N', 'N', 'Y', 0, 'Y'),
(4, 'laser', NULL, 'Laser Cutter', 'N', 'Y', 'N', 'N', 0, 'Y'),
(5, 'vinyl', NULL, 'Vinyl Cutter', 'N', 'Y', 'N', 'N', 0, 'Y'),
(6, 'e_station', NULL, 'Electronics Station', 'N', 'N', 'N', 'N', 0, 'Y'),
(7, 'uprint', 1, 'Stratus uPrint', 'Y', 'Y', 'Y', 'N', 0, 'Y'),
(8, 'pla', 1, 'PLA 3D Printers', 'N', 'Y', 'Y', 'N', 0, 'Y'),
(9, 'scan', NULL, '3D Scan', 'N', 'N', 'N', 'N', 0, 'Y'),
(10, 'sew', NULL, 'Sewing Station', 'N', 'Y', 'N', 'N', 0, 'Y'),
(11, 'embroidery', NULL, 'Embroidery Machines', 'N', 'Y', 'N', 'N', 0, 'Y'),
(12, 'mill', NULL, 'CNC Mill', 'N', 'Y', 'N', 'N', 0, 'Y'),
(13, 'vr', NULL, 'VR Equipment', 'N', 'N', 'N', 'N', 0, 'Y'),
(15, 'NFPrinter', 1, 'Ninja Flex 3D Printer', 'N', 'Y', 'Y', 'Y', 0, 'Y'),
(16, 'kiln', NULL, 'Electric Kilns', 'N', 'N', 'N', 'N', 0, 'Y'),
(17, 'screen', NULL, 'Silk Screen', 'N', 'Y', 'N', 'N', 0, 'Y'),
(18, 'sandGrind', 3, 'Sanders & Grinders', 'N', 'N', 'N', 'Y', 0, 'Y'),
(19, 'drills', 3, 'Drill Presses', 'N', 'N', 'N', 'Y', 0, 'Y'),
(20, 'linear_Saw', 3, 'Linear Saws', 'N', 'N', 'N', 'Y', 0, 'Y'),
(21, 'paperMaker', NULL, 'Paper Making', 'N', 'N', 'N', 'N', 0, 'Y'),
(22, 'pcbMill', NULL, 'PCB Mill', 'N', 'Y', 'N', 'N', 0, 'Y'),
(23, 'software', NULL, 'Applications & Software', 'N', 'N', 'N', 'N', 0, 'N'),
(24, 'heatPress', NULL, 'Heat Press Group', 'N', 'N', 'N', 'N', 0, 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `device_materials`
--

DROP TABLE IF EXISTS `device_materials`;
CREATE TABLE `device_materials` (
  `dm_id` int(11) NOT NULL,
  `dg_id` int(11) NOT NULL,
  `m_id` int(11) NOT NULL,
  `required` enum('N','Y') DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `device_materials`
--

INSERT INTO `device_materials` (`dm_id`, `dg_id`, `m_id`, `required`) VALUES
(1, 8, 6, 'N'),
(2, 2, 13, 'N'),
(3, 2, 14, 'N'),
(4, 2, 15, 'N'),
(6, 2, 16, 'N'),
(7, 2, 17, 'N'),
(8, 2, 18, 'N'),
(9, 2, 19, 'N'),
(10, 4, 2, 'N'),
(11, 4, 5, 'N'),
(12, 4, 8, 'N'),
(13, 4, 9, 'N'),
(14, 4, 10, 'N'),
(16, 4, 12, 'N'),
(18, 5, 20, 'N'),
(19, 5, 21, 'N'),
(20, 5, 22, 'N'),
(21, 5, 23, 'N'),
(22, 5, 24, 'N'),
(23, 5, 25, 'N'),
(24, 5, 26, 'N'),
(25, 2, 29, 'N'),
(26, 8, 28, 'N'),
(27, 10, 52, 'Y'),
(28, 11, 52, 'Y'),
(29, 7, 27, 'Y'),
(30, 7, 32, 'Y'),
(34, 12, 1, 'N'),
(35, 12, 2, 'N'),
(36, 12, 11, 'N'),
(39, 2, 33, 'N'),
(40, 2, 34, 'N'),
(41, 2, 35, 'N'),
(42, 2, 36, 'N'),
(43, 2, 37, 'N'),
(44, 2, 38, 'N'),
(45, 2, 39, 'N'),
(46, 2, 40, 'N'),
(47, 2, 41, 'N'),
(48, 2, 42, 'N'),
(49, 5, 43, 'N'),
(50, 5, 44, 'N'),
(51, 5, 45, 'N'),
(52, 5, 46, 'N'),
(53, 5, 48, 'N'),
(54, 5, 30, 'N'),
(55, 5, 31, 'N'),
(56, 4, 53, 'N'),
(58, 15, 55, 'N'),
(59, 15, 56, 'N'),
(60, 5, 57, 'N'),
(61, 5, 58, 'N'),
(62, 2, 59, 'N'),
(63, 2, 60, 'N'),
(64, 2, 61, 'N'),
(65, 2, 62, 'N'),
(66, 2, 63, 'N'),
(67, 2, 64, 'N'),
(68, 5, 65, 'N'),
(69, 5, 66, 'N'),
(70, 5, 67, 'N'),
(72, 15, 69, 'N'),
(73, 15, 70, 'N'),
(74, 15, 71, 'N'),
(75, 2, 72, 'N'),
(76, 2, 73, 'N'),
(77, 2, 74, 'N'),
(78, 17, 75, 'N'),
(79, 17, 76, 'N'),
(80, 17, 77, 'N'),
(81, 17, 78, 'N'),
(82, 17, 79, 'N'),
(83, 5, 80, 'N'),
(84, 8, 81, 'N'),
(85, 8, 82, 'N'),
(86, 8, 83, 'N'),
(87, 8, 84, 'N'),
(88, 8, 85, 'N'),
(89, 8, 86, 'N'),
(90, 8, 87, 'N'),
(91, 8, 88, 'N'),
(92, 8, 89, 'N'),
(93, 8, 90, 'N'),
(94, 8, 91, 'N'),
(95, 8, 92, 'N'),
(96, 8, 93, 'N'),
(97, 8, 94, 'N'),
(98, 8, 95, 'N'),
(99, 8, 96, 'N'),
(100, 8, 97, 'N'),
(101, 8, 98, 'N'),
(102, 8, 99, 'N'),
(103, 5, 100, 'N'),
(104, 5, 101, 'N'),
(105, 5, 102, 'N'),
(106, 5, 103, 'N'),
(107, 5, 104, 'N'),
(108, 5, 105, 'N'),
(109, 5, 106, 'N'),
(110, 5, 107, 'N'),
(111, 5, 108, 'N'),
(112, 5, 109, 'N'),
(113, 5, 110, 'N'),
(114, 5, 111, 'N'),
(115, 5, 112, 'N'),
(116, 5, 113, 'N'),
(117, 5, 114, 'N'),
(118, 5, 115, 'N'),
(119, 5, 116, 'N'),
(120, 5, 117, 'N'),
(121, 5, 118, 'N'),
(122, 5, 119, 'N'),
(123, 5, 120, 'N'),
(124, 22, 121, 'N'),
(125, 8, 122, 'N');

-- --------------------------------------------------------

--
-- Table structure for table `error`
--

DROP TABLE IF EXISTS `error`;
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

DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `m_id` int(11) NOT NULL,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(8,4) DEFAULT NULL,
  `product_number` varchar(30) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `color_hex` varchar(6) DEFAULT NULL,
  `measurable` enum('Y','N') NOT NULL DEFAULT 'N',
  `current` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`m_id`, `m_name`, `m_parent`, `price`, `product_number`, `unit`, `color_hex`, `measurable`, `current`) VALUES
(1, 'ABS (Generic)', NULL, '0.0000', NULL, 'gram(s)', NULL, 'N', 'Y'),
(2, 'Acrylic', 123, '0.0200', NULL, 'sq_inch(es)', NULL, 'N', 'Y'),
(3, 'Cotton', NULL, '0.0000', NULL, '', NULL, 'N', 'Y'),
(4, 'Glass (Generic)', 123, '0.0200', NULL, 'sq_inch(es)', NULL, 'N', 'Y'),
(5, 'Leather', NULL, '0.0000', NULL, '', NULL, 'N', 'Y'),
(6, 'FabLab PLA', NULL, '0.0500', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(7, 'Vinyl (Generic)', NULL, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(8, 'Wood', 123, '0.0200', NULL, 'sq_inch(es)', NULL, 'N', 'Y'),
(9, 'Basswood', 123, '0.0200', NULL, 'sq_inch(es)', NULL, 'N', 'Y'),
(10, 'Plywood', 123, '0.0200', NULL, 'sq_inch(es)', NULL, 'N', 'Y'),
(11, 'MDF', NULL, '0.0000', NULL, '', NULL, 'N', 'Y'),
(12, 'Other', NULL, NULL, NULL, '', NULL, 'N', 'Y'),
(13, 'ABS Black', 1, '0.0500', '3D ABS-1KG1.75-BLK', 'gram(s)', '000000', 'Y', 'Y'),
(14, 'ABS Blue', 1, '0.0500', NULL, 'gram(s)', '0047BB', 'Y', 'Y'),
(15, 'ABS Green', 1, '0.0500', NULL, 'gram(s)', '00BF6F', 'Y', 'Y'),
(16, 'ABS Orange', 1, '0.0500', NULL, 'gram(s)', 'fe5000', 'Y', 'Y'),
(17, 'ABS Red', 1, '0.0500', NULL, 'gram(s)', 'D22630', 'Y', 'Y'),
(18, 'ABS Purple', 1, '0.0500', NULL, 'gram(s)', '440099', 'Y', 'Y'),
(19, 'ABS Yellow', 1, '0.0500', NULL, 'gram(s)', 'FFE900', 'Y', 'Y'),
(20, 'Vinyl Black', 7, '0.2500', NULL, 'inch(es)', '000000', 'Y', 'Y'),
(21, 'Vinyl Sapphire Gloss', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(22, 'Vinyl Green', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(23, 'Vinyl Orange', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(24, 'Vinyl Cherry Red Matte', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(25, 'Vinyl Plum', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(26, 'Vinyl Yellow', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(27, 'uPrint Material', NULL, '8.1900', NULL, 'inch<sup>3</sup>', 'fdffe2', 'Y', 'Y'),
(28, 'BYO Hatchbox', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(29, 'ABS White', 1, '0.0500', NULL, 'gram(s)', 'ffffff', 'Y', 'Y'),
(30, 'Vinyl White', 7, '0.2500', NULL, 'inch(es)', 'ffffff', 'Y', 'Y'),
(31, 'Transfer Tape', NULL, '0.1000', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(32, 'uPrint Support', NULL, '8.1900', NULL, 'inch<sup>3</sup>', NULL, 'Y', 'Y'),
(33, 'ABS Bronze', 1, '0.0500', NULL, 'gram(s)', 'A09200', 'Y', 'Y'),
(35, 'ABS Pink', 1, '0.0500', NULL, 'gram(s)', 'FF3EB5', 'Y', 'Y'),
(36, 'ABS Mint', 1, '0.0500', NULL, 'gram(s)', '88DBDF', 'Y', 'Y'),
(37, 'ABS Glow in the dark', 1, '0.0500', NULL, 'gram(s)', 'D0DEBB', 'Y', 'Y'),
(38, 'ABS Trans Orange', 1, '0.0500', NULL, 'gram(s)', 'FCC89B', 'Y', 'Y'),
(39, 'ABS Trans Red', 1, '0.0500', NULL, 'gram(s)', 'DF4661', 'Y', 'Y'),
(40, 'ABS Trans White', 1, '0.0500', NULL, 'gram(s)', 'D9D9D6', 'Y', 'Y'),
(41, 'ABS Trans Green', 1, '0.0500', NULL, 'gram(s)', 'A0DAB3', 'Y', 'Y'),
(42, 'ABS Gold', 1, '0.0500', NULL, 'gram(s)', 'CFB500', 'Y', 'Y'),
(43, 'Vinyl Ocean Blue', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(44, 'Vinyl Red Gloss', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(45, 'Vinyl Pink', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(46, 'Vinyl Teal Gloss', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(48, 'Vinyl Silver', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(49, 'uPrint Bed New', NULL, '0.0000', NULL, '', NULL, 'N', 'Y'),
(50, 'uPrint Bed Partly_Used', NULL, '0.0000', NULL, '', NULL, 'N', 'Y'),
(51, 'Delrin Sheet', NULL, '0.0000', NULL, '', NULL, 'N', 'Y'),
(52, 'Thread', NULL, '1.0000', NULL, 'hour(s)', NULL, 'Y', 'Y'),
(53, 'Paper-stock (chipboard)', NULL, NULL, NULL, '', NULL, 'N', 'Y'),
(54, 'NinjaFlex (Generic)', NULL, '0.1500', NULL, 'gram(s)', NULL, 'N', 'Y'),
(55, 'NinjaFlex Black', 54, '0.1500', NULL, 'gram(s)', '000000', 'Y', 'Y'),
(56, 'NinjaFlex White', 54, '0.1500', NULL, 'gram(s)', 'ffffff', 'Y', 'Y'),
(57, 'Vinyl Coral', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(58, 'Vinyl *Scraps', 7, '0.0000', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(59, 'ABS Lime', 1, '0.0500', NULL, 'gram(s)', 'c2e189', 'Y', 'Y'),
(60, 'ABS Copper', 1, '0.0500', NULL, 'gram(s)', '7C4D3A', 'Y', 'Y'),
(61, 'ABS Silver', 1, '0.0500', NULL, 'gram(s)', '9EA2A2', 'Y', 'Y'),
(62, 'ABS Trans Black', 1, '0.0500', NULL, 'gram(s)', '919D9D', 'Y', 'Y'),
(63, 'ABS Trans Blue', 1, '0.0500', NULL, 'gram(s)', 'C8D8EB', 'Y', 'Y'),
(64, 'ABS Trans Yellow', 1, '0.0500', NULL, 'gram(s)', 'FFFF74', 'Y', 'Y'),
(65, 'Vinyl Mint', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(66, 'Vinyl Lime Green', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(67, 'Vinyl Gold', 7, '0.2500', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(68, 'Screen Ink(Generic)', NULL, '0.0500', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(69, 'NinjaFlex Water', 54, '0.1500', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(70, 'NinjaFlex Lava', 54, '0.1500', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(71, 'NinjaFlex Sapphire', 54, '0.1500', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(72, 'ABS Neon Green', 1, '0.0500', NULL, 'gram(s)', '77ff35', 'Y', 'Y'),
(73, 'ABS Brown', 1, '0.0500', NULL, 'gram(s)', '721c00', 'Y', 'Y'),
(74, 'ABS Beige', 1, '0.0500', NULL, 'gram(s)', 'f7f799', 'Y', 'N'),
(75, 'Comet White', 68, '0.0500', NULL, 'gram(s)', 'ffffff', 'Y', 'Y'),
(76, 'Pitch Black', 68, '0.0500', NULL, 'gram(s)', '000000', 'Y', 'Y'),
(77, 'Neptune Blue', 68, '0.0500', NULL, 'gram(s)', '0011ff', 'Y', 'Y'),
(78, 'Mars Red', 68, '0.0500', NULL, 'gram(s)', 'ff0000', 'Y', 'Y'),
(79, 'Starburst Yellow', 68, '0.0500', NULL, 'gram(s)', 'faff00', 'Y', 'Y'),
(80, 'BYO Mats', NULL, '0.0000', NULL, 'inch(es)', NULL, 'Y', 'Y'),
(81, 'BYO 3D Prima', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(82, 'BYO 3DFuel', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(83, 'BYO 3rDment', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(84, 'BYO Alchement', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(85, 'BYO ColorFabb', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(86, 'BYO Faberdashery', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(87, 'BYO Fenner Drives/Ninja', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(88, 'BYO Filamentum', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(89, 'BYO FormFutura', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(90, 'BYO GizmoDorks', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(91, 'BYO 3D FilaPrint', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(92, 'BYO IC3D', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(93, 'BYO Inland Plastics', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(94, 'BYO Lulzbot', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(95, 'BYO MakerBot', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(96, 'BYO MatterHackers', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(97, 'BYO PolyMaker', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(98, 'BYO Proto-Pasta', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(99, 'BYO Taulmann', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(100, 'Vinyl Sky Blue', 7, '0.2500', NULL, 'inch(es)', '7cc8ff', 'Y', 'Y'),
(101, 'HT Hibiscous', 7, '0.3000', NULL, 'inch(es)', 'f44265', 'Y', 'Y'),
(102, 'HT Bright Red', 7, '0.3000', NULL, 'inch(es)', 'ff0000', 'Y', 'Y'),
(103, 'HT Orange', 7, '0.3000', NULL, 'inch(es)', 'ffa500', 'Y', 'Y'),
(104, 'HT Red', 7, '0.3000', NULL, 'inch(es)', 'ea0b0b', 'Y', 'Y'),
(105, 'HT Lemon Yellow', 7, '0.3000', NULL, 'inch(es)', 'fffa00', 'Y', 'Y'),
(106, 'HT Texas Orange', 7, '0.3000', NULL, 'inch(es)', 'ff9102', 'Y', 'Y'),
(107, 'HT Yellow', 7, '0.3000', NULL, 'inch(es)', 'ffc700', 'Y', 'Y'),
(108, 'HT Sun Yellow', 7, '0.3000', NULL, 'inch(es)', 'ffb600', 'Y', 'Y'),
(109, 'HT Lime', 7, '0.3000', NULL, 'inch(es)', '6bff02', 'Y', 'Y'),
(110, 'HT Green Apple', 7, '0.3000', NULL, 'inch(es)', '5de000', 'Y', 'Y'),
(111, 'HT Green', 7, '0.3000', NULL, 'inch(es)', '45a800', 'Y', 'Y'),
(112, 'HT Dark Green', 7, '0.3000', NULL, 'inch(es)', '327a00', 'Y', 'Y'),
(113, 'HT Sky Blue', 7, '0.3000', NULL, 'inch(es)', '0090ff', 'Y', 'Y'),
(114, 'HT Royal Blue', 7, '0.3000', NULL, 'inch(es)', '0036ad', 'Y', 'Y'),
(115, 'HT Lilac', 7, '0.3000', NULL, 'inch(es)', 'f27fff', 'Y', 'Y'),
(116, 'HT Violet', 7, '0.3000', NULL, 'inch(es)', '6b0077', 'Y', 'Y'),
(117, 'HT Black', 7, '0.3000', NULL, 'inch(es)', '000000', 'Y', 'Y'),
(118, 'HT White', 7, '0.3000', NULL, 'inch(es)', 'ffffff', 'Y', 'Y'),
(119, 'HT Silver', 7, '0.3000', NULL, 'inch(es)', 'adadad', 'Y', 'Y'),
(120, 'HT Gold', 7, '0.3000', NULL, 'inch(es)', 'c6b900', 'Y', 'Y'),
(121, 'BYO Copper Clad Board', NULL, '0.2500', NULL, 'in<sup>2</sup>', NULL, 'Y', 'Y'),
(122, 'FabLab-Approved BYO PLA', NULL, '0.0000', NULL, 'gram(s)', NULL, 'Y', 'Y'),
(123, 'Sheet Goods', NULL, NULL, NULL, 'inch(es)', NULL, 'N', 'N'),
(124, 'Clear Glass', 4, '0.0200', NULL, 'sq_inch(es)', NULL, 'Y', 'Y'),
(125, 'Red Glass', 4, '0.0200', NULL, 'sq_inch(es)', 'ff0a00', 'Y', 'Y'),
(126, 'Blue Glass', 4, '0.0200', NULL, 'sq_inch(es)', '0008ff', 'Y', 'Y'),
(127, 'Pink Glass', 4, '0.0200', NULL, 'sq_inch(es)', 'ff00fa', 'Y', 'Y'),
(128, 'Dark Basswood', 9, '0.0200', NULL, 'sq_inch(es)', '49320d', 'Y', 'Y'),
(129, 'Light Basswood', 9, '0.0200', NULL, 'sq_inch(es)', '8c6500', 'Y', 'Y'),
(130, 'Oak Wood', 8, '0.0200', NULL, 'sq_inch(es)', '805300', 'Y', 'Y'),
(131, 'Cherry Wood', 8, '0.0200', NULL, 'sq_inch(es)', 'ff9f94', 'Y', 'Y'),
(132, 'Birch Wood', 8, '0.0200', NULL, 'sq_inch(es)', 'efe0a7', 'Y', 'Y'),
(133, 'Softwood Plywood', 10, '0.0200', NULL, 'sq_inch(es)', 'f1ecbc', 'Y', 'Y'),
(134, 'Hardwood Plywood', 10, '0.0200', NULL, 'sq_inch(es)', 'cdb677', 'Y', 'Y'),
(135, 'Black Glass', 4, '0.0200', NULL, 'sq_inch(es)', '000000', 'Y', 'Y'),
(136, 'Clear Acrylic', 2, '0.0200', NULL, 'sq_inch(es)', NULL, 'Y', 'Y'),
(137, 'Purple Glass', 4, '0.0200', NULL, 'sq_inch(es)', 'b95aff', 'Y', 'Y'),
(138, 'Sheet Test', 123, '0.0200', NULL, 'sq_inch(es)', NULL, 'Y', 'Y'),
(139, 'Brown Sheet', 138, '0.0200', NULL, 'sq_inch(es)', '804040', 'Y', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `mats_used`
--

DROP TABLE IF EXISTS `mats_used`;
CREATE TABLE `mats_used` (
  `mu_id` int(11) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `m_id` int(11) NOT NULL,
  `quantity` decimal(7,2) DEFAULT NULL,
  `status_id` int(4) NOT NULL,
  `staff_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `objbox`
--

DROP TABLE IF EXISTS `objbox`;
CREATE TABLE `objbox` (
  `o_id` int(11) NOT NULL,
  `o_start` datetime NOT NULL,
  `o_end` datetime DEFAULT NULL,
  `address` varchar(10) DEFAULT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `purpose`
--

DROP TABLE IF EXISTS `purpose`;
CREATE TABLE `purpose` (
  `p_id` int(11) NOT NULL,
  `p_title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
-- Table structure for table `rfid`
--

DROP TABLE IF EXISTS `rfid`;
CREATE TABLE `rfid` (
  `rf_id` int(11) NOT NULL,
  `rfid_no` varchar(64) NOT NULL,
  `operator` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `r_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `lvl_desc` varchar(255) NOT NULL,
  `r_rate` decimal(9,2) DEFAULT NULL,
  `variable` varchar(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`r_id`, `title`, `lvl_desc`, `r_rate`, `variable`) VALUES
(1, 'Visitor', 'Non-member lvl', '0.00', 'visitor'),
(2, 'Learner', 'Student Level Membership', '0.00', 'learner'),
(3, 'Learner-RFID', 'Learner\'s with RFID access', '2.00', 'learner_rfid'),
(4, 'Community Member', 'Non-Student, 4 Month Membership', '10.00', 'community'),
(7, 'Service', 'Service technicians that need to work on FabLab Equipment', '0.00', 'service'),
(8, 'FabLabian', 'Student Worker', '0.00', 'staff'),
(9, 'Lead FabLabian', 'Student Supervisor', '0.00', 'lead'),
(10, 'Admin', 'Staff with additioanal duties ', '0.00', 'admin'),
(11, 'Super', 'Administration Level of FabLab', '0.00', 'super');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `service_lvl`
--

DROP TABLE IF EXISTS `service_lvl`;
CREATE TABLE `service_lvl` (
  `sl_id` int(11) NOT NULL,
  `msg` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
-- Table structure for table `service_reply`
--

DROP TABLE IF EXISTS `service_reply`;
CREATE TABLE `service_reply` (
  `sr_id` int(11) NOT NULL,
  `sc_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `sr_notes` text NOT NULL,
  `sr_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sheet_good_inventory`
--

DROP TABLE IF EXISTS `sheet_good_inventory`;
CREATE TABLE `sheet_good_inventory` (
  `inv_id` int(11) NOT NULL,
  `m_id` int(11) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `width` decimal(11,2) DEFAULT NULL,
  `height` decimal(11,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sheet_good_transactions`
--

DROP TABLE IF EXISTS `sheet_good_transactions`;
CREATE TABLE `sheet_good_transactions` (
  `sg_trans_ID` int(11) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `inv_id` int(11) DEFAULT NULL,
  `quantity` int(10) NOT NULL,
  `remove_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
(3, 'box_number', '11', 'Number of Shelves used for object storage'),
(4, 'letter', '15', 'Number of Rows in each Box'),
(5, 'grace_period', '300', 'Grace period allotted to each Ticket(sec)'),
(6, 'limit', '180', '(seconds) 3 minutes before auto-logout'),
(7, 'limit_long', '800', '(seconds) 10 minutes before auto-logout'),
(8, 'maxHold', '14', '# of Days for Holding Period for 3D prints'),
(9, 'serving', '0', 'Now serving number such and such'),
(10, 'bServing', '0', 'Boss Laser Now serving number'),
(11, 'eServing', '0', 'Epilog Laser Now serving number'),
(12, 'next', '0', 'Last Number Issued for 3D Printing'),
(13, 'bNext', '0', 'Last Number Issued for Boss Laser'),
(14, 'eNext', '0', 'Last Number Issued for Epilog Laser'),
(15, 'forgotten', 'webapps.uta.edu/oit/selfservice/', 'UTA\'s Password Reset'),
(16, 'check_expire', 'N', 'Do we deny users if they have an expired membership. Expected Values (Y,N)'),
(17, 'ip_range_1', '/^129\\.107\\.\\d{2,}\\.\\d{2,}/', 'Block certain abilities based upon IP. Follow Regex format.'),
(18, 'ip_range_2', '/^129\\.107\\.73\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(19, 'inspectPrint', 'Once a print has been picked up & paid for we can not issue a refund.', 'Disclosure for picking up a 3D Print'),
(20, 'site_name', 'FabApp', 'Name of site owner'),
(21, 'paySite', 'https://csgoldweb.uta.edu/admin/quicktran/main.php', '3rd party Pay System. (CsGold)'),
(22, 'paySite_name', 'CS Gold', '3rd party pay site'),
(23, 'currency', 'fas fa-dollar-sign', 'Icon as Defined by Font Awesome'),
(24, 'api_key', 'HDVmyqkZB5vsPQGAKwpLtPPQ8Pauy5DMVWsefcBVsbzv9AQnrJFhyAuqBhLCL9r8AFxtDAgjc7Qjf8bdL9eaAXd7VnejU7DHw', 'Temp fix to secure FLUD script'),
(25, 'dateFormat', 'M d, Y g:i a', 'format the date using Php\'s date() function.'),
(26, 'timezone', 'America/Chicago', 'Set Local Time Zone'),
(27, 'timeInterval', '.25', 'Minimum time unit of an hour.'),
(28, 'LvlOfStaff', '8', 'First role level ID of staff.'),
(29, 'minRoleTrainer', '11', 'Minimum Role Level of Trainer, below this value you can not issue a training.'),
(30, 'editTrans', '9', ' Role level required to edit a Transaction'),
(31, 'editRole', '11', 'Level of Staff Required to edit RoleID'),
(32, 'editRfid', '11', 'Level of Staff Required to edit RFID'),
(33, 'lastRfid', '382094490', 'This is the last RFID that was scanned by the JuiceBox.'),
(34, 'regexUser', '^\\d{10}$', 'regular expression used to verify a user\'s identification number'),
(35, 'regexPayee', '^\\d{9,10}$', 'regular expression used to verify a payee\'s identification number'),
(36, 'rank_period', '3', '# of months the rank is based off of'),
(37, 'misc', 'Vinyl ', 'Miscellaneous Wait-Tab'),
(38, 'mServing', '0', 'Misc now serving'),
(39, 'mNext', '0', 'Misc Next Issuable Number'),
(40, 'backdoor_pass', 'cI94eg0cXZWL', 'General password to be used when the authentication server is not working.'),
(41, 'service', 'pearce229', 'Service Technician '),
(42, 'wait_system', 'new', 'toggle between the new system and the old. (any/new)'),
(43, 'editSV', '11', 'Role Level that allows you to edit Site Variables.  Do not set this beyond your highest assignable level. '),
(44, 'clear_queue', '9', 'Minimum Level Required to clear the Wait Queue'),
(45, 'staffTechnican', '10', 'Minimum Staff Level Required to perform Service Replies and override Gatekeeper'),
(46, 'serviceTechnican', '7', 'External Role Level Required to perform Service Replies and override Gatekeeper'),
(47, 'wait_period', '300', 'Waiting period allotted to each Wait Queue Ticket(sec)'),
(48, 'LvlOfLead', '9', 'Role of Lead for inventory editing'),
(49, 'sheet_goods_parent', '123', 'sheet good parent material id'),
(50, 'sheet_device', '68', ''),
(51, 'website_url', 'http://www.fablab.uta.edu', 'Website for FabLab organization'),
(52, 'phone_number', '(817) 272-1785', 'FabLab helpline'),
(53, 'strg_drwr_indicator', 'numer', 'numer for a numeric drawer label, alpha for an alphabetical drawer label'),
(54, 'icon', 'fa-icon.png', 'FabApp icon'),
(55, 'icon2', 'fablab2.png', 'FabApp icon');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `status_id` int(11) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `variable` varchar(25) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `message`, `variable`) VALUES
(1, NULL, NULL),
(2, 'Failed Material', 'failed_mat'),
(3, 'Removed', 'removed'),
(4, 'Used', 'used'),
(5, 'Unused', 'unused'),
(6, 'Updated', 'updated'),
(7, 'Received', 'received'),
(8, NULL, NULL),
(9, NULL, NULL),
(10, NULL, NULL),
(11, 'Active', 'active'),
(12, 'Offline', 'offline'),
(13, 'Moveable', 'moveable'),
(14, 'Total Fail', 'total_fail'),
(15, 'Partial', 'partial_fail'),
(16, 'Cancelled', 'cancelled'),
(17, 'Complete', 'complete'),
(18, 'Stored', 'stored'),
(19, NULL, NULL),
(20, NULL, NULL),
(21, 'Charge to Account', 'charge_to_acct'),
(22, 'Charge to FabLab', 'charge_to_fablab'),
(23, 'Charge to Library', 'charge_to_library'),
(24, 'Unprocessed Sheet Ticket', 'sheet_sale');

-- --------------------------------------------------------

--
-- Table structure for table `storage_box`
--

DROP TABLE IF EXISTS `storage_box`;
CREATE TABLE `storage_box` (
  `drawer` varchar(3) NOT NULL DEFAULT '1',
  `unit` varchar(3) NOT NULL DEFAULT 'A',
  `drawer_size` varchar(7) DEFAULT '5-3',
  `start` varchar(7) NOT NULL DEFAULT '1-1',
  `span` varchar(7) NOT NULL DEFAULT '1-1',
  `trans_id` int(11) DEFAULT NULL,
  `item_change_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff_id` varchar(10) DEFAULT NULL,
  `type` varchar(15) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `table_descriptions`
--

DROP TABLE IF EXISTS `table_descriptions`;
CREATE TABLE `table_descriptions` (
  `t_d_id` int(11) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `label` varchar(50) NOT NULL,
  `description` varchar(140) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `table_descriptions`
--

INSERT INTO `table_descriptions` (`t_d_id`, `table_name`, `label`, `description`) VALUES
(1, 'accounts', 'Account Charge Types', 'List out the types of account charges'),
(2, 'acct_charge', 'Charges to Accounts', 'List of the transactions made'),
(3, 'authrecipients', 'Item Pickup Recipients', 'List of people who are allowed to pick up an item based on ticket'),
(4, 'auth_accts', 'Authorized Accounts', 'Unknown, Empty Table'),
(5, 'carrier', 'Phone Carriers', 'List of carriers and associated data'),
(6, 'citation', 'Citiations', 'Unknown, Empty Table'),
(7, 'devices', 'Devices', 'List of devices'),
(8, 'device_group', 'Device Groups', 'Device group names and desc. Device groups are referenced by devices'),
(9, 'device_materials', 'Device Materials', 'Connect device groups and materials'),
(10, 'error', 'Errors', 'List of errors'),
(11, 'interaction', 'Interaction', 'Unknown, Empty Table'),
(12, 'materials', 'Materials', 'List of specific materials and descriptions'),
(13, 'mats_used', 'Material Qty Tracking', 'All changes in quantity for materials'),
(14, 'objbox', 'Stored Items', 'List of items created by learners that have not yet been picked up'),
(15, 'purpose', 'Reasons for Creation', 'Set list of reasons for creating an item'),
(16, 'rfid', 'RFID Numbers', 'List of accepted RFID'),
(17, 'role', 'Privelege Role Levels', 'List of available roles'),
(18, 'service_call', 'Current Service Issue', 'Current service Issue'),
(19, 'service_lvl', 'Service Type', 'Reason for Service'),
(20, 'service_reply', 'Service Reply', 'Explanation of work done for service issue'),
(21, 'site_variables', 'Site Variables', 'Current variables used for creation of site'),
(22, 'status', 'Material Transaction Status', 'Explanation of material trasnaction'),
(23, 'table_descriptions', 'Table Descriptions', 'Table that displays this information'),
(24, 'tm_enroll', 'Certified Trainings', 'List of certified trainings'),
(25, 'trainingmodule', 'Trainings', 'List of possible trainings'),
(26, 'transactions', 'FabLab Transactions', 'List of all transactions of the FabLab'),
(27, 'users', 'Users', 'List of everyone signed into FabApp'),
(28, 'wait_queue', 'Wait Queue', 'List of people waiting for a device');

-- --------------------------------------------------------

--
-- Table structure for table `tm_enroll`
--

DROP TABLE IF EXISTS `tm_enroll`;
CREATE TABLE `tm_enroll` (
  `tme_key` int(11) NOT NULL,
  `tm_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `completed` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `current` enum('Y','N') NOT NULL,
  `altered_date` datetime DEFAULT NULL,
  `altered_notes` text,
  `altered_by` varchar(10) DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `tm_required` enum('Y','N') NOT NULL DEFAULT 'N',
  `file_name` varchar(100) DEFAULT NULL,
  `file_bin` mediumblob,
  `class_size` int(11) NOT NULL,
  `tm_stamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `trainingmodule`
--

INSERT INTO `trainingmodule` (`tm_id`, `title`, `tm_desc`, `duration`, `d_id`, `dg_id`, `tm_required`, `file_name`, `file_bin`, `class_size`, `tm_stamp`) VALUES
(22, 'Plasma Cutter Training', 'Learn how to design and cut 2D objects out of sheet metal by using the CNC plasma cutter! Successful completion of the Shop Safety Orientation training and a signed liability waiver on file are required in order to participate in this training.', '02:00:00', 4, NULL, 'Y', NULL, NULL, 5, '2018-01-22 14:57:12'),
(23, 'General Shoproom Training', 'Get an overview of all the Shop Room equipment and learn how to operate safety in this space! This training is required and a signed liability waiver must be on file before learners will be permitted to participate in any of the equipment specific trainings.', '01:00:00', NULL, 3, 'Y', NULL, NULL, 5, '2017-10-23 17:11:25'),
(24, 'Sanding and Grinding Training', 'trained on random orbital sander, both combo sanders, bench grinder', '02:00:00', NULL, 18, 'Y', NULL, NULL, 2, '2017-10-25 19:44:33'),
(25, 'ShopBot Training part 1', 'ShopBot basics, safety, and fundamental commands', '02:00:00', 19, NULL, 'Y', NULL, NULL, 3, '2017-10-30 20:11:58'),
(26, 'Drills and Drill Press Training', 'Learn how to use a hammer drill, hand drill, and our drill presses to create cylindrical holes in your workpiece.  Successful completion of the Shop Safety Orientation training and a signed liability waiver on file are required in order to participate in this training.', '02:00:00', NULL, 19, 'Y', NULL, NULL, 5, '2017-11-01 18:09:06'),
(27, 'ShopBot Part 2', 'Demonstration', '02:00:00', 19, NULL, 'Y', NULL, NULL, 3, '2017-11-06 20:19:03'),
(28, 'Compound Mitre Saw Training', 'introduction to the compound mitre saw', '02:00:00', 6, NULL, 'Y', NULL, NULL, 4, '2017-11-08 19:43:09'),
(29, 'SawStop safety training', 'Learn how to safely handle wood on a table saw to create cross cuts and rip cuts. Participants will also learn how to use a fence and an outfeed table. Successful completion of the Shop Safety Orientation training and a signed liability waiver on file are required in order to participate in this training.', '02:00:00', 20, NULL, 'Y', NULL, NULL, 4, '2018-01-13 13:54:49'),
(30, 'Jigsaw, ScrollSaw, & Bandsaw Training', 'Learn the distinction between a jig, band, and scroll saw and what type of work is most appropriate for each machine.  Participants will practice cutting scrolls (non-straight lines). Successful completion of the Shop Safety Orientation training and a signed liability waiver on file are required in order to participate in this training.', '02:00:00', NULL, 20, 'Y', NULL, NULL, 5, '2018-02-05 15:43:40'),
(32, 'Media Blaster', 'Basic operational training - air line safety, mitigating clogs, practice time\r\n(does not include blast media change out training)', '00:30:00', 49, NULL, 'Y', NULL, NULL, 5, '2018-02-05 15:43:25'),
(33, 'Sherline demo course', 'This should be deleted before we do any actual trainings', '01:30:00', 17, NULL, 'Y', NULL, NULL, 5, '2019-02-18 10:23:04');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `trans_id` int(11) NOT NULL,
  `d_id` int(11) NOT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `est_time` time DEFAULT NULL,
  `t_start` datetime NOT NULL,
  `t_end` datetime DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `p_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  `notes` text,
  `pickup_time` datetime DEFAULT NULL,
  `pickedup_by` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  `icon` varchar(40) DEFAULT NULL,
  `adj_date` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `long_close` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wait_queue`
--

DROP TABLE IF EXISTS `wait_queue`;
CREATE TABLE `wait_queue` (
  `Q_id` int(11) NOT NULL,
  `Operator` char(10) DEFAULT NULL,
  `Dev_id` int(11) DEFAULT NULL,
  `Devgr_id` int(11) DEFAULT NULL,
  `Start_date` datetime DEFAULT NULL,
  `estTime` time DEFAULT NULL,
  `End_date` datetime DEFAULT NULL,
  `last_contact` datetime DEFAULT NULL,
  `valid` enum('Y','N') DEFAULT 'Y',
  `Op_email` varchar(100) DEFAULT NULL,
  `Op_phone` char(10) DEFAULT NULL,
  `carrier` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  ADD PRIMARY KEY (`ar_id`);

--
-- Indexes for table `auth_accts`
--
ALTER TABLE `auth_accts`
  ADD PRIMARY KEY (`aa_id`);

--
-- Indexes for table `carrier`
--
ALTER TABLE `carrier`
  ADD PRIMARY KEY (`c_id`);

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
-- Indexes for table `service_reply`
--
ALTER TABLE `service_reply`
  ADD PRIMARY KEY (`sr_id`);

--
-- Indexes for table `sheet_good_inventory`
--
ALTER TABLE `sheet_good_inventory`
  ADD PRIMARY KEY (`inv_id`);

--
-- Indexes for table `sheet_good_transactions`
--
ALTER TABLE `sheet_good_transactions`
  ADD PRIMARY KEY (`sg_trans_ID`);

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
-- Indexes for table `storage_box`
--
ALTER TABLE `storage_box`
  ADD PRIMARY KEY (`drawer`,`unit`);

--
-- Indexes for table `table_descriptions`
--
ALTER TABLE `table_descriptions`
  ADD PRIMARY KEY (`t_d_id`);

--
-- Indexes for table `tm_enroll`
--
ALTER TABLE `tm_enroll`
  ADD PRIMARY KEY (`tme_key`),
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
  ADD KEY `transactions_index_uta_id` (`operator`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `operator` (`operator`);

--
-- Indexes for table `wait_queue`
--
ALTER TABLE `wait_queue`
  ADD PRIMARY KEY (`Q_id`),
  ADD KEY `Operator` (`Operator`),
  ADD KEY `Dev_id` (`Dev_id`),
  ADD KEY `Devgr_id` (`Devgr_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `a_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `acct_charge`
--
ALTER TABLE `acct_charge`
  MODIFY `ac_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8897;
--
-- AUTO_INCREMENT for table `authrecipients`
--
ALTER TABLE `authrecipients`
  MODIFY `ar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
--
-- AUTO_INCREMENT for table `auth_accts`
--
ALTER TABLE `auth_accts`
  MODIFY `aa_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `carrier`
--
ALTER TABLE `carrier`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `citation`
--
ALTER TABLE `citation`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;
--
-- AUTO_INCREMENT for table `device_group`
--
ALTER TABLE `device_group`
  MODIFY `dg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `device_materials`
--
ALTER TABLE `device_materials`
  MODIFY `dm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;
--
-- AUTO_INCREMENT for table `error`
--
ALTER TABLE `error`
  MODIFY `e_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;
--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `m_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;
--
-- AUTO_INCREMENT for table `mats_used`
--
ALTER TABLE `mats_used`
  MODIFY `mu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32003;
--
-- AUTO_INCREMENT for table `objbox`
--
ALTER TABLE `objbox`
  MODIFY `o_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13485;
--
-- AUTO_INCREMENT for table `rfid`
--
ALTER TABLE `rfid`
  MODIFY `rf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;
--
-- AUTO_INCREMENT for table `service_call`
--
ALTER TABLE `service_call`
  MODIFY `sc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;
--
-- AUTO_INCREMENT for table `service_lvl`
--
ALTER TABLE `service_lvl`
  MODIFY `sl_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `service_reply`
--
ALTER TABLE `service_reply`
  MODIFY `sr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;
--
-- AUTO_INCREMENT for table `sheet_good_transactions`
--
ALTER TABLE `sheet_good_transactions`
  MODIFY `sg_trans_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
--
-- AUTO_INCREMENT for table `site_variables`
--
ALTER TABLE `site_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;
--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `table_descriptions`
--
ALTER TABLE `table_descriptions`
  MODIFY `t_d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT for table `tm_enroll`
--
ALTER TABLE `tm_enroll`
  MODIFY `tme_key` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=658;
--
-- AUTO_INCREMENT for table `trainingmodule`
--
ALTER TABLE `trainingmodule`
  MODIFY `tm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `trans_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35286;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;
--
-- AUTO_INCREMENT for table `wait_queue`
--
ALTER TABLE `wait_queue`
  MODIFY `Q_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1564;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
