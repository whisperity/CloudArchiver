-- phpMyAdmin SQL Dump
-- version 3.3.7deb7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 03, 2013 at 03:22 AM
-- Server version: 5.1.66
-- PHP Version: 5.3.3-7+squeeze14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `CloudArchiver`
--
CREATE DATABASE `CloudArchiver` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `CloudArchiver`;

-- --------------------------------------------------------

--
-- Table structure for table `Posts`
--

CREATE TABLE IF NOT EXISTS `Posts` (
  `ID` int(32) NOT NULL,
  `ThreadID` int(32) NOT NULL,
  `Board` varchar(16) COLLATE utf8_bin NOT NULL,
  `PostTime` int(16) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `Posts`
--


-- --------------------------------------------------------

--
-- Table structure for table `Threads`
--

CREATE TABLE IF NOT EXISTS `Threads` (
  `ID` int(16) NOT NULL,
  `Board` varchar(16) COLLATE utf8_bin NOT NULL,
  `Description` varchar(500) COLLATE utf8_bin NOT NULL,
  `Status` tinyint(1) NOT NULL,
  `LastChecked` int(16) NOT NULL,
  `TimeAdded` int(16) NOT NULL,
  `AddedBy` varchar(50) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `Threads`
--


-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE IF NOT EXISTS `Users` (
  `Username` varchar(50) COLLATE utf8_bin NOT NULL,
  `PassHash` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`Username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `Users`
--

