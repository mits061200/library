<?php
include('db.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $institution_name = $_POST['institution_name'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $project_name = $_POST['project_name'];
    $po_date = $_POST['po_date'];
    $purpose = $_POST['purpose'];
    $total_amount = $_POST['total_amount'];
    $prepared_by = $_POST['prepared_by'];
    $prepared_by_position = $_POST['prepared_by_position'];
    $noted_by = $_POST['noted_by'];
    $noted_by_position = $_POST['noted_by_position'];
    $approved_by = $_POST['approved_by'];
    $approved_by_position = $_POST['approved_by_position'];

    // Debug the prepared_by_position value


    // Check if supplier exists
    $supplier_name = 'Default Supplier'; // Replace with dynamic supplier name if applicable
    $stmt = $conn->prepare("SELECT SupplierID FROM suppliers WHERE Name = ?");
    $stmt->bind_param("s", $supplier_name);
    $stmt->execute();
    $stmt->bind_result($supplier_id);
    $stmt->fetch();
    $stmt->close();

    // If supplier doesn't exist, insert it
    if (!$supplier_id) {
        $stmt = $conn->prepare("INSERT INTO suppliers (Name, Address, ContactInfo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $supplier_name, $address, $contact);
        $stmt->execute();
        $supplier_id = $stmt->insert_id;
        $stmt->close();
    }

    // Insert into purchase_orders table
    $stmt = $conn->prepare("
        INSERT INTO purchase_orders (
            SupplierID, InstitutionName, Address, ContactInfo, ProjectName, 
            PurchaseOrderDate, Purpose, TotalAmount, PreparedBy, PreparedByPosition, 
            NotedBy, NotedByPosition, ApprovedBy, ApprovedByPosition
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "isssssssssssss",
        $supplier_id, $institution_name, $address, $contact, $project_name,
        $po_date, $purpose, $total_amount, $prepared_by, $prepared_by_position,
        $noted_by, $noted_by_position, $approved_by, $approved_by_position
    );
    $stmt->execute();
    $purchase_order_id = $stmt->insert_id; // Get the last inserted ID
    $stmt->close();

    // Insert items into purchase_order_items table
    foreach ($_POST['item_no'] as $index => $item_no) {
        $quantity = $_POST['item_qty'][$index];
        $description = $_POST['item_description'][$index];
        $unit_price = $_POST['item_unit_price'][$index];
        $amount = $_POST['item_amount'][$index];

        $stmt = $conn->prepare("
            INSERT INTO purchase_order_items (
                PurchaseOrderID, ItemNo, Quantity, Description, UnitPrice, Amount
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissdd",
            $purchase_order_id, $item_no, $quantity, $description, $unit_price, $amount
        );
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to a success page or back to the form
    header("Location: add_po.php?success=1");
    exit();
}
?>