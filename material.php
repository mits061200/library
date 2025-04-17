<?php 
include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

// Initialize variables
$edit_mode = false;
$edit_material = [];

// Add material
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_material'])) {
    $material_name = trim($_POST['material_name']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM material WHERE MaterialName = ?");
    $stmt->bind_param("s", $material_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Material already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO material (materialName) VALUES (?)");
        $stmt->bind_param("s", $material_name);
        if ($stmt->execute()) {
            echo "<script>alert('Material added successfully');</script>";
        } else {
            echo "<script>alert('Error adding material');</script>";
        }
        $stmt->close();
    }
}

// Delete material - Fixed Error Handling
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // First check if author exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM material WHERE MaterialID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        // Material exists, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM material WHERE MaterialID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Material deleted successfully'); window.location='material.php';</script>";
        } else {
            echo "<script>alert('Error: Unable to material author. It may be in use.'); window.location='material.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error: Material not found'); window.location='material.php';</script>";
    }
    exit;
}

// Edit Material
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM material WHERE MaterialID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_material = $result_edit->fetch_assoc();
    $stmt->close();
    
    if (!$edit_material) {
        echo "<script>alert('Material not found'); window.location='material.php';</script>";
        exit;
    }
}

// Update material
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_material'])) {
    $id = $_POST['material_id'];
    $material_name = trim($_POST['material_name']);

    // Check if new name already exists for other materials
    $stmt = $conn->prepare("SELECT COUNT(*) FROM material WHERE materialName = ? AND MaterialID != ?");
    $stmt->bind_param("si", $material_name, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Another material with this name already exists!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE material SET MaterialName = ? WHERE MaterialID = ?");
        $stmt->bind_param("si", $material_name, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Material updated successfully'); window.location='material.php';</script>";
        } else {
            echo "<script>alert('Error updating material: " . $conn->error . "');</script>";
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
$total_query = "SELECT COUNT(*) as total FROM material";
$params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE materialName LIKE ?";
        $total_query = "SELECT COUNT(*) as total FROM material $search_condition";
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

// Fetch materials
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT * FROM material $search_condition LIMIT ?, ?");
    $stmt->bind_param(str_repeat('s', count($params)) . "ii", ...[...$params, $offset, $records_per_page]);
} else {
    $stmt = $conn->prepare("SELECT * FROM material LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Material' : 'Add Material' ?></h2>

        <div class="form-container">
            <form action="material.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="material_id" value="<?= htmlspecialchars($edit_material['MaterialID']) ?>">
                <?php endif; ?>
                
                <label>Material Name:</label>
                <div class="input-button-container">
                    <input type="text" name="material_name" class="last-name-input" 
                           placeholder="Enter Material Name" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_material['MaterialName']) : '' ?>" required>
                    
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_material" class="add-btn">Update</button>
                        <a href="category.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_material" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form action="material.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Material ID</th>
                        <th>Material Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0) {
                        $serial = ($page - 1) * $records_per_page + 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['MaterialID'] . "</td>"; 
                            echo "<td>" . htmlspecialchars($row['MaterialName']) . "</td>";
                            echo "<td>
                                    <a href='material.php?edit=" . $row['MaterialID'] . "' class='edit'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='material.php?delete=" . $row['MaterialID'] . "' class='delete' onclick=\"return confirm('Are you sure you want to delete this material?');\"><i class='fas fa-trash'></i> Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No materials found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="material.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">First</a>
                    <a href="material.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i == $page) ? ' active' : '';
                ?>
                    <a href="material.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link<?= $active ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="material.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Next</a>
                    <a href="material.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
        <?php endif; ?>

    </div>
</main>

<link rel="stylesheet" href="css\author.css">