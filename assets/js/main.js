(function () {
  'use strict';

  document.querySelectorAll('.navbar-collapse .nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
      var nav = document.querySelector('.navbar-collapse.show');
      if (nav && window.bootstrap) {
        window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
      }
    });
  });

  if (window.AOS) {
    document.documentElement.classList.add('aos-initialized');
    window.AOS.init({
      duration: 700,
      easing: 'ease-out-cubic',
      once: true,
      offset: 70
    });
  } else {
    document.querySelectorAll('[data-aos]').forEach(function (el) {
      el.removeAttribute('data-aos');
      el.removeAttribute('data-aos-delay');
      el.removeAttribute('data-aos-duration');
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
  }

  var form = document.getElementById('notifyForm');
  var message = document.getElementById('formMessage');

  function showMessage(text, type) {
    if (!message) return;
    message.textContent = text;
    message.className = 'form-message ' + (type || '');
  }

  if (form) {
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      var email = form.querySelector('input[name="email"]');
      var submit = form.querySelector('button[type="submit"]');

      if (!email || !email.value.trim()) {
        showMessage('Please enter your email address.', 'error');
        return;
      }

      if (!email.checkValidity()) {
        showMessage('Please enter a valid email address.', 'error');
        email.focus();
        return;
      }

      var originalText = submit ? submit.textContent : '';
      if (submit) {
        submit.disabled = true;
        submit.textContent = 'Saving...';
      }
      showMessage('', '');

      try {
        var response = await fetch(form.action, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new FormData(form)
        });
        var data = await response.json().catch(function () { return {}; });

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Unable to save your email right now. Please try again.');
        }

        showMessage(data.message || 'Thank you! We’ll notify you when updates are available.', 'success');
        form.reset();
      } catch (error) {
        showMessage(error.message || 'Unable to save your email right now. Please try again.', 'error');
      } finally {
        if (submit) {
          submit.disabled = false;
          submit.textContent = originalText;
        }
      }
    });
  }

  document.querySelectorAll('.speaker-profile-card').forEach(function (card) {
    card.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        card.click();
      }
    });
  });

  var speakerModal = document.getElementById('speakerModal');
  if (speakerModal) {
    speakerModal.addEventListener('show.bs.modal', function (event) {
      var card = event.relatedTarget;
      if (!card) return;

      var name = card.getAttribute('data-name') || '';
      var role = card.getAttribute('data-role') || '';
      var track = card.getAttribute('data-track') || '';
      var img = card.getAttribute('data-img') || '';
      var bio = card.getAttribute('data-bio') || '';

      var titleEl = document.getElementById('speakerModalTitle');
      var roleEl = document.getElementById('speakerModalRole');
      var trackEl = document.getElementById('speakerModalTrack');
      var bioEl = document.getElementById('speakerModalBio');
      var imgEl = document.getElementById('speakerModalImg');

      if (titleEl) titleEl.textContent = name;
      if (roleEl) roleEl.textContent = role;
      if (trackEl) trackEl.textContent = track;
      if (bioEl) bioEl.textContent = bio;
      if (imgEl) {
        imgEl.src = img;
        imgEl.alt = name;
      }
    });
  }
})();
