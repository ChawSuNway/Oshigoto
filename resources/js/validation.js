/**
 * Lightweight inline form validation built on native HTML5 constraint
 * validation. Opt in by adding `data-validate` to a <form>.
 *
 * Behaviour:
 *  - Marks a field with `.cv-invalid` (red border) once the user has touched it
 *    and it fails its constraints (required, type=email, min/max, maxlength, …).
 *  - On submit, blocks if invalid, flags every offending field, and lets the
 *    browser show its native message on (and focus) the first invalid control.
 *
 * It only toggles a class and reads validity — it never injects or removes DOM
 * nodes, so it coexists safely with Alpine's dynamic (x-for) field lists.
 */

function inValidatedForm(el) {
    return el && el.form && typeof el.form.matches === 'function' && el.form.matches('[data-validate]');
}

function mark(el) {
    if (!el.willValidate) return;
    const touched = el.dataset.cvTouched === '1';
    if (touched && !el.checkValidity()) {
        el.classList.add('cv-invalid');
    } else {
        el.classList.remove('cv-invalid');
    }
}

function touchAndMark(event) {
    const el = event.target;
    if (!inValidatedForm(el)) return;
    el.dataset.cvTouched = '1';
    mark(el);
}

document.addEventListener('input', touchAndMark, true);
document.addEventListener('blur', touchAndMark, true);

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form || typeof form.matches !== 'function' || !form.matches('[data-validate]')) return;

    if (form.checkValidity()) return;

    event.preventDefault();
    form.querySelectorAll('input, select, textarea').forEach((el) => {
        if (el.willValidate) {
            el.dataset.cvTouched = '1';
            mark(el);
        }
    });

    // Shows the browser's native message on the first invalid control and focuses it.
    form.reportValidity();
}, true);
