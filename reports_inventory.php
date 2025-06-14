<?php
include('header.php');
include('navbar.php');
include('db.php');

// Fetch all purchase orders with supplier and total amount
$sql = "
    SELECT 
        po.PurchaseOrderID,
        po.ProjectName,
        po.PurchaseOrderDate,
        po.TotalAmount,
        s.Name AS SupplierName,
        po.Purpose
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.SupplierID = s.SupplierID
    ORDER BY po.PurchaseOrderDate DESC
";
$result = $conn->query($sql);

// Fetch items for each PO (for details)
function get_po_items($conn, $po_id) {
    $items = [];
    $res = $conn->query("SELECT * FROM purchase_order_items WHERE PurchaseOrderID = $po_id");
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order Reports</title>
    <link rel="stylesheet" href="css/reports_inventory.css">
</head>
<body>

<div class="report-container">
    <h2>Purchase Order Reports</h2>
    <div class="tab-buttons">
        <button class="tab-btn active">Purchased order</button>
        <span class="arrow1">&gt;</span>
        <button class="tab-btn" onclick="window.location.href='reports_holdings.php'">Holdings</button>
    </div>
    <table class="po-table">
        <thead>
            <tr>
                <th>PO #</th>
                <th>Supplier</th>
                <th>Project Name</th>
                <th>PO Date</th>
                <th>Total Amount</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['PurchaseOrderID'] ?></td>
                        <td><?= htmlspecialchars($row['SupplierName']) ?></td>
                        <td><?= htmlspecialchars($row['ProjectName']) ?></td>
                        <td><?= htmlspecialchars($row['PurchaseOrderDate']) ?></td>
                        <td>₱<?= number_format($row['TotalAmount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['Purpose']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <table class="po-items-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Qty</th>
                                        <th>Item/Particular</th>
                                        <th>Unit Price</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $items = get_po_items($conn, $row['PurchaseOrderID']);
                                    if ($items):
                                        foreach ($items as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['ItemNo']) ?></td>
                                                <td><?= htmlspecialchars($item['Quantity']) ?></td>
                                                <td><?= htmlspecialchars($item['Description']) ?></td>
                                                <td>₱<?= number_format($item['UnitPrice'], 2) ?></td>
                                                <td>₱<?= number_format($item['Amount'], 2) ?></td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr><td colspan="5">No items found for this PO.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">No purchase orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>