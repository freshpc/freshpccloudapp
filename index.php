<?php
// index.php v3 | Main app entry point/router | est lines: ~50 | Author: franklos
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // User is logged in, redirect to appropriate dashboard
    if ($_SESSION['role'] === 'admin') {
        header('Location: /admin');
        exit;
    } else {
        header('Location: /field-engineer');
        exit;
    }
} else {
    // User is not logged in, show landing page (index.html)
    // Check if index.html exists
    if (file_exists('index.html')) {
        // Read and display index.html
        readfile('index.html');
        exit;
    } else {
        // Fallback if index.html is missing
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshPC Cloud - Smart Hands Services</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .container {
            max-width: 600px;
            padding: 2rem;
        }
        
        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background-color: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1.1rem;
            margin: 0 1rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #219a52;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .features {
            margin-top: 3rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .feature {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .feature h3 {
            margin-bottom: 1rem;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>FreshPC Cloud</h1>
        <p>Professional Smart Hands Services for International Customers in the Netherlands</p>
        
        <div>
            <a href="/login" class="btn">Staff Login</a>
            <a href="#services" class="btn btn-secondary">Our Services</a>
        </div>
        
        <div class="features" id="services">
            <div class="feature">
                <h3>⚡ 24/7 Availability</h3>
                <p>Round-the-clock technical support for your critical systems</p>
            </div>
            <div class="feature">
                <h3>🌍 Netherlands Coverage</h3>
                <p>Comprehensive coverage across the Netherlands</p>
            </div>
            <div class="feature">
                <h3>🔧 POS & Retail Systems</h3>
                <p>Expert maintenance and support for retail point-of-sale systems</p>
            </div>
            <div class="feature">
                <h3>🖥️ Computer Hardware</h3>
                <p>Professional hardware diagnostics, repair, and replacement services</p>
            </div>
            <div class="feature">
                <h3>🔍 Remote Troubleshooting</h3>
                <p>Advanced remote diagnostics and problem resolution</p>
            </div>
            <div class="feature">
                <h3>📊 Equipment Monitoring</h3>
                <p>Proactive monitoring and performance optimization</p>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
        exit;
    }
}
?>