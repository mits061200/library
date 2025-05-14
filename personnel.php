<?php 
// Start session and check authentication
session_start();
if (!isset($_SESSION['personnelid'])) {
    header("Location: login.php");
    exit();
}

// Check if user is a librarian
if ($_SESSION['position'] !== 'librarian') {
    header("Location: unauthorized.php");
    exit();
}

include 'header.php'; 
include 'navbar.php'; 
include 'db.php';

$edit_mode = false;
$edit_personnel = [];
$search_term = '';

// Handle search functionality
if (isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

// Add Personnel
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['add_personnel'])) {
    $personnel_id = trim($_POST['personnel_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position']);
    // In the update personnel section
        if ($position === 'librarian' && $_SESSION['position'] !== 'librarian') {
            echo "<script>alert('Only librarians can assign librarian positions.');</script>";
            exit;
        }
    $address = trim($_POST['address']);
    $phone_number = trim($_POST['phone_number']);
    
    // For credentials form
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Password and Confirm Password do not match.');</script>";
        exit;
    }

    // Validate password security
    if (strlen($password) < 8) {
        echo "<script>alert('Password must be at least 8 characters long.');</script>";
        exit;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        echo "<script>alert('Password must include at least one uppercase letter.');</script>";
        exit;
    }
    if (!preg_match('/[a-z]/', $password)) {
        echo "<script>alert('Password must include at least one lowercase letter.');</script>";
        exit;
    }
    if (!preg_match('/[0-9]/', $password)) {
        echo "<script>alert('Password must include at least one number.');</script>";
        exit;
    }
    if (!preg_match('/[\W_]/', $password)) {
        echo "<script>alert('Password must include at least one special character (e.g., !, @, #, $, etc.).');</script>";
        exit;
    }
    

    // Hash the password after validation
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $status = 'active'; // Default status for new personnel

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert personnel information
        $stmt = $conn->prepare("INSERT INTO personnel (PersonnelID, FirstName, MiddleName, LastName, Position, Address, PhoneNumber) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $personnel_id, $first_name, $middle_name, $last_name, $position, $address, $phone_number);
        $stmt->execute();
        $stmt->close();
        
        // Insert login credentials
        $stmt = $conn->prepare("INSERT INTO personnelLogin (PersonnelID, Username, Password, Status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $personnel_id, $username, $hashed_password, $status);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        echo "<script>alert('Personnel added successfully');</script>";
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "<script>alert('Error adding personnel: " . $e->getMessage() . "');</script>";
    }
}



// Delete Personnel
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete personnel record (will cascade to delete login record)
        $stmt = $conn->prepare("DELETE FROM personnel WHERE PersonnelID = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        echo "<script>alert('Personnel deleted successfully'); window.location='personnel.php';</script>";
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "<script>alert('Error deleting personnel: " . $e->getMessage() . "'); window.location='personnel.php';</script>";
    }
    exit;
}

// Edit Personnel
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    
    // Get personnel information
    $stmt = $conn->prepare("SELECT p.*, pl.Username, pl.Status FROM personnel p 
                          LEFT JOIN personnelLogin pl ON p.PersonnelID = pl.PersonnelID 
                          WHERE p.PersonnelID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_personnel = $result_edit->fetch_assoc();
    $stmt->close();
    
    if (!$edit_personnel) {
        echo "<script>alert('Personnel not found'); window.location='personnel.php';</script>";
        exit;
    }
}

