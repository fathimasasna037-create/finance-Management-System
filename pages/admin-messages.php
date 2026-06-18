<?php
session_start();
include '../includes/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
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

            // Insert message into messages table
            $stmt = $conn->prepare("INSERT INTO messages (student_id, subject, message, sent_at, reply) VALUES (?, ?, ?, ?, ?)");
            $reply = "This is an admin-initiated message.";
            $stmt->bind_param("issss", $student_id, $subject, $message, $sent_at, $reply);
            $stmt->execute();
            $stmt->close();

            // Send email
            $headers = "From: admin@iescampus.edu.in\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            mail($student_email, $subject, $message, $headers);

            echo "<script>alert('Message sent successfully!'); window.location.href='admin-messages.php';</script>";
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

// Fetch all messages
$messages_stmt = $conn->prepare("SELECT m.*, u.name, u.email FROM messages m JOIN users u ON m.student_id = u.id ORDER BY m.sent_at DESC");
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();
$messages = $messages_result->fetch_all(MYSQLI_ASSOC);
$messages_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            padding: 30px;
            background: #f4f4f4;
        }

        h1 {
            color: maroon;
            margin-bottom: 30px;
        }

        .message-card {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 6px solid maroon;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .message-card h3 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
        }

        .message-meta {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }

        .message-body {
            font-size: 15px;
            margin-bottom: 15px;
        }

        .send-message-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
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

        .send-message-form button {
            padding: 10px 20px;
            background-color: maroon;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .send-message-form button:hover {
            background-color: #a00000;
        }

        .reply-text {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>

<h1>Admin Messages</h1>

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

<!-- Message History -->
<?php foreach ($messages as $msg): ?>
    <div class="message-card">
        <h3><?= htmlspecialchars($msg['name']) ?> (<?= htmlspecialchars($msg['email']) ?>)</h3>
        <div class="message-meta">
            Sent: <?= date("F j, Y, g:i a", strtotime($msg['sent_at'])) ?>
        </div>
        <div class="message-body">
            <strong>Subject:</strong> <?= htmlspecialchars($msg['subject']) ?><br><br>
            <strong>Message:</strong><br>
            <?= nl2br(htmlspecialchars($msg['message'])) ?>
        </div>
        <?php if (!empty($msg['reply'])): ?>
            <div class="reply-text">
                <strong>Reply:</strong><br>
                <?= nl2br(htmlspecialchars($msg['reply'])) ?>
            </div>
            <div class="message-meta">
                Replied at: <?= date("F j, Y, g:i a", strtotime($msg['replied_at'])) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</body>
</html>