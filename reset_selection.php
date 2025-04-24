<?php
// Start session
session_start();

// Clear selected books
$_SESSION['selected_books'] = [];

// Redirect back to loan page
header('Location: loan.php');
exit;
?>