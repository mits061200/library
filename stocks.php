<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

include('header.php');
include('navbar.php');
include('db.php');

$message = '';
$message_type = '';

// Handle delete from storage
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM storage WHERE StorageID = $id");
    $message = "Item deleted successfully.";
    $message_type = 'success';
    header("Location: inventory.php?msg=" . urlencode($message) . "&type=success");
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = $_GET['type'] ?? '';
}

// Handle donation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donate_book'])) {
    $donate_title = $conn->real_escape_string(trim($_POST['donate_title']));
    $donate_quantity = intval($_POST['donate_quantity']);
    $donate_remarks = "This book is donation";

    if ($donate_title && $donate_quantity > 0) {
        $check = $conn->query("SELECT * FROM storage WHERE ItemDescription = '$donate_title'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE storage SET Quantity = Quantity + $donate_quantity, Remarks = '$donate_remarks' WHERE ItemDescription = '$donate_title'");
        } else {
            $conn->query("INSERT INTO storage (ItemDescription, Quantity, Remarks) VALUES ('$donate_title', $donate_quantity, '$donate_remarks')");
        }
        $message = "Donation Added \"$donate_title\"!";
        $message_type = 'success';
    } else {
        $message = "Please enter a valid title and quantity.";
        $message_type = 'error';
    }
}

// Handle add stock from PO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['po_id'])) {
    $po_id = intval($_POST['po_id']);
    $items = $conn->query("SELECT Description, Quantity FROM purchase_order_items WHERE PurchaseOrderID = $po_id");
    while ($item = $items->fetch_assoc()) {
        $desc = $conn->real_escape_string($item['Description']);
        $qty = intval($item['Quantity']);
        $check = $conn->query("SELECT * FROM storage WHERE ItemDescription = '$desc'");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE storage SET Quantity = Quantity + $qty WHERE ItemDescription = '$desc'");
        } else {
            $conn->query("INSERT INTO storage (ItemDescription, Quantity) VALUES ('$desc', $qty)");
        }
    }
    $message = "Stock added from PO #$po_id!";
    $message_type = 'success';
}

// Fetch books
$book_sql = "
    SELECT 
        b.BookID, b.Title, b.ISBN, b.TotalCopies, b.HoldCopies, 
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName
    FROM book b
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
";
$books = $conn->query($book_sql)->fetch_all(MYSQLI_ASSOC);

// Fetch storage and PO
$storage_result = $conn->query("SELECT * FROM storage");
$po_result = $conn->query("SELECT PurchaseOrderID, ProjectName, PurchaseOrderDate FROM purchase_orders");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock In</title>
    <link rel="stylesheet" href="css/stock_in.css">
   
</head>
<body>
    <div class="content">
        <h1>Stock In</h1>

        <?php if ($message): ?>
            <div class="msg <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Book Stocks Section -->
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
                        $available = $book['TotalCopies'];
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

     
<!-- Storage Section -->
<h2>Storage</h2>

<!-- Add Stock from PO -->
<form method="post" class="add-stock-form" style="margin-bottom: 32px; max-width:600px;">
    <div class="form-group">
        <label for="po_id" class="form-label">Add Stock from PO:</label>
        <select name="po_id" id="po_id" required>
            <option value="">Select PO</option>
            <?php while ($po = $po_result->fetch_assoc()): ?>
                <option value="<?= $po['PurchaseOrderID'] ?>">
                    <?= $po['PurchaseOrderID'] ?> - <?= htmlspecialchars($po['ProjectName']) ?> (<?= $po['PurchaseOrderDate'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="donate-btn">Add Stock</button>
    </div>
</form>

<!-- Donate a Book -->
<form method="post" class="donate-form" style="margin-bottom: 32px; max-width:900px;">
    <h3 style="margin-bottom:12px;">Donate a Book</h3>
    <div class="form-group">
        <div class="input-wrap">
            <label for="donate_title">Book Title:</label>
            <input type="text" id="donate_title" name="ItemDescription" required>
        </div>
        <div class="input-wrap">
            <label for="donate_quantity">Quantity:</label>
            <input type="number" id="donate_quantity" name="donate_quantity" min="1" required>
        </div>
        <button type="submit" name="donate_book" class="donate-btn">Donate</button>
    </div>
</form>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Item Description</th>
                <th>Quantity</th>
                <th>Remarks</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $storage_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['ItemDescription']) ?></td>
                <td><?= $row['Quantity'] ?></td>
                <td><?= htmlspecialchars($row['Remarks']) ?></td>
                <td>
                    <a href="edit_storage.php?id=<?= $row['StorageID'] ?>" class="action-btn edit-btn">Edit</a>
                    <form action="inventory.php" method="get" style="display:inline;">
                        <input type="hidden" name="delete" value="<?= $row['StorageID'] ?>">
                        <button type="submit" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
