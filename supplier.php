<?php
// Handle delete operation
if (isset($_GET['delete'])) {
    include 'db.php';
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE SupplierID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: supplier.php");
    exit;
}

include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

// Add Supplier
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_supplier'])) {
    $supplier_name = trim($_POST['supplier_name']);
    $contact_info = trim($_POST['contact_info']);
    $address = trim($_POST['address']);

    // Check for duplicates
    $stmt = $conn->prepare("SELECT COUNT(*) FROM suppliers WHERE Name = ?");
    $stmt->bind_param("s", $supplier_name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Error: Supplier already exists!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO suppliers (Name, Address, ContactInfo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $supplier_name, $address, $contact_info);
        if ($stmt->execute()) {
            echo "<script>alert('Supplier added successfully');</script>";
        } else {
            echo "<script>alert('Error adding supplier');</script>";
        }
        $stmt->close();
    }
}

// Edit Mode
$edit_mode = false;
$edit_supplier = [];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE SupplierID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_supplier = $result_edit->fetch_assoc();
    $stmt->close();
}

// Update Supplier
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_supplier'])) {
    $id = $_POST['supplier_id'];
    $supplier_name = trim($_POST['supplier_name']);
    $contact_info = trim($_POST['contact_info']);
    $address = trim($_POST['address']);

    $stmt = $conn->prepare("UPDATE suppliers SET Name = ?, Address = ?, ContactInfo = ? WHERE SupplierID = ?");
    $stmt->bind_param("sssi", $supplier_name, $address, $contact_info, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Supplier updated successfully'); window.location='supplier.php';</script>";
    } else {
        echo "<script>alert('Error updating supplier');</script>";
    }
    $stmt->close();
}

// Pagination and Search
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = "";
$search_condition = "";
$total_pages_query = "SELECT COUNT(*) as total FROM suppliers";
$count_params = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE Name LIKE ? OR Address LIKE ? OR ContactInfo LIKE ?";
        $count_params = [$search_param, $search_param, $search_param];
        $total_pages_query = "SELECT COUNT(*) as total FROM suppliers $search_condition";
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

// Fetch suppliers for current page
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT * FROM suppliers $search_condition LIMIT ?, ?");
    $stmt->bind_param(str_repeat('s', count($count_params)) . "ii", ...[...$count_params, $offset, $records_per_page]);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM suppliers LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<main class="content">
    <div class="supplier-container">
        <h2><?= $edit_mode ? 'Edit Supplier' : 'Add Supplier' ?></h2>

        <div class="form-container">
            <form action="supplier.php" method="POST">
                <input type="hidden" name="supplier_id" value="<?= $edit_mode ? htmlspecialchars($edit_supplier['SupplierID']) : '' ?>">
                <label>Supplier Name:</label>
                <input type="text" name="supplier_name" placeholder="Enter Supplier Name" value="<?= $edit_mode ? htmlspecialchars($edit_supplier['Name']) : '' ?>" required>
                <label>Contact Info:</label>
                <input type="text" name="contact_info" placeholder="Enter Contact Info" value="<?= $edit_mode ? htmlspecialchars($edit_supplier['ContactInfo']) : '' ?>" required>
                <label for="address">Address:</label>
                <textarea id="address" name="address" placeholder="Enter Address" required><?= $edit_mode ? htmlspecialchars($edit_supplier['Address']) : '' ?></textarea>

                <div class="button-group">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_supplier" class="update-btn">Update</button>
                        <a href="supplier.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_supplier" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Search Form -->
        <form action="supplier.php" method="POST">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <!-- Supplier Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Supplier ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Info</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['SupplierID']) ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['ContactInfo']) ?></td>
                            <td><?= htmlspecialchars($row['Address']) ?></td>
                            <td>
                                <a href="supplier.php?edit=<?= $row['SupplierID'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="supplier.php?delete=<?= $row['SupplierID'] ?>" class="delete" onclick="return confirmDelete(<?= $row['SupplierID'] ?>)"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">No suppliers found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_records > $records_per_page): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="supplier.php?page=1" class="pagination-link">First</a>
                <a href="supplier.php?page=<?= $page - 1 ?>" class="pagination-link">Previous</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="supplier.php?page=<?= $i ?>" class="pagination-link<?= $i == $page ? ' active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="supplier.php?page=<?= $page + 1 ?>" class="pagination-link">Next</a>
                <a href="supplier.php?page=<?= $total_pages ?>" class="pagination-link">Last</a>
            <?php endif; ?>
            <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function confirmDelete(id) {
    return confirm("Are you sure you want to delete this supplier?");
}
</script>

<link rel="stylesheet" href="css/supplier.css">