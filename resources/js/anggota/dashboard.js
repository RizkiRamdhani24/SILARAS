import '../ui-dialogs';

// =========================================
// MODULE: DOM references
// Purpose: Resolve shared controls used by member pages.
// =========================================
const toggle = document.querySelector('[data-member-menu-toggle]');
const close = document.querySelector('[data-member-menu-close]');
const sidebar = document.getElementById('member-sidebar');
const backdrop = document.querySelector('[data-member-menu-backdrop]');
let lastFocusedElement = null;

// =========================================
// MODULE: Sidebar and drawer state
// Purpose: Keep mobile navigation state synchronized with the DOM.
// =========================================
const sidebarFocusables = () => {
    if (!(sidebar instanceof HTMLElement)) {
        return [];
    }

    return [...sidebar.querySelectorAll('a[href], button:not([disabled])')];
};

const setSidebarOpen = (open, restoreFocus = true) => {
    if (!(toggle instanceof HTMLButtonElement) || !(sidebar instanceof HTMLElement)) {
        return;
    }

    if (open) {
        lastFocusedElement = document.activeElement;
    }

    document.body.classList.toggle('member-menu-open', open);
    sidebar.setAttribute('aria-hidden', String(!open && window.innerWidth <= 780));
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Tutup navigasi anggota' : 'Buka navigasi anggota');

    if (open) {
        (close instanceof HTMLButtonElement ? close : sidebarFocusables()[0])?.focus();
    } else if (restoreFocus) {
        (lastFocusedElement instanceof HTMLElement ? lastFocusedElement : toggle).focus();
        lastFocusedElement = null;
    }
};

toggle?.addEventListener('click', () => {
    setSidebarOpen(toggle.getAttribute('aria-expanded') !== 'true');
});

close?.addEventListener('click', () => setSidebarOpen(false));
backdrop?.addEventListener('click', () => setSidebarOpen(false));

sidebar?.addEventListener('click', (event) => {
    if (event.target instanceof Element && event.target.closest('a')) {
        setSidebarOpen(false);
    }
});

// =========================================
// MODULE: Keyboard and focus management
// Purpose: Trap focus in the open mobile drawer and restore it on close.
// =========================================
document.addEventListener('keydown', (event) => {
    if (!(toggle instanceof HTMLButtonElement) || toggle.getAttribute('aria-expanded') !== 'true') {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        setSidebarOpen(false);
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusables = sidebarFocusables();
    const first = focusables[0];
    const last = focusables.at(-1);

    if (!first || !last) {
        return;
    }

    if (!sidebar?.contains(document.activeElement)) {
        event.preventDefault();
        first.focus();
    } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});

// =========================================
// MODULE: Responsive synchronization
// Purpose: Keep drawer ARIA state aligned with the current breakpoint.
// =========================================
const desktopMediaQuery = window.matchMedia('(min-width: 781px)');
desktopMediaQuery.addEventListener('change', (event) => {
    if (event.matches) {
        setSidebarOpen(false, false);
        sidebar?.setAttribute('aria-hidden', 'false');
    } else {
        sidebar?.setAttribute('aria-hidden', 'true');
    }
});

if (!desktopMediaQuery.matches) {
    sidebar?.setAttribute('aria-hidden', 'true');
}

// =========================================
// MODULE: Loan request preview
// Purpose: Provide visual-only feedback without submitting a transaction.
// =========================================
const loanForm = document.querySelector('[data-loan-request-form]');
const loanDate = document.querySelector('[data-loan-date]');
const loanFeedback = document.querySelector('[data-loan-feedback]');
const validateLoanPlan = () => {
    if (!(loanDate instanceof HTMLInputElement) || !(loanFeedback instanceof HTMLElement)) {
        return;
    }

    if (!loanDate.value) {
        loanDate.setAttribute('aria-invalid', 'true');
        loanFeedback.textContent = 'Pilih tanggal rencana pengambilan untuk melengkapi pengecekan visual.';
        return;
    }

    loanDate.removeAttribute('aria-invalid');
    loanFeedback.textContent = 'Rencana pengambilan siap ditinjau. Pengajuan masih menunggu integrasi layanan.';
};

loanForm?.addEventListener('input', validateLoanPlan);
loanForm?.addEventListener('change', validateLoanPlan);

const paymentProofInput = document.querySelector('[data-payment-proof]');
const proofFileName = document.querySelector('[data-proof-filename]');
const proofPreview = document.querySelector('[data-proof-preview]');
const proofError = document.querySelector('[data-proof-error]');
let proofPreviewUrl = null;

paymentProofInput?.addEventListener('change', () => {
    if (!(paymentProofInput instanceof HTMLInputElement)) return;

    const file = paymentProofInput.files?.[0];
    paymentProofInput.setCustomValidity('');
    if (proofError instanceof HTMLElement) proofError.textContent = '';
    if (proofPreview instanceof HTMLImageElement) {
        proofPreview.hidden = true;
        proofPreview.removeAttribute('src');
    }
    if (proofPreviewUrl) {
        URL.revokeObjectURL(proofPreviewUrl);
        proofPreviewUrl = null;
    }
    if (!file) {
        if (proofFileName instanceof HTMLElement) proofFileName.textContent = 'Belum ada file dipilih';
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png'];
    let error = '';
    if (!allowedTypes.includes(file.type)) {
        error = 'Pilih file gambar JPG, JPEG, atau PNG.';
    } else if (file.size > 2 * 1024 * 1024) {
        error = 'Ukuran file maksimal 2 MB.';
    }

    if (proofFileName instanceof HTMLElement) proofFileName.textContent = file.name;
    if (error) {
        paymentProofInput.setCustomValidity(error);
        if (proofError instanceof HTMLElement) proofError.textContent = error;
        if (proofPreview instanceof HTMLImageElement) proofPreview.hidden = true;
        return;
    }

    if (proofPreview instanceof HTMLImageElement) {
        proofPreviewUrl = URL.createObjectURL(file);
        proofPreview.src = proofPreviewUrl;
        proofPreview.hidden = false;
    }
});
