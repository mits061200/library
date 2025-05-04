-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2025 at 09:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `AuthorID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`AuthorID`, `FirstName`, `MiddleName`, `LastName`) VALUES
(18, 'kaye ann', 'revill', 'tuquib'),
(25, 'Michelle', 'Sarmiento', 'Cebritas'),
(26, 'mits', 'ererer', 'rererere'),
(30, 'dfsdf', 'sdsd', 'wwe');

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `BookID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `ISBN` varchar(50) NOT NULL,
  `AuthorID` int(11) NOT NULL,
  `CategoryID` int(11) NOT NULL,
  `MaterialID` int(11) NOT NULL,
  `LocationID` int(11) NOT NULL,
  `MainClassificationID` int(11) NOT NULL,
  `SubClassificationID` int(11) NOT NULL,
  `CallNumber` varchar(50) DEFAULT NULL,
  `TotalCopies` int(11) NOT NULL DEFAULT 0,
  `HoldCopies` int(11) NOT NULL DEFAULT 0,
  `AcquisitionDate` date NOT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Publisher` varchar(255) DEFAULT NULL,
  `Edition` varchar(50) DEFAULT NULL,
  `Year` year(4) DEFAULT NULL,
  `AccessionNumber` varchar(50) DEFAULT NULL,
  `Status` enum('Available','Unavailable') GENERATED ALWAYS AS (case when `TotalCopies` - `HoldCopies` > 0 then 'Available' else 'Unavailable' end) STORED,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`BookID`, `Title`, `ISBN`, `AuthorID`, `CategoryID`, `MaterialID`, `LocationID`, `MainClassificationID`, `SubClassificationID`, `CallNumber`, `TotalCopies`, `HoldCopies`, `AcquisitionDate`, `Price`, `Publisher`, `Edition`, `Year`, `AccessionNumber`, `CreatedAt`, `UpdatedAt`) VALUES
(12, 'HCL', '32432424', 26, 14, 2, 6, 2, 2, '353535', 3, 1, '2025-04-26', 234.00, 'AVFS', '5', '2007', '32424', '2025-04-26 10:37:53', '2025-04-29 05:57:24'),
(13, 'English', '33231421', 18, 14, 3, 4, 1, 1, '353535', 12, 5, '2025-04-26', 265.00, 'ass', '2', '2023', '432432423', '2025-04-26 14:14:23', '2025-04-29 05:56:56'),
(14, 'Filipino', '4344', 25, 14, 2, 5, 2, 2, '24235421', 10, 3, '2025-04-29', 309.00, 'Book Prod.', '2', '2009', '2342342', '2025-04-29 05:55:01', '2025-04-29 05:57:35'),
(15, 'The Hunger Games', '242424', 18, 1, 2, 3, 1, 1, '2421421', 3, 1, '2025-04-29', 20076.00, 'Book Prod.', '3', '2005', '4214', '2025-04-29 05:59:37', '2025-04-29 05:59:37'),
(16, 'Harry Potter', '3131', 26, 1, 2, 3, 2, 4, '1321321', 2, 1, '2025-04-29', 409.00, 'AVFS', '3', '1997', '2442', '2025-04-29 06:01:00', '2025-04-29 06:01:00'),
(17, 'To Kill a Mocking Bird', '2321323', 25, 1, 2, 4, 1, 1, '1232', 4, 2, '2025-04-29', 5465.00, 'ass', '1', '2003', '321324', '2025-04-29 06:03:10', '2025-04-29 06:03:10');

-- --------------------------------------------------------

--
-- Table structure for table `borrowers`
--

CREATE TABLE `borrowers` (
  `BorrowerID` varchar(20) DEFAULT NULL,
  `FirstName` varchar(50) NOT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) NOT NULL,
  `ContactNumber` varchar(15) NOT NULL,
  `Role` enum('Student','Employee','Faculty') NOT NULL,
  `Level` enum('College','Senior High School') DEFAULT NULL,
  `Year` varchar(10) DEFAULT NULL,
  `Course` varchar(50) DEFAULT NULL,
  `GradeLevel` varchar(10) DEFAULT NULL,
  `Strand` varchar(50) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowers`
--

