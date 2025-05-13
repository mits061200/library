<?php
session_start();
require_once 'header.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "library");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get transaction IDs
$transaction_ids = isset($_POST['transaction_ids']) ? explode(',', $_POST['transaction_ids']) : 
                  (isset($_GET['transaction_id']) ? [$_GET['transaction_id']] : []);

// Get borrower ID
$borrower_id = isset($_POST['borrower_id']) ? $conn->real_escape_string($_POST['borrower_id']) : '';

// Fetch borrower details
$borrower = [];
if (!empty($borrower_id)) {
    $borrower_query = "SELECT * FROM borrowers WHERE BorrowerID = '$borrower_id'";
    $borrower_result = $conn->query($borrower_query);
    if ($borrower_result && $borrower_result->num_rows > 0) {
        $borrower = $borrower_result->fetch_assoc();
    }
}

// Fetch penalty details
$penalties = [];
$total_penalty = 0;

if (!empty($transaction_ids)) {
    $ids = implode("','", array_map([$conn, 'real_escape_string'], $transaction_ids));
    
    $penalty_query = "SELECT l.TransactionID, b.Title, pt.PenaltyAmount, pt.DateIssued, 
                      DATEDIFF(pt.DateIssued, l.DueDate) AS DaysOverdue
                      FROM penaltytransaction pt
                      JOIN loan l ON pt.LoanID = l.TransactionID
                      JOIN book b ON l.BookID = b.BookID
                      WHERE l.TransactionID IN ('$ids')";
    
    $penalty_result = $conn->query($penalty_query);
    
    if ($penalty_result && $penalty_result->num_rows > 0) {
        while ($row = $penalty_result->fetch_assoc()) {
            $penalties[] = $row;
            $total_penalty += $row['PenaltyAmount'];
        }
    }
}

// Generate invoice HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penalty Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .invoice-subtitle {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .borrower-info, .invoice-info {
            width: 48%;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invoice-table th {
            background-color: #f2f2f2;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .invoice-container {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="invoice-title">PACIFIC SOUTHBAY COLLEGE, INC.</div>
            <div class="invoice-subtitle">LIBRARY MANAGEMENT SYSTEM</div>
            <div>Penalty Invoice</div>
        </div>
        
        <div class="invoice-details">
            <div class="borrower-info">
                <p><strong>Date:</strong> <?php echo date('F j, Y'); ?></p>
                <p><strong>Borrower Information:</strong></p>
                <p><strong>ID no.:</strong> <?php echo htmlspecialchars($borrower['BorrowerID'] ?? ''); ?></p>
                <p><strong>Name:</strong> <?php 
                    echo htmlspecialchars(($borrower['FirstName'] ?? '') . ' ' . 
                                         ($borrower['MiddleName'] ?? '') . ' ' . 
                                         ($borrower['LastName'] ?? '')); 
                ?></p>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Days Overdue</th>
                    <th>Penalty Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penalties as $index => $penalty): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($penalty['Title']); ?></td>
                    <td><?php echo $penalty['DaysOverdue']; ?> days</td>
                    <td>₱<?php echo number_format($penalty['PenaltyAmount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <p>Total Penalty: ₱<?php echo number_format($total_penalty, 2); ?></p>
        </div>
        
        <div class="footer">
            <p>Generated on <?php echo date('F j, Y H:i:s'); ?></p>
            <p>Thank you for using our library services</p>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>