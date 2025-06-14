<?php
include('header.php');
include('navbar.php');
include('db.php');

// Filter logic
$where = [];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where[] = "(l.TransactionID LIKE '%$search%' OR b.Title LIKE '%$search%' OR CONCAT(br.FirstName, ' ', br.LastName) LIKE '%$search%')";
}
if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $where[] = "DATE(l.DateBorrowed) = '$date'";
}
if (!empty($_GET['month'])) {
    $month = $conn->real_escape_string($_GET['month']);
    $where[] = "DATE_FORMAT(l.DateBorrowed, '%Y-%m') = '$month'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch loans
$sql = "
    SELECT 
        l.TransactionID,
        l.DateBorrowed,
        l.DueDate,
        l.DateReturned,
        l.Status,
        b.Title AS BookTitle,
        CONCAT(br.FirstName, ' ', br.LastName) AS BorrowerName
    FROM loan l
    LEFT JOIN book b ON l.BookID = b.BookID
    LEFT JOIN borrowers br ON l.BorrowerID = br.BorrowerID
    $where_sql
    ORDER BY l.DateBorrowed DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Reports</title>
    <link rel="stylesheet" href="css/reports_inventoryHoldings.css">
</head>
<body>
<div class="report-container">
    <h2 class="report-title">Loan Reports</h2>
    <div class="tab-buttons1">
        <button class="tab-btn1 active">Loans</button>
        <span class="arrow1">&gt;</span>
        <button class="tab-btn1" onclick="window.location.href='reports_returns.php'">Returns</button>
    </div>
    <form method="get" class="filter-form">
        <input type="text" name="search" placeholder="Search Loan ID, Book, Borrower..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <label for="date">By Date:</label>
        <input type="date" name="date" id="date" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>">
        <label for="month">By Month:</label>
        <input type="month" name="month" id="month" value="<?= isset($_GET['month']) ? htmlspecialchars($_GET['month']) : '' ?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['date']) || !empty($_GET['month'])): ?>
            <a href="reports_loans.php" class="search-btn clear-btn" style="text-align:center; text-decoration:none;">Clear</a>
        <?php endif; ?>
        <button type="button" onclick="window.print()" class="search-btn print-btn" style="margin-left:10px;">Print</button>
    </form>

    <table class="po-table">
        <thead>
            <tr>
                <th>Loan ID</th>
                <th>Book Title</th>
                <th>Borrower</th>
                <th>Date Borrowed</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['TransactionID']) ?></td>
                        <td><?= htmlspecialchars($row['BookTitle']) ?></td>
                        <td><?= htmlspecialchars($row['BorrowerName']) ?></td>
                        <td><?= htmlspecialchars($row['DateBorrowed']) ?></td>
                        <td><?= htmlspecialchars($row['DueDate']) ?></td>
                        <td><?= htmlspecialchars($row['Status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No loan records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>