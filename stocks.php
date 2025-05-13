<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

include('header.php');
include('navbar.php');
include('db.php'); // Ensure this connects to your database

// Fetch books and calculate stock
$sql = "
    SELECT 
        b.BookID, b.Title, b.ISBN, b.TotalCopies, b.HoldCopies, 
        b.CanBorrow, b.MaxBorrow, b.CategoryID,
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName
    FROM book b
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
";
$books = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<div class="content">
    <h2>Book Stocks</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>ISBN</th>
                    <th>Author</th>
                    <th>Hold Copies</th>
                    <th>Available Copies</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): 
                    // Available copies are now directly the TotalCopies
                    $available = $book['TotalCopies'];

                    // Define low stock threshold
                    $low_stock = $available < 5;
                ?>
                    <tr class="<?= $low_stock ? 'low-stock' : '' ?>">
                        <td><?= htmlspecialchars($book['Title']) ?></td>
                        <td><?= htmlspecialchars($book['ISBN']) ?></td>
                        <td><?= htmlspecialchars($book['AuthorName']) ?></td>
                        <td><?= htmlspecialchars($book['HoldCopies']) ?></td>
                        <td><?= $available ?></td>

                        <td>
                            <a href="book.php?edit=<?= $book['BookID'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<link rel="stylesheet" href="css/book.css">