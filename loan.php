<?php
include 'header.php';
?>
<link rel="stylesheet" href="css/loan.css">
<?php
include 'navbar.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$borrower_data = [];
$book_data = [];
$borrower_search = '';
$book_search = '';
$selected_category = 'All Categories';
$selected_location = 'All Location';
$error_message = '';
$success_message = '';

// Handle borrower search
if (isset($_POST['search_borrower'])) {
    $borrower_search = $conn->real_escape_string($_POST['borrower']);
    
    // First try direct ID match
    $sql = "SELECT * FROM borrowers WHERE BorrowerID = '$borrower_search' LIMIT 1";
    $result = $conn->query($sql);
    
    // If no direct match, try the search terms
    if (!$result || $result->num_rows == 0) {
        $sql = "SELECT * FROM borrowers WHERE 
                BorrowerID LIKE '%$borrower_search%' OR 
                FirstName LIKE '%$borrower_search%' OR 
                LastName LIKE '%$borrower_search%' 
                LIMIT 1";
                
        $result = $conn->query($sql);
    }
    
    if ($result && $result->num_rows > 0) {
        $borrower_data = $result->fetch_assoc();
    } else {
        $error_message = "Borrower not found";
    }
}

// Get categories for dropdown
$categories = [];
$sql = "SELECT DISTINCT CategoryID FROM book";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['CategoryID'];
    }
}

// Get locations for dropdown
$locations = [];
$sql = "SELECT LocationID, LocationName FROM location";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[$row['LocationID']] = $row['LocationName'];
    }
}

// Handle book search
if (isset($_POST['search_book'])) {
    $book_search = $conn->real_escape_string($_POST['book']);
    $category_filter = $_POST['category'] !== 'All Categories' ? "AND b.CategoryID = " . intval($_POST['category']) : "";
    $location_filter = $_POST['location'] !== 'All Location' ? "AND b.LocationID = " . intval($_POST['location']) : "";
    
    // First try direct ID match
    $sql = "SELECT b.*, a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, 
            a.LastName AS AuthorLastName, m.MaterialName AS Category,
            mc.ClassificationNumber, mc.Description AS Classification,
            l.LocationName
            FROM book b
            LEFT JOIN authors a ON b.AuthorID = a.AuthorID
            LEFT JOIN material m ON b.MaterialID = m.MaterialID
            LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
            LEFT JOIN location l ON b.LocationID = l.LocationID
            WHERE b.BookID = '$book_search'
            AND b.Status = 'Available'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    // If no direct match, try the search terms
    if (!$result || $result->num_rows == 0) {
        $sql = "SELECT b.*, a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, 
                a.LastName AS AuthorLastName, m.MaterialName AS Category,
                mc.ClassificationNumber, mc.Description AS Classification,
                l.LocationName
                FROM book b
                LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                LEFT JOIN material m ON b.MaterialID = m.MaterialID
                LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
                LEFT JOIN location l ON b.LocationID = l.LocationID
                WHERE (b.Title LIKE '%$book_search%' OR 
                      b.ISBN LIKE '%$book_search%' OR
                      b.AccessionNumber LIKE '%$book_search%')
                      $category_filter
                      $location_filter
                      AND b.Status = 'Available'
                LIMIT 1";
        
        $result = $conn->query($sql);
    }
    
    if ($result && $result->num_rows > 0) {
        $book_data = $result->fetch_assoc();
    } else {
        $error_message = "Book not found or not available";
    }
}

