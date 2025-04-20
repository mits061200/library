<?php
// Handle delete first before any output
if (isset($_GET['delete'])) {
    include 'db.php';
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM authors WHERE AuthorID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: author.php");
    exit;
}

include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

// Add Author
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_author'])) {
    $first_name = trim($_POST['FirstName']);
    $middle_name = trim($_POST['MiddleName']);
    $last_name = trim($_POST['LastName']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE FirstName = ? AND MiddleName = ? AND LastName = ?");
    $stmt->bind_param("sss", $first_name, $middle_name, $last_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Author already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO authors (FirstName, MiddleName, LastName) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $first_name, $middle_name, $last_name);
        if ($stmt->execute()) {
            echo "<script>alert('Author added successfully');</script>";
        } else {
            echo "<script>alert('Error adding author');</script>";
        }
        $stmt->close();
    }
}

// Edit Mode
$edit_mode = false;
$edit_author = [];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM authors WHERE AuthorID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_author = $result_edit->fetch_assoc();
    $stmt->close();
}

// Update Author
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_author'])) {
    $id = $_POST['AuthorID'];
    $first_name = trim($_POST['FirstName']);
    $middle_name = trim($_POST['MiddleName']);
    $last_name = trim($_POST['LastName']);

    $stmt = $conn->prepare("UPDATE authors SET FirstName = ?, MiddleName = ?, LastName = ? WHERE AuthorID = ?");
    $stmt->bind_param("sssi", $first_name, $middle_name, $last_name, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Author updated successfully'); window.location='author.php';</script>";
    } else {
        echo "<script>alert('Error updating author');</script>";
    }
    $stmt->close();
}

// Pagination and Search
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = "";
$search_condition = "";
$total_pages_query = "SELECT COUNT(*) as total FROM authors";
$count_params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE FirstName LIKE ? OR MiddleName LIKE ? OR LastName LIKE ?";
        $count_params = [$search_param, $search_param, $search_param];
        $total_pages_query = "SELECT COUNT(*) as total FROM authors $search_condition";
    }
}

// Count total records
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

// Fetch authors for current page
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
            <form action="author.php" method="POST">
                <input type="hidden" name="AuthorID" value="<?= $edit_mode ? htmlspecialchars($edit_author['AuthorID']) : '' ?>">
                <label>First Name:</label>
                <input type="text" name="FirstName" placeholder="Enter First Name" value="<?= $edit_mode ? htmlspecialchars($edit_author['FirstName']) : '' ?>" required>
                <label>Middle Name:</label>
                <input type="text" name="MiddleName" placeholder="Enter Middle Name" value="<?= $edit_mode ? htmlspecialchars($edit_author['MiddleName']) : '' ?>">
                <label>Last Name:</label>
                <div class="input-button-container">
                    <input type="text" name="LastName" class="last-name-input" placeholder="Enter Last Name" value="<?= $edit_mode ? htmlspecialchars($edit_author['LastName']) : '' ?>" required>
                    <?php if ($edit_mode): ?>
                        <div class="button-group">
                            <button type="submit" name="update_author" class="add-btn">Update</button>
                            <a href="author.php" class="cancel-btn">Cancel</a>
                        </div>
                    <?php else: ?>
                        <button type="submit" name="add_author" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Search Form -->
        <form action="author.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <!-- Author Table -->
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
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['AuthorID']) ?></td>
                            <td><?= htmlspecialchars($row['FirstName']) ?></td>
                            <td><?= htmlspecialchars($row['MiddleName']) ?></td>
                            <td><?= htmlspecialchars($row['LastName']) ?></td>
                            <td>
                                <a href="author.php?edit=<?= $row['AuthorID'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="author.php?delete=<?= $row['AuthorID'] ?>" class="delete" onclick="return confirmDelete(<?= $row['AuthorID'] ?>)"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No authors found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_records > $records_per_page): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="author.php?page=1" class="pagination-link">First</a>
                <a href="author.php?page=<?= $page - 1 ?>" class="pagination-link">Previous</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="author.php?page=<?= $i ?>" class="pagination-link<?= $i == $page ? ' active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="author.php?page=<?= $page + 1 ?>" class="pagination-link">Next</a>
                <a href="author.php?page=<?= $total_pages ?>" class="pagination-link">Last</a>
            <?php endif; ?>
            <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function confirmDelete(id) {
    return confirm("Are you sure you want to delete this author?");
}
</script>

<link rel="stylesheet" href="css/author.css">
