import './bootstrap';

document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const toggle = target.closest('[data-password-toggle]');

    if (!toggle) {
        return;
    }

    const input = document.getElementById(toggle.getAttribute('aria-controls'));

    if (!(input instanceof HTMLInputElement)) {
        console.error('Password toggle is missing its input.');
        return;
    }

    const isVisible = input.type === 'password';
    input.type = isVisible ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(isVisible));
    toggle.setAttribute('aria-label', isVisible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});
