/**
 * Mobile nav toggle only.
 * Never use innerHTML with unsanitized data — textContent / classList only.
 */
(function () {
  'use strict';

  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('site-nav');
  if (!toggle || !nav) {
    return;
  }

  toggle.addEventListener('click', function () {
    var open = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
