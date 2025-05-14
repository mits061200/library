<?php
session_start();

include 'header.php';
?>
<link rel="stylesheet" href="css/loan_transactions.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
include 'navbar.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$error_message = '';
$success_message = '';
$borrower_search = '';
$transactions = [];
$selected_returns = isset($_SESSION['selected_returns']) ? $_SESSION['selected_returns'] : [];

// First, fetch a valid personnel ID that we can use for returns
$valid_personnel_id = null;
$personnel_query = "SELECT PersonnelID FROM personnel LIMIT 1";
$personnel_result = $conn->query($personnel_query);
if ($personnel_result && $personnel_result->num_rows > 0) {
    $personnel_row = $personnel_result->fetch_assoc();
    $valid_personnel_id = $personnel_row['PersonnelID'];
}

// Handle adding a book to return list
if (isset($_POST['add_book_return']) && isset($_POST['transaction_id'])) {
    $transaction_id = $conn->real_escape_string($_POST['transaction_id']);
    
    // Check if book is already in return list
    $book_exists = false;
    foreach ($selected_returns as $book) {
        if ($book['TransactionID'] == $transaction_id) {
            $book_exists = true;
            break;
        }
    }
    
    if ($book_exists) {
        $error_message = "This book is already in your return list.";
    } else {
        // Fetch loan details
        $loan_query = "SELECT l.TransactionID, l.BookID, l.DateBorrowed, l.DueDate, l.Status,
        b.Title, b.ISBN, b.AccessionNumber, b.CategoryID, c.CategoryName,
        a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName
        FROM loan l
        JOIN book b ON l.BookID = b.BookID
        LEFT JOIN authors a ON b.AuthorID = a.AuthorID
        LEFT JOIN category c ON b.CategoryID = c.CategoryID
        WHERE l.TransactionID = '$transaction_id'";
        $loan_result = $conn->query($loan_query);

        if ($loan_result && $loan_result->num_rows > 0) {
            $loan_data = $loan_result->fetch_assoc();
        
            // Calculate days overdue and penalty
            $due_date = new DateTime($loan_data['DueDate']);
            $today = new DateTime();
            $days_overdue = ($today > $due_date) ? $today->diff($due_date)->days : 0;
        
            $penalty_amount = 0;
            if ($days_overdue > 0) {
                // Fetch penalty rate and duration
                $penalty_query = "SELECT PenaltyID, PenaltyRate, Duration FROM penalty WHERE ";
                $penalty_query .= ($loan_data['CategoryID'] == 1) ? "PenaltyName = 'Overdue (Fiction)'" : "PenaltyName = 'Overdue'";
                $penalty_query .= " LIMIT 1";
        
                $penalty_result = $conn->query($penalty_query);
                if ($penalty_result && $penalty_result->num_rows > 0) {
                    $penalty_data = $penalty_result->fetch_assoc();
                    $penalty_rate = floatval($penalty_data['PenaltyRate']);
                    $grace_period = intval($penalty_data['Duration']);
        
                    // Apply penalty only after the grace period
                    if ($days_overdue > $grace_period) {
                        $effective_days = $days_overdue - $grace_period;
                        $penalty_amount = $effective_days * $penalty_rate;
                    }
                }
            }
        
            // Determine the status of the book
            if ($book['days_overdue'] > 0) {
                // Handle overdue penalties
                $penalty_amount = $book['penalty_amount'];
                $penalty_sql = "INSERT INTO penaltytransaction (LoanID, PenaltyID, PenaltyAmount, PenaltyType, DateIssued, Remarks, Status) 
                                VALUES ('$loan_id', 1, '$penalty_amount', 'overdue', '$return_date', 'Days Overdue: {$book['days_overdue']}', 'unpaid')";
                if (!$conn->query($penalty_sql)) {
                    throw new Exception("Error recording penalty: " . $conn->error);
                }
            }
        
            // Add penalty and return date to loan data
$loan_data['return_date'] = date('Y-m-d');
$loan_data['days_overdue'] = $days_overdue;
$loan_data['penalty_amount'] = $penalty_amount;
$loan_data['Title'] = isset($loan_data['Title']) ? $loan_data['Title'] : 'Unknown Title';
$loan_data['AuthorFirstName'] = isset($loan_data['AuthorFirstName']) ? $loan_data['AuthorFirstName'] : '';
$loan_data['AuthorMiddleName'] = isset($loan_data['AuthorMiddleName']) ? $loan_data['AuthorMiddleName'] : '';
$loan_data['AuthorLastName'] = isset($loan_data['AuthorLastName']) ? $loan_data['AuthorLastName'] : '';
$loan_data['CategoryName'] = isset($loan_data['CategoryName']) ? $loan_data['CategoryName'] : 'Unknown Category';

// Add to return list
$selected_returns[] = $loan_data;
$_SESSION['selected_returns'] = $selected_returns;
$success_message = "Book added to return list.";
        } else {
            $error_message = "Could not find loan information.";
        }
    }
}

// Handle removing a book from return list
if (isset($_POST['remove_book']) && isset($_POST['remove_index'])) {
    $index = intval($_POST['remove_index']);
    if (isset($selected_returns[$index])) {
        array_splice($selected_returns, $index, 1);
        $_SESSION['selected_returns'] = $selected_returns;
        $success_message = "Book removed from return list.";
    }
}

