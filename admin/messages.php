<?php
session_start();
include '../includes/database.php';
include '../includes/navbar.php';
include '../includes/footer.php';

// Only admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Fallback: Check if user_id exists and fetch role from DB
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && $user['role'] === 'admin') {
            $_SESSION['role'] = 'admin'; // Re-set role if missing
        } else {
            header("Location: ../pages/login.php");
            exit();
        }
    } else {
        header("Location: ../pages/login.php");
        exit();
    }
}

// Handle admin reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply'], $_POST['message_id'], $_POST['student_email'])) {
    $reply = mysqli_real_escape_string($conn, $_POST['reply']);
    $message_id = (int)$_POST['message_id'];
    $student_email = filter_var($_POST['student_email'], FILTER_VALIDATE_EMAIL);

    // Update reply in DB
    $stmt = $conn->prepare("UPDATE messages SET reply = ?, replied_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $reply, $message_id);
    $stmt->execute();
    $stmt->close();

    // Send email to student
    if ($student_email) {
        $subject = "Reply to your message at IES Campus";
        $headers = "From: no-reply@iescampus.edu.in\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        mail($student_email, $subject, $reply, $headers);
    }

    header("Location: messages.php");
    exit();
}

// Handle new admin message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_email'], $_POST['subject'], $_POST['message'])) {
    $student_email = filter_var($_POST['student_email'], FILTER_VALIDATE_EMAIL);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    if ($student_email) {
        // Find student ID based on email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
        $stmt->bind_param("s", $student_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student) {
            $student_id = $student['id'];
            $sent_at = date('Y-m-d H:i:s');

            // Insert message into messages table with admin as sender
            $stmt = $conn->prepare("INSERT INTO messages (student_id, subject, message, sent_at, reply) VALUES (?, ?, ?, ?, ?)");
            $reply = "This is an admin-initiated message.";
            $stmt->bind_param("issss", $student_id, $subject, $message, $sent_at, $reply);
            $stmt->execute();
            $stmt->close();

            // Send email
            $headers = "From: admin@iescampus.edu.in\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            mail($student_email, $subject, $message, $headers);

            echo "<script>alert('Message sent successfully!'); window.location.href='messages.php';</script>";
        } else {
            echo "<script>alert('Student email not found!');</script>";
        }
    }
}

// Fetch all students for the dropdown
$students_stmt = $conn->prepare("SELECT email, name FROM users WHERE role = 'student'");
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result->fetch_all(MYSQLI_ASSOC);
$students_stmt->close();

// Fetch all messages with user details
$stmt = $conn->prepare("SELECT m.*, u.name, u.email AS student_email FROM messages m JOIN users u ON m.student_id = u.id ORDER BY m.sent_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Messages - Admin Panel</title>
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

       J .dark-mode-toggle {
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

        h1 {
            color: var(--primary-color);
        }

        body.dark h1 {
            color: var(--dark-text);
        }

        .message-card {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 6px solid var(--primary-color);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        body.dark .message-card {
            background: #1e1e1e;
            border-left-color: var(--primary-color);
        }

        .message-card h3 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
        }

        body.dark .message-card h3 {
            color: var(--dark-text);
        }

        .message-meta {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }

        body.dark .message-meta {
            color: #aaa;
        }

        .message-body {
            font-size: 15px;
            margin-bottom: 15px;
        }

        .reply-box {
            margin-top: 15px;
        }

        .reply-box textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            font-size: 14px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        body.dark .reply-box textarea {
            background: #2a2a2a;
            color: var(--dark-text);
            border-color: #555;
        }

        .reply-box button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .reply-box button:hover {
            background-color: var(--primary-hover);
        }

        .reply-text {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-style: italic;
        }

        body.dark .reply-text {
            background: #2a2a2a;
        }

        .send-message-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        body.dark .send-message-form {
            background: #1e1e1e;
        }

        .send-message-form h2 {
            color: #333;
        }

        body.dark .send-message-form h2 {
            color: var(--dark-text);
        }

        .send-message-form label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .send-message-form select, .send-message-form input, .send-message-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        body.dark .send-message-form select, body.dark .send-message-form input, body.dark .send-message-form textarea {
            background: #2a2a2a;
            color: var(--dark-text);
            border-color: #555;
        }

        .send-message-form button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .send-message-form button:hover {
            background-color: var(--primary-hover);
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

            .message-card, .send-message-form {
                padding: 15px;
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
                    <li><a href="manage-courses.php" class="nav-link"><i class="fas fa-book"></i> Manage Courses</a></li>
                    <li><a href="budget-allocation.php" class="nav-link"><i class="fas fa-money-bill"></i> Budget Allocation</a></li>
                    <li><a href="messages.php" class="nav-link active"><i class="fas fa-envelope"></i> Messages</a></li>
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
                <h1>Student Messages</h1>
            </div>

            <!-- Send New Message Form -->
            <div class="send-message-form">
                <h2>Send New Message to Student</h2>
                <form method="post">
                    <label for="student_email">Student Email:</label>
                    <select name="student_email" id="student_email" required>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= htmlspecialchars($student['email']) ?>"><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" required>
                    <label for="message">Message:</label>
                    <textarea name="message" id="message" required></textarea>
                    <button type="submit">Send Message</button>
                </form>
            </div>

            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="message-card">
                    <h3><?php echo htmlspecialchars($row['name']); ?> (<?php echo htmlspecialchars($row['student_email']); ?>)</h3>
                    <div class="message-meta">
                        Sent: <?php echo date("F j, Y, g:i a", strtotime($row['sent_at'])); ?>
                    </div>

                    <div class="message-body">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($row['subject']); ?><br><br>
                        <strong>Message:</strong><br>
                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                    </div>

                    <div>
                        <?php if (!empty($row['reply'])): ?>
                            <strong>Reply:</strong>
                            <div class="reply-text">
                                <?php echo nl2br(htmlspecialchars($row['reply'])); ?>
                            </div>
                            <div class="message-meta">
                                Replied at: <?php echo date("F j, Y, g:i a", strtotime($row['replied_at'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="reply-box">
                                <p><strong>Reply will be sent to:</strong> <?php echo htmlspecialchars($row['student_email']); ?></p>
                                <form method="post">
                                    <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="student_email" value="<?php echo htmlspecialchars($row['student_email']); ?>">
                                    <textarea name="reply" required placeholder="Write your reply here..."></textarea>
                                    <button type="submit">Send Reply</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
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

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>