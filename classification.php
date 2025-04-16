<?php
include 'header.php';
include 'navbar.php';
include 'db.php';

// --- Handle Add Main Classification ---
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

// --- Handle Add Sub Classification ---
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

// --- Pagination and Search ---
$records_per_page = 3;
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

// Count total records
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

// Fetch paginated data
$main_data = [];
$fetch_sql = "
    SELECT 
        mc.MainClassificationID,
        mc.ClassificationNumber AS main_number,
        mc.Description AS main_desc,
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
    $main_number = $row['main_number'];
    if (!isset($main_data[$main_number])) {
        $main_data[$main_number] = [
            'description' => $row['main_desc'],
            'subs' => []
        ];
    }
    if ($row['sub_number']) {
        $main_data[$main_number]['subs'][] = [
            'number' => $row['sub_number'],
            'description' => $row['sub_desc']
        ];
    }
}
$stmt->close();

// Get all main classifications for dropdown
$main_classes = $conn->query("SELECT * FROM mainClassification");
?>

<main class="content">
    <div class="classification-container">
        <h2>Classification Management</h2>

        <div class="forms-container">
            <!-- Main Classification Form -->
            <div class="form-section">
                <h3>Main Classification</h3>
                <form method="POST">
                    <input type="text" name="main_classification_number" placeholder="Main Classification Number" required>
                    <input type="text" name="main_description" placeholder="Description" required>
                    <button type="submit" name="add_main_class">Add</button>
                </form>
            </div>

            <!-- Sub Classification Form -->
            <div class="form-section">
                <h3>Sub Classification</h3>
                <form method="POST">
                    <input type="text" name="sub_classification_number" placeholder="Sub Classification Number" required>
                    <input type="text" name="sub_description" placeholder="Description" required>
                    <select name="main_class" required>
                        <option value="">Select Main Class</option>
                        <?php while ($row = $main_classes->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['MainClassificationID']) ?>">
                                <?= htmlspecialchars($row['ClassificationNumber']) ?> - <?= htmlspecialchars($row['Description']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="add_sub_class">Add</button>
                </form>
            </div>
        </div>

        <!-- Search -->
        <form method="POST" class="search-form" style="margin-top: 20px;">
            <input type="text" name="search_query" placeholder="Search classification..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Table Display -->
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
                        <?php foreach ($main_data as $main_num => $main_info): ?>
                            <tr style="background: #ccc; font-weight: bold;">
                                <td><?= htmlspecialchars($main_num) ?></td>
                                <td><?= htmlspecialchars($main_info['description']) ?></td>
                                <td>
                                    <a href="#">Edit</a>
                                    <a href="#">Delete</a>
                                </td>
                            </tr>
                            <?php foreach ($main_info['subs'] as $sub): ?>
                            <tr>
                                <td style="padding-left: 20px;">&rarr; <?= htmlspecialchars($sub['number']) ?></td>
                                <td><?= htmlspecialchars($sub['description']) ?></td>
                                <td>
                                    <a href="#">Edit</a>
                                    <a href="#">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" style="margin-top: 20px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" <?= $i == $page ? 'style="font-weight:bold;"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<link rel="stylesheet" href="css/author.css">