// Process returns
if (isset($_POST['return_book']) && isset($_POST['transaction_id'])) {
    $transaction_id = $conn->real_escape_string($_POST['transaction_id']);
    $return_date = date('Y-m-d');

    // Fetch loan details
    $loan_query = "SELECT l.TransactionID, l.BookID, l.DueDate, l.Status,
                   DATEDIFF('$return_date', l.DueDate) AS days_overdue
                   FROM loan l
                   WHERE l.TransactionID = '$transaction_id'";
    $loan_result = $conn->query($loan_query);

    if ($loan_result && $loan_result->num_rows > 0) {
        $loan_data = $loan_result->fetch_assoc();

        if ($loan_data['Status'] === 'returned') {
            $error_message = "This book has already been returned.";
        } else {
            $days_overdue = intval($loan_data['days_overdue']);
            $penalty_amount = 0;

            // Handle overdue penalties
        if ($days_overdue > 0) {
    // Fetch the PenaltyID and PenaltyRate
    $penalty_query = "SELECT PenaltyID, PenaltyRate FROM penalty WHERE PenaltyName = 'Overdue' LIMIT 1";
    $penalty_result = $conn->query($penalty_query);

    if ($penalty_result && $penalty_result->num_rows > 0) {
        $penalty_data = $penalty_result->fetch_assoc();
        $penalty_id = $penalty_data['PenaltyID'];
        $penalty_rate = floatval($penalty_data['PenaltyRate']);
        $penalty_amount = $days_overdue * $penalty_rate;

        // Insert penalty record
        $penalty_sql = "INSERT INTO penaltytransaction (LoanID, PenaltyID, PenaltyAmount, PenaltyType, DateIssued, Remarks, Status) 
                        VALUES ('$transaction_id', '$penalty_id', '$penalty_amount', 'overdue', '$return_date', 'Overdue by $days_overdue days', 'unpaid')";
        if (!$conn->query($penalty_sql)) {
            throw new Exception("Error recording penalty: " . $conn->error);
        }
    } else {
        // Handle missing penalty configuration
        throw new Exception("Penalty configuration not found for overdue penalties.");
    }
}

            // Update loan status to returned
            $update_loan_sql = "UPDATE loan SET 
                                DateReturned = '$return_date', 
                                Status = '" . ($days_overdue > 0 ? 'penalized' : 'returned') . "' 
                                WHERE TransactionID = '$transaction_id'";
            if (!$conn->query($update_loan_sql)) {
                $error_message = "Error updating loan status: " . $conn->error;
            }

            // Increment TotalCopies for the returned book
            $update_copies_sql = "UPDATE book 
                                  SET TotalCopies = TotalCopies + 1 
                                  WHERE BookID = '{$loan_data['BookID']}'";
            if (!$conn->query($update_copies_sql)) {
                $error_message = "Error updating book copies: " . $conn->error;
            }

            if (empty($error_message)) {
                $success_message = "Book returned successfully!";
            }
        }
    } else {
        $error_message = "Loan record not found.";
    }
}
// Handle updating return date and recalculating penalties
if (isset($_POST['update_return_date_index']) && isset($_POST['update_return_date_value'])) {
    $index = intval($_POST['update_return_date_index']);
    $new_date = $_POST['update_return_date_value'];
    
    if (isset($selected_returns[$index])) {
        // Update return date
        $selected_returns[$index]['return_date'] = $new_date;
        
        // Get appropriate penalty rate for this book's category
        $penalty_rate = 10.00; // Default fallback
        
        $penalty_query = "SELECT PenaltyRate FROM penalty WHERE ";
        if ($selected_returns[$index]['CategoryID'] == 1) {
            $penalty_query .= "PenaltyName = 'Overdue (Fiction)' LIMIT 1";
        } else {
            $penalty_query .= "PenaltyName = 'Overdue' LIMIT 1";
        }
            
        $penalty_result = $conn->query($penalty_query);
        if ($penalty_result && $penalty_result->num_rows > 0) {
            $penalty_row = $penalty_result->fetch_assoc();
            $penalty_rate = floatval($penalty_row['PenaltyRate']);
        }
        
        // Recalculate days overdue and penalty
        $due_date = new DateTime($selected_returns[$index]['DueDate']);
        $return_date = new DateTime($new_date);
        $days_overdue = ($return_date > $due_date) ? $return_date->diff($due_date)->days : 0;
        $penalty_amount = $days_overdue * $penalty_rate;
        
        // Update the data
        $selected_returns[$index]['days_overdue'] = $days_overdue;
        $selected_returns[$index]['penalty_amount'] = $penalty_amount;
        $selected_returns[$index]['penalty_rate'] = $penalty_rate;
        
        // Save updated data to session
        $_SESSION['selected_returns'] = $selected_returns;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Handle search
if (isset($_POST['search_borrower']) || isset($_GET['search'])) {
    $search_term = isset($_POST['borrower']) ? $conn->real_escape_string($_POST['borrower']) : 
                  (isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '');
    
    $borrower_search = $search_term;
    
    if (!empty($search_term)) {
        // Query to find distinct borrowers with loans based on search term
        $sql = "SELECT DISTINCT b.BorrowerID, b.FirstName, b.MiddleName, b.LastName, b.Role
                FROM loan l
                JOIN borrowers b ON l.BorrowerID = b.BorrowerID
                WHERE (b.BorrowerID LIKE '%$search_term%' OR 
                      b.FirstName LIKE '%$search_term%' OR 
                      b.LastName LIKE '%$search_term%')";
                
        // Apply status filter
        if ($status_filter == 'borrowed') {
            $sql .= " AND l.Status = 'borrowed'";
        } elseif ($status_filter == 'returned') {
            $sql .= " AND l.Status = 'returned'";
        } elseif ($status_filter == 'overdue') {
            $sql .= " AND ((l.Status = 'borrowed' AND CURDATE() > l.DueDate) OR l.Status = 'penalized')";
        } elseif ($status_filter == 'penalized') {
            $sql .= " AND l.Status = 'penalized'";
        }
                
        $sql .= " ORDER BY b.LastName, b.FirstName";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            // Loop through each borrower
            while ($borrower = $result->fetch_assoc()) {
                $borrower_id = $borrower['BorrowerID'];
                
                // Count active loans for this borrower
                $borrowed_sql = "SELECT COUNT(*) AS borrowed_count 
                                FROM loan 
                                WHERE BorrowerID = '$borrower_id' 
                                AND Status = 'borrowed'";
                $borrowed_result = $conn->query($borrowed_sql);
                $borrowed_row = $borrowed_result->fetch_assoc();
                $borrowed_count = $borrowed_row['borrowed_count'];
                
                // Count returned books for this borrower
                $returned_sql = "SELECT COUNT(*) AS returned_count 
                               FROM loan 
                               WHERE BorrowerID = '$borrower_id' 
                               AND Status = 'returned'";
                $returned_result = $conn->query($returned_sql);
                $returned_row = $returned_result->fetch_assoc();
                $returned_count = $returned_row['returned_count'];
                
                // Count penalized books for this borrower
                $penalized_sql = "SELECT COUNT(*) AS penalized_count 
                                FROM loan 
                                WHERE BorrowerID = '$borrower_id' 
                                AND Status = 'penalized'";
                $penalized_result = $conn->query($penalized_sql);
                $penalized_row = $penalized_result->fetch_assoc();
                $penalized_count = $penalized_row['penalized_count'];
                
                // Count overdue but not yet returned books
                $overdue_sql = "SELECT COUNT(*) AS overdue_count 
                              FROM loan 
                              WHERE BorrowerID = '$borrower_id' 
                              AND Status = 'borrowed'
                              AND CURDATE() > DueDate";
                $overdue_result = $conn->query($overdue_sql);
                $overdue_row = $overdue_result->fetch_assoc();
                $overdue_count = $overdue_row['overdue_count'];
                
                // Skip borrowers with no activity if filtering
                if ($status_filter == 'borrowed' && $borrowed_count == 0) continue;
                if ($status_filter == 'returned' && $returned_count == 0) continue;
                if ($status_filter == 'overdue' && ($overdue_count == 0 && $penalized_count == 0)) continue;
                if ($status_filter == 'penalized' && $penalized_count == 0) continue;
                
                // Format borrower name
                $name = $borrower['FirstName'];
                if (!empty($borrower['MiddleName'])) {
                    $name .= ' ' . $borrower['MiddleName'];
                }
                $name .= ' ' . $borrower['LastName'];
                
                // Add to transactions array
                $transactions[] = [
                    'borrower_id' => $borrower_id,
                    'name' => $name,
                    'role' => $borrower['Role'],
                    'borrowed_count' => $borrowed_count,
                    'returned_count' => $returned_count,
                    'penalized_count' => $penalized_count,
                    'overdue_count' => $overdue_count,
                    'total_count' => $borrowed_count + $returned_count + $penalized_count
                ];
            }
            
            if (empty($transactions)) {
                $error_message = "No transactions found for the search criteria.";
            }
        } else {
            $error_message = "No borrowers found matching the search criteria.";
        }
    }
} else {
    // If no search, show recent transactions (limit to 20)
    $sql = "SELECT DISTINCT b.BorrowerID, b.FirstName, b.MiddleName, b.LastName, b.Role
            FROM loan l
            JOIN borrowers b ON l.BorrowerID = b.BorrowerID
            WHERE 1=1 ";
    
    // Apply status filter
    if ($status_filter == 'borrowed') {
        $sql .= " AND l.Status = 'borrowed'";
    } elseif ($status_filter == 'returned') {
        $sql .= " AND l.Status = 'returned'";
    } elseif ($status_filter == 'overdue') {
        $sql .= " AND ((l.Status = 'borrowed' AND CURDATE() > l.DueDate) OR l.Status = 'penalized')";
    } elseif ($status_filter == 'penalized') {
        $sql .= " AND l.Status = 'penalized'";
    }
    
    // Apply date filter based on status
    if ($status_filter == 'returned' || $status_filter == 'penalized') {
        $sql .= " AND (l.DateReturned BETWEEN '$start_date' AND '$end_date')";
    } else {
        $sql .= " AND (l.DateBorrowed BETWEEN '$start_date' AND '$end_date')";
    }
    
    $sql .= " ORDER BY l.DateBorrowed DESC LIMIT 20";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($borrower = $result->fetch_assoc()) {
            $borrower_id = $borrower['BorrowerID'];
            
            // Count active loans for this borrower
            $borrowed_sql = "SELECT COUNT(*) AS borrowed_count 
                            FROM loan 
                            WHERE BorrowerID = '$borrower_id' 
                            AND Status = 'borrowed'";
            $borrowed_result = $conn->query($borrowed_sql);
            $borrowed_row = $borrowed_result->fetch_assoc();
            $borrowed_count = $borrowed_row['borrowed_count'];
            
            // Count returned books for this borrower
            $returned_sql = "SELECT COUNT(*) AS returned_count 
                           FROM loan 
                           WHERE BorrowerID = '$borrower_id' 
                           AND Status = 'returned'";
            $returned_result = $conn->query($returned_sql);
            $returned_row = $returned_result->fetch_assoc();
            $returned_count = $returned_row['returned_count'];
            
            // Count penalized books for this borrower
            $penalized_sql = "SELECT COUNT(*) AS penalized_count 
                            FROM loan 
                            WHERE BorrowerID = '$borrower_id' 
                            AND Status = 'penalized'";
            $penalized_result = $conn->query($penalized_sql);
            $penalized_row = $penalized_result->fetch_assoc();
            $penalized_count = $penalized_row['penalized_count'];
            
            // Count overdue but not yet returned books
            $overdue_sql = "SELECT COUNT(*) AS overdue_count 
                          FROM loan 
                          WHERE BorrowerID = '$borrower_id' 
                          AND Status = 'borrowed'
                          AND CURDATE() > DueDate";
            $overdue_result = $conn->query($overdue_sql);
            $overdue_row = $overdue_result->fetch_assoc();
            $overdue_count = $overdue_row['overdue_count'];
            
            // Format borrower name
            $name = $borrower['FirstName'];
            if (!empty($borrower['MiddleName'])) {
                $name .= ' ' . $borrower['MiddleName'];
            }
            $name .= ' ' . $borrower['LastName'];
            
            // Add to transactions array
            $transactions[] = [
                'borrower_id' => $borrower_id,
                'name' => $name,
                'role' => $borrower['Role'],
                'borrowed_count' => $borrowed_count,
                'returned_count' => $returned_count,
                'penalized_count' => $penalized_count,
                'overdue_count' => $overdue_count,
                'total_count' => $borrowed_count + $returned_count + $penalized_count
            ];
        }
    }
}