// Update Personnel
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_personnel'])) {
    $personnel_id = trim($_POST['personnel_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position']);

    //  validation check
    if ($position === 'librarian' && $_SESSION['position'] !== 'librarian') {
        echo "<script>alert('Only librarians can assign librarian positions.'); window.location='personnel.php';</script>";
        exit;
    }
    $address = trim($_POST['address']);
    $phone_number = trim($_POST['phone_number']);
    
    // For credentials form
    $username = trim($_POST['username']);
    $new_password = trim($_POST['password']); // New password field
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update personnel information
        $stmt = $conn->prepare("UPDATE personnel SET FirstName = ?, MiddleName = ?, LastName = ?, 
                              Position = ?, Address = ?, PhoneNumber = ? WHERE PersonnelID = ?");
        $stmt->bind_param("sssssss", $first_name, $middle_name, $last_name, $position, $address, $phone_number, $personnel_id);
        $stmt->execute();
        $stmt->close();
        
        if (!empty($new_password)) {
            // If new password is provided, update both username and password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE personnelLogin SET Username = ?, Password = ?, Status = ? WHERE PersonnelID = ?");
            $stmt->bind_param("ssss", $username, $hashed_password, $status, $personnel_id);
        } else {
            // If no new password, just update username and status
            $stmt = $conn->prepare("UPDATE personnelLogin SET Username = ?, Status = ? WHERE PersonnelID = ?");
            $stmt->bind_param("sss", $username, $status, $personnel_id);
        }
        
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        echo "<script>alert('Personnel updated successfully'); window.location='personnel.php';</script>";
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo "<script>alert('Error updating personnel: " . $e->getMessage() . "');</script>";
    }
}

