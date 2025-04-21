<?php
include 'db.php';

if (isset($_GET['main_id'])) {
    $main_id = intval($_GET['main_id']);
    $stmt = $conn->prepare("SELECT SubClassificationID AS sub_classification_id, Description AS sub_classification_name FROM subclassification WHERE MainClassID = ?");
    $stmt->bind_param("i", $main_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sub_classifications = [];

    while ($row = $result->fetch_assoc()) {
        $sub_classifications[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($sub_classifications);
}
?>