INSERT INTO `borrowers` (`BorrowerID`, `FirstName`, `MiddleName`, `LastName`, `ContactNumber`, `Role`, `Level`, `Year`, `Course`, `GradeLevel`, `Strand`, `CreatedAt`) VALUES
('2111600005', 'Michelle', 'Sarmiento', 'Cebritas', '09077489667', 'Student', 'College', '4', 'BSIT', NULL, NULL, '2025-04-22 15:33:13'),
('234232', 'kaye', 'revilla', 'tuquib', '09836746534', 'Student', 'College', '4', 'BSIT', NULL, NULL, '2025-04-24 16:30:11'),
('34532532532', 'Jayson', 'Sarmiento', 'Cebritas', '09847523564', 'Student', 'College', '3', 'BSMT', NULL, NULL, '2025-04-26 14:19:38');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`) VALUES
(1, 'Fiction'),
(3, 'Fantasy'),
(11, 'Drama'),
(12, 'romance'),
(14, 'Educational');

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `TransactionID` int(11) NOT NULL,
  `BorrowerID` varchar(20) NOT NULL,
  `BookID` int(11) NOT NULL,
  `DateBorrowed` date NOT NULL,
  `DueDate` date NOT NULL,
  `DateReturned` date DEFAULT NULL,
  `PersonnelID` varchar(50) NOT NULL,
  `PenaltyID` int(11) DEFAULT NULL,
  `Status` enum('borrowed','returned','penalized') NOT NULL DEFAULT 'borrowed',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan`
--

