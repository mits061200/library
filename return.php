<?php
// First line - enable output buffering
ob_start();
// Start session at the very beginning of the script
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include 'header.php';
include 'navbar.php';

// Get all available penalties for dropdown
$penalties = [];
$penalty_query = "SELECT * FROM penalty";
$penalty_result = $conn->query($penalty_query);
if ($penalty_result && $penalty_result->num_rows > 0) {
    while ($row = $penalty_result->fetch_assoc()) {
        $penalties[$row['PenaltyID']] = [
            'name' => $row['PenaltyName'],
            'rate' => $row['PenaltyRate'],
            'duration' => $row['Duration']
        ];
    }
}

// Check if this is a fresh page load (not a form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Clear transaction data from session
    unset($_SESSION['return_transaction']);
}

?>
<link rel="stylesheet" href="css/loan.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php

// Initialize variables
$borrower_data = [];
$borrowed_books = [];
$return_transaction = [];
$borrower_search = '';
$error_message = '';
$success_message = '';
$valid_personnel_id = null;

// Get return transaction from session if available
if (isset($_SESSION['return_transaction'])) {
    $return_transaction = $_SESSION['return_transaction'];
}

// First, fetch a valid personnel ID that we can use for returns
$personnel_query = "SELECT PersonnelID FROM personnel LIMIT 1";
$personnel_result = $conn->query($personnel_query);
if ($personnel_result && $personnel_result->num_rows > 0) {
    $personnel_row = $personnel_result->fetch_assoc();
    $valid_personnel_id = $personnel_row['PersonnelID'];
}

// Get all available penalties for dropdown
$penalties = [];
$penalty_query = "SELECT * FROM penalty";
$penalty_result = $conn->query($penalty_query);
if ($penalty_result && $penalty_result->num_rows > 0) {
    while ($row = $penalty_result->fetch_assoc()) {
        $penalties[$row['PenaltyID']] = [
            'name' => $row['PenaltyName'],
            'rate' => $row['PenaltyRate'],
            'duration' => $row['Duration']
        ];
    }
}

// Handle borrower search
if (isset($_POST['search_borrower']) || isset($_POST['borrower_id'])) {
    // Get borrower ID either from search or from hidden field
    $borrower_id = isset($_POST['borrower_id']) ? $conn->real_escape_string($_POST['borrower_id']) : 
                 (isset($_POST['borrower']) ? $conn->real_escape_string($_POST['borrower']) : '');
    
    if (!empty($borrower_id)) {
        // First try direct ID match
        $sql = "SELECT * FROM borrowers WHERE BorrowerID = '$borrower_id' LIMIT 1";
        $result = $conn->query($sql);
        
        // If no direct match, try the search terms
        if (!$result || $result->num_rows == 0) {
            $sql = "SELECT * FROM borrowers WHERE 
                    BorrowerID LIKE '%$borrower_id%' OR 
                    FirstName LIKE '%$borrower_id%' OR 
                    LastName LIKE '%$borrower_id%' 
                    LIMIT 1";
                    
            $result = $conn->query($sql);
        }
        
        if ($result && $result->num_rows > 0) {
            $borrower_data = $result->fetch_assoc();
            
         // Fetch borrowed books for this borrower
            $sql = "SELECT l.*, b.Title, b.ISBN, b.CallNumber, b.Price, b.AccessionNumber, 
                    a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, 
                    a.LastName AS AuthorLastName, m.MaterialName AS Category,
                    loc.LocationName
                    FROM loan l
                    JOIN book b ON l.BookID = b.BookID
                    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                    LEFT JOIN material m ON b.MaterialID = m.MaterialID 
                    LEFT JOIN location loc ON b.LocationID = loc.LocationID
                    WHERE l.BorrowerID = '{$borrower_data['BorrowerID']}' 
                    AND l.Status = 'borrowed'";

            $books_result = $conn->query($sql);

            if ($books_result && $books_result->num_rows > 0) {
            while($book = $books_result->fetch_assoc()) {
            // Calculate days overdue
            $due_date = new DateTime($book['DueDate']);
            $today = new DateTime(date('Y-m-d'));
            $days_difference = $today->diff($due_date)->format('%r%a');

            // Initialize penalty fields
            $book['is_overdue'] = false;
            $book['days_overdue'] = 0;
            $book['penalty_amount'] = 0;
            $book['penalty_type'] = 'none';
            $book['return_date'] = date('Y-m-d');
            $book['is_lost'] = false;

            // Check if overdue and borrower is a student
            if ($days_difference < 0 && strtolower($borrower_data['Role']) === 'student') {
                $book['is_overdue'] = true;
                $book['days_overdue'] = abs($days_difference);
                
                // Find overdue penalty and calculate the amount
                foreach ($penalties as $pid => $penalty) {
                    if (strtolower($penalty['name']) === 'overdue') {
                        $book['penalty_amount'] = $penalty['rate'] * $book['days_overdue'];
                        $book['penalty_type'] = 'overdue';
                        $book['penalty_id'] = $pid;
                        break;
                    }
                }
            }

        $borrowed_books[] = $book;
            }
        }
        else {
        $error_message = "No borrowed books found for this borrower.";
        }
        } else if (isset($_POST['search_borrower'])) { // Only show error if explicitly searching
            $error_message = "Borrower not found";
        }
    }
}

