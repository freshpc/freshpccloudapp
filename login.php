<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Login to <?php echo COMPANY_NAME; ?> - Professional smart hands services for POS systems, computer hardware, and IT equipment in the Netherlands">
  <meta name="keywords" content="login, smart hands, POS systems, computer hardware, IT support, Netherlands, technical services">
  <meta name="robots" content="noindex, follow">
  <meta property="og:title" content="Login - <?php echo COMPANY_NAME; ?> Smart Hands & IT Services">
  <meta property="og:description" content="Access your <?php echo COMPANY_NAME; ?> account for smart hands and IT services in the Netherlands">
  <meta property="og:type" content="website">
  <title>Login – <?php echo COMPANY_NAME; ?> Smart Hands Services</title>
  <link rel="icon" type="image/x-icon" href="<?php echo LOGIN_FAVICON; ?>">
  <link rel="stylesheet" href="/style.css">
  <?php echo generateDynamicCSS(); ?>
</head>
<body>
  <nav class="navbar header">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="<?php echo SITE_LOGO; ?>" alt="Logo" class="logo">
        <h2 class="site-title"><?php echo COMPANY_NAME; ?></h2>
        <span>Field Engineering</span>
      </div>
      <div class="nav-menu">
        <a class="nav-link" href="/">Home</a>
      </div>
    </div>
  </nav>

  <main class="login-main">
    <div class="login-form-container">
      <div class="login-header">
        <img src="<?php echo SITE_LOGO; ?>" alt="Logo" class="logo">
        <h2>Inloggen</h2>
        <p>Toegang tot <?php echo COMPANY_NAME; ?> Smart Hands platform</p>
      </div>

      <form id="loginForm" class="login-form">
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

      <div id="loginError" class="error-message" style="display: none;"></div>
    </div>
  </main>

  <script src="/assets/login.js"></script>
</body>
</html>