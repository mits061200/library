<?php
include('header.php');
include('navbar.php');
include('db.php');

// Filter logic
$where = [];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where[] = "(b.Title LIKE '%$search%' OR b.ISBN LIKE '%$search%' OR c.CategoryName LIKE '%$search%')";
}
if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $where[] = "DATE(b.AcquisitionDate) = '$date'";
}
if (!empty($_GET['month'])) {
    $month = $conn->real_escape_string($_GET['month']);
    $where[] = "DATE_FORMAT(b.AcquisitionDate, '%Y-%m') = '$month'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch holdings with acquisition date
$sql = "
    SELECT 
        b.BookID,
        b.Title,
        b.ISBN,
        b.TotalCopies,
        b.HoldCopies,
        b.AcquisitionDate,
        c.CategoryName,
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName
    FROM book b
    LEFT JOIN category c ON b.CategoryID = c.CategoryID
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
    $where_sql
    ORDER BY b.AcquisitionDate DESC, b.Title ASC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Holdings Report</title>
    <link rel="stylesheet" href="css/reports_inventoryHoldings.css">
</head>
<body>
<div class="report-container">
    <h2>Library Holdings Report</h2>
    <div class="tab-buttons1">
        <button class="tab-btn1" onclick="window.location.href='reports_inventory.php'">Purchased order</button>
        <span class="arrow1">&gt;</span>
        <button class="tab-btn1 active">Holdings</button>
    </div>
    <form method="get" class="filter-form">
        <input type="text" name="search" placeholder="Search Title, ISBN, Category..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <label for="date">By Date:</label>
        <input type="date" name="date" id="date" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>">
        <label for="month">By Month:</label>
        <input type="month" name="month" id="month" value="<?= isset($_GET['month']) ? htmlspecialchars($_GET['month']) : '' ?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['date']) || !empty($_GET['month'])): ?>
            <a href="reports_inventoryHoldings.php" class="search-btn clear-btn" style="text-align:center; text-decoration:none;">Clear</a>
        <?php endif; ?>
        <button type="button" onclick="window.print()" class="search-btn print-btn" style="margin-left:10px;">Print</button>
    </form>

    <table class="po-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>ISBN</th>
                <th>Category</th>
                <th>Total Copies</th>
                <th>Hold Copies</th>
                <th>Available</th>
                <th>Acquisition Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Title']) ?></td>
                        <td><?= htmlspecialchars($row['AuthorName']) ?></td>
                        <td><?= htmlspecialchars($row['ISBN']) ?></td>
                        <td><?= htmlspecialchars($row['CategoryName']) ?></td>
                        <td><?= $row['TotalCopies'] ?></td>
                        <td><?= $row['HoldCopies'] ?></td>
                        <td><?= $row['TotalCopies'] - $row['HoldCopies'] ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['AcquisitionDate']))) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No holdings found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>