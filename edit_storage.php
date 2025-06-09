<?php
include('db.php');
include('header.php');
include('navbar.php');

if (!isset($_GET['id'])) {
    echo "<p>No storage item selected.</p>";
    exit;
}

$id = intval($_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc = $conn->real_escape_string($_POST['ItemDescription']);
    $qty = intval($_POST['Quantity']);

    $conn->query("UPDATE storage SET ItemDescription = '$desc', Quantity = $qty WHERE StorageID = $id");
    header("Location: storage.php");
    exit;
}

// Fetch current item data
$result = $conn->query("SELECT * FROM storage WHERE StorageID = $id");
if ($result->num_rows === 0) {
    echo "<p>Item not found.</p>";
    exit;
}
$item = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Storage Item</title>
    <link rel="stylesheet" href="css/storage.css">
</head>
<body>
<div class="container-storage">
    <h1>Edit Storage Item</h1>
    <form method="post">
        <div class="form-group">
            <label for="ItemDescription">Item Description:</label>
            <input type="text" id="ItemDescription" name="ItemDescription" value="<?= htmlspecialchars($item['ItemDescription']) ?>" required>
        </div>
        <div class="form-group">
            <label for="Quantity">Quantity:</label>
            <input type="number" id="Quantity" name="Quantity" value="<?= $item['Quantity'] ?>" min="0" required>
        </div>
        <button type="submit" class="action-btn edit-btn">Save</button>
        <a href="storage.php" class="action-btn">Cancel</a>
    </form>
</div>
</body>
</html>