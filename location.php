<?php 
include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

// Initialize variables
$edit_mode = false;
$edit_category = [];

// Add location
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM location WHERE locationName = ?");
    $stmt->bind_param("s", $location_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Location already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO location (locationName) VALUES (?)");
        $stmt->bind_param("s", $location_name);
        if ($stmt->execute()) {
            echo "<script>alert('Location added successfully');</script>";
        } else {
            echo "<script>alert('Error adding location');</script>";
        }
        $stmt->close();
    }
}

// Delete location - Fixed Error Handling
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First check if author exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM location WHERE LocationID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        // Location exists, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM location WHERE LocationID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Location deleted successfully'); window.location='location.php';</script>";
        } else {
            echo "<script>alert('Error: Unable to location author. It may be in use.'); window.location='location.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error: Category not found'); window.location='location.php';</script>";
    }
    exit;
}

// Edit Location
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM location WHERE LocationID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_location = $result_edit->fetch_assoc();
    $stmt->close();
    
    if (!$edit_location) {
        echo "<script>alert('Location not found'); window.location='location.php';</script>";
        exit;
    }
}

// Update Location
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_location'])) {
    $id = $_POST['location_id'];
    $location_name = trim($_POST['location_name']);

    // Check if new name already exists for other locations
    $stmt = $conn->prepare("SELECT COUNT(*) FROM location WHERE locationName = ? AND LocationID != ?");
    $stmt->bind_param("si", $location_name, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Another location with this name already exists!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE location SET LocationName = ? WHERE LocationID = ?");
        $stmt->bind_param("si", $location_name, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Location updated successfully'); window.location='location.php';</script>";
        } else {
            echo "<script>alert('Error updating location: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

// Pagination
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search
$search = "";
$search_condition = "";
$total_query = "SELECT COUNT(*) as total FROM location";
$params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE locationName LIKE ?";
        $total_query = "SELECT COUNT(*) as total FROM location $search_condition";
        $params = [$search_param];
    }
}

// Get Total Records
if (!empty($search_condition)) {
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result_total = $stmt->get_result();
    $total_records = $result_total->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result_total = $conn->query($total_query);
    $total_records = $result_total->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Fetch Categories
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT * FROM location $search_condition LIMIT ?, ?");
    $stmt->bind_param(str_repeat('s', count($params)) . "ii", ...[...$params, $offset, $records_per_page]);
} else {
    $stmt = $conn->prepare("SELECT * FROM location LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Location' : 'Add Location' ?></h2>

        <div class="form-container">
            <form action="location.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="location_id" value="<?= htmlspecialchars($edit_location['LocationID']) ?>">
                <?php endif; ?>
                
                <label>Location Name:</label>
                <div class="input-button-container">
                    <input type="text" name="location_name" class="last-name-input" 
                           placeholder="Enter Location Name" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_location['LocationName']) : '' ?>" required>
                    
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_location" class="add-btn">Update</button>
                        <a href="category.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_location" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form action="location.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Location ID</th>
                        <th>Location Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0) {
                        $serial = ($page - 1) * $records_per_page + 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['LocationID'] . "</td>"; // Changed from $serial++ to show actual LocationID
                            echo "<td>" . htmlspecialchars($row['LocationName']) . "</td>";
                            echo "<td>
                                    <a href='location.php?edit=" . $row['LocationID'] . "' class='edit'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='location.php?delete=" . $row['LocationID'] . "' class='delete' onclick=\"return confirm('Are you sure you want to delete this location?');\"><i class='fas fa-trash'></i> Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No locations found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="location.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">First</a>
                    <a href="location.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i == $page) ? ' active' : '';
                ?>
                    <a href="location.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link<?= $active ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="location.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Next</a>
                    <a href="location.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
        <?php endif; ?>

    </div>
</main>

<link rel="stylesheet" href="css\author.css">