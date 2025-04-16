<?php
include 'header.php';
include 'navbar.php';
include 'db.php';

// Add Classification
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_classification'])) {
    $classification_name = trim($_POST['classification_name']);

    // Check if classification already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM classifications WHERE classification_name = ?");
    $stmt->bind_param("s", $classification_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Classification already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO classifications (classification_name) VALUES (?)");
        $stmt->bind_param("s", $classification_name);
        if ($stmt->execute()) {
            echo "<script>alert('Classification added successfully');</script>";
        } else {
            echo "<script>alert('Error adding classification');</script>";
        }
        $stmt->close();
    }
}

// Edit Classification
$edit_mode = false;
$edit_classification = [];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM classifications WHERE classification_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_classification = $result_edit->fetch_assoc();
    $stmt->close();
}

// Update Classification
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_classification'])) {
    $id = $_POST['classification_id'];
    $classification_name = trim($_POST['classification_name']);

    $stmt = $conn->prepare("UPDATE classifications SET classification_name = ? WHERE classification_id = ?");
    $stmt->bind_param("si", $classification_name, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Classification updated successfully'); window.location='classification.php';</script>";
    } else {
        echo "<script>alert('Error updating classification');</script>";
    }
    $stmt->close();
}

// Delete Classification
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM classifications WHERE classification_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $stmt = $conn->prepare("DELETE FROM classifications WHERE classification_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "<script>alert('Classification deleted successfully'); window.location='classification.php';</script>";
        } else {
            echo "<script>alert('Error deleting classification. It may be in use.'); window.location='classification.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error: Classification not found'); window.location='classification.php';</script>";
    }
    exit;
}

// Pagination settings
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search
$search = "";
$search_condition = "";
$total_pages_query = "SELECT COUNT(*) as total FROM classifications";
$count_params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);

    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE classification_name LIKE ?";
        $count_params = [$search_param];
        $total_pages_query = "SELECT COUNT(*) as total FROM classifications $search_condition";
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

// Get classifications
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT * FROM classifications $search_condition LIMIT ?, ?");
    $stmt->bind_param(str_repeat('s', count($count_params)) . "ii", ...[...$count_params, $offset, $records_per_page]);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM classifications LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<main class="content">
    <div class="classification-container">
        <h2><?= $edit_mode ? 'Edit Classification' : 'Add Classification' ?></h2>

        <div class="form-container">
            <form action="classification.php" method="POST">
                <input type="hidden" name="classification_id" value="<?= $edit_mode ? htmlspecialchars($edit_classification['classification_id']) : '' ?>">
                <label>Classification Name:</label>
                <div class="input-button-container">
                    <input type="text" name="classification_name" placeholder="Enter classification" value="<?= $edit_mode ? htmlspecialchars($edit_classification['classification_name']) : '' ?>" required>
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_classification" class="add-btn">Update</button>
                    <?php else: ?>
                        <button type="submit" name="add_classification" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form action="classification.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Classification Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['classification_id']) ?></td>
                                <td><?= htmlspecialchars($row['classification_name']) ?></td>
                                <td>
                                    <a href="classification.php?edit=<?= htmlspecialchars($row['classification_id']) ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="classification.php?delete=<?= htmlspecialchars($row['classification_id']) ?>" class="delete" onclick="return confirmDelete(<?= htmlspecialchars($row['classification_id']) ?>)"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center;">No classifications found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="classification.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">First</a>
                    <a href="classification.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = ($i == $page) ? ' active' : '';
                    echo '<a href="classification.php?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="pagination-link' . $active_class . '">' . $i . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="classification.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Next</a>
                    <a href="classification.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function confirmDelete(id) {
    return confirm("Do you really want to delete this classification?");
}
</script>

<link rel="stylesheet" href="css/classification.css">
