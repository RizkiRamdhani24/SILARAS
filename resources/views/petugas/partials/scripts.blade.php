{{-- JavaScript bersama untuk semua halaman petugas (tanpa dependensi tambahan). --}}
<script>
(() => {
    // Menu samping (layar kecil)
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const setSidebar = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
    };
    document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) =>
        btn.addEventListener('click', () => setSidebar(sidebar.classList.contains('-translate-x-full'))));
    overlay.addEventListener('click', () => setSidebar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

    // Notifikasi singkat
    let toastTimer;
    window.silarasToast = (pesan) => {
        const el = document.getElementById('toast');
        el.textContent = pesan;
        el.classList.remove('opacity-0', 'translate-y-2');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.add('opacity-0', 'translate-y-2'), 3000);
    };

    // Formulir contoh: belum terhubung ke backend
    document.querySelectorAll('form[data-demo]').forEach((form) =>
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            window.silarasToast(form.dataset.demo || 'Tampilan contoh: belum terhubung ke backend.');
        }));
    document.querySelectorAll('[data-demo-click]').forEach((btn) =>
        btn.addEventListener('click', () => window.silarasToast(btn.dataset.demoClick)));

    // Tabel: tab filter + pencarian digabung
    const terapkan = (tabel) => tabel.querySelectorAll('tbody tr').forEach((tr) => {
        tr.hidden = Boolean(tr.dataset.hideTab || tr.dataset.hideSearch);
    });

    document.querySelectorAll('[data-tabs]').forEach((grup) => {
        const tabel = document.querySelector(grup.dataset.tabs);
        grup.querySelectorAll('[data-tab]').forEach((btn) => btn.addEventListener('click', () => {
            grup.querySelectorAll('[data-tab]').forEach((b) => { b.dataset.active = String(b === btn); b.setAttribute('aria-pressed', String(b === btn)); });
            tabel.querySelectorAll('tbody tr').forEach((tr) => {
                const kategori = (tr.dataset.cat || '').split(' ');
                if (btn.dataset.tab === 'all' || kategori.includes(btn.dataset.tab)) delete tr.dataset.hideTab;
                else tr.dataset.hideTab = '1';
            });
            terapkan(tabel);
        }));
    });

    document.querySelectorAll('[data-search]').forEach((input) => {
        const tabel = document.querySelector(input.dataset.search);
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            tabel.querySelectorAll('tbody tr').forEach((tr) => {
                if (!q || tr.textContent.toLowerCase().includes(q)) delete tr.dataset.hideSearch;
                else tr.dataset.hideSearch = '1';
            });
            terapkan(tabel);
        });
    });

    // Centang semua baris
    document.querySelectorAll('[data-check-all]').forEach((master) => {
        const tabel = document.querySelector(master.dataset.checkAll);
        master.addEventListener('change', () => tabel.querySelectorAll('[data-row-check]').forEach((c) => {
            if (!c.closest('tr').hidden && !c.disabled) c.checked = master.checked;
        }));
    });
})();
</script>