// Process loan submission
if (isset($_POST['submit_loan'])) {
    // Validate required fields
    if (empty($_POST['borrower_id']) || empty($_POST['book_id']) || empty($_POST['date_loan']) || empty($_POST['due_date'])) {
        $error_message = "All fields are required";
    } else {
        $borrower_id = $conn->real_escape_string($_POST['borrower_id']);
        $book_id = $conn->real_escape_string($_POST['book_id']);
        $date_loan = $conn->real_escape_string($_POST['date_loan']);
        $due_date = $conn->real_escape_string($_POST['due_date']);
        $personnel_id = isset($_SESSION['personnel_id']) ? $_SESSION['personnel_id'] : 'ADMIN';
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into loan table
            $sql = "INSERT INTO loan (BorrowerID, BookID, DateBorrowed, DueDate, PersonnelID, Status) 
                   VALUES ('$borrower_id', '$book_id', '$date_loan', '$due_date', '$personnel_id', 'borrowed')";
            
            if (!$conn->query($sql)) {
                throw new Exception("Error recording loan: " . $conn->error);
            }
            
            // Update book status to 'On Loan'
            $sql = "UPDATE book SET Status = 'On Loan' WHERE BookID = '$book_id'";
            if (!$conn->query($sql)) {
                throw new Exception("Error updating book status: " . $conn->error);
            }
            
            $conn->commit();
            $success_message = "Book loaned successfully!";
            
            // Keep the borrower and book data for display
            $borrower_data = $conn->query("SELECT * FROM borrowers WHERE BorrowerID = '$borrower_id'")->fetch_assoc();
            $book_data = $conn->query("SELECT b.*, a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, 
                                      a.LastName AS AuthorLastName, m.MaterialName AS Category,
                                      mc.ClassificationNumber, mc.Description AS Classification,
                                      l.LocationName
                                      FROM book b
                                      LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                                      LEFT JOIN material m ON b.MaterialID = m.MaterialID
                                      LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
                                      LEFT JOIN location l ON b.LocationID = l.LocationID
                                      WHERE b.BookID = '$book_id'")->fetch_assoc();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

// Get today's date and default due date (7 days from now)
$today = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime('+7 days'));
?>

<main class="content">
    <div class="loan-section">
        <div class="classification-container">
            <h2>Loan</h2>

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
                <button class="tab-btn active">Loan</button>
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
                        $penalty_query = "SELECT SUM(p.PenaltyAmount) as TotalPenalty 
                                        FROM loan bl 
                                        JOIN penalty p ON bl.PenaltyID = p.PenaltyID 
                                        WHERE bl.BorrowerID = '{$borrower_data['BorrowerID']}' 
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
                    <p><strong>Last Name:</strong> <?php echo $borrower_data['LastName']; ?></p>
                <?php else: ?>
                    <p><strong>ID no.:</strong> <span class="gap"></span> <strong>Role:</strong></p>
                    <p><strong>First Name:</strong> <span class="gap"></span> <strong>Contact Number:</strong></p>
                    <p><strong>Middle Name:</strong> <span class="gap"></span> <strong>Latest Penalty:</strong> 0.00</p>
                    <p><strong>Last Name:</strong></p>
                <?php endif; ?>
            </div>

            <!-- Book Search -->
            <h3 class="section-title">Book Information</h3>
            <form class="search-form" method="POST" action="">
                <i class="fas fa-search"></i>
                <div class="search-container">
                    <input type="text" class="search-input" id="book-search" name="book" placeholder="Search by Title, ISBN, or Accession Number" value="<?php echo isset($_POST['book']) ? htmlspecialchars($_POST['book']) : ''; ?>">
                    <div id="book-suggestions" class="suggestions-container"></div>
                </div>
                <select class="dropdown" name="category">
                    <option>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category; ?>" <?php echo (isset($_POST['category']) && $_POST['category'] == $category) ? 'selected' : ''; ?>>
                            <?php echo $category; ?>
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

            <!-- Book Info -->
            <div class="borrower-info">
                <?php if (!empty($book_data)): ?>
                    <p><strong>Title:</strong> <?php echo $book_data['Title']; ?> <span class="gap"></span> <strong>Category:</strong> <?php echo $book_data['Category']; ?></p>
                    <p><strong>ISBN:</strong> <?php echo $book_data['ISBN']; ?> <span class="gap"></span> <strong>Classification:</strong> <?php echo $book_data['Classification']; ?></p>
                    <p><strong>Author:</strong> 
                        <?php 
                            $author = $book_data['AuthorFirstName'];
                            if (!empty($book_data['AuthorMiddleName'])) {
                                $author .= ' ' . $book_data['AuthorMiddleName'];
                            }
                            $author .= ' ' . $book_data['AuthorLastName'];
                            echo $author;
                        ?> 
                        <span class="gap"></span> <strong>Status:</strong> <?php echo $book_data['Status']; ?>
                    </p>
                    <p><strong>Call Number:</strong> <?php echo $book_data['CallNumber']; ?> <span class="gap"></span> <strong>Date Loan:</strong> 
                        <input type="date" class="date-input" name="date_loan" value="<?php echo $today; ?>" form="loan_form">
                    </p>
                    <p><strong>No. of Copy:</strong> <?php echo $book_data['TotalCopies'] - $book_data['HoldCopies']; ?> available of <?php echo $book_data['TotalCopies']; ?> <span class="gap"></span> <strong>Due Date:</strong> 
                        <input type="date" class="date-input" name="due_date" value="<?php echo $default_due_date; ?>" form="loan_form">
                    </p>
                    <p><strong>Subject:</strong> <?php echo $book_data['ClassificationNumber']; ?> <span class="gap"></span> <strong>Year:</strong> <?php echo $book_data['Year']; ?></p>
                    <p><strong>Publisher:</strong> <?php echo $book_data['Publisher']; ?> <span class="gap"></span> <strong>Edition:</strong> <?php echo $book_data['Edition']; ?></p>
                    <p><strong>Accession Number:</strong> <?php echo $book_data['AccessionNumber']; ?> <span class="gap"></span> <strong>Location:</strong> <?php echo $book_data['LocationName']; ?></p>
                    <p><strong>Price:</strong> <?php echo number_format($book_data['Price'], 2); ?></p>
                <?php else: ?>
                    <p><strong>Title:</strong> <span class="gap"></span> <strong>Category:</strong></p>
                    <p><strong>ISBN:</strong> <span class="gap"></span> <strong>Classification:</strong></p>
                    <p><strong>Author:</strong> <span class="gap"></span> <strong>Status:</strong></p>
                    <p><strong>Call Number:</strong> <span class="gap"></span> <strong>Date Loan:</strong> 
                        <input type="date" class="date-input" name="date_loan" value="<?php echo $today; ?>" form="loan_form">
                    </p>
                    <p><strong>No. of Copy:</strong> <span class="gap"></span> <strong>Due Date:</strong> 
                        <input type="date" class="date-input" name="due_date" value="<?php echo $default_due_date; ?>" form="loan_form">
                    </p>
                    <p><strong>Subject:</strong> <span class="gap"></span> <strong>Year:</strong></p>
                    <p><strong>Publisher:</strong> <span class="gap"></span> <strong>Edition:</strong></p>
                    <p><strong>Accession Number:</strong> <span class="gap"></span> <strong>Location:</strong></p>
                    <p><strong>Price:</strong></p>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <form id="loan_form" method="POST" action="">
                <?php if (!empty($borrower_data)): ?>
                    <input type="hidden" name="borrower_id" value="<?php echo $borrower_data['BorrowerID']; ?>">
                <?php endif; ?>
                
                <?php if (!empty($book_data)): ?>
                    <input type="hidden" name="book_id" value="<?php echo $book_data['BookID']; ?>">
                <?php endif; ?>
                
                <div class="button-group">
                    <button type="submit" name="submit_loan" class="add-btn" <?php echo (empty($borrower_data) || empty($book_data)) ? 'disabled' : ''; ?>>
                        Loan Book
                    </button>
                    <a href="loan.php" class="cancel-btn">Cancel</a>
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
    
    // Book Search Suggestions
    const bookSearchInput = document.getElementById('book-search');
    const bookSuggestionsContainer = document.getElementById('book-suggestions');
    let selectedBookSuggestionIndex = -1;
    
    // Function to get book suggestions
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
                        
                        // Store all needed data as attributes
                        Object.keys(book).forEach(key => {
                            if (book[key] !== null) {
                                div.setAttribute('data-' + key.replace(/_/g, '-'), book[key]);
                            }
                        });
                        
                        div.addEventListener('click', function() {
                            bookSearchInput.value = book.id;
                            bookSuggestionsContainer.style.display = 'none';
                            
                            // Submit the form
                            const bookForm = document.querySelectorAll('form.search-form')[1];
                            
                            // Add hidden input to trigger the search
                            let searchTrigger = document.createElement('input');
                            searchTrigger.type = 'hidden';
                            searchTrigger.name = 'search_book';
                            searchTrigger.value = '1';
                            bookForm.appendChild(searchTrigger);
                            
                            // Submit form
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
    
    // Event listeners for borrower search
    if (borrowerSearchInput) {
        borrowerSearchInput.addEventListener('input', function() {
            getBorrowerSuggestions(this.value);
            selectedBorrowerSuggestionIndex = -1;
        });
    }
    
    // Event listeners for book search
    if (bookSearchInput) {
        bookSearchInput.addEventListener('input', function() {
            getBookSuggestions(this.value);
            selectedBookSuggestionIndex = -1;
        });
    }
    
    // Date input handling
    const dateLoanInput = document.querySelector('input[name="date_loan"]');
    const dueDateInput = document.querySelector('input[name="due_date"]');
    
    if (dateLoanInput && dueDateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateLoanInput.setAttribute('min', today);
        
        dateLoanInput.addEventListener('change', function() {
            if (this.value) {
                const loanDate = new Date(this.value);
                loanDate.setDate(loanDate.getDate() + 7);
                dueDateInput.value = loanDate.toISOString().split('T')[0];
                dueDateInput.setAttribute('min', this.value);
            }
        });
    }
});
</script>

<?php
// Close the database connection
$conn->close();
?>