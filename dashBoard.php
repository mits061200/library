<?php
session_start();
include 'header.php';
?>
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
include 'navbar.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get statistics for dashboard
$stats = [];

// Total books
$result = $conn->query("SELECT COUNT(*) as total FROM book");
$stats['total_books'] = $result->fetch_assoc()['total'];

// Available books
$result = $conn->query("SELECT COUNT(*) as available FROM book WHERE TotalCopies > 0");
$stats['available_books'] = $result->fetch_assoc()['available'];

// Active loans
$result = $conn->query("SELECT COUNT(*) as active FROM loan WHERE Status = 'borrowed'");
$stats['active_loans'] = $result->fetch_assoc()['active'];

// Overdue loans
$result = $conn->query("SELECT COUNT(*) as overdue FROM loan WHERE Status = 'borrowed' AND DueDate < CURDATE()");
$stats['overdue_loans'] = $result->fetch_assoc()['overdue'];

// Total borrowers
$result = $conn->query("SELECT COUNT(*) as borrowers FROM borrowers");
$stats['total_borrowers'] = $result->fetch_assoc()['borrowers'];

// Recent transactions (last 7 days)
$result = $conn->query("SELECT COUNT(*) as recent FROM loan WHERE DateBorrowed >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stats['recent_transactions'] = $result->fetch_assoc()['recent'];

// Unpaid penalties
$result = $conn->query("SELECT SUM(PenaltyAmount) as unpaid FROM penaltytransaction WHERE Status = 'unpaid'");
$stats['unpaid_penalties'] = $result->fetch_assoc()['unpaid'] ?: 0;

// Get recent books added
$recent_books = [];
$result = $conn->query("SELECT b.Title, b.ISBN, b.AccessionNumber, c.CategoryName, 
                       CONCAT(a.FirstName, ' ', a.LastName) as Author
                       FROM book b
                       LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                       LEFT JOIN category c ON b.CategoryID = c.CategoryID
                       ORDER BY b.DateAdded DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_books[] = $row;
    }
}

// Get overdue books
$overdue_books = [];
$result = $conn->query("SELECT l.TransactionID, b.Title, 
                       CONCAT(br.FirstName, ' ', br.LastName) as Borrower,
                       l.DueDate, DATEDIFF(CURDATE(), l.DueDate) as DaysOverdue
                       FROM loan l
                       JOIN book b ON l.BookID = b.BookID
                       JOIN borrowers br ON l.BorrowerID = br.BorrowerID
                       WHERE l.Status = 'borrowed' AND l.DueDate < CURDATE()
                       ORDER BY DaysOverdue DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $overdue_books[] = $row;
    }
}

// Get recent returns
$recent_returns = [];
$result = $conn->query("SELECT l.TransactionID, b.Title, 
                       CONCAT(br.FirstName, ' ', br.LastName) as Borrower,
                       l.DateReturned
                       FROM loan l
                       JOIN book b ON l.BookID = b.BookID
                       JOIN borrowers br ON l.BorrowerID = br.BorrowerID
                       WHERE l.Status = 'returned'
                       ORDER BY l.DateReturned DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_returns[] = $row;
    }
}

$conn->close();
?>

<!-- MAIN DASHBOARD CONTENT -->
<main class="dashboard-content">
    <div class="dashboard-container">
        <h2>Library Dashboard</h2>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="card">
                <div class="card-icon bg-primary">
                    <i class="fas fa-book"></i>
                </div>
                <div class="card-info">
                    <h3><?php echo $stats['total_books']; ?></h3>
                    <p>Total Books</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon bg-success">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="card-info">
                    <h3><?php echo $stats['available_books']; ?></h3>
                    <p>Available Books</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon bg-warning">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="card-info">
                    <h3><?php echo $stats['active_loans']; ?></h3>
                    <p>Active Loans</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon bg-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="card-info">
                    <h3><?php echo $stats['overdue_loans']; ?></h3>
                    <p>Overdue Loans</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-info">
                    <h3><?php echo $stats['total_borrowers']; ?></h3>
                    <p>Registered Borrowers</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-icon bg-secondary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-info">
                    <h3>₱<?php echo number_format($stats['unpaid_penalties'], 2); ?></h3>
                    <p>Unpaid Penalties</p>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Recent Books Added -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-book-medical"></i> Recently Added Books</h3>
                    <div class="section-content">
                        <?php if (!empty($recent_books)): ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>ISBN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['Title']; ?></td>
                                        <td><?php echo $book['Author']; ?></td>
                                        <td><?php echo $book['CategoryName']; ?></td>
                                        <td><?php echo $book['ISBN']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No recently added books</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Overdue Books -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-exclamation-circle"></i> Overdue Books</h3>
                    <div class="section-content">
                        <?php if (!empty($overdue_books)): ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Borrower</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdue_books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['Title']; ?></td>
                                        <td><?php echo $book['Borrower']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['DueDate'])); ?></td>
                                        <td class="text-danger"><?php echo $book['DaysOverdue']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="loan_transactions.php?status=overdue" class="view-all">View All Overdue Books</a>
                        <?php else: ?>
                            <p class="no-data">No overdue books</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="right-column">
                <!-- Recent Returns -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-undo"></i> Recently Returned Books</h3>
                    <div class="section-content">
                        <?php if (!empty($recent_returns)): ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Borrower</th>
                                        <th>Return Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_returns as $return): ?>
                                    <tr>
                                        <td><?php echo $return['Title']; ?></td>
                                        <td><?php echo $return['Borrower']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($return['DateReturned'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="loan_transactions.php?status=returned" class="view-all">View All Returns</a>
                        <?php else: ?>
                            <p class="no-data">No recent returns</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="loan.php" class="action-btn bg-primary">
                            <i class="fas fa-book-reader"></i> New Loan
                        </a>
                        <a href="loan_transactions.php" class="action-btn bg-success">
                            <i class="fas fa-exchange-alt"></i> Manage Loans
                        </a>
                        <a href="add_book.php" class="action-btn bg-warning">
                            <i class="fas fa-book-medical"></i> Add New Book
                        </a>
                        <a href="borrowers.php" class="action-btn bg-info">
                            <i class="fas fa-user-plus"></i> Register Borrower
                        </a>
                        <a href="reports.php" class="action-btn bg-secondary">
                            <i class="fas fa-chart-bar"></i> Generate Reports
                        </a>
                        <a href="settings.php" class="action-btn bg-dark">
                            <i class="fas fa-cog"></i> System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>