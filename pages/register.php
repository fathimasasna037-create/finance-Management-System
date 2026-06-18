<?php
include '../includes/database.php';
include '../includes/footer.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IES Campus</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background-image: url('../assets/images/ies.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
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

        .register-container {
            margin-top: 100px;
            width: 100%;
            max-width: 450px;
            background: rgba(128, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
        }

        h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #fff;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            color: #000;
            font-size: 15px;
        }

        input:focus, select:focus {
            outline: none;
            background-color: #fff;
        }

        .btn {
            width: 100%;
            background-color: #fff;
            color: #800000;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: #ffd2d2;
        }

        .login-text {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #fff;
        }

        .login-text a {
            color: #00ffff;
            text-decoration: underline;
        }

        #admin-code-group {
            display: none;
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

            .register-container {
                margin-top: 120px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <img src="/FinanceManagementSystem/assets/images/ies_campus_logo.jpg" alt="IES Campus Logo" class="logo">
            <span class="brand-text">IES Campus</span>
        </div>
        <div class="nav-links">
            <a href="/FinanceManagementSystem/index.php">Home</a>
            <a href="/FinanceManagementSystem/pages/about.php">About</a>
            <a href="/FinanceManagementSystem/pages/contact.php">Contact</a>
            <a href="/FinanceManagementSystem/pages/login.php">Login</a>
        </div>
    </nav>

    <div class="register-container">
        <h2>Create Your Account</h2>
        <form action="register-process.php" method="POST">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label for="password">Create Password</label>
                <input type="password" id="password" name="password" placeholder="" required>
            </div>

            <div class="form-group">
                <label for="role">Select Role</label>
                <select id="role" name="role" required>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-group" id="admin-code-group">
                <label for="admin_code">Admin Registration Code</label>
                <input type="text" id="admin_code" name="admin_code" placeholder="Enter admin code (e.g. iesa001)">
            </div>

            <button type="submit" class="btn">Register</button>

            <p class="login-text">
                Already have an account?
                <a href="login.php">Login here</a>
            </p>
        </form>
    </div>

    <script>
        // Show/Hide admin code field based on selected role
        document.getElementById('role').addEventListener('change', function () {
            const adminCodeGroup = document.getElementById('admin-code-group');
            if (this.value === 'admin') {
                adminCodeGroup.style.display = 'block';
                document.getElementById('admin_code').setAttribute('required', 'required');
            } else {
                adminCodeGroup.style.display = 'none';
                document.getElementById('admin_code').removeAttribute('required');
            }
        });
    </script>
</body>
</html>