// View details of a specific transaction
$view_borrower_id = isset($_GET['view']) ? $conn->real_escape_string($_GET['view']) : '';
$borrowed_books = [];
$returned_books = [];
$penalized_books = [];

if (!empty($view_borrower_id)) {
    // Get borrower details
    $borrower_sql = "SELECT * FROM borrowers WHERE BorrowerID = '$view_borrower_id'";
    $borrower_result = $conn->query($borrower_sql);
    $borrower_details = $borrower_result->fetch_assoc();
    
    // Get all borrowed books for this borrower, excluding those already in return list
    $borrowed_sql = "SELECT l.TransactionID, l.BookID, l.DateBorrowed, l.DueDate, l.Status,
    b.Title, b.ISBN, b.AccessionNumber, b.CategoryID, c.CategoryName,
    a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName
    FROM loan l
    JOIN book b ON l.BookID = b.BookID
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
    LEFT JOIN category c ON b.CategoryID = c.CategoryID
    WHERE l.BorrowerID = '$view_borrower_id'
    AND l.Status = 'borrowed'";

    // Exclude books already in return list
    if (!empty($selected_returns)) {
        $return_ids = array_column($selected_returns, 'TransactionID');
        $ids_string = implode("','", $return_ids);
        $borrowed_sql .= " AND l.TransactionID NOT IN ('$ids_string')";
    }

    $borrowed_sql .= " ORDER BY 
                    CASE 
                        WHEN CURDATE() > l.DueDate THEN 0
                        ELSE 1
                    END,
                    l.DueDate ASC";
    
    $borrowed_result = $conn->query($borrowed_sql);
    
    
    if ($borrowed_result && $borrowed_result->num_rows > 0) {
        while ($book = $borrowed_result->fetch_assoc()) {
            // Format author name
            $author = $book['AuthorFirstName'];
            if (!empty($book['AuthorMiddleName'])) {
                $author .= ' ' . $book['AuthorMiddleName'];
            }
            $author .= ' ' . $book['AuthorLastName'];
            
            // Calculate days until due or days overdue
            $due_date = new DateTime($book['DueDate']);
            $today = new DateTime();
            $days_diff = $today->diff($due_date)->format("%r%a");
            
            $borrowed_books[] = [
                'transaction_id' => $book['TransactionID'],
                'book_id' => $book['BookID'],
                'title' => $book['Title'],
                'author' => $author,
                'isbn' => $book['ISBN'],
                'category' => $book['CategoryName'],
                'accession_number' => $book['AccessionNumber'],
                'date_borrowed' => $book['DateBorrowed'],
                'due_date' => $book['DueDate'],
                'days_diff' => $days_diff,
                'status' => $book['Status']
            ];
        }
    }
    
    // Get all returned books for this borrower
    $returned_sql = "SELECT l.TransactionID, l.BookID, l.DateBorrowed, l.DueDate, l.DateReturned, l.Status,
                    b.Title, b.ISBN, b.AccessionNumber, b.CategoryID, c.CategoryName,
                    a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                    pt.PenaltyAmount, pt.Status AS PenaltyStatus
                 FROM loan l
                 JOIN book b ON l.BookID = b.BookID
                 LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                 LEFT JOIN category c ON b.CategoryID = c.CategoryID
                 LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
                 WHERE l.BorrowerID = '$view_borrower_id'
                 AND l.Status = 'returned'
                 ORDER BY l.DateReturned DESC
                 LIMIT 10";
    
    $returned_result = $conn->query($returned_sql);
    
if ($returned_result && $returned_result->num_rows > 0) {
    while ($book = $returned_result->fetch_assoc()) {
        // Format author name
        $author = $book['AuthorFirstName'];
        if (!empty($book['AuthorMiddleName'])) {
            $author .= ' ' . $book['AuthorMiddleName'];
        }
        $author .= ' ' . $book['AuthorLastName'];
        
        // Calculate loan duration in days
        $borrowed_date = new DateTime($book['DateBorrowed']);
        $returned_date = new DateTime($book['DateReturned']);
        $loan_duration = $borrowed_date->diff($returned_date)->days;
        
        $returned_books[] = [
            'transaction_id' => $book['TransactionID'],
            'book_id' => $book['BookID'],
            'title' => $book['Title'],
            'author' => $author,
            'isbn' => $book['ISBN'],
            'category' => $book['CategoryName'],
            'accession_number' => $book['AccessionNumber'],
            'date_borrowed' => $book['DateBorrowed'],
            'due_date' => $book['DueDate'],
            'date_returned' => $book['DateReturned'],
            'loan_duration' => $loan_duration,
            'PenaltyAmount' => $book['PenaltyAmount'],
            'PenaltyStatus' => $book['PenaltyStatus'],
            'status' => $book['Status']
        ];
    }
}
    
    // Get all penalized books for this borrower
    $penalized_sql = "SELECT l.TransactionID, l.BookID, l.DateBorrowed, l.DueDate, l.DateReturned, l.Status,
                        b.Title, b.ISBN, b.AccessionNumber, b.CategoryID, c.CategoryName,
                        a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                        pt.PenaltyAmount, pt.Status AS PenaltyStatus, pt.DatePaid
                     FROM loan l
                     JOIN book b ON l.BookID = b.BookID
                     LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                     LEFT JOIN category c ON b.CategoryID = c.CategoryID
                     LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
                     WHERE l.BorrowerID = '$view_borrower_id'
                     AND l.Status = 'penalized'
                     ORDER BY l.DateReturned DESC";
    
    $penalized_result = $conn->query($penalized_sql);
    
    if ($penalized_result && $penalized_result->num_rows > 0) {
        while ($book = $penalized_result->fetch_assoc()) {
            // Format author name
            $author = $book['AuthorFirstName'];
            if (!empty($book['AuthorMiddleName'])) {
                $author .= ' ' . $book['AuthorMiddleName'];
            }
            $author .= ' ' . $book['AuthorLastName'];
            
            // Calculate days overdue
            $due_date = new DateTime($book['DueDate']);
            $returned_date = new DateTime($book['DateReturned']);
            $days_overdue = $due_date->diff($returned_date)->days;
            
            $penalized_books[] = [
                'transaction_id' => $book['TransactionID'],
                'book_id' => $book['BookID'],
                'title' => $book['Title'],
                'author' => $author,
                'isbn' => $book['ISBN'],
                'category' => $book['CategoryName'],
                'accession_number' => $book['AccessionNumber'],
                'date_borrowed' => $book['DateBorrowed'],
                'due_date' => $book['DueDate'],
                'date_returned' => $book['DateReturned'],
                'days_overdue' => $days_overdue,
                'penalty_amount' => $book['PenaltyAmount'],
                'penalty_status' => $book['PenaltyStatus'],
                'date_paid' => $book['DatePaid'],
                'status' => $book['Status']
            ];
        }
    }
}

