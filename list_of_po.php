<?php
include('header.php'); // Include the header
include('navbar.php'); // Include the sidebar
include('db.php'); // Include database connection

// Fetch all purchase orders
$query = "SELECT * FROM purchase_orders ORDER BY PurchaseOrderID DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/list_of_po.css">
    <title>Purchase Orders</title>
</head>
<body>
    <div class="container">

        <!-- Header Tabs -->
        <div class="tab-buttons">
            <button class="tab-btn" onclick="window.location.href='add_po.php'">Purchase Order</button>
            <span class="arrow">&gt;</span>
            <button class="tab-btn active">List of PO</button>
                    
        </div>
        <h2>Purchase Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>PO ID</th>
                    <th>Institution Name</th>
                    <th>Project Name</th>
                    <th>PO Date</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['PurchaseOrderID']) ?></td>
                            <td><?= htmlspecialchars($row['InstitutionName']) ?></td>
                            <td><?= htmlspecialchars($row['ProjectName']) ?></td>
                            <td><?= htmlspecialchars($row['PurchaseOrderDate']) ?></td>
                            <td><?= number_format($row['TotalAmount'], 2) ?></td>
                            <td>
    <a href="printlayout/po.php?id=<?= $row['PurchaseOrderID'] ?>" target="_blank" class="print-btn">Print</a>
</td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No purchase orders found</td></tr>
        
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>