// Handle selecting a book for return
if (isset($_POST['select_book']) && isset($_POST['transaction_id'])) {
    $transaction_id = intval($_POST['transaction_id']);
    
    // Find the book in borrowed books
    foreach ($borrowed_books as $book) {
        if ($book['TransactionID'] == $transaction_id) {
            // Create a copy of the book for return transaction
            $selected_book = $book;
            
            // Only apply penalties if borrower is a student
            if ($selected_book['is_overdue'] && strtolower($borrower_data['Role']) === 'student') {
                $selected_book['penalty_type'] = 'overdue';
                
                foreach ($penalties as $pid => $penalty) {
                    if (strtolower($penalty['name']) === 'overdue') {
                        $selected_book['penalty_id'] = $pid;
                        $selected_book['penalty_amount'] = $penalty['rate'] * $selected_book['days_overdue'];
                        break;
                    }
                }
            }
            
            // Check if book is already in return transaction
            $book_exists = false;
            foreach ($return_transaction as $existing_book) {
                if ($existing_book['TransactionID'] == $transaction_id) {
                    $book_exists = true;
                    break;
                }
            }
            
            if (!$book_exists) {
                $return_transaction[] = $selected_book;
                $_SESSION['return_transaction'] = $return_transaction;
                $success_message = "Book added to return transaction.";
            } else {
                $error_message = "This book is already in your return transaction.";
            }
            
            break;
        }
    }
}

// Handle removing a book from return transaction
if (isset($_POST['remove_book']) && isset($_POST['remove_index'])) {
    $index = intval($_POST['remove_index']);
    if (isset($return_transaction[$index])) {
        array_splice($return_transaction, $index, 1);
        $_SESSION['return_transaction'] = $return_transaction;
        $success_message = "Book removed from return transaction.";
    }
}

// Handle updating return status (normal, lost, damaged)
if (isset($_POST['update_status_index']) && isset($_POST['update_status_value'])) {
    $index = intval($_POST['update_status_index']);
    $status = $_POST['update_status_value'];
    
    if (isset($return_transaction[$index])) {
        // Update the status
        $return_transaction[$index]['penalty_type'] = $status;
        
        // Special handling for lost books
        if ($status === 'lost') {
            $return_transaction[$index]['is_lost'] = true;
            foreach ($penalties as $pid => $penalty) {
                if (strtolower($penalty['name']) === 'lost') {
                    $return_transaction[$index]['penalty_id'] = $pid;
                    $return_transaction[$index]['penalty_amount'] = $return_transaction[$index]['Price'];
                    break;
                }
            }
        } 
        // For overdue books, maintain the overdue status
        else if ($return_transaction[$index]['is_overdue'] && $status !== 'lost') {
            $return_transaction[$index]['is_lost'] = false;
            $return_transaction[$index]['penalty_type'] = 'overdue';
            
            // Recalculate overdue penalty
            foreach ($penalties as $pid => $penalty) {
                if (strtolower($penalty['name']) === 'overdue') {
                    $return_transaction[$index]['penalty_id'] = $pid;
                    $return_transaction[$index]['penalty_amount'] = $penalty['rate'] * 
                        max(0, ($return_transaction[$index]['days_overdue'] - $penalty['duration']));
                    break;
                }
            }
        }
        // For normal returns with no overdue
        else {
            $return_transaction[$index]['is_lost'] = false;
            $return_transaction[$index]['penalty_amount'] = 0;
            $return_transaction[$index]['penalty_type'] = 'none';
        }
        
        $_SESSION['return_transaction'] = $return_transaction;
        $success_message = "Status updated successfully.";
    }
}

