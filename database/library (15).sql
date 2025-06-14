-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2025 at 07:21 AM
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
(30, 'dfsdf', 'sdsd', 'wwe'),
(31, 'ann', '', 'ref'),
(32, 'Danilo', 'E.', 'Ponce');

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
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `CanBorrow` tinyint(1) DEFAULT 1,
  `MaxBorrow` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`BookID`, `Title`, `ISBN`, `AuthorID`, `CategoryID`, `MaterialID`, `LocationID`, `MainClassificationID`, `SubClassificationID`, `CallNumber`, `TotalCopies`, `HoldCopies`, `AcquisitionDate`, `Price`, `Publisher`, `Edition`, `Year`, `AccessionNumber`, `CreatedAt`, `UpdatedAt`, `CanBorrow`, `MaxBorrow`) VALUES
(23, 'ROMEO', '231', 25, 3, 1, 1, 1, 1, '2421421', 11, 5, '2025-05-04', 499.00, 'Book Prod.', '1', '2023', '321324', '2025-05-04 09:46:47', '2025-05-15 15:05:56', 1, NULL),
(24, 'Harry potter', '242424', 18, 1, 2, 1, 1, 1, '345', 7, 5, '2025-05-05', 500.00, 'Book Prod.', '3', '2025', '2135', '2025-05-04 16:21:35', '2025-05-14 13:24:16', 1, NULL),
(25, 'Kill The Mocking Bird', '332324', 26, 1, 1, 5, 1, 1, '11213131', 13, 3, '2025-05-11', 322432.00, 'wrwrwrw', '3', '2009', '1313131', '2025-05-11 13:42:26', '2025-05-14 09:21:34', 1, NULL),
(26, 'Noli', '13131', 26, 1, 4, 5, 2, 2, '3133', 10, 2, '2025-05-11', 234.00, 'Abc', '2', '2005', '13133', '2025-05-11 13:43:54', '2025-05-11 13:43:54', 1, NULL);

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
('34532532532', 'Jayson', 'Sarmiento', 'Cebritas', '09847523564', 'Student', 'College', '3', 'BSMT', NULL, NULL, '2025-04-26 14:19:38'),
('21312', 'Ron', 'Oliver', 'Saladero', '09847523564', 'Student', 'College', '4', 'BSIT', NULL, NULL, '2025-05-04 08:10:02');

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
(75, '21312', 23, '2025-05-04', '2025-05-05', '2025-05-04', '0012-2097', NULL, 'returned', '2025-05-04 16:53:01'),
(76, '21312', 23, '2025-05-06', '2025-05-07', '2025-05-11', '0012-2097', NULL, 'returned', '2025-05-06 06:54:09'),
(77, '2111600005', 24, '2025-05-11', '2025-05-18', '2025-05-11', '0012-2097', NULL, 'returned', '2025-05-11 03:19:59'),
(79, '2111600005', 24, '2025-05-11', '2025-05-18', '2025-05-11', '0012-2097', NULL, 'returned', '2025-05-11 04:29:07'),
(81, '234232', 23, '2025-05-11', '2025-05-14', '2025-05-15', '0012-2097', NULL, 'penalized', '2025-05-11 06:40:48'),
(82, '234232', 25, '2025-05-11', '2025-05-12', '2025-05-14', '0012-2097', NULL, 'returned', '2025-05-11 16:42:22'),
(85, '21312', 24, '2025-05-11', '2025-05-11', NULL, '0012-2097', NULL, 'borrowed', '2025-05-11 17:09:44'),
(86, '2111600005', 24, '2025-05-14', '2025-05-14', NULL, '0012-2097', NULL, 'borrowed', '2025-05-14 13:23:33');

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
(4, 'sdszdsd', 'Dsdsd'),
(5, '350', 'Filipiana'),
(6, '35464', 'fik');

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
(1, 'Overdue', 5.00, 0),
(10, 'Overdue (Fiction)', 5.00, 0);

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
(7, 76, 1, 20.00, 'overdue', '2025-05-11', 'Overdue by 4 days', 'paid', '2025-05-11', '2025-05-11 06:42:20'),
(10, 82, 1, 10.00, 'overdue', '2025-05-14', 'Overdue by 2 days', 'paid', '2025-05-14', '2025-05-14 09:21:34'),
(11, 81, 1, 5.00, 'overdue', '2025-05-15', 'Overdue by 1 days', 'unpaid', NULL, '2025-05-15 15:05:56');

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
('0012-2097', 'Michelle', 'Sarmiento', 'Cebritas', 'Librarian', 'Prk. 24, Nursery Road, Lagao, G.S.C.', '09109248412', '2025-04-19 14:09:01'),
('345', 'Rose', 'Villa', 'Go', 'librarian', 'Prk. 18, Vicente, G.S.C', '098736524234', '2025-05-14 03:32:28'),
('435', 'Kenneth', '', 'Fritz', 'assistant', 'Prk. 24, Lagao, G.S.C', '09876354256', '2025-05-14 03:34:50');

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
(3, '0012-2097', 'mits', '$2y$10$Zij1AhbNj0I2d7acN8L.D.XDiQ0wHgHMKK119AQjYklfDH4aTC.5.', NULL, 'active'),
(5, '345', 'Rose', '$2y$10$Js.GNm6Lse24j5Ta5T6Hgu/pEoqMOTfPkvq219Gk8JzhSpKeVl3nu', '2025-05-15 16:02:40', 'active'),
(6, '435', 'Ken', '$2y$10$Zjcuqw6.lYLPVh3FwYdXF.Xdy7j1Mdzi7JMlt5VIapHsO615LFBE2', '2025-05-14 04:02:08', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `PurchaseOrderID` int(11) NOT NULL,
  `SupplierID` int(11) NOT NULL,
  `InstitutionName` varchar(255) NOT NULL,
  `Address` text NOT NULL,
  `ContactInfo` varchar(255) NOT NULL,
  `ProjectName` varchar(255) NOT NULL,
  `PurchaseOrderDate` date NOT NULL,
  `Purpose` text NOT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `PreparedBy` varchar(255) NOT NULL,
  `PreparedByPosition` varchar(255) NOT NULL,
  `NotedBy` varchar(255) NOT NULL,
  `NotedByPosition` varchar(255) NOT NULL,
  `ApprovedBy` varchar(255) NOT NULL,
  `ApprovedByPosition` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`PurchaseOrderID`, `SupplierID`, `InstitutionName`, `Address`, `ContactInfo`, `ProjectName`, `PurchaseOrderDate`, `Purpose`, `TotalAmount`, `PreparedBy`, `PreparedByPosition`, `NotedBy`, `NotedByPosition`, `ApprovedBy`, `ApprovedByPosition`) VALUES
