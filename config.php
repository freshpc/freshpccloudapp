<?php
// config.php v5 | est lines: ~75
// FreshPC Cloud Configuration - Production MySQL/ISPConfig

// Production MySQL configuration for ISPConfig
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'c2freshdb');
define('DB_USER', 'c2freshdbu');
define('DB_PASS', 'rn@iC3HS');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- Mail Configuration ---
define('MAIL_FROM', 'noreply@freshpccloud.nl');
define('MAIL_FROM_NAME', 'FreshPC Cloud');
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 587);

// --- App Configuration ---
define('APP_URL', 'https://freshpccloud.nl');
define('UPLOAD_DIR', './uploads');
define('SESSION_TIMEOUT', 3600);
define('ENVIRONMENT', 'production');
define('DEBUG', false);

// --- Company Branding ---
define('COMPANY_NAME', 'FreshPC Cloud');
define('COMPANY_ADDRESS', 'Professional Smart Hands Services, Netherlands');
define('COMPANY_PHONE', '+31 20 123 4567');
define('COMPANY_EMAIL', 'info@freshpccloud.nl');
define('COMPANY_WEBSITE', 'https://freshpccloud.nl');

// --- Google Maps API Key ---
define('GOOGLE_MAPS_API_KEY','AIzaSyA8jv75JyboPw1fz9zjAwB9IZTzepTLa7A');

// --- Logo and Icon Configuration ---
define('SITE_LOGO', './assets/freshpc-logo.svg');
define('ADMIN_FAVICON', './admin-favicon.ico');
define('FIELD_FAVICON', './field-favicon.ico');
define('LOGIN_FAVICON', './favicon.ico');

// --- Color Configuration ---
define('BACKGROUND_COLOR', '#ffffff');
define('TEXT_COLOR', '#333333');
define('HEADER_BG_COLOR', '#2c3e50');
define('HEADER_TEXT_COLOR', '#ffffff');
define('HOVER_COLOR', '#3498db');
define('FONT_WEIGHT', '400');

// --- Button Color Classes ---
define('BTN_PRIMARY_COLOR', '#3498db');
define('BTN_SUCCESS_COLOR', '#27ae60');
define('BTN_WARNING_COLOR', '#f39c12');
define('BTN_DANGER_COLOR', '#e74c3c');
define('BTN_INFO_COLOR', '#17a2b8');
define('BTN_SECONDARY_COLOR', '#6c757d');

// --- Dynamic CSS Generator ---
function generateDynamicCSS() {
    return "
    <style>
    :root {
        --bg-color: " . BACKGROUND_COLOR . ";
        --text-color: " . TEXT_COLOR . ";
        --header-bg-color: " . HEADER_BG_COLOR . ";
        --header-text-color: " . HEADER_TEXT_COLOR . ";
        --hover-color: " . HOVER_COLOR . ";
        --font-weight: " . FONT_WEIGHT . ";
        --btn-primary: " . BTN_PRIMARY_COLOR . ";
        --btn-success: " . BTN_SUCCESS_COLOR . ";
        --btn-warning: " . BTN_WARNING_COLOR . ";
        --btn-danger: " . BTN_DANGER_COLOR . ";
        --btn-info: " . BTN_INFO_COLOR . ";
        --btn-secondary: " . BTN_SECONDARY_COLOR . ";
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        font-weight: var(--font-weight);
    }
    .header {
        background-color: var(--header-bg-color) !important;
        color: var(--header-text-color) !important;
    }
    .header h1, .header h2, .header h3 {
        color: var(--header-text-color) !important;
    }
    .logo {
        height: 32px;
        margin-right: 10px;
        vertical-align: middle;
    }
    .site-title {
        display: inline-block;
        vertical-align: middle;
        margin: 0;
    }
    a:hover {
        color: var(--hover-color) !important;
    }
    .btn-primary {
        background-color: var(--btn-primary);
        border-color: var(--btn-primary);
        color: white;
    }
    .btn-primary:hover {
        background-color: color-mix(in srgb, var(--btn-primary) 85%, black);
        border-color: color-mix(in srgb, var(--btn-primary) 85%, black);
    }
    .btn-success {
        background-color: var(--btn-success);
        border-color: var(--btn-success);
        color: white;
    }
    .btn-success:hover {
        background-color: color-mix(in srgb, var(--btn-success) 85%, black);
        border-color: color-mix(in srgb, var(--btn-success) 85%, black);
    }
    .btn-warning {
        background-color: var(--btn-warning);
        border-color: var(--btn-warning);
        color: white;
    }
    .btn-warning:hover {
        background-color: color-mix(in srgb, var(--btn-warning) 85%, black);
        border-color: color-mix(in srgb, var(--btn-warning) 85%, black);
    }
    .btn-danger {
        background-color: var(--btn-danger);
        border-color: var(--btn-danger);
        color: white;
    }
    .btn-danger:hover {
        background-color: color-mix(in srgb, var(--btn-danger) 85%, black);
        border-color: color-mix(in srgb, var(--btn-danger) 85%, black);
    }
    .btn-info {
        background-color: var(--btn-info);
        border-color: var(--btn-info);
        color: white;
    }
    .btn-info:hover {
        background-color: color-mix(in srgb, var(--btn-info) 85%, black);
        border-color: color-mix(in srgb, var(--btn-info) 85%, black);
    }
    .btn-secondary {
        background-color: var(--btn-secondary);
        border-color: var(--btn-secondary);
        color: white;
    }
    .btn-secondary:hover {
        background-color: color-mix(in srgb, var(--btn-secondary) 85%, black);
        border-color: color-mix(in srgb, var(--btn-secondary) 85%, black);
    }
    </style>
    ";
}