// Get pending penalties for this borrower
$total_penalties = 0;
if (!empty($borrower_details)) {
    $penalty_query = "SELECT SUM(pt.PenaltyAmount) as TotalPenalty 
                    FROM loan bl 
                    JOIN penaltytransaction pt ON bl.TransactionID = pt.LoanID 
                    WHERE bl.BorrowerID = '{$borrower_details['BorrowerID']}' 
                    AND bl.Status = 'penalized'";
    $penalty_result = $conn->query($penalty_query);
    if ($penalty_result && $penalty_result->num_rows > 0) {
        $penalty_row = $penalty_result->fetch_assoc();
        $total_penalties = $penalty_row['TotalPenalty'] ?: 0;
    }
}


// Handle penalty payment status update
if (isset($_POST['penalty_status']) && isset($_POST['transaction_id'])) {
    $transaction_id = $conn->real_escape_string($_POST['transaction_id']);
    $status = $conn->real_escape_string($_POST['penalty_status']);
    $date_paid = ($status == 'paid') ? date('Y-m-d') : null;
    
    $update_sql = "UPDATE penaltytransaction SET 
                  Status = '$status', 
                  DatePaid = " . ($date_paid ? "'$date_paid'" : "NULL") . "
                  WHERE LoanID = '$transaction_id'";
    
    if ($conn->query($update_sql)) {
        $success_message = "Penalty status updated successfully!";
        
        // Also update loan status if all penalties are paid
        if ($status == 'paid') {
            $check_penalties = "SELECT COUNT(*) AS unpaid_count 
                               FROM penaltytransaction 
                               WHERE LoanID = '$transaction_id' 
                               AND Status = 'unpaid'";
            $result = $conn->query($check_penalties);
            $row = $result->fetch_assoc();
            
            if ($row['unpaid_count'] == 0) {
                $update_loan = "UPDATE loan SET Status = 'returned' 
                               WHERE TransactionID = '$transaction_id'";
                $conn->query($update_loan);
            }
        }
    } else {
        $error_message = "Error updating penalty status: " . $conn->error;
    }
    
    // Refresh the page to show updated status
    header("Location: loan_transactions.php?view=$view_borrower_id");
    exit();
}


