(function(){
  // Try to detect an existing nav list and append Profile icon
  function addToNav(){
    var nav = document.querySelector('.nav-links');
    if (!nav) return false;

    // Prevent duplicates
    if (nav.querySelector('[data-profile-link]')) return true;

    var li = document.createElement('li');
    var a = document.createElement('a');
    a.href = '/profile.php';
    a.setAttribute('data-profile-link', '1');
    a.innerHTML = '<i class="fas fa-user"></i> Profile';

    // Insert before logout if present
    var logout = Array.from(nav.querySelectorAll('a'))
      .find(x => /logout\.php$/i.test(x.getAttribute('href') || ''));

    if (logout && logout.parentElement) {
      nav.insertBefore(li, logout.parentElement);
    } else {
      nav.appendChild(li);
    }

    li.appendChild(a);
    return true;
  }

  // Fallback: floating button if no nav found
  function addFloating(){
    if (document.querySelector('.profile-fab')) return;

    var a = document.createElement('a');
    a.href = '/profile.php';
    a.className = 'profile-fab';
    a.title = 'Profile';
    a.innerHTML = '<i class="fas fa-user"></i>';

    document.body.appendChild(a);
  }

  function init(){
    if (!addToNav()) {
      addFloating();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
