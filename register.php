<?php
require_once 'config.php';
// register.php
if (basename($_SERVER['PHP_SELF']) == 'register.php') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Todo App - Register</title>
    <style>
        /* Same CSS as before */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background: linear-gradient(135deg, rgba(126,64,246,1) 0%, rgba(80,139,252,1) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: white;
        }
        .auth-container {
            background: rgba(24,24,16,0.2);
            backdrop-filter: blur(25px);
            border-radius: 2em;
            border: 2px solid rgba(255,255,255,0.05);
            background-clip: padding-box;
            box-shadow: 10px 10px 30px rgba(46,54,68,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .auth-header {
            margin-bottom: 30px;
        }
        .auth-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .auth-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
        }
        .form-input {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 25px;
            padding: 0 20px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.2);
        }
        .form-input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        .auth-btn {
            width: 100%;
            height: 50px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 25px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .auth-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .auth-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .auth-link:hover {
            color: white;
        }
        .error-message {
            background: rgba(220,53,69,0.2);
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-message {
            background: rgba(40,167,69,0.2);
            border: 1px solid #28a745;
            color: #51cf66;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .divider {
            margin: 30px 0;
            position: relative;
            text-align: center;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255,255,255,0.2);
        }
        .divider span {
            background: rgba(24,24,16,0.2);
            padding: 0 20px;
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-header">
        <h1 class="auth-title">📅 Join Todo App</h1>
        <p class="auth-subtitle">Create your account to get started</p>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (strlen($username) < 3) {
            $error = "Username must be at least 3 characters long";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email address is already registered";
                } else {
                    // Handle avatar upload
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['avatar'];
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                        if (!in_array($ext, $allowed)) {
                            $error = "Invalid image format for profile picture. Allowed: jpg, jpeg, png, gif.";
                        } elseif ($file['size'] > 2 * 1024 * 1024) {
                            $error = "Profile picture must be smaller than 2MB.";
                        } else {
                            $uploadDir = 'uploads/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            $avatarFileName = uniqid('avatar_', true) . '.' . $ext;
                            $uploadFilePath = $uploadDir . $avatarFileName;

                            if (!move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
                                $error = "Failed to upload profile picture.";
                            }
                        }
                    } else {
                        $error = "Please upload a profile picture.";
                    }
                }

                // Insert user if no errors
                if (!isset($error)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, avatar) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $avatarFileName]);

                    $success = "Account created successfully! You can now sign in.";
                }
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again.";
            }
        }
    }
    ?>

    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-input"
                placeholder="Choose a username"
                required
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
            />
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input"
                placeholder="Enter your email"
                required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            />
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-input"
                placeholder="Create a password"
                required
            />
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                class="form-input"
                placeholder="Confirm your password"
                required
            />
        </div>

        <div class="form-group">
            <label class="form-label" for="avatar">Profile Picture</label>
            <input
                type="file"
                id="avatar"
                name="avatar"
                class="form-input"
                accept="image/*"
                required
            />
        </div>

        <button type="submit" name="register" class="auth-btn">Create Account</button>
    </form>

    <div class="divider">
        <span>Already have an account?</span>
    </div>

    <a href="login.php" class="auth-link">Sign in instead</a>
</div>
</body>
</html>
<?php } ?>