?>


<!-- MAIN CONTENT STRUCTURE -->
<main class="content">
    <div class="loan-section">
        <div class="classification-container">
            <!-- Page Header -->
            <h2>Loan Transactions</h2>

            <!-- Display error/success messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <div class="tab-buttons">
                <button class="tab-btn" onclick="window.location.href='loan.php'">Loan</button>
                <span class="arrow">&gt;</span>
                <button class="tab-btn active">Loan Transactions</button>
            </div>

            <!-- Filter Controls -->
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <?php if (!empty($view_borrower_id)): ?>
                        <input type="hidden" name="view" value="<?php echo $view_borrower_id; ?>">
                    <?php endif; ?>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="borrowed" <?php echo $status_filter == 'borrowed' ? 'selected' : ''; ?>>Active Loans</option>
                                <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                                <option value="overdue" <?php echo $status_filter == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                <option value="penalized" <?php echo $status_filter == 'penalized' ? 'selected' : ''; ?>>Penalized</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="start_date">From:</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <div class="filter-group">
                            <label for="end_date">To:</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" onchange="this.form.submit()">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Search Form -->
            <form class="search-form" method="POST" action="">
                <i class="fas fa-search"></i>
                <div class="search-container">
                    <input type="text" class="search-input" id="borrower-search" name="borrower" placeholder="Search by ID, First Name, or Last Name" value="<?php echo htmlspecialchars($borrower_search); ?>">
                    <div id="borrower-suggestions" class="suggestions-container"></div>
                </div>
                <button type="submit" name="search_borrower" class="search-btn">Search</button>
            </form>

            <?php if (empty($view_borrower_id)): ?>
                <!-- TRANSACTIONS TABLE -->
                <?php if (!empty($transactions)): ?>
                    <h3 class="section-title">
                        <?php 
                        // Dynamic title based on filter
                        if ($status_filter == 'borrowed') echo 'Active Loans';
                        elseif ($status_filter == 'returned') echo 'Returned Books';
                        elseif ($status_filter == 'overdue') echo 'Overdue Books';
                        elseif ($status_filter == 'penalized') echo 'Penalized Books';
                        else echo 'All Transactions';
                        ?>
                        (<?php echo count($transactions); ?> borrowers)
                    </h3>
                    
                    <div class="table-responsive">
                        <table class="transaction-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Borrower</th>
                                    <th>Role</th>
                                    <th>Status Summary</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $index => $transaction): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $transaction['name'] . " (" . $transaction['borrower_id'] . ")"; ?></td>
                                    <td><?php echo $transaction['role']; ?></td>
                                    <td>
                                        <div class="status-summary">
                                            <?php if ($transaction['borrowed_count'] > 0): ?>
                                                <span class="status-badge status-active">
                                                    <?php echo $transaction['borrowed_count']; ?> Active
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($transaction['overdue_count'] > 0): ?>
                                                <span class="status-badge status-overdue">
                                                    <?php echo $transaction['overdue_count']; ?> Overdue
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($transaction['returned_count'] > 0): ?>
                                                <span class="status-badge status-returned">
                                                    <?php echo $transaction['returned_count']; ?> Returned
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($transaction['penalized_count'] > 0): ?>
                                                <span class="status-badge status-penalty">
                                                    <?php echo $transaction['penalized_count']; ?> Penalized
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="actions-cell">
                                        <!-- View Details Button -->
                                        <a href="?view=<?php echo $transaction['borrower_id']; ?>&status=<?php echo $status_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?><?php echo !empty($borrower_search) ? '&search=' . urlencode($borrower_search) : ''; ?>" class="view-btn" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No transactions found. Use the search above to find borrowers.</p>
                <?php endif; ?>
            <?php else: ?>
                    <!-- BORROWER DETAILS VIEW -->
            <div class="borrower-details">
   <div class="borrower-details">
    <h3>Borrower Information</h3>
    <?php if (!empty($borrower_details)): ?>
        <div class="borrower-info two-column">
            <div class="left-column">
                <p><strong>ID no.:</strong> <?php echo $borrower_details['BorrowerID']; ?></p>
                <p><strong>First Name:</strong> <?php echo $borrower_details['FirstName']; ?></p>
                <p><strong>Middle Name:</strong> <?php echo $borrower_details['MiddleName']; ?></p>
                <p><strong>Last Name:</strong> <?php echo $borrower_details['LastName']; ?></p>
            </div>
            <div class="right-column">
                <p><strong>Role:</strong> <?php echo $borrower_details['Role']; ?></p>
                <p><strong>Contact Number:</strong> <?php echo $borrower_details['ContactNumber']; ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

                <!-- SELECTED BOOKS FOR RETURN SECTION -->
                <?php if (!empty($selected_returns)): ?>
                    <div class="selected-books-section">
                        <h3>Books to Return</h3>
                        <div class="selected-books-list">
                            <table class="books-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>Date Borrowed</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Penalty</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($selected_returns as $index => $book): ?>
                                    <tr>
                                    <td><?php echo isset($book['Title']) ? $book['Title'] : 'Unknown Title'; ?></td>
                                    <td>
                                        <?php 
                                        $author = '';
                                        if (isset($book['AuthorFirstName'])) {
                                            $author .= $book['AuthorFirstName'];
                                        }
                                        if (isset($book['AuthorMiddleName']) && !empty($book['AuthorMiddleName'])) {
                                            $author .= ' ' . $book['AuthorMiddleName'];
                                        }
                                        if (isset($book['AuthorLastName'])) {
                                            $author .= ' ' . $book['AuthorLastName'];
                                        }
                                        echo !empty($author) ? $author : 'Unknown Author';
                                        ?>
                                    </td>
                                    <td><?php echo isset($book['CategoryName']) ? $book['CategoryName'] : 'Unknown Category'; ?></td>
                                    <td><?php echo isset($book['DateBorrowed']) ? $book['DateBorrowed'] : 'N/A'; ?></td>
                                    <td><?php echo isset($book['DueDate']) ? $book['DueDate'] : 'N/A'; ?></td>
                                        <td>
                                            <input type="date" class="date-input" name="return_date_<?php echo $index; ?>" 
                                                value="<?php echo $book['return_date']; ?>" 
                                                min="<?php echo $book['DateBorrowed']; ?>"
                                                onchange="updateReturnDate(<?php echo $index; ?>, this.value)">
                                        </td>
                                        <td>
                                            <?php if ($book['days_overdue'] > 0): ?>
                                                <span class="status-badge status-overdue">Overdue (<?php echo $book['days_overdue']; ?> days)</span>
                                            <?php else: ?>
                                                <span class="status-badge status-active">On Time</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                if ($book['days_overdue'] > 0) {
                                                    echo '₱' . number_format($book['penalty_amount'], 2) . ' (' . number_format($book['penalty_rate'], 2) . '/day)';
                                                } else {
                                                    echo 'No Penalty';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="remove_index" value="<?php echo $index; ?>">
                                                <button type="submit" name="remove_book" class="remove-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php 
                        // Calculate total penalties
                        $total_return_penalties = 0;
                        foreach ($selected_returns as $book) {
                            if ($book['days_overdue'] > 0) {
                                $total_return_penalties += $book['penalty_amount'];
                            }
                        }
                        
                        if ($total_return_penalties > 0):
                        ?>
                        <div class="penalty-summary">
                            Total Penalties: ₱<?php echo number_format($total_return_penalties, 2); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Return Form -->
                        <form id="return_form" method="POST" action="">
                            <?php if ($valid_personnel_id): ?>
                                <input type="hidden" name="personnel_id" value="<?php echo $valid_personnel_id; ?>">
                            <?php endif; ?>
                            
                            <div class="button-group">
                                <button type="submit" name="submit_returns" class="return-btn" <?php echo (empty($selected_returns) || !$valid_personnel_id) ? 'disabled' : ''; ?>>
                                    Return <?php echo count($selected_returns); ?> Book<?php echo count($selected_returns) != 1 ? 's' : ''; ?>
                                </button>
                                <a href="loan_transactions.php?view=<?php echo $view_borrower_id; ?>&clear_returns=1" class="cancel-btn">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- TABBED INTERFACE FOR LOAN DETAILS -->
                <div class="tabs-container">
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="showTab('active-loans')">
                            Active Loans (<?php echo count($borrowed_books); ?>)
                        </button>
                        <button class="tab-btn" onclick="showTab('returned-books')">
                            Returned Books (<?php echo count($returned_books); ?>)
                        </button>
                        <button class="tab-btn" onclick="showTab('penalized-books')">
                            Penalized Books (<?php echo count($penalized_books); ?>)
                        </button>
                    </div>

                    <div class="tab-content-container">
                        <!-- ACTIVE LOANS TAB -->
                        <div id="active-loans" class="tab-content active">
                            <h3>Currently Borrowed Books</h3>
                            <?php if (!empty($borrowed_books)): ?>
                                <div class="table-responsive">
                                    <table class="books-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Category</th>
                                                <th>ISBN</th>
                                                <th>Date Borrowed</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($borrowed_books as $index => $book): ?>
                                            <tr class="<?php echo $book['days_diff'] < 0 ? 'row-overdue' : ''; ?>">
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo $book['title']; ?></td>
                                                <td><?php echo $book['author']; ?></td>
                                                <td><?php echo $book['category']; ?></td>
                                                <td><?php echo $book['isbn']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($book['date_borrowed'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($book['days_diff'] < 0) {
                                                        echo '<span class="status-badge status-overdue">Overdue (' . abs($book['days_diff']) . ' days)</span>';
                                                    } elseif ($book['days_diff'] <= 2) {
                                                        echo '<span class="status-badge status-warning">Due Soon (' . $book['days_diff'] . ' days)</span>';
                                                    } else {
                                                        echo '<span class="status-badge status-active">Active</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                <form method="POST" action="">
                                                <input type="hidden" name="transaction_id" value="<?= $book['transaction_id'] ?>">
                                                <button type="submit" name="return_book" class="return-btn">
                                                    <i class="fas fa-undo"></i> Return
                                                </button>
                                            </form>
                                            </td>
                                             
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No books currently borrowed by this borrower.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- RETURNED BOOKS TAB -->
                        <div id="returned-books" class="tab-content">
                            <h3>Returned Books</h3>
                            <?php if (!empty($returned_books)): ?>
                                <div class="table-responsive">
                                    <table class="books-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Category</th>
                                                <th>Date Borrowed</th>
                                                <th>Date Returned</th>
                                                <th>Duration</th>
                                                <th>Penalty Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($returned_books as $index => $book): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo $book['title']; ?></td>
                                                <td><?php echo $book['author']; ?></td>
                                                <td><?php echo $book['category']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($book['date_borrowed'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($book['date_returned'])); ?></td>
                                                <td><?php echo $book['loan_duration']; ?> days</td>
                                                <td>
                                                    <?php if (!empty($book['PenaltyAmount'])): ?>
                                                        <?php if ($book['PenaltyStatus'] == 'paid'): ?>
                                                            <span class="status-badge status-paid">
                                                                ₱<?php echo number_format($book['PenaltyAmount'], 2); ?> (Paid)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-badge status-unpaid">
                                                                ₱<?php echo number_format($book['PenaltyAmount'], 2); ?> (Unpaid)
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="status-badge status-no-penalty">No Penalty</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No returned books found for this borrower.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- PENALIZED BOOKS TAB -->
                        <div id="penalized-books" class="tab-content">
                            <h3>Penalized Books</h3>
                            <?php if (!empty($penalized_books)): ?>
                                <div class="table-responsive">
                                    <table class="books-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Title</th>
                                                <th>Author</th>
                                                <th>Category</th>
                                                <th>Due Date</th>
                                                <th>Return Date</th>
                                                <th>Days Overdue</th>
                                                <th>Penalty Amount</th>
                                                <th>Payment Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_penalty = 0;
                                            foreach ($penalized_books as $index => $book): 
                                                if ($book['penalty_status'] != 'paid') {
                                                    $total_penalty += $book['penalty_amount'];
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo $book['title']; ?></td>
                                                <td><?php echo $book['author']; ?></td>
                                                <td><?php echo $book['category']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($book['date_returned'])); ?></td>
                                                <td><?php echo $book['days_overdue']; ?> days</td>
                                                <td>₱<?php echo number_format($book['penalty_amount'], 2); ?></td>
                                                <td>
                                                    <form method="POST" action="" class="status-form">
                                                        <input type="hidden" name="transaction_id" value="<?php echo $book['transaction_id']; ?>">
                                                        <select name="penalty_status" onchange="this.form.submit()" class="status-select <?php echo $book['penalty_status'] == 'paid' ? 'paid' : 'unpaid'; ?>">
                                                            <option value="unpaid" <?php echo $book['penalty_status'] == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                                            <option value="paid" <?php echo $book['penalty_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td>
                                                    <button class="invoice-btn" onclick="generateInvoice(<?php echo $book['transaction_id']; ?>)">
                                                        <i class="fas fa-file-invoice"></i> Invoice
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    
                                    <!-- Total Penalty Summary -->
                                    <div class="total-penalty">
                                        <strong>Total Outstanding Penalty:</strong> ₱<?php echo number_format($total_penalty, 2); ?>
                                    </div>
                                    

                                    <!-- Invoice Generation Form -->
                                    <form id="invoice_form" method="POST" action="generate_penalty_invoice.php" target="_blank">
                                        <input type="hidden" name="borrower_id" value="<?php echo $view_borrower_id; ?>">
                                        <input type="hidden" name="transaction_ids" id="invoice_transaction_ids">
                                        <button type="button" class="generate-invoice-btn" onclick="prepareInvoice()">
                                            <i class="fas fa-file-invoice-dollar"></i> Generate Combined Invoice
                                        </button>
                                    </form>
                                </div>
                           
                                
                                <!-- Invoice Modal -->
                                <div id="invoiceModal" class="modal" style="display:none;">
                                    <div class="modal-content">
                                        <span class="close" onclick="closeModal()">&times;</span>
                                        <iframe id="invoiceFrame" style="width:100%; height:80vh; border:none;"></iframe>
                                        <button class="print-btn" onclick="printInvoice()">
                                            <i class="fas fa-print"></i> Print Invoice
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No penalties found for this borrower.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="button-group">
                    <a href="loan_transactions.php?status=<?php echo $status_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?><?php echo !empty($borrower_search) ? '&search=' . urlencode($borrower_search) : ''; ?>" class="cancel-btn">
                        <i class="fas fa-arrow-left"></i> Back to Transactions
                    </a>
                    
                    <?php if (!empty($selected_returns)): ?>
                    <button type="submit" form="return_form" name="submit_returns" class="return-btn">
                        <i class="fas fa-undo-alt"></i> Submit Returns
                    </button>
                    <?php endif; ?>
                    
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- JAVASCRIPT FUNCTIONS -->
<script>
// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Borrower search suggestions
    const borrowerSearchInput = document.getElementById('borrower-search');
    const borrowerSuggestionsContainer = document.getElementById('borrower-suggestions');
    
    if (borrowerSearchInput) {
        borrowerSearchInput.addEventListener('input', function() {
            const searchTerm = this.value;
            
            if (searchTerm.length < 2) {
                borrowerSuggestionsContainer.style.display = 'none';
                return;
            }
            
            // Fetch suggestions from server
            fetch('get_borrowers.php?term=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(data => {
                    borrowerSuggestionsContainer.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(borrower => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = borrower.value;
                            
                            div.addEventListener('click', function() {
                                borrowerSearchInput.value = borrower.id;
                                borrowerSuggestionsContainer.style.display = 'none';
                                document.querySelector('.search-form').submit();
                            });
                            
                            borrowerSuggestionsContainer.appendChild(div);
                        });
                        
                        borrowerSuggestionsContainer.style.display = 'block';
                    } else {
                        borrowerSuggestionsContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching borrower suggestions:', error);
                });
        });
    }
    
    // Close suggestions when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (borrowerSuggestionsContainer && !borrowerSearchInput.contains(e.target)) {
            borrowerSuggestionsContainer.style.display = 'none';
        }
    });
});

