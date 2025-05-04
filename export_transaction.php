<?php
// Start session
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get export parameters
$borrower_id = isset($_GET['borrower_id']) ? $conn->real_escape_string($_GET['borrower_id']) : '';
$export_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Set the filename
$timestamp = date('Ymd_His');
$filename = 'library_transactions_' . $timestamp;

// Build query based on export type
if (!empty($borrower_id)) {
    // Export transactions for a specific borrower
    if ($export_type == 'active') {
        $query = "SELECT l.TransactionID, l.DateBorrowed, l.DueDate, 
                 b.Title, b.ISBN, b.AccessionNumber, c.CategoryName,
                 a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                 DATEDIFF(l.DueDate, CURDATE()) AS DaysRemaining
                 FROM loan l
                 JOIN book b ON l.BookID = b.BookID
                 LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                 LEFT JOIN category c ON b.CategoryID = c.CategoryID
                 WHERE l.BorrowerID = '$borrower_id' AND l.Status = 'borrowed'
                 ORDER BY l.DueDate ASC";
        $filename = 'active_loans_' . $borrower_id . '_' . $timestamp;
    } elseif ($export_type == 'returned') {
        $query = "SELECT l.TransactionID, l.DateBorrowed, l.DueDate, l.DateReturned,
                 b.Title, b.ISBN, b.AccessionNumber, c.CategoryName,
                 a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                 DATEDIFF(l.DateReturned, l.DateBorrowed) AS LoanDuration
                 FROM loan l
                 JOIN book b ON l.BookID = b.BookID
                 LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                 LEFT JOIN category c ON b.CategoryID = c.CategoryID
                 WHERE l.BorrowerID = '$borrower_id' AND l.Status = 'returned'
                 ORDER BY l.DateReturned DESC";
        $filename = 'returned_books_' . $borrower_id . '_' . $timestamp;
    } elseif ($export_type == 'penalized') {
        $query = "SELECT l.TransactionID, l.DateBorrowed, l.DueDate, l.DateReturned,
                 b.Title, b.ISBN, b.AccessionNumber, c.CategoryName,
                 a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                 DATEDIFF(l.DateReturned, l.DueDate) AS DaysOverdue,
                 pt.PenaltyAmount, pt.Status AS PaymentStatus, pt.DatePaid
                 FROM loan l
                 JOIN book b ON l.BookID = b.BookID
                 LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                 LEFT JOIN category c ON b.CategoryID = c.CategoryID
                 LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
                 WHERE l.BorrowerID = '$borrower_id' AND l.Status = 'penalized'
                 ORDER BY l.DateReturned DESC";
        $filename = 'penalties_' . $borrower_id . '_' . $timestamp;
    } else {
        // All transactions for this borrower
        $query = "SELECT l.TransactionID, l.DateBorrowed, l.DueDate, l.DateReturned, l.Status,
                 b.Title, b.ISBN, b.AccessionNumber, c.CategoryName,
                 a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                 CASE 
                    WHEN l.Status = 'borrowed' THEN DATEDIFF(l.DueDate, CURDATE())
                    WHEN l.Status = 'returned' THEN DATEDIFF(l.DateReturned, l.DateBorrowed)
                    WHEN l.Status = 'penalized' THEN DATEDIFF(l.DateReturned, l.DueDate)
                 END AS DaysDiff,
                 pt.PenaltyAmount
                 FROM loan l
                 JOIN book b ON l.BookID = b.BookID
                 LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                 LEFT JOIN category c ON b.CategoryID = c.CategoryID
                 LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
                 WHERE l.BorrowerID = '$borrower_id'
                 ORDER BY l.DateBorrowed DESC";
        $filename = 'all_transactions_' . $borrower_id . '_' . $timestamp;
    }
} else {
    // Export all transactions
    $status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';
    $start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : date('Y-m-d');
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    
    $query = "SELECT l.TransactionID, l.DateBorrowed, l.DueDate, l.DateReturned, l.Status,
             b.Title, b.ISBN, b.AccessionNumber, c.CategoryName,
             a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
             bo.BorrowerID, bo.FirstName AS BorrowerFirstName, bo.MiddleName AS BorrowerMiddleName, 
             bo.LastName AS BorrowerLastName, bo.Role AS BorrowerRole,
             CASE 
                WHEN l.Status = 'borrowed' AND CURDATE() > l.DueDate THEN 'overdue'
                ELSE l.Status 
             END AS CurrentStatus,
             CASE 
                WHEN l.Status = 'borrowed' AND CURDATE() > l.DueDate THEN DATEDIFF(CURDATE(), l.DueDate)
                WHEN l.Status = 'penalized' THEN DATEDIFF(l.DateReturned, l.DueDate)
                ELSE 0
             END AS DaysOverdue,
             pt.PenaltyAmount
             FROM loan l
             JOIN book b ON l.BookID = b.BookID
             LEFT JOIN authors a ON b.AuthorID = a.AuthorID
             LEFT JOIN category c ON b.CategoryID = c.CategoryID
             JOIN borrowers bo ON l.BorrowerID = bo.BorrowerID
             LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
             WHERE 1=1 ";
    
    // Apply status filter
    if ($status_filter == 'borrowed') {
        $query .= "AND l.Status = 'borrowed' ";
        $filename = 'active_loans_' . $timestamp;
    } elseif ($status_filter == 'returned') {
        $query .= "AND l.Status = 'returned' ";
        $filename = 'returned_books_' . $timestamp;
    } elseif ($status_filter == 'overdue') {
        $query .= "AND ((l.Status = 'borrowed' AND CURDATE() > l.DueDate) OR l.Status = 'penalized') ";
        $filename = 'overdue_books_' . $timestamp;
    } elseif ($status_filter == 'penalized') {
        $query .= "AND l.Status = 'penalized' ";
        $filename = 'penalized_books_' . $timestamp;
    } else {
        $filename = 'all_transactions_' . $timestamp;
    }
    
    // Apply date filter based on status
    if ($status_filter == 'returned' || $status_filter == 'penalized') {
        $query .= "AND (l.DateReturned BETWEEN '$start_date' AND '$end_date') ";
    } else {
        $query .= "AND (l.DateBorrowed BETWEEN '$start_date' AND '$end_date') ";
    }
    
    // Apply search filter if provided
    if (!empty($search)) {
        $query .= "AND (b.Title LIKE '%$search%' OR 
                  b.ISBN LIKE '%$search%' OR 
                  b.AccessionNumber LIKE '%$search%' OR
                  bo.BorrowerID LIKE '%$search%' OR
                  bo.FirstName LIKE '%$search%' OR
                  bo.LastName LIKE '%$search%') ";
    }
    
    $query .= "ORDER BY l.DateBorrowed DESC";
}

