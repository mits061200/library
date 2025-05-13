<?php
ob_start();
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include 'header.php';
include 'navbar.php';

if (!empty($_SESSION['current_borrower_id'])) {
    $borrower_id = $_SESSION['current_borrower_id'];
    // Re-fetch borrower data if needed
    $sql = "SELECT * FROM borrowers WHERE BorrowerID = '$borrower_id' LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $borrower_data = $result->fetch_assoc();
        $_SESSION['borrower_data'] = $borrower_data;
    }
    unset($_SESSION['current_borrower_id']);
}

// Check if this is a fresh page load (not a form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Clear borrower and selected books data from session
    unset($_SESSION['borrower_data']);
    unset($_SESSION['selected_books']);
}

// Initialize variables
$borrower_data = [];
$book_data = [];
$selected_books = [];
$error_message = '';
$success_message = '';
$total_borrowed = 0;
$total_borrowed_fiction = 0;
$total_borrowed_other = 0;

// Maximum borrowing limits
$MAX_TOTAL_BOOKS = 4;
$MAX_FICTION_BOOKS = 2;
$MAX_OTHER_BOOKS = 2;

// Get selected books from session if available
if(isset($_SESSION['selected_books'])) {
    $selected_books = $_SESSION['selected_books'];
}

// Get today's date and default due date
$today = date('Y-m-d');
$default_loan_duration = 3; // Default fallback
$default_due_date = date('Y-m-d', strtotime("+$default_loan_duration days"));

// Get valid personnel ID for loans
$valid_personnel_id = null;
$personnel_query = "SELECT PersonnelID FROM personnel LIMIT 1";
$personnel_result = $conn->query($personnel_query);
if ($personnel_result && $personnel_result->num_rows > 0) {
    $personnel_row = $personnel_result->fetch_assoc();
    $valid_personnel_id = $personnel_row['PersonnelID'];
}

// Handle borrower search
if (isset($_POST['search_borrower']) || isset($_POST['borrower_id']) || isset($_GET['borrower_id'])) {
    $borrower_id = '';
    
    if (isset($_POST['borrower_id'])) {
        $borrower_id = $conn->real_escape_string($_POST['borrower_id']);
    } elseif (isset($_GET['borrower_id'])) {
        $borrower_id = $conn->real_escape_string($_GET['borrower_id']);
    } elseif (isset($_POST['borrower'])) {
        $borrower_id = $conn->real_escape_string($_POST['borrower']);
    }
    
    if (!empty($borrower_id)) {
        // Search for borrower by exact ID first
        $sql = "SELECT * FROM borrowers WHERE BorrowerID = '$borrower_id' LIMIT 1";
        $result = $conn->query($sql);
        
        // If not found, search by partial match
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
            $_SESSION['borrower_data'] = $borrower_data;
        } elseif (isset($_POST['search_borrower'])) {
            $error_message = "Borrower not found";
        }
    }
} elseif (isset($_SESSION['borrower_data'])) {
    $borrower_data = $_SESSION['borrower_data'];
}

// Handle updating book dates
if (isset($_POST['update_date_index']) && isset($_POST['update_date_type']) && isset($_POST['update_date_value'])) {
    $index = intval($_POST['update_date_index']);
    $type = $_POST['update_date_type'];
    $value = $_POST['update_date_value'];
    
    if (isset($_SESSION['selected_books'][$index])) {
        $_SESSION['selected_books'][$index][$type] = $value;
        
        if ($type === 'date_loan') {
            $loan_date = new DateTime($value);
            $book_category = $_SESSION['selected_books'][$index]['CategoryID'];
            
            $duration_query = "SELECT Duration FROM penalty WHERE ";
            if ($book_category == 1) {
                $duration_query .= "PenaltyName = 'Overdue (Fiction)' LIMIT 1";
            } else {
                $duration_query .= "PenaltyName = 'Overdue' LIMIT 1";
            }
            
            $duration_result = $conn->query($duration_query);
            $loan_duration = $default_loan_duration;
            if ($duration_result && $duration_result->num_rows > 0) {
                $loan_duration = intval($duration_result->fetch_assoc()['Duration']);
            }
            
            $loan_date->modify("+$loan_duration days");
            $_SESSION['selected_books'][$index]['due_date'] = $loan_date->format('Y-m-d');
        }
        
        $success_message = "Date updated successfully.";
        $selected_books = $_SESSION['selected_books'];
    }
}

// Get categories and locations for dropdowns
$categories = [];
$locations = [];

$sql = "SELECT DISTINCT CategoryID FROM book";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['CategoryID'];
    }
}

$sql = "SELECT LocationID, LocationName FROM location";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[$row['LocationID']] = $row['LocationName'];
    }
}

