-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2021 at 09:10 PM
-- Server version: 10.4.14-MariaDB
-- PHP Version: 7.2.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tirocini`
--
CREATE DATABASE IF NOT EXISTS `tirocini` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tirocini`;

-- --------------------------------------------------------

--
-- Table structure for table `aziende`
--

CREATE TABLE `aziende` (
  `ID_azienda` int(8) NOT NULL,
  `denominazione` varchar(50) NOT NULL,
  `referente` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `classi`
--

CREATE TABLE `classi` (
  `ID_classe` int(11) NOT NULL,
  `anno` int(11) NOT NULL,
  `sezione` int(11) NOT NULL,
  `indirizzo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `indirizzi`
--

CREATE TABLE `indirizzi` (
  `sigla` varchar(10) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `sovrintendente` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tipi_utente`
--

CREATE TABLE `tipi_utente` (
  `ID_tipoutente` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tutoring`
--

CREATE TABLE `tutoring` (
  `id_tutor` int(8) NOT NULL,
  `id_studente` int(8) NOT NULL,
  `data_inizio` date NOT NULL,
  `data_fine` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `utenti`
--

CREATE TABLE `utenti` (
  `ID_utente` int(8) NOT NULL,
  `nome` varchar(30) NOT NULL,
  `cognome` varchar(30) NOT NULL,
  `password` varchar(512) NOT NULL,
  `email` varchar(50) NOT NULL,
  `autorizzato` tinyint(1) NOT NULL,
  `id_azienda` int(8) DEFAULT NULL,
  `tipo_utente` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aziende`
--
ALTER TABLE `aziende`
  ADD PRIMARY KEY (`ID_azienda`),
  ADD KEY `referente` (`referente`);

--
-- Indexes for table `classi`
--
ALTER TABLE `classi`
  ADD PRIMARY KEY (`ID_classe`),
  ADD KEY `indirizzo` (`indirizzo`);

--
-- Indexes for table `indirizzi`
--
ALTER TABLE `indirizzi`
  ADD PRIMARY KEY (`sigla`),
  ADD KEY `sovrintendente` (`sovrintendente`);

--
-- Indexes for table `tipi_utente`
--
ALTER TABLE `tipi_utente`
  ADD PRIMARY KEY (`ID_tipoutente`);

--
-- Indexes for table `tutoring`
--
ALTER TABLE `tutoring`
  ADD PRIMARY KEY (`id_tutor`,`id_studente`),
  ADD KEY `id_studente` (`id_studente`);

--
-- Indexes for table `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`ID_utente`),
  ADD KEY `id_azienda` (`id_azienda`),
  ADD KEY `tipo_utente` (`tipo_utente`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aziende`
--
ALTER TABLE `aziende`
  MODIFY `ID_azienda` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classi`
--
ALTER TABLE `classi`
  MODIFY `ID_classe` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipi_utente`
--
ALTER TABLE `tipi_utente`
  MODIFY `ID_tipoutente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utenti`
--
ALTER TABLE `utenti`
  MODIFY `ID_utente` int(8) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aziende`
--
ALTER TABLE `aziende`
  ADD CONSTRAINT `aziende_ibfk_1` FOREIGN KEY (`referente`) REFERENCES `utenti` (`ID_utente`);

--
-- Constraints for table `classi`
--
ALTER TABLE `classi`
  ADD CONSTRAINT `classi_ibfk_1` FOREIGN KEY (`indirizzo`) REFERENCES `indirizzi` (`sigla`);

--
-- Constraints for table `indirizzi`
--
ALTER TABLE `indirizzi`
  ADD CONSTRAINT `indirizzi_ibfk_1` FOREIGN KEY (`sovrintendente`) REFERENCES `utenti` (`ID_utente`);

--
-- Constraints for table `tutoring`
--
ALTER TABLE `tutoring`
  ADD CONSTRAINT `tutoring_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `utenti` (`ID_utente`),
  ADD CONSTRAINT `tutoring_ibfk_2` FOREIGN KEY (`id_studente`) REFERENCES `utenti` (`ID_utente`);

--
-- Constraints for table `utenti`
--
ALTER TABLE `utenti`
  ADD CONSTRAINT `utenti_ibfk_1` FOREIGN KEY (`id_azienda`) REFERENCES `aziende` (`ID_azienda`),
  ADD CONSTRAINT `utenti_ibfk_2` FOREIGN KEY (`tipo_utente`) REFERENCES `tipi_utente` (`ID_tipoutente`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
