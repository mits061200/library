<?php
// Start session at the very beginning of the script
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
                WHERE b.BorrowerID LIKE '%$search_term%' OR 
                      b.FirstName LIKE '%$search_term%' OR 
                      b.LastName LIKE '%$search_term%'
                ORDER BY b.LastName, b.FirstName";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            // Loop through each borrower
            while ($borrower = $result->fetch_assoc()) {
                $borrower_id = $borrower['BorrowerID'];
                
                // Count active loans for this borrower
                $count_sql = "SELECT COUNT(*) AS loan_count 
                             FROM loan 
                             WHERE BorrowerID = '$borrower_id' 
                             AND Status = 'borrowed'";
                $count_result = $conn->query($count_sql);
                $count_row = $count_result->fetch_assoc();
                $loan_count = $count_row['loan_count'];
                
                // Only add borrowers with active loans
                if ($loan_count > 0) {
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
                        'loan_count' => $loan_count
                    ];
                }
            }
            
            if (empty($transactions)) {
                $error_message = "No active loans found for the search criteria.";
            }
        } else {
            $error_message = "No borrowers found matching the search criteria.";
        }
    }
} else {
    // If no search, show recent transactions (limit to 10)
    $sql = "SELECT DISTINCT b.BorrowerID, b.FirstName, b.MiddleName, b.LastName, b.Role
            FROM loan l
            JOIN borrowers b ON l.BorrowerID = b.BorrowerID
            WHERE l.Status = 'borrowed'
            ORDER BY l.DateBorrowed DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($borrower = $result->fetch_assoc()) {
            $borrower_id = $borrower['BorrowerID'];
            
            // Count active loans for this borrower
            $count_sql = "SELECT COUNT(*) AS loan_count 
                         FROM loan 
                         WHERE BorrowerID = '$borrower_id' 
                         AND Status = 'borrowed'";
            $count_result = $conn->query($count_sql);
            $count_row = $count_result->fetch_assoc();
            $loan_count = $count_row['loan_count'];
            
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
                'loan_count' => $loan_count
            ];
        }
    }
}

// View details of a specific transaction
$view_borrower_id = isset($_GET['view']) ? $conn->real_escape_string($_GET['view']) : '';
$borrowed_books = [];

if (!empty($view_borrower_id)) {
    // Get borrower details
    $borrower_sql = "SELECT * FROM borrowers WHERE BorrowerID = '$view_borrower_id'";
    $borrower_result = $conn->query($borrower_sql);
    $borrower_details = $borrower_result->fetch_assoc();
    
    // Get all borrowed books for this borrower
    $books_sql = "SELECT l.TransactionID, l.DateBorrowed, l.DueDate, l.Status,
                        b.Title, b.ISBN, b.AccessionNumber, 
                        a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName
                 FROM loan l
                 JOIN book b ON l.BookID = b.BookID
                 LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                 WHERE l.BorrowerID = '$view_borrower_id'
                 AND l.Status = 'borrowed'
                 ORDER BY l.DateBorrowed DESC";
    
    $books_result = $conn->query($books_sql);
    
    if ($books_result && $books_result->num_rows > 0) {
        while ($book = $books_result->fetch_assoc()) {
            // Format author name
            $author = $book['AuthorFirstName'];
            if (!empty($book['AuthorMiddleName'])) {
                $author .= ' ' . $book['AuthorMiddleName'];
            }
            $author .= ' ' . $book['AuthorLastName'];
            
            $borrowed_books[] = [
                'transaction_id' => $book['TransactionID'],
                'title' => $book['Title'],
                'author' => $author,
                'isbn' => $book['ISBN'],
                'accession_number' => $book['AccessionNumber'],
                'date_borrowed' => $book['DateBorrowed'],
                'due_date' => $book['DueDate'],
                'status' => $book['Status']
            ];
        }
    }
}
?>

