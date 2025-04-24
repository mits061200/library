<?php
// Start session
session_start();

// Initialize response array
$response = [
    'success' => false,
    'error' => ''
];

// Check if required parameters are set
if (isset($_POST['action']) && $_POST['action'] === 'update_book_date' && 
    isset($_POST['index']) && isset($_POST['date_type']) && isset($_POST['value'])) {
    
    $index = intval($_POST['index']);
    $dateType = $_POST['date_type']; // 'date_loan' or 'due_date'
    $value = $_POST['value'];
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $response['error'] = 'Invalid date format';
        echo json_encode($response);
        exit;
    }
    
    // Make sure selected_books session variable exists
    if (!isset($_SESSION['selected_books']) || !is_array($_SESSION['selected_books'])) {
        $response['error'] = 'No books selected';
        echo json_encode($response);
        exit;
    }
    
    // Make sure the index exists in the array
    if (!isset($_SESSION['selected_books'][$index])) {
        $response['error'] = 'Invalid book index';
        echo json_encode($response);
        exit;
    }
    
    // Update the date value
    $_SESSION['selected_books'][$index][$dateType] = $value;
    
    // Success response
    $response['success'] = true;
} else {
    $response['error'] = 'Missing required parameters';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>