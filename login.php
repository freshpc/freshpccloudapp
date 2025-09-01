<?php
// login.php v5 | User login interface | est lines: ~120 | Author: franklos
session_start();

// If already logged in, redirect based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /admin');
        exit;
    } else {
        header('Location: /field-engineer');
        exit;
    }
}

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FreshPC Cloud</title>
    <link rel="icon" type="image/x-icon" href="<?php echo LOGIN_FAVICON; ?>">
    <style>
        <?php echo generateDynamicCSS(); ?>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--header-bg-color), var(--btn-primary));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: var(--background-color);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: var(--header-bg-color);
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--btn-primary);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: var(--btn-primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            color: var(--hover-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>FreshPC Cloud</h1>
            <p>Please sign in to continue</p>
        </div>
        
        <div id="alert" class="alert"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="identifier">Username or Email:</label>
                <input type="text" id="identifier" name="identifier" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        
        <div class="back-link">
            <a href="/">← Back to Home</a>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const identifier = document.getElementById('identifier').value;
            const password = document.getElementById('password').value;
            const alertDiv = document.getElementById('alert');
            
            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: identifier,
                        email: identifier,
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertDiv.className = 'alert alert-success';
                    alertDiv.style.display = 'block';
                    alertDiv.textContent = 'Login successful! Redirecting...';
                    
                    // Redirect based on role
                    setTimeout(() => {
                        if (data.role === 'admin') {
                            window.location.href = '/admin';
                        } else {
                            window.location.href = '/field-engineer';
                        }
                    }, 1000);
                } else {
                    throw new Error(data.error || 'Login failed');
                }
            } catch (error) {
                alertDiv.className = 'alert alert-error';
                alertDiv.style.display = 'block';
                alertDiv.textContent = error.message || 'An error occurred during login';
            }
        });
    </script>
</body>
</html>