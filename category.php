<?php 
include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

// Initialize variables
$edit_mode = false;
$edit_category = [];

// Add Category
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM category WHERE categoryName = ?");
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Category already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO category (categoryName) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        if ($stmt->execute()) {
            echo "<script>alert('Category added successfully');</script>";
        } else {
            echo "<script>alert('Error adding category');</script>";
        }
        $stmt->close();
    }
}

// Delete category - Fixed Error Handling
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First check if author exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM category WHERE CategoryID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        // Category exists, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM category WHERE CategoryID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Category deleted successfully'); window.location='category.php';</script>";
        } else {
            echo "<script>alert('Error: Unable to category author. It may be in use.'); window.location='category.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error: Category not found'); window.location='category.php';</script>";
    }
    exit;
}

// Edit Category
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM category WHERE CategoryID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_category = $result_edit->fetch_assoc();
    $stmt->close();
    
    if (!$edit_category) {
        echo "<script>alert('Category not found'); window.location='category.php';</script>";
        exit;
    }
}

// Update Category
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $category_name = trim($_POST['category_name']);

    // Check if new name already exists for other categories
    $stmt = $conn->prepare("SELECT COUNT(*) FROM category WHERE categoryName = ? AND CategoryID != ?");
    $stmt->bind_param("si", $category_name, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Another category with this name already exists!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE category SET CategoryName = ? WHERE CategoryID = ?");
        $stmt->bind_param("si", $category_name, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Category updated successfully'); window.location='category.php';</script>";
        } else {
            echo "<script>alert('Error updating category: " . $conn->error . "');</script>";
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
$total_query = "SELECT COUNT(*) as total FROM category";
$params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE categoryName LIKE ?";
        $total_query = "SELECT COUNT(*) as total FROM category $search_condition";
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
    $stmt = $conn->prepare("SELECT * FROM category $search_condition LIMIT ?, ?");
    $stmt->bind_param(str_repeat('s', count($params)) . "ii", ...[...$params, $offset, $records_per_page]);
} else {
    $stmt = $conn->prepare("SELECT * FROM category LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Category' : 'Add Category' ?></h2>

        <div class="form-container">
            <form action="category.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($edit_category['CategoryID']) ?>">
                <?php endif; ?>
                
                <label>Category Name:</label>
                <div class="input-button-container">
                    <input type="text" name="category_name" class="last-name-input" 
                           placeholder="Enter Category Name" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_category['CategoryName']) : '' ?>" required>
                    
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_category" class="add-btn">Update</button>
                    <?php else: ?>
                        <button type="submit" name="add_category" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form action="category.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0) {
                        $serial = ($page - 1) * $records_per_page + 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['CategoryID'] . "</td>"; // Changed from $serial++ to show actual CategoryID
                            echo "<td>" . htmlspecialchars($row['CategoryName']) . "</td>";
                            echo "<td>
                                    <a href='category.php?edit=" . $row['CategoryID'] . "' class='edit'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='category.php?delete=" . $row['CategoryID'] . "' class='delete' onclick=\"return confirm('Are you sure you want to delete this category?');\"><i class='fas fa-trash'></i> Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No categories found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="category.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">First</a>
                    <a href="category.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i == $page) ? ' active' : '';
                ?>
                    <a href="category.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link<?= $active ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="category.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Next</a>
                    <a href="category.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
        <?php endif; ?>

    </div>
</main>

<link rel="stylesheet" href="css\author.css">