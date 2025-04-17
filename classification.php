<?php
include 'header.php';
include 'navbar.php';
include 'db.php';

// Initialize variables
$edit_mode_main = false;
$edit_mode_sub = false;
$edit_main = [];
$edit_sub = [];

// Add Main Classification
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_main_class'])) {
    $main_number = trim($_POST['main_classification_number']);
    $main_description = trim($_POST['main_description']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM mainClassification WHERE ClassificationNumber = ?");
    $stmt->bind_param("s", $main_number);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Main classification already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO mainClassification (ClassificationNumber, Description) VALUES (?, ?)");
        $stmt->bind_param("ss", $main_number, $main_description);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Main classification added successfully');</script>";
    }
}

// Add Sub Classification
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_sub_class'])) {
    $sub_number = trim($_POST['sub_classification_number']);
    $sub_description = trim($_POST['sub_description']);
    $main_class_id = intval($_POST['main_class']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM subClassification WHERE SubClassificationNumber = ?");
    $stmt->bind_param("s", $sub_number);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Sub classification already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO subClassification (MainClassID, SubClassificationNumber, Description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $main_class_id, $sub_number, $sub_description);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Sub classification added successfully');</script>";
    }
}

// DELETE MAIN CLASSIFICATION
if (isset($_GET['delete_main'])) {
    $id = $_GET['delete_main'];
    
    // First check if main classification exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM mainClassification WHERE MainClassificationID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        // Check if there are any sub classifications
        $stmt = $conn->prepare("SELECT COUNT(*) FROM subClassification WHERE MainClassID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($sub_count);
        $stmt->fetch();
        $stmt->close();
        
        if ($sub_count > 0) {
            echo "<script>alert('Error: Cannot delete main classification because it has sub classifications.'); window.location='classification.php';</script>";
        } else {
            // Main classification exists and has no sub classifications, proceed with deletion
            $stmt = $conn->prepare("DELETE FROM mainClassification WHERE MainClassificationID = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo "<script>alert('Main classification deleted successfully'); window.location='classification.php';</script>";
            } else {
                echo "<script>alert('Error: Unable to delete main classification.'); window.location='classification.php';</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>alert('Error: Main classification not found'); window.location='classification.php';</script>";
    }
    exit;
}

// DELETE SUB CLASSIFICATION
if (isset($_GET['delete_sub'])) {
    $id = $_GET['delete_sub'];
    
    // First check if sub classification exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM subClassification WHERE SubClassificationID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        // Sub classification exists, proceed with deletion
        $stmt = $conn->prepare("DELETE FROM subClassification WHERE SubClassificationID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Sub classification deleted successfully'); window.location='classification.php';</script>";
        } else {
            echo "<script>alert('Error: Unable to delete sub classification.'); window.location='classification.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error: Sub classification not found'); window.location='classification.php';</script>";
    }
    exit;
}

// EDIT MAIN CLASSIFICATION
if (isset($_GET['edit_main'])) {
    $edit_mode_main = true;
    $id = $_GET['edit_main'];
    $stmt = $conn->prepare("SELECT * FROM mainClassification WHERE MainClassificationID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_main = $result_edit->fetch_assoc();
    $stmt->close();
    
    if (!$edit_main) {
        echo "<script>alert('Main classification not found'); window.location='classification.php';</script>";
        exit;
    }
}

// EDIT SUB CLASSIFICATION
if (isset($_GET['edit_sub'])) {
    $edit_mode_sub = true;
    $id = $_GET['edit_sub'];
    $stmt = $conn->prepare("SELECT * FROM subClassification WHERE SubClassificationID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_sub = $result_edit->fetch_assoc();
    $stmt->close();
    
    if (!$edit_sub) {
        echo "<script>alert('Sub classification not found'); window.location='classification.php';</script>";
        exit;
    }
}

// UPDATE MAIN CLASSIFICATION
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_main_class'])) {
    $id = $_POST['main_class_id'];
    $main_number = trim($_POST['main_classification_number']);
    $main_description = trim($_POST['main_description']);

    // Check if new number already exists for other classifications
    $stmt = $conn->prepare("SELECT COUNT(*) FROM mainClassification WHERE ClassificationNumber = ? AND MainClassificationID != ?");
    $stmt->bind_param("si", $main_number, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Another main classification with this number already exists!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE mainClassification SET ClassificationNumber = ?, Description = ? WHERE MainClassificationID = ?");
        $stmt->bind_param("ssi", $main_number, $main_description, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Main classification updated successfully'); window.location='classification.php';</script>";
        } else {
            echo "<script>alert('Error updating main classification: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

// UPDATE SUB CLASSIFICATION
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_sub_class'])) {
    $id = $_POST['sub_class_id'];
    $sub_number = trim($_POST['sub_classification_number']);
    $sub_description = trim($_POST['sub_description']);
    $main_class_id = intval($_POST['main_class']);

    // Check if new number already exists for other sub classifications
    $stmt = $conn->prepare("SELECT COUNT(*) FROM subClassification WHERE SubClassificationNumber = ? AND SubClassificationID != ?");
    $stmt->bind_param("si", $sub_number, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Another sub classification with this number already exists!');</script>";
    } else {
        $stmt = $conn->prepare("UPDATE subClassification SET SubClassificationNumber = ?, Description = ?, MainClassID = ? WHERE SubClassificationID = ?");
        $stmt->bind_param("ssii", $sub_number, $sub_description, $main_class_id, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Sub classification updated successfully'); window.location='classification.php';</script>";
        } else {
            echo "<script>alert('Error updating sub classification: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
}

// Pagination and Search
$records_per_page = 2;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;
$search_query = "";
$search_param = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_query'])) {
    $search_query = trim($_POST['search_query']);
}

$search_sql = "";
if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $search_sql = "WHERE mc.ClassificationNumber LIKE ? OR mc.Description LIKE ?";
}

$count_sql = "SELECT COUNT(DISTINCT mc.MainClassificationID) AS total FROM mainClassification mc $search_sql";
$count_stmt = $conn->prepare($count_sql);
if ($search_sql) {
    $count_stmt->bind_param("ss", $search_param, $search_param);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

$main_data = [];
$fetch_sql = "
    SELECT 
        mc.MainClassificationID,
        mc.ClassificationNumber AS main_number,
        mc.Description AS main_desc,
        sc.SubClassificationID,
        sc.SubClassificationNumber AS sub_number,
        sc.Description AS sub_desc
    FROM mainClassification mc
    LEFT JOIN subClassification sc ON mc.MainClassificationID = sc.MainClassID
    $search_sql
    ORDER BY mc.ClassificationNumber, sc.SubClassificationNumber
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($fetch_sql);
if ($search_sql) {
    $stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $main_id = $row['MainClassificationID'];
    $main_number = $row['main_number'];
    if (!isset($main_data[$main_id])) {
        $main_data[$main_id] = [
            'number' => $main_number,
            'description' => $row['main_desc'],
            'subs' => []
        ];
    }
    if ($row['sub_number']) {
        $main_data[$main_id]['subs'][] = [
            'id' => $row['SubClassificationID'],
            'number' => $row['sub_number'],
            'description' => $row['sub_desc']
        ];
    }
}
$stmt->close();

$main_classes = $conn->query("SELECT * FROM mainClassification ORDER BY ClassificationNumber");
?>

<link rel="stylesheet" href="css/classification.css">

<main class="content">
<div class="classification-container">
        <h2>Add Classification</h2>

        <div class="forms-wrapper">
            <!-- 🟥 MAIN Classification form on the LEFT -->
            <div class="form-section left">
                <h3><?= $edit_mode_main ? 'Edit Main Classification' : 'Main Classification' ?></h3>
                <form method="POST">
                    <?php if ($edit_mode_main): ?>
                        <input type="hidden" name="main_class_id" value="<?= htmlspecialchars($edit_main['MainClassificationID']) ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="main_classification_number">Main Classification Number:</label>
                        <input type="text" id="main_classification_number" name="main_classification_number" 
                               placeholder="Enter main classification number" 
                               value="<?= $edit_mode_main ? htmlspecialchars($edit_main['ClassificationNumber']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="main_description">Description:</label>
                        <input type="text" id="main_description" name="main_description" 
                               placeholder="Enter description" 
                               value="<?= $edit_mode_main ? htmlspecialchars($edit_main['Description']) : '' ?>" required>
                    </div>
                    <?php if ($edit_mode_main): ?>
                        <button type="submit" name="update_main_class" class="add-btn">Update</button>
                        <a href="classification.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_main_class" class="add-btn">Add</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 🟦 SUB Classification form on the RIGHT -->
            <div class="form-section right">
                <h3><?= $edit_mode_sub ? 'Edit Sub Classification' : 'Sub Classification' ?></h3>
                <form method="POST">
                    <?php if ($edit_mode_sub): ?>
                        <input type="hidden" name="sub_class_id" value="<?= htmlspecialchars($edit_sub['SubClassificationID']) ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="sub_classification_number">Sub Classification Number:</label>
                        <input type="text" id="sub_classification_number" name="sub_classification_number" 
                               placeholder="Enter sub classification number" 
                               value="<?= $edit_mode_sub ? htmlspecialchars($edit_sub['SubClassificationNumber']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="sub_description">Description:</label>
                        <input type="text" id="sub_description" name="sub_description" 
                               placeholder="Enter description" 
                               value="<?= $edit_mode_sub ? htmlspecialchars($edit_sub['Description']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="main_class">Main Classification:</label>
                        <select id="main_class" name="main_class" required>
                            <option value="">Select Main Class</option>
                            <?php 
                            $main_classes_result = $conn->query("SELECT * FROM mainClassification ORDER BY ClassificationNumber");
                            while ($row = $main_classes_result->fetch_assoc()): 
                                $selected = $edit_mode_sub && $edit_sub['MainClassID'] == $row['MainClassificationID'] ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($row['MainClassificationID']) ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($row['ClassificationNumber']) ?> - <?= htmlspecialchars($row['Description']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php if ($edit_mode_sub): ?>
                        <button type="submit" name="update_sub_class" class="add-btn">Update</button>
                        <a href="classification.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_sub_class" class="add-btn">Add</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <form method="POST" class="search-form">
            <input type="text" id="search_query" name="search_query" placeholder="Search classification..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Classification Number</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($main_data)): ?>
                        <tr><td colspan="3">No results found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($main_data as $main_id => $main_info): ?>
                            <tr class="main-row">
                                <td><?= htmlspecialchars($main_info['number']) ?></td>
                                <td><?= htmlspecialchars($main_info['description']) ?></td>
                                <td>
                                    <a href="classification.php?edit_main=<?= $main_id ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="classification.php?delete_main=<?= $main_id ?>" class="delete" 
                                        onclick="return confirm('Are you sure you want to delete this main classification? This will delete all related sub classifications.');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php foreach ($main_info['subs'] as $sub): ?>
                                <tr>
                                    <td style="padding-left: 30px;">&rarr; <?= htmlspecialchars($sub['number']) ?></td>
                                    <td><?= htmlspecialchars($sub['description']) ?></td>
                                    <td>
                                        <a href="classification.php?edit_sub=<?= $sub['id'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="classification.php?delete_sub=<?= $sub['id'] ?>" class="delete" 
                                            onclick="return confirm('Are you sure you want to delete this sub classification?');"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (like category.php style) -->
        <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="classification.php?page=1<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="pagination-link">First</a>
                    <a href="classification.php?page=<?= $page - 1 ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i == $page) ? ' active' : '';
                ?>
                    <a href="classification.php?page=<?= $i ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="pagination-link<?= $active ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="classification.php?page=<?= $page + 1 ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="pagination-link">Next</a>
                    <a href="classification.php?page=<?= $total_pages ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
        <?php endif; ?>
    </div>
</main>

