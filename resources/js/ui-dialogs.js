const dialogMarkup = `
    <section class="silaras-dialog-card" role="alertdialog" aria-modal="true" aria-labelledby="silaras-dialog-title" aria-describedby="silaras-dialog-message">
        <div class="silaras-dialog-icon" data-dialog-icon aria-hidden="true">
            <span class="material-symbols-rounded">info</span>
        </div>
        <h2 id="silaras-dialog-title"></h2>
        <p id="silaras-dialog-message"></p>
        <div class="silaras-dialog-actions">
            <button type="button" class="silaras-dialog-button is-secondary" data-dialog-cancel>Batal</button>
            <button type="button" class="silaras-dialog-button is-primary" data-dialog-confirm>Ya, lanjutkan</button>
        </div>
    </section>
`;

let activeDialog = null;
let pendingForm = null;
let pendingSubmitter = null;
let previousFocus = null;

const getDialog = () => {
    let dialog = document.querySelector('[data-silaras-dialog]');
    if (dialog) return dialog;

    const wrapper = document.createElement('div');
    wrapper.dataset.silarasDialog = '';
    wrapper.className = 'silaras-dialog-backdrop';
    wrapper.setAttribute('aria-hidden', 'true');
    wrapper.innerHTML = dialogMarkup;
    document.body.append(wrapper);
    return wrapper;
};

const closeDialog = (confirmed) => {
    if (!activeDialog) return;

    const { wrapper } = activeDialog;
    wrapper.classList.remove('is-open');
    wrapper.setAttribute('aria-hidden', 'true');
    activeDialog = null;

    if (confirmed && pendingForm?.isConnected) {
        const form = pendingForm;
        const submitter = pendingSubmitter;
        pendingForm = null;
        pendingSubmitter = null;
        form.dataset.confirmedSubmit = 'true';
        if (submitter instanceof HTMLElement && submitter.isConnected) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }
    } else {
        pendingForm = null;
        pendingSubmitter = null;
    }

    if (previousFocus instanceof HTMLElement && previousFocus.isConnected) {
        previousFocus.focus();
    }
    previousFocus = null;
};

const openDialog = (message, options = {}) => new Promise((resolve) => {
    if (activeDialog) activeDialog.cancel();

    const wrapper = getDialog();
    const title = wrapper.querySelector('#silaras-dialog-title');
    const description = wrapper.querySelector('#silaras-dialog-message');
    const confirmButton = wrapper.querySelector('[data-dialog-confirm]');
    const cancelButton = wrapper.querySelector('[data-dialog-cancel]');
    const icon = wrapper.querySelector('[data-dialog-icon]');
    const destructive = options.variant === 'danger';

    title.textContent = options.title || (destructive ? 'Konfirmasi tindakan' : 'Mohon konfirmasi');
    description.textContent = message;
    confirmButton.textContent = options.confirmLabel || (destructive ? 'Ya, lanjutkan' : 'Ya, lanjutkan');
    confirmButton.classList.toggle('is-danger', destructive);
    icon.classList.toggle('is-danger', destructive);
    icon.querySelector('span').textContent = destructive ? 'warning' : 'info';
    wrapper.setAttribute('aria-hidden', 'false');
    wrapper.classList.add('is-open');

    const finish = (confirmed) => {
        cancelButton.removeEventListener('click', cancel);
        confirmButton.removeEventListener('click', confirm);
        wrapper.removeEventListener('click', backdropClick);
        document.removeEventListener('keydown', onKeydown);
        closeDialog(confirmed);
        resolve(confirmed);
    };
    const cancel = () => finish(false);
    const confirm = () => finish(true);
    const backdropClick = (event) => {
        if (event.target === wrapper) cancel();
    };
    const onKeydown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            cancel();
        } else if (event.key === 'Tab') {
            event.preventDefault();
            (document.activeElement === cancelButton ? confirmButton : cancelButton).focus();
        }
    };

    cancelButton.addEventListener('click', cancel);
    confirmButton.addEventListener('click', confirm);
    wrapper.addEventListener('click', backdropClick);
    document.addEventListener('keydown', onKeydown);
    previousFocus = document.activeElement;
    activeDialog = { wrapper, cancel };
    confirmButton.focus();
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

    if (form.dataset.confirmedSubmit === 'true') {
        delete form.dataset.confirmedSubmit;
        return;
    }

    event.preventDefault();
    pendingForm = form;
    pendingSubmitter = event.submitter;
    await openDialog(form.dataset.confirm, {
        title: form.dataset.confirmTitle,
        confirmLabel: form.dataset.confirmLabel,
        variant: form.dataset.confirmVariant,
    });
});

window.silarasConfirm = (message, options = {}) => openDialog(message, options);