(12, 1, 'PACIFIC SOUTHBAY COLLEGE, INC', 'PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY', 'TEL. NO. 553-1450 MOBILE NO. 0946-713-6519', 'BOOKS TO PURCHASE', '2024-12-02', 'SOCIAL WORK PROGRAM.', 7682.00, 'GELYMAE V. ENERO', '0', 'KENNETH D. CLAUDIO, MBM', 'VP for Academics', 'DR. LEANDRO ADOR A. DIZON, CPA', 'School President'),
(13, 1, 'PACIFIC SOUTHBAY COLLEGE, INC', 'PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY', 'TEL. NO. 553-1450 MOBILE NO. 0946-713-6519', 'BOOKS TO PURCHASE', '2024-12-02', 'SOCIAL WORK PROGRAM.', 1242.00, 'GELYMAE V. ENERO', 'PSCI, Librarian', 'KENNETH D. CLAUDIO, MBM', 'VP for Academics', 'DR. LEANDRO ADOR A. DIZON, CPA', 'School President'),
(14, 1, 'PACIFIC SOUTHBAY COLLEGE, INC', 'PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY', 'TEL. NO. 553-1450 MOBILE NO. 0946-713-6519', 'BOOKS TO PURCHASE', '2024-12-02', 'SOCIAL WORK PROGRAM.', 100166.00, 'GELYMAE V. ENERO', 'PSCI, Librarian', 'KENNETH D. CLAUDIO, MBM', 'VP for Academics', 'DR. LEANDRO ADOR A. DIZON, CPA', 'School President'),
(15, 1, 'PACIFIC SOUTHBAY COLLEGE, INC', 'PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY', 'TEL. NO. 553-1450 MOBILE NO. 0946-713-6519', 'BOOKS TO PURCHASE', '2024-12-02', 'SOCIAL WORK PROGRAM.', 672.00, 'GELYMAE V. ENERO', 'PSCI, Librarian', 'KENNETH D. CLAUDIO, MBM', 'VP for Academics', 'DR. LEANDRO ADOR A. DIZON, CPA', 'School President');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `ItemID` int(11) NOT NULL,
  `PurchaseOrderID` int(11) NOT NULL,
  `ItemNo` int(11) NOT NULL,
  `Quantity` varchar(50) NOT NULL,
  `Description` text NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `Amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`ItemID`, `PurchaseOrderID`, `ItemNo`, `Quantity`, `Description`, `UnitPrice`, `Amount`) VALUES
(12, 12, 1, '23', '2', 334.00, 7682.00),
(13, 13, 1, '23', '45', 54.00, 1242.00),
(14, 14, 22, '2334', 'BOKS', 32.00, 74688.00),
(15, 14, 3234, '434', 'BOOKS', 43.00, 18662.00),
(16, 14, 4343, '32', 'HEY', 213.00, 6816.00),
(17, 15, 112, '32', '5676', 21.00, 672.00);

-- --------------------------------------------------------

--
-- Table structure for table `storage`
--

CREATE TABLE `storage` (
  `StorageID` int(11) NOT NULL,
  `ItemDescription` varchar(255) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `Remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storage`
--

INSERT INTO `storage` (`StorageID`, `ItemDescription`, `Quantity`, `Remarks`) VALUES
(16, 'noli', 6, 'This book is donation');

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
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `SupplierID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Address` text NOT NULL,
  `ContactInfo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`SupplierID`, `Name`, `Address`, `ContactInfo`) VALUES