<main class="content">
    <div class="loan-section">
        <div class="classification-container">
            <h2>Loan Transactions</h2>

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

            <!-- Header Tabs -->
            <div class="tab-buttons">
                <button class="tab-btn" onclick="window.location.href='loan.php'">Loan</button>
                <span class="arrow">&gt;</span>
                <button class="tab-btn active">Loan Transactions</button>
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
                <!-- Transactions Table -->
                <?php if (!empty($transactions)): ?>
                    <div class="table-responsive">
                        <table class="table transaction-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Borrower</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $index => $transaction): ?>
                                <tr>
                                    <td><?php echo sprintf('%02d', $index + 1); ?></td>
                                    <td><?php echo $transaction['name'] . " (" . $transaction['borrower_id'] . ")"; ?></td>
                                    <td>
                                        <a href="?view=<?php echo $transaction['borrower_id']; ?><?php echo !empty($borrower_search) ? '&search=' . urlencode($borrower_search) : ''; ?>" class="view-btn" title="View">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="#" class="delete-btn" title="Delete" onclick="confirmDelete('<?php echo $transaction['borrower_id']; ?>')">
                                            <i class="fas fa-trash"></i> Delete
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
                <!-- Borrower Details -->
                <div class="borrower-details">
                    <h3>Borrower Information</h3>
                    <?php if (!empty($borrower_details)): ?>
                        <div class="borrower-info">
                            <p><strong>ID no.:</strong> <?php echo $borrower_details['BorrowerID']; ?> <span class="gap"></span> <strong>Role:</strong> <?php echo $borrower_details['Role']; ?></p>
                            <p><strong>First Name:</strong> <?php echo $borrower_details['FirstName']; ?> <span class="gap"></span> <strong>Contact Number:</strong> <?php echo $borrower_details['ContactNumber']; ?></p>
                            <p><strong>Middle Name:</strong> <?php echo $borrower_details['MiddleName']; ?> <span class="gap"></span> <strong>Latest Penalty:</strong> 
                            <?php 
                                $penalty_query = "SELECT SUM(p.PenaltyRate) as TotalPenalty 
                                                FROM loan bl 
                                                JOIN penalty p ON bl.PenaltyID = p.PenaltyID 
                                                WHERE bl.BorrowerID = '{$borrower_details['BorrowerID']}' 
                                                AND bl.Status = 'penalized'";
                                $penalty_result = $conn->query($penalty_query);
                                $penalty_amount = 0;
                                if ($penalty_result && $penalty_result->num_rows > 0) {
                                    $penalty_row = $penalty_result->fetch_assoc();
                                    $penalty_amount = $penalty_row['TotalPenalty'];
                                }
                                echo number_format($penalty_amount, 2);
                            ?>
                            </p>
                            <p><strong>Last Name:</strong> <?php echo $borrower_details['LastName']; ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Borrowed Books Table -->
                <h3>Borrowed Books</h3>
                <?php if (!empty($borrowed_books)): ?>
                    <div class="table-responsive">
                        <table class="table books-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Accession Number</th>
                                    <th>Date Borrowed</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowed_books as $index => $book): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['author']; ?></td>
                                    <td><?php echo $book['isbn']; ?></td>
                                    <td><?php echo $book['accession_number']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['date_borrowed'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $due_date = new DateTime($book['due_date']);
                                        $today = new DateTime();
                                        $status_class = 'status-normal';
                                        $status_text = 'Borrowed';
                                        
                                        if ($today > $due_date) {
                                            $status_class = 'status-overdue';
                                            $status_text = 'Overdue';
                                        } elseif ($due_date->diff($today)->days <= 2) {
                                            $status_class = 'status-warning';
                                            $status_text = 'Due Soon';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No books currently borrowed by this borrower.</p>
                <?php endif; ?>

                <!-- Back Button -->
                <div class="button-group">
                    <a href="loan_transactions.php<?php echo !empty($borrower_search) ? '?search=' . urlencode($borrower_search) : ''; ?>" class="cancel-btn">Back to Transactions</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // Borrower Search Suggestions
    const borrowerSearchInput = document.getElementById('borrower-search');
    const borrowerSuggestionsContainer = document.getElementById('borrower-suggestions');
    
    if (borrowerSearchInput) {
        borrowerSearchInput.addEventListener('input', function() {
            const searchTerm = this.value;
            
            if (searchTerm.length < 2) {
                borrowerSuggestionsContainer.style.display = 'none';
                return;
            }
            
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
                                
                                // Submit the form
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

// Confirmation for delete action
function confirmDelete(borrowerId) {
    if (confirm('Are you sure you want to delete all transactions for this borrower?')) {
        window.location.href = 'delete_transactions.php?borrower_id=' + borrowerId;
    }
}
</script>

<?php
// Close database connection
$conn->close();
?>