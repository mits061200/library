<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Bar</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
</head>
<body>

<nav>
    <ul>
        <li class="dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-folder"></i>
                <span class="nav-item">File</span>
                <i class="fas fa-chevron-down arrow"></i> <!-- "V" Shape Indicator -->
            </a>
            <ul class="dropdown-content">
                <li><a href="author.php">Author</a></li>
                <li><a href="classification.php">Classification</a></li>
                <li><a href="category.php">Category</a></li>
                <li><a href="material.php">Material</a></li>
                <li><a href="location.php">Location</a></li>
                <li><a href="personnel.php">Personnel</a></li>
                <li><a href="borrowersr.php">Borrowers</a></li>
                <li><a href="penalty.php">Penalty</a></li>
                <li><a href="supplier.php">Supplier</a></li>
                <li><a href="book.php">Book</a></li>
            </ul>
        </li>

        <li class="dropdown">
            <a href="catalog.php">
                <i class="fas fa-file"></i>
                <span class="nav-item">Catalog</span>
            </a>
        </li>

        <li class="dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-book-open"></i>
                <span class="nav-item">Transactions</span>
                <i class="fas fa-chevron-down arrow"></i>
            </a>
            <ul class="dropdown-content">
                <li><a href="loan.php">Loan</a></li>
                <li><a href="return.php">Return</a></li>
                <li><a href="penalized.php">Penalized</a></li>
            </ul>
        </li>

        <li class="dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-archive"></i>
                <span class="nav-item">Inventory</span>
                <i class="fas fa-chevron-down arrow"></i>
            </a>
            <ul class="dropdown-content">
                <li><a href="purchaseOrder.php">Purchase Order</a></li>
                <li><a href="stockIn.php">Stock in</a></li>
                <li><a href="holdings.php">View Holdings</a></li>
                <li><a href="outOfStock.php">Out of Stock</a></li>
                <li><a href="agingDisposal.php">Aging Books/For Disposal</a></li>
            </ul>
        </li>

        <li class="dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="fas fa-chart-line"></i>
                <span class="nav-item">Reports</span>
                <i class="fas fa-chevron-down arrow"></i>
            </a>
            <ul class="dropdown-content">
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="loansReturns.php">Loans/Returns</a></li>
                <li><a href="classifications.php">Classification</a></li>
                <li><a href="lists.php">Lists</a></li>
                <li><a href="penalizedBorroers.php">Penalized Borrowers</a></li>
            </ul>
        </li>

        <li><a href="#" class="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-item">Log out</span>
        </a></li>
    </ul>
</nav>

<script>
    // JavaScript for toggling dropdown on click
document.querySelectorAll(".dropdown-toggle").forEach(button => {
    button.addEventListener("click", function(event) {
        event.preventDefault(); // Prevent default link behavior

        let dropdown = this.nextElementSibling;

        // Close all other dropdowns before opening a new one
        document.querySelectorAll(".dropdown-content").forEach(menu => {
            if (menu !== dropdown) {
                menu.classList.remove("active");
                let arrow = menu.previousElementSibling.querySelector(".arrow");
                if (arrow) arrow.classList.remove("rotate");
            }
        });

        // Toggle active class for clicked dropdown
        dropdown.classList.toggle("active");

        // Rotate arrow icon
        let arrow = this.querySelector(".arrow");
        if (arrow) arrow.classList.toggle("rotate");
    });
});

// Close dropdown if clicked outside
document.addEventListener("click", function(event) {
    if (!event.target.closest(".dropdown")) {
        document.querySelectorAll(".dropdown-content").forEach(menu => {
            menu.classList.remove("active");
            let arrow = menu.previousElementSibling.querySelector(".arrow");
            if (arrow) arrow.classList.remove("rotate");
        });
    }
});

document.querySelectorAll(".dropdown-content li a").forEach(item => {
    item.addEventListener("click", function() {
        document.querySelectorAll(".dropdown-content li a").forEach(link => {
            link.classList.remove("active");
        });
        this.classList.add("active");
    });
});

</script>


</body>
</html>
