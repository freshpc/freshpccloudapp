// session.js v5 | Robust session/auth handler for admin and engineer pages | line est: ~54 | Author: franklos
(function () {
  // Always include cookies on same-origin fetch
  const origFetch = window.fetch;
  window.fetch = function(i, init){ init=init||{}; if(!init.credentials) init.credentials='include'; return origFetch(i, init); };

  async function status(){ try{ const r=await fetch('/api/auth/status',{method:'GET'}); if(!r.ok) return {authenticated:false}; return await r.json(); }catch{return {authenticated:false}} }
  function attachLoginIfPresent(){
    const f = document.querySelector('form');
    if(!f) return false;
    const u = f.querySelector('input[name="username"], #username');
    const p = f.querySelector('input[name="password"], #password');
    if(!u||!p) return false;
    const msg = document.getElementById('loginMessage') || (()=>{const d=document.createElement('div'); d.id='loginMessage'; d.style.color='#b00020'; f.appendChild(d); return d;})();
    f.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const username=(u.value||'').trim(), password=p.value||'';
      if(!username||!password){ msg.textContent='Vul gebruikersnaam en wachtwoord in.'; return; }
      try{
        const r=await fetch('/api/auth/login', {method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({username,password})});
        if(!r.ok){ let err='Login mislukt'; try{const j=await r.json(); if(j&&j.error) err=j.error;}catch{} throw new Error(err); }
        const data = await r.json(); const role=data?.user?.role||'';
        const path=location.pathname; if(path.includes('admin')&&role!=='admin') location.assign('/engineer'); else location.reload();
      }catch(err){ msg.textContent=err.message; }
    });
    return true;
  }

  async function ensureAuth(){
    const path = location.pathname;
    const needAdmin = path.includes('admin');
    const needAuth  = path.includes('admin') || path.includes('field-engineer');
    if(!needAuth) return document.body.style.display = '';
    const s = await status();
    if(s.authenticated){
      if(needAdmin && s.user?.role!=='admin'){ location.assign('/engineer'); }
      document.body.style.display = '';
      return;
    }
    if(!attachLoginIfPresent()) location.assign('/');
  }

  document.addEventListener('DOMContentLoaded', ensureAuth);
})();