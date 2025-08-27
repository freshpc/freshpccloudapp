<?php

require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}
?>

<!-- Your full HTML dashboard follows here -->
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Login to <?= COMPANY_NAME ?> - Professional smart hands services for POS systems, computer hardware, and IT equipment in the Netherlands">
  <meta name="robots" content="noindex, follow">
  <title>Login – <?= COMPANY_NAME ?> Smart Hands Services</title>
  <link rel="icon" type="image/x-icon" href="<?= LOGIN_FAVICON ?>">
  <link rel="stylesheet" href="/style.css">
  <?= generateDynamicCSS() ?>
</head>
<body>
  <nav class="navbar header">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="<?= SITE_LOGO ?>" alt="Logo" class="logo">
        <h2 class="site-title"><?= COMPANY_NAME ?></h2>
        <span>Field Engineering</span>
      </div>
      <div class="nav-menu"><a class="nav-link" href="/">Home</a></div>
    </div>
  </nav>

  <main class="login-main">
    <div class="login-form-container">
      <div class="login-header">
        <img src="<?= SITE_LOGO ?>" alt="Logo" class="logo">
        <h2>Inloggen</h2>
        <p>Toegang tot <?= COMPANY_NAME ?> Smart Hands platform</p>
      </div>
      <?php if ($error): ?><div class="error-message" style="margin-bottom:1em;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form id="loginForm" class="login-form" method="post">
        <div class="form-group">
          <label for="email">E-mailadres</label>
          <input type="email" id="email" name="email" required autocomplete="email" placeholder="Voer uw e-mailadres in">
        </div>
        <div class="form-group">
          <label for="password">Wachtwoord</label>
          <div class="password-input-group">
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="button" id="togglePw" class="btn-password-toggle">Toon</button>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-block">Inloggen</button>
        </div>
        <div class="form-footer">
          <a href="/password-help" class="link-secondary">Wachtwoord vergeten?</a>
        </div>
      </form>
    </div>
  </main>
  <script src="/assets/login.js"></script>
</body>
</html>