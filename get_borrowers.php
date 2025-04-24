<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "library"); // Update with your actual credentials
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search term
$search = isset($_GET['term']) ? $conn->real_escape_string($_GET['term']) : '';

// Query borrowers matching the search term
$sql = "SELECT BorrowerID, FirstName, MiddleName, LastName 
        FROM borrowers 
        WHERE BorrowerID LIKE '%$search%' 
        OR FirstName LIKE '%$search%' 
        OR LastName LIKE '%$search%' 
        LIMIT 5";
        
$result = $conn->query($sql);
$borrowers = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $name = $row['FirstName'];
        if (!empty($row['MiddleName'])) {
            $name .= ' ' . $row['MiddleName'];
        }
        $name .= ' ' . $row['LastName'];
        
        $borrowers[] = [
            'id' => $row['BorrowerID'],
            'name' => $name,
            'value' => $row['BorrowerID'] . ' - ' . $name,
            'borrower_id' => $row['BorrowerID'],   // Add this for direct access to the ID
            'first_name' => $row['FirstName'],     // Include these fields for direct form submission
            'middle_name' => $row['MiddleName'],
            'last_name' => $row['LastName']
        ];
    }
}

// Return results as JSON
header('Content-Type: application/json');
echo json_encode($borrowers);

$conn->close();
?>