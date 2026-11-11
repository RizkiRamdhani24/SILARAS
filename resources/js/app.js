import './bootstrap';
import './ui-dialogs';

const landingNavbar = document.querySelector('.silaras-landing-shell .silaras-auth-navbar');

if (landingNavbar) {
    const navLinks = Array.from(landingNavbar.querySelectorAll('.silaras-auth-nav-links a[href*="#"]'));
    const sections = navLinks
        .map((link) => document.getElementById(link.hash.slice(1)))
        .filter((section) => section instanceof HTMLElement);

    const setActiveLink = (activeId) => {
        for (const link of navLinks) {
            if (link.hash.slice(1) === activeId) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        }
    };

    const updateActiveLink = () => {
        const activationLine = landingNavbar.getBoundingClientRect().bottom + 80;
        let activeId = sections[0]?.id;

        for (const section of sections) {
            if (section.getBoundingClientRect().top > activationLine) {
                break;
            }

            activeId = section.id;
        }

        setActiveLink(activeId);
    };

    let updateScheduled = false;
    const scheduleActiveLinkUpdate = () => {
        if (updateScheduled) {
            return;
        }

        updateScheduled = true;
        window.requestAnimationFrame(() => {
            updateActiveLink();
            updateScheduled = false;
        });
    };

    window.addEventListener('scroll', scheduleActiveLinkUpdate, { passive: true });
    window.addEventListener('resize', scheduleActiveLinkUpdate);
    window.addEventListener('hashchange', scheduleActiveLinkUpdate);
    landingNavbar.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const link = target.closest('.silaras-auth-nav-links a[href*="#"]');
        if (link instanceof HTMLAnchorElement && navLinks.includes(link)) {
            setActiveLink(link.hash.slice(1));
        }
    });
    updateActiveLink();
}

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