(1, 'Default Supplier', 'PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY', 'TEL. NO. 553-1450 MOBILE NO. 0946-713-6519'),
(2, 'ron', 'Apopong', '0902343232');

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
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`PurchaseOrderID`),
  ADD KEY `SupplierID` (`SupplierID`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`ItemID`),
  ADD KEY `PurchaseOrderID` (`PurchaseOrderID`);

--
-- Indexes for table `storage`
--
ALTER TABLE `storage`
  ADD PRIMARY KEY (`StorageID`);

--
-- Indexes for table `subclassification`
--
ALTER TABLE `subclassification`
  ADD PRIMARY KEY (`SubClassificationID`),
  ADD KEY `MainClassID` (`MainClassID`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`SupplierID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `AuthorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `book`
--
ALTER TABLE `book`
  MODIFY `BookID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `TransactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `LocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mainclassification`
--
ALTER TABLE `mainclassification`
  MODIFY `MainClassificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `PenaltyTransactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personnellogin`
--
ALTER TABLE `personnellogin`
  MODIFY `LoginID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `PurchaseOrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `ItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `storage`
--
ALTER TABLE `storage`
  MODIFY `StorageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `subclassification`
--
ALTER TABLE `subclassification`
  MODIFY `SubClassificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `SupplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchase_orders` (`PurchaseOrderID`);

--
-- Constraints for table `subclassification`
--
ALTER TABLE `subclassification`
  ADD CONSTRAINT `subclassification_ibfk_1` FOREIGN KEY (`MainClassID`) REFERENCES `mainclassification` (`MainClassificationID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
