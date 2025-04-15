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
}

// Search Author
$search = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
    $search = $_POST['search_query'];
    $stmt = $conn->prepare("SELECT * FROM authors WHERE first_name LIKE ? OR last_name LIKE ?");
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM authors");

    // Edit Author
$edit_mode = false;
$edit_author = [];
}
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

?>


<main class="content">
    <div class="author-container">
        <h2><?= $edit_mode ? 'Edit Author' : 'Add Author' ?></h2>


        <div class="form-container">
            <form action="author.php" method="POST">
                <input type="hidden" name="author_id" value="<?= $edit_mode ? htmlspecialchars($edit_author['author_id']) : '' ?>">

                <label>First Name:</label>
                <input type="text" name="first_name" placeholder="Enter First Name" required>

                <label>Middle Name:</label>
                <input type="text" name="middle_name" placeholder="Enter Middle Name">

                <label>Last Name:</label>
                <div class="input-button-container">
                    <input type="text" name="last_name" class="last-name-input" placeholder="Enter Last Name" required value="<?= $edit_mode ? htmlspecialchars($edit_author['last_name']) : '' ?>">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="update_author" class="add-btn">Update</button>
                    <?php else: ?>
                        <button type="submit" name="add_author" class="add-btn">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <form action="author.php" method="POST">
        <div class="search-container">
            
                <input type="text" class="search-input" name="search_query" placeholder="Search">
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
                        <?php while ($row = $result->fetch_assoc()) { ?>
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
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</main>
<script>
function confirmDelete(authorId) {
    let confirmAction = confirm("Do you want to delete this author?");
    return confirmAction; // Returns true to proceed, false to cancel
}
</script>
jshajsjasjaska

<link rel="stylesheet" href="author.css">
