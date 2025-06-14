<?php
// filepath: c:\xampp\htdocs\library\view_holdings.php
include('db.php');
include('header.php');
include('navbar.php');

// Pagination setup
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Count total records for pagination
$count_sql = "
    SELECT COUNT(*) as total
    FROM book b
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
    LEFT JOIN category c ON b.CategoryID = c.CategoryID
    LEFT JOIN location l ON b.LocationID = l.LocationID
";
if ($search) {
    $count_sql .= " WHERE 
        b.Title LIKE '%$search%' OR 
        b.ISBN LIKE '%$search%' OR 
        c.CategoryName LIKE '%$search%' OR 
        l.LocationName LIKE '%$search%' OR
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) LIKE '%$search%'
    ";
}
$count_result = $conn->query($count_sql);
$total_records = $count_result ? (int)$count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated book holdings
$sql = "
    SELECT 
        b.Title,
        b.ISBN,
        b.TotalCopies,
        b.HoldCopies,
        c.CategoryName AS Category,
        l.LocationName AS Location,
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName
    FROM book b
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
    LEFT JOIN category c ON b.CategoryID = c.CategoryID
    LEFT JOIN location l ON b.LocationID = l.LocationID
";
if ($search) {
    $sql .= " WHERE 
        b.Title LIKE '%$search%' OR 
        b.ISBN LIKE '%$search%' OR 
        c.CategoryName LIKE '%$search%' OR 
        l.LocationName LIKE '%$search%' OR
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) LIKE '%$search%'
    ";
}
$sql .= " ORDER BY b.Title ASC LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Book Holdings</title>
    <link rel="stylesheet" href="css/view_holdings.css">
</head>
<body>
<div class="content">
    <h2>Library Book Holdings</h2>
    <form class="filter-form" method="get" action="">
        <input type="text" name="search" placeholder="Search title, author, ISBN, category, location..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="view_holdings.php" style="margin-left:10px; color:#2196f3; text-decoration:underline;">Clear</a>
        <?php endif; ?>
    </form>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Total Copies</th>
                    <th>Hold Copies</th>
                    <th>Available</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Title']) ?></td>
                            <td><?= htmlspecialchars($row['AuthorName']) ?></td>
                            <td><?= htmlspecialchars($row['ISBN']) ?></td>
                            <td><?= htmlspecialchars($row['Category']) ?></td>
                            <td><?= htmlspecialchars($row['Location']) ?></td>
                            <td><?= $row['TotalCopies'] ?></td>
                            <td><?= $row['HoldCopies'] ?></td>
                            <td><?= $row['TotalCopies'] - $row['HoldCopies'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No book holdings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination Links -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top:18px;">
            <?php if ($page > 1): ?>
                <a href="?search=<?= urlencode($search) ?>&page=1">&laquo; First</a>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>">&lt; Prev</a>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $total_pages ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>">Next &gt;</a>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $total_pages ?>">Last &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>