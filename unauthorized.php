<?php
include 'header.php';
include 'navbar.php';
?>

<main class="content">
    <div class="unauthorized-container">
        <h2>Access Denied</h2>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Unauthorized Access:</strong> You don't have permission to view this page.
        </div>
        <p>Only librarians can access the Personnel.</p>
        <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
    </div>
</main>

<style>
.unauthorized-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    text-align: center;
    margin-top: -80px;
}

.alert {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}

.btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}
</style>

