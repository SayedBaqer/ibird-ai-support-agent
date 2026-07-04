/* AI Support Agent — Admin JS */
(function () {
  'use strict';

  // Auto-dismiss notices after 5s.
  document.querySelectorAll('.notice.is-dismissible').forEach(function (n) {
    setTimeout(function () { n.style.transition = 'opacity .4s'; n.style.opacity = '0'; setTimeout(function () { n.remove(); }, 400); }, 5000);
  });

  // Confirm before destructive actions.
  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });

})();
