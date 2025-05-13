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

// 1. Book Statistics
$result = $conn->query("SELECT COUNT(*) as total FROM book");
$stats['total_books'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT SUM(TotalCopies) as total_copies FROM book");
$stats['total_copies'] = $result->fetch_assoc()['total_copies'];

$result = $conn->query("SELECT COUNT(*) as available FROM book WHERE Status = 'Available'");
$stats['available_books'] = $result->fetch_assoc()['available'];

$result = $conn->query("SELECT COUNT(DISTINCT AuthorID) as authors FROM book");
$stats['total_authors'] = $result->fetch_assoc()['authors'];

// 2. Loan Statistics
$result = $conn->query("SELECT COUNT(*) as active FROM loan WHERE Status = 'borrowed'");
$stats['active_loans'] = $result->fetch_assoc()['active'];

$result = $conn->query("SELECT COUNT(*) as overdue FROM loan WHERE Status = 'borrowed' AND DueDate < CURDATE()");
$stats['overdue_loans'] = $result->fetch_assoc()['overdue'];

$result = $conn->query("SELECT COUNT(*) as returned FROM loan WHERE Status = 'returned' AND DateReturned >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stats['recent_returns'] = $result->fetch_assoc()['returned'];

// 3. Borrower Statistics
$result = $conn->query("SELECT COUNT(*) as borrowers FROM borrowers");
$stats['total_borrowers'] = $result->fetch_assoc()['borrowers'];

$result = $conn->query("SELECT COUNT(*) as students FROM borrowers WHERE Role = 'Student'");
$stats['student_borrowers'] = $result->fetch_assoc()['students'];

$result = $conn->query("SELECT COUNT(*) as faculty FROM borrowers WHERE Role = 'Faculty'");
$stats['faculty_borrowers'] = $result->fetch_assoc()['faculty'];

// 4. Penalty Statistics
$result = $conn->query("SELECT SUM(PenaltyAmount) as unpaid FROM penaltytransaction WHERE Status = 'unpaid'");
$stats['unpaid_penalties'] = $result->fetch_assoc()['unpaid'] ?: 0;

$result = $conn->query("SELECT SUM(PenaltyAmount) as paid FROM penaltytransaction WHERE Status = 'paid' AND DatePaid >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stats['recent_payments'] = $result->fetch_assoc()['paid'] ?: 0;

// 5. Acquisition Statistics
$result = $conn->query("SELECT COUNT(*) as recent_acquisitions FROM book WHERE AcquisitionDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stats['recent_acquisitions'] = $result->fetch_assoc()['recent_acquisitions'];

$result = $conn->query("SELECT SUM(Price) as total_value FROM book");
$stats['collection_value'] = $result->fetch_assoc()['total_value'] ?: 0;

// Get recent books added
$recent_books = [];
$result = $conn->query("SELECT b.BookID, b.Title, b.ISBN, b.AccessionNumber, 
                       CONCAT(a.FirstName, ' ', a.LastName) as Author,
                       c.CategoryName, m.MaterialName, l.LocationName,
                       b.Status, b.TotalCopies
                       FROM book b
                       LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                       LEFT JOIN category c ON b.CategoryID = c.CategoryID
                       LEFT JOIN material m ON b.MaterialID = m.MaterialID
                       LEFT JOIN location l ON b.LocationID = l.LocationID
                       ORDER BY b.CreatedAt DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_books[] = $row;
    }
}

// Get overdue books with borrower info
$overdue_books = [];
$result = $conn->query("SELECT l.TransactionID, b.Title, b.AccessionNumber,
                       CONCAT(br.FirstName, ' ', br.LastName) as Borrower,
                       br.Role, br.ContactNumber,
                       l.DateBorrowed, l.DueDate, 
                       DATEDIFF(CURDATE(), l.DueDate) as DaysOverdue,
                       p.PenaltyRate
                       FROM loan l
                       JOIN book b ON l.BookID = b.BookID
                       JOIN borrowers br ON l.BorrowerID = br.BorrowerID
                       LEFT JOIN penalty p ON l.PenaltyID = p.PenaltyID
                       WHERE l.Status = 'borrowed' AND l.DueDate < CURDATE()
                       ORDER BY DaysOverdue DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['estimated_penalty'] = $row['DaysOverdue'] * $row['PenaltyRate'];
        $overdue_books[] = $row;
    }
}

// Get recent returns with penalty info
$recent_returns = [];
$result = $conn->query("SELECT l.TransactionID, b.Title, 
                       CONCAT(br.FirstName, ' ', br.LastName) as Borrower,
                       l.DateReturned, pt.PenaltyAmount, pt.Status as PenaltyStatus
                       FROM loan l
                       JOIN book b ON l.BookID = b.BookID
                       JOIN borrowers br ON l.BorrowerID = br.BorrowerID
                       LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
                       WHERE l.Status = 'returned'
                       ORDER BY l.DateReturned DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_returns[] = $row;
    }
}

// Get recent acquisitions
$recent_acquisitions = [];
$result = $conn->query("SELECT b.BookID, b.Title, b.ISBN, b.AcquisitionDate, b.Price,
                       CONCAT(a.FirstName, ' ', a.LastName) as Author,
                       c.CategoryName, b.Publisher
                       FROM book b
                       LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                       LEFT JOIN category c ON b.CategoryID = c.CategoryID
                       WHERE b.AcquisitionDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                       ORDER BY b.AcquisitionDate DESC LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_acquisitions[] = $row;
    }
}

$conn->close();
?>