// Execute query
$result = $conn->query($query);

// Export based on format
if ($format == 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Set up headers based on query type
    if (!empty($borrower_id)) {
        if ($export_type == 'active') {
            // Headers for active loans
            fputcsv($output, [
                'Transaction ID',
                'Book Title',
                'Author',
                'Category',
                'ISBN',
                'Accession Number',
                'Date Borrowed',
                'Due Date',
                'Days Remaining',
                'Status'
            ]);
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    // Format status
                    $days_remaining = $row['DaysRemaining'];
                    $status = ($days_remaining < 0) ? 'Overdue (' . abs($days_remaining) . ' days)' : 
                              (($days_remaining <= 2) ? 'Due Soon' : 'Active');
                    
                    fputcsv($output, [
                        $row['TransactionID'],
                        $row['Title'],
                        $author,
                        $row['CategoryName'],
                        $row['ISBN'],
                        $row['AccessionNumber'],
                        $row['DateBorrowed'],
                        $row['DueDate'],
                        $days_remaining,
                        $status
                    ]);
                }
            }
        } elseif ($export_type == 'returned') {
            // Headers for returned books
            fputcsv($output, [
                'Transaction ID',
                'Book Title',
                'Author',
                'Category',
                'ISBN',
                'Accession Number',
                'Date Borrowed',
                'Due Date',
                'Date Returned',
                'Loan Duration (Days)'
            ]);
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    fputcsv($output, [
                        $row['TransactionID'],
                        $row['Title'],
                        $author,
                        $row['CategoryName'],
                        $row['ISBN'],
                        $row['AccessionNumber'],
                        $row['DateBorrowed'],
                        $row['DueDate'],
                        $row['DateReturned'],
                        $row['LoanDuration']
                    ]);
                }
            }
        } elseif ($export_type == 'penalized') {
            // Headers for penalized books
            fputcsv($output, [
                'Transaction ID',
                'Book Title',
                'Author',
                'Category',
                'ISBN',
                'Accession Number',
                'Date Borrowed',
                'Due Date',
                'Date Returned',
                'Days Overdue',
                'Penalty Amount',
                'Payment Status',
                'Date Paid'
            ]);
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    fputcsv($output, [
                        $row['TransactionID'],
                        $row['Title'],
                        $author,
                        $row['CategoryName'],
                        $row['ISBN'],
                        $row['AccessionNumber'],
                        $row['DateBorrowed'],
                        $row['DueDate'],
                        $row['DateReturned'],
                        $row['DaysOverdue'],
                        $row['PenaltyAmount'],
                        $row['PaymentStatus'],
                        $row['DatePaid'] ?: 'Not Paid'
                    ]);
                }
            }
        } else {
            // Headers for all transactions
            fputcsv($output, [
                'Transaction ID',
                'Book Title',
                'Author',
                'Category',
                'ISBN',
                'Accession Number',
                'Date Borrowed',
                'Due Date',
                'Date Returned',
                'Status',
                'Days Info',
                'Penalty'
            ]);
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    // Format days info based on status
                    $days_info = '';
                    if ($row['Status'] == 'borrowed') {
                        $days_info = ($row['DaysDiff'] < 0) ? abs($row['DaysDiff']) . ' days overdue' : $row['DaysDiff'] . ' days remaining';
                    } elseif ($row['Status'] == 'returned') {
                        $days_info = $row['DaysDiff'] . ' days borrowed';
                    } elseif ($row['Status'] == 'penalized') {
                        $days_info = $row['DaysDiff'] . ' days overdue';
                    }
                    
                    fputcsv($output, [
                        $row['TransactionID'],
                        $row['Title'],
                        $author,
                        $row['CategoryName'],
                        $row['ISBN'],
                        $row['AccessionNumber'],
                        $row['DateBorrowed'],
                        $row['DueDate'],
                        $row['DateReturned'] ?: 'Not returned',
                        $row['Status'],
                        $days_info,
                        $row['PenaltyAmount'] ? '₱' . number_format($row['PenaltyAmount'], 2) : 'None'
                    ]);
                }
            }
        }
    } else {
        // Headers for all borrowers
        fputcsv($output, [
            'Transaction ID',
            'Borrower ID',
            'Borrower Name',
            'Role',
            'Book Title',
            'Author',
            'Category', 
            'ISBN',
            'Date Borrowed',
            'Due Date',
            'Return Date',
            'Status',
            'Days Overdue',
            'Penalty Amount'
        ]);
        
        // Add data rows
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format author name
                $author = $row['AuthorFirstName'];
                if (!empty($row['AuthorMiddleName'])) {
                    $author .= ' ' . $row['AuthorMiddleName'];
                }
                $author .= ' ' . $row['AuthorLastName'];
                
                // Format borrower name
                $borrower_name = $row['BorrowerFirstName'];
                if (!empty($row['BorrowerMiddleName'])) {
                    $borrower_name .= ' ' . $row['BorrowerMiddleName'];
                }
                $borrower_name .= ' ' . $row['BorrowerLastName'];
                
                // Format status
                $status = $row['CurrentStatus'];
                if ($status == 'overdue') {
                    $status = 'Overdue (' . $row['DaysOverdue'] . ' days)';
                } elseif ($status == 'borrowed') {
                    $status = 'Active';
                } elseif ($status == 'returned') {
                    $status = 'Returned';
                } elseif ($status == 'penalized') {
                    $status = 'Penalized';
                }
                
                fputcsv($output, [
                    $row['TransactionID'],
                    $row['BorrowerID'],
                    $borrower_name,
                    $row['BorrowerRole'],
                    $row['Title'],
                    $author,
                    $row['CategoryName'],
                    $row['ISBN'],
                    $row['DateBorrowed'],
                    $row['DueDate'],
                    $row['DateReturned'] ?: 'Not returned',
                    $status,
                    $row['DaysOverdue'],
                    $row['PenaltyAmount'] ? '₱' . number_format($row['PenaltyAmount'], 2) : 'None'
                ]);
            }
        }
    }
    
    // Close output stream
    fclose($output);
} elseif ($format == 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start HTML output for Excel
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $filename . '</title>
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
    </head>
    <body>
        <table>';
    
    // Set up headers based on query type
    if (!empty($borrower_id)) {
        if ($export_type == 'active') {
            // Headers for active loans
            echo '<thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Accession Number</th>
                    <th>Date Borrowed</th>
                    <th>Due Date</th>
                    <th>Days Remaining</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    // Format status
                    $days_remaining = $row['DaysRemaining'];
                    $status = ($days_remaining < 0) ? 'Overdue (' . abs($days_remaining) . ' days)' : 
                              (($days_remaining <= 2) ? 'Due Soon' : 'Active');
                    
                    echo '<tr>
                        <td>' . $row['TransactionID'] . '</td>
                        <td>' . htmlspecialchars($row['Title']) . '</td>
                        <td>' . htmlspecialchars($author) . '</td>
                        <td>' . htmlspecialchars($row['CategoryName']) . '</td>
                        <td>' . htmlspecialchars($row['ISBN']) . '</td>
                        <td>' . htmlspecialchars($row['AccessionNumber']) . '</td>
                        <td>' . $row['DateBorrowed'] . '</td>
                        <td>' . $row['DueDate'] . '</td>
                        <td>' . $days_remaining . '</td>
                        <td>' . $status . '</td>
                    </tr>';
                }
            }
        } elseif ($export_type == 'returned') {
            // Headers for returned books
            echo '<thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Accession Number</th>
                    <th>Date Borrowed</th>
                    <th>Due Date</th>
                    <th>Date Returned</th>
                    <th>Loan Duration (Days)</th>
                </tr>
            </thead>
            <tbody>';
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    echo '<tr>
                        <td>' . $row['TransactionID'] . '</td>
                        <td>' . htmlspecialchars($row['Title']) . '</td>
                        <td>' . htmlspecialchars($author) . '</td>
                        <td>' . htmlspecialchars($row['CategoryName']) . '</td>
                        <td>' . htmlspecialchars($row['ISBN']) . '</td>
                        <td>' . htmlspecialchars($row['AccessionNumber']) . '</td>
                        <td>' . $row['DateBorrowed'] . '</td>
                        <td>' . $row['DueDate'] . '</td>
                        <td>' . $row['DateReturned'] . '</td>
                        <td>' . $row['LoanDuration'] . '</td>
                    </tr>';
                }
            }
        } elseif ($export_type == 'penalized') {
            // Headers for penalized books
            echo '<thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Accession Number</th>
                    <th>Date Borrowed</th>
                    <th>Due Date</th>
                    <th>Date Returned</th>
                    <th>Days Overdue</th>
                    <th>Penalty Amount</th>
                    <th>Payment Status</th>
                    <th>Date Paid</th>
                </tr>
            </thead>
            <tbody>';
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    echo '<tr>
                        <td>' . $row['TransactionID'] . '</td>
                        <td>' . htmlspecialchars($row['Title']) . '</td>
                        <td>' . htmlspecialchars($author) . '</td>
                        <td>' . htmlspecialchars($row['CategoryName']) . '</td>
                        <td>' . htmlspecialchars($row['ISBN']) . '</td>
                        <td>' . htmlspecialchars($row['AccessionNumber']) . '</td>
                        <td>' . $row['DateBorrowed'] . '</td>
                        <td>' . $row['DueDate'] . '</td>
                        <td>' . $row['DateReturned'] . '</td>
                        <td>' . $row['DaysOverdue'] . '</td>
                        <td>' . $row['PenaltyAmount'] . '</td>
                        <td>' . $row['PaymentStatus'] . '</td>
                        <td>' . ($row['DatePaid'] ?: 'Not Paid') . '</td>
                    </tr>';
                }
            }
        } else {
            // Headers for all transactions
            echo '<thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Accession Number</th>
                    <th>Date Borrowed</th>
                    <th>Due Date</th>
                    <th>Date Returned</th>
                    <th>Status</th>
                    <th>Days Info</th>
                    <th>Penalty</th>
                </tr>
            </thead>
            <tbody>';
            
            // Add data rows
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Format author name
                    $author = $row['AuthorFirstName'];
                    if (!empty($row['AuthorMiddleName'])) {
                        $author .= ' ' . $row['AuthorMiddleName'];
                    }
                    $author .= ' ' . $row['AuthorLastName'];
                    
                    // Format days info based on status
                    $days_info = '';
                    if ($row['Status'] == 'borrowed') {
                        $days_info = ($row['DaysDiff'] < 0) ? abs($row['DaysDiff']) . ' days overdue' : $row['DaysDiff'] . ' days remaining';
                    } elseif ($row['Status'] == 'returned') {
                        $days_info = $row['DaysDiff'] . ' days borrowed';
                    } elseif ($row['Status'] == 'penalized') {
                        $days_info = $row['DaysDiff'] . ' days overdue';
                    }
                    
                    echo '<tr>
                        <td>' . $row['TransactionID'] . '</td>
                        <td>' . htmlspecialchars($row['Title']) . '</td>
                        <td>' . htmlspecialchars($author) . '</td>
                        <td>' . htmlspecialchars($row['CategoryName']) . '</td>
                        <td>' . htmlspecialchars($row['ISBN']) . '</td>
                        <td>' . htmlspecialchars($row['AccessionNumber']) . '</td>
                        <td>' . $row['DateBorrowed'] . '</td>
                        <td>' . $row['DueDate'] . '</td>
                        <td>' . ($row['DateReturned'] ?: 'Not returned') . '</td>
                        <td>' . $row['Status'] . '</td>
                        <td>' . $days_info . '</td>
                        <td>' . ($row['PenaltyAmount'] ? '₱' . number_format($row['PenaltyAmount'], 2) : 'None') . '</td>
                    </tr>';
                }
            }
        }
    } else {
        // Headers for all borrowers
        echo '<thead>
            <tr>
                <th>Transaction ID</th>
                <th>Borrower ID</th>
                <th>Borrower Name</th>
                <th>Role</th>
                <th>Book Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>ISBN</th>
                <th>Date Borrowed</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Days Overdue</th>
                <th>Penalty Amount</th>
            </tr>
        </thead>
        <tbody>';
        
        // Add data rows
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format author name
                $author = $row['AuthorFirstName'];
                if (!empty($row['AuthorMiddleName'])) {
                    $author .= ' ' . $row['AuthorMiddleName'];
                }
                $author .= ' ' . $row['AuthorLastName'];
                
                // Format borrower name
                $borrower_name = $row['BorrowerFirstName'];
                if (!empty($row['BorrowerMiddleName'])) {
                    $borrower_name .= ' ' . $row['BorrowerMiddleName'];
                }
                $borrower_name .= ' ' . $row['BorrowerLastName'];
                
                // Format status
                $status = $row['CurrentStatus'];
                if ($status == 'overdue') {
                    $status = 'Overdue (' . $row['DaysOverdue'] . ' days)';
                } elseif ($status == 'borrowed') {
                    $status = 'Active';
                } elseif ($status == 'returned') {
                    $status = 'Returned';
                } elseif ($status == 'penalized') {
                    $status = 'Penalized';
                }
                
                echo '<tr>
                    <td>' . $row['TransactionID'] . '</td>
                    <td>' . $row['BorrowerID'] . '</td>
                    <td>' . htmlspecialchars($borrower_name) . '</td>
                    <td>' . htmlspecialchars($row['BorrowerRole']) . '</td>
                    <td>' . htmlspecialchars($row['Title']) . '</td>
                    <td>' . htmlspecialchars($author) . '</td>
                    <td>' . htmlspecialchars($row['CategoryName']) . '</td>
                    <td>' . htmlspecialchars($row['ISBN']) . '</td>
                    <td>' . $row['DateBorrowed'] . '</td>
                    <td>' . $row['DueDate'] . '</td>
                    <td>' . ($row['DateReturned'] ?: 'Not returned') . '</td>
                    <td>' . $status . '</td>
                    <td>' . $row['DaysOverdue'] . '</td>
                    <td>' . ($row['PenaltyAmount'] ? '₱' . number_format($row['PenaltyAmount'], 2) : 'None') . '</td>
                </tr>';
            }
        }
    }
    
    // Close HTML
    echo '</tbody>
        </table>
        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
            Generated on ' . date('F d, Y h:i A') . ' | Library Management System
        </div>
    </body>
    </html>';
}

// Close database connection
$conn->close();
exit;