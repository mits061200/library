<?php
include('header.php');
include('navbar.php');
include('db.php'); // Ensure this connects to your database


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect form inputs
    $title = $conn->real_escape_string($_POST['Title']);
    $isbn = $conn->real_escape_string($_POST['ISBN']);
    $authorID = $conn->real_escape_string($_POST['AuthorID']);
    $callNumber = $conn->real_escape_string($_POST['CallNumber']);
    $totalCopies = (int)$_POST['TotalCopies'];
    $holdCopies = (int)$_POST['HoldCopies'];
    $acquisitionDate = $conn->real_escape_string($_POST['AcquisitionDate']);
    $price = $conn->real_escape_string($_POST['Price']);
    $publisher = $conn->real_escape_string($_POST['Publisher']);
    $edition = $conn->real_escape_string($_POST['Edition']);
    $year = (int)$_POST['Year'];
    $accessionNumber = $conn->real_escape_string($_POST['AccessionNumber']);
    $categoryID = $conn->real_escape_string($_POST['CategoryID']);
    $materialID = $conn->real_escape_string($_POST['MaterialID']);
    $locationID = $conn->real_escape_string($_POST['LocationID']);
    $mainClassificationID = $conn->real_escape_string($_POST['MainClassificationID']);
    $subClassificationID = $conn->real_escape_string($_POST['SubClassificationID']);
    $status = 'Available'; // Set the default status

    // Insert data into the `book` table
    $sql = "
        INSERT INTO book (
            Title, ISBN, AuthorID, CallNumber, TotalCopies, HoldCopies, 
            AcquisitionDate, Price, Publisher, Edition, Year, AccessionNumber, 
            CategoryID, MaterialID, LocationID, MainClassificationID, 
            SubClassificationID, Status
        ) VALUES (
            '$title', '$isbn', $authorID, '$callNumber', $totalCopies, $holdCopies,
            '$acquisitionDate', '$price', '$publisher', '$edition', $year, '$accessionNumber',
            $categoryID, $materialID, $locationID, $mainClassificationID,
            $subClassificationID, '$status'
        )
    ";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Book added successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}


// Fetch dropdown options
$authors = $conn->query("SELECT AuthorID, CONCAT(FirstName, ' ', MiddleName, ' ', LastName) AS AuthorName FROM authors")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT CategoryID, CategoryName FROM category")->fetch_all(MYSQLI_ASSOC);
$materials = $conn->query("SELECT MaterialID, MaterialName FROM material")->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query("SELECT LocationID, LocationName FROM location")->fetch_all(MYSQLI_ASSOC);
$mainClassifications = $conn->query("SELECT MainClassificationID, Description AS MainClassificationName FROM mainclassification")->fetch_all(MYSQLI_ASSOC);
?>





