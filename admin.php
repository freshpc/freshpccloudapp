<?php require_once 'config.php'; ?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin – <?php echo COMPANY_NAME; ?></title>
  <link rel="icon" type="image/x-icon" href="<?php echo ADMIN_FAVICON; ?>">
  <link rel="stylesheet" href="/style.css">
  <?php echo generateDynamicCSS(); ?>
</head>
<body>
  <nav class="navbar header">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="<?php echo SITE_LOGO; ?>" alt="Logo" class="logo">
        <h2 class="site-title"><?php echo COMPANY_NAME; ?></h2>
        <span>Admin</span>
      </div>
      <div class="nav-menu">
        <a class="nav-link" href="/">Home</a>
        <a class="nav-link" href="/engineer">Engineer</a>
        <a class="nav-link" href="/logout">Uitloggen</a>
      </div>
    </div>
  </nav>

  <main class="container admin-container">
    <nav class="admin-nav">
      <button class="nav-btn active" data-section="tasks">Taken</button>
      <button class="nav-btn" data-section="clients">Klanten</button>
      <button class="nav-btn" data-section="history">Historie</button>
      <button class="nav-btn" data-section="users">Gebruikers</button>
      <button class="nav-btn" data-section="reports">Rapporten</button>
    </nav>

    <!-- Tasks Section -->
    <section id="tasks-section" class="content-section">
      <div class="section-header">
        <h2>Nieuwe taak</h2>
      </div>
      <form id="taskForm" class="task-form">
        <div class="form-row">
          <div class="form-group">
            <label for="title">Titel</label>
            <input id="title" name="title" required>
          </div>
          <div class="form-group">
            <label for="client_id">Klant</label>
            <select id="client_id" name="client_id" required></select>
          </div>
          <div class="form-group">
            <label for="assigned_user_id">Engineer</label>
            <select id="assigned_user_id" name="assigned_user_id" required></select>
          </div>
          <div class="form-group">
            <label for="priority">Prioriteit</label>
            <select id="priority" name="priority">
              <option value="low">Laag</option>
              <option value="medium" selected>Middel</option>
              <option value="high">Hoog</option>
            </select>
          </div>
          <div class="form-group">
            <label for="scheduled_date">Gepland (DD-MM-YYYY HH:MM)</label>
            <input id="scheduled_date" name="scheduled_date" placeholder="20-08-2025 10:00:00">
          </div>
        </div>
        <div class="form-group">
          <label for="description">Omschrijving</label>
          <textarea id="description" name="description"></textarea>
        </div>
        <div class="form-buttons">
          <button type="submit" class="btn btn-primary">Opslaan</button>
          <span id="taskMsg" class="error"></span>
        </div>
      </form>
    </section>

    <section class="content-section" style="margin-top:2rem;">
      <div class="section-header">
        <h2>Taken</h2>
      </div>
      <div class="table-wrap">
        <table id="tasksTable">
          <thead><tr>
            <th>ID</th><th>Titel</th><th>Klant</th><th>Engineer</th><th>Prioriteit</th><th>Gepland</th><th>Adres</th>
          </tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <!-- Clients Section -->
    <section id="clients-section" class="content-section" style="margin-top:2rem; display:none;">
      <div class="section-header">
        <h2>Klanten beheren</h2>
      </div>
      <div class="table-wrap">
        <table id="clientsTable">
          <thead>
            <tr><th>ID</th><th>Naam</th><th>E-mail</th><th>Telefoon</th><th>Plaats</th><th>Aangemaakt</th><th>Acties</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <!-- User Management Section -->
    <section id="users-section" class="content-section" style="margin-top:2rem; display:none;">
      <div class="section-header">
        <h2>Gebruikers beheren</h2>
        <button id="addUserBtn" class="btn btn-primary">Nieuwe gebruiker</button>
      </div>
      
      <div class="table-wrap">
        <table id="usersTable">
          <thead>
            <tr><th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Naam</th><th>Rol</th><th>Geactiveerd</th><th>Acties</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <form id="userForm" class="task-form" style="display:none; margin-top:2rem;">
        <h3 id="userFormTitle">Nieuwe gebruiker</h3>
        <div class="form-row">
          <div class="form-group"><label for="uusername">Gebruikersnaam</label><input id="uusername" name="username" required></div>
          <div class="form-group"><label for="uemail">E-mail</label><input id="uemail" name="email" type="email" required></div>
          <div class="form-group"><label for="ufullname">Volledige naam</label><input id="ufullname" name="full_name" required></div>
          <div class="form-group">
            <label for="urole">Rol</label>
            <select id="urole" name="role" required>
              <option value="field_engineer">Field Engineer</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
        </div>
        <div class="form-buttons">
          <button type="submit" class="btn btn-primary">Gebruiker opslaan</button>
          <button type="button" id="cancelUserBtn" class="btn btn-secondary">Annuleren</button>
          <span id="userMsg" class="error"></span>
        </div>
      </form>
    </section>

    <!-- History Section -->
    <section id="history-section" class="content-section" style="margin-top:2rem; display:none;">
      <div class="section-header">
        <h2>Historie</h2>
      </div>
      <div id="historyTasks">
        <p>Laden...</p>
      </div>
    </section>

    <!-- Reports Section -->
    <section id="reports-section" class="content-section" style="margin-top:2rem; display:none;">
      <div class="section-header">
        <h2>Rapporten</h2>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="reportType">Rapport type</label>
          <select id="reportType">
            <option value="completed">Afgeronde taken</option>
            <option value="rejected">Afgewezen taken</option>
            <option value="all_finished">Alle afgehandelde taken</option>
          </select>
        </div>
        <div class="form-group">
          <label for="reportDate">Datum vanaf</label>
          <input id="reportDate" type="date">
        </div>
        <div class="form-group">
          <button id="generateReportBtn" class="btn btn-primary">Genereer PDF Rapport</button>
        </div>
      </div>
      
      <div id="reportResult" style="margin-top:2rem;"></div>
    </section>


  </main>

  <!-- Belangrijk: session.js vóór admin.js, anders dropdowns leeg -->
  <script src="/assets/session.js" defer></script>
  <script src="/assets/admin.js" defer></script>
</body>
</html>
