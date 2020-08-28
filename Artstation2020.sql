-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 28, 2020 at 01:55 PM
-- Server version: 5.7.31-0ubuntu0.18.04.1
-- PHP Version: 7.2.24-0ubuntu0.18.04.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Artstation2020`
--
CREATE DATABASE IF NOT EXISTS `Artstation2020` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `Artstation2020`;

-- --------------------------------------------------------

--
-- Table structure for table `character_profiles`
--

CREATE TABLE `character_profiles` (
  `id` int(11) NOT NULL,
  `json_content` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `character_profiles`
--

INSERT INTO `character_profiles` (`id`, `json_content`) VALUES
(1, '{\"era\": \"6th Century\", \"name\": \"Saint Cadoc\", \"authors\": [\"Geoffrey Chaucer\", \"Percy Bysshe Shelley\", \"Philip Gross\"], \"img_url\": \"img/cadoc_cut-min.png\", \"location\": {\"lat\": \"51.391389\", \"lon\": \"-2.963889\"}}'),
(2, '{\"era\": \"Present Day\", \"name\": \"Lesser Black Backed Gull\", \"authors\": [\"Alexander Pope\", \"John Milton\", \"Lord Alfred Tennyson\", \"Philip Gross\"], \"img_url\": \"img/gull_cut-min.png\", \"location\": {\"lat\": \"51.31277\", \"lon\": \"-2.885901\"}}'),
(3, '{\"era\": \"19th Century\", \"name\": \"Nurse In The Hospital\", \"authors\": [\"John Keats\", \"Philip Gross\", \"Robert Browning\", \"Walt Whitman\"], \"img_url\": \"img/nurse_cut-min.png\", \"location\": {\"lat\": \"51.464793\", \"lon\": \"-2.672704\"}}'),
(4, '{\"era\": \"1940s\", \"name\": \"Frank Harris\", \"authors\": [\"John Clare\", \"Robert Burns\", \"William Topaz McGonagall\"], \"img_url\": \"img/harris_cut-min.png\", \"location\": {\"lat\": \"51.376269\", \"lon\": \"-3.121959\"}}'),
(5, '{\"era\": \"18th Century\", \"name\": \"Lighthouse Keeper\", \"authors\": [\"Algernon Charles Swinburne\", \"Christopher Smart\", \"Henry Wadsworth Longfellow\"], \"img_url\": \"img/lighthouse_cut-min.png\", \"location\": {\"lat\": \"51.394061\", \"lon\": \"-3.2883\"}}'),
(6, '{\"era\": \"Present Day\", \"name\": \"Warden\", \"authors\": [\"Edward Thomas\", \"Emily Dickinson\", \"William Shakespeare\"], \"img_url\": \"img/warden_cut-min.png\", \"location\": {\"lat\": \"51.083861\", \"lon\": \"-3.302084\"}}');

-- --------------------------------------------------------

--
-- Table structure for table `custom_soundscapes`
--

CREATE TABLE `custom_soundscapes` (
  `id` int(11) NOT NULL,
  `json_content` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `postcard_shares`
--

CREATE TABLE `postcard_shares` (
  `id` int(11) NOT NULL,
  `json_content` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `character_profiles`
--
ALTER TABLE `character_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_soundscapes`
--
ALTER TABLE `custom_soundscapes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `postcard_shares`
--
ALTER TABLE `postcard_shares`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `character_profiles`
--
ALTER TABLE `character_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `custom_soundscapes`
--
ALTER TABLE `custom_soundscapes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `postcard_shares`
--
ALTER TABLE `postcard_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