// Handle book search
if (isset($_POST['search_book']) || isset($_POST['book_id'])) {
    $book_id = isset($_POST['book_id']) ? $conn->real_escape_string($_POST['book_id']) : 
               (isset($_POST['book']) ? $conn->real_escape_string($_POST['book']) : '');
    
    $category_filter = (isset($_POST['category']) && $_POST['category'] !== 'All Categories') ? 
                      "AND b.CategoryID = " . intval($_POST['category']) : "";
    $location_filter = (isset($_POST['location']) && $_POST['location'] !== 'All Location') ? 
                      "AND b.LocationID = " . intval($_POST['location']) : "";
    
    if (!empty($book_id)) {
        $sql = "SELECT b.*, a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, 
                a.LastName AS AuthorLastName, m.MaterialName AS Category,
                mc.ClassificationNumber, mc.Description AS Classification,
                l.LocationName, c.CategoryName
                FROM book b
                LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                LEFT JOIN material m ON b.MaterialID = m.MaterialID
                LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
                LEFT JOIN location l ON b.LocationID = l.LocationID
                LEFT JOIN category c ON b.CategoryID = c.CategoryID
                WHERE b.BookID = '$book_id'
                AND b.Status = 'Available'
                LIMIT 1";
        
        $result = $conn->query($sql);
        
        if (!$result || $result->num_rows == 0) {
            $sql = "SELECT b.*, a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, 
                    a.LastName AS AuthorLastName, m.MaterialName AS Category,
                    mc.ClassificationNumber, mc.Description AS Classification,
                    l.LocationName, c.CategoryName
                    FROM book b
                    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                    LEFT JOIN material m ON b.MaterialID = m.MaterialID
                    LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
                    LEFT JOIN location l ON b.LocationID = l.LocationID
                    LEFT JOIN category c ON b.CategoryID = c.CategoryID
                    WHERE (b.Title LIKE '%$book_id%' OR 
                          b.ISBN LIKE '%$book_id%' OR
                          b.AccessionNumber LIKE '%$book_id%')
                          $category_filter
                          $location_filter
                          AND b.Status = 'Available'
                    LIMIT 1";
            
            $result = $conn->query($sql);
        }
        
        if ($result && $result->num_rows > 0) {
            $book_data = $result->fetch_assoc();
        } else if (isset($_POST['search_book'])) {
            $error_message = "Book not found or not available";
        }
    }
}


// Count selected books by category
$selected_fiction_count = 0;
$selected_other_count = 0;
foreach ($selected_books as $book) {
    if ($book['CategoryID'] == 1) {
        $selected_fiction_count++;
    } else {
        $selected_other_count++;
    }
}
// Calculate available slots considering both currently borrowed and selected books
$available_total = $MAX_TOTAL_BOOKS - ($total_borrowed + count($selected_books));
$available_fiction = $MAX_FICTION_BOOKS - ($total_borrowed_fiction + $selected_fiction_count);
$available_other = $MAX_OTHER_BOOKS - ($total_borrowed_other + $selected_other_count);

// Handle adding a book to selection
if (isset($_POST['add_book']) && !empty($book_data)) {
    $book_exists = false;
    $category_limit_reached = false;
    $already_borrowed = false;
    $max_loans_reached = false;
    
    // Check if book already exists in selection
    foreach ($selected_books as $book) {
        if ($book['BookID'] == $book_data['BookID']) {
            $book_exists = true;
            break;
        }
    }
    
    // Check if adding this book would exceed category limits
    if ($book_data['CategoryID'] == 1) {
        if (($total_borrowed_fiction + $selected_fiction_count) >= $MAX_FICTION_BOOKS) {
            $category_limit_reached = true;
            $error_message = "Maximum limit of $MAX_FICTION_BOOKS fiction books reached.";
        }
    } else {
        if (($total_borrowed_other + $selected_other_count) >= $MAX_OTHER_BOOKS) {
            $category_limit_reached = true;
            $error_message = "Maximum limit of $MAX_OTHER_BOOKS non-fiction books reached.";
        }
    }

    if (!empty($borrower_data)) {
        // Check if book is already borrowed by this borrower
        $check_loan_sql = "SELECT * FROM loan 
                          WHERE BorrowerID = '{$borrower_data['BorrowerID']}' 
                          AND BookID = '{$book_data['BookID']}' 
                          AND Status = 'borrowed'";
        $check_loan_result = $conn->query($check_loan_sql);
        if ($check_loan_result && $check_loan_result->num_rows > 0) {
            $already_borrowed = true;
            $error_message = "This book is already borrowed by this borrower.";
        }
        
        // Check if total limit is reached
        if (($total_borrowed + count($selected_books)) >= $MAX_TOTAL_BOOKS) {
            $max_loans_reached = true;
            $error_message = "Maximum limit of $MAX_TOTAL_BOOKS total books reached.";
        }
    }
    
    if (!$book_exists && !$already_borrowed && !$max_loans_reached && !$category_limit_reached) {
        $loan_duration = $default_loan_duration;
        $duration_query = "SELECT Duration FROM penalty WHERE ";
        
        if ($book_data['CategoryID'] == 1) {
            $duration_query .= "PenaltyName = 'Overdue (Fiction)' LIMIT 1";
        } else {
            $duration_query .= "PenaltyName = 'Overdue' LIMIT 1";
        }
        
        $duration_result = $conn->query($duration_query);
        if ($duration_result && $duration_result->num_rows > 0) {
            $loan_duration = intval($duration_result->fetch_assoc()['Duration']);
        }
        
        $book_data['date_loan'] = $today;
        $book_data['due_date'] = date('Y-m-d', strtotime("+$loan_duration days"));
        
        $selected_books[] = $book_data;
        $_SESSION['selected_books'] = $selected_books;
        
        
        $success_message = "Book added." ;
    }
    
    $book_data = [];
}

