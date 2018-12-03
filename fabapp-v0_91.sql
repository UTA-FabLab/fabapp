-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 30, 2018 at 01:56 AM
-- Server version: 5.7.21
-- PHP Version: 5.6.35

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
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
CREATE TABLE IF NOT EXISTS `accounts` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(140) NOT NULL,
  `balance` decimal(6,2) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `role_access` varchar(2) NOT NULL,
  PRIMARY KEY (`a_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`a_id`, `name`, `description`, `balance`, `operator`, `role_access`) VALUES
(1, 'Unpaid', 'Unpaid charge, Blocked until Paid', '0.0', '', '9'),
(2, 'CSGold', 'CSGold Account', '0.0', '', '8');

-- --------------------------------------------------------

--
-- Table structure for table `acct_charge`
--

DROP TABLE IF EXISTS `acct_charge`;
CREATE TABLE IF NOT EXISTS `acct_charge` (
  `ac_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) NOT NULL,
  `trans_id` int(11) NOT NULL,
  `ac_date` datetime NOT NULL,
  `operator` varchar(10) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `amount` decimal(7,2) NOT NULL,
  `recon_date` datetime DEFAULT NULL,
  `recon_id` varchar(10) DEFAULT NULL,
  `ac_notes` text,
  PRIMARY KEY (`ac_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `authrecipients`
--

DROP TABLE IF EXISTS `authrecipients`;
CREATE TABLE IF NOT EXISTS `authrecipients` (
  `ar_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  PRIMARY KEY (`ar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `auth_accts`
--

DROP TABLE IF EXISTS `auth_accts`;
CREATE TABLE IF NOT EXISTS `auth_accts` (
  `aa_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `valid` enum('Y','N') NOT NULL DEFAULT 'Y',
  `aa_date` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  PRIMARY KEY (`aa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `carrier`
--

DROP TABLE IF EXISTS `carrier`;
CREATE TABLE IF NOT EXISTS `carrier` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `email` varchar(110) NOT NULL,
  PRIMARY KEY (`c_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

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
CREATE TABLE IF NOT EXISTS `citation` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(10) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `c_date` datetime NOT NULL,
  `c_notes` text NOT NULL,
  PRIMARY KEY (`c_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE IF NOT EXISTS `devices` (
  `d_id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(4) NOT NULL,
  `public_view` enum('Y','N') NOT NULL DEFAULT 'N',
  `device_desc` varchar(255) NOT NULL,
  `d_duration` time NOT NULL DEFAULT '00:00:00',
  `base_price` decimal(7,5) NOT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  `device_key` varchar(128) NOT NULL,
  `salt_key` varchar(64) NOT NULL,
  `exp_key` datetime DEFAULT NULL,
  PRIMARY KEY (`d_id`),
  KEY `devices_index_device_id` (`device_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `device_group`
--

DROP TABLE IF EXISTS `device_group`;
CREATE TABLE IF NOT EXISTS `device_group` (
  `dg_id` int(11) NOT NULL AUTO_INCREMENT,
  `dg_name` varchar(10) NOT NULL,
  `dg_parent` int(11) DEFAULT NULL,
  `dg_desc` varchar(50) NOT NULL,
  `payFirst` enum('Y','N') NOT NULL DEFAULT 'N',
  `selectMatsFirst` enum('Y','N') NOT NULL DEFAULT 'N',
  `storable` enum('Y','N') NOT NULL DEFAULT 'N',
  `juiceboxManaged` enum('Y','N') NOT NULL DEFAULT 'N',
  `thermalPrinterNum` int(11) NOT NULL,
  `granular_wait` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`dg_id`),
  UNIQUE KEY `dg_name` (`dg_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `device_materials`
--

DROP TABLE IF EXISTS `device_materials`;
CREATE TABLE IF NOT EXISTS `device_materials` (
  `dm_id` int(11) NOT NULL AUTO_INCREMENT,
  `dg_id` int(11) NOT NULL,
  `m_id` int(11) NOT NULL,
  PRIMARY KEY (`dm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `error`
--

DROP TABLE IF EXISTS `error`;
CREATE TABLE IF NOT EXISTS `error` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `e_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page` varchar(100) NOT NULL,
  `msg` text NOT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `interaction`
--

DROP TABLE IF EXISTS `interaction`;
CREATE TABLE IF NOT EXISTS `interaction` (
  `uta_id` varchar(10) NOT NULL,
  `trans_id` int(11) NOT NULL,
  PRIMARY KEY (`uta_id`,`trans_id`),
  KEY `trans_id` (`trans_id`),
  KEY `trans_index_uta_id` (`uta_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
CREATE TABLE IF NOT EXISTS `materials` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(8,4) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `color_hex` varchar(6) DEFAULT NULL,
  `measurable` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`m_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mats_used`
--

DROP TABLE IF EXISTS `mats_used`;
CREATE TABLE IF NOT EXISTS `mats_used` (
  `mu_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) DEFAULT NULL,
  `m_id` int(11) NOT NULL,
  `fil_amt` decimal(6,2) DEFAULT NULL,
  `unit_used` decimal(7,2) DEFAULT NULL,
  `mu_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_id` int(4) NOT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `mu_notes` text,
  `staff_id` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`mu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `objbox`
--

DROP TABLE IF EXISTS `objbox`;
CREATE TABLE IF NOT EXISTS `objbox` (
  `o_id` int(11) NOT NULL AUTO_INCREMENT,
  `o_start` datetime NOT NULL,
  `o_end` datetime DEFAULT NULL,
  `address` varchar(10) DEFAULT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`o_id`),
  KEY `trans_id` (`trans_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `purpose`
--

DROP TABLE IF EXISTS `purpose`;
CREATE TABLE IF NOT EXISTS `purpose` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT,
  `p_title` varchar(100) NOT NULL,
  PRIMARY KEY (`p_id`)
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
CREATE TABLE IF NOT EXISTS `rfid` (
  `rf_id` int(11) NOT NULL AUTO_INCREMENT,
  `rfid_no` varchar(64) NOT NULL,
  `operator` varchar(10) NOT NULL,
  PRIMARY KEY (`rf_id`),
  UNIQUE KEY `rfid_no` (`rfid_no`),
  KEY `rfid_index_operator` (`operator`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `lvl_desc` varchar(255) NOT NULL,
  `r_rate` decimal(9,2) DEFAULT NULL,
  PRIMARY KEY (`r_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

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
(9, 'Lead FabLabian', 'Student Supervisor', '0.00'),
(10, 'Student Service Technician', 'Staff with additioanal duties ', '0.00'),
(11, 'Admin', 'Administration Level of FabLab', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `service_call`
--

DROP TABLE IF EXISTS `service_call`;
CREATE TABLE IF NOT EXISTS `service_call` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(10) NOT NULL,
  `d_id` int(11) NOT NULL,
  `sl_id` int(11) NOT NULL,
  `solved` enum('Y','N') NOT NULL DEFAULT 'N',
  `sc_notes` text NOT NULL,
  `sc_time` datetime NOT NULL,
  PRIMARY KEY (`sc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `service_lvl`
--

DROP TABLE IF EXISTS `service_lvl`;
CREATE TABLE IF NOT EXISTS `service_lvl` (
  `sl_id` int(11) NOT NULL AUTO_INCREMENT,
  `msg` varchar(50) NOT NULL,
  PRIMARY KEY (`sl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

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
CREATE TABLE IF NOT EXISTS `service_reply` (
  `sr_id` int(11) NOT NULL AUTO_INCREMENT,
  `sc_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `sr_notes` text NOT NULL,
  `sr_time` datetime NOT NULL,
  PRIMARY KEY (`sr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `site_variables`
--

DROP TABLE IF EXISTS `site_variables`;
CREATE TABLE IF NOT EXISTS `site_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `value` varchar(100) NOT NULL,
  `notes` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `site_variables`
--

INSERT INTO `site_variables` (`id`, `name`, `value`, `notes`) VALUES
(1, 'uprint_conv', '16.387', 'inches^3 to grams'),
(2, 'minTime', '1', 'Minimum hour charge for a device'),
(3, 'box_number', '6', 'Number of Shelves used for object storage'),
(4, 'letter', '9', 'Number of Rows in each Box'),
(5, 'grace_period', '300', 'Grace period allotted to each Ticket(sec)'),
(6, 'limit', '180', '(seconds) 3 minutes before auto-logout'),
(7, 'limit_long', '600', '(seconds) 10 minutes before auto-logout'),
(8, 'maxHold', '14', '# of Days for Holding Period for 3D prints'),
(9, 'serving', '0', 'Now serving number such and such'),
(10, 'bServing', '0', 'Boss Laser Now serving number'),
(11, 'eServing', '0', 'Epilog Laser Now serving number'),
(12, 'next', '0', 'Last Number Issued for 3D Printing'),
(13, 'bNext', '0', 'Last Number Issued for Boss Laser'),
(14, 'eNext', '0', 'Last Number Issued for Epilog Laser'),
(15, 'forgotten', 'webapps.uta.edu/oit/selfservice/', 'UTA\'s Password Reset'),
(16, 'check_expire', 'N', 'Do we deny users if they have an expired membership. Expected Values (Y,N)'),
(17, 'ip_range_1', '/^129\\.107\\.72\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(18, 'ip_range_2', '/^129\\.107\\.73\\..*/', 'Block certain abilities based upon IP. Follow Regex format.'),
(19, 'inspectPrint', 'Once a print has been picked up & paid for we can not issue a refund.', 'Disclosure for picking up a 3D Print'),
(20, 'site_name', 'FabApp', 'Name of site owner'),
(21, 'paySite', 'https://csgoldweb.uta.edu/admin/quicktran/main.php', '3rd party Pay System. (CsGold)'),
(22, 'paySite_name', 'CS Gold', '3rd party pay site'),
(23, 'currency', 'fas fa-dollar-sign', 'Icon as Defined by Font Awesome'),
(24, 'api_key', 'correct_key', 'Temp fix to secure FLUD script'),
(25, 'dateFormat', 'M d, Y g:i a', 'format the date using Php\'s date() function.'),
(26, 'timezone', 'America/Chicago', 'Set Local Time Zone'),
(27, 'timeInterval', '.25', 'Minimum time unit of an hour.'),
(28, 'LvlOfStaff', '8', 'First role level ID of staff.'),
(29, 'minRoleTrainer', '11', 'Minimum Role Level of Trainer, below this value you can not issue a training.'),
(30, 'editTrans', '9', ' Role level required to edit a Transaction'),
(31, 'editRole', '11', 'Level of Staff Required to edit RoleID'),
(32, 'editRfid', '11', 'Level of Staff Required to edit RFID'),
(33, 'lastRfid', '', 'This is the last RFID that was scanned by the JuiceBox.'),
(34, 'regexUser', '^\\d{10}$', 'regular expression used to verify a user\'s identification number'),
(35, 'regexPayee', '^\\d{9,10}$', 'regular expression used to verify a payee\'s identification number'),
(36, 'rank_period', '3', '# of months the rank is based off of'),
(37, 'misc', 'Misc', 'Miscellaneous Wait-Tab'),
(38, 'mServing', '0', 'Misc now serving'),
(39, 'mNext', '0', 'Misc Next Issuable Number'),
(40, 'backdoor_pass', 'opensaysame', 'General password to be used when the authentication server is not working.'),
(41, 'service_pass', 'service_password', 'Service Technician '),
(42, 'wait_system', 'new', 'toggle between the new system and the old. (any/new)'),
(43, 'editSV', '11', 'Role Level that allows you to edit Site Variables.  Do not set this beyond your highest assignable level. '),
(44, 'clear_queue', '9', 'Minimum Level Required to clear the Wait Queue'),
(45, 'staffTechnican', '10', 'Minimum Staff Level Required to perform Service Replies and override Gatekeeper'),
(46, 'serviceTechnican', '7', 'External Role Level Required to perform Service Replies and override Gatekeeper'),
(47, 'wait_period', '300', 'Waiting period allotted to each Wait Queue Ticket(sec)');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE IF NOT EXISTS `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `msg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

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
(13, 'Misprocessed'),
(14, 'Completed'),
(15, 'Cancelled'),
(20, 'Charge to Accounts');

-- --------------------------------------------------------

--
-- Table structure for table `tm_enroll`
--

DROP TABLE IF EXISTS `tm_enroll`;
CREATE TABLE IF NOT EXISTS `tm_enroll` (
  `tm_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `completed` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `revoked` enum('Y','N') NOT NULL,
  `altered_date` datetime DEFAULT NULL,
  `altered_notes` text,
  `altered_by` varchar(10) DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL,
  PRIMARY KEY (`tm_id`,`operator`),
  KEY `tm_enroll_index_operator` (`operator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `trainingmodule`
--

DROP TABLE IF EXISTS `trainingmodule`;
CREATE TABLE IF NOT EXISTS `trainingmodule` (
  `tm_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `tm_desc` text,
  `duration` time NOT NULL,
  `d_id` int(11) DEFAULT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `tm_required` enum('Y','N') NOT NULL DEFAULT 'N',
  `file_name` varchar(100) DEFAULT NULL,
  `file_bin` mediumblob,
  `class_size` int(11) NOT NULL,
  `tm_stamp` datetime NOT NULL,
  PRIMARY KEY (`tm_id`),
  KEY `device_id` (`d_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `trans_id` int(11) NOT NULL AUTO_INCREMENT,
  `d_id` int(11) NOT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `est_time` time DEFAULT NULL,
  `t_start` datetime NOT NULL,
  `t_end` datetime DEFAULT NULL,
  `duration` time DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `total` decimal(7,2) DEFAULT NULL,
  `p_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`trans_id`),
  KEY `device_id` (`d_id`),
  KEY `transactions_index_uta_id` (`operator`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `u_id` int(11) NOT NULL AUTO_INCREMENT,
  `operator` varchar(10) NOT NULL,
  `r_id` int(11) NOT NULL,
  `exp_date` datetime DEFAULT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `adj_date` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `long_close` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`u_id`),
  UNIQUE KEY `operator` (`operator`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `operator`, `r_id`, `exp_date`, `icon`, `adj_date`, `notes`, `long_close`) VALUES
(1, '1000000001', 1, NULL, NULL, NULL, '', 'N'),
(2, '1000000002', 2, NULL, NULL, NULL, '', 'N'),
(3, '1000000003', 3, NULL, NULL, NULL, '', 'N'),
(4, '1000000004', 4, NULL, NULL, NULL, '', 'N'),
(5, '1000000005', 5, NULL, NULL, NULL, '', 'N'),
(6, '1000000006', 6, NULL, NULL, NULL, '', 'N'),
(7, '1000000007', 7, NULL, NULL, NULL, '', 'N'),
(8, '1000000008', 8, NULL, NULL, NULL, '', 'N'),
(9, '1000000009', 9, NULL, NULL, NULL, '', 'N'),
(10, '1000000010', 10, NULL, NULL, NULL, '', 'N'),
(11, '1000000011', 11, NULL, NULL, NULL, '', 'N');


-- --------------------------------------------------------

--
-- Table structure for table `wait_queue`
--

DROP TABLE IF EXISTS `wait_queue`;
CREATE TABLE IF NOT EXISTS `wait_queue` (
  `Q_id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`Q_id`),
  KEY `Operator` (`Operator`),
  KEY `Dev_id` (`Dev_id`),
  KEY `Devgr_id` (`Devgr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