// Handle updating return date
if (isset($_POST['update_return_date_index']) && isset($_POST['update_return_date_value'])) {
    $index = intval($_POST['update_return_date_index']);
    $return_date = $_POST['update_return_date_value'];
    
    if (isset($return_transaction[$index])) {
        // Update the return date in the session
        $return_transaction[$index]['return_date'] = $return_date;
        
        // Recalculate overdue days and penalty
        $due_date = new DateTime($return_transaction[$index]['DueDate']);
        $return_date_obj = new DateTime($return_date);
        $days_difference = $return_date_obj->diff($due_date)->format('%r%a');
        
        // Reset penalty information
        $return_transaction[$index]['is_overdue'] = false;
        $return_transaction[$index]['days_overdue'] = 0;
        $return_transaction[$index]['penalty_amount'] = 0;
        $return_transaction[$index]['penalty_type'] = 'none';
        
        // Check if overdue based on return date
        if ($days_difference < 0) {
            $return_transaction[$index]['is_overdue'] = true;
            $return_transaction[$index]['days_overdue'] = abs($days_difference);
            
            // Find overdue penalty
            foreach ($penalties as $pid => $penalty) {
                if (strtolower($penalty['name']) === 'overdue') {
                    $return_transaction[$index]['penalty_id'] = $pid;
                    
                    // Calculate penalty amount if overdue is beyond grace period
                    $return_transaction[$index]['penalty_amount'] = $penalty['rate'] * 
                        max(0, ($return_transaction[$index]['days_overdue'] - $penalty['duration']));
                    $return_transaction[$index]['penalty_type'] = 'overdue';
                    break;
                }
            }
        }
        
        $_SESSION['return_transaction'] = $return_transaction;
        $success_message = "Return date updated successfully.";
    }
}

