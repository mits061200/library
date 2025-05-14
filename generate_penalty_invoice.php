<?php
// Enable full error reporting 
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Fetch penalty details - MODIFIED QUERY TO DIRECTLY JOIN BOOK TABLE
$penalties = [];
$total_penalty = 0;

if (!empty($transaction_ids)) {
   $escaped_ids = array_map([$conn, 'real_escape_string'], $transaction_ids);
    $ids = "'" . implode("','", $escaped_ids) . "'";

    
    // CRITICAL FIX: Use straightforward JOINs to get all data at once
    $penalty_query = "SELECT 
                    l.TransactionID, 
                    b.Title, 
                    b.AccessionNumber,
                    pt.PenaltyAmount, 
                    pt.DateIssued, 
                    l.DueDate,
                    GREATEST(0, DATEDIFF(COALESCE(pt.DateIssued, NOW()), l.DueDate)) AS DaysOverdue,
                    COALESCE(p.PenaltyRate, 5.00) AS PenaltyRate
                  FROM loan l
                  LEFT JOIN book b ON l.BookID = b.BookID
                  LEFT JOIN penaltytransaction pt ON l.TransactionID = pt.LoanID
                  LEFT JOIN penalty p ON pt.PenaltyID = p.PenaltyID
                  WHERE l.TransactionID IN ($ids)";


    $penalty_result = $conn->query($penalty_query);
    
    if ($penalty_result && $penalty_result->num_rows > 0) {
        while ($row = $penalty_result->fetch_assoc()) {
            // Calculate penalty amount if not already set
            if (!isset($row['PenaltyAmount']) || $row['PenaltyAmount'] == 0 || $row['PenaltyAmount'] === null) {
                $row['PenaltyAmount'] = $row['DaysOverdue'] * ($row['PenaltyRate'] ?? 5.00);
            }
            $penalties[] = $row;
            $total_penalty += $row['PenaltyAmount'];
        }
    } else {
        // If no results from main query, let's try a different approach
        $alt_query = "SELECT 
                        l.TransactionID, 
                        b.Title, 
                        b.AccessionNumber,
                        l.DueDate,
                        GREATEST(0, DATEDIFF(NOW(), l.DueDate)) AS DaysOverdue,
                        COALESCE(p.PenaltyRate, 5.00) AS PenaltyRate,
                        GREATEST(0, DATEDIFF(NOW(), l.DueDate)) * COALESCE(p.PenaltyRate, 5.00) AS PenaltyAmount
                      FROM loan l
                      JOIN book b ON l.BookID = b.BookID
                      LEFT JOIN penalty p ON p.PenaltyID = 1
                      WHERE l.TransactionID IN ($ids)";
                      
        $alt_result = $conn->query($alt_query);
        
        if ($alt_result && $alt_result->num_rows > 0) {
            while ($row = $alt_result->fetch_assoc()) {
                $penalties[] = $row;
                $total_penalty += $row['PenaltyAmount'];
            }
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
                    <th>Accession No.</th>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Penalty Rate</th>
                    <th>Penalty Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($penalties)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No penalty records found</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($penalties as $index => $penalty): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($penalty['AccessionNumber'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($penalty['Title'] ?? 'Unknown Title'); ?></td>
                        <td><?php echo date('M j, Y', strtotime($penalty['DueDate'] ?? 'now')); ?></td>
                        <td><?php echo $penalty['DaysOverdue'] ?? 0; ?> days</td>
                        <td>₱<?php echo number_format($penalty['PenaltyRate'] ?? 0, 2); ?>/day</td>
                        <td>₱<?php echo number_format($penalty['PenaltyAmount'] ?? 0, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="total-section">
            <p>Total Penalty: ₱<?php echo number_format($total_penalty, 2); ?></p>
        </div>
        
        <div class="footer">
            <p>Generated on <?php echo date('F j, Y H:i:s'); ?></p>
            <p>Thank you for using our library services</p>


        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                Print Invoice
            </button>
            <a href="loan_transactions.php?view" style="text-decoration: none;">
                <button style="padding: 10px 20px; background-color: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Cancel
                </button>
            </a>
        </div>

    </div>
</body>
</html>
<?php
$conn->close();
?>