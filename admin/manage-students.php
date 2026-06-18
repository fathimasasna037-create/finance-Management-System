<?php
session_start();
include '../includes/config.php';
include '../includes/navbar.php';
include '../includes/footer.php';

// Fetch course list for dropdown
$courses = $conn->query("SELECT id, course_name FROM courses");

// Base query
$filter_query = "
    SELECT 
        e.id AS enrollment_id,
        e.student_id,
        u.name AS student_name,
        e.course_id,
        c.course_name,
        c.price AS total_course_fee,
        e.paid_amount,
        (c.price - e.paid_amount) AS balance,
        e.enrolled_at
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN courses c ON e.course_id = c.id
";

// Apply filters if set
$conditions = [];
$params = [];
$types = "";

if (!empty($_GET['course_id'])) {
    $conditions[] = "c.id = ?";
    $params[] = $_GET['course_id'];
    $types .= "i";
}
if (!empty($_GET['student_id'])) {
    $conditions[] = "u.id = ?";
    $params[] = $_GET['student_id'];
    $types .= "i";
}

if (!empty($conditions)) {
    $filter_query .= " WHERE " . implode(" AND ", $conditions);
}
$filter_query .= " ORDER BY e.enrolled_at DESC";

$stmt = $conn->prepare($filter_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Finance Overview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #800000;
            --primary-hover: #a00000;
            --accent-color: #ffc107;
            --light: #fff;
            --gray-light: #f4f4f4;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        body.dark {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-sizing: border-box;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            height: 60px;
            width: auto;
        }

        .brand-text {
            font-size: 22px;
            font-weight: bold;
            color: white;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            font-weight: bold;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: #ffcc00;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            transform: scale(1.05);
        }

        .dashboard {
            display: flex;
            width: 100%;
            margin-top: 100px;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transform: translateX(-100%);
            transition: var(--transition);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 4px;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 0 20px;
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

        .main-content {
            margin-left: 0;
            padding: 40px;
            flex: 1;
            transition: var(--transition);
        }

        .main-content.sidebar-active {
            margin-left: 250px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .burger-menu {
            font-size: 24px;
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 10px;
        }

        body.dark .burger-menu {
            color: var(--dark-text);
        }

        .dark-mode-toggle {
            background: var(--accent-color);
            color: black;
            padding: 10px;
            border: none;
            width: calc(100% - 40px);
            margin: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
        }

        .logout-btn {
            background: #ff4b2b;
            color: white;
            border: none;
            padding: 12px 20px;
            margin: 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            width: calc(100% - 40px);
        }

        h2 {
            color: #333;
        }

        body.dark h2 {
            color: var(--dark-text);
        }

        form {
            margin-bottom: 20px;
        }

        select, input[type="text"] {
            padding: 10px;
            margin-right: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        body.dark select, body.dark input[type="text"] {
            background-color: #1e1e1e;
            color: var(--dark-text);
            border-color: #555;
        }

        button {
            padding: 10px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 30px;
        }

        body.dark table {
            background-color: #1e1e1e;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        body.dark th, body.dark td {
            border-bottom: 1px solid #555;
        }

        th {
            background: var(--primary-color);
            color: white;
        }

        tr:hover {
            background: #f1f1f1;
        }

        body.dark tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        #searchInput {
            float: right;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        body.dark #searchInput {
            background-color: #1e1e1e;
            color: var(--dark-text);
            border-color: #555;
        }

        h3 {
            margin-top: 60px;
            color: #333;
        }

        body.dark h3 {
            color: var(--dark-text);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }

            .nav-links {
                width: 100%;
                justify-content: space-between;
                margin-top: 10px;
                flex-wrap: wrap;
            }

            .nav-links a {
                flex: 1 1 auto;
                text-align: center;
            }

            .sidebar {
                top: 100px;
                height: calc(100vh - 100px);
            }

            .main-content {
                padding: 20px;
            }

            .main-content.sidebar-active {
                margin-left: 0;
            }

            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div>
                <div class="sidebar-header">
                    <h3>Admin Panel</h3>
                    <p>Welcome, Admin</p>
                </div>
                <ul class="nav-menu">
                    <li><a href="admin-dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="manage-students.php" class="nav-link active"><i class="fas fa-users"></i> Manage Students</a></li>
                    <li><a href="manage-courses.php" class="nav-link"><i class="fas fa-book"></i> Manage Courses</a></li>
                    <li><a href="budget-allocation.php" class="nav-link"><i class="fas fa-money-bill"></i> Budget Allocation</a></li>
                    <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a></li>
                    <li><a href="salary-transaction.php" class="nav-link"><i class="fas fa-money-check-alt"></i> Salary Transactions</a></li>
                </ul>
            </div>
            <div>
                <button class="dark-mode-toggle" onclick="toggleDarkMode()"><i class="fas fa-moon"></i> Dark Mode</button>
                <button class="logout-btn" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <div class="header">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>Student Finance Overview</h1>
            </div>

            <form method="GET">
                <select name="course_id">
                    <option value="">-- Filter by Course --</option>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?= $course['id'] ?>" <?= ($_GET['course_id'] ?? '') == $course['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <input type="text" name="student_id" placeholder="Student ID" value="<?= htmlspecialchars($_GET['student_id'] ?? '') ?>">
                <button type="submit">Apply Filters</button>
            </form>

            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Quick Search..." />

            <table id="studentTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Total Fee (Rs)</th>
                        <th>Paid Amount (Rs)</th>
                        <th>Balance (Rs)</th>
                        <th>Enrolled At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_id']) ?></td>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['course_id']) ?></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><?= number_format($row['total_course_fee'], 2) ?></td>
                                <td><?= number_format($row['paid_amount'], 2) ?></td>
                                <td><?= number_format($row['balance'], 2) ?></td>
                                <td><?= htmlspecialchars($row['enrolled_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No student enrollments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            // Fetch and display installment details
            $sql = "SELECT 
                        u.name AS student_name, 
                        c.course_name, 
                        i.installment_number, 
                        i.amount, 
                        i.due_date, 
                        i.paid 
                    FROM installments i
                    JOIN enrollments e ON i.enrollment_id = e.id
                    JOIN users u ON e.student_id = u.id
                    JOIN courses c ON e.course_id = c.id
                    ORDER BY u.name, i.installment_number";

            $installments = $conn->query($sql);
            ?>

            <h3>All Installments</h3>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Installment No.</th>
                        <th>Amount (Rs)</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($installments->num_rows > 0): ?>
                        <?php while ($row = $installments->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><?= $row['installment_number'] ?></td>
                                <td><?= number_format($row['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['due_date']) ?></td>
                                <td><?= $row['paid'] ? 'Paid' : 'Unpaid' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No installment records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> IES Campus Finance System. All Rights Reserved.
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
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

        function filterTable() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#studentTable tbody tr");
            rows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                row.style.display = rowText.includes(input) ? "" : "none";
            });
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>