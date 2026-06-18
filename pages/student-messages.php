<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../pages/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch messages AND replies for this student
$messages_stmt = $conn->prepare("
    SELECT subject, message, reply, replied_at, sent_at 
    FROM messages 
    WHERE student_id = ? 
    ORDER BY replied_at DESC, sent_at DESC
");
if ($messages_stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$messages_stmt->bind_param("i", $student_id);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

$messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $messages[] = $row;
}
$messages_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Messages</title>
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

        .message-item {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 6px solid maroon;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .message-item h3 {
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

        .reply-text {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-line;
            margin-top: 10px;
        }

        body.dark .reply-text {
            background: #2a2a2a;
        }
    </style>
</head>
<body>

<h1>Your Messages</h1>

<?php if (count($messages) > 0): ?>
    <?php foreach ($messages as $msg): ?>
        <div class="message-item">
            <h3>Message Details</h3>
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
                    <strong>Admin Reply:</strong><br>
                    <?= nl2br(htmlspecialchars($msg['reply'])) ?>
                </div>
                <div class="message-meta">
                    Replied at: <?= date("F j, Y, g:i a", strtotime($msg['replied_at'])) ?>
                </div>
            <?php else: ?>
                <p style="font-style: italic; color: #999;">No reply yet.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="message-item">
        <p>No messages available.</p>
    </div>
<?php endif; ?>

</body>
</html>