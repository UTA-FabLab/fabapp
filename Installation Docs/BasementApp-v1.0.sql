-- phpMyAdmin SQL Dump
-- version 4.6.6deb5ubuntu0.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 21, 2023 at 03:30 PM
-- Server version: 5.7.41-0ubuntu0.18.04.1-log
-- PHP Version: 7.2.34-8+ubuntu16.04.1+deb.sury.org+1

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thebasement`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE IF NOT EXISTS `accounts` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(140) NOT NULL,
  `balance` decimal(9,2) DEFAULT NULL,
  `operator` varchar(10) NOT NULL,
  `role_access` varchar(2) NOT NULL,
  PRIMARY KEY (`a_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `accounts`:
--

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`a_id`, `name`, `description`, `balance`, `operator`, `role_access`) VALUES
(1, 'Unpaid', 'Unpaid charge, Blocked until Paid', '0.00', '0', '9'),
(2, 'CSGold', 'CSGold Account', '0.00', '0', '11'),
(3, 'TheBasement', 'The Basement\'s in-House Charge Account', '1.02', '0', '7'),
(4, 'Library', 'General Library Account', '0.00', '0', '11'),
(5, 'IDT', 'Inter-Departmental Transfers', '0.00', '0', '11'),
(6, 'Bursar', 'Office of Student Accounts', '0.00', '0', '11');

-- --------------------------------------------------------

--
-- Table structure for table `acct_charge`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `acct_charge`:
--

-- --------------------------------------------------------

--
-- Table structure for table `authrecipients`
--

