// admin.js v7 — Engineers beheren (bewerken, wachtwoord resetten, verwijderen) + bestaande taken/klant edits
(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);
  const $$ = (s, ctx)=> Array.from((ctx||document).querySelectorAll(s));
  const state = { clients: [], users: [] };
  console.log('[admin.js] v7 loaded');

  async function api(path, init){
    init = init || {};
    init.credentials = 'include';
    const r = await fetch(path, init);
    let body = null; try { body = await r.json(); } catch(e){}
    if(!r.ok){ const msg = (body && (body.error||body.message)) || r.statusText || 'API error'; throw new Error(msg); }
    return body;
  }

  function fillSelect(sel, items, getVal, getText, placeholder){
    const s = (typeof sel==='string') ? $(sel) : sel;
    if(!s) return null;
    s.innerHTML = '';
    if (placeholder) {
      const opt = document.createElement('option');
      opt.value = ''; opt.textContent = placeholder; s.appendChild(opt);
    }
    for(const it of items){
      const opt = document.createElement('option');
      opt.value = String(getVal(it));
      opt.textContent = getText(it);
      s.appendChild(opt);
    }
    return s;
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      if (isNaN(date)) return dateStr;
      const day = date.getDate().toString().padStart(2, '0');
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const year = date.getFullYear();
      return `${day}-${month}-${year}`;
    } catch (e) {
      return dateStr;
    }
  }

  function hideLegacyAddressFields(){
    const candidates = ['#location','[name="location"]','[name="address_street"]','[name="address_number"]','[name="address_postcode"]','[name="address_city"]','[name="address_country"]'];
    for(const sel of candidates){
      const el = $(sel);
      if(el){
        const row = el.closest('.row') || el.closest('div') || el;
        row.style.display = 'none';
      }
    }
  }

  /* ---------- Clients (existing) ---------- */
  async function loadClients(){
    const rows = await api('/api/clients');
    state.clients = rows || [];
    let sel = document.querySelector('select[name="client_id"], #client_id');
    if (!sel) {
      const candidates = $$('select');
      sel = candidates.find(x => /client/i.test(x.name||'') || /client/i.test(x.id||'')) || null;
    }
    if (!sel) return;
    sel = fillSelect(sel, state.clients, x=>x.id, x=>x.name || ('Klant #'+x.id), '— kies klant —');
    if (sel){
      sel.dataset.fpc = 'client-select';
      if (!sel.id) sel.id = 'client_id';
    }
    ensureClientEditButton();
  }

  function clientSelect(){
    return document.querySelector('select[data-fpc="client-select"]');
  }

  function ensureClientEditButton(){
    const sel = clientSelect();
    if (!sel) return;
    if ($('#editClientBtn')) return;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'editClientBtn';
    btn.textContent = 'Bewerk klant';
    btn.style.marginLeft = '.5rem';
    btn.addEventListener('click', ()=>{
      const raw = (sel.value||'').trim();
      let id = parseInt(raw, 10);
      if (!raw || Number.isNaN(id)) {
        const name = sel.options[sel.selectedIndex]?.text?.trim();
        const hit = state.clients.find(c => (c.name||'').trim() === (name||''));
        id = hit ? parseInt(hit.id,10) : NaN;
      }
      if (Number.isNaN(id) || !id) { showClientPanelError('Kies eerst een geldige klant.'); return; }
      openClientEditor(id);
    });
    sel.insertAdjacentElement('afterend', btn);
  }

  function ensureClientEditorPanel(){
    let host = $('#clientEditor');
    if (!host){
      host = document.createElement('div');
      host.id = 'clientEditor';
      host.className = 'card';
      host.style.marginTop = '1rem';
      host.style.display = 'none';
      host.innerHTML = `
        <h3 style="margin-top:0;">Klant bewerken</h3>
        <div id="clientEditInfo" class="muted" style="min-height:1.2em;"></div>
        <form id="clientEditForm" style="margin-top:.5rem;">
          <input type="hidden" name="id">
          <div class="row"><label>Naam</label><input name="name" type="text" placeholder="Naam"></div>
          <div class="row"><label>E-mail</label><input name="email" type="email" placeholder="E-mail"></div>
          <div class="row"><label>Telefoon</label><input name="phone" type="text" placeholder="Telefoon"></div>
          <div class="row"><label>Straat</label><input name="address_street" type="text" placeholder="Straat"></div>
          <div class="row"><label>Nr</label><input name="address_number" type="text" placeholder="Nr"></div>
          <div class="row"><label>Postcode</label><input name="address_postcode" type="text" placeholder="Postcode"></div>
          <div class="row"><label>Plaats</label><input name="address_city" type="text" placeholder="Plaats"></div>
          <div class="row"><label>Land</label><input name="address_country" type="text" placeholder="Land"></div>
          <div class="row"><label>Adres (vrij)</label><input name="address" type="text" placeholder="Vrij adres (fallback)"></div>
          <div class="row"><label>Notities</label><textarea name="notes" placeholder="Notities"></textarea></div>
          <div class="row">
            <button type="submit">Opslaan</button>
            <button type="button" id="closeClientEditor" style="margin-left:.5rem;">Sluiten</button>
            <span id="clientEditMsg" class="error" style="margin-left:.5rem;"></span>
          </div>
        </form>`;
      const target = $('.container') || document.body;
      target.appendChild(host);
      $('#closeClientEditor').addEventListener('click', ()=>{ host.style.display='none'; });
      bindClientEditForm();
    }
    return host;
  }

  function showClientPanelError(msg){
    ensureClientEditorPanel();
    const panel = $('#clientEditor');
    const info  = $('#clientEditInfo');
    if (panel) panel.style.display = '';
    if (info)  info.textContent = msg || '';
  }

  async function openClientEditor(clientId){
    const panel = ensureClientEditorPanel();
    const form  = $('#clientEditForm');
    const msg   = $('#clientEditMsg');
    const info  = $('#clientEditInfo');
    if (panel) panel.style.display = '';
    if (msg)   msg.textContent = '';
    if (info)  info.textContent = 'Laden…';
    try{
      const c = await api('/api/client?id='+encodeURIComponent(clientId));
      if (info) info.textContent = '';
      form.querySelector('[name="id"]').value = c.id;
      for (const k of ['name','email','phone','address','address_street','address_number','address_postcode','address_city','address_country','notes']){
        const el = form.querySelector('[name="'+k+'"]'); if (el) el.value = c[k] || '';
      }
    }catch(e){
      if (info) info.textContent = e.message || 'Laden mislukt';
    }
  }

  function bindClientEditForm(){
    const form = $('#clientEditForm');
    if (!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const msg = $('#clientEditMsg');
      const fd = new FormData(form);
      const payload = { id: (fd.get('id')||'').toString().trim() };
      for (const k of ['name','email','phone','address','address_street','address_number','address_postcode','address_city','address_country','notes']){
        const v = (fd.get(k)||'').toString();
        if (v !== '') payload[k] = v;
      }
      try{
        if (msg) msg.textContent = 'Opslaan...';
        await api('/api/client', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        if (msg) msg.textContent = 'Opgeslagen.';
        await loadClients();
      }catch(err){
        if (msg) msg.textContent = err.message || 'API error';
      }
    });
  }

  /* ---------- Users: Engineers beheer ---------- */
  function ensureEngineersPanel(){
    let host = $('#engineersPanel');
    if (!host){
      host = document.createElement('div');
      host.id = 'engineersPanel';
      host.className = 'card';
      host.style.marginTop = '1rem';
      host.innerHTML = `
        <h3 style="margin-top:0;">Engineers beheren</h3>
        <div id="engMsg" class="error" style="min-height:1.2em;"></div>
        <div class="table-wrap">
          <table id="engTable">
            <thead><tr>
              <th>ID</th><th>Gebruiker</th><th>Naam</th><th>E-mail</th><th>Telefoon</th><th>Acties</th>
            </tr></thead>
            <tbody></tbody>
          </table>
        </div>
      `;
      const target = $('.container') || document.body;
      target.appendChild(host);
    }
    return host;
  }

  async function loadEngineers(){
    const msg = $('#engMsg'); if (msg) msg.textContent='';
    const rows = await api('/api/users');
    state.users = rows || [];
    const engineers = state.users.filter(u => u.role === 'field_engineer');
    const tbody = $('#engTable tbody'); if (!tbody) return;
    tbody.innerHTML='';
    for (const u of engineers){
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${u.id}</td>
        <td>${u.username}</td>
        <td>${u.full_name||''}</td>
        <td>${u.email||''}</td>
        <td>${u.phone||''}</td>
        <td>
          <button type="button" data-act="edit" data-id="${u.id}">Bewerk</button>
          <button type="button" data-act="pw" data-id="${u.id}">Reset wachtwoord</button>
          <button type="button" data-act="del" data-id="${u.id}">Verwijder</button>
        </td>
      `;
      tbody.appendChild(tr);
    }
  }

  function bindEngineerActions(){
    document.addEventListener('click', async (e)=>{
      const btn = e.target;
      if (!(btn instanceof HTMLElement)) return;
      const act = btn.getAttribute('data-act');
      const id  = parseInt(btn.getAttribute('data-id')||'0',10);
      if (!act || !id) return;

      const msg = $('#engMsg');
      try{
        if (act === 'edit'){
          const user = state.users.find(x=>x.id===id);
          if (!user) throw new Error('User niet gevonden');
          const full_name = prompt('Naam', user.full_name||''); if (full_name===null) return;
          const email = prompt('E-mail', user.email||''); if (email===null) return;
          const phone = prompt('Telefoon (optioneel)', user.phone||''); if (phone===null) return;
          await api('/api/users', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, full_name, email, phone }) });
          if (msg) msg.textContent = 'Gewijzigd.';
          await loadEngineers();
          await loadUsers(); // refresh assign dropdown
        }
        if (act === 'pw'){
          const np = prompt('Nieuw wachtwoord voor engineer #' + id + ':'); if (np===null || np==='') return;
          await api('/api/users/password', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, new_password: np }) });
          if (msg) msg.textContent = 'Wachtwoord gereset.';
        }
        if (act === 'del'){
          if (!confirm('Weet je zeker dat je engineer #' + id + ' wilt verwijderen? Alle gekoppelde taken moeten eerst worden herverdeeld.')) return;
          const r = await fetch('/api/users?id='+id, { method:'DELETE', credentials:'include' });
          let body=null; try{ body=await r.json(); }catch(_){}
          if(!r.ok){ throw new Error((body && (body.error||body.message)) || r.statusText); }
          if (msg) msg.textContent = 'Engineer verwijderd.';
          await loadEngineers();
          await loadUsers(); // refresh assign dropdown
        }
      }catch(err){
        if (msg) msg.textContent = err.message || 'Actie mislukt';
      }
    });
  }

  /* ---------- Engineers (for task assign dropdown) ---------- */
  async function loadEngineers(){
    const rows = await api('/api/engineers');
    state.engineers = rows || [];
    const sel = document.querySelector('select[name="assigned_user_id"], #assigned_user_id');
    if (sel) fillSelect(sel, state.engineers, x=>x.id, x=>x.name || `Engineer #${x.id}`, '— kies engineer —');
  }

  /* ---------- Users (existing for task assign) ---------- */
  async function loadUsers(){
    const rows = await api('/api/users');
    state.users = rows || [];
    const eng = state.users.filter(u => u.role === 'field_engineer');
    const sel = document.querySelector('select[name="assigned_to"], #assigned_to');
    if (sel) fillSelect(sel, eng, x=>x.id, x=>x.full_name || x.username, '— kies engineer —');
  }

  /* ---------- Tasks (existing) ---------- */
  function bindTaskForm(){
    const form = $('#taskForm') || $('form#taskForm') || document.forms[0];
    const msg  = $('#taskMsg') || $('.error');
    if(!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(form);
      const payload = {
        title: (fd.get('title')||'').toString().trim(),
        description: (fd.get('description')||'').toString().trim(),
        client_id: (fd.get('client_id')||'').toString().trim(),
        assigned_user_id: (fd.get('assigned_user_id')||'').toString().trim(),
        priority: (fd.get('priority')||'medium').toString().trim(),
        scheduled_date: (fd.get('scheduled_date')||'').toString().trim()
      };
      if(!payload.title){ if(msg) msg.textContent = 'Titel is verplicht.'; return; }
      try{
        if(msg) msg.textContent = 'Opslaan...';
        await api('/api/tasks', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload)
        });
        if(msg) msg.textContent = 'Taak toegevoegd.';
        form.reset();
        try { await loadTasks(); } catch(e){}
      }catch(err){
        if(msg) msg.textContent = err.message || 'API error';
      }
    });
  }

  async function loadTasks(){
    const table = $('#tasksTable'); if(!table) return;
    const rows = await api('/api/tasks');
    const tbody = table.tBodies[0] || table.createTBody();
    tbody.innerHTML='';
    for(const t of rows){
      const addr = (t.full_address||t.location||'');
      const map = t.maps_url ? ` <a href="${t.maps_url}" target="_blank" rel="noopener">Kaart</a>` : '';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${t.id}</td>
        <td>${t.title||''}</td>
        <td>${t.client_name||''}</td>
        <td>${t.assigned_to_name || t.assigned_user_id_name || ''}</td>
        <td>${t.priority||''}</td>
        <td>${formatDate(t.scheduled_date)||''}</td>
        <td>${addr}${map}</td>
      `;
      tbody.appendChild(tr);
    }
  }

  /* ---------- Init ---------- */
  document.addEventListener('DOMContentLoaded', async function(){
    hideLegacyAddressFields();
    ensureEngineersPanel();
    bindEngineerActions();
    initializeNavigation();
    bindUserManagement();
    try{
      await Promise.all([loadClients(), loadUsers(), loadEngineers()]);
    }catch(e){ console.error(e); }
    ensureClientEditButton();
    ensureClientEditorPanel();
    bindClientEditForm();
    bindTaskForm();
    try{ await loadTasks(); }catch(e){}
  });
  // Navigation functionality
  function initializeNavigation() {
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const section = btn.getAttribute('data-section');
        showSection(section);
        
        // Update active nav button
        navButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  }
  
  function showSection(section) {
    // Hide all sections first
    const allSections = document.querySelectorAll('.content-section');
    allSections.forEach(s => {
      if (s.id === 'users-section' || s.id === 'reports-section') {
        s.style.display = 'none';
      }
    });
    
    // Show or hide main content based on section
    const mainSections = document.querySelectorAll('.content-section:not(#users-section):not(#reports-section)');
    if (section === 'tasks' || section === 'clients') {
      mainSections.forEach(s => s.style.display = 'block');
    } else {
      mainSections.forEach(s => s.style.display = 'none');
    }
    
    // Show selected section
    if (section === 'users') {
      const el = document.getElementById('users-section');
      if (el) el.style.display = 'block';
    } else if (section === 'reports') {
      const el = document.getElementById('reports-section');
      if (el) el.style.display = 'block';
    }
  }

  // User management
  async function loadUsers() {
    try {
      const users = await api('/api/users');
      const tbody = document.querySelector('#usersTable tbody');
      if (!tbody) return;
      
      tbody.innerHTML = '';
      users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${user.id}</td>
          <td>${user.username || ''}</td>
          <td>${user.email || ''}</td>
          <td>${user.full_name || ''}</td>
          <td>${user.role || ''}</td>
          <td>${user.activated ? 'Ja' : 'Nee'}</td>
          <td>
            <button class="btn btn-sm" onclick="editUser(${user.id})">Bewerken</button>
            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Verwijderen</button>
          </td>
        `;
        tbody.appendChild(row);
      });
    } catch (error) {
      console.error('Failed to load users:', error);
    }
  }

  function bindUserManagement() {
    const addUserBtn = document.getElementById('addUserBtn');
    const userForm = document.getElementById('userForm');
    const cancelUserBtn = document.getElementById('cancelUserBtn');
    
    if (addUserBtn) {
      addUserBtn.addEventListener('click', () => {
        document.getElementById('userForm').style.display = 'block';
        document.getElementById('userFormTitle').textContent = 'Nieuwe gebruiker';
        document.getElementById('userForm').reset();
      });
    }
    
    if (cancelUserBtn) {
      cancelUserBtn.addEventListener('click', () => {
        document.getElementById('userForm').style.display = 'none';
      });
    }
    
    if (userForm) {
      userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(userForm);
        const userData = {
          username: formData.get('username'),
          email: formData.get('email'),
          full_name: formData.get('full_name'),
          role: formData.get('role')
        };
        
        try {
          await api('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
          });
          document.getElementById('userMsg').textContent = 'Gebruiker toegevoegd!';
          userForm.style.display = 'none';
          await loadUsers();
        } catch (error) {
          document.getElementById('userMsg').textContent = error.message;
        }
      });
    }
  }

  // Reports functionality
  function bindReports() {
    const generateBtn = document.getElementById('generateReportBtn');
    if (generateBtn) {
      generateBtn.addEventListener('click', async () => {
        const type = document.getElementById('reportType').value;
        const dateFrom = document.getElementById('reportDate').value;
        const resultDiv = document.getElementById('reportResult');
        
        try {
          resultDiv.innerHTML = 'Rapport genereren...';
          
          const response = await api('/api/reports/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              type: type,
              date_from: dateFrom
            })
          });
          
          if (response.ok) {
            resultDiv.innerHTML = `
              <div class="success-message">
                <p>Rapport succesvol gegenereerd!</p>
                <a href="${response.path}" target="_blank" class="btn btn-primary">Download PDF</a>
              </div>
            `;
          }
        } catch (error) {
          resultDiv.innerHTML = `<div class="error-message">Fout bij genereren rapport: ${error.message}</div>`;
        }
      });
    }
  }

  // Global functions for user management
  window.editUser = function(userId) {
    // Implementation for editing users (if needed)
    console.log('Edit user:', userId);
  };

  window.deleteUser = async function(userId) {
    if (confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')) {
      try {
        const response = await fetch('/api/users?id=' + userId, {
          method: 'DELETE',
          credentials: 'include'
        });
        
        const result = await response.json();
        
        if (response.ok) {
          document.getElementById('userMsg').textContent = 'Gebruiker verwijderd!';
          await loadUsers(); // Reload the users list
        } else {
          document.getElementById('userMsg').textContent = result.error || 'Fout bij verwijderen';
        }
      } catch (error) {
        document.getElementById('userMsg').textContent = 'Er ging iets mis: ' + error.message;
      }
    }
  };

  /* ---------- Navigation ---------- */
  function initializeNavigation() {
    const navButtons = document.querySelectorAll('.admin-nav .nav-btn');
    const sections = {
      'tasks': document.getElementById('tasks-section'),
      'clients': document.getElementById('clients-section'), 
      'users': document.getElementById('users-section'),
      'reports': document.getElementById('reports-section')
    };

    navButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const section = btn.getAttribute('data-section');
        
        // Hide all sections
        Object.values(sections).forEach(sec => {
          if (sec) sec.style.display = 'none';
        });
        
        // Show selected section
        if (sections[section]) {
          sections[section].style.display = 'block';
        }
        
        // Update active nav button
        navButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  }

  // Global user management functions
  window.editUser = function(userId) {
    console.log('Edit user:', userId);
    // Add edit functionality here
  };
  
  window.deleteUser = function(userId) {
    if (confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')) {
      api('/api/users/' + userId, { method: 'DELETE' })
        .then(() => loadUsers())
        .catch(err => alert(err.message));
    }
  };

})();