// Handle removing a book from selection
if (isset($_POST['remove_book']) && isset($_POST['remove_index'])) {
    $index = intval($_POST['remove_index']);
    if (isset($selected_books[$index])) {
        array_splice($selected_books, $index, 1);
        $_SESSION['selected_books'] = $selected_books;
        $success_message = "Book removed from selection.";
    }
}

// Process loan submission
// Process loan submission
if (isset($_POST['submit_loan']) && !empty($selected_books) && !empty($borrower_data)) {
    if ($valid_personnel_id === null) {
        $error_message = "No valid personnel found in the system.";
    } else {
        $conn->begin_transaction();
        
        try {
            $fiction_count = 0;
            $nonfiction_count = 0;
            foreach ($selected_books as $book) {
                $book_id = $book['BookID'];
                $date_loan = $conn->real_escape_string($book['date_loan']);
                $due_date = $conn->real_escape_string($book['due_date']);
            
                // Fetch the book's details
                $book_query = "SELECT TotalCopies FROM book WHERE BookID = '$book_id'";
                $book_result = $conn->query($book_query);
                if (!$book_result || $book_result->num_rows == 0) {
                    throw new Exception("Book not found or unavailable.");
                }
                $book_info = $book_result->fetch_assoc();
                $available_copies = $book_info['TotalCopies'];
            
                // Check if the book can still be borrowed
                if ($available_copies <= 0) {
                    throw new Exception("The book '{$book['Title']}' has no available copies for borrowing.");
                }
            
                // Insert loan record
                $sql = "INSERT INTO loan (BorrowerID, BookID, DateBorrowed, DueDate, PersonnelID, Status) 
                        VALUES ('{$borrower_data['BorrowerID']}', '$book_id', '$date_loan', '$due_date', '$valid_personnel_id', 'borrowed')";
                if (!$conn->query($sql)) {
                    throw new Exception("Error recording loan: " . $conn->error);
                }
            
                // Decrement TotalCopies to reflect the borrowed copies
                $sql = "UPDATE book 
                        SET TotalCopies = TotalCopies - 1 
                        WHERE BookID = '$book_id' AND TotalCopies > 0";
                if (!$conn->query($sql)) {
                    throw new Exception("Error updating TotalCopies: " . $conn->error);
                }
            }
            $conn->commit();
            $_SESSION['success_message'] = count($selected_books) . " book(s) loaned successfully!";
            unset($_SESSION['selected_books']);

            // Redirect back to the same borrower view
            header("Location: loan.php?borrower_id=" . $borrower_data['BorrowerID']);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// Count currently borrowed books by category
if (!empty($borrower_data)) {
    $total_loans_sql = "SELECT COUNT(*) as total_loans FROM loan 
                       WHERE BorrowerID = '{$borrower_data['BorrowerID']}' 
                       AND Status = 'borrowed'";
    $total_loans_result = $conn->query($total_loans_sql);
    if ($total_loans_result && $total_loans_result->num_rows > 0) {
        $total_borrowed = $total_loans_result->fetch_assoc()['total_loans'];
    }
    
    $fiction_loans_sql = "SELECT COUNT(*) as fiction_loans FROM loan l
                         JOIN book b ON l.BookID = b.BookID
                         WHERE l.BorrowerID = '{$borrower_data['BorrowerID']}' 
                         AND l.Status = 'borrowed'
                         AND b.CategoryID = 1";
    $fiction_loans_result = $conn->query($fiction_loans_sql);
    if ($fiction_loans_result && $fiction_loans_result->num_rows > 0) {
        $total_borrowed_fiction = $fiction_loans_result->fetch_assoc()['fiction_loans'];
    }
    
    $other_loans_sql = "SELECT COUNT(*) as other_loans FROM loan l
                       JOIN book b ON l.BookID = b.BookID
                       WHERE l.BorrowerID = '{$borrower_data['BorrowerID']}' 
                       AND l.Status = 'borrowed'
                       AND b.CategoryID != 1";
    $other_loans_result = $conn->query($other_loans_sql);
    if ($other_loans_result && $other_loans_result->num_rows > 0) {
        $total_borrowed_other = $other_loans_result->fetch_assoc()['other_loans'];
    }
}

// Process return submission
if (isset($_POST['submit_returns']) && !empty($selected_returns)) {
    $conn->begin_transaction();

    try {
        foreach ($selected_returns as $book) {
            $loan_id = $book['TransactionID'];
            $return_date = $conn->real_escape_string($book['return_date']);
            $book_id = $book['BookID'];

            // Check if the book has already been returned
            $status_check_query = "SELECT Status FROM loan WHERE TransactionID = '$loan_id'";
            $status_check_result = $conn->query($status_check_query);
            if ($status_check_result && $status_check_result->num_rows > 0) {
                $status_row = $status_check_result->fetch_assoc();
                if ($status_row['Status'] === 'returned') {
                    // Skip this book as it has already been returned
                    continue;
                }
            }

            // Handle overdue penalties if needed
            if ($book['days_overdue'] > 0) {
                // Insert into penalty transaction table
                $penalty_amount = $book['penalty_amount'];
                $penalty_sql = "INSERT INTO penaltytransaction (LoanID, PenaltyID, PenaltyAmount, PenaltyType, DateIssued, Remarks, Status) 
                                VALUES ('$loan_id', 1, '$penalty_amount', 'overdue', '$return_date', 'Days Overdue: {$book['days_overdue']}', 'unpaid')";
                if (!$conn->query($penalty_sql)) {
                    throw new Exception("Error recording penalty: " . $conn->error);
                }

                // Update loan with penalty and status
                $update_loan_sql = "UPDATE loan SET 
                                    DateReturned = '$return_date', 
                                    PenaltyID = 1, 
                                    Status = 'penalized' 
                                    WHERE TransactionID = '$loan_id'";
            } else {
                // Update loan with returned status
                $update_loan_sql = "UPDATE loan SET 
                                    DateReturned = '$return_date', 
                                    Status = 'returned' 
                                    WHERE TransactionID = '$loan_id'";
            }

            if (!$conn->query($update_loan_sql)) {
                throw new Exception("Error updating loan: " . $conn->error);
            }

            // Increment TotalCopies to reflect the returned book
            $sql = "UPDATE book 
                    SET TotalCopies = TotalCopies + 1 
                    WHERE BookID = '$book_id'";
            if (!$conn->query($sql)) {
                throw new Exception("Error updating TotalCopies: " . $conn->error);
            }
        }

        $conn->commit();
        $success_message = count($selected_returns) . " book(s) returned successfully!";
        $selected_returns = [];
        $_SESSION['selected_returns'] = [];
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Loan System</title>
    <link rel="stylesheet" href="css/loan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <main class="content">
        <div class="loan-section">
            <div class="classification-container">
                <h2>Loan</h2>
                <!-- Header Tabs -->
                <div class="tab-buttons">
                    <button class="tab-btn active">Loan</button>
                    <span class="arrow">&gt;</span>
                    <button class="tab-btn" onclick="window.location.href='loan_transactions.php'">Loan Transactions</button>
                </div>

                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']); // Clear after display
                        ?>
                    </div>
                <?php endif; ?>

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
                        <p>
                            <strong>ID no.:</strong> 
                            <span><?php echo $borrower_data['BorrowerID']; ?></span>
                        </p>
                         <p>
                            <strong>Last Name:</strong> 
                            <span><?php echo $borrower_data['LastName']; ?></span>
                        </p>
                      
                        <p>
                            <strong>First Name:</strong> 
                            <span><?php echo $borrower_data['FirstName']; ?></span>
                        </p>
                        <p>
                            <strong>Contact Number:</strong> 
                            <span><?php echo $borrower_data['ContactNumber']; ?></span>
                        </p>
                        <p>
                            <strong>Middle Name:</strong> 
                            <span><?php echo $borrower_data['MiddleName']; ?></span>
                        </p>
                          <p>
                            <strong>Role:</strong> 
                            <span><?php echo $borrower_data['Role']; ?></span>
                        </p>
                       
                    <?php else: ?>
                        <p><strong>ID no.:</strong> <span></span></p>
                        <p><strong>Role:</strong> <span></span></p>
                        <p><strong>First Name:</strong> <span></span></p>
                        <p><strong>Contact Number:</strong> <span></span></p>
                        <p><strong>Middle Name:</strong> <span></span></p>
                        <p><strong>Last Name:</strong> <span></span></p>
                    <?php endif; ?>
                </div>

                    
                   <!-- Currently Borrowed Books Section -->
                <?php
                if (!empty($borrower_data)):

                    // Fix: assign BorrowerID to $view_borrower_id
                    $view_borrower_id = $borrower_data['BorrowerID'];

                    $borrowed_sql = "SELECT l.TransactionID, l.BookID, l.DateBorrowed, l.DueDate, l.Status,
                    b.Title, b.ISBN, b.AccessionNumber, b.CategoryID, c.CategoryName,
                    a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName
                    FROM loan l
                    JOIN book b ON l.BookID = b.BookID
                    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                    LEFT JOIN category c ON b.CategoryID = c.CategoryID
                    WHERE l.BorrowerID = '$view_borrower_id'
                    AND l.Status = 'borrowed'";

                    $borrowed_result = $conn->query($borrowed_sql);
                    
                    if ($borrowed_result && $borrowed_result->num_rows > 0):
                        $currently_borrowed_books = $borrowed_result->fetch_all(MYSQLI_ASSOC);
                ?>

                        <div class="currently-borrowed-section">
                            <h3 class="section-title">Currently Borrowed Books</h3>
                            <div class="borrowed-books-list">
                                <table class="selected-books-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Category</th>
                                            <th>Date Borrowed</th>
                                            <th>Due Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($currently_borrowed_books as $index => $book): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($book['Title']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($book['AuthorFirstName'] . ' ' . $book['AuthorMiddleName'] . ' ' . $book['AuthorLastName']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($book['CategoryName']) ?></td>
                                            <td><?= htmlspecialchars($book['DateBorrowed']) ?></td>
                                            <td><?= htmlspecialchars($book['DueDate']) ?></td>
                                            <td>
                                            <form method="POST" action="">
                            <input type="hidden" name="transaction_id" value="<?= $book['TransactionID'] ?>">
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
</div>
<?php endif; endif; ?>

                <!-- Book Search Section -->
                <?php 
                // Calculate available slots
                $available_total = $MAX_TOTAL_BOOKS - ($total_borrowed + count($selected_books));
                $available_fiction = $MAX_FICTION_BOOKS - ($total_borrowed_fiction + $selected_fiction_count);
                $available_other = $MAX_OTHER_BOOKS - ($total_borrowed_other + $selected_other_count);

                // Only show book search if borrower hasn't reached total limit (4 books)
                $show_book_section = !empty($borrower_data) && ($total_borrowed + count($selected_books)) < $MAX_TOTAL_BOOKS;

                if ($show_book_section): ?>
                    <h3 class="section-title">Book Information</h3>
                    <form class="search-form" method="POST" action="">
                        <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                        
                        <i class="fas fa-search"></i>
                        <div class="search-container">
                            <input type="text" class="search-input" id="book-search" name="book" placeholder="Search by Title, ISBN, or Accession Number" value="<?php echo isset($_POST['book']) ? htmlspecialchars($_POST['book']) : ''; ?>">
                            <div id="book-suggestions" class="suggestions-container"></div>
                        </div>
                        <select class="dropdown" name="category">
                            <option>All Categories</option>
                            <?php foreach ($categories as $category): 
                                // Skip Fiction category if fiction limit is reached
                                if ($category == 1 && ($total_borrowed_fiction + $selected_fiction_count) >= $MAX_FICTION_BOOKS) continue;
                                // Skip Other categories if other limit is reached
                                if ($category != 1 && ($total_borrowed_other + $selected_other_count) >= $MAX_OTHER_BOOKS) continue;
                            ?>
                                <option value="<?php echo $category; ?>" <?php echo (isset($_POST['category']) && $_POST['category'] == $category) ? 'selected' : ''; ?>>
                                    <?php echo $category; ?> <?php echo ($category == 1) ? '(Fiction)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="dropdown" name="location">
                            <option>All Location</option>
                            <?php foreach ($locations as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo (isset($_POST['location']) && $_POST['location'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="search_book" class="search-btn">Search</button>
                    </form>

<!-- Book Info Display -->
<div class="borrower-info book-info">
    <?php if (!empty($book_data)): ?>
        <?php
            $book_loan_duration = $default_loan_duration;
            if ($book_data['CategoryID'] == 1) {
                $fiction_duration_query = "SELECT Duration FROM penalty WHERE PenaltyName = 'Overdue (Fiction)' LIMIT 1";
                $fiction_duration_result = $conn->query($fiction_duration_query);
                if ($fiction_duration_result && $fiction_duration_result->num_rows > 0) {
                    $book_loan_duration = intval($fiction_duration_result->fetch_assoc()['Duration']);
                }
            }

            $book_due_date = date('Y-m-d', strtotime("+$book_loan_duration days"));
            $category_limit_reached = false;
            if ($book_data['CategoryID'] == 1 && $total_borrowed_fiction + $selected_fiction_count >= $MAX_FICTION_BOOKS) {
                $category_limit_reached = true;
            } elseif ($book_data['CategoryID'] != 1 && $total_borrowed_other + $selected_other_count >= $MAX_OTHER_BOOKS) {
                $category_limit_reached = true;
            }
        ?>

        <div class="book-columns">
            <!-- LEFT COLUMN -->
            <div class="book-column">
                <p><strong>Title:</strong> <span><?php echo $book_data['Title']; ?></span></p>
                <p><strong>Category:</strong> 
                    <span>
                        <?php echo $book_data['Category']; ?>
                        <?php if ($book_data['CategoryID'] == 1): ?>
                            <span class="category-badge fiction">Fiction</span>
                        <?php else: ?>
                            <span class="category-badge non-fiction">Non-Fiction</span>
                        <?php endif; ?>
                    </span>
                </p>
                <p><strong>ISBN:</strong> <span><?php echo $book_data['ISBN']; ?></span></p>
                <p><strong>Classification:</strong> <span><?php echo $book_data['Classification']; ?></span></p>
                <p><strong>Author:</strong> 
                    <span>
                        <?php 
                            $author = $book_data['AuthorFirstName'];
                            if (!empty($book_data['AuthorMiddleName'])) {
                                $author .= ' ' . $book_data['AuthorMiddleName'];
                            }
                            $author .= ' ' . $book_data['AuthorLastName'];
                            echo $author;
                        ?>
                    </span>
                </p>
                <p><strong>Status:</strong> <span><?php echo $book_data['Status']; ?></span></p>
                <p><strong>Call Number:</strong> <span><?php echo $book_data['CallNumber']; ?></span></p>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="book-column">
                <p><strong>No. of Copy:</strong> <span id="available-copies"><?php echo $book_data['TotalCopies'] - $book_data['HoldCopies']; ?> available of <?php echo $book_data['TotalCopies']; ?></span></p>
                <p><strong>Subject:</strong> <span><?php echo $book_data['ClassificationNumber']; ?></span></p>
                <p><strong>Year:</strong> <span><?php echo $book_data['Year']; ?></span></p>
                <p><strong>Publisher:</strong> <span><?php echo $book_data['Publisher']; ?></span></p>
                <p><strong>Edition:</strong> <span><?php echo $book_data['Edition']; ?></span></p>
                <p><strong>Accession Number:</strong> <span><?php echo $book_data['AccessionNumber']; ?></span></p>
                <p><strong>Location:</strong> <span><?php echo $book_data['LocationName']; ?></span></p>
                <p><strong>Price:</strong> <span><?php echo number_format($book_data['Price'], 2); ?></span></p>
            </div>
        </div>

        <div class="add-book-btn-container">
            <form method="POST" action="">
                <input type="hidden" name="book_id" value="<?php echo $book_data['BookID']; ?>">
                <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                <button type="submit" name="add_book" class="add-btn" <?php echo (empty($borrower_data) || $category_limit_reached) ? 'disabled' : ''; ?>>
                    <i class="fas fa-plus"></i> Add Book
                </button>
                <?php if ($category_limit_reached): ?>
                    <span class="limit-warning">
                        <?php if ($book_data['CategoryID'] == 1): ?>
                            Maximum fiction book limit reached (<?php echo $MAX_FICTION_BOOKS; ?> books)
                        <?php else: ?>
                            Maximum non-fiction book limit reached (<?php echo $MAX_OTHER_BOOKS; ?> books)
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </form>
        </div>
    <?php else: ?>
        <p><strong>Title:</strong> <span></span></p>
        <p><strong>Category:</strong> <span></span></p>
        <p><strong>ISBN:</strong> <span></span></p>
        <p><strong>Classification:</strong> <span></span></p>
        <p><strong>Author:</strong> <span></span></p>
        <p><strong>Status:</strong> <span></span></p>
        <p><strong>Call Number:</strong> <span></span></p>
        <p><strong>No. of Copy:</strong> <span></span></p>
        <p><strong>Subject:</strong> <span></span></p>
        <p><strong>Year:</strong> <span></span></p>
        <p><strong>Publisher:</strong> <span></span></p>
        <p><strong>Edition:</strong> <span></span></p>
        <p><strong>Accession Number:</strong> <span></span></p>
        <p><strong>Location:</strong> <span></span></p>
        <p><strong>Price:</strong> <span></span></p>
    <?php endif; ?>
</div>
                <?php elseif (!empty($borrower_data) && ($total_borrowed + count($selected_books)) >= $MAX_TOTAL_BOOKS): ?>
                    <div class="alert alert-warning">
                        <strong>Maximum Limit Reached:</strong> This borrower has already loaned/selected <?php echo $total_borrowed + count($selected_books); ?> books (maximum <?php echo $MAX_TOTAL_BOOKS; ?> books allowed).
                    </div>
                <?php endif; ?>

                <!-- Borrowing Limits Info Box -->
                <?php if (!empty($borrower_data)): 
                        $available_total = $MAX_TOTAL_BOOKS - ($total_borrowed + count($selected_books));
                        $available_fiction = $MAX_FICTION_BOOKS - ($total_borrowed_fiction + $selected_fiction_count);
                        $available_other = $MAX_OTHER_BOOKS - ($total_borrowed_other + $selected_other_count);
                    ?>
                    <div class="borrowing-limits">
                        <div class="alert alert-info">
                            <strong>Borrowing Limits:</strong> 
                            <div class="limits-info">
                                <div class="limit-item">
                                    <span class="limit-label">Total Books:</span>
                                    <span class="limit-value"><?php echo ($total_borrowed + count($selected_books)); ?>/<?php echo $MAX_TOTAL_BOOKS; ?></span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo (($total_borrowed + count($selected_books)) / $MAX_TOTAL_BOOKS) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="limit-item">
                                    <span class="limit-label">Fiction:</span>
                                    <span class="limit-value"><?php echo ($total_borrowed_fiction + $selected_fiction_count); ?>/<?php echo $MAX_FICTION_BOOKS; ?></span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo (($total_borrowed_fiction + $selected_fiction_count) / $MAX_FICTION_BOOKS) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="limit-item">
                                    <span class="limit-label">Non-Fiction:</span>
                                    <span class="limit-value"><?php echo ($total_borrowed_other + $selected_other_count); ?>/<?php echo $MAX_OTHER_BOOKS; ?></span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo (($total_borrowed_other + $selected_other_count) / $MAX_OTHER_BOOKS) * 100; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <p>This borrower can borrow or select <?php echo max(0, $available_total); ?> more book(s): 
                            <?php echo max(0, $available_fiction); ?> fiction or 
                            <?php echo max(0, $available_other); ?> non-fiction.</p>
                        </div>
                    </div>
                    <?php endif; ?>

           

                <!-- Selected Books Section -->
                <?php if (!empty($selected_books)): ?>
                <div class="selected-books-section">
                <h3 class="section-title">Selected Books (<?php echo count($selected_books); ?>/<?php echo ($MAX_TOTAL_BOOKS - $total_borrowed); ?> remaining)</h3>
                    <div class="selected-books-list">
                        <table class="selected-books-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>ISBN</th>
                                    <th>Date Loan</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selected_books as $index => $book): 
                                    $book_loan_duration = $default_loan_duration;
                                    if ($book['CategoryID'] == 1) {
                                        $fiction_duration_query = "SELECT Duration FROM penalty WHERE PenaltyName = 'Overdue (Fiction)' LIMIT 1";
                                        $fiction_duration_result = $conn->query($fiction_duration_query);
                                        
                                        if ($fiction_duration_result && $fiction_duration_result->num_rows > 0) {
                                            $book_loan_duration = intval($fiction_duration_result->fetch_assoc()['Duration']);
                                        }
                                    }
                                ?>
                                <tr>
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
                                    <td>
                                        <?php 
                                            echo isset($book['CategoryName']) ? $book['CategoryName'] : ''; 
                                            if ($book['CategoryID'] == 1) {
                                                echo ' <span class="category-badge fiction">Fiction</span>';
                                            } else {
                                                echo ' <span class="category-badge non-fiction">Non-Fiction</span>';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo $book['ISBN']; ?></td>
                                    <td>
                                        <input type="date" class="date-input" name="date_loan_<?php echo $index; ?>" 
                                               value="<?php echo $book['date_loan']; ?>" 
                                               min="<?php echo $today; ?>"
                                               onchange="updateBookDate(<?php echo $index; ?>, 'date_loan', this.value)">
                                    </td>
                                    <td>
                                        <input type="date" class="date-input" name="due_date_<?php echo $index; ?>" 
                                               value="<?php echo $book['due_date']; ?>"
                                               min="<?php echo $book['date_loan']; ?>"
                                               onchange="updateBookDate(<?php echo $index; ?>, 'due_date', this.value)">
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="remove_index" value="<?php echo $index; ?>">
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
                </div>
                <?php endif; ?>

                <!-- Error and Success Messages -->
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
                <form id="loan_form" method="POST" action="">
                    <?php if ($valid_personnel_id): ?>
                        <input type="hidden" name="personnel_id" value="<?php echo $valid_personnel_id; ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($borrower_data)): ?>
                    <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                    <?php endif; ?>
                    
                    <div class="button-group">
                        <button type="submit" name="submit_loan" class="add-btn" <?php echo (empty($borrower_data) || empty($selected_books) || !$valid_personnel_id) ? 'disabled' : ''; ?>>
                            Loan <?php echo count($selected_books); ?> Book<?php echo count($selected_books) != 1 ? 's' : ''; ?>
                        </button>
                        <a href="loan.php?clear=1" class="cancel-btn">Cancel</a>
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
                            
                            Object.keys(borrower).forEach(key => {
                                if (borrower[key] !== null) {
                                    div.setAttribute('data-' + key.replace(/_/g, '-'), borrower[key]);
                                }
                            });
                            
                            div.addEventListener('click', function() {
                                borrowerSearchInput.value = borrower.id;
                                borrowerSuggestionsContainer.style.display = 'none';
                                
                                const borrowerForm = document.querySelectorAll('form.search-form')[0];
                                
                                let searchTrigger = document.createElement('input');
                                searchTrigger.type = 'hidden';
                                searchTrigger.name = 'search_borrower';
                                searchTrigger.value = '1';
                                borrowerForm.appendChild(searchTrigger);
                                
                                const bookIdField = document.querySelector('input[name="book_id"]');
                                if (bookIdField) {
                                    let bookIdInput = document.createElement('input');
                                    bookIdInput.type = 'hidden';
                                    bookIdInput.name = 'book_id';
                                    bookIdInput.value = bookIdField.value;
                                    borrowerForm.appendChild(bookIdInput);
                                }
                                
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
        
        // Book Search Suggestions
        const bookSearchInput = document.getElementById('book-search');
        const bookSuggestionsContainer = document.getElementById('book-suggestions');
        let selectedBookSuggestionIndex = -1;
        
        function getBookSuggestions(searchTerm) {
            if (searchTerm.length < 2) {
                bookSuggestionsContainer.style.display = 'none';
                return;
            }
            
            fetch('get_books.php?term=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(data => {
                    bookSuggestionsContainer.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach((book, index) => {
                            const div = document.createElement('div');
                            div.className = 'suggestion-item';
                            div.textContent = book.value || book.title;
                            div.setAttribute('data-id', book.id);
                            
                            Object.keys(book).forEach(key => {
                                if (book[key] !== null) {
                                    div.setAttribute('data-' + key.replace(/_/g, '-'), book[key]);
                                }
                            });
                            
                            div.addEventListener('click', function() {
                                bookSearchInput.value = book.id;
                                bookSuggestionsContainer.style.display = 'none';
                                
                                const bookForm = document.querySelectorAll('form.search-form')[1];
                                
                                let searchTrigger = document.createElement('input');
                                searchTrigger.type = 'hidden';
                                searchTrigger.name = 'search_book';
                                searchTrigger.value = '1';
                                bookForm.appendChild(searchTrigger);
                                
                                const borrowerIdField = document.querySelector('input[name="borrower_id"]');
                                if (borrowerIdField) {
                                    let borrowerIdInput = document.createElement('input');
                                    borrowerIdInput.type = 'hidden';
                                    borrowerIdInput.name = 'borrower_id';
                                    borrowerIdInput.value = borrowerIdField.value;
                                    bookForm.appendChild(borrowerIdInput);
                                }
                                
                                bookForm.submit();
                            });
                            
                            bookSuggestionsContainer.appendChild(div);
                        });
                        
                        bookSuggestionsContainer.style.display = 'block';
                    } else {
                        bookSuggestionsContainer.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching book suggestions:', error);
                });
        }
        
        if (borrowerSearchInput) {
            borrowerSearchInput.addEventListener('input', function() {
                getBorrowerSuggestions(this.value);
                selectedBorrowerSuggestionIndex = -1;
            });
        }
        
        if (bookSearchInput) {
            bookSearchInput.addEventListener('input', function() {
                getBookSuggestions(this.value);
                selectedBookSuggestionIndex = -1;
            });
        }
        
       window.updateBookDate = function(index, type, value) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        const indexInput = document.createElement('input');
        indexInput.type = 'hidden';
        indexInput.name = 'update_date_index';
        indexInput.value = index;
        form.appendChild(indexInput);

        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'update_date_type';
        typeInput.value = type;
        form.appendChild(typeInput);

        const valueInput = document.createElement('input');
        valueInput.type = 'hidden';
        valueInput.name = 'update_date_value';
        valueInput.value = value;
        form.appendChild(valueInput);

        const borrowerIdField = document.querySelector('input[name="borrower_id"]');
        if (borrowerIdField) {
            const borrowerIdInput = document.createElement('input');
            borrowerIdInput.type = 'hidden';
            borrowerIdInput.name = 'borrower_id';
            borrowerIdInput.value = borrowerIdField.value;
            form.appendChild(borrowerIdInput);
        }

        // Dynamically update the displayed due date
        if (type === 'due_date') {
            const dueDateDisplay = document.getElementById('due-date-display');
            if (dueDateDisplay) {
                dueDateDisplay.textContent = value; // Update the span with the new due date
            }
        }

        document.body.appendChild(form);
        form.submit();
    };
    });
    </script>
</body>
</html>

<?php
$conn->close();
ob_end_flush();
?>