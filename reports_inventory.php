<?php
include('header.php');
include('navbar.php');
include('db.php');

// Filter logic
$where = [];
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where[] = "(po.PurchaseOrderID LIKE '%$search%' OR s.Name LIKE '%$search%' OR po.ProjectName LIKE '%$search%')";
}
if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $where[] = "DATE(po.PurchaseOrderDate) = '$date'";
}
if (!empty($_GET['month'])) {
    $month = $conn->real_escape_string($_GET['month']);
    $where[] = "DATE_FORMAT(po.PurchaseOrderDate, '%Y-%m') = '$month'";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

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
    $where_sql
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
    <div class="tab-buttons1">
        <button class="tab-btn active">Purchased order</button>
        <span class="arrow1">&gt;</span>
        <button class="tab-btn" onclick="window.location.href='reports_inventoryHoldings.php'">Holdings</button>
    </div>

    <!-- Filter Form -->
    <form method="get" class="filter-form" style="margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: center;">
        <input type="text" name="search" placeholder="Search PO #, Supplier, Project..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" style="padding:8px 14px; border-radius:20px; border:1.5px solid #bbb; min-width:220px;">
        
        <label for="date" style="font-weight:500;">By Date:</label>
        <input type="date" name="date" id="date" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>" style="padding:8px 14px; border-radius:20px; border:1.5px solid #bbb;">
        
        <label for="month" style="font-weight:500;">By Month:</label>
        <input type="month" name="month" id="month" value="<?= isset($_GET['month']) ? htmlspecialchars($_GET['month']) : '' ?>" style="padding:8px 14px; border-radius:20px; border:1.5px solid #bbb;">
        
        <button type="submit" class="tab-btn active" style="padding:8px 24px; border-radius:20px; border:none; background:#e53935; color:#fff; font-weight:600;">Search</button>
        <?php if (!empty($_GET['search']) || !empty($_GET['date']) || !empty($_GET['month'])): ?>
            <a href="reports_inventory.php" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>

    <button type="button" onclick="window.print()" class="print-btn" style="background:#e53935;color:#fff;border:none;padding:8px 24px;font-size:1rem;font-weight:600;cursor:pointer;border-radius:0;margin-bottom:18px;">
        Print
    </button>

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