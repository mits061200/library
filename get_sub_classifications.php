<?php
include 'db.php';

header('Content-Type: application/json');

if (isset($_GET['main_id'])) {
    $mainId = (int)$_GET['main_id'];
    $stmt = $conn->prepare("SELECT SubClassificationID, Description FROM subclassification WHERE MainClassID = ?");
    $stmt->bind_param("i", $mainId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subClassifications = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($subClassifications);
    exit;
}

echo json_encode([]);
?>