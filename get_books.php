<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "library"); // Update with your actual credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search term
$search = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';

// Query books matching the search term
$sql = "SELECT b.BookID, b.Title, b.ISBN, b.AccessionNumber, b.Status,
               a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
               m.MaterialName AS Category, mc.ClassificationNumber, mc.Description AS Classification,
               l.LocationName, b.CallNumber, b.TotalCopies, b.HoldCopies, b.Year, b.Publisher, b.Edition, b.Price
        FROM book b
        LEFT JOIN authors a ON b.AuthorID = a.AuthorID
        LEFT JOIN material m ON b.MaterialID = m.MaterialID
        LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
        LEFT JOIN location l ON b.LocationID = l.LocationID
        WHERE (b.Title LIKE '%$search%' 
        OR b.ISBN LIKE '%$search%' 
        OR b.AccessionNumber LIKE '%$search%')
        AND b.Status = 'Available' 
        LIMIT 5";
        
$result = $conn->query($sql);
$books = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $author = $row['AuthorFirstName'];
        if (!empty($row['AuthorMiddleName'])) {
            $author .= ' ' . $row['AuthorMiddleName'];
        }
        $author .= ' ' . $row['AuthorLastName'];
        
        $books[] = [
            'id' => $row['BookID'],
            'title' => $row['Title'],
            'value' => $row['Title'] . ' - ' . $author,
            'isbn' => $row['ISBN'],
            'accession' => $row['AccessionNumber'],
            'book_id' => $row['BookID'],        // Add these fields for direct form submission
            'status' => $row['Status'],
            'author' => $author,
            'call_number' => $row['CallNumber'],
            'category' => $row['Category'],
            'classification' => $row['Classification'],
            'classification_number' => $row['ClassificationNumber'],
            'total_copies' => $row['TotalCopies'],
            'hold_copies' => $row['HoldCopies'],
            'year' => $row['Year'],
            'publisher' => $row['Publisher'],
            'edition' => $row['Edition'],
            'location' => $row['LocationName'],
            'price' => $row['Price']
        ];
    }
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($books);

$conn->close();
?>