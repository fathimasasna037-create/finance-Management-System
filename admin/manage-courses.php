<?php
session_start();
include '../includes/config.php';
include '../includes/navbar.php';
include '../includes/footer.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "pages/login.php");
    exit();
}

$message = "";
$alertClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_course'])) {
    $course_id = $_POST['course_id'] ?? null;
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];

    if ($course_id) {
        $stmt = $conn->prepare("UPDATE courses SET course_name=?, description=?, duration=?, price=? WHERE id=?");
        $stmt->bind_param("sssdi", $course_name, $description, $duration, $price, $course_id);
        $stmt->execute();
        $message = "Course updated successfully.";
        $alertClass = "success";
    } else {
        $stmt = $conn->prepare("INSERT INTO courses (course_name, description, duration, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $course_name, $description, $duration, $price);
        $stmt->execute();
        $message = "Course added successfully.";
        $alertClass = "success";
    }
    $stmt->close();
}

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Course deleted successfully.";
        $alertClass = "danger";
    } else {
        $message = "Error deleting course.";
        $alertClass = "danger";
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM courses ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #800000;
            --primary-hover: #a00000;
            --accent-color: #ffc107;
            --light: #fff;
            --gray-light: #f8f9fa;
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

        .theme-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .theme-btn:hover {
            background-color: var(--primary-hover);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(to right, var(--primary-color), var(--primary-hover));
            color: #fff;
            border: none;
            min-width: 80px;
        }

        .btn-edit:hover {
            background: linear-gradient(to right, var(--primary-hover), var(--primary-color));
        }

        .btn-danger {
            min-width: 80px;
        }

        #searchInput {
            max-width: 300px;
            border: 2px solid var(--primary-color);
            border-radius: 5px;
            padding: 8px 12px;
        }

        #searchInput:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(128, 0, 0, 0.25);
            border-color: var(--primary-color);
        }

        .container {
            max-width: 1200px;
        }

        h2 {
            color: var(--primary-color);
        }

        body.dark h2 {
            color: var(--dark-text);
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        body.dark .table {
            background-color: #1e1e1e;
        }

        .table thead {
            background-color: var(--primary-color);
            color: white;
        }

        .table tbody tr:hover {
            background: #f1f1f1;
        }

        body.dark .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .alert {
            margin-bottom: 20px;
        }

        body.dark .alert-success {
            background-color: #2e7d32;
            color: var(--dark-text);
        }

        body.dark .alert-danger {
            background-color: #d32f2f;
            color: var(--dark-text);
        }

        .modal-content {
            border-radius: 10px;
        }

        body.dark .modal-content {
            background-color: #1e1e1e;
            color: var(--dark-text);
        }

        body.dark .modal-content .form-control {
            background-color: #2a2a2a;
            color: var(--dark-text);
            border-color: #555;
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

            .table {
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
                    <li><a href="manage-students.php" class="nav-link"><i class="fas fa-users"></i> Manage Students</a></li>
                    <li><a href="manage-courses.php" class="nav-link active"><i class="fas fa-book"></i> Manage Courses</a></li>
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
                <h1>Manage Courses</h1>
            </div>

            <div class="container mt-5">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <button class="btn theme-btn" data-bs-toggle="modal" data-bs-target="#courseModal">➕ Add New Course</button>
                    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search courses...">
                </div>

                <table class="table table-bordered table-hover bg-white" id="coursesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Course Name</th>
                            <th>Description</th>
                            <th>Duration</th>
                            <th>Price (LKR)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $index = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $index++ ?></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= htmlspecialchars($row['duration']) ?></td>
                                <td><?= number_format($row['price'], 2) ?></td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-edit me-1" onclick='editCourse(<?= json_encode($row) ?>)'> Edit</button>
                                    <a class="btn btn-sm btn-danger" href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this course?')"> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Modal for Add/Edit -->
                <div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="courseModalLabel">Add / Edit Course</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="course_id" id="course_id">
                                <div class="mb-3">
                                    <label class="form-label">Course Name</label>
                                    <input type="text" name="course_name" id="course_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="duration" id="duration" class="form-control" placeholder="e.g. 6 Months" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price (LKR)</label>
                                    <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="save_course" class="btn theme-btn">💾 Save</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        © <?php echo date("Y"); ?> IES Campus Finance System. All Rights Reserved.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        function editCourse(course) {
            document.getElementById('course_id').value = course.id;
            document.getElementById('course_name').value = course.course_name;
            document.getElementById('description').value = course.description;
            document.getElementById('duration').value = course.duration;
            document.getElementById('price').value = course.price;
            new bootstrap.Modal(document.getElementById('courseModal')).show();
        }

        document.getElementById('searchInput').addEventListener('input', function () {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('#coursesTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>