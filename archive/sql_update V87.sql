-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: libwebmysqldev.ardclb.uta.edu
-- Generation Time: Aug 09, 2016 at 03:15 PM
-- Server version: 5.1.73
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `fabapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `d_id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(4) NOT NULL,
  `public_view` enum('Y','N') NOT NULL DEFAULT 'N',
  `device_name` varchar(50) NOT NULL,
  `device_desc` varchar(255) NOT NULL,
  `d_duration` time NOT NULL DEFAULT '00:00:00',
  `base_price` decimal(7,5) NOT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `multi_open` enum('N','Y') NOT NULL DEFAULT 'N',
  `url` varchar(50) DEFAULT NULL,
  `device_key` varchar(128) NOT NULL,
  PRIMARY KEY (`d_id`),
  KEY `devices_index_device_id` (`device_id`),
  KEY `device_name` (`device_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=45 ;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` VALUES(1, '0001', 'N', 'bandsaw01', 'Bandsaw', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(2, '0002', 'N', 'benchgrind01', 'Bench Grinder', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(3, '0003', 'N', 'embroidery01', 'Brother P1000', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(4, '0004', 'N', 'plasma01', 'CNC Plasma cutter', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(5, '0005', 'N', 'blender01', 'commercial blender', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(6, '0006', 'N', 'miter01', 'Compound sliding miter saw', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(7, '0007', 'N', 'disc01', 'Disc sander', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(8, '0008', 'N', 'edm01', 'EDM Machine', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(9, '0009', 'Y', 'serger01', 'Janome 990D Serger #1', '00:00:00', 0.00000, 10, 'N', NULL, '');
INSERT INTO `devices` VALUES(10, '0010', 'Y', 'serger02', 'Janome 990D Serger #2', '00:00:00', 0.00000, 10, 'N', NULL, '');
INSERT INTO `devices` VALUES(11, '0011', 'Y', 'sewing01', 'Janome Sewing #1', '00:00:00', 0.00000, 10, 'N', NULL, '');
INSERT INTO `devices` VALUES(12, '0012', 'Y', 'sewing02', 'Janome Sewing #2', '00:00:00', 0.00000, 10, 'N', NULL, '');
INSERT INTO `devices` VALUES(13, '0013', 'N', 'airbrush01', 'Airbrush station', '00:00:00', 0.00000, 14, 'N', NULL, '');
INSERT INTO `devices` VALUES(14, '0014', 'N', 'drillpress01', 'Machinist''s drill press', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(15, '0015', 'N', 'scrollsaw01', 'Scroll saw', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(16, '0016', 'N', 'sherl_mill01', 'Sherline Desktop CNC Mill', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(17, '0017', 'N', 'sherl_lathe01', 'Sherline Desktop CNC Lathe', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(18, '0018', 'N', 'handibot01', 'Shopbot Handi-bot', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(19, '0019', 'N', 'shopbot01', 'Shopbot PRS-Alpha ', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(20, '0020', 'N', 'tablesaw01', 'Sawstop Table Saw', '00:00:00', 0.00000, 3, 'N', NULL, '');
INSERT INTO `devices` VALUES(21, '0021', 'Y', 'Polyprinter01', 'Polyprinter #1', '00:00:00', 0.00000, 2, 'N', 'polyprinter-1.uta.edu', '');
INSERT INTO `devices` VALUES(22, '0022', 'Y', 'Polyprinter02', 'Polyprinter #2', '00:00:00', 0.00000, 2, 'N', 'polyprinter-2.uta.edu', '');
INSERT INTO `devices` VALUES(23, '0023', 'Y', 'Polyprinter03', 'Polyprinter #3', '00:00:00', 0.00000, 2, 'N', 'polyprinter-3.uta.edu', '');
INSERT INTO `devices` VALUES(24, '0024', 'Y', 'Polyprinter04', 'Polyprinter #4', '00:00:00', 0.00000, 2, 'N', 'polyprinter-4.uta.edu', '');
INSERT INTO `devices` VALUES(25, '0025', 'Y', 'Polyprinter05', 'Polyprinter #5', '00:00:00', 0.00000, 2, 'N', 'polyprinter-5.uta.edu', '');
INSERT INTO `devices` VALUES(26, '0026', 'Y', 'Polyprinter06', 'Polyprinter #6', '00:00:00', 0.00000, 2, 'N', 'polyprinter-6.uta.edu', '');
INSERT INTO `devices` VALUES(27, '0027', 'Y', 'Polyprinter07', 'Polyprinter #7', '00:00:00', 0.00000, 2, 'N', 'polyprinter-7.uta.edu', '');
INSERT INTO `devices` VALUES(28, '0028', 'Y', 'Polyprinter08', 'Polyprinter #8', '00:00:00', 0.00000, 2, 'N', 'polyprinter-8.uta.edu', '');
INSERT INTO `devices` VALUES(29, '0029', 'Y', 'Polyprinter09', 'Polyprinter #9', '00:00:00', 0.00000, 2, 'N', 'polyprinter-9.uta.edu', '');
INSERT INTO `devices` VALUES(30, '0030', 'Y', 'Polyprinter10', 'Polyprinter #10', '00:00:00', 0.00000, 2, 'N', 'polyprinter-10.uta.edu', '');
INSERT INTO `devices` VALUES(31, '0031', 'N', 'orion01', 'Orion Delta', '00:00:00', 0.00000, 8, 'N', NULL, '');
INSERT INTO `devices` VALUES(32, '0032', 'N', 'max01', 'Rostock MAX', '00:00:00', 0.00000, 8, 'N', NULL, '');
INSERT INTO `devices` VALUES(33, '0033', 'Y', 'kossel01', 'Kossel Pro ', '00:00:00', 0.00000, 8, 'N', NULL, '');
INSERT INTO `devices` VALUES(34, '0034', 'Y', 'epilog01', 'Epilog Laser', '01:00:00', 0.00000, 4, 'N', NULL, '');
INSERT INTO `devices` VALUES(35, '0035', 'Y', 'boss01', 'Boss Laser', '01:00:00', 0.00000, 4, 'N', NULL, '');
INSERT INTO `devices` VALUES(36, '0036', 'Y', 'cncminimill', 'CNC Mini Mill', '00:00:00', 0.00000, 12, 'N', NULL, '');
INSERT INTO `devices` VALUES(37, '0037', 'Y', '3dscan01', '3D Scanner station', '00:00:00', 0.00000, 9, 'N', NULL, '');
INSERT INTO `devices` VALUES(38, '0038', 'N', 'glassk01', 'Glass kiln', '00:00:00', 0.00000, 11, 'N', NULL, '');
INSERT INTO `devices` VALUES(39, '0039', 'N', 'ceramicsk01', 'Ceramics kiln', '00:00:00', 0.00000, 11, 'N', NULL, '');
INSERT INTO `devices` VALUES(40, '0040', 'N', 'mcor01', 'Mcor paper 3d printer', '00:00:00', 0.00000, NULL, 'N', NULL, '');
INSERT INTO `devices` VALUES(41, '0041', 'Y', 'rolandVinyl', 'Roland Vinyl Cutter', '00:00:00', 0.00000, 5, 'N', NULL, '');
INSERT INTO `devices` VALUES(42, '0042', 'Y', 'electronics_station', 'Electronics Station', '00:00:00', 0.00000, 6, 'Y', NULL, '');
INSERT INTO `devices` VALUES(43, '0043', 'Y', 'uPrint', 'uPrint SEplus', '00:00:00', 0.00000, 7, 'N', NULL, '');
INSERT INTO `devices` VALUES(44, '0044', 'Y', 'oculus', 'Oculus Rift', '00:00:00', 0.00000, 13, 'N', NULL, '');
INSERT INTO `devices` VALUES(45, '0045', 'N', 'aquarium', 'CNC Room', '00:00:00', 0.00000, NULL, 'N', NULL, '');


--
-- Table structure for table `device_group`
--

DROP TABLE IF EXISTS `device_group`;
CREATE TABLE IF NOT EXISTS `device_group` (
  `dg_id` int(11) NOT NULL AUTO_INCREMENT,
  `dg_name` varchar(10) NOT NULL,
  `dg_parent` int(11) DEFAULT NULL,
  PRIMARY KEY (`dg_id`),
  UNIQUE KEY `dg_name` (`dg_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `device_group`
--

INSERT INTO `device_group` (`dg_id`, `dg_name`, `dg_parent`) VALUES
(1, '3d', NULL),
(2, 'poly', 1),
(3, 'shop', NULL),
(4, 'laser', NULL),
(5, 'vinyl', NULL),
(6, 'e_station', NULL),
(7, 'uprint', 1),
(8, 'delta', 1),
(9, 'scan', NULL),
(10, 'sew', NULL),
(11, 'kiln', NULL),
(12, 'mill', NULL),
(13, 'vr', NULL),
(14, 'air_brush', NULL);

--
-- Table structure for table `device_materials`
--

DROP TABLE IF EXISTS `device_materials`;
CREATE TABLE IF NOT EXISTS `device_materials` (
  `dm_id` int(11) NOT NULL AUTO_INCREMENT,
  `dg_id` int(11) NOT NULL,
  `m_id` int(11) NOT NULL,
  PRIMARY KEY (`dm_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=55 ;

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
(54, 5, 30);

--
-- Table structure for table `materials`
--
DROP TABLE IF EXISTS `material`;
DROP TABLE IF EXISTS `materials`;
CREATE TABLE IF NOT EXISTS `materials` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(4,2) DEFAULT NULL,
  `unit` varchar(10) NOT NULL,
  PRIMARY KEY (`m_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=53 ;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`m_id`, `m_name`, `m_parent`, `price`, `unit`) VALUES
(1, 'ABS (Generic)', NULL, '0.05', 'gram(s)'),
(2, 'Acrylic', NULL, '0.00', ''),
(3, 'Cotton', NULL, '0.00', ''),
(4, 'Glass', NULL, '0.00', ''),
(5, 'Leather', NULL, '0.00', ''),
(6, 'PLA', NULL, '0.05', 'gram(s)'),
(7, 'Vinyl', NULL, '0.25', 'inch(es)'),
(8, 'Wood', NULL, '0.00', ''),
(9, 'Basswood', NULL, '0.00', ''),
(10, 'Plywood', NULL, '0.00', ''),
(11, 'MDF', NULL, '0.00', ''),
(12, 'Other', NULL, NULL, ''),
(13, 'ABS Black', 1, '0.05', 'gram(s)'),
(14, 'ABS Blue', 1, '0.05', 'gram(s)'),
(15, 'ABS Green', 1, '0.05', 'gram(s)'),
(16, 'ABS Orange', 1, '0.05', 'gram(s)'),
(17, 'ABS Red', 1, '0.05', 'gram(s)'),
(18, 'ABS Purple', 1, '0.05', 'gram(s)'),
(19, 'ABS Yellow', 1, '0.05', 'gram(s)'),
(20, 'Vinyl Black', 7, '0.25', 'inch(es)'),
(21, 'Vinyl Blue Royal', 7, '0.25', 'inch(es)'),
(22, 'Vinyl Green', 7, '0.25', 'inch(es)'),
(23, 'Vinyl Orange', 7, '0.25', 'inch(es)'),
(24, 'Vinyl Flat Red', 7, '0.25', 'inch(es)'),
(25, 'Vinyl Violet', 7, '0.25', 'inch(es)'),
(26, 'Vinyl Yellow', 7, '0.25', 'inch(es)'),
(27, 'uPrint Material', NULL, '1.00', 'gram(s)'),
(28, 'Learner Supplied Filament', NULL, '0.00', ''),
(29, 'ABS White', 1, '0.05', 'gram(s)'),
(30, 'Vinyl White', 7, '0.25', 'inch(es)'),
(31, 'Transfer Tape', NULL, '0.10', 'inch(es)'),
(32, 'uPrint Support', NULL, '0.00', 'gram(s)'),
(33, 'ABS Bronze', 1, '0.05', 'gram(s)'),
(44, 'Vinyl Glossy Red', 7, '0.25', 'inch(es)'),
(35, 'ABS Pink', 1, '0.05', 'gram(s)'),
(36, 'ABS Mint', 1, '0.05', 'gram(s)'),
(37, 'ABS Glow in the dark', 1, '0.05', 'gram(s)'),
(38, 'ABS Trans Orange', 1, '0.05', 'gram(s)'),
(39, 'ABS Trans Red', 1, '0.05', 'gram(s)'),
(40, 'ABS Transparent ', 1, '0.05', 'gram(s)'),
(41, 'ABS Neon Green', 1, '0.05', 'gram(s)'),
(42, 'ABS Gold', 1, '0.05', 'gram(s)'),
(43, 'Vinyl Blue Sky', 7, '0.25', 'inch(es)'),
(45, 'Vinyl Pink', 7, '0.25', 'inch(es)'),
(46, 'Vinyl Turquoise', 7, '0.25', 'inch(es)'),
(48, 'Vinyl Silver', 7, '0.25', 'inch(es)'),
(49, 'uPrint Bed New', NULL, '0.00', ''),
(50, 'uPrint Bed Partly_Used', NULL, '0.00', ''),
(51, 'Delrin Sheet', NULL, '0.00', ''),
(52, 'Thread', NULL, '1.00', 'hour(s)');
--
-- Table structure for table `mats_used`
--

DROP TABLE IF EXISTS `mats_used`;
CREATE TABLE IF NOT EXISTS `mats_used` (
  `mu_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) DEFAULT NULL,
  `m_id` int(11) NOT NULL,
  `fil_amt` decimal(6,2) DEFAULT NULL,
  `unit_used` decimal(6,2) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_id` int(4) NOT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `notes` varchar(140) DEFAULT NULL,
  PRIMARY KEY (`mu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=34 ;

--
-- Dumping data for table `mats_used`
--

INSERT INTO `mats_used` (`mu_id`, `trans_id`, `m_id`, `fil_amt`, `unit_used`, `status_id`, `operator`, `notes`) VALUES
(1, NULL, 13, NULL, '9541.00', 8, '1001169856', 'Initial Entry'),
(2, NULL, 14, NULL, '4131.00', 8, '1001169856', 'Initial Entry'),
(3, NULL, 33, NULL, '651.00', 8, '1001169856', 'Initial Entry'),
(4, NULL, 37, NULL, '4471.00', 8, '1001169856', 'Initial Entry'),
(5, NULL, 42, NULL, '403.00', 8, '1001169856', 'Initial Entry'),
(6, NULL, 15, NULL, '149.00', 8, '1001169856', 'Initial Entry'),
(7, NULL, 36, NULL, '3324.00', 8, '1001169856', 'Initial Entry'),
(8, NULL, 41, NULL, '1459.00', 8, '1001169856', 'Initial Entry'),
(9, NULL, 16, NULL, '3257.00', 8, '1001169856', 'Initial Entry'),
(10, NULL, 35, NULL, '787.00', 8, '1001169856', 'Initial Entry'),
(11, NULL, 18, NULL, '2706.00', 8, '1001169856', 'Initial Entry'),
(12, NULL, 17, NULL, '3299.00', 8, '1001169856', 'Initial Entry'),
(13, NULL, 38, NULL, '5000.00', 8, '1001169856', 'Initial Entry'),
(14, NULL, 40, NULL, '59.00', 8, '1001169856', 'Initial Entry'),
(15, NULL, 29, NULL, '7191.00', 8, '1001169856', 'Initial Entry'),
(16, NULL, 19, NULL, '1337.00', 8, '1001169856', 'Initial Entry'),
(17, NULL, 31, NULL, '1544.00', 8, '1001169856', 'Initial Entry'),
(18, NULL, 49, NULL, '51.00', 8, '1001169856', 'Initial Entry'),
(19, NULL, 50, NULL, '15.00', 8, '1001169856', 'Initial Entry'),
(20, NULL, 27, NULL, '4540.00', 8, '1001169856', 'Initial Entry'),
(21, NULL, 32, NULL, '3280.00', 8, '1001169856', 'Initial Entry'),
(22, NULL, 20, NULL, '1328.00', 8, '1001169856', 'Initial Entry'),
(23, NULL, 21, NULL, '951.00', 8, '1001169856', 'Initial Entry'),
(24, NULL, 44, NULL, '1434.00', 8, '1001169856', 'Initial Entry'),
(25, NULL, 22, NULL, '1389.00', 8, '1001169856', 'Initial Entry'),
(26, NULL, 23, NULL, '815.00', 8, '1001169856', 'Initial Entry'),
(27, NULL, 45, NULL, '1608.00', 8, '1001169856', 'Initial Entry'),
(28, NULL, 24, NULL, '1162.00', 8, '1001169856', 'Initial Entry'),
(29, NULL, 48, NULL, '320.00', 8, '1001169856', 'Initial Entry'),
(30, NULL, 43, NULL, '343.00', 8, '1001169856', 'Initial Entry'),
(31, NULL, 46, NULL, '1313.00', 8, '1001169856', 'Initial Entry'),
(32, NULL, 30, NULL, '1496.00', 8, '1001169856', 'Initial Entry'),
(33, NULL, 26, NULL, '1481.00', 8, '1001169856', 'Initial Entry');


--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE IF NOT EXISTS `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `msg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `msg`) VALUES
(1, 'Denied'),
(2, 'Training Required'),
(3, 'Membership Expired'),
(8, 'Mats Received '),
(9, 'Mats Removed'),
(10, 'Powered On'),
(11, 'Moveable'),
(12, 'Failed'),
(13, 'Missprocessed'),
(14, 'Completed'),
(20, 'CS Gold'),
(21, 'FabLab Account'),
(22, 'Library Account');

--
-- Table structure for table `site_variables`
--

DROP TABLE IF EXISTS `site_variables`;
CREATE TABLE IF NOT EXISTS `site_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `value` varchar(20) NOT NULL,
  `notes` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `site_variables`
--

INSERT INTO `site_variables` (`id`, `name`, `value`, `notes`) VALUES
(1, 'uprint_conv', '16.387', 'inches^3 to grams'),
(2, 'minTime', '1', 'Minimum hour charge for a device'),
(3, 'LaserMax', '1', 'Maximum Time Limit for Laser Cutter'),
(4, 'box_number', '5', 'Number of Box used for object storage'),
(5, 'letter', '8', 'Number of Rows in each Box'),
(6, 'grace_period', '300', 'Grace period allotted to each Ticket(sec)'),
(7, 'limit', '300', '(seconds) 5 minutes before auto-logout'),
(8, 'limit_long', '6000', '(seconds) 100 minutes before auto-logout'),
(9, 'maxHold', '336:00:00', '2 Week Holding Period for 3D prints');

