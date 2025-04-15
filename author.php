<?php 
include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

// Add Author
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_author'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);

    // Check if the author already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE first_name = ? AND middle_name = ? AND last_name = ?");
    $stmt->bind_param("sss", $first_name, $middle_name, $last_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Author already exists!');</script>";
    } else {
        // Insert new author if not duplicate
        $stmt = $conn->prepare("INSERT INTO authors (first_name, middle_name, last_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $first_name, $middle_name, $last_name);
        
        if ($stmt->execute()) {
            echo "<script>alert('Author added successfully');</script>";
        } else {
            echo "<script>alert('Error adding author');</script>";
        }

        $stmt->close();
    }
}

// Delete Author
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM authors WHERE author_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Redirect to avoid resubmission
    header("Location: author.php");
    exit;
}

// Initialize edit mode variables
$edit_mode = false;
$edit_author = [];

// Edit Author
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM authors WHERE author_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_author = $result_edit->fetch_assoc();
    $stmt->close();
}

// Update Author
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_author'])) {
    $id = $_POST['author_id'];
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);

    $stmt = $conn->prepare("UPDATE authors SET first_name = ?, middle_name = ?, last_name = ? WHERE author_id = ?");
    $stmt->bind_param("sssi", $first_name, $middle_name, $last_name, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Author updated successfully'); window.location='author.php';</script>";
    } else {
        echo "<script>alert('Error updating author');</script>";
    }
    $stmt->close();
}

// Pagination settings
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search Author
$search = "";
$search_condition = "";
$total_pages_query = "SELECT COUNT(*) as total FROM authors";
$count_params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ?";
        $count_params = [$search_param, $search_param, $search_param];
        $total_pages_query = "SELECT COUNT(*) as total FROM authors $search_condition";
    }
}

// Count total records for pagination
if (!empty($search_condition)) {
    $stmt = $conn->prepare($total_pages_query);
    $stmt->bind_param(str_repeat('s', count($count_params)), ...$count_params);
    $stmt->execute();
    $total_result = $stmt->get_result();
    $total_records = $total_result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total_result = $conn->query($total_pages_query);
    $total_records = $total_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Get authors for current page
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT * FROM authors $search_condition LIMIT ?, ?");
    $stmt->bind_param(str_repeat('s', count($count_params)) . "ii", ...[...$count_params, $offset, $records_per_page]);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM authors LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Author' : 'Add Author' ?></h2>

        <div class="form-container">
            <form action="category.php" method="POST">
                <!-- Hidden input for category ID when editing -->
                <?php if ($edit_mode && isset($edit_category['categoryID'])): ?>
                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($edit_category['categoryID']) ?>">
                <?php endif; ?>

                <label>Category Name:</label>
                <div class="input-button-container">
                    <input type="text" name="category_name" class="last-name-input" placeholder="Enter Category Name"
                        value="<?= $edit_mode && isset($edit_category['categoryName']) ? htmlspecialchars($edit_category['categoryName']) : '' ?>" required>

                    <!-- Show the corresponding button based on the mode -->
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_category" class="add-btn">Update</button>
                    <?php else: ?>
                        <button type="submit" name="add_category" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form action="author.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Author ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) { 
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['author_id']) ?></td>
                        <td><?= htmlspecialchars($row['first_name']) ?></td>
                        <td><?= htmlspecialchars($row['middle_name']) ?></td>
                        <td><?= htmlspecialchars($row['last_name']) ?></td>
                        <td>
                            <a href="author.php?edit=<?= htmlspecialchars($row['author_id']) ?>" class="edit">
                            <i class="fas fa-edit"></i> Edit</a>
                            <a href="author.php?delete=<?= htmlspecialchars($row['author_id']) ?>" class="delete" onclick="return confirmDelete(<?= htmlspecialchars($row['author_id']) ?>)">
                            <i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="5" style="text-align: center;">No authors found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
    </div>    
            <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="author.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">First</a>
                    <a href="author.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>
                
                <?php
                // Show page numbers
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = ($i == $page) ? ' active' : '';
                    echo '<a href="author.php?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="pagination-link' . $active_class . '">' . $i . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="author.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Next</a>
                    <a href="author.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>
                
                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
            <?php endif; ?>
        
    </div>
</main>

<script>
function confirmDelete(authorId) {
    let confirmAction = confirm("Do you want to delete this author?");
    return confirmAction; // Returns true to proceed, false to cancel
}
</script>


<link rel="stylesheet" href="css\author.css">