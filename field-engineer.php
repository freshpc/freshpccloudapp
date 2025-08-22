<?php require_once 'config.php'; ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="FreshPC Cloud field engineer portal - Smart hands services for POS systems, computer hardware, servers, and IT equipment in the Netherlands. Professional technical support.">
  <meta name="keywords" content="smart hands, field engineer, POS systems, computer hardware, server maintenance, IT support, Netherlands, technical services">
  <meta name="robots" content="noindex, follow">
  <meta property="og:title" content="Field Engineer Portal - <?php echo COMPANY_NAME; ?>">
  <meta property="og:description" content="Professional smart hands services for POS systems, computer hardware, and IT equipment in the Netherlands">
  <meta property="og:type" content="website">
  <title>Field Engineer Portal – <?php echo COMPANY_NAME; ?> Smart Hands Services</title>
  <link rel="icon" type="image/x-icon" href="<?php echo FIELD_FAVICON; ?>">
  <link rel="stylesheet" href="/style.css">
  <?php echo generateDynamicCSS(); ?>
</head>
<body>
  <header class="engineer-header header">
    <div class="header-container">
      <div class="logo-section">
        <img src="<?php echo SITE_LOGO; ?>" alt="Logo" class="logo">
        <h1 class="site-title"><?php echo COMPANY_NAME; ?></h1>
        <span>Smart Hands Services</span>
      </div>
      <nav class="header-nav">
        <button class="nav-btn active" data-section="tasks">Mijn Taken</button>
        <button class="nav-btn" data-section="profile">Profiel</button>
        <button id="logoutBtn" class="btn btn-logout" type="button">Uitloggen</button>
      </nav>
    </div>
  </header>

  <main class="main-container">

    <section class="content-section" id="tasks-section">
      <div class="section-header">
        <h2>Mijn taken</h2>
        <div>
          <button id="refreshBtn" class="btn btn-secondary" type="button">Verversen</button>
        </div>
      </div>

      <div id="taskList" class="accordion"></div>

      <div class="section-header" style="margin-top:2rem;">
        <h2>Mijn historie</h2>
      </div>
      <div class="table-wrap">
        <table id="historyTable">
          <thead>
            <tr><th>Datum/tijd</th><th>Taak</th><th>Event/Status</th><th>Details</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <!-- Profile Section -->
    <section id="profile-section" class="content-section" style="display:none;">
      <div class="section-header">
        <h2>Mijn profiel</h2>
      </div>
      
      <form id="profileForm" class="task-form">
        <div class="form-row">
          <div class="form-group">
            <label for="profile_full_name">Volledige naam</label>
            <input id="profile_full_name" name="full_name" required>
          </div>
          <div class="form-group">
            <label for="profile_email">E-mail</label>
            <input id="profile_email" name="email" type="email" required>
          </div>
          <div class="form-group">
            <label for="profile_phone">Telefoon</label>
            <input id="profile_phone" name="phone">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label for="current_password">Huidig wachtwoord</label>
            <input id="current_password" name="current_password" type="password">
          </div>
          <div class="form-group">
            <label for="new_password">Nieuw wachtwoord</label>
            <input id="new_password" name="new_password" type="password">
          </div>
        </div>
        
        <div class="form-buttons">
          <button type="submit" class="btn btn-primary">Profiel bijwerken</button>
          <span id="profileMsg" class="error"></span>
        </div>
      </form>
    </section>
  </main>

  <script src="/assets/engineers2.js" defer></script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=JOUW_ECHTE_API_KEY"></script>
</body>
</html>