<div class="content">
    <h2>Add Book</h2>    <form method="post" action="book.php" class="form-container">
        <div class="input-row">
            <div class="input-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="Title" required>
            </div>
            <div class="input-group">
                <label for="isbn">ISBN</label>
                <input type="text" id="isbn" name="ISBN" required>
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="author">Author</label>
                <select id="author" name="AuthorID" required>
                    <option value="" disabled selected>Select Author</option>
                    <?php foreach ($authors as $author) : ?>
                        <option value="<?= $author['AuthorID'] ?>"><?= $author['AuthorName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="call-number">Call Number</label>
                <input type="text" id="call-number" name="CallNumber">
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="copies">Total Copies</label>
                <input type="number" id="copies" name="TotalCopies" required>
            </div>
            <div class="input-group">
                <label for="hold-copies">Hold Copies</label>
                <input type="number" id="hold-copies" name="HoldCopies" required>
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="acquisition-date">Acquisition Date</label>
                <input type="date" id="acquisition-date" name="AcquisitionDate" required>
            </div>
            <div class="input-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="Price">
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" name="Publisher">
            </div>
            <div class="input-group">
                <label for="edition">Edition</label>
                <input type="text" id="edition" name="Edition">
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="year">Year</label>
                <input type="number" id="year" name="Year" min="1900" max="<?= date('Y') ?>">
            </div>
            <div class="input-group">
                <label for="accession-number">Accession Number</label>
                <input type="text" id="accession-number" name="AccessionNumber">
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="category">Category</label>
                <select id="category" name="CategoryID" required>
                    <option value="" disabled selected>Select Category</option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?= $category['CategoryID'] ?>"><?= $category['CategoryName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="material">Material</label>
                <select id="material" name="MaterialID" required>
                    <option value="" disabled selected>Select Material</option>
                    <?php foreach ($materials as $material) : ?>
                        <option value="<?= $material['MaterialID'] ?>"><?= $material['MaterialName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="location">Location</label>
                <select id="location" name="LocationID" required>
                    <option value="" disabled selected>Select Location</option>
                    <?php foreach ($locations as $location) : ?>
                        <option value="<?= $location['LocationID'] ?>"><?= $location['LocationName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
            <label for="sub-classification">Sub Classification</label>
                <select id="sub-classification" name="SubClassificationID" required>
                    <option value="" disabled selected>Select Sub Classification</option>
                </select>


            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
               
                <label for="main-classification">Main Classification</label>
                <select id="main-classification" name="MainClassificationID" required>
                    <option value="" disabled selected>Select Main Classification</option>
                    <?php foreach ($mainClassifications as $classification) : ?>
                        <option value="<?= $classification['MainClassificationID'] ?>"><?= $classification['MainClassificationName'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="status">Status</label>
                <select id="status" name="Status" disabled>
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>
        </div>

        <button type="submit" class="add-btn">Add</button>
    </form>

    <form method="GET" class="filter-form">
    <input type="text" name="search" placeholder="Search by title or ISBN" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
    
    <select name="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $category) : ?>
            <option value="<?= $category['CategoryID'] ?>" <?= (isset($_GET['category']) && $_GET['category'] == $category['CategoryID']) ? 'selected' : '' ?>>
                <?= $category['CategoryName'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="location">
        <option value="">All Locations</option>
        <?php foreach ($locations as $location) : ?>
            <option value="<?= $location['LocationID'] ?>" <?= (isset($_GET['location']) && $_GET['location'] == $location['LocationID']) ? 'selected' : '' ?>>
                <?= $location['LocationName'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
</form>


    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>ISBN</th>
                    <th>Author</th>
                    <th>Call Number</th>
                    <th>Total Copies</th>
                    <th>Hold Copies</th>
                    <th>Available</th>
                    <th>Year</th>
                    <th>Publisher</th>
                    <th>Edition</th>
                    <th>Accession Number</th>
                    <th>Price</th>
                    <th>Location</th>
                    <th>Classification</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
                $location = isset($_GET['location']) ? (int)$_GET['location'] : 0;

                $where = "WHERE 1=1";

                if (!empty($search)) {
                    $where .= " AND (b.Title LIKE '%$search%' OR b.ISBN LIKE '%$search%')";
                }

                if (!empty($category)) {
                    $where .= " AND b.CategoryID = $category";
                }

                if (!empty($location)) {
                    $where .= " AND b.LocationID = $location";
                }

                $sql = "
                    SELECT 
                        b.*, 
                        CONCAT(a.FirstName, ' ', a.MiddleName, ' ', a.LastName) AS AuthorName 
                    FROM book b
                    LEFT JOIN authors a ON b.AuthorID = a.AuthorID
                    $where
                ";

                $books = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);


                foreach ($books as $book) :
                    $available = $book['TotalCopies'] - $book['HoldCopies'];
                ?>
                    <tr>
                        <td><?= $book['Title'] ?></td>
                        <td><?= $book['ISBN'] ?></td>
                        <td><?= $book['AuthorName'] ?></td>
                        <td><?= $book['CallNumber'] ?></td>
                        <td><?= $book['TotalCopies'] ?></td>
                        <td><?= $book['HoldCopies'] ?></td>
                        <td><?= $available ?></td>
                        <td><?= $book['Year'] ?></td>
                        <td><?= $book['Publisher'] ?></td>
                        <td><?= $book['Edition'] ?></td>
                        <td><?= $book['AccessionNumber'] ?></td>
                        <td><?= $book['Price'] ?></td>
                        <td><?= $book['LocationID'] ?></td>
                        <td><?= $book['MainClassificationID'] ?></td>
                        <td><?= $book['Status'] ?></td>
                        <td>
                            <button class="edit">Edit</button>
                            <button class="delete">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="css/book.css">


<script>
document.getElementById('copies').addEventListener('input', updateStatus);
document.getElementById('hold-copies').addEventListener('input', updateStatus);

function updateStatus() {
    const total = parseInt(document.getElementById('copies').value) || 0;
    const hold = parseInt(document.getElementById('hold-copies').value) || 0;
    const status = document.getElementById('status');

    status.disabled = false;
    status.value = (total - hold) > 0 ? 'Available' : 'Unavailable';
}
</script>

<script>
document.getElementById('main-classification').addEventListener('change', function () {
    const mainId = this.value;

    fetch('get_sub_classifications.php?main_id=' + mainId)
        .then(response => response.json())
        .then(data => {
            const subSelect = document.getElementById('sub-classification');
            subSelect.innerHTML = '<option value="">-- Select Sub Classification --</option>';
            data.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.sub_classification_id;
                option.textContent = sub.sub_classification_name;
                subSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
        });
});

</script>
