<?php
include('../db.php'); // Include database connection

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$po_id = intval($_GET['id']);

// Fetch purchase order details
$stmt = $conn->prepare("SELECT * FROM purchase_orders WHERE PurchaseOrderID = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_result = $stmt->get_result();
$po = $po_result->fetch_assoc();
$stmt->close();

// Fetch purchase order items
$stmt = $conn->prepare("SELECT * FROM purchase_order_items WHERE PurchaseOrderID = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 210mm;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid black;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sub-header {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        .bold {
            font-weight: bold;
        }
        .button-container {
            width: 210mm;
            margin: 0 auto 20px auto;
            text-align: left;
        }
        .cancel-btn {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .print-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            .button-container {
                display: none;
            }
        }
    </style>
</head>
<body>
 

    <div class="container">
        <div class="header">PACIFIC SOUTHBAY COLLEGE, INC</div>
        <div class="sub-header">
            PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY<br>
            TEL. NO. 553-1450 MOBILE NO. 0946-713-6519
        </div>
        <div class="header">PROJECT NAME: <?= htmlspecialchars($po['ProjectName']) ?></div>

        <!-- Supplier and PO Details -->
        <table>
            <tr>
                <td colspan="3"><strong>Purchase Order:</strong><?= htmlspecialchars($po['PurchaseOrderID']) ?></td>
                <td colspan="3"><strong>Date:</strong> <?= htmlspecialchars($po['PurchaseOrderDate']) ?></td>
            </tr>
        </table>

        <!-- Items Table -->
        <table>
            <tr>
                <th>NO.</th>
                <th>QTY</th>
                <th>ITEMS/PARTICULAR</th>
                <th>UNIT PRICE</th>
                <th>AMOUNT</th>
            </tr>
            <?php while ($item = $items_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['ItemNo']) ?></td>
                <td><?= htmlspecialchars($item['Quantity']) ?></td>
                <td><?= htmlspecialchars($item['Description']) ?></td>
                <td><?= number_format($item['UnitPrice'], 2) ?></td>
                <td><?= number_format($item['Amount'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="4" class="bold">TOTAL:</td>
                <td class="bold"><?= number_format($po['TotalAmount'], 2) ?></td>
            </tr>
        </table>

        <!-- Purpose -->
        <p><strong>Purpose:</strong> <?= htmlspecialchars($po['Purpose']) ?></p>

        <!-- Signatures -->
        <table>
            <tr>
                <td>
                    <p>Prepared by:</p>
                    <p><strong><?= htmlspecialchars($po['PreparedBy']) ?></strong></p>
                    <p><?= htmlspecialchars($po['PreparedByPosition']) ?></p>
                </td>
                <td>
                    <p>Noted by:</p>
                    <p><strong><?= htmlspecialchars($po['NotedBy']) ?></strong></p>
                    <p><?= htmlspecialchars($po['NotedByPosition']) ?></p>
                </td>
                <td>
                    <p>Approved by:</p>
                    <p><strong><?= htmlspecialchars($po['ApprovedBy']) ?></strong></p>
                    <p><?= htmlspecialchars($po['ApprovedByPosition']) ?></p>
                </td>
            </tr>
        </table>
    </div>

       <div class="button-container">
        <button class="cancel-btn" onclick="window.location.href='../list_of_po.php'">Back to List</button>
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>

    <script>
        // If user needs to close immediately when cancel is clicked
        document.querySelector('.cancel-btn').addEventListener('click', function() {
            // If opened in a new tab/window
            if(window.opener) {
                window.close();
            }
        });
    </script>
</body>
</html>