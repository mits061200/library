<?php 
include 'header.php'; 
include 'navbar.php'; 
include 'db.php'; 

$edit_mode = false;
$edit_penalty = [];

// Add Penalty
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_penalty'])) {
    $name = trim($_POST['penalty_name']);
    $amount = trim($_POST['penalty_amount']) !== '' ? (float)$_POST['penalty_amount'] : null;
    $duration = trim($_POST['penalty_duration']) !== '' ? trim($_POST['penalty_duration']) : null;

    $stmt = $conn->prepare("INSERT INTO penalty (PenaltyName, PenaltyRate, Duration) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $name, $amount, $duration);
    $stmt->execute();
    echo "<script>alert('Penalty added successfully');</script>";
    $stmt->close();
}

// Delete Penalty
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM penalty WHERE PenaltyID = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Penalty deleted successfully'); window.location='penalty.php';</script>";
    } else {
        echo "<script>alert('Error deleting penalty'); window.location='penalty.php';</script>";
    }
    $stmt->close();
    exit;
}

// Edit Penalty
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM penalty WHERE PenaltyID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_penalty = $result_edit->fetch_assoc();
    $stmt->close();

    if (!$edit_penalty) {
        echo "<script>alert('Penalty not found'); window.location='penalty.php';</script>";
        exit;
    }
}

// Update Penalty
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_penalty'])) {
    $id = $_POST['penalty_id'];
    $name = trim($_POST['penalty_name']);
    $amount = trim($_POST['penalty_amount']) !== '' ? (float)$_POST['penalty_amount'] : null;
    $duration = trim($_POST['penalty_duration']) !== '' ? trim($_POST['penalty_duration']) : null;

    $stmt = $conn->prepare("UPDATE penalty SET PenaltyName = ?, PenaltyRate = ?, Duration = ? WHERE PenaltyID = ?");
    $stmt->bind_param("sdsi", $name, $amount, $duration, $id);
    if ($stmt->execute()) {
        echo "<script>alert('Penalty updated successfully'); window.location='penalty.php';</script>";
    } else {
        echo "<script>alert('Error updating penalty');</script>";
    }
    $stmt->close();
}

// Pagination + Search
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;
$search = "";

// Handle Search
$search_condition = "";
$params = [];
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['search_query'])) {
    $search = trim($_POST['search_query']);
    if (!empty($search)) {
        $search_param = "%$search%";
        $search_condition = "WHERE PenaltyName LIKE ? OR Duration LIKE ?";
        $params = [$search_param, $search_param];
    }
}

// Count total records
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM penalty $search_condition");
    $stmt->bind_param("ss", ...$params);
    $stmt->execute();
    $result_total = $stmt->get_result();
    $total_records = $result_total->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result_total = $conn->query("SELECT COUNT(*) as total FROM penalty");
    $total_records = $result_total->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Fetch Penalties
if (!empty($search_condition)) {
    $stmt = $conn->prepare("SELECT * FROM penalty $search_condition LIMIT ?, ?");
    $params[] = $offset;
    $params[] = $records_per_page;
    $stmt->bind_param("ssii", ...$params);

} else {
    $stmt = $conn->prepare("SELECT * FROM penalty LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Penalty' : 'Add Penalty' ?></h2>

        <div class="form-container">
            <form action="penalty.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="penalty_id" value="<?= htmlspecialchars($edit_penalty['PenaltyID']) ?>">
                <?php endif; ?>

                <label>Penalty Name / Book Status:</label>
                <input type="text" name="penalty_name" class="last-name-input" 
                       placeholder="Enter Penalty Name or Book Status" 
                       value="<?= $edit_mode ? htmlspecialchars($edit_penalty['PenaltyName']) : '' ?>" required>

                <label>Penalty Amount:</label>
                <input type="number" step="0.01" name="penalty_amount" class="last-name-input" 
                       placeholder="Enter Amount (Optional)" 
                       value="<?= $edit_mode ? htmlspecialchars($edit_penalty['PenaltyRate']) : '' ?>">

                <label>Duration (e.g., 7 days):</label>
                <input type="text" name="penalty_duration" class="last-name-input" 
                       placeholder="Enter Duration (Optional)" 
                       value="<?= $edit_mode ? htmlspecialchars($edit_penalty['Duration']) : '' ?>">

                <div class="input-button-container">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_penalty" class="add-btn">Update</button>
                        <a href="penalty.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_penalty" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Search Form -->
        <form method="POST" action="penalty.php">
            <div class="search-container">
                <input type="text" class="search-input" name="search_query" placeholder="Search Penalty..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <!-- Penalty Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Penalty ID</th>
                        <th>Penalty Name / Book Status</th>
                        <th>Amount</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['PenaltyID'] . "</td>"; 
                            echo "<td>" . htmlspecialchars($row['PenaltyName']) . "</td>";
                            echo "<td>" . ($row['PenaltyRate'] !== null ? htmlspecialchars($row['PenaltyRate']) : '—') . "</td>";
                            echo "<td>" . ($row['Duration'] !== null ? htmlspecialchars($row['Duration']) : '—') . "</td>";
                            echo "<td>
                                    <a href='penalty.php?edit=" . $row['PenaltyID'] . "' class='edit'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='penalty.php?delete=" . $row['PenaltyID'] . "' class='delete' onclick=\"return confirm('Are you sure you want to delete this penalty?');\"><i class='fas fa-trash'></i> Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No penalties found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="penalty.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">First</a>
                    <a href="penalty.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i == $page) ? ' active' : '';
                ?>
                    <a href="penalty.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link<?= $active ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="penalty.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Next</a>
                    <a href="penalty.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
        <?php endif; ?>
    </div>
</main>

<link rel="stylesheet" href="css/penalty.css">
