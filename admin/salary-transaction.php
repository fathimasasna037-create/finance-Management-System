<?php
session_start();
include '../includes/database.php';
include '../includes/navbar.php';
include '../includes/footer.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../pages/login.php");
    exit();
}

if (!isset($conn) || $conn === null) {
    die("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['employee_name'], $_POST['position'], $_POST['bank_name'], $_POST['bank_account_number'], $_POST['phone_number'], $_POST['amount'], $_POST['transaction_date'])) {
    $employee_name = $_POST['employee_name'];
    $position = $_POST['position'];
    $bank_name = $_POST['bank_name'];
    $bank_account_number = $_POST['bank_account_number'];
    $phone_number = $_POST['phone_number'];
    $amount = floatval($_POST['amount']);
    $transaction_date = $_POST['transaction_date'];

    mysqli_begin_transaction($conn);

    try {
        $stmt = $conn->prepare("INSERT INTO salary_transactions (employee_name, position, bank_name, bank_account_number, phone_number, amount, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssd", $employee_name, $position, $bank_name, $bank_account_number, $phone_number, $amount, $transaction_date);
        $stmt->execute();
        $stmt->close();

        $updateStmt = $conn->prepare("UPDATE enrollments SET paid_amount = paid_amount - ? WHERE paid_amount >= ?");
        $updateStmt->bind_param("dd", $amount, $amount);
        $updateStmt->execute();
        $affectedRows = $updateStmt->affected_rows;

        if ($affectedRows == 0) {
            throw new Exception("Insufficient balance in enrollments to process the transaction.");
        }

        $updateStmt->close();
        mysqli_commit($conn);

        echo "<script>alert('Transaction processed successfully!'); window.location.href='salary-transaction.php';</script>";
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='salary-transaction.php';</script>";
        exit();
    }
}

$transactionsResult = mysqli_query($conn, "SELECT * FROM salary_transactions ORDER BY transaction_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Transactions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #800000;
            --primary-hover: #a00000;
            --accent-color: #ffc107;
            --light: #fff;
            --gray-light: #f5f5f5;
            --dark-bg: #121212;
            --dark-text: #e0e0e0;
            --shadow: 0 4px 8px rgba(0,0,0,0.1);
            --transition: all 0.3s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--gray-light);
            color: #333;
            min-height: 100vh;
            padding-top: 80px;
            transition: var(--transition);
        }

        body.dark {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .brand-text {
            font-size: 22px;
            font-weight: bold;
            color: white;
        }

        .dashboard {
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            top: 80px;
            left: -250px;
            height: 100%;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 999;
            padding: 20px 0;
        }

        .sidebar.active {
            left: 0;
        }

        .nav-menu {
            list-style: none;
            padding: 0 20px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin: 8px 0;
            color: #f1f1f1;
            text-decoration: none;
            border-radius: 4px;
        }

        .nav-link i {
            margin-right: 10px;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .dark-mode-toggle,
        .logout-btn {
            margin: 20px;
            width: calc(100% - 40px);
            padding: 12px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .dark-mode-toggle {
            background: var(--accent-color);
            color: black;
        }

        .logout-btn {
            background: #ff4b2b;
            color: white;
        }

        .main-content {
            margin-left: 0;
            padding: 40px;
            flex: 1;
            width: 100%;
            transition: var(--transition);
        }

        .main-content.sidebar-active {
            margin-left: 250px;
        }

        .burger-menu {
            font-size: 24px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }

        h1, h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        body.dark .form-group {
            background: #1e1e1e;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        body.dark .form-group input,
        body.dark .form-group select {
            background: #2a2a2a;
            color: var(--dark-text);
            border-color: #555;
        }

        .form-group button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-group button:hover {
            background-color: var(--primary-hover);
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .transactions-table th,
        .transactions-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .transactions-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .footer {
            text-align: center;
            margin-top: auto;
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .main-content.sidebar-active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <span class="brand-text">IES Campus</span>
    </div>

    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <ul class="nav-menu">
                <li><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage-students.php" class="nav-link"><i class="fas fa-users"></i> Manage Students</a></li>
                <li><a href="manage-courses.php" class="nav-link"><i class="fas fa-book"></i> Manage Courses</a></li>
                <li><a href="budget-allocation.php" class="nav-link"><i class="fas fa-money-bill"></i> Budget Allocation</a></li>
                <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="salary-transaction.php" class="nav-link active"><i class="fas fa-money-check-alt"></i> Salary Transactions</a></li>
            </ul>
            <button class="dark-mode-toggle" onclick="toggleDarkMode()">🌙 Dark Mode</button>
            <button class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </div>

        <div class="main-content" id="main-content">
            <h1>Salary Transactions</h1>

            <h2>Add New Transaction</h2>
            <form method="post">
                <div class="form-group">
                    <label for="employee_name">Employee Name:</label>
                    <input type="text" name="employee_name" id="employee_name" required>
                </div>
                <div class="form-group">
                    <label for="position">Position:</label>
                    <select name="position" id="position" required>
                        <option value="lecturer">Lecturer</option>
                        <option value="non_administration_staff">Administration Staff</option>
                        <option value="maintenance_staff">Maintenance Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bank_name">Bank Name:</label>
                    <input type="text" name="bank_name" id="bank_name" required>
                </div>
                <div class="form-group">
                    <label for="bank_account_number">Bank Account Number:</label>
                    <input type="text" name="bank_account_number" id="bank_account_number" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="tel" name="phone_number" id="phone_number" pattern="[0-9]{10}" required>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (LKR):</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="transaction_date">Transaction Date:</label>
                    <input type="date" name="transaction_date" id="transaction_date" required>
                </div>
                <div class="form-group">
                    <button type="submit">Process Transaction</button>
                </div>
            </form>

            <h2>Transaction History</h2>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Position</th>
                        <th>Bank Name</th>
                        <th>Bank Account</th>
                        <th>Phone Number</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $transactionsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_name']); ?></td>
                            <td><?= htmlspecialchars($row['position']); ?></td>
                            <td><?= htmlspecialchars($row['bank_name']); ?></td>
                            <td><?= htmlspecialchars($row['bank_account_number']); ?></td>
                            <td><?= htmlspecialchars($row['phone_number']); ?></td>
                            <td>LKR <?= number_format($row['amount'], 2); ?></td>
                            <td><?= $row['transaction_date']; ?></td>
                            <td><?= $row['created_at']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        © <?= date("Y") ?> IES Campus Finance System. All Rights Reserved.
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const main = document.getElementById('main-content');
            sidebar.classList.toggle('active');
            main.classList.toggle('sidebar-active');
        }

        function toggleDarkMode() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        }

        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = '../pages/logout.php';
            }
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>
