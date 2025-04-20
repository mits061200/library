<?php
include('db.php');

if (isset($_GET['main_id'])) {
    $mainId = intval($_GET['main_id']);

    $query = "SELECT SubClassificationID, Description FROM subclassification WHERE MainClassID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mainId);
    $stmt->execute();
    $result = $stmt->get_result();

    $subClassifications = [];
    while ($row = $result->fetch_assoc()) {
        $subClassifications[] = [
            'sub_classification_id' => $row['SubClassificationID'],
            'sub_classification_name' => $row['Description']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($subClassifications);
}
?>