/// Process return submission
if (isset($_POST['submit_return']) && !empty($return_transaction) && !empty($borrower_data)) {
    // Validate required fields
    if ($valid_personnel_id === null) {
        $error_message = "No valid personnel found in the system. Please add personnel before processing returns.";
    } else {
        $return_success = true;
        $return_errors = [];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            foreach ($return_transaction as $book) {
                $transaction_id = $book['TransactionID'];
                $book_id = $book['BookID'];
                $return_date = $conn->real_escape_string($book['return_date']);
                $penalty_type = $book['penalty_type'];
                $penalty_amount = $book['penalty_amount'];
                $penalty_id = isset($book['penalty_id']) ? $book['penalty_id'] : null;
                
                // Determine new status for the loan
                if ($book['penalty_amount'] > 0 && strtolower($borrower_data['Role']) === 'student')
                    $new_loan_status = 'penalized';
                    
                     // Insert into PenaltyTransaction
                    $penalty_sql = "INSERT INTO PenaltyTransaction (LoanID, PenaltyID, PenaltyAmount, PenaltyType, DateIssued, Remarks) 
                    VALUES ('$transaction_id', '$penalty_id', '$penalty_amount', 
                    '$penalty_type', '$return_date', 'Days Overdue: {$book['days_overdue']}')";
 
                    if (!$conn->query($penalty_sql)) {
                        throw new Exception("Error recording penalty for transaction ID $transaction_id: " . $conn->error);
                    }
                    
                    $sql = "UPDATE loan SET 
                            DateReturned = '$return_date', 
                            Status = '$new_loan_status', 
                            PenaltyID = '$penalty_id'
                            WHERE TransactionID = '$transaction_id'";
                    } else {
                    $new_loan_status = 'returned';
                    $sql = "UPDATE loan SET 
                            DateReturned = '$return_date', 
                            Status = '$new_loan_status'
                            WHERE TransactionID = '$transaction_id'";
                    }
                
                if (!$conn->query($sql)) {
                    throw new Exception("Error updating loan record for transaction ID $transaction_id: " . $conn->error);
                }
                
                // Update book status
                $book_status = 'Available';
                if ($book['is_lost']) {
                    // If book is lost, update the total copies (reduce by 1)
                    $update_book_sql = "UPDATE book SET 
                                       HoldCopies = CASE WHEN HoldCopies > 0 THEN HoldCopies - 1 ELSE 0 END
                                       WHERE BookID = '$book_id'";
                } else {
                    // If book is returned normally, update the status
                    $update_book_sql = "UPDATE book SET 
                                       Status = '$book_status'
                                       WHERE BookID = '$book_id'";
                }
                
                if (!$conn->query($update_book_sql)) {
                    throw new Exception("Error updating book status for book ID $book_id: " . $conn->error);
                }
            }
            
            $conn->commit();
            $success_message = count($return_transaction) . " book(s) returned successfully!";
            
            // Clear session data after successful return
            $return_transaction = [];
            $_SESSION['return_transaction'] = [];
            
            // Redirect to prevent resubmission
            header("Location: return.php?success=1");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// Success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Books returned successfully!";
}

// Get today's date
$today = date('Y-m-d');
?>

<main class="content">
    <div class="loan-section">
        <div class="classification-container">
            <h2>Return</h2>
            <!-- Header Tabs -->
            <div class="tab-buttons">
                <button class="tab-btn" onclick="window.location.href='loan.php'">Loan</button>
                <span class="arrow">&gt;</span>
                <button class="tab-btn active">Return</button>
                <span class="arrow">&gt;</span>
                <button class="tab-btn" onclick="window.location.href='loan_transactions.php'">Loan Transactions</button>
            </div>

            <!-- Borrower Search -->
            <h3 class="section-title">Borrower's Information</h3>
            <form class="search-form" method="POST" action="">
                <i class="fas fa-search"></i>
                <div class="search-container">
                    <input type="text" class="search-input" id="borrower-search" name="borrower" placeholder="Search by ID, First Name, or Last Name" value="<?php echo isset($_POST['borrower']) ? htmlspecialchars($_POST['borrower']) : ''; ?>">
                    <div id="borrower-suggestions" class="suggestions-container"></div>
                </div>
                <button type="submit" name="search_borrower" class="search-btn">Search</button>
            </form>

            <!-- Borrower Info -->
            <div class="borrower-info">
                <?php if (!empty($borrower_data)): ?>
                    <p><strong>ID no.:</strong> <?php echo $borrower_data['BorrowerID']; ?> <span class="gap"></span> <strong>Role:</strong> <?php echo $borrower_data['Role']; ?></p>
                    <p><strong>First Name:</strong> <?php echo $borrower_data['FirstName']; ?> <span class="gap"></span> <strong>Contact Number:</strong> <?php echo $borrower_data['ContactNumber']; ?></p>
                    <p><strong>Middle Name:</strong> <?php echo $borrower_data['MiddleName']; ?> <span class="gap"></span> <strong>Latest Penalty:</strong> 
                    <?php 
                        $penalty_query = "SELECT SUM(pt.PenaltyAmount) as TotalPenalty 
                        FROM loan bl 
                        JOIN PenaltyTransaction pt ON bl.TransactionID = pt.LoanID 
                        WHERE bl.BorrowerID = '{$borrower_data['BorrowerID']}' 
                        AND bl.Status = 'penalized'";
                        $penalty_result = $conn->query($penalty_query);
                        $penalty_amount = 0;
                        if ($penalty_result && $penalty_result->num_rows > 0) {
                            $penalty_row = $penalty_result->fetch_assoc();
                            $penalty_amount = $penalty_row['TotalPenalty'] ?: 0;
                        }
                        echo number_format($penalty_amount, 2);
                    ?>
                    </p>
                    <p><strong>Last Name:</strong> <?php echo $borrower_data['LastName']; ?></p>
                <?php else: ?>
                    <p><strong>ID no.:</strong> <span class="gap"></span> <strong>Role:</strong></p>
                    <p><strong>First Name:</strong> <span class="gap"></span> <strong>Contact Number:</strong></p>
                    <p><strong>Middle Name:</strong> <span class="gap"></span> <strong>Latest Penalty:</strong> 0.00</p>
                    <p><strong>Last Name:</strong></p>
                <?php endif; ?>
            </div>

