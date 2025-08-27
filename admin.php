<?php
// admin.php v23 | Full user/client add/edit/delete, AJAX forms | est lines: ~275 | Author: franklos

<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}


?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>Admin – <?php echo COMPANY_NAME; ?></title>
  <link rel="icon" type="image/x-icon" href="<?php echo ADMIN_FAVICON; ?>">
  <link rel="stylesheet" href="/style.css">
  <?php echo generateDynamicCSS(); ?>
</head>
<body style="display:none;">
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
      <div class="table-wrap">
        <table id="tasksTable">
          <thead>
            <tr>
              <th>ID</th><th>Titel</th><th>Klant</th><th>Engineer</th>
              <th>Prioriteit</th><th>Gepland</th><th>Adres</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <!-- Clients Section -->
    <section id="clients-section" class="content-section" style="margin-top:2rem; display:none;">
      <div class="section-header">
        <h2>Klanten beheren</h2>
        <button id="addClientBtn" class="btn btn-primary">Klant toevoegen</button>
      </div>
      <div class="table-wrap">
        <table id="clientsTable">
          <thead>
            <tr>
              <th>ID</th><th>Naam</th><th>E-mail</th><th>Telefoon</th>
              <th>Plaats</th><th>Aangemaakt</th><th>Acties</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <!-- Add/Edit Client Form -->
      <form id="editClientForm" style="display:none; margin-top:1em; background:#f9f9f9; padding:1em; border-radius:6px; max-width:400px;">
        <h3 id="clientFormTitle">Klant toevoegen</h3>
        <input type="hidden" id="editClientId" name="client_id">
        <div>
          <label for="editClientName">Naam</label>
          <input type="text" id="editClientName" name="name" required>
        </div>
        <div>
          <label for="editClientEmail">E-mail</label>
          <input type="email" id="editClientEmail" name="email">
        </div>
        <div>
          <label for="editClientPhone">Telefoon</label>
          <input type="text" id="editClientPhone" name="phone">
        </div>
        <div>
          <label for="editClientCity">Plaats</label>
          <input type="text" id="editClientCity" name="address_city">
        </div>
        <div>
          <label for="editClientPostcode">Postcode</label>
          <input type="text" id="editClientPostcode" name="address_postcode">
        </div>
        <div id="editClientMsg" style="color:#d00;margin-top:0.5em;"></div>
        <button type="submit" class="btn btn-primary">Opslaan</button>
        <button type="button" id="cancelEditClientBtn" class="btn">Annuleren</button>
      </form>
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
            <tr>
              <th>ID</th><th>Gebruikersnaam</th><th>E-mail</th><th>Naam</th>
              <th>Rol</th><th>Geactiveerd</th><th>Acties</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <!-- Add/Edit User Form -->
      <form id="userForm" style="display:none; margin-top:1em; background:#f9f9f9; padding:1em; border-radius:6px; max-width:400px;">
        <h3 id="userFormTitle">Nieuwe gebruiker</h3>
        <input type="hidden" id="userFormUserId" name="user_id">
        <div>
          <label for="userFormUsername">Gebruikersnaam</label>
          <input type="text" id="userFormUsername" name="username" required>
        </div>
        <div>
          <label for="userFormEmail">E-mail</label>
          <input type="email" id="userFormEmail" name="email" required>
        </div>
        <div>
          <label for="userFormFullName">Volledige naam</label>
          <input type="text" id="userFormFullName" name="full_name">
        </div>
        <div>
          <label for="userFormRole">Rol</label>
          <select id="userFormRole" name="role">
            <option value="admin">Admin</option>
            <option value="field_engineer">Field Engineer</option>
          </select>
        </div>
        <div id="userMsg" style="color:#d00;margin-top:0.5em;"></div>
        <button type="submit" class="btn btn-primary">Opslaan</button>
        <button type="button" id="cancelUserBtn" class="btn">Annuleren</button>
      </form>
    </section>

    <!-- History Section -->
    <section id="history-section" class="content-section" style="margin-top:2rem; display:none;">
      <div class="section-header">
        <h2>Historie</h2>
        <button id="exportHistoryPdfBtn" class="btn btn-primary">Exporteer Historie als PDF</button>
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
  <script src="/assets/session.js" defer></script>
  <script src="/assets/admin.js" defer></script>
  <script>
    window.addEventListener('DOMContentLoaded', function() {
      if (document.body.style.display === 'none') document.body.style.display = '';
    });

    // v23: All menu buttons working, history loads via AJAX, forms toggle
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.nav-btn[data-section]').forEach(btn => {
        btn.addEventListener('click', function() {
          document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          document.querySelectorAll('.content-section').forEach(sec => sec.style.display = 'none');
          var sectionId = btn.dataset.section + '-section';
          var section = document.getElementById(sectionId);
          if (section) section.style.display = '';
          if (document.getElementById('editClientForm')) document.getElementById('editClientForm').style.display = 'none';
          if (document.getElementById('userForm')) document.getElementById('userForm').style.display = 'none';
          if (btn.dataset.section === 'history') {
            document.getElementById('historyTasks').innerHTML = "<p>Laden...</p>";
            fetch('/api/history/pdf.php')
              .then(response => response.text())
              .then(html => {
                document.getElementById('historyTasks').innerHTML = html;
              })
              .catch(err => {
                document.getElementById('historyTasks').innerHTML = "<p>Fout bij laden van historie.</p>";
              });
          }
        });
      });

      var firstSection = document.querySelector('.nav-btn[data-section]');
      if (firstSection) firstSection.click();
    });
  </script>
</body>
</html>
</html>