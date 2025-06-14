<?php
include('db.php');
include('header.php');
include('navbar.php');

// Fetch all books with their total copies
$sql = "
    SELECT 
        b.BookID,
        b.Title,
        b.ISBN,
        b.TotalCopies,
        b.HoldCopies,
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName
    FROM book b
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
    ORDER BY b.Title ASC
";
$result = $conn->query($sql);

// Handle disposal action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispose_book'])) {
    $book_id = (int)$_POST['book_id'];
    $dispose_qty = (int)$_POST['dispose_qty'];

    // Get current total copies
    $book = $conn->query("SELECT TotalCopies FROM book WHERE BookID = $book_id")->fetch_assoc();
    if ($book && $dispose_qty > 0 && $dispose_qty <= $book['TotalCopies']) {
        $conn->query("UPDATE book SET TotalCopies = TotalCopies - $dispose_qty WHERE BookID = $book_id");
        echo "<script>alert('Disposed $dispose_qty copies successfully.');window.location='aging_books.php';</script>";
        exit;
    } else {
        echo "<script>alert('Invalid quantity to dispose.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aging Books / Disposal</title>
    <link rel="stylesheet" href="css/aging_books.css">
    <style>
        .dispose-form { display: flex; gap: 8px; align-items: center; }
        .dispose-form input[type="number"] { width: 60px; padding: 4px 6px; }
        .dispose-btn {
            background: #e53935;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 4px 12px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .dispose-btn:hover { background: #b71c1c; }
    </style>
</head>
<body>
<div class="content">
    <h2>Aging Books / Disposal</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Total Copies</th>
                    <th>Hold Copies</th>
                    <th>Available</th>
                    <th>Dispose (Aged)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Title']) ?></td>
                            <td><?= htmlspecialchars($row['AuthorName']) ?></td>
                            <td><?= htmlspecialchars($row['ISBN']) ?></td>
                            <td><?= $row['TotalCopies'] ?></td>
                            <td><?= $row['HoldCopies'] ?></td>
                            <td><?= $row['TotalCopies'] - $row['HoldCopies'] ?></td>
                            <td>
                                <form method="post" class="dispose-form" onsubmit="return confirm('Dispose selected copies?');">
                                    <input type="hidden" name="book_id" value="<?= $row['BookID'] ?>">
                                    <input type="number" name="dispose_qty" min="1" max="<?= $row['TotalCopies'] ?>" required>
                                    <button type="submit" name="dispose_book" class="dispose-btn">Dispose</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No books found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>