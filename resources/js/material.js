/**
 * Material Design ripple effect. Any element with the `md-ripple` class emits a
 * ripple from the click point. The element must be positioned/overflow-hidden,
 * which the `.md-ripple` CSS rule provides.
 */
document.addEventListener('click', (event) => {
    const target = event.target.closest('.md-ripple');
    if (! target) return;

    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);

    const ink = document.createElement('span');
    ink.className = 'md-ripple-ink';
    ink.style.width = ink.style.height = `${size}px`;
    ink.style.left = `${event.clientX - rect.left - size / 2}px`;
    ink.style.top = `${event.clientY - rect.top - size / 2}px`;

    const previous = target.querySelector('.md-ripple-ink');
    if (previous) previous.remove();

    target.appendChild(ink);
    setTimeout(() => ink.remove(), 600);
});

/**
 * Material snackbar (toast). Brief, non-blocking confirmation at the bottom.
 * Usage: window.showSnackbar('Copied to clipboard').
 */
window.showSnackbar = function (message, duration = 2500) {
    let el = document.getElementById('app-snackbar');
    if (! el) {
        el = document.createElement('div');
        el.id = 'app-snackbar';
        el.className = 'snackbar';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        document.body.appendChild(el);
    }

    el.textContent = message;
    clearTimeout(el._hideTimer);
    requestAnimationFrame(() => el.classList.add('show'));
    el._hideTimer = setTimeout(() => el.classList.remove('show'), duration);
};
