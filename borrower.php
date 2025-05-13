<?php
// Handle delete first before any output
if (isset($_GET['delete'])) {
    include 'db.php';
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM borrowers WHERE BorrowerID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: borrower.php");
    exit;
}
ASASAS

include('header.php');
include('navbar.php');
include('db.php'); // Ensure this connects to your database

// Pagination setup
$records_per_page = 3;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Edit Mode
$edit_mode = false;
$edit_borrower = [];

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM borrowers WHERE BorrowerID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $edit_borrower = $result_edit->fetch_assoc();
    $stmt->close();
}

// Update Borrower
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_borrower'])) {
    $id = $_POST['BorrowerID'];
    $firstName = $conn->real_escape_string($_POST['FirstName']);
    $middleName = $conn->real_escape_string($_POST['MiddleName']);
    $lastName = $conn->real_escape_string($_POST['LastName']);
    $contactNumber = $conn->real_escape_string($_POST['ContactNumber']);
    $role = $conn->real_escape_string($_POST['Role']);
    
    // Student-specific fields
    $level = ($role == 'Student') ? $conn->real_escape_string($_POST['Level']) : NULL;
    
    // Fields based on education level
    $year = NULL;
    $course = NULL;
    $gradeLevel = NULL;
    $strand = NULL;
    
    if ($role == 'Student') {
        if ($level == 'College') {
            $year = $conn->real_escape_string($_POST['Year']);
            $course = $conn->real_escape_string($_POST['Course']);
        } elseif ($level == 'Senior High School') {
            $gradeLevel = $conn->real_escape_string($_POST['GradeLevel']);
            $strand = $conn->real_escape_string($_POST['Strand']);
        }
    }

    $stmt = $conn->prepare("
        UPDATE borrowers SET 
            FirstName = ?, MiddleName = ?, LastName = ?, 
            ContactNumber = ?, Role = ?, Level = ?, 
            Year = ?, Course = ?, GradeLevel = ?, Strand = ?
        WHERE BorrowerID = ?
    ");
    $stmt->bind_param(
        "ssssssssssi",
        $firstName, $middleName, $lastName, $contactNumber,
        $role, $level, $year, $course, $gradeLevel, $strand, $id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Borrower updated successfully'); window.location='borrower.php';</script>";
    } else {
        echo "<script>alert('Error updating borrower: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Add New Borrower
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_borrower'])) {
    // Sanitize and collect form inputs
    $firstName = $conn->real_escape_string($_POST['FirstName']);
    $middleName = $conn->real_escape_string($_POST['MiddleName']);
    $lastName = $conn->real_escape_string($_POST['LastName']);
    $contactNumber = $conn->real_escape_string($_POST['ContactNumber']);
    $role = $conn->real_escape_string($_POST['Role']);
    
    // Student-specific fields
    $level = ($role == 'Student') ? $conn->real_escape_string($_POST['Level']) : NULL;
    
    // Fields based on education level
    $year = NULL;
    $course = NULL;
    $gradeLevel = NULL;
    $strand = NULL;
    
    if ($role == 'Student') {
        if ($level == 'College') {
            $year = $conn->real_escape_string($_POST['Year']);
            $course = $conn->real_escape_string($_POST['Course']);
        } elseif ($level == 'Senior High School') {
            $gradeLevel = $conn->real_escape_string($_POST['GradeLevel']);
            $strand = $conn->real_escape_string($_POST['Strand']);
        }
    }

    // Insert data into the `borrowers` table
    $stmt = $conn->prepare("
    INSERT INTO borrowers (
        BorrowerID, FirstName, MiddleName, LastName, ContactNumber, 
        Role, Level, Year, Course, GradeLevel, Strand
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
    "issssssssss",
    $_POST['BorrowerID'], // Add this
    $firstName, $middleName, $lastName, $contactNumber,
    $role, $level, $year, $course, $gradeLevel, $strand
    );

    if ($stmt->execute()) {
        echo "<script>alert('Borrower added successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }
    
    $stmt->close();
}

// Fetch borrowers with pagination
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role_filter']) ? $conn->real_escape_string($_GET['role_filter']) : '';

$where = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (CONCAT(FirstName, ' ', LastName) LIKE '%$search%' OR ContactNumber LIKE '%$search%')";
}

if (!empty($role_filter)) {
    $where .= " AND Role = '$role_filter'";
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) AS total FROM borrowers $where";
$total_records = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get paginated results
$sql = "
    SELECT 
        BorrowerID, FirstName, MiddleName, LastName, 
        ContactNumber, Role, Level, Year, Course, GradeLevel, Strand
    FROM borrowers
    $where
    ORDER BY LastName, FirstName
    LIMIT $offset, $records_per_page
";

$borrowers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>


<div class="content">
    <h2>Manage Borrowers</h2>   
    <form method="post" action="borrower.php" class="form-container">
        <div class="input-group">
            <label for="borrower-id">Borrower ID</label>
            <input type="text" id="borrower-id" name="BorrowerID" 
                value="<?= !$edit_mode ? '' : htmlspecialchars($edit_borrower['BorrowerID']) ?>" 
                <?= $edit_mode ? 'readonly' : '' ?>>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="FirstName" 
                    value="<?= $edit_mode ? htmlspecialchars($edit_borrower['FirstName']) : '' ?>" required>
            </div>
        </div>

        
        <div class="input-row">

            <div class="input-group">
                <label for="middle-name">Middle Name</label>
                <input type="text" id="middle-name" name="MiddleName" 
                    value="<?= $edit_mode ? htmlspecialchars($edit_borrower['MiddleName']) : '' ?>">
            </div>
        </div>


        <div class="input-row">
            <div class="input-group">
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="LastName" 
                    value="<?= $edit_mode ? htmlspecialchars($edit_borrower['LastName']) : '' ?>" required>
            </div>
            <div class="input-group">
                <label for="role">Role</label>
                <select id="role" name="Role" required onchange="toggleStudentFields()">
                    <option value="" disabled <?= !$edit_mode ? 'selected' : '' ?>>Select Role</option>
                    <option value="Student" <?= $edit_mode && $edit_borrower['Role'] == 'Student' ? 'selected' : '' ?>>Student</option>
                    <option value="Employee" <?= $edit_mode && $edit_borrower['Role'] == 'Employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="Faculty" <?= $edit_mode && $edit_borrower['Role'] == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                </select>
            </div>
        </div>

        <div class="input-row">
            <div class="input-group">
                <label for="contact-number">Contact Number</label>
                <input type="text" id="contact-number" name="ContactNumber" 
                    value="<?= $edit_mode ? htmlspecialchars($edit_borrower['ContactNumber']) : '' ?>" required>
            </div>
            <div class="input-group" id="level-group" style="<?= (!$edit_mode || $edit_borrower['Role'] != 'Student') ? 'display: none;' : '' ?>">
                <label for="level">Level</label>
                <select id="level" name="Level" onchange="toggleEducationFields()">
                    <option value="" disabled <?= (!$edit_mode || !isset($edit_borrower['Level'])) ? 'selected' : '' ?>>Select Level</option>
                    <option value="College" <?= $edit_mode && $edit_borrower['Level'] == 'College' ? 'selected' : '' ?>>College</option>
                    <option value="Senior High School" <?= $edit_mode && $edit_borrower['Level'] == 'Senior High School' ? 'selected' : '' ?>>Senior High School</option>
                </select>
            </div>
        </div>


        <!-- College Fields -->
        <div class="input-row" id="college-fields" style="display: none;">
            <div class="input-group">
                <label for="year">Year</label>
                <input type="text" id="year" name="Year" 
                    value="<?= $edit_mode && $edit_borrower['Year'] ? htmlspecialchars($edit_borrower['Year']) : '' ?>">
            </div>
            <div class="input-group">
                <label for="course">Course</label>
                <input type="text" id="course" name="Course" 
                    value="<?= $edit_mode && $edit_borrower['Course'] ? htmlspecialchars($edit_borrower['Course']) : '' ?>">
            </div>
        </div>

        <!-- Senior High School Fields -->
        <div class="input-row" id="shs-fields" style="display: none;">
            <div class="input-group">
                <label for="grade-level">Grade Level</label>
                <input type="text" id="grade-level" name="GradeLevel" 
                    value="<?= $edit_mode && $edit_borrower['GradeLevel'] ? htmlspecialchars($edit_borrower['GradeLevel']) : '' ?>">
            </div>
            <div class="input-group">
                <label for="strand">Strand</label>
                <input type="text" id="strand" name="Strand" 
                    value="<?= $edit_mode && $edit_borrower['Strand'] ? htmlspecialchars($edit_borrower['Strand']) : '' ?>">
            </div>
        </div>
    
        <?php if ($edit_mode): ?>
            <div class="button-group">
                <button type="submit" name="update_borrower" class="add-btn">Update</button>
                <a href="borrower.php" class="cancel-btn">Cancel</a>
            </div>
        <?php else: ?>
            <div class="button-group">
                <button type="submit" name="add_borrower" class="add-btn">Add</button>
            </div>
        <?php endif; ?>
    </form>

    <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="Search by name or contact number" 
               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        
        <select name="role_filter">
            <option value="">All Roles</option>
            <option value="Student" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'Student') ? 'selected' : '' ?>>Student</option>
            <option value="Employee" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'Employee') ? 'selected' : '' ?>>Employee</option>
            <option value="Faculty" <?= (isset($_GET['role_filter']) && $_GET['role_filter'] == 'Faculty') ? 'selected' : '' ?>>Faculty</option>
        </select>

        <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Borrower ID</th>
                    <th>Name</th>
                    <th>Contact Number</th>
                    <th>Role</th>
                    <th>Level</th>
                    <th>Year</th>
                    <th>Course</th>
                    <th>Grade Level</th>
                    <th>Strand</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowers as $borrower) : ?>
                    <tr>
                        <td><?= htmlspecialchars($borrower['BorrowerID']) ?></td>
                        <td><?= htmlspecialchars($borrower['FirstName'] . ' ' . ($borrower['MiddleName'] ? $borrower['MiddleName'] . ' ' : '') . $borrower['LastName']) ?></td>
                        <td><?= htmlspecialchars($borrower['ContactNumber']) ?></td>
                        <td><?= htmlspecialchars($borrower['Role']) ?></td>
                        <td><?= htmlspecialchars($borrower['Level'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($borrower['Year'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($borrower['Course'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($borrower['GradeLevel'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($borrower['Strand'] ?? 'N/A') ?></td>
                        <td>
                            <a href="borrower.php?edit=<?= $borrower['BorrowerID'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                            <a href="borrower.php?delete=<?= $borrower['BorrowerID'] ?>" class="delete" onclick="return confirmDelete(<?= $borrower['BorrowerID'] ?>)"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_records > $records_per_page): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="borrower.php?page=1<?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['role_filter']) && $_GET['role_filter'] != '' ? '&role_filter='.$_GET['role_filter'] : '' ?>" class="pagination-link">First</a>
                <a href="borrower.php?page=<?= $page - 1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['role_filter']) && $_GET['role_filter'] != '' ? '&role_filter='.$_GET['role_filter'] : '' ?>" class="pagination-link">Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="borrower.php?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['role_filter']) && $_GET['role_filter'] != '' ? '&role_filter='.$_GET['role_filter'] : '' ?>" class="pagination-link<?= $i == $page ? ' active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="borrower.php?page=<?= $page + 1 ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['role_filter']) && $_GET['role_filter'] != '' ? '&role_filter='.$_GET['role_filter'] : '' ?>" class="pagination-link">Next</a>
                <a href="borrower.php?page=<?= $total_pages ?><?= isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '' ?><?= isset($_GET['role_filter']) && $_GET['role_filter'] != '' ? '&role_filter='.$_GET['role_filter'] : '' ?>" class="pagination-link">Last</a>
            <?php endif; ?>
            
            <div class="page-info">Page <?= $page ?> of <?= $total_pages ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="css/book.css">


<script>
function confirmDelete(id) {
    return confirm("Are you sure you want to delete this borrower?");
}

function toggleStudentFields() {
    const role = document.getElementById('role').value;
    const levelGroup = document.getElementById('level-group');
    const collegeFields = document.getElementById('college-fields');
    const shsFields = document.getElementById('shs-fields');
    
    if (role === 'Student') {
        levelGroup.style.display = 'block';
        toggleEducationFields(); // Call this to set appropriate education fields
    } else {
        levelGroup.style.display = 'none';
        collegeFields.style.display = 'none';
        shsFields.style.display = 'none';
    }
}

function toggleEducationFields() {
    const role = document.getElementById('role').value;
    const level = document.getElementById('level').value;
    const collegeFields = document.getElementById('college-fields');
    const shsFields = document.getElementById('shs-fields');
    
    // Hide all education fields first
    collegeFields.style.display = 'none';
    shsFields.style.display = 'none';
    
    // Only show education fields if role is Student
    if (role === 'Student') {
        if (level === 'College') {
            collegeFields.style.display = 'flex';
        } else if (level === 'Senior High School') {
            shsFields.style.display = 'flex';
        }
    }
}

// Initialize fields visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleStudentFields();
    
    <?php if ($edit_mode && $edit_borrower['Role'] == 'Student'): ?>
        // If in edit mode and role is Student, also set up the education level fields
        toggleEducationFields();
    <?php endif; ?>
});
</script>
