<?php
// get_book_details.php
include 'db.php'; // Connect to database

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Book ID is required']);
    exit;
}

$id = (int)$_GET['id'];

// Fetch book details
$sql = "
    SELECT 
        b.*, 
        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName,
        c.CategoryName,
        m.MaterialName,
        l.LocationName,
        mc.Description AS MainClassificationName,
        sc.Description AS SubClassificationName
    FROM book b
    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
    LEFT JOIN category c ON b.CategoryID = c.CategoryID
    LEFT JOIN material m ON b.MaterialID = m.MaterialID
    LEFT JOIN location l ON b.LocationID = l.LocationID
    LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
    LEFT JOIN subclassification sc ON b.SubClassificationID = sc.SubClassificationID
    WHERE b.BookID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Book not found']);
    exit;
}

$book = $result->fetch_assoc();

// Return book details as JSON
header('Content-Type: application/json');
echo json_encode($book);
?>