-- phpMyAdmin SQL Dump
-- version 3.4.11.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 13, 2013 at 07:45 PM
-- Server version: 5.5.30
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `kaplanex_research`
--

-- --------------------------------------------------------

--
-- Table structure for table `data_analysis`
--

DROP TABLE IF EXISTS `data_analysis`;
CREATE TABLE IF NOT EXISTS `data_analysis` (
  `user_id` int(20) unsigned NOT NULL,
  `data_analysis_type` varchar(200) NOT NULL,
  `nonrecurring_included` tinyint(1) NOT NULL,
  `recurring_included` tinyint(1) NOT NULL,
  `array_serialized` varchar(10000) NOT NULL,
  PRIMARY KEY (`user_id`,`data_analysis_type`,`nonrecurring_included`,`recurring_included`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
CREATE TABLE IF NOT EXISTS `event` (
  `event_id` int(100) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(20) unsigned NOT NULL,
  `google_event_id` varchar(200) NOT NULL,
  `google_created` varchar(100) NOT NULL,
  `google_updated` varchar(100) NOT NULL,
  `google_start` varchar(100) NOT NULL,
  `google_end` varchar(100) NOT NULL,
  `google_recurrence` varchar(1000) NOT NULL,
  `google_recurring_event_id` varchar(200) NOT NULL,
  `recurring_event_id` int(100) NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8675 ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `ignore` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