// Tab switching function
function showTab(tabId) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Deactivate all tab buttons
    const tabButtons = document.querySelectorAll('.tab-nav .tab-btn');
    tabButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show the selected tab
    document.getElementById(tabId).classList.add('active');
    
    // Activate the clicked button
    event.currentTarget.classList.add('active');
}

// Update return date function
function updateReturnDate(index, newDate) {
    // Create a hidden form to submit the update
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    // Add index field
    const indexField = document.createElement('input');
    indexField.type = 'hidden';
    indexField.name = 'update_return_date_index';
    indexField.value = index;
    form.appendChild(indexField);
    
    // Add date field
    const dateField = document.createElement('input');
    dateField.type = 'hidden';
    dateField.name = 'update_return_date_value';
    dateField.value = newDate;
    form.appendChild(dateField);
    
    // Submit the form
    document.body.appendChild(form);
    form.submit();
}

// Function to prepare invoice data before submission
function prepareInvoice() {
    // Get all checked or selected transaction IDs
    const transactionIds = [];
    
    // For penalized books, we'll use all unpaid transactions
    document.querySelectorAll('#penalized-books .status-select.unpaid').forEach(select => {
        const form = select.closest('form');
        const transactionId = form.querySelector('input[name="transaction_id"]').value;
        transactionIds.push(transactionId);
    });
    
    if (transactionIds.length === 0) {
        alert('No unpaid penalties found to generate invoice.');
        return;
    }
    
    // Set the transaction IDs in the form
    document.getElementById('invoice_transaction_ids').value = transactionIds.join(',');
    
    // Submit the form
    document.getElementById('invoice_form').submit();
}

// Function to generate invoice for a single transaction
function generateInvoice(transactionId) {
    // Create a temporary form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'generate_penalty_invoice.php';
    form.target = '_blank';
    
    // Add borrower ID
    const borrowerField = document.createElement('input');
    borrowerField.type = 'hidden';
    borrowerField.name = 'borrower_id';
    borrowerField.value = '<?php echo $view_borrower_id; ?>';
    form.appendChild(borrowerField);
    
    // Add transaction ID
    const transactionField = document.createElement('input');
    transactionField.type = 'hidden';
    transactionField.name = 'transaction_id';
    transactionField.value = transactionId;
    form.appendChild(transactionField);
    
    // Submit the form
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Modal functions
function openModal(content) {
    document.getElementById('invoiceContent').innerHTML = content;
    document.getElementById('invoiceModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}

function printInvoice() {
    window.print();
}


</script>

<?php
// Close database connection
$conn->close();
?>