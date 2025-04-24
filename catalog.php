<?php
session_start();
    
include 'db.php'; 
include 'navbar.php'; 

// Initialize search and filtering variables
$search_query = "";
$filter_category = "";
$filter_location = "";
$filter_material = "";
$filter_classification = "";
$search_performed = false; // Flag to track if search was performed

if (isset($_POST['search'])) {
    // Get input values and sanitize them
    $search_query = mysqli_real_escape_string($conn, $_POST['search_query']);
    $filter_category = mysqli_real_escape_string($conn, $_POST['filter_category']);
    $filter_location = mysqli_real_escape_string($conn, $_POST['filter_location']);
    $filter_material = mysqli_real_escape_string($conn, $_POST['filter_material']);
    $filter_classification = mysqli_real_escape_string($conn, $_POST['filter_classification']);
    
    // Set search_performed to true only if at least one search field has content
    $search_performed = !empty($search_query) || 
                        !empty($filter_category) || 
                        !empty($filter_location) || 
                        !empty($filter_material) || 
                        !empty($filter_classification);
}

// Only execute query if search was performed
$result = null;
if ($search_performed) {
    // Build the query with initial conditions
    $query = "SELECT b.*, 
                     a.FirstName AS AuthorFirstName, a.MiddleName AS AuthorMiddleName, a.LastName AS AuthorLastName,
                     c.CategoryName, 
                     l.LocationName,
                     m.MaterialName,
                     mc.ClassificationNumber AS MainClassNumber, mc.Description AS MainClassification,
                     sc.SubClassificationNumber, sc.Description AS SubClassification
              FROM book b
              LEFT JOIN authors a ON b.AuthorID = a.AuthorID
              LEFT JOIN category c ON b.CategoryID = c.CategoryID
              LEFT JOIN location l ON b.LocationID = l.LocationID
              LEFT JOIN material m ON b.MaterialID = m.MaterialID
              LEFT JOIN mainclassification mc ON b.MainClassificationID = mc.MainClassificationID
              LEFT JOIN subclassification sc ON b.SubClassificationID = sc.SubClassificationID
              WHERE 1=1";

    // Add search conditions if a search query is provided
    if (!empty($search_query)) {
        $query .= " AND (b.Title LIKE '%$search_query%' 
                        OR CONCAT(a.FirstName, ' ', IFNULL(a.MiddleName, ''), ' ', a.LastName) LIKE '%$search_query%' 
                        OR b.ISBN LIKE '%$search_query%'
                        OR b.CallNumber LIKE '%$search_query%'
                        OR b.AccessionNumber LIKE '%$search_query%'
                        OR b.Publisher LIKE '%$search_query%'
                        OR b.Edition LIKE '%$search_query%')";
    }

    // Add category filter if selected
    if (!empty($filter_category)) {
        $query .= " AND b.CategoryID = '$filter_category'";
    }

    // Add location filter if selected
    if (!empty($filter_location)) {
        $query .= " AND b.LocationID = '$filter_location'";
    }

    // Add material filter if selected
    if (!empty($filter_material)) {
        $query .= " AND b.MaterialID = '$filter_material'";
    }

    // Add classification filter if selected
    if (!empty($filter_classification)) {
        $query .= " AND (b.MainClassificationID = '$filter_classification' OR b.SubClassificationID = '$filter_classification')";
    }

    // Execute the query
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "Error in query: " . mysqli_error($conn); // Error debugging
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Catalog</title>
    <link rel="stylesheet" href="css/catalog.css"> <!-- Link to your CSS file -->
  
