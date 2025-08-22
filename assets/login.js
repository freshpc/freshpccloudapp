(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);

  function getParam(name){
    const m = new URLSearchParams(location.search).get(name);
    return m ? m.toString() : '';
  }

  function setRoleFromQuery(){
    const role = (getParam('role') || '').toLowerCase();
    const roleField = $('#roleField');
    const title = $('#loginTitle');
    if (roleField){
      roleField.value = (role==='admin' || role==='engineer') ? (role==='engineer' ? 'engineer' : 'admin') : '';
    }
    if (title){
      if (role==='admin') title.textContent = 'Inloggen als Admin';
      else if (role==='engineer') title.textContent = 'Inloggen als Engineer';
      else title.textContent = 'Inloggen';
    }
    const err = getParam('err');
    const msg = $('#msg');
    if (msg && err){
      msg.textContent = (err==='invalid') ? 'Ongeldige gegevens.' : (err==='missing' ? 'Vul gebruikersnaam en wachtwoord in.' : 'Er ging iets mis.');
    }
  }

  function bindPwToggle(){
    const btn = $('#togglePw');
    const pw  = $('#password');
    if (!btn || !pw) return;
    btn.addEventListener('click', ()=>{
      pw.type = (pw.type === 'password') ? 'text' : 'password';
      btn.textContent = (pw.type === 'password') ? 'Toon' : 'Verberg';
    });
  }

  function handleLogin(){
    const form = $('#loginForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      
      const email = $('#email').value.trim();
      const password = $('#password').value;
      const errorDiv = $('#loginError');
      
      if (!email || !password) {
        if (errorDiv) {
          errorDiv.textContent = 'Vul alle velden in.';
          errorDiv.style.display = 'block';
        }
        return;
      }
      
      // Hide any previous errors
      if (errorDiv) {
        errorDiv.style.display = 'none';
      }
      
      try {
        const response = await fetch('/api/auth/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            username: email,
            password: password
          })
        });
        
        const data = await response.json();
        
        if (response.ok && data.ok) {
          // Successful login - redirect based on role
          const role = data.user?.role;
          if (role === 'admin') {
            window.location.href = '/admin';
          } else if (role === 'field_engineer') {
            window.location.href = '/engineer';
          } else {
            window.location.href = '/';
          }
        } else {
          // Login failed
          if (errorDiv) {
            errorDiv.textContent = data.error || 'Ongeldige inloggegevens.';
            errorDiv.style.display = 'block';
          }
        }
      } catch (error) {
        if (errorDiv) {
          errorDiv.textContent = 'Er ging iets mis. Probeer opnieuw.';
          errorDiv.style.display = 'block';
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    setRoleFromQuery();
    bindPwToggle();
    handleLogin();
  });
})();