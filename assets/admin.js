// admin.js v23 | Full user/client add/edit/delete, AJAX forms, button logic | est lines: ~245 | Author: franklos

(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);
  const $$ = (s, ctx)=> Array.from((ctx||document).querySelectorAll(s));
  const state = { clients: [], users: [] };

  document.addEventListener('DOMContentLoaded', function() {
    const navButtons = $$('.admin-nav .nav-btn');
    const sections = $$('.content-section');
    navButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        navButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        sections.forEach(sec => sec.style.display = 'none');
        const sectionId = btn.getAttribute('data-section');
        const section = $('#' + sectionId + '-section');
        if (section) section.style.display = 'block';
        if ($('#editClientForm')) $('#editClientForm').style.display = 'none';
        if ($('#userForm')) $('#userForm').style.display = 'none';
        if (sectionId === 'tasks') loadTasks();
        if (sectionId === 'clients') loadClients();
        if (sectionId === 'users') loadUsers();
      });
    });
  });

  async function api(path, init){
    init = init || {};
    init.credentials = 'include';
    const r = await fetch(path, init);
    let body = null; try { body = await r.json(); } catch(e){}
    if(!r.ok){ const msg = (body && (body.error||body.message)) || r.statusText || 'API error'; throw new Error(msg); }
    return body;
  }

  // --- Clients ---
  async function loadClients(){
    const rows = await api('/api/clients');
    state.clients = rows;
    let sel = $('#client_id');
    if (sel) {
      sel.innerHTML = '<option value="">— kies klant —</option>';
      for(const c of rows) {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name || 'Klant #' + c.id;
        sel.appendChild(opt);
      }
    }
    const table = $('#clientsTable');
    if(table){
      const tbody = table.tBodies[0] || table.createTBody();
      tbody.innerHTML = '';
      for(const c of rows){
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${c.id}</td>
          <td>${c.name||''}</td>
          <td>${c.email||''}</td>
          <td>${c.phone||''}</td>
          <td>${c.address_city||''} ${c.address_postcode||''}</td>
          <td>${c.created_at||''}</td>
          <td>
            <button type="button" class="btn btn-sm edit-client-btn" data-id="${c.id}">Bewerken</button>
            <button type="button" class="btn btn-sm btn-danger delete-client-btn" data-id="${c.id}">Verwijderen</button>
          </td>
        `;
        tbody.appendChild(row);
      }
      $$('.edit-client-btn', tbody).forEach(btn => {
        btn.onclick = function() {
          editClient(btn.dataset.id);
        };
      });
      $$('.delete-client-btn', tbody).forEach(btn => {
        btn.onclick = function() {
          deleteClient(btn.dataset.id);
        };
      });
    }
    if ($('#editClientForm')) $('#editClientForm').style.display = 'none';
  }

  // --- Add Client button ---
  const addClientBtn = $('#addClientBtn');
  const editClientForm = $('#editClientForm');
  if(addClientBtn && editClientForm){
    addClientBtn.addEventListener('click', function(){
      editClientForm.style.display = 'block';
      editClientForm.reset();
      $('#clientFormTitle').textContent = 'Klant toevoegen';
      $('#editClientId').value = '';
      $('#editClientMsg').textContent = '';
    });
  }

  // --- Edit Client ---
  window.editClient = function(id){
    const client = state.clients.find(c => c.id == id);
    if (!client) return;
    editClientForm.style.display = 'block';
    editClientForm.reset();
    $('#clientFormTitle').textContent = 'Klant bewerken';
    $('#editClientId').value = client.id;
    $('#editClientName').value = client.name || '';
    $('#editClientEmail').value = client.email || '';
    $('#editClientPhone').value = client.phone || '';
    $('#editClientCity').value = client.address_city || '';
    $('#editClientPostcode').value = client.address_postcode || '';
    $('#editClientMsg').textContent = '';
  };

  // --- Delete Client ---
  window.deleteClient = async function(id){
    if (!confirm('Weet je zeker dat je deze klant wilt verwijderen?')) return;
    try {
      await api(`/api/clients/${id}`, { method:'DELETE' });
      alert('Klant verwijderd.');
      await loadClients();
    } catch(err) {
      alert('Fout bij verwijderen: ' + err.message);
    }
  };

  // --- Save/Add Client ---
  editClientForm?.addEventListener('submit', async function(e){
    e.preventDefault();
    const id = $('#editClientId').value;
    const payload = {
      name: $('#editClientName').value,
      email: $('#editClientEmail').value,
      phone: $('#editClientPhone').value,
      address_city: $('#editClientCity').value,
      address_postcode: $('#editClientPostcode').value
    };
    try{
      if(id) {
        await api(`/api/clients/${id}`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        $('#editClientMsg').textContent = 'Klant opgeslagen!';
      } else {
        await api('/api/clients', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        $('#editClientMsg').textContent = 'Klant toegevoegd!';
      }
      editClientForm.style.display = 'none';
      await loadClients();
    }catch(err){
      $('#editClientMsg').textContent = err.message;
    }
  });
  $('#cancelEditClientBtn')?.addEventListener('click', function(){
    editClientForm.style.display = 'none';
  });

  // --- Users ---
  async function loadUsers(){
    const users = await api('/api/users');
    state.users = users;
    populateEngineerDropdown(users);
    const table = $('#usersTable');
    if(table){
      const tbody = table.tBodies[0] || table.createTBody();
      tbody.innerHTML = '';
      for(const u of users){
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${u.id}</td><td>${u.username||''}</td><td>${u.email||''}</td>
          <td>${u.full_name||u.first_name||''} ${u.last_name||''}</td><td>${u.role||''}</td>
          <td>${u.activated == 1 ? 'Ja' : 'Nee'}</td>
          <td>
            <button class="btn btn-sm edit-user-btn" data-id="${u.id}">Bewerken</button>
            <button class="btn btn-sm btn-danger delete-user-btn" data-id="${u.id}">Verwijderen</button>
          </td>
        `;
        tbody.appendChild(row);
      }
      $$('.edit-user-btn', tbody).forEach(btn => {
        btn.onclick = function() {
          editUser(btn.dataset.id);
        };
      });
      $$('.delete-user-btn', tbody).forEach(btn => {
        btn.onclick = function() {
          deleteUser(btn.dataset.id);
        };
      });
    }
  }

  // --- Add User Button ---
  const addUserBtn = $('#addUserBtn');
  const userForm = $('#userForm');
  if(addUserBtn && userForm){
    addUserBtn.addEventListener('click', function(){
      userForm.style.display = 'block';
      userForm.reset();
      $('#userFormTitle').textContent = 'Nieuwe gebruiker';
      $('#userFormUserId').value = '';
      $('#userMsg').textContent = '';
    });
  }

  // --- Edit User ---
  window.editUser = function(id){
    const user = state.users.find(u => u.id == id);
    if (!user) return;
    userForm.style.display = 'block';
    userForm.reset();
    $('#userFormTitle').textContent = 'Gebruiker bewerken';
    $('#userFormUserId').value = user.id;
    $('#userFormUsername').value = user.username || '';
    $('#userFormEmail').value = user.email || '';
    $('#userFormFullName').value = user.full_name || '';
    $('#userFormRole').value = user.role || '';
    $('#userMsg').textContent = '';
  };

  // --- Delete User ---
  window.deleteUser = async function(id){
    if (!confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')) return;
    try{
      await api(`/api/users/${id}`, { method:'DELETE' });
      alert('Gebruiker verwijderd.');
      await loadUsers();
    }catch(err){
      alert('Fout bij verwijderen: ' + err.message);
    }
  };

  // --- Save/Add User ---
  userForm?.addEventListener('submit', async function(e){
    e.preventDefault();
    const userId = $('#userFormUserId').value;
    const payload = {
      username: $('#userFormUsername').value,
      email: $('#userFormEmail').value,
      full_name: $('#userFormFullName').value,
      role: $('#userFormRole').value
    };
    try{
      if (userId) {
        await api(`/api/users/${userId}`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        $('#userMsg').textContent = 'Gebruiker opgeslagen!';
      } else {
        await api('/api/users', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        $('#userMsg').textContent = 'Gebruiker toegevoegd!';
      }
      userForm.style.display = 'none';
      await loadUsers();
    }catch(err){
      $('#userMsg').textContent = err.message;
    }
  });
  $('#cancelUserBtn')?.addEventListener('click', function(){
    userForm.style.display = 'none';
  });

  function populateEngineerDropdown(users){
    const sel = $('#assigned_user_id');
    if(!sel) return;
    sel.innerHTML = '<option value="">— kies engineer —</option>';
    users.filter(u =>
      u.role === 'field_engineer' && parseInt(u.activated) === 1
    ).forEach(u => {
      const opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = u.full_name || ((u.first_name || '') + ' ' + (u.last_name || '')).trim() || u.username;
      sel.appendChild(opt);
    });
  }

  async function loadTasks(){
    const rows = await api('/api/tasks');
    const table = $('#tasksTable');
    if(table){
      const tbody = table.tBodies[0] || table.createTBody();
      tbody.innerHTML = '';
      for(const t of rows){
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${t.id}</td><td>${t.title||''}</td><td>${t.client_name||''}</td>
          <td>${t.assigned_to_name||''}</td>
          <td>${t.priority||''}</td><td>${t.scheduled_date||''}</td>
          <td>${t.full_address||''}</td>
        `;
        tbody.appendChild(row);
      }
    }
  }

  const generateReportBtn = $('#generateReportBtn');
  if(generateReportBtn){
    generateReportBtn.addEventListener('click', async function(){
      const type = $('#reportType').value;
      const dateFrom = $('#reportDate').value;
      const resultDiv = $('#reportResult');
      try{
        const response = await api('/api/reports/generate', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({type:type, date_from:dateFrom}) });
        resultDiv.innerHTML = `<div class="success-message">Rapport succesvol gegenereerd! <a href="${response.path}" target="_blank">Download PDF</a></div>`;
      }catch(err){
        resultDiv.innerHTML = `<div class="error-message">Fout bij genereren rapport: ${err.message}</div>`;
      }
    });
  }

  const exportHistoryPdfBtn = $('#exportHistoryPdfBtn');
  if(exportHistoryPdfBtn){
    exportHistoryPdfBtn.addEventListener('click', async function(){
      exportHistoryPdfBtn.disabled = true;
      exportHistoryPdfBtn.textContent = 'PDF maken...';
      try{
        const result = await api('/api/history/pdf', { method:'POST' });
        if(result && result.path){
          window.open(result.path, '_blank');
        }
      }catch(err){
        alert('PDF export mislukt: ' + (err.message || err));
      }finally{
        exportHistoryPdfBtn.disabled = false;
        exportHistoryPdfBtn.textContent = 'Exporteer Historie als PDF';
      }
    });
  }

  (async function(){
    $$('.content-section').forEach(sec => sec.style.display = 'none');
    const defaultSection = $('#tasks-section');
    if (defaultSection) defaultSection.style.display = 'block';
    await Promise.all([loadClients(), loadUsers(), loadTasks()]);
  })();
})();