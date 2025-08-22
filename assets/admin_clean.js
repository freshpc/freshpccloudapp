// admin.js - Clean version with fixed navigation and professional UI
(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);
  const $$ = (s, ctx)=> Array.from((ctx||document).querySelectorAll(s));
  const state = { clients: [], users: [] };

  async function api(path, init){
    init = init || {};
    init.credentials = 'include';
    const r = await fetch(path, init);
    let body = null; try { body = await r.json(); } catch(e){}
    if(!r.ok){ const msg = (body && (body.error||body.message)) || r.statusText || 'API error'; throw new Error(msg); }
    return body;
  }

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
      if (s.id === 'users-section' || s.id === 'reports-section' || s.id === 'history-section') {
        s.style.display = 'none';
      }
    });
    
    // Show or hide main content based on section
    const mainSections = document.querySelectorAll('.content-section:not(#users-section):not(#reports-section):not(#history-section)');
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
    } else if (section === 'history') {
      const el = document.getElementById('history-section');
      if (el) {
        el.style.display = 'block';
        loadHistoryTasks();
      }
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
          <td>${user.username}</td>
          <td>${user.email}</td>
          <td>${user.full_name}</td>
          <td>${user.role}</td>
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

  // Global user management functions
  window.editUser = function(userId) {
    console.log('Edit user:', userId);
  };
  
  window.deleteUser = function(userId) {
    if (confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')) {
      api('/api/users/' + userId, { method: 'DELETE' })
        .then(() => loadUsers())
        .catch(err => alert(err.message));
    }
  };

  // Load history tasks
  async function loadHistoryTasks() {
    try {
      const tasks = await api('/api/tasks/history');
      const container = document.getElementById('historyTasks');
      if (!container) return;
      
      container.innerHTML = '';
      if (tasks.length === 0) {
        container.innerHTML = '<p>Geen afgehandelde taken gevonden.</p>';
        return;
      }
      
      tasks.forEach(task => {
        const div = document.createElement('div');
        div.className = 'history-task';
        
        const statusIcon = task.status === 'rejected' ? '❌ AFGEWEZEN' : '✅ AFGEROND';
        const pdfButtons = task.pdf_path ? 
          `<a href="${task.pdf_path}" target="_blank" class="pdf-btn">📄 PDF Bekijken</a>` : 
          '<span class="text-gray">Geen PDF beschikbaar</span>';
        
        div.innerHTML = `
          <h4>${task.title} ${statusIcon}</h4>
          <div class="task-meta">
            <strong>Klant:</strong> ${task.client_name || 'Onbekend'} |
            <strong>Engineer:</strong> ${task.engineer_name || 'Onbekend'} |
            <strong>Afgerond:</strong> ${task.completed_at || task.updated_at}
          </div>
          <div class="task-description">${task.description || ''}</div>
          ${task.rejection_reason ? `<div style="color: #dc2626; margin-top: 0.5rem;"><strong>Reden afwijzing:</strong> ${task.rejection_reason}</div>` : ''}
          <div class="task-actions">
            ${pdfButtons}
          </div>
        `;
        container.appendChild(div);
      });
    } catch (error) {
      console.error('Failed to load history tasks:', error);
      const container = document.getElementById('historyTasks');
      if (container) {
        container.innerHTML = '<p style="color: red;">Fout bij laden historie: ' + error.message + '</p>';
      }
    }
  }

  // Report functionality
  function bindReportManagement() {
    const reportTypeSelect = document.getElementById('reportType');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const reportResult = document.getElementById('reportResult');
    
    // Show results immediately when dropdown changes
    if (reportTypeSelect) {
      reportTypeSelect.addEventListener('change', showReportPreview);
      // Show initial preview
      setTimeout(showReportPreview, 100);
    }
    
    // Generate PDF button
    if (generateReportBtn) {
      generateReportBtn.addEventListener('click', generateReport);
    }
  }
  
  async function showReportPreview() {
    const reportType = document.getElementById('reportType').value;
    const reportDate = document.getElementById('reportDate').value;
    const reportResult = document.getElementById('reportResult');
    
    if (!reportResult) return;
    
    try {
      reportResult.innerHTML = '<div class="loading">Laden...</div>';
      
      const response = await api('/api/reports/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: reportType,
          date_from: reportDate
        })
      });
      
      const tasks = response.tasks || [];
      let html = `<div class="report-summary">
        <h3>Overzicht: ${getReportTypeName(reportType)}</h3>
        <p><strong>Aantal taken:</strong> ${tasks.length}</p>
        ${reportDate ? `<p><strong>Vanaf datum:</strong> ${reportDate}</p>` : ''}
      </div>`;
      
      if (tasks.length > 0) {
        html += '<div class="task-list">';
        tasks.forEach(task => {
          const statusIcon = task.status === 'rejected' ? '❌' : '✅';
          const completedDate = task.updated_at ? 
            new Date(task.updated_at).toLocaleDateString('nl-NL') : 'Onbekend';
          
          html += `
            <div class="task-item" style="border: 1px solid #e5e7eb; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
              <div class="task-header" style="font-weight: bold; margin-bottom: 0.5rem;">
                ${statusIcon} Taak #${task.id}: ${task.title || 'Geen titel'}
              </div>
              <div class="task-details" style="color: #6b7280; font-size: 0.9rem;">
                <div><strong>Klant:</strong> ${task.client_name || 'Onbekend'}</div>
                <div><strong>Engineer:</strong> ${task.engineer_name || 'Niet toegewezen'}</div>
                <div><strong>Status:</strong> ${getStatusText(task.status)}</div>
                <div><strong>Afgerond:</strong> ${completedDate}</div>
                ${task.description ? `<div style="margin-top: 0.5rem;"><strong>Beschrijving:</strong> ${task.description}</div>` : ''}
                ${task.rejection_reason ? `<div style="color: #dc2626; margin-top: 0.5rem;"><strong>Afwijzing reden:</strong> ${task.rejection_reason}</div>` : ''}
              </div>
            </div>
          `;
        });
        html += '</div>';
      } else {
        html += '<p>Geen taken gevonden voor de geselecteerde criteria.</p>';
      }
      
      reportResult.innerHTML = html;
      
    } catch (error) {
      console.error('Failed to load report preview:', error);
      reportResult.innerHTML = `<div style="color: red;">Fout bij laden preview: ${error.message}</div>`;
    }
  }
  
  async function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const reportDate = document.getElementById('reportDate').value;
    const generateReportBtn = document.getElementById('generateReportBtn');
    
    if (!generateReportBtn) return;
    
    try {
      generateReportBtn.textContent = 'Genereren...';
      generateReportBtn.disabled = true;
      
      const response = await api('/api/reports/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: reportType,
          date_from: reportDate
        })
      });
      
      if (response.ok && response.path) {
        // Open PDF in new window
        window.open(response.path, '_blank');
        alert('PDF rapport gegenereerd en geopend!');
      } else {
        throw new Error('PDF generatie mislukt');
      }
      
    } catch (error) {
      console.error('Failed to generate report:', error);
      alert('Fout bij PDF generatie: ' + error.message);
    } finally {
      generateReportBtn.textContent = 'Genereer PDF Rapport';
      generateReportBtn.disabled = false;
    }
  }
  
  function getReportTypeName(type) {
    switch(type) {
      case 'completed': return 'Afgeronde taken';
      case 'rejected': return 'Afgewezen taken';
      case 'all_finished': return 'Alle afgehandelde taken';
      default: return 'Onbekend';
    }
  }
  
  function getStatusText(status) {
    switch(status) {
      case 'done': return 'Afgerond';
      case 'rejected': return 'Afgewezen';
      case 'in_progress': return 'Bezig';
      case 'accepted': return 'Geaccepteerd';
      case 'received': return 'Ontvangen';
      default: return status || 'Onbekend';
    }
  }

  // Initialize everything
  document.addEventListener('DOMContentLoaded', async function(){
    initializeNavigation();
    bindUserManagement();
    bindReportManagement();
    try{
      await loadUsers();
    }catch(e){ console.error(e); }
  });

})();