</head>
<body>
    <?php include('header.php'); ?>
   
    <div class="container-fluid">
        <!-- Breadcrumbs -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <h2>Library Catalog</h2>
            </li>
        </ol>

        <!-- Search and Filter Form -->
        <form method="post" class="form-inline mb-4">
            <div class="form-row">
                <div class="col-md-12 mb-3">
                    <input type="text" name="search_query" class="form-control mr-2" placeholder="Search by title, author, ISBN, call number, accession number, publisher or edition" value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="col-md-2 mb-3">
                    <select name="filter_category" class="form-control">
                        <option value="">All Categories</option>
                        <?php
                        $category_query = mysqli_query($conn, "SELECT * FROM category");
                        while ($category = mysqli_fetch_assoc($category_query)) {
                            echo "<option value='" . $category['CategoryID'] . "' " . ($filter_category == $category['CategoryID'] ? "selected" : "") . ">" . $category['CategoryName'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <select name="filter_location" class="form-control">
                        <option value="">All Locations</option>
                        <?php
                        $location_query = mysqli_query($conn, "SELECT * FROM location");
                        while ($location = mysqli_fetch_assoc($location_query)) {
                            echo "<option value='" . $location['LocationID'] . "' " . ($filter_location == $location['LocationID'] ? "selected" : "") . ">" . $location['LocationName'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <select name="filter_material" class="form-control">
                        <option value="">All Materials</option>
                        <?php
                        $material_query = mysqli_query($conn, "SELECT * FROM material");
                        while ($material = mysqli_fetch_assoc($material_query)) {
                            echo "<option value='" . $material['MaterialID'] . "' " . ($filter_material == $material['MaterialID'] ? "selected" : "") . ">" . $material['MaterialName'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <select name="filter_classification" class="form-control">
                        <option value="">All Classifications</option>
                        <?php
                        // Main classifications
                        $main_class_query = mysqli_query($conn, "SELECT * FROM mainclassification");
                        while ($main_class = mysqli_fetch_assoc($main_class_query)) {
                            echo "<option value='" . $main_class['MainClassificationID'] . "' " . ($filter_classification == $main_class['MainClassificationID'] ? "selected" : "") . ">" . $main_class['ClassificationNumber'] . " - " . $main_class['Description'] . "</option>";
                        }
                        
                        // Sub classifications
                        $sub_class_query = mysqli_query($conn, "SELECT sc.*, mc.ClassificationNumber AS MainClassNumber 
                                                               FROM subclassification sc
                                                               JOIN mainclassification mc ON sc.MainClassID = mc.MainClassificationID");
                        while ($sub_class = mysqli_fetch_assoc($sub_class_query)) {
                            echo "<option value='" . $sub_class['SubClassificationID'] . "' " . ($filter_classification == $sub_class['SubClassificationID'] ? "selected" : "") . ">" . $sub_class['MainClassNumber'] . $sub_class['SubClassificationNumber'] . " - " . $sub_class['Description'] . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-1 mb-3">
                    <button type="submit" name="search" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>

        <?php if (!$search_performed): ?>
        <!-- Initial message when no search has been performed -->
        <div class="alert alert-info">
            Please use the search form above to find books in the catalog.
        </div>
        <?php else: ?>
            <!-- Catalog Table - Only displayed after search -->
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Title</th>
                            <th>ISBN</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Material</th>
                            <th>Location</th>
                            <th>Main Classification</th>
                            <th>Sub Classification</th>
                            <th>Call Number</th>
                            <th>Total Copies</th>
                            <th>Hold Copies</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Publisher</th>
                            <th>Edition</th>
                            <th>Year</th>
                            <th>Accession Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = mysqli_fetch_assoc($result)) {
                            $authorName = $row['AuthorFirstName'] . 
                                         (!empty($row['AuthorMiddleName']) ? ' ' . $row['AuthorMiddleName'] : '') . 
                                         ' ' . $row['AuthorLastName'];
                            
                            $availableCopies = $row['TotalCopies'] - $row['HoldCopies'];
                            
                            echo "<tr>
                                <td>" . htmlspecialchars($row['Title']) . "</td>
                                <td>" . htmlspecialchars($row['ISBN']) . "</td>
                                <td>" . htmlspecialchars($authorName) . "</td>
                                <td>" . htmlspecialchars($row['CategoryName']) . "</td>
                                <td>" . htmlspecialchars($row['MaterialName']) . "</td>
                                <td>" . htmlspecialchars($row['LocationName']) . "</td>
                                <td>" . htmlspecialchars($row['MainClassNumber'] . ' - ' . $row['MainClassification']) . "</td>
                                <td>" . htmlspecialchars($row['SubClassificationNumber'] . ' - ' . $row['SubClassification']) . "</td>
                                <td>" . htmlspecialchars($row['CallNumber']) . "</td>
                                <td>" . htmlspecialchars($row['TotalCopies']) . "</td>
                                <td>" . htmlspecialchars($row['HoldCopies']) . "</td>
                                <td>" . htmlspecialchars($availableCopies) . "</td>
                                <td>" . htmlspecialchars($row['Status']) . "</td>
                                <td>" . htmlspecialchars(number_format($row['Price'], 2)) . "</td>
                                <td>" . htmlspecialchars($row['Publisher']) . "</td>
                                <td>" . htmlspecialchars($row['Edition']) . "</td>
                                <td>" . htmlspecialchars($row['Year']) . "</td>
                                <td>" . htmlspecialchars($row['AccessionNumber']) . "</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class='alert alert-info'>No books found matching your criteria. Please try different search or filter options.</div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>