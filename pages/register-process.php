<?php
session_start();
include '../includes/config.php'; // Database connection + BASE_URL

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $admin_code = $_POST['admin_code'] ?? null;

    // Step 0: Admin code check
    if ($role === 'admin') {
        if (empty($admin_code)) {
            echo "<script>alert('Admin registration code is required.'); window.location.href='register.php';</script>";
            exit();
        }

        // Validate the code and ensure it's unused
        $code_check = $conn->prepare("SELECT id, is_used FROM admin_registration_codes WHERE code = ?");
        $code_check->bind_param("s", $admin_code);
        $code_check->execute();
        $code_result = $code_check->get_result();

        if ($code_result->num_rows === 0) {
            echo "<script>alert('Invalid admin registration code.'); window.location.href='register.php';</script>";
            exit();
        }

        $code_data = $code_result->fetch_assoc();
        if ($code_data['is_used']) {
            echo "<script>alert('This admin code has already been used.'); window.location.href='register.php';</script>";
            exit();
        }
        $code_check->close();
    }

    // Step 1: Register user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $password, $role);

    if ($stmt->execute()) {
        $inserted_id = $stmt->insert_id;
        $_SESSION['user_id'] = $inserted_id;
        $_SESSION['role'] = $role;

        // Step 2: Custom ID generation
        if ($role === 'student') {
            $custom_id = 'IES' . str_pad($inserted_id, 3, '0', STR_PAD_LEFT);
        } elseif ($role === 'admin') {
            $custom_id = 'ADM' . str_pad($inserted_id, 3, '0', STR_PAD_LEFT);
        }

        // Step 3: Save custom ID
        $update_stmt = $conn->prepare("UPDATE users SET user_custom_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $custom_id, $inserted_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Step 4: If admin, mark code as used
        if ($role === 'admin') {
            $mark_used = $conn->prepare("UPDATE admin_registration_codes SET is_used = 1 WHERE id = ?");
            $mark_used->bind_param("i", $code_data['id']);
            $mark_used->execute();
            $mark_used->close();
        }

        // Step 5: Redirect
        $redirect_url = ($role === 'admin') ? BASE_URL . "admin/admin-dashboard.php" : BASE_URL . "pages/courses.php";
        header("Location: $redirect_url");
        exit();
    } else {
        echo "<script>alert('Registration failed. Please try again.'); window.location.href='register.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
