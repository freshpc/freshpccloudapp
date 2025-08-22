// engineers2.js — mobile accordion with signature + finish flow + robust fallbacks
(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);
  const $$ = (s, ctx)=> Array.from((ctx||document).querySelectorAll(s));

  (function injectFallbackCSS(){
    const css = `.task-body{display:none}.task-item.open .task-body{display:block}
      .task-item{border:1px solid #eef1f6;border-radius:12px;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,.06);margin-bottom:.75rem}
      .task-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1rem}
      .task-actions{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem}`;
    const tag = document.createElement('style'); tag.textContent = css; document.head.appendChild(tag);
  })();

  async function api(path, init){
    init = init || {};
    init.credentials = 'include';
    const r = await fetch(path, init);
    let body = null; try { body = await r.json(); } catch(e){}
    if(!r.ok){ const msg = (body && (body.error||body.message)) || r.statusText || 'API error'; throw new Error(msg); }
    return body;
  }

  function bindLogout(){
    const btn = $('#logoutBtn');
    if (!btn) return;
    btn.addEventListener('click', async ()=>{
      try{ await api('/api/logout', { method:'POST' }); }catch(e){}
      location.href = '/login?role=engineer';
    });
  }

  const label = (s)=>{
    const m = (s||'').trim();
    if (m==='done') return 'afgerond';
    if (m==='rejected') return '❌ AFGEWEZEN';
    if (m==='declined') return 'niet geaccepteerd';
    if (m==='accepted') return 'geaccepteerd';
    if (m==='in_progress') return 'bezig';
    if (m===''||m==='received') return 'ontvangen';
    return m;
  };

  function ensureTaskListContainer(){
    let list = $('#taskList');
    if (!list){
      list = document.createElement('div');
      list.className = 'accordion';
      list.id = 'taskList';
      const oldTable = document.querySelector('.table-wrap');
      if (oldTable){ 
        oldTable.style.display = 'none'; 
        oldTable.insertAdjacentElement('afterend', list); 
      } else { 
        (document.querySelector('main .container') || document.body).appendChild(list); 
      }
    }
    // Always ensure accordion is visible on mobile
    list.style.display = 'block';
    return list;
  }

  function signatureBlock(id){
    const cid = 'sig-'+id;
    return `
      <div class="sigbox">
        <p class="muted">Klant handtekening:</p>
        <canvas id="${cid}" width="500" height="160" style="width:100%;max-width:560px;height:160px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;"></canvas>
        <div class="task-actions" style="margin-top:.5rem;">
          <button type="button" data-act="sig-clear" data-id="${id}">Wis</button>
          <button type="button" data-act="finish" data-id="${id}">Afronden</button>
          <button type="button" data-act="reject" data-id="${id}" style="background-color:#dc3545;color:white;">Afwijzen</button>
        </div>
      </div>`;
  }

  function taskItemHTML(t){
    const addr = t.full_address || t.location || '';
    const st = label(t.status);
    const sched = (t.scheduled_date||'').replace('T',' ').slice(0,16);
    const mapHtml = (t.latitude && t.longitude) ? 
      `<div class="task-map-container">
         <div id="map-${t.id}" class="task-map"></div>
       </div>` : '';
    const navLink = t.nav_url ? `<a href="${t.nav_url}" target="_blank" rel="noopener" class="btn btn-secondary">Navigeren</a>` : '';
    return `
    <div class="task-item" data-id="${t.id}">
      <div class="task-head">
        <div class="task-info">
          <div class="task-title">${t.title||''}</div>
          <div class="task-meta">${sched || ''} • ${st}</div>
        </div>
        <button type="button" data-act="toggle" aria-label="Open taak" class="task-toggle-btn">OPEN</button>
      </div>
      <div class="task-body" style="display:none">
        <div class="customer-details">
          <h3>Klantgegevens</h3>
          <div class="customer-info">
            <p><strong>Naam:</strong> ${t.client_name||'Niet opgegeven'}</p>
            <p><strong>E-mail:</strong> ${t.client_email||'Niet opgegeven'}</p>
            <p><strong>Adres:</strong> ${addr||'Niet opgegeven'}</p>
            <p><strong>Taak beschrijving:</strong> ${t.description||'Geen beschrijving'}</p>
          </div>
          ${mapHtml}
        </div>
        <div class="task-actions">
          <button type="button" data-act="accept" data-id="${t.id}">Accepteer</button>
          <button type="button" data-act="start" data-id="${t.id}">Start taak</button>
          <button type="button" data-act="photo" data-id="${t.id}">Foto maken</button>
          <button type="button" data-act="done" data-id="${t.id}">Afronden</button>
          <button type="button" data-act="reject" data-id="${t.id}" style="background-color:#dc3545;color:white;">Afwijzen</button>
          <button type="button" data-act="maps" data-id="${t.id}">Locatie bekijken</button>
        </div>
        <div style="margin-top:.75rem;">
          <div class="row"><input class="clientName" placeholder="Klantnaam" value="${t.client_name||''}"></div>
          <div class="row"><input class="clientEmail" placeholder="Klant e‑mail (rapport)" value="${t.client_email||''}"></div>
          <div class="row"><textarea class="workNotes" placeholder="Uitgevoerde werkzaamheden"></textarea></div>
        </div>
        ${signatureBlock(t.id)}
      </div>
    </div>`;
  }

  async function loadTasks(){
    const list = ensureTaskListContainer();
    list.innerHTML = '<div class="muted">Laden…</div>';
    try{
      const rows = await api('/api/tasks');
      const active = rows.filter(t => (t.status||'received') !== 'declined' && (t.status||'received') !== 'done' && (t.status||'received') !== 'rejected');
      const hist   = rows.filter(t => (t.status||'received') === 'declined' || (t.status||'received') === 'done' || (t.status||'received') === 'rejected');
      list.innerHTML = active.length ? active.map(taskItemHTML).join('') : '<div class="muted">Geen taken.</div>';
      ensureSigBindings();
      initializeMaps();
      const htbody = document.querySelector('#historyTable tbody');
      if (htbody){
        htbody.innerHTML = '';
        if (!hist.length){
          htbody.innerHTML = '<tr><td colspan="4" class="muted">Geen historie.</td></tr>';
        } else {
          for (const t of hist){
            const st = label(t.status);
            const addr = t.full_address || t.location || '';
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${t.updated_at||t.completed_date||t.created_at||''}</td><td>#${t.id} ${t.title||''}</td><td>${st}</td><td>${addr}</td>`;
            htbody.appendChild(tr);
          }
        }
      }
    }catch(e){
      list.innerHTML = '<div class="error">Laden mislukt.</div>';
      console.error(e);
    }
  }

  function ensureCameraInput(){
    let input = document.getElementById('fpcCameraInput');
    if (!input){
      input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.capture = 'environment';
      input.id = 'fpcCameraInput';
      input.style.display = 'none';
      document.body.appendChild(input);
    }
    return input;
  }
  async function uploadPhoto(taskId, file){
    const fd = new FormData(); fd.append('task_id', String(taskId)); fd.append('kind','start'); fd.append('photo', file, file.name||'photo.jpg');
    const r = await fetch('/api/task/photo', { method:'POST', credentials:'include', body: fd });
    if (!r.ok) { let t; try{ t=await r.json(); }catch(_){ t={}; } throw new Error((t && (t.error||t.message)) || 'Upload mislukt'); }
  }

  function getSigCanvas(id){ return document.getElementById('sig-'+id); }
  function bindSigDrawing(root){
    const c = root.querySelector('canvas'); if (!c) return;
    const ctx = c.getContext('2d'); ctx.lineWidth = 2;
    let drawing = false;
    function pos(e){ const r=c.getBoundingClientRect(); const t=e.touches?e.touches[0]:e; return {x:t.clientX-r.left, y:t.clientY-r.top}; }
    function start(e){ drawing=true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); }
    function move(e){ if(!drawing) return; const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }
    function end(){ drawing=false; }
    c.addEventListener('mousedown', start); c.addEventListener('mousemove', move); c.addEventListener('mouseup', end); c.addEventListener('mouseleave', end);
    c.addEventListener('touchstart', start, {passive:true}); c.addEventListener('touchmove', move, {passive:true}); c.addEventListener('touchend', end);
  }
  function ensureSigBindings(){ document.querySelectorAll('.sigbox').forEach(bindSigDrawing); }

  async function saveSignatureAndFinish(id){
    const canvas = getSigCanvas(id);
    if (!canvas){ alert('Geen handtekening canvas gevonden.'); return; }
    const empty = document.createElement('canvas'); empty.width=canvas.width; empty.height=canvas.height;
    if (canvas.toDataURL() === empty.toDataURL()){ if(!confirm('Er is geen handtekening gezet. Toch afronden?')) return; }
    const png = canvas.toDataURL('image/png');
    try {
      const response = await api('/api/task/signature', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, image: png }) });
      await loadTasks();
      if (response && response.success) {
        alert('Taak afgerond en naar historie verplaatst. Werkbon is gemaild naar admin@freshpccloud.nl.');
      } else {
        alert('Taak opgeslagen, maar er was een probleem met historie/email.');
      }
    } catch (error) {
      console.error('Error finishing task:', error);
      alert('Handtekening opgeslagen, maar er was een probleem: ' + error.message);
    }
  }

  document.addEventListener('click', async (e)=>{
    const el = e.target; if (!(el instanceof HTMLElement)) return;
    const act = el.getAttribute('data-act'); if (!act) return;
    const wrap = el.closest('.task-item'); const id = wrap ? parseInt(wrap.getAttribute('data-id')||'0',10) : 0;

    if (act==='toggle'){
      const body = wrap.querySelector('.task-body');
      const toggleBtn = wrap.querySelector('.task-toggle-btn');
      if (body){
        const isVisible = (body.style.display!=='' && body.style.display!=='none');
        
        // Close all other tasks
        document.querySelectorAll('.task-item .task-body').forEach(b => b.style.display='none');
        document.querySelectorAll('.task-item').forEach(i => {
          i.classList.remove('open');
          const btn = i.querySelector('.task-toggle-btn');
          if (btn) {
            btn.textContent = 'OPEN';
            btn.style.display = 'block';
          }
        });
        
        // Toggle current task
        if (!isVisible){ 
          body.style.display='block'; 
          wrap.classList.add('open');
          if (toggleBtn) {
            toggleBtn.textContent = 'SLUITEN';
          }
        } else {
          if (toggleBtn) {
            toggleBtn.textContent = 'OPEN';
          }
        }
      }
      ensureSigBindings();
      return;
    }
    if (!id) return;

    try{
      let keepTaskOpen = false;
      const currentOpenTask = wrap && wrap.classList.contains('open') ? wrap : null;
      
      if (act==='accept') {
        await api('/api/task', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, status:'accepted' }) });
        keepTaskOpen = true;
      }
      else if (act==='start') {
        await api('/api/task', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, status:'in_progress' }) });
        keepTaskOpen = true;
      }
      else if (act==='done') await api('/api/task', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, status:'done' }) });
      else if (act==='photo'){ 
        const cam=ensureCameraInput(); 
        cam.onchange=async()=>{ 
          const f=cam.files&&cam.files[0]; 
          if(f){ 
            try{ 
              await uploadPhoto(id,f); 
              alert('Foto opgeslagen.'); 
            }catch(err){ 
              alert(err.message); 
            } 
          } 
          cam.value=''; 
        }; 
        cam.click(); 
        return; 
      }
      else if (act==='maps'){ const mapDiv=document.getElementById('map-'+id); if(mapDiv){ mapDiv.scrollIntoView({behavior:'smooth',block:'center'}); } return; }
      else if (act==='sig-clear'){ const c=getSigCanvas(id); if(c){ const x=c.getContext('2d'); x.clearRect(0,0,c.width,c.height); } return; }
      else if (act==='finish'){ await saveSignatureAndFinish(id); return; }
      else if (act==='reject'){ 
        const reason = prompt('Reden voor afwijzing:');
        if (reason !== null) {
          await api('/api/task', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, status:'rejected', rejection_reason: reason }) });
        } else {
          return;
        }
      }
      
      await loadTasks();
      
      // Reopen the task if it was open before and we want to keep it open
      if (keepTaskOpen && currentOpenTask) {
        setTimeout(() => {
          const updatedTask = document.querySelector(`[data-id="${id}"]`);
          if (updatedTask) {
            const body = updatedTask.querySelector('.task-body');
            const toggleBtn = updatedTask.querySelector('.task-toggle-btn');
            if (body) {
              body.style.display = 'block';
              updatedTask.classList.add('open');
              if (toggleBtn) {
                toggleBtn.textContent = 'SLUITEN';
              }
              ensureSigBindings();
              initializeMaps();
            }
          }
        }, 100);
      }
    }catch(err){
      console.error(err); alert(err.message||'Actie mislukt');
    }
  });

  // Engineer profile management
  function initializeEngineerNav() {
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const section = btn.getAttribute('data-section');
        showEngineerSection(section);
        
        // Update active nav button
        navButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  }
  
  function showEngineerSection(section) {
    const tasksSection = document.getElementById('tasks-section');
    const profileSection = document.getElementById('profile-section');
    
    if (section === 'tasks') {
      if (tasksSection) tasksSection.style.display = 'block';
      if (profileSection) profileSection.style.display = 'none';
    } else if (section === 'profile') {
      if (tasksSection) tasksSection.style.display = 'none';
      if (profileSection) profileSection.style.display = 'block';
      loadEngineerProfile();
    }
  }
  
  async function loadEngineerProfile() {
    try {
      const user = await api('/api/me');
      if (user) {
        document.getElementById('profile_full_name').value = user.full_name || '';
        document.getElementById('profile_email').value = user.email || '';
        document.getElementById('profile_phone').value = user.phone || '';
      }
    } catch (error) {
      console.error('Failed to load profile:', error);
    }
  }
  
  function bindProfileForm() {
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
      profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(profileForm);
        const payload = {
          full_name: formData.get('full_name'),
          email: formData.get('email'),
          phone: formData.get('phone')
        };
        
        // Add password if provided
        const currentPw = formData.get('current_password');
        const newPw = formData.get('new_password');
        if (currentPw && newPw) {
          payload.current_password = currentPw;
          payload.new_password = newPw;
        }
        
        const msg = document.getElementById('profileMsg');
        try {
          if (msg) msg.textContent = 'Opslaan...';
          
          await api('/api/me', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          
          if (msg) msg.textContent = 'Profiel bijgewerkt.';
          
          // Clear password fields for security
          document.getElementById('current_password').value = '';
          document.getElementById('new_password').value = '';
          
        } catch (error) {
          if (msg) msg.textContent = error.message || 'Bijwerken mislukt';
        }
      });
    }
  }

  function initializeMaps() {
    // Initialize Google Maps for visible task details
    const visibleMaps = document.querySelectorAll('.task-map');
    visibleMaps.forEach(mapElement => {
      if (mapElement && typeof google !== 'undefined' && google.maps) {
        const taskId = mapElement.id.replace('map-', '');
        // For demo purposes, using Amsterdam coordinates
        // In production, you'd get these from the task data
        const lat = 52.3676;
        const lng = 4.9041;
        
        const map = new google.maps.Map(mapElement, {
          center: { lat: lat, lng: lng },
          zoom: 15,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        });
        
        new google.maps.Marker({
          position: { lat: lat, lng: lng },
          map: map,
          title: 'Task Location'
        });
      }
    });
  }

  function initializeEngineerNav() {
    const navButtons = document.querySelectorAll('.header-nav .nav-btn');
    const sections = {
      'tasks': document.getElementById('tasks-section'),
      'profile': document.getElementById('profile-section')
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

  document.addEventListener('DOMContentLoaded', async function(){
    bindLogout();
    initializeEngineerNav();
    bindProfileForm();
    const rb = document.querySelector('#refreshBtn');
    if (rb) rb.addEventListener('click', () => loadTasks());
    await loadTasks();
  });
})();