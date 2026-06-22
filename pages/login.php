<?php 
session_start();
include '../includes/database.php';
include '../includes/navbar.php';
include '../includes/footer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = trim(strtolower($user['role']));
            $_SESSION['email'] = $user['email'];

            if ($_SESSION['role'] == 'admin') {
                header("Location: ../admin/admin-dashboard.php");
                exit();
            } elseif ($_SESSION['role'] == 'student') {
                $student_id = $_SESSION['user_id'];
                $check_enrollment = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ?");
                $check_enrollment->bind_param("i", $student_id);
                $check_enrollment->execute();
                $enrollment_result = $check_enrollment->get_result();

                if ($enrollment_result->num_rows > 0) {
                    header("Location: /Finance_Management_System/pages/student-dashboard.php");
                } else {
                    header("Location: /Finance_Management_System/pages/courses.php");
                }
                exit();
            } else {
                echo "Error: Role not recognized (" . htmlspecialchars($_SESSION['role']) . ")";
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IES Campus</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: url('../assets/images/ies.webp') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .navbar {
            background-color: maroon;
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
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            font-weight: bold;
            transition: all 0.3s ease-in-out;
        }

        .nav-links a:hover {
            color: #ffcc00;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            transform: scale(1.05);
        }

        .login-container {
            margin-top: 120px; /* Increased to account for fixed navbar */
            background: rgba(128, 0, 0, 0.7); /* Maroon with 70% opacity */
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 60px; /* Increased padding for larger card */
            width: 100%;
            max-width: 500px; /* Significantly increased width for larger card */
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 35px;
            color: #fff;
            font-size: 28px; /* Larger font for title */
            letter-spacing: 1px;
        }

        .input-group {
            margin-bottom: 28px; /* Increased spacing for larger card */
        }

        label {
            display: block;
            font-size: 16px; /* Larger font */
            color: #fff;
            margin-bottom: 12px;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 16px 18px; /* Increased padding for inputs */
            border: none;
            border-radius: 8px;
            font-size: 17px; /* Larger font */
            outline: none;
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        input:focus {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }

        .login-btn {
            width: 100%;
            padding: 18px; /* Increased padding for button */
            background-color: #fff;
            color: #800000;
            border: none;
            border-radius: 8px;
            font-size: 18px; /* Larger font */
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            letter-spacing: 0.5px;
        }

        .login-btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            color: #ffcccc;
            font-size: 15px; /* Slightly larger font */
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            background-color: rgba(255, 0, 0, 0.2);
            border-radius: 5px;
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

            .login-container {
                margin-top: 140px; /* Adjusted for navbar height on mobile */
                padding: 40px; /* Slightly reduced padding for mobile */
                max-width: 450px; /* Slightly smaller for mobile */
            }

            h2 {
                font-size: 26px;
            }

            input {
                padding: 14px 16px;
                font-size: 16px;
            }

            .login-btn {
                padding: 16px;
                font-size: 17px;
            }

            .error-message {
                font-size: 14px;
                padding: 10px;
            }
        }
p{
    color:white;
}

    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login to IES</h2>
        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="" required>
            </div>
            <button type="submit" class="login-btn">LOGIN</button>
            <p class="register.php">
            don't have an account? 
                <a href="register.php">registers here</a>
            </p>
        </form>
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>