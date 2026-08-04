@props([
    'title' => 'Sistem CAT Psikiatri',
    'fullBleed' => false,
])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sistem CAT Psikiatri' }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&display=swap" rel="stylesheet">
    @if (! app()->runningUnitTests())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .app-fallback-dialog {
            border: none;
            padding: 0;
            background: transparent;
            width: min(92vw, 420px);
        }

        .app-fallback-dialog::backdrop {
            background: rgb(15 23 42 / 0.45);
        }

        .app-fallback-dialog-card {
            margin: 0;
            border: 1px solid #d7ded4;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 20px 40px rgb(13 36 25 / 0.22);
            padding: 1rem;
            color: #1a1a17;
        }

        .app-fallback-dialog-title {
            margin: 0 0 0.4rem;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .app-fallback-dialog-text {
            margin: 0;
            color: #55534a;
            line-height: 1.5;
        }

        .app-fallback-dialog-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.6rem;
            justify-content: flex-end;
        }
    </style>
    <script>
        const appFallbackDialog = {
            escapeHtml(value = '') {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            },
            confirm({
                title = 'Konfirmasi',
                text = 'Lanjutkan tindakan ini?',
                confirmButtonText = 'Ya, lanjutkan',
                cancelButtonText = 'Batal',
            } = {}) {
                return new Promise((resolve) => {
                    const safeTitle = appFallbackDialog.escapeHtml(title);
                    const safeText = appFallbackDialog.escapeHtml(text);
                    const safeConfirmButtonText = appFallbackDialog.escapeHtml(confirmButtonText);
                    const safeCancelButtonText = appFallbackDialog.escapeHtml(cancelButtonText);
                    const dialog = document.createElement('dialog');
                    dialog.className = 'app-fallback-dialog';
                    dialog.innerHTML = `
                        <form method="dialog" class="app-fallback-dialog-card">
                            <h2 class="app-fallback-dialog-title">${safeTitle}</h2>
                            <p class="app-fallback-dialog-text">${safeText}</p>
                            <div class="app-fallback-dialog-actions">
                                <button type="button" class="btn btn-ghost" data-cancel>${safeCancelButtonText}</button>
                                <button type="button" class="btn btn-primary" data-confirm>${safeConfirmButtonText}</button>
                            </div>
                        </form>
                    `;

                    document.body.appendChild(dialog);
                    const cleanup = () => dialog.remove();
                    const cancelBtn = dialog.querySelector('[data-cancel]');
                    const confirmBtn = dialog.querySelector('[data-confirm]');

                    dialog.addEventListener('close', cleanup, { once: true });
                    dialog.addEventListener('cancel', (event) => {
                        event.preventDefault();
                        resolve(false);
                        dialog.close();
                    });

                    cancelBtn?.addEventListener('click', () => {
                        resolve(false);
                        dialog.close();
                    }, { once: true });

                    confirmBtn?.addEventListener('click', () => {
                        resolve(true);
                        dialog.close();
                    }, { once: true });

                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        resolve(window.confirm(text));
                        cleanup();
                    }
                });
            },
            alert({
                title = 'Informasi',
                text = '',
                confirmButtonText = 'OK',
            } = {}) {
                return new Promise((resolve) => {
                    const safeTitle = appFallbackDialog.escapeHtml(title);
                    const safeText = appFallbackDialog.escapeHtml(text);
                    const safeConfirmButtonText = appFallbackDialog.escapeHtml(confirmButtonText);
                    const dialog = document.createElement('dialog');
                    dialog.className = 'app-fallback-dialog';
                    dialog.innerHTML = `
                        <form method="dialog" class="app-fallback-dialog-card">
                            <h2 class="app-fallback-dialog-title">${safeTitle}</h2>
                            <p class="app-fallback-dialog-text">${safeText}</p>
                            <div class="app-fallback-dialog-actions">
                                <button type="button" class="btn btn-primary" data-confirm>${safeConfirmButtonText}</button>
                            </div>
                        </form>
                    `;

                    document.body.appendChild(dialog);
                    const cleanup = () => dialog.remove();
                    const confirmBtn = dialog.querySelector('[data-confirm]');

                    dialog.addEventListener('close', cleanup, { once: true });
                    dialog.addEventListener('cancel', (event) => {
                        event.preventDefault();
                        resolve();
                        dialog.close();
                    });

                    confirmBtn?.addEventListener('click', () => {
                        resolve();
                        dialog.close();
                    }, { once: true });

                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        window.alert(text || title);
                        resolve();
                        cleanup();
                    }
                });
            },
        };

        window.AppDialog = {
            async confirm({
                title = 'Konfirmasi',
                text = 'Lanjutkan tindakan ini?',
                icon = 'warning',
                confirmButtonText = 'Ya, lanjutkan',
                cancelButtonText = 'Batal',
            } = {}) {
                if (typeof window.Swal === 'undefined' || typeof window.Swal.fire !== 'function') {
                    return appFallbackDialog.confirm({
                        title,
                        text,
                        confirmButtonText,
                        cancelButtonText,
                    });
                }

                const unanswered = Number((text.match(/\d+/) || [0])[0]);

                const result = await window.Swal.fire({
                    title: 'Konfirmasi Pengumpulan Tes',

                    html: `
                        <div class="exam-confirm-body">

                            ${
                                unanswered > 0
                                ? `
                                <div class="exam-warning-box">
                                    <div class="warning-icon">⚠️</div>

                                    <div class="warning-content">
                                        <p class="warning-title">
                                            Masih terdapat
                                        </p>

                                        <div class="warning-count">
                                            ${unanswered}
                                        </div>

                                        <p class="warning-subtitle">
                                            soal yang belum dijawab
                                        </p>
                                    </div>
                                </div>
                                `
                                : `
                                <div class="exam-success-box">
                                    <div class="success-icon">✅</div>

                                    <p class="warning-title">
                                        Semua soal telah dijawab.
                                    </p>
                                </div>
                                `
                            }

                            <div class="exam-info-box">
                                Setelah tes dikirim, <strong>jawaban tidak dapat diubah kembali.</strong>
                            </div>

                        </div>
                    `,

                    showCancelButton: true,

                    confirmButtonText,
                    cancelButtonText,

                    reverseButtons: true,
                    focusCancel: true,

                    buttonsStyling: false,

                    allowOutsideClick: false,
                    allowEscapeKey: false,

                    customClass: {
                        popup: 'exam-popup',
                        title: 'exam-title',
                        confirmButton: 'exam-btn-confirm',
                        cancelButton: 'exam-btn-cancel'
                    }
                });

                return result.isConfirmed === true;
            },

            async alert({
                title = 'Informasi',
                text = '',
                icon = 'info',
                confirmButtonText = 'OK',
            } = {}) {
                if (typeof window.Swal === 'undefined' || typeof window.Swal.fire !== 'function') {
                    await appFallbackDialog.alert({
                        title,
                        text,
                        confirmButtonText,
                    });
                    return;
                }

                await window.Swal.fire({
                    title,
                    text,
                    icon,
                    confirmButtonText,
                    confirmButtonColor: '#143829',
                });
            },
        };

        document.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const confirmMessage = form.dataset.confirmMessage;
            if (!confirmMessage || form.dataset.confirmed === '1') return;

            event.preventDefault();
            const approved = await window.AppDialog.confirm({
                title: form.dataset.confirmTitle || 'Konfirmasi',
                text: confirmMessage,
                icon: form.dataset.confirmIcon || 'warning',
                confirmButtonText: form.dataset.confirmButtonText || 'Ya, lanjutkan',
                cancelButtonText: form.dataset.cancelButtonText || 'Batal',
            });

            if (!approved) return;

            const confirmMessage2 = form.dataset.confirmSecondMessage;
            if (confirmMessage2) {
                const approvedAgain = await window.AppDialog.confirm({
                    title: form.dataset.confirmSecondTitle || 'Konfirmasi Sekali Lagi',
                    text: confirmMessage2,
                    icon: form.dataset.confirmSecondIcon || 'error',
                    confirmButtonText: form.dataset.confirmSecondButtonText || 'Ya, saya benar-benar yakin',
                    cancelButtonText: form.dataset.cancelButtonText || 'Batal',
                });

                if (!approvedAgain) return;
            }

            form.dataset.confirmed = '1';
            form.submit();
            delete form.dataset.confirmed;
        }, true);
    </script>
</head>
<body class="{{ $fullBleed ? 'is-full-bleed' : '' }}">
<a href="#main-content" class="skip-link">Lewati ke konten utama</a>
<div class="page-bg"></div>
<main id="main-content" class="main-shell">
    {{ $slot }}
</main>
@unless ($fullBleed)
    <footer class="app-footer">
        <p>&copy; {{ now()->year }}. Made with &hearts; by IT RSBL</p>
    </footer>
@endunless
</body>
</html>
