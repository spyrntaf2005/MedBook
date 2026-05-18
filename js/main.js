/**
 * js/main.js
 * Validation φόρμας & βοηθητικές λειτουργίες
 */

/* ===== FORM VALIDATION ===== */
function validateBookingForm() {
  const form = document.getElementById('bookingForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    let isValid = true;

    // Καθαρισμός προηγούμενων σφαλμάτων
    document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
    document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

    // Ονοματεπώνυμο
    const name = document.getElementById('patient_name');
    if (!name.value.trim() || name.value.trim().length < 3) {
      showError(name, 'errorName', 'Παρακαλώ εισάγετε το ονοματεπώνυμό σας (τουλάχιστον 3 χαρακτήρες).');
      isValid = false;
    }

    // Τηλέφωνο (10 ψηφία)
    const phone = document.getElementById('patient_phone');
    if (!phone.value.trim() || !/^[0-9]{10}$/.test(phone.value.trim())) {
      showError(phone, 'errorPhone', 'Παρακαλώ εισάγετε έγκυρο τηλέφωνο (10 ψηφία).');
      isValid = false;
    }

    // Email
    const email = document.getElementById('patient_email');
    if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      showError(email, 'errorEmail', 'Παρακαλώ εισάγετε έγκυρη διεύθυνση email.');
      isValid = false;
    }

    // Λόγος επίσκεψης
    const reason = document.getElementById('visit_reason');
    if (!reason.value.trim() || reason.value.trim().length < 5) {
      showError(reason, 'errorReason', 'Παρακαλώ περιγράψτε τον λόγο επίσκεψης.');
      isValid = false;
    }

    // Ημερομηνία (δεν επιτρέπεται παρελθόν)
    const date = document.getElementById('appointment_date');
    const today = new Date().toISOString().split('T')[0];
    if (!date.value || date.value < today) {
      showError(date, 'errorDate', 'Παρακαλώ επιλέξτε μελλοντική ημερομηνία.');
      isValid = false;
    }

    // Ώρα
    const time = document.getElementById('appointment_time');
    if (!time.value) {
      showError(time, 'errorTime', 'Παρακαλώ επιλέξτε ώρα ραντεβού.');
      isValid = false;
    }

    if (!isValid) {
      e.preventDefault();
      // Scroll στο πρώτο σφάλμα
      const firstError = document.querySelector('.error');
      if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      // Loading state
      const btn = form.querySelector('[type="submit"]');
      if (btn) {
        btn.innerHTML = '<span class="spinner"></span> Αποστολή...';
        btn.disabled = true;
      }
    }
  });
}

function showError(input, errorId, message) {
  input.classList.add('error');
  const errorEl = document.getElementById(errorId);
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.classList.add('visible');
  }
}

/* ===== LOGIN FORM VALIDATION ===== */
function validateLoginForm() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    let isValid = true;
    document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
    document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

    const username = document.getElementById('username');
    if (!username.value.trim()) {
      showError(username, 'errorUsername', 'Παρακαλώ εισάγετε το όνομα χρήστη.');
      isValid = false;
    }

    const password = document.getElementById('password');
    if (!password.value.trim()) {
      showError(password, 'errorPassword', 'Παρακαλώ εισάγετε τον κωδικό πρόσβασης.');
      isValid = false;
    }

    if (!isValid) e.preventDefault();
  });
}

/* ===== STATUS UPDATE (AJAX) ===== */
function initStatusButtons() {
  document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function () {
      const id     = this.dataset.id;
      const status = this.dataset.status;
      const label  = status === 'confirmed' ? 'Επιβεβαιωμένο' : status === 'cancelled' ? 'Ακυρωμένο' : 'Εκκρεμεί';

      if (!confirm(`Αλλαγή κατάστασης σε "${label}";`)) return;

      const btn = this;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span>';

      fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}&csrf_token=${encodeURIComponent(getCsrfToken())}`
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            alert('Σφάλμα: ' + (data.message || 'Άγνωστο σφάλμα'));
            btn.disabled = false;
          }
        })
        .catch(() => {
          alert('Σφάλμα δικτύου. Παρακαλώ δοκιμάστε ξανά.');
          btn.disabled = false;
        });
    });
  });
}

/* ===== DELETE CONFIRM ===== */
function initDeleteButtons() {
  document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
    btn.addEventListener('click', function (e) {
      if (!confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτό το ραντεβού; Η ενέργεια δεν αναιρείται.')) {
        e.preventDefault();
      }
    });
  });
}

/* ===== CSRF TOKEN HELPER ===== */
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

/* ===== DATE MIN (σήμερα) ===== */
function setDateMin() {
  const dateInput = document.getElementById('appointment_date');
  if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
  }
}

/* ===== TABLE SEARCH ===== */
function initTableSearch() {
  const searchInput = document.getElementById('tableSearch');
  if (!searchInput) return;

  searchInput.addEventListener('input', function () {
    const term = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(term) ? '' : 'none';
    });
  });
}

/* ===== MODAL ===== */
function initModals() {
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.modalOpen);
      if (modal) modal.classList.add('active');
    });
  });

  document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
    el.addEventListener('click', function (e) {
      if (e.target === this || this.classList.contains('modal-close')) {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
      }
    });
  });
}

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', function () {
  validateBookingForm();
  validateLoginForm();
  initStatusButtons();
  initDeleteButtons();
  setDateMin();
  initTableSearch();
  initModals();
});
