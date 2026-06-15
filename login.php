<?php
session_start();
require_once 'config.php';

// Check if user already has a valid remember me cookie
if (!isset($_SESSION['username']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Find user with this token
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Auto-login the user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        // Invalid token, remove the cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

// If already logged in, redirect to index
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']) ? true : false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Handle Remember Me functionality
                if ($remember) {
                    // Generate a secure random token
                    $token = bin2hex(random_bytes(32));
                    
                    // Store token in database
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                    
                    // Set cookie for 30 days
                    setcookie(
                        'remember_token',
                        $token,
                        time() + (30 * 24 * 60 * 60), // 30 days
                        '/',
                        '',
                        true,  // Secure - only HTTPS
                        true   // HttpOnly - no JS access
                    );
                }
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $error = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        body {
    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 400% 400%;
    animation: gradient 15s ease infinite;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: white;
    position: relative;
    overflow-x: hidden; /* ✅ Allow vertical scrolling */
}


        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
            pointer-events: none;
            opacity: 0.3;
        }

        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            animation: float 6s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            left: 10%;
            top: 20%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 60px;
            height: 60px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            right: 15%;
            top: 30%;
            animation-delay: 1s;
        }

        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%;
            left: 20%;
            bottom: 20%;
            animation-delay: 2s;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px) saturate(200%);
            -webkit-backdrop-filter: blur(20px) saturate(200%);
            border-radius: 2.5em;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 
                0 8px 32px 0 rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.3),
                inset 0 -1px 0 rgba(0, 0, 0, 0.1);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
            animation: slideIn 0.6s ease-out;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 180deg at 50% 50%, rgba(255, 255, 255, 0.1) 0deg, transparent 360deg);
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }

        .auth-header {
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .auth-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.5px;
            animation: slideIn 0.7s ease-out 0.1s both;
        }

        .auth-title::before {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            border-radius: 2px;
        }

        .auth-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 17px;
            font-weight: 400;
            margin-top: 20px;
            animation: slideIn 0.7s ease-out 0.2s both;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
            position: relative;
            z-index: 1;
            animation: slideIn 0.7s ease-out 0.3s both;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: white;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 15px;
            padding: 0 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.1);
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 
                0 0 0 4px rgba(255, 255, 255, 0.1),
                0 4px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
            animation: slideIn 0.7s ease-out 0.4s both;
        }

        .remember-checkbox {
            width: 22px;
            height: 22px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
        }

        .remember-checkbox:hover {
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
        }

        .remember-checkbox:checked {
            background: linear-gradient(135deg, #23d5ab 0%, #23a6d5 100%);
            border-color: transparent;
            transform: scale(1.1);
        }

        .remember-checkbox:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 16px;
            font-weight: bold;
        }

        .remember-label {
            color: white;
            cursor: pointer;
            user-select: none;
            font-size: 15px;
            font-weight: 500;
        }

        .auth-btn {
            width: 100%;
            height: 55px;
            background: linear-gradient(135deg, #23d5ab 0%, #23a6d5 100%);
            border: none;
            border-radius: 15px;
            color: white;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
            box-shadow: 0 6px 25px rgba(35, 213, 171, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            overflow: hidden;
            animation: slideIn 0.7s ease-out 0.5s both;
        }

        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .auth-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 35px rgba(35, 213, 171, 0.5);
        }

        .auth-btn:hover::before {
            left: 100%;
        }

        .auth-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 5px 20px rgba(35, 213, 171, 0.4);
        }

        .auth-link {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            font-weight: 600;
            font-size: 15px;
            animation: slideIn 0.7s ease-out 0.7s both;
        }

        .auth-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #23d5ab, #23a6d5);
            transition: width 0.3s ease;
        }

        .auth-link:hover::after {
            width: 100%;
        }

        .error-message {
            background: rgba(255, 65, 108, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 65, 108, 0.3);
            color: #ff8fa3;
            padding: 18px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 15px;
            font-weight: 500;
            position: relative;
            z-index: 1;
            animation: slideIn 0.5s ease-out;
        }

        .error-message::before {
            content: '⚠️';
            margin-right: 8px;
        }

        .gif-container {
            margin: 30px 0;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideIn 0.7s ease-out 0.6s both;
        }

        .gif-container img {
            max-width: 100%;
            height: 140px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .gif-container img:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 40px 30px;
            }
            
            .auth-title {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="auth-container">
        <div class="auth-header">
            <h1 class="auth-title">📅 Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your Todo account</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                       placeholder="Enter your email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="Enter your password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember_me" name="remember_me" class="remember-checkbox">
                <label for="remember_me" class="remember-label">Remember me for 30 days</label>
            </div>

            <button type="submit" name="login" class="auth-btn">Sign In</button>
        </form>

        <div class="gif-container">
            <img src="https://mir-s3-cdn-cf.behance.net/project_modules/max_632/cbd49383364993.5d39d09977c9f.gif" 
                 alt="Welcome GIF">
        </div>

        <a href="register.php" class="auth-link">Create a new account</a>
    </div>
</body>
</html>