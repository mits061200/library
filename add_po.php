<?php
include('header.php'); // Include the header
include('navbar.php'); // Include the sidebar
include('db.php');

$suppliers_result = $conn->query("SELECT SupplierID, Name FROM suppliers");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/add_po.css">
    <title>Add Purchase Order</title>
</head>
<body>
    <div class="container">
        <!-- Header Tabs -->
                <div class="tab-buttons">
                    <button class="tab-btn active">Purchase Order</button>
                    <span class="arrow">&gt;</span>
                    <button class="tab-btn" onclick="window.location.href='list_of_po.php'">List of PO</button>
                </div>

        <div class="header">Add New Purchase Order</div>
        <form action="save_po.php" method="POST">
            <!-- Institution Details -->
        <div class="form-group">
            <label for="supplier_id">Supplier:</label>
            <select id="supplier_id" name="supplier_id" required>
                <option value="">Select Supplier</option>
                <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                    <option value="<?= $supplier['SupplierID'] ?>"
                        <?= (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['SupplierID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($supplier['Name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
            <div class="form-group">
                <label for="institution_name">Institution Name:</label>
                <input type="text" id="institution_name" name="institution_name" value="PACIFIC SOUTHBAY COLLEGE, INC" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" rows="3" required>PUROK CARMENVILLE BRGY. CALUMPANG, GENERAL SANTOS CITY</textarea>
            </div>
            <div class="form-group">
                <label for="contact">Contact Information:</label>
                <input type="text" id="contact" name="contact" value="TEL. NO. 553-1450 MOBILE NO. 0946-713-6519" required>
            </div>

            <!-- Project Details -->
            <div class="form-group">
                <label for="project_name">Project Name:</label>
                <input type="text" id="project_name" name="project_name" value="BOOKS TO PURCHASE" required>
            </div>

            <!-- Purchase Order Details -->
            <div class="form-group">
                <label for="po_date">Purchase Order Date:</label>
                <input type="date" id="po_date" name="po_date" value="2024-12-02" required>
            </div>

            <!-- Items Table -->
            <table id="items-table">
                <thead>
                    <tr>
                        <th>NO.</th>
                        <th>QTY</th>
                        <th>ITEMS/PARTICULAR</th>
                        <th>UNIT PRICE</th>
                        <th>AMOUNT</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="number" name="item_no[]" required></td>
                        <td><input type="number" name="item_qty[]" oninput="calculateRowAmount(this)" required></td>
                        <td><input type="text" name="item_description[]" required></td>
                        <td><input type="number" step="0.01" name="item_unit_price[]" oninput="calculateRowAmount(this)" required></td>
                        <td><input type="number" step="0.01" name="item_amount[]" readonly></td>
                        <td><button type="button" class="remove-item-btn" onclick="removeRow(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="add-item-btn" onclick="addRow()">Add Item</button>

            <!-- Total -->
            <div class="form-group">
                <label for="total_amount">Total Amount:</label>
                <input type="number" step="0.01" id="total_amount" name="total_amount" readonly>
            </div>

            <!-- Purpose -->
            <div class="form-group">
                <label for="purpose">Purpose:</label>
                <textarea id="purpose" name="purpose" rows="3" required>SOCIAL WORK PROGRAM.</textarea>
            </div>

            <!-- Signatures -->
            <div class="form-group">
                <label for="prepared_by">Prepared By:</label>
                <input type="text" id="prepared_by" name="prepared_by" value="GELYMAE V. ENERO" required>
            </div>
            <div class="form-group">
                <label for="prepared_by_position">Position:</label>
                <input type="text" id="prepared_by_position" name="prepared_by_position" value="PSCI, Librarian" required>
            </div>
            <div class="form-group">
                <label for="noted_by">Noted By:</label>
                <input type="text" id="noted_by" name="noted_by" value="KENNETH D. CLAUDIO, MBM" required>
            </div>
            <div class="form-group">
                <label for="noted_by_position">Position:</label>
                <input type="text" id="noted_by_position" name="noted_by_position" value="VP for Academics" required>
            </div>
            <div class="form-group">
                <label for="approved_by">Approved By:</label>
                <input type="text" id="approved_by" name="approved_by" value="DR. LEANDRO ADOR A. DIZON, CPA" required>
            </div>
            <div class="form-group">
                <label for="approved_by_position">Position:</label>
                <input type="text" id="approved_by_position" name="approved_by_position" value="School President" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="submit-btn">Save Purchase Order</button>
        </form>
    </div>

    <script>
        function calculateRowAmount(element) {
            const row = element.closest('tr');
            const qty = parseFloat(row.querySelector('input[name="item_qty[]"]').value) || 0;
            const unitPrice = parseFloat(row.querySelector('input[name="item_unit_price[]"]').value) || 0;
            const amountField = row.querySelector('input[name="item_amount[]"]');
            const amount = qty * unitPrice;
            amountField.value = amount.toFixed(2);

            calculateTotalAmount();
        }

        function calculateTotalAmount() {
            const rows = document.querySelectorAll('#items-table tbody tr');
            let total = 0;

            rows.forEach(row => {
                const amount = parseFloat(row.querySelector('input[name="item_amount[]"]').value) || 0;
                total += amount;
            });

            document.getElementById('total_amount').value = total.toFixed(2);
        }

        function addRow() {
            const table = document.getElementById('items-table').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="number" name="item_no[]" required></td>
                <td><input type="number" name="item_qty[]" oninput="calculateRowAmount(this)" required></td>
                <td><input type="text" name="item_description[]" required></td>
                <td><input type="number" step="0.01" name="item_unit_price[]" oninput="calculateRowAmount(this)" required></td>
                <td><input type="number" step="0.01" name="item_amount[]" readonly></td>
                <td><button type="button" class="remove-item-btn" onclick="removeRow(this)">Remove</button></td>
            `;
        }

        function removeRow(button) {
            const row = button.parentElement.parentElement;
            row.remove();
            calculateTotalAmount();
        }
    </script>
</body>
</html>