CREATE TABLE IF NOT EXISTS `authrecipients` (
  `ar_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  PRIMARY KEY (`ar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `authrecipients`:
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_accts`
--

CREATE TABLE IF NOT EXISTS `auth_accts` (
  `aa_id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `valid` enum('Y','N') NOT NULL DEFAULT 'Y',
  `aa_date` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  PRIMARY KEY (`aa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `auth_accts`:
--

-- --------------------------------------------------------

--
-- Table structure for table `carrier`
--

CREATE TABLE IF NOT EXISTS `carrier` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `email` varchar(110) NOT NULL,
  PRIMARY KEY (`c_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `carrier`:
--

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

CREATE TABLE IF NOT EXISTS `citation` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(10) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `c_date` datetime NOT NULL,
  `c_notes` text NOT NULL,
  PRIMARY KEY (`c_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `citation`:
--

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE IF NOT EXISTS `devices` (
  `d_id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(4) NOT NULL,
  `public_view` enum('Y','N') NOT NULL DEFAULT 'N',
  `device_desc` varchar(255) NOT NULL,
  `time_limit` time DEFAULT NULL,
  `base_price` decimal(7,5) NOT NULL,
  `dg_id` int(11) DEFAULT NULL,
  `url` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`d_id`),
  KEY `devices_index_device_id` (`device_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `devices`:
--


-- --------------------------------------------------------

--
-- Table structure for table `device_group`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `device_group`:
--

-- --------------------------------------------------------

--
-- Table structure for table `device_materials`
--

CREATE TABLE IF NOT EXISTS `device_materials` (
  `dm_id` int(11) NOT NULL AUTO_INCREMENT,
  `dg_id` int(11) NOT NULL,
  `m_id` int(11) NOT NULL,
  `required` enum('N','Y') DEFAULT 'N',
  PRIMARY KEY (`dm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `device_materials`:
--

-- --------------------------------------------------------

--
-- Table structure for table `error`
--

CREATE TABLE IF NOT EXISTS `error` (
  `e_id` int(11) NOT NULL AUTO_INCREMENT,
  `e_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page` varchar(100) NOT NULL,
  `msg` text NOT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `error`:
--

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE IF NOT EXISTS `materials` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_name` varchar(50) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `price` decimal(8,4) DEFAULT NULL,
  `product_number` varchar(30) DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `color_hex` varchar(6) DEFAULT NULL,
  `measurable` enum('Y','N') NOT NULL DEFAULT 'N',
  `current` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`m_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `materials`:
--

-- --------------------------------------------------------

--
-- Table structure for table `mats_used`
--

CREATE TABLE IF NOT EXISTS `mats_used` (
  `mu_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) DEFAULT NULL,
  `m_id` int(11) NOT NULL,
  `quantity` decimal(7,2) DEFAULT NULL,
  `status_id` int(4) NOT NULL,
  `mu_notes` text,
  `staff_id` varchar(10) DEFAULT NULL,
  `mu_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `mats_used`:
--

-- --------------------------------------------------------

--
-- Table structure for table `objbox`
--

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

--
-- RELATIONS FOR TABLE `objbox`:
--

-- --------------------------------------------------------

--
-- Table structure for table `offline_transactions`
--

CREATE TABLE IF NOT EXISTS `offline_transactions` (
  `trans_id` int(11) NOT NULL,
  `off_trans_id` varchar(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- RELATIONS FOR TABLE `offline_transactions`:
--

-- --------------------------------------------------------

--
-- Table structure for table `purpose`
--

CREATE TABLE IF NOT EXISTS `purpose` (
  `p_id` int(11) NOT NULL,
  `p_title` varchar(100) NOT NULL,
  PRIMARY KEY (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `purpose`:
--

--
-- Dumping data for table `purpose`
--

INSERT INTO `purpose` (`p_id`, `p_title`) VALUES
(1, 'Recreation'),
(2, 'Development R&D'),
(3, 'Tournament');

-- --------------------------------------------------------

--
-- Table structure for table `rfid`
--

CREATE TABLE IF NOT EXISTS `rfid` (
  `rf_id` int(11) NOT NULL AUTO_INCREMENT,
  `rfid_no` varchar(64) NOT NULL,
  `operator` varchar(10) NOT NULL,
  PRIMARY KEY (`rf_id`),
  UNIQUE KEY `rfid_no` (`rfid_no`),
  KEY `rfid_index_operator` (`operator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `rfid`:
--

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE IF NOT EXISTS `role` (
  `r_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `lvl_desc` varchar(255) NOT NULL,
  `r_rate` decimal(9,2) DEFAULT NULL,
  `variable` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`r_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `role`:
--

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`r_id`, `title`, `lvl_desc`, `r_rate`, `variable`) VALUES
(1, 'Visitor', 'Non-member lvl', '0.00', 'visitor'),
(2, 'Learner', 'Student Level Membership', '0.00', 'learner'),
(3, 'Learner-RFID', 'Learner\'s with RFID access', '2.00', 'learner_rfid'),
(4, 'Community Member', 'Non-Student, 4 Month Membership', '10.00', 'community'),
(7, 'Service', 'Service technicians that need to work on Basement Equipment', '0.00', 'service'),
(8, 'Basement Dweller', 'Student Worker', '0.00', 'staff'),
(9, 'Lead Basement Dweller', 'Student Supervisor', '0.00', 'lead'),
(10, 'Admin', 'Staff with additioanal duties ', '0.00', 'admin'),
(11, 'Super', 'Administration Level of FabLab', '0.00', 'super');

-- --------------------------------------------------------

--
-- Table structure for table `service_call`
--

CREATE TABLE IF NOT EXISTS `service_call` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(10) NOT NULL,
  `d_id` int(11) NOT NULL,
  `sl_id` int(11) NOT NULL,
  `solved` enum('Y','N') NOT NULL DEFAULT 'N',
  `sc_notes` text NOT NULL,
  `sc_time` datetime NOT NULL,
  PRIMARY KEY (`sc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `service_call`:
--

-- --------------------------------------------------------

--
-- Table structure for table `service_lvl`
--

CREATE TABLE IF NOT EXISTS `service_lvl` (
  `sl_id` int(11) NOT NULL AUTO_INCREMENT,
  `msg` varchar(50) NOT NULL,
  PRIMARY KEY (`sl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `service_lvl`:
--

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

CREATE TABLE IF NOT EXISTS `service_reply` (
  `sr_id` int(11) NOT NULL AUTO_INCREMENT,
  `sc_id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `sr_notes` text NOT NULL,
  `sr_time` datetime NOT NULL,
  PRIMARY KEY (`sr_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `service_reply`:
--

-- --------------------------------------------------------

--
-- Table structure for table `sheet_good_inventory`
--

CREATE TABLE IF NOT EXISTS `sheet_good_inventory` (
  `inv_ID` int(11) NOT NULL AUTO_INCREMENT,
  `m_ID` int(11) DEFAULT NULL,
  `m_parent` int(11) DEFAULT NULL,
  `width` decimal(11,2) DEFAULT NULL,
  `height` decimal(11,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT '0',
  `notes` text CHARACTER SET utf8,
  PRIMARY KEY (`inv_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `sheet_good_inventory`:
--

-- --------------------------------------------------------

--
-- Table structure for table `sheet_good_transactions`
--

CREATE TABLE IF NOT EXISTS `sheet_good_transactions` (
  `sg_trans_ID` int(11) NOT NULL,
  `trans_id` int(11) DEFAULT NULL,
  `inv_id` int(11) DEFAULT NULL,
  `quantity` int(10) NOT NULL,
  `remove_date` datetime DEFAULT NULL,
  PRIMARY KEY (`sg_trans_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `sheet_good_transactions`:
--

-- --------------------------------------------------------

--
-- Table structure for table `site_variables`
--

CREATE TABLE IF NOT EXISTS `site_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `value` varchar(100) NOT NULL,
  `notes` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `site_variables`:
--

--
-- Dumping data for table `site_variables`
--

INSERT INTO `site_variables` (`id`, `name`, `value`, `notes`) VALUES
(1, 'uprint_conv', '16.387', 'inches^3 to grams'),
(2, 'minTime', '1', 'Minimum hour charge for a device'),
(5, 'grace_period', '300', 'Grace period allotted to each Ticket(sec)'),
(6, 'limit', '6000', '(seconds) 3 minutes before auto-logout'),
(7, 'limit_long', '8000', '(seconds) 10 minutes before auto-logout'),
(8, 'maxHold', '14', '# of Days for Holding Period for stored jobs'),
(9, 'serving', '0', 'Now serving number such and such'),
(10, 'bServing', '0', 'Boss Laser Now serving number'),
(11, 'eServing', '0', 'Epilog Laser Now serving number'),
(12, 'next', '0', 'Last Number Issued for 3D Printing'),
(13, 'bNext', '0', 'Last Number Issued for Boss Laser'),
(14, 'eNext', '0', 'Last Number Issued for Epilog Laser'),
(15, 'forgotten', '', 'Password Reset URL goes here'),
(16, 'check_expire', 'N', 'Do we deny users if they have an expired membership. Expected Values (Y,N)'),
(17, 'ip_range_1', '', 'Block certain abilities based upon IP. Follow Regex format.'),
(18, 'ip_range_2', '', 'Block certain abilities based upon IP. Follow Regex format.'),
(19, 'inspectPrint', 'Once a print has been picked up & paid for we can not issue a refund.', 'Disclosure for picking up a 3D Print'),
(20, 'site_name', 'FabApp', 'Name of site owner'),
(21, 'paySite', '', '3rd party Pay System URL'),
(22, 'paySite_name', '', 'Human-readable name for payment system'),
(23, 'currency', 'fas fa-dollar-sign', 'Icon as Defined by Font Awesome'),
(24, 'api_key', '', 'API key for JuiceBox/OctoPuppet communications'),
(25, 'dateFormat', 'M d, Y g:i a', 'format the date using Php\'s date() function.'),
(26, 'timezone', 'America/Chicago', 'Set Local Time Zone'),
(27, 'timeInterval', '.25', 'Minimum time unit of an hour.'),
(28, 'LvlOfStaff', '8', 'First role level ID of staff.'),
(29, 'minRoleTrainer', '8', 'Minimum Role Level of Trainer, below this value you can not issue a training.'),
(30, 'editTrans', '8', ' Role level required to edit a Transaction'),
(31, 'editRole', '8', 'Level of Staff Required to edit RoleID'),
(32, 'editRfid', '8', 'Level of Staff Required to edit RFID'),
(33, 'lastRfid', '', 'This is the last RFID that was scanned by the JuiceBox.'),
(34, 'regexUser', '^\\d{10}$', 'regular expression used to verify a user\'s identification number'),
(35, 'regexPayee', '^\\d{9,10}$', 'regular expression used to verify a payee\'s identification number'),
(36, 'rank_period', '3', '# of months the rank is based off of'),
(37, 'misc', 'Vinyl ', 'Miscellaneous Wait-Tab'),
(38, 'mServing', '0', 'Misc now serving'),
(39, 'mNext', '0', 'Misc Next Issuable Number'),
(40, 'backdoor_pass', '1234567890', 'General password to be used when the authentication server is not working.'),
(41, 'service', 'service1234', 'Service Technician PW'),
(42, 'wait_system', 'new', 'toggle between the new system and the old. (any/new)'),
(43, 'editSV', '8', 'Role Level that allows you to edit Site Variables.  Do not set this beyond your highest assignable level. '),
(44, 'clear_queue', '8', 'Minimum Level Required to clear the Wait Queue'),
(45, 'staffTechnican', '8', 'Minimum Staff Level Required to perform Service Replies and override Gatekeeper'),
(46, 'serviceTechnican', '7', 'External Role Level Required to perform Service Replies and override Gatekeeper'),
(47, 'wait_period', '300', 'Waiting period allotted to each Wait Queue Ticket(sec)'),
(48, 'LvlOfLead', '9', 'Role of Lead for inventory editing'),
(49, 'website_url', 'https://libraries.uta.edu/locations/basement', 'Website for Makerspace'),
(50, 'phone_number', '', 'Makerspace phone number'),
(51, 'sheet_goods_parent', '123', 'sheet good parent material id'),
(52, 'sheet_device', '68', 'Meta-device for association of sheetgoods with device'),
(53, 'strg_drwr_indicator', 'numer', 'numer for a numeric drawer label, alpha for an alphabetical drawer label'),
(54, 'icon2', 'fablab2.png', 'Thermal printer icon'),
(55, 'wq_operatorInQueue', 'Operator is already in this Wait Queue.', 'Alert message sent when an operator is already in the wait queue.'),
(56, 'wq_successAlert', 'You have added a user to the wait queue.', 'Alert message sent when an operator is added to the wait queue.'),
(57, 'wq_phoneAlert', 'You must enter a phone number if selecting a carrier.', 'Alert message sent when an operator selects a carrier but not a phone number.'),
(58, 'wq_carrierAlert', 'You must select a carrier if entering a phone number!', 'Alert message sent when an operator inputs a phone number but not a carrier.'),
(59, 'wq_deviceAlert', 'You must select a device.', 'Alert message sent when an operator does not select a device.'),
(60, 'wq_device_desc', 'Your PC is now available', 'Text/Email message sent when an operator presses the \\\"Send Alert\\\" button and a specific device is available. Must end with a space to properly format with time variable.\\r\\n'),
(61, 'wq_ticketNum', 'You are now in the Basement\'s wait queue.', 'Alert message sent when an operator is added to the wait queue.'),
(62, 'wq_ticketCancel', 'Your Wait Ticket has been cancelled.  There is no undoing this.', 'Alert message sent when an operator\'s wait queue ticket has been cancelled.'),
(63, 'wq_ticketComplete', 'Your wait is nearly over.  Please come to the desk to be assigned a PC.', 'Alert message sent when an operator\'s wait queue ticket has been completed.'),
(64, 'wq_SecondaryEmail', '', 'Listserv address to notify when a self-cancellation has occurred.  Leave blank to disable the notifications.'),
(65, 'gk_MaxTabSize', '10', 'Maximum amount of money a learner can owe us before they are cut off from further jobs.'),
(66, 'gk_MaxTicketTab', '10', 'Maximum amount of tickets a learner may have waiting for action before new ones are denied.'),
(67, 'banlist', ',', 'Unique ID of users no longer allowed to use the services.  Separate the IDs with , characters'),
(68, 'default_time', '1:00:00', 'Maximum time limit for using any device in your makerspace. Is overridden by individual device time limits.HR:Min:Sec format.');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE IF NOT EXISTS `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `message` varchar(255) DEFAULT NULL,
  `variable` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `status`:
--

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`status_id`, `message`, `variable`) VALUES
(1, NULL, NULL),
(2, 'Hardware Failure', 'failed_mat'),
(3, 'Removed', 'removed'),
(4, 'Played', 'used'),
(5, 'Sell Sheet', 'sell_sheet'),
(6, 'Not Played', 'unused'),
(7, 'Updated', 'updated'),
(8, 'Received', 'received'),
(9, NULL, NULL),
(10, NULL, NULL),
(11, 'Active', 'active'),
(12, 'Offline', 'offline'),
(13, 'Moveable', 'moveable'),
(14, 'Hardware Failure', 'total_fail'),
(15, 'Partial', 'partial_fail'),
(16, 'Not Played', 'cancelled'),
(17, 'Complete', 'complete'),
(18, 'Stored', 'stored'),
(19, NULL, NULL),
(20, NULL, NULL),
(21, 'Charge to Account', 'charge_to_acct'),
(22, 'Charge to Basement', 'charge_to_fablab'),
(23, 'Charge to Library', 'charge_to_library'),
(24, 'Charge to IDT', 'charge_to_idt');

-- --------------------------------------------------------

--
-- Table structure for table `storage_box`
--

CREATE TABLE IF NOT EXISTS `storage_box` (
  `drawer` varchar(3) NOT NULL DEFAULT '1',
  `unit` varchar(3) NOT NULL DEFAULT 'A',
  `drawer_size` varchar(7) DEFAULT '5-3',
  `start` varchar(7) NOT NULL DEFAULT '1-1',
  `span` varchar(7) NOT NULL DEFAULT '1-1',
  `trans_id` int(11) DEFAULT NULL,
  `item_change_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `staff_id` varchar(10) DEFAULT NULL,
  `type` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`drawer`,`unit`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `storage_box`:
--

-- --------------------------------------------------------

--
-- Table structure for table `table_descriptions`
--

CREATE TABLE IF NOT EXISTS `table_descriptions` (
  `t_d_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `label` varchar(50) NOT NULL,
  `description` varchar(140) NOT NULL,
  PRIMARY KEY (`t_d_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `table_descriptions`:
--

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

CREATE TABLE IF NOT EXISTS `tm_enroll` (
  `tme_key` int(11) NOT NULL AUTO_INCREMENT,
  `tm_id` int(11) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `completed` datetime NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `current` enum('Y','N') NOT NULL,
  `altered_date` datetime DEFAULT NULL,
  `altered_notes` text,
  `altered_by` varchar(10) DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL,
  PRIMARY KEY (`tme_key`),
  KEY `tm_enroll_index_operator` (`operator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `tm_enroll`:
--

-- --------------------------------------------------------

--
-- Table structure for table `trainingmodule`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `trainingmodule`:
--

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `trans_id` int(11) NOT NULL AUTO_INCREMENT,
  `d_id` int(11) NOT NULL,
  `operator` varchar(10) DEFAULT NULL,
  `est_time` time DEFAULT NULL,
  `t_start` datetime NOT NULL,
  `t_end` datetime DEFAULT NULL,
  `duration` time DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `p_id` int(11) DEFAULT NULL,
  `staff_id` varchar(10) DEFAULT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `pickedup_by` varchar(10) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`trans_id`),
  KEY `device_id` (`d_id`),
  KEY `transactions_index_uta_id` (`operator`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `transactions`:
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `users`:
--

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `operator`, `r_id`, `exp_date`, `icon`, `adj_date`, `notes`, `long_close`) VALUES
(1, '1000000000', 9, NULL, '', '2020-01-13 00:00:00', 'Backdoor lead student account', 'N')
;

-- --------------------------------------------------------

--
-- Table structure for table `wait_queue`
--

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
  `carrier` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Q_id`),
  KEY `Operator` (`Operator`),
  KEY `Dev_id` (`Dev_id`),
  KEY `Devgr_id` (`Devgr_id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;

--
-- RELATIONS FOR TABLE `wait_queue`:
--

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