INSERT INTO `loan` (`TransactionID`, `BorrowerID`, `BookID`, `DateBorrowed`, `DueDate`, `DateReturned`, `PersonnelID`, `PenaltyID`, `Status`, `CreatedAt`) VALUES
(28, '2111600005', 13, '2025-04-25', '2025-05-02', '2025-05-04', '0012-2097', 1, 'penalized', '2025-04-29 09:45:53'),
(29, '234232', 16, '2025-04-29', '2025-05-06', NULL, '0012-2097', NULL, 'borrowed', '2025-04-29 10:15:41'),
(30, '234232', 15, '2025-04-29', '2025-05-06', NULL, '0012-2097', NULL, 'borrowed', '2025-04-29 10:49:28'),
(31, '234232', 13, '2025-04-29', '2025-05-02', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-29 10:53:27'),
(32, '234232', 14, '2025-04-29', '2025-05-02', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-29 10:53:27'),
(33, '2111600005', 14, '2025-04-29', '2025-05-02', '2025-05-04', '0012-2097', 1, 'penalized', '2025-04-29 11:18:28'),
(34, '2111600005', 16, '2025-04-29', '2025-05-06', NULL, '0012-2097', NULL, 'borrowed', '2025-04-29 12:14:34'),
(35, '2111600005', 17, '2025-04-29', '2025-05-06', NULL, '0012-2097', NULL, 'borrowed', '2025-04-29 12:35:05'),
(36, '34532532532', 12, '2025-04-29', '2025-05-02', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-29 12:53:53'),
(37, '34532532532', 13, '2025-04-29', '2025-05-02', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-29 12:54:36'),
(38, '34532532532', 16, '2025-04-29', '2025-05-06', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-29 12:55:22'),
(39, '34532532532', 17, '2025-04-29', '2025-05-06', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-29 12:55:22'),
(40, '234232', 13, '2025-04-30', '2025-05-03', '2025-04-30', '0012-2097', NULL, 'returned', '2025-04-30 12:17:41');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `LocationID` int(11) NOT NULL,
  `LocationName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`LocationID`, `LocationName`) VALUES
(1, 'shelf 1'),
(3, 'shelf 2'),
(4, 'shelf 3'),
(5, 'shelf 4'),
(6, 'shelf 5'),
(7, 'shelf 6');

-- --------------------------------------------------------

--
-- Table structure for table `mainclassification`
--

CREATE TABLE `mainclassification` (
  `MainClassificationID` int(11) NOT NULL,
  `ClassificationNumber` varchar(10) NOT NULL,
  `Description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mainclassification`
--

INSERT INTO `mainclassification` (`MainClassificationID`, `ClassificationNumber`, `Description`) VALUES
(1, '000', 'General Work'),
(2, '010`', 'Bibliographies'),
(4, 'sdszdsd', 'Dsdsd');

-- --------------------------------------------------------

--
-- Table structure for table `material`
--

CREATE TABLE `material` (
  `MaterialID` int(11) NOT NULL,
  `MaterialName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material`
--

INSERT INTO `material` (`MaterialID`, `MaterialName`) VALUES
(1, 'Magazine'),
(2, 'Book'),
(3, 'Article'),
(4, 'Newspaper'),
(5, 'Research'),
(8, 'Capstone');

-- --------------------------------------------------------

--
-- Table structure for table `penalty`
--

CREATE TABLE `penalty` (
  `PenaltyID` int(11) NOT NULL,
  `PenaltyName` varchar(50) NOT NULL,
  `PenaltyRate` decimal(10,2) DEFAULT NULL,
  `Duration` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalty`
--

INSERT INTO `penalty` (`PenaltyID`, `PenaltyName`, `PenaltyRate`, `Duration`) VALUES
(1, 'Overdue', 5.00, 3),
(10, 'Overdue (Fiction)', 5.00, 7);

-- --------------------------------------------------------

--
-- Table structure for table `penaltytransaction`
--

CREATE TABLE `penaltytransaction` (
  `PenaltyTransactionID` int(11) NOT NULL,
  `LoanID` int(11) NOT NULL,
  `PenaltyID` int(11) NOT NULL,
  `PenaltyAmount` decimal(10,2) NOT NULL,
  `PenaltyType` enum('overdue','lost','damaged') NOT NULL,
  `DateIssued` date NOT NULL,
  `Remarks` text DEFAULT NULL,
  `Status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `DatePaid` date DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penaltytransaction`
--

INSERT INTO `penaltytransaction` (`PenaltyTransactionID`, `LoanID`, `PenaltyID`, `PenaltyAmount`, `PenaltyType`, `DateIssued`, `Remarks`, `Status`, `DatePaid`, `CreatedAt`) VALUES
(3, 33, 1, 10.00, 'overdue', '2025-05-04', 'Days Overdue: 2', 'unpaid', NULL, '2025-05-04 06:44:09'),
(4, 33, 1, 10.00, 'overdue', '2025-05-04', 'Days Overdue: 2', 'unpaid', NULL, '2025-05-04 06:44:13'),
(5, 33, 1, 10.00, 'overdue', '2025-05-04', 'Days Overdue: 2', 'unpaid', NULL, '2025-05-04 06:50:24'),
(6, 28, 1, 10.00, 'overdue', '2025-05-04', 'Days Overdue: 2', 'unpaid', NULL, '2025-05-04 06:50:24');

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `PersonnelID` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) NOT NULL,
  `Position` varchar(50) NOT NULL,
  `Address` varchar(50) NOT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `DateAdded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`PersonnelID`, `FirstName`, `MiddleName`, `LastName`, `Position`, `Address`, `PhoneNumber`, `DateAdded`) VALUES
('0012-2097', 'Michelle', 'Sarmiento', 'Cebritas', 'Librarian', 'Prk. 24, Nursery Road, Lagao, G.S.C.', '09109248412', '2025-04-19 14:09:01');

-- --------------------------------------------------------

--
-- Table structure for table `personnellogin`
--

CREATE TABLE `personnellogin` (
  `LoginID` int(11) NOT NULL,
  `PersonnelID` varchar(50) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `Status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnellogin`
--

INSERT INTO `personnellogin` (`LoginID`, `PersonnelID`, `Username`, `Password`, `LastLogin`, `Status`) VALUES
(3, '0012-2097', 'mits', '$2y$10$Zij1AhbNj0I2d7acN8L.D.XDiQ0wHgHMKK119AQjYklfDH4aTC.5.', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `subclassification`
--

CREATE TABLE `subclassification` (
  `SubClassificationID` int(11) NOT NULL,
  `MainClassID` int(11) NOT NULL,
  `SubClassificationNumber` varchar(10) NOT NULL,
  `Description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subclassification`
--

INSERT INTO `subclassification` (`SubClassificationID`, `MainClassID`, `SubClassificationNumber`, `Description`) VALUES
(1, 1, '001', 'Knowledge'),
(2, 2, '011', 'Bibliography'),
(4, 2, '012', 'dwsdsaeded'),
(6, 1, 'dsds', 'DD');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `SupplierID` int(11) NOT NULL,
  `SupplierName` varchar(50) NOT NULL,
  `ContactPerson` varchar(50) NOT NULL,
  `ContactNumber` varchar(15) NOT NULL,
  `Address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`SupplierID`, `SupplierName`, `ContactPerson`, `ContactNumber`, `Address`) VALUES
(1, 'Abc Publisher', 'Maria Ming', '09077489667', 'San Miguel, Calumpang, General Santos City, South Cotabato, Mindanao, 9500');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`AuthorID`);

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`BookID`),
  ADD UNIQUE KEY `ISBN` (`ISBN`),
  ADD KEY `AuthorID` (`AuthorID`),
  ADD KEY `CategoryID` (`CategoryID`),
  ADD KEY `MaterialID` (`MaterialID`),
  ADD KEY `LocationID` (`LocationID`),
  ADD KEY `MainClassificationID` (`MainClassificationID`),
  ADD KEY `SubClassificationID` (`SubClassificationID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`TransactionID`),
  ADD KEY `BorrowerID` (`BorrowerID`),
  ADD KEY `BookID` (`BookID`),
  ADD KEY `PersonnelID` (`PersonnelID`),
  ADD KEY `PenaltyID` (`PenaltyID`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`LocationID`);

--
-- Indexes for table `mainclassification`
--
ALTER TABLE `mainclassification`
  ADD PRIMARY KEY (`MainClassificationID`),
  ADD UNIQUE KEY `ClassificationNumber` (`ClassificationNumber`);

--
-- Indexes for table `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`MaterialID`);

--
-- Indexes for table `penalty`
--
ALTER TABLE `penalty`
  ADD PRIMARY KEY (`PenaltyID`);

--
-- Indexes for table `penaltytransaction`
--
ALTER TABLE `penaltytransaction`
  ADD PRIMARY KEY (`PenaltyTransactionID`),
  ADD KEY `LoanID` (`LoanID`),
  ADD KEY `PenaltyID` (`PenaltyID`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`PersonnelID`);

--
-- Indexes for table `personnellogin`
--
ALTER TABLE `personnellogin`
  ADD PRIMARY KEY (`LoginID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `PersonnelID` (`PersonnelID`);

--
-- Indexes for table `subclassification`
--
ALTER TABLE `subclassification`
  ADD PRIMARY KEY (`SubClassificationID`),
  ADD KEY `MainClassID` (`MainClassID`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`SupplierID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `AuthorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `BookID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `TransactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mainclassification`
--
ALTER TABLE `mainclassification`
  MODIFY `MainClassificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `material`
--
ALTER TABLE `material`
  MODIFY `MaterialID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `PenaltyID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `penaltytransaction`
--
ALTER TABLE `penaltytransaction`
  MODIFY `PenaltyTransactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `personnellogin`
--
ALTER TABLE `personnellogin`
  MODIFY `LoginID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subclassification`
--
ALTER TABLE `subclassification`
  MODIFY `SubClassificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `SupplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book`
--
ALTER TABLE `book`
  ADD CONSTRAINT `book_ibfk_1` FOREIGN KEY (`AuthorID`) REFERENCES `authors` (`AuthorID`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_ibfk_2` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_ibfk_3` FOREIGN KEY (`MaterialID`) REFERENCES `material` (`MaterialID`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_ibfk_4` FOREIGN KEY (`LocationID`) REFERENCES `location` (`LocationID`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_ibfk_5` FOREIGN KEY (`MainClassificationID`) REFERENCES `mainclassification` (`MainClassificationID`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_ibfk_6` FOREIGN KEY (`SubClassificationID`) REFERENCES `subclassification` (`SubClassificationID`) ON DELETE CASCADE;

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `loan_ibfk_2` FOREIGN KEY (`PenaltyID`) REFERENCES `penalty` (`PenaltyID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `loan_ibfk_3` FOREIGN KEY (`PersonnelID`) REFERENCES `personnel` (`PersonnelID`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `penaltytransaction`
--
ALTER TABLE `penaltytransaction`
  ADD CONSTRAINT `penalty_transaction_ibfk_1` FOREIGN KEY (`LoanID`) REFERENCES `loan` (`TransactionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `penalty_transaction_ibfk_2` FOREIGN KEY (`PenaltyID`) REFERENCES `penalty` (`PenaltyID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `personnellogin`
--
ALTER TABLE `personnellogin`
  ADD CONSTRAINT `personnellogin_ibfk_1` FOREIGN KEY (`PersonnelID`) REFERENCES `personnel` (`PersonnelID`) ON DELETE CASCADE;

--
-- Constraints for table `subclassification`
--
ALTER TABLE `subclassification`
  ADD CONSTRAINT `subclassification_ibfk_1` FOREIGN KEY (`MainClassID`) REFERENCES `mainclassification` (`MainClassificationID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
