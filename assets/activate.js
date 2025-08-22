(function(){
  const $ = (s, ctx)=> (ctx||document).querySelector(s);

  function getParam(name){
    const m = new URLSearchParams(location.search).get(name);
    return m ? m.toString() : '';
  }

  function bindPwToggle(){
    const toggles = [
      { btn: $('#toggleNewPw'), pw: $('#newPassword') },
      { btn: $('#toggleConfirmPw'), pw: $('#confirmPassword') }
    ];
    
    toggles.forEach(({ btn, pw }) => {
      if (!btn || !pw) return;
      btn.addEventListener('click', ()=>{
        pw.type = (pw.type === 'password') ? 'text' : 'password';
        btn.textContent = (pw.type === 'password') ? 'Toon' : 'Verberg';
      });
    });
  }

  function handleActivation(){
    const form = $('#activationForm');
    if (!form) return;
    
    // Set token from URL
    const token = getParam('token');
    if (!token) {
      const errorDiv = $('#activationError');
      if (errorDiv) {
        errorDiv.textContent = 'Ongeldige activatielink.';
        errorDiv.style.display = 'block';
      }
      return;
    }
    $('#token').value = token;
    
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      
      const newPassword = $('#newPassword').value;
      const confirmPassword = $('#confirmPassword').value;
      const errorDiv = $('#activationError');
      const successDiv = $('#activationSuccess');
      
      // Hide previous messages
      if (errorDiv) errorDiv.style.display = 'none';
      if (successDiv) successDiv.style.display = 'none';
      
      if (!newPassword || !confirmPassword) {
        if (errorDiv) {
          errorDiv.textContent = 'Vul alle velden in.';
          errorDiv.style.display = 'block';
        }
        return;
      }
      
      if (newPassword !== confirmPassword) {
        if (errorDiv) {
          errorDiv.textContent = 'Wachtwoorden komen niet overeen.';
          errorDiv.style.display = 'block';
        }
        return;
      }
      
      if (newPassword.length < 8) {
        if (errorDiv) {
          errorDiv.textContent = 'Wachtwoord moet minimaal 8 karakters zijn.';
          errorDiv.style.display = 'block';
        }
        return;
      }
      
      try {
        const response = await fetch('/api/activate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            token: token,
            password: newPassword
          })
        });
        
        const data = await response.json();
        
        if (response.ok && data.ok) {
          if (successDiv) {
            successDiv.textContent = 'Account succesvol geactiveerd! U wordt doorgestuurd naar de login pagina.';
            successDiv.style.display = 'block';
          }
          setTimeout(() => {
            window.location.href = '/login';
          }, 2000);
        } else {
          if (errorDiv) {
            errorDiv.textContent = data.error || 'Activatie mislukt. Probeer opnieuw.';
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
    bindPwToggle();
    handleActivation();
  });
})();