<!-- MAIN DASHBOARD CONTENT -->
<main class="dashboard-content">
    <div class="dashboard-container">
        <h2>Library Management Dashboard</h2>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <!-- Book Statistics -->
            <a href="book.php" class="card-link">
                <div class="card">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $stats['total_books']; ?></h3>
                        <p>Total Titles</p>
                        <small><?php echo $stats['total_copies']; ?> physical copies</small>
                    </div>
                </div>
            </a>
            
            <a href="author.php?filter=available" class="card-link">
                <div class="card">
                    <div class="card-icon bg-success">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $stats['available_books']; ?></h3>
                        <p>Available Titles</p>
                        <small><?php echo $stats['total_authors']; ?> authors</small>
                    </div>
                </div>
            </a>
            
            <!-- Loan Statistics -->
            <a href="loan_transactions.php?status=borrowed" class="card-link">
                <div class="card">
                    <div class="card-icon bg-info">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $stats['active_loans']; ?></h3>
                        <p>Active Loans</p>
                        <small><?php echo $stats['recent_returns']; ?> recent returns</small>
                    </div>
                </div>
            </a>
            
            <a href="loan_transactions.php?status=overdue" class="card-link">
                <div class="card">
                    <div class="card-icon bg-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $stats['overdue_loans']; ?></h3>
                        <p>Overdue Loans</p>
                        <small>₱<?php echo number_format($stats['unpaid_penalties'], 2); ?> unpaid</small>
                    </div>
                </div>
            </a>
            
            <!-- Borrower Statistics -->
            <a href="borrower.php" class="card-link">
                <div class="card">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h3><?php echo $stats['total_borrowers']; ?></h3>
                        <p>Registered Borrowers</p>
                        <small><?php echo $stats['student_borrowers']; ?> students, <?php echo $stats['faculty_borrowers']; ?> faculty</small>
                    </div>
                </div>
            </a>
            
            <!-- Financial Statistics -->
            <a href="add_po.php" class="card-link">
                <div class="card">
                    <div class="card-icon bg-secondary">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-info">
                        <h3>₱<?php echo number_format($stats['collection_value'], 2); ?></h3>
                        <p>Collection Value</p>
                        <small><?php echo $stats['recent_acquisitions']; ?> new titles</small>
                    </div>
                </div>
            </a>
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
                                        <th>Material</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_books as $book): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $book['Title']; ?></strong>
                                            <div class="text-muted small"><?php echo $book['ISBN']; ?></div>
                                        </td>
                                        <td><?php echo $book['Author']; ?></td>
                                        <td><?php echo $book['CategoryName']; ?></td>
                                        <td><?php echo $book['MaterialName']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $book['Status'] == 'Available' ? 'status-active' : 'status-unavailable'; ?>">
                                                <?php echo $book['Status']; ?>
                                                <div class="text-muted small"><?php echo $book['TotalCopies']; ?> copies</div>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="book.php" class="view-all">View All Books</a>
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
                                        <th>Est. Penalty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdue_books as $book): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $book['Title']; ?></strong>
                                            <div class="text-muted small">Acc: <?php echo $book['AccessionNumber']; ?></div>
                                        </td>
                                        <td>
                                            <?php echo $book['Borrower']; ?>
                                            <div class="text-muted small"><?php echo $book['Role']; ?></div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($book['DueDate'])); ?></td>
                                        <td class="text-danger"><?php echo $book['DaysOverdue']; ?></td>
                                        <td>₱<?php echo number_format($book['estimated_penalty'], 2); ?></td>
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
                <!-- Quick Actions -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="loan.php" class="action-btn bg-primary">
                            <i class="fas fa-book-reader"></i> New Loan
                        </a>
                        <a href="loan_transactions.php" class="action-btn bg-success">
                            <i class="fas fa-undo-alt"></i> Process Returns
                        </a>
                        <a href="book.php" class="action-btn bg-warning">
                            <i class="fas fa-book-medical"></i> Add New Book
                        </a>
                        <a href="borrower.php" class="action-btn bg-info">
                            <i class="fas fa-user-edit"></i> Manage Borrowers
                        </a>
                        <a href="loan_transactions.php" class="action-btn bg-danger">
                            <i class="fas fa-exclamation-circle"></i> Manage Penalties
                        </a>
                        <a href="reports.php" class="action-btn bg-secondary">
                            <i class="fas fa-chart-bar"></i> Generate Reports
                        </a>
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
                                        <th>Penalty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_returns as $return): ?>
                                    <tr>
                                        <td><?php echo $return['Title']; ?></td>
                                        <td><?php echo $return['Borrower']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($return['DateReturned'])); ?></td>
                                        <td>
                                            <?php if ($return['PenaltyAmount']): ?>
                                                <span class="status-badge <?php echo $return['PenaltyStatus'] == 'paid' ? 'status-paid' : 'status-unpaid'; ?>">
                                                    ₱<?php echo number_format($return['PenaltyAmount'], 2); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-no-penalty">None</span>
                                            <?php endif; ?>
                                        </td>
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
                
                <!-- Recent Acquisitions -->
                <div class="dashboard-section">
                    <h3><i class="fas fa-shopping-cart"></i> Recent Acquisitions</h3>
                    <div class="section-content">
                        <?php if (!empty($recent_acquisitions)): ?>
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Date</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_acquisitions as $acquisition): ?>
                                    <tr>
                                        <td><?php echo $acquisition['Title']; ?></td>
                                        <td><?php echo $acquisition['Author']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($acquisition['AcquisitionDate'])); ?></td>
                                        <td>₱<?php echo number_format($acquisition['Price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="add_po.php" class="view-all">View All Acquisitions</a>
                        <?php else: ?>
                            <p class="no-data">No recent acquisitions</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                
            </div>
        </div>
    </div>
</main>