<!-- Return Books Selection -->
<?php if (!empty($return_transaction)): ?>
<div class="selected-books-section">
    <h3 class="section-title">Books to Return (<?php echo count($return_transaction); ?>)</h3>
    <div class="selected-books-list">
        <table class="selected-books-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Date Borrowed</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Penalty</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Initialize total penalty amount
                $total_penalty = 0;
                
                foreach ($return_transaction as $index => $book): 
                    // Recalculate penalty for each book based on its return date and status
                    $due_date = new DateTime($book['DueDate']);
                    $return_date_obj = new DateTime($book['return_date']);
                    $days_difference = $return_date_obj->diff($due_date)->format('%r%a');
                    
                    // Check if overdue based on return date
                    $is_overdue = ($days_difference < 0);
                    $days_overdue = $is_overdue ? abs($days_difference) : 0;
                    
                    // Update book properties
                    $return_transaction[$index]['is_overdue'] = $is_overdue;
                    $return_transaction[$index]['days_overdue'] = $days_overdue;
                    
                    // Only update the penalty amount if not lost and is overdue
                    if (!$book['is_lost'] && $is_overdue) {
                        // Find overdue penalty and calculate the amount (no grace period)
                        foreach ($penalties as $pid => $penalty) {
                            if (strtolower($penalty['name']) === 'overdue') {
                                $return_transaction[$index]['penalty_amount'] = $penalty['rate'] * $days_overdue;
                                $return_transaction[$index]['penalty_type'] = 'overdue';
                                $return_transaction[$index]['penalty_id'] = $pid;
                                break;
                            }
                        }
                    }
                    
                    // Add to total penalty amount
                    $total_penalty += $return_transaction[$index]['penalty_amount'];
                    
                    // Store updated book data back in session
                    $_SESSION['return_transaction'] = $return_transaction;
                    
                    // For display use the updated values
                    $book = $return_transaction[$index];
                ?>
                <tr class="<?php echo $is_overdue ? 'overdue' : ''; ?> <?php echo $book['is_lost'] ? 'lost' : ''; ?>">
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $book['Title']; ?></td>
                    <td>
                        <?php 
                            $author = $book['AuthorFirstName'];
                            if (!empty($book['AuthorMiddleName'])) {
                                $author .= ' ' . $book['AuthorMiddleName'];
                            }
                            $author .= ' ' . $book['AuthorLastName'];
                            echo $author;
                        ?>
                    </td>
                    <td><?php echo $book['DateBorrowed']; ?></td>
                    <td><?php echo $book['DueDate']; ?></td>
                    <td>
                        <input type="date" class="date-input" name="return_date_<?php echo $index; ?>" 
                                value="<?php echo $book['return_date']; ?>" 
                                max="<?php echo $today; ?>"
                                onchange="updateReturnDate(<?php echo $index; ?>, this.value)">
                    </td>
                    <td>
                        <select class="status-select" name="status_<?php echo $index; ?>" 
                                onchange="updateReturnStatus(<?php echo $index; ?>, this.value)">
                            <option value="none" <?php echo (!$is_overdue && $book['penalty_type'] !== 'lost') ? 'selected' : ''; ?>>Normal</option>
                            <?php if ($is_overdue): ?>
                            <option value="overdue" <?php echo $is_overdue ? 'selected' : ''; ?>>Overdue</option>
                            <?php endif; ?>
                            <option value="lost" <?php echo $book['penalty_type'] === 'lost' ? 'selected' : ''; ?>>Lost</option>
                        </select>
                    </td>
                    <td>
                        <?php 
                        if ($book['is_overdue']) {
                            if (strtolower($borrower_data['Role']) === 'student') {
                                echo "₱" . number_format($book['penalty_amount'], 2);
                                echo "<br><small>({$book['days_overdue']} days)</small>";
                            } else {
                                echo "Exempt (Faculty/Employee)";
                            }
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="remove_index" value="<?php echo $index; ?>">
                            <!-- Add hidden field to preserve borrower ID -->
                            <?php if (!empty($borrower_data)): ?>
                            <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                            <?php endif; ?>
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
    
    <!-- Show total penalty - made more prominent -->
    <?php if ($total_penalty > 0): ?>
    <div class="alert alert-warning" style="margin-top: 15px; font-size: 1.1em; padding: 10px; text-align: right;">
        <strong>Total Penalty Amount:</strong> ₱<?php echo number_format($total_penalty, 2); ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
                <!-- Currently Borrowed Books Section -->
                <?php if (!empty($borrowed_books)): ?>
                <div class="currently-borrowed-section">
                    <h3 class="section-title">Currently Borrowed Books (<?php echo count($borrowed_books); ?>)</h3>
                    <div class="borrowed-books-list">
                        <table class="borrowed-books-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Date Borrowed</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Penalty</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowed_books as $index => $book): 
                                    // Check if this book is already in the return transaction
                                    $already_selected = false;
                                    foreach ($return_transaction as $selected_book) {
                                        if ($selected_book['TransactionID'] == $book['TransactionID']) {
                                            $already_selected = true;
                                            break;
                                        }
                                    }
                                    
                                    if (!$already_selected):
                                ?>
                                <tr class="<?php echo $book['is_overdue'] ? 'overdue' : ''; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $book['Title']; ?></td>
                                    <td>
                                        <?php 
                                            $author = $book['AuthorFirstName'];
                                            if (!empty($book['AuthorMiddleName'])) {
                                                $author .= ' ' . $book['AuthorMiddleName'];
                                            }
                                            $author .= ' ' . $book['AuthorLastName'];
                                            echo $author;
                                        ?>
                                    </td>
                                    <td><?php echo $book['DateBorrowed']; ?></td>
                                    <td><?php echo $book['DueDate']; ?></td>
                                    <td>
                                    <?php 
                                        if ($book['is_overdue']) {
                                            echo '<span style="color:red">Overdue</span>';
                                            if ($book['days_overdue'] > 0) {
                                                echo '<br><small>(' . $book['days_overdue'] . ' days)</small>';
                                            }
                                        } else {
                                            echo 'Active';
                                        }
                                    ?>
                                    </td>
                                    <td>
                                    <?php 
                                          if ($book['is_overdue']) {
                                            echo "₱" . number_format($book['penalty_amount'], 2);
                                            echo "<br><small>({$book['days_overdue']} days)</small>";
                                        } else {
                                            echo "-";
                                        }
                                    ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="transaction_id" value="<?php echo $book['TransactionID']; ?>">
                                            <!-- Add hidden field to preserve borrower ID -->
                                            <?php if (!empty($borrower_data)): ?>
                                            <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                                            <?php endif; ?>
                                            <button type="submit" name="select_book" class="add-btn">
                                                <i class="fas fa-plus"></i> Select
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

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

            <!-- Action Buttons -->
            <form id="return_form" method="POST" action="">
                <!-- Add hidden field for personnel ID -->
                <?php if ($valid_personnel_id): ?>
                    <input type="hidden" name="personnel_id" value="<?php echo $valid_personnel_id; ?>">
                <?php endif; ?>
                
                <!-- Add hidden field to preserve borrower ID -->
                <?php if (!empty($borrower_data)): ?>
                <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                <?php endif; ?>
                
                <div class="button-group">
                    <button type="submit" name="submit_return" class="add-btn" <?php echo (empty($borrower_data) || empty($return_transaction) || !$valid_personnel_id) ? 'disabled' : ''; ?>>
                        Return <?php echo count($return_transaction); ?> Book<?php echo count($return_transaction) != 1 ? 's' : ''; ?>
                    </button>
                    <a href="return.php?clear=1" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Borrower Search Suggestions
    const borrowerSearchInput = document.getElementById('borrower-search');
    const borrowerSuggestionsContainer = document.getElementById('borrower-suggestions');
    let selectedBorrowerSuggestionIndex = -1;
    
    // Function to get borrower suggestions
    function getBorrowerSuggestions(searchTerm) {
        if (searchTerm.length < 2) {
            borrowerSuggestionsContainer.style.display = 'none';
            return;
        }
        
        fetch('get_borrowers.php?term=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(data => {
                borrowerSuggestionsContainer.innerHTML = '';
                
                if (data.length > 0) {
                    data.forEach((borrower, index) => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.textContent = borrower.value;
                        div.setAttribute('data-id', borrower.id);
                        
                        // Store all needed data as attributes
                        Object.keys(borrower).forEach(key => {
                            if (borrower[key] !== null) {
                                div.setAttribute('data-' + key.replace(/_/g, '-'), borrower[key]);
                            }
                        });
                        
                        div.addEventListener('click', function() {
                            borrowerSearchInput.value = borrower.id;
                            borrowerSuggestionsContainer.style.display = 'none';
                            
                            // Submit the form
                            const borrowerForm = document.querySelectorAll('form.search-form')[0];
                            
                            // Add hidden input to trigger the search
                            let searchTrigger = document.createElement('input');
                            searchTrigger.type = 'hidden';
                            searchTrigger.name = 'search_borrower';
                            searchTrigger.value = '1';
                            borrowerForm.appendChild(searchTrigger);
                            
                            // Submit form
                            borrowerForm.submit();
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
    }
    
    // Event listeners for borrower search
    if (borrowerSearchInput) {
        borrowerSearchInput.addEventListener('input', function() {
            getBorrowerSuggestions(this.value);
            selectedBorrowerSuggestionIndex = -1;
        });
    }

    // Update return status function
    window.updateReturnStatus = function(index, value) {
        // Create a form to update the return status
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        // Add hidden inputs
        const indexInput = document.createElement('input');
        indexInput.type = 'hidden';
        indexInput.name = 'update_status_index';
        indexInput.value = index;
        form.appendChild(indexInput);
        
        const valueInput = document.createElement('input');
        valueInput.type = 'hidden';
        valueInput.name = 'update_status_value';
        valueInput.value = value;
        form.appendChild(valueInput);
        
        // Preserve borrower ID if already selected
        const borrowerIdField = document.querySelector('input[name="borrower_id"]');
        if (borrowerIdField) {
            const borrowerIdInput = document.createElement('input');
            borrowerIdInput.type = 'hidden';
            borrowerIdInput.name = 'borrower_id';
            borrowerIdInput.value = borrowerIdField.value;
            form.appendChild(borrowerIdInput);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    };

    // Update return date function
    window.updateReturnDate = function(index, value) {
        // Create a form to update the return date
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        // Add hidden inputs
        const indexInput = document.createElement('input');
        indexInput.type = 'hidden';
        indexInput.name = 'update_return_date_index';
        indexInput.value = index;
        form.appendChild(indexInput);
        
        const valueInput = document.createElement('input');
        valueInput.type = 'hidden';
        valueInput.name = 'update_return_date_value';
        valueInput.value = value;
        form.appendChild(valueInput);
        
        // Preserve borrower ID if already selected
        const borrowerIdField = document.querySelector('input[name="borrower_id"]');
        if (borrowerIdField) {
            const borrowerIdInput = document.createElement('input');
            borrowerIdInput.type = 'hidden';
            borrowerIdInput.name = 'borrower_id';
            borrowerIdInput.value = borrowerIdField.value;
            form.appendChild(borrowerIdInput);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    };

});
</script>

<?php
// Close the database connection
$conn->close();
?>