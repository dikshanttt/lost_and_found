/**
 * CivicFind – main.js
 */
document.addEventListener('DOMContentLoaded', () => {
    /* ── Mobile nav toggle ─────────────────────────── */
    const toggle = document.getElementById('navToggle');
    const menu   = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', () => menu.classList.toggle('show'));
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
    }

    /* ── User dropdown ─────────────────────────────── */
    const userBtn  = document.getElementById('userDropToggle');
    const userMenu = document.getElementById('userDropMenu');
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
        document.addEventListener('click', () => userMenu.classList.remove('show'));
    }

    /* ── Modals ────────────────────────────────────── */
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modal);
            if (modal) modal.classList.add('show');
        });
    });
    document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').classList.remove('show');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('show');
        });
    });

    /* ── File upload preview ───────────────────────── */
    const fileArea = document.querySelector('.file-upload-area');
    if (fileArea) {
        const input = fileArea.querySelector('input[type="file"]');
        const preview = fileArea.querySelector('.file-preview');

        fileArea.addEventListener('click', () => input.click());
        fileArea.addEventListener('dragover', (e) => { e.preventDefault(); fileArea.style.borderColor = '#1d4ed8'; });
        fileArea.addEventListener('dragleave', () => { fileArea.style.borderColor = ''; });
        fileArea.addEventListener('drop', (e) => {
            e.preventDefault(); fileArea.style.borderColor = '';
            input.files = e.dataTransfer.files;
            showPreview(input.files[0]);
        });
        input.addEventListener('change', () => { if (input.files[0]) showPreview(input.files[0]); });

        function showPreview(file) {
            if (!file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                fileArea.querySelector('.file-upload-text').textContent = file.name;
            };
            reader.readAsDataURL(file);
        }
    }

    /* ── Type toggle (report form) ─────────────────── */
    document.querySelectorAll('.type-toggle input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.type-toggle label').forEach(l => l.classList.remove('selected'));
            if (radio.checked) radio.parentElement.querySelector('label')?.classList.add('selected');
        });
    });

    /* ── Auto-dismiss flash after 5s ───────────────── */
    document.querySelectorAll('.flash').forEach(f => {
        setTimeout(() => f.style.opacity = '0', 5000);
        setTimeout(() => f.remove(), 5500);
    });
});