// Pagination
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build search query
if (!empty($search_term)) {
    $search_query = "WHERE p.PersonnelID LIKE ? OR p.FirstName LIKE ? OR p.MiddleName LIKE ? OR 
                    p.LastName LIKE ? OR p.Position LIKE ? OR p.PhoneNumber LIKE ?";
    $search_param = "%$search_term%";
    
    $total_query = "SELECT COUNT(*) as total FROM personnel p $search_query";
    $stmt_total = $conn->prepare($total_query);
    $stmt_total->bind_param("ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total_records = $result_total->fetch_assoc()['total'];
    $stmt_total->close();
    
    // Fetch records with search
    $stmt = $conn->prepare("SELECT p.*, pl.Username, pl.Status, pl.LastLogin FROM personnel p 
                          LEFT JOIN personnelLogin pl ON p.PersonnelID = pl.PersonnelID 
                          $search_query ORDER BY p.PersonnelID LIMIT ?, ?");
    $stmt->bind_param("ssssssii", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $offset, $records_per_page);
} else {
    $total_query = "SELECT COUNT(*) as total FROM personnel";
    $result_total = $conn->query($total_query);
    $total_records = $result_total->fetch_assoc()['total'];
    
    // Fetch records without search
    $stmt = $conn->prepare("SELECT p.*, pl.Username, pl.Status, pl.LastLogin FROM personnel p 
                          LEFT JOIN personnelLogin pl ON p.PersonnelID = pl.PersonnelID 
                          ORDER BY p.PersonnelID LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $records_per_page);
}

$total_pages = ceil($total_records / $records_per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Personnel' : 'Add Personnel' ?></h2>

        <!-- Two-column layout for forms -->
        <div class="form-columns-container">
            <!-- Left column - Personnel Info -->
            <div class="form-column personnel-info">
                <h3>Personnel Information</h3>
                <form action="personnel.php" method="POST" id="personnel-form">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="personnel_id" value="<?= htmlspecialchars($edit_personnel['PersonnelID']) ?>">
                    <?php else: ?>
                        <label>Personnel ID:</label>
                        <input type="text" name="personnel_id" class="last-name-input" 
                               placeholder="Enter Personnel ID" 
                               value="" required>
                    <?php endif; ?>

                    <label>First Name:</label>
                    <input type="text" name="first_name" class="last-name-input" 
                           placeholder="Enter First Name" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_personnel['FirstName']) : '' ?>" required>

                    <label>Middle Name:</label>
                    <input type="text" name="middle_name" class="last-name-input" 
                           placeholder="Enter Middle Name (Optional)" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_personnel['MiddleName']) : '' ?>">

                    <label>Last Name:</label>
                    <input type="text" name="last_name" class="last-name-input" 
                           placeholder="Enter Last Name" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_personnel['LastName']) : '' ?>" required>

                    <label>Position:</label>
                    <input type="text" name="position" class="last-name-input" 
                           placeholder="Enter Position" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_personnel['Position']) : '' ?>" required>

                    <label>Address:</label>
                    <input type="text" name="address" class="last-name-input" 
                           placeholder="Enter Address" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_personnel['Address']) : '' ?>" required>

                    <label>Phone Number:</label>
                    <input type="text" name="phone_number" class="last-name-input" 
                           placeholder="Enter Phone Number" 
                           value="<?= $edit_mode ? htmlspecialchars($edit_personnel['PhoneNumber']) : '' ?>" required>
                </form>
            </div>

            <!-- Right column - Login Info -->
            <div class="form-column login-info">
                <h3>Login Credentials</h3>
                
                <label>Username:</label>
                <input type="text" name="username" form="personnel-form" class="last-name-input" 
                    placeholder="Enter Username" 
                    value="<?= $edit_mode ? htmlspecialchars($edit_personnel['Username']) : '' ?>" required>

                <?php if ($edit_mode): ?>
                    <label>New Password:</label>
                    <input type="password" name="password" form="personnel-form" class="last-name-input" 
                        placeholder="Enter new password (leave blank to keep current)">
                <?php else: ?>
                    <label>Password:</label>
                    <input type="password" name="password" form="personnel-form" class="last-name-input" 
                        placeholder="Enter Password" required>
                        
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" form="personnel-form" class="last-name-input" 
                        placeholder="Confirm Password" required>
                <?php endif; ?>

                <?php if ($edit_mode): ?>
                    <label>Status:</label>
                    <select name="status" form="personnel-form" class="last-name-input">
                        <option value="active" <?= ($edit_personnel['Status'] == 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($edit_personnel['Status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                <?php endif; ?>

                <div class="input-button-container">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_personnel" form="personnel-form" class="add-btn">Update</button>
                        <a href="personnel.php" class="cancel-btn">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_personnel" form="personnel-form" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

     
         <!-- Search Form -->
        <form action="personnel.php" method="POST">
            <div class="search-container">
            <input type="text" class="search-input" name="search" placeholder="Search" value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>  
            </div>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $full_name = $row['FirstName'] . ' ' . 
                                        ($row['MiddleName'] ? $row['MiddleName'] . ' ' : '') . 
                                        $row['LastName'];
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['PersonnelID']) . "</td>"; 
                            echo "<td>" . htmlspecialchars($full_name) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Position']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Address']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['PhoneNumber']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['DateAdded']) . "</td>";
                            echo "<td>
                                    <a href='personnel.php?edit=" . $row['PersonnelID'] . "' class='edit'><i class='fas fa-edit'></i> Edit</a>
                                    <a href='personnel.php?delete=" . $row['PersonnelID'] . "' class='delete' onclick=\"return confirm('Are you sure you want to delete this personnel record?');\"><i class='fas fa-trash'></i> Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No personnel records found" . (!empty($search_term) ? " matching '$search_term'" : "") . ".</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_records > $records_per_page): ?>
            <div class="pagination">
                <?php 
                // Build pagination URL with search term if exists
                $pagination_url = "personnel.php?" . (!empty($search_term) ? "search=" . urlencode($search_term) . "&" : "");
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?= $pagination_url ?>page=1" class="pagination-link">First</a>
                    <a href="<?= $pagination_url ?>page=<?= $page - 1 ?>" class="pagination-link">Previous</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i == $page) ? ' active' : '';
                ?>
                    <a href="<?= $pagination_url ?>page=<?= $i ?>" class="pagination-link<?= $active ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= $pagination_url ?>page=<?= $page + 1 ?>" class="pagination-link">Next</a>
                    <a href="<?= $pagination_url ?>page=<?= $total_pages ?>" class="pagination-link">Last</a>
                <?php endif; ?>

                <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($search_term) && $result->num_rows > 0): ?>
            <div class="search-results-info">
                Found <?= $total_records ?> result<?= $total_records != 1 ? 's' : '' ?> for "<?= htmlspecialchars($search_term) ?>"
            </div>
        <?php endif; ?>
    </div>
</main>


<link rel="stylesheet" href="css/personnel.css">