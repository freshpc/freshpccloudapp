(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);

  async function api(path, init){
    init = init || {};
    init.credentials = 'include';
    const r = await fetch(path, init);
    let body = null; try { body = await r.json(); } catch(e){}
    if(!r.ok){ const msg=(body && (body.error||body.message))||r.statusText||'API error'; throw new Error(msg); }
    return body;
  }

  function ensureProfilePanel(){
    let host = document.getElementById('profilePanel');
    if (!host){
      host = document.createElement('div');
      host.id = 'profilePanel';
      host.className = 'card';
      host.style.maxWidth = '700px';
      host.style.margin = '1rem auto';
      host.innerHTML = `
        <h3 style="margin-top:0;">Mijn profiel</h3>
        <form id="meForm">
          <div class="row"><label>Volledige naam</label><input name="full_name" type="text" placeholder="Naam"></div>
          <div class="row"><label>E-mail</label><input name="email" type="email" placeholder="E-mail"></div>
          <div class="row"><label>Telefoon</label><input name="phone" type="text" placeholder="Telefoon"></div>
          <hr>
          <div class="row"><label>Huidig wachtwoord</label><input name="current_password" type="password" placeholder="Huidig wachtwoord"></div>
          <div class="row"><label>Nieuw wachtwoord</label><input name="new_password" type="password" placeholder="Nieuw wachtwoord"></div>
          <div class="row">
            <button type="submit">Opslaan</button>
            <span id="meMsg" class="error" style="margin-left:.5rem;"></span>
          </div>
        </form>`;
      const target = document.querySelector('.container') || document.body;
      target.prepend(host);
      bindMeForm();
    }
    return host;
  }

  async function loadMe(){
    const me = await api('/api/me');
    const f = document.getElementById('meForm');
    if (!f) return;
    for (const k of ['full_name','email','phone']){
      const el = f.querySelector('[name="'+k+'"]'); if (el) el.value = me && me[k] ? me[k] : '';
    }
  }

  function bindMeForm(){
    const f = document.getElementById('meForm');
    if (!f) return;
    f.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd = new FormData(f);
      const payload = {};
      for (const k of ['full_name','email','phone','current_password','new_password']){
        const v = (fd.get(k)||'').toString();
        if (v !== '') payload[k] = v;
      }
      const msg = document.getElementById('meMsg');
      try{
        if (msg) msg.textContent = 'Opslaan...';
        await api('/api/me', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        if (msg) msg.textContent = 'Opgeslagen.';
        // wis wachtwoordvelden uit veiligheid
        f.querySelector('[name="current_password"]').value='';
        f.querySelector('[name="new_password"]').value='';
      }catch(err){
        if (msg) msg.textContent = err.message || 'API error';
      }
    });
  }

  document.addEventListener('DOMContentLoaded', async function(){
    ensureProfilePanel();
    try{ await loadMe(); } catch(e){ console.error(e); }
  });
})();
