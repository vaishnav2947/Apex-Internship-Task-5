/**
 * assets/js/main.js  —  ApexPlanet Internship
 *
 * Sections:
 *  1. DOM Ready init
 *  2. Alert auto-dismiss
 *  3. Bootstrap form validation
 *  4. Bootstrap tooltips
 *  5. Active nav link
 *  6. Password show/hide toggle
 *  7. Password strength meter
 *  8. Confirm-delete helper
 */
'use strict';

// ════════════════════════════════════════════
// 1. DOM READY
// ════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    initAlertDismiss();
    initFormValidation();
    initTooltips();
    initActiveNavLink();
    initPasswordStrength();
    console.log('%cApexPlanet ✓', 'color:#2e86c1;font-weight:700;font-size:14px;');
});


// ════════════════════════════════════════════
// 2. ALERT AUTO-DISMISS  (4 s fade-out)
// ════════════════════════════════════════════
function initAlertDismiss() {
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s ease';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 520);
        }, 4000);
    });
}


// ════════════════════════════════════════════
// 3. BOOTSTRAP FORM VALIDATION
// Prevents submit if HTML5 constraints fail
// ════════════════════════════════════════════
function initFormValidation() {
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}


// ════════════════════════════════════════════
// 4. BOOTSTRAP TOOLTIPS
// ════════════════════════════════════════════
function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(
        el => new bootstrap.Tooltip(el)
    );
}


// ════════════════════════════════════════════
// 5. ACTIVE NAV LINK HIGHLIGHT
// ════════════════════════════════════════════
function initActiveNavLink() {
    const path = window.location.pathname;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        if (link.getAttribute('href') === path) {
            link.classList.add('active');
        }
    });
}


// ════════════════════════════════════════════
// 6. PASSWORD SHOW / HIDE TOGGLE
// Called from HTML: onclick="togglePassword('id', this)"
// ════════════════════════════════════════════
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isText = input.type === 'text';
    input.type   = isText ? 'password' : 'text';
    const icon   = btn.querySelector('i');
    if (icon) icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
}


// ════════════════════════════════════════════
// 7. PASSWORD STRENGTH METER
// Driven by the #password input on register page
// Updates #pwStrengthBar (Bootstrap progress) and #pwStrengthLabel
// ════════════════════════════════════════════
function initPasswordStrength() {
    const input = document.getElementById('password');
    const bar   = document.getElementById('pwStrengthBar');
    const label = document.getElementById('pwStrengthLabel');
    if (!input || !bar || !label) return;   // not on register page

    input.addEventListener('input', () => {
        const val   = input.value;
        const score = calcStrength(val);

        // width: score * 25 %  (0–4 → 0–100%)
        bar.style.width = (score * 25) + '%';

        const configs = [
            { color: '#e74c3c', text: 'Very weak' },
            { color: '#e67e22', text: 'Weak'      },
            { color: '#f39c12', text: 'Fair'      },
            { color: '#27ae60', text: 'Good'      },
            { color: '#1e8449', text: 'Strong ✓'  },
        ];
        bar.style.backgroundColor = configs[score].color;
        label.textContent         = val.length ? configs[score].text : 'Enter a password';
        label.style.color         = configs[score].color;
    });
}

/**
 * Return a score 0–4 based on password criteria.
 * Each criterion adds 1 point.
 */
function calcStrength(pw) {
    if (!pw) return 0;
    let score = 0;
    if (pw.length >= 8)          score++;
    if (/[A-Z]/.test(pw))        score++;
    if (/[0-9]/.test(pw))        score++;
    if (/[\W_]/.test(pw))        score++;
    return score;
}


// ════════════════════════════════════════════
// 8. CONFIRM DELETE DIALOG
// Called inline: onclick="return confirmDelete('title')"
// ════════════════════════════════════════════
function confirmDelete(itemName) {
    return window.confirm(
        `Delete "${itemName}"?\n\nThis cannot be undone.`
    );
}
