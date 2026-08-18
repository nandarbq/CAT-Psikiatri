@php
    $pageTitle = $pageTitle ?? 'Sesi Tes';
    $brandKicker = $brandKicker ?? 'RSUD Blambangan';
    $brandName = $brandName ?? 'CAT Psikiatri';
    $examKicker = $examKicker ?? 'Tes Berbantuan Komputer';
    $sessionTitle = $sessionTitle ?? 'Sesi Tes';
    $metaChips = is_array($metaChips ?? null) ? $metaChips : [];
    $saveUrl = $saveUrl ?? route('exam.save-answer');
    $submitUrl = $submitUrl ?? route('exam.submit');
    $deadlineAt = $deadlineAt ?? now()->addMinutes(1)->toIso8601String();
    $requiresLocation = (bool) ($requiresLocation ?? false);
    $backUrl = $backUrl ?? null;
    $backLabel = $backLabel ?? 'Kembali';
    $finishButtonText = $finishButtonText ?? 'Akhiri Tes';
    $confirmSubmitTitle = $confirmSubmitTitle ?? 'Akhiri tes sekarang?';
    $confirmSubmitConfirmButton = $confirmSubmitConfirmButton ?? 'Ya, akhiri tes';
    $confirmSubmitCancelButton = $confirmSubmitCancelButton ?? 'Lanjut mengerjakan';
    $confirmSubmitTail = $confirmSubmitTail ?? 'Setelah diakhiri, jawaban tidak bisa diubah lagi.';
    $submitFailureStatus = $submitFailureStatus ?? 'Pengiriman jawaban akhir gagal. Pastikan koneksi stabil lalu ulangi.';
    $submitFailureAlertTitle = $submitFailureAlertTitle ?? 'Pengiriman gagal';
    $submitFailureAlertText = $submitFailureAlertText ?? 'Pastikan koneksi stabil lalu coba lagi.';
@endphp

<x-layouts.app :title="$pageTitle">
    <section
        x-data="examEngine({
            questions: @js($questions),
            answers: @js($answers),
            currentIndex: {{ $currentIndex }},
            saveUrl: @js($saveUrl),
            submitUrl: @js($submitUrl),
            deadlineAt: @js($deadlineAt),
            requiresLocation: @js($requiresLocation),
            finishButtonText: @js($finishButtonText),
            confirmSubmitTitle: @js($confirmSubmitTitle),
            confirmSubmitConfirmButton: @js($confirmSubmitConfirmButton),
            confirmSubmitCancelButton: @js($confirmSubmitCancelButton),
            confirmSubmitTail: @js($confirmSubmitTail),
            submitFailureStatus: @js($submitFailureStatus),
            submitFailureAlertTitle: @js($submitFailureAlertTitle),
            submitFailureAlertText: @js($submitFailureAlertText)
        })"
        class="panel exam-shell exam-modern"
    >
        <header class="exam-header">
            <div class="exam-header-main">
                @if ($backUrl)
                    <a href="{{ $backUrl }}" class="btn btn-ghost" style="width: fit-content;">{{ $backLabel }}</a>
                @endif
                <div class="org-brand-inline exam-org-brand">
                    <img src="{{ asset('assets/logo-rsud-blambangan.png') }}" alt="Logo RSUD Blambangan" class="org-brand-logo">
                    <div>
                        <p class="org-brand-kicker">{{ $brandKicker }}</p>
                        <p class="org-brand-name">{{ $brandName }}</p>
                    </div>
                </div>
                <p class="exam-kicker">{{ $examKicker }}</p>
                <h1 class="exam-session-title">{{ $sessionTitle }}</h1>
                @if ($metaChips !== [])
                    <div class="exam-meta-row">
                        @foreach ($metaChips as $chip)
                            <span class="exam-chip">{{ $chip }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="exam-timer-card" :class="timerStateClass" aria-live="polite">
                <p class="time-caption">Sisa Waktu</p>
                <strong class="time-value" x-text="timeLeftLabel"></strong>
                <p class="time-subcaption" x-text="`${unansweredCount} soal belum dijawab`"></p>
            </div>
        </header>

        <div class="exam-workspace" :class="questionNavOpen ? '' : 'is-question-nav-hidden'">
            <section class="exam-main">
                <button
                    type="button"
                    class="btn btn-ghost exam-nav-toggle"
                    @click="toggleQuestionNav"
                    :aria-expanded="String(questionNavOpen)"
                    aria-controls="exam-question-nav-panel"
                >
                    <span class="exam-nav-toggle-icon" :class="questionNavOpen ? '' : 'is-collapsed'" aria-hidden="true">&gt;</span>
                    <span x-show="!isMobileViewport" x-text="questionNavOpen ? 'Sembunyikan Navigasi Soal' : 'Tampilkan Navigasi Soal'"></span>
                    <span x-show="isMobileViewport" x-text="questionNavOpen ? 'Tutup Soal' : `Soal (${unansweredCount})`"></span>
                </button>

                <div class="progress-wrap">
                    <div class="progress-label">
                        <span x-text="`Soal ${currentQuestionNumber} dari ${questions.length}`"></span>
                        <span x-text="`${answeredCount} terjawab • ${unansweredCount} belum`"></span>
                    </div>
                    <div class="progress-bar" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100">
                        <span :style="`width:${progressPercent}%`"></span>
                    </div>
                </div>

                <article class="question-card participant-question-card" x-show="currentQuestion" x-ref="questionCard">
                    <div class="question-head">
                        <span class="question-number-badge" x-text="`No. ${currentQuestionNumber}`"></span>
                        <p class="question-category" x-text="currentQuestion?.category || 'Umum'"></p>
                    </div>
                    <h2 class="question-text" x-text="currentQuestion?.question"></h2>

                    <div class="answer-grid">
                        <button
                            type="button"
                            class="btn-answer"
                            :class="isAnswered(true) ? 'active-yes' : ''"
                            @click="selectAnswer(true)"
                            :disabled="isSaving || isSubmitting"
                        >YA</button>
                        <button
                            type="button"
                            class="btn-answer"
                            :class="isAnswered(false) ? 'active-no' : ''"
                            @click="selectAnswer(false)"
                            :disabled="isSaving || isSubmitting"
                        >TIDAK</button>
                    </div>
                </article>

                <p class="save-status" :class="statusType" x-text="saveStatus" aria-live="polite"></p>

                <div class="exam-actions">
                    <button
                        type="button"
                        class="exam-nav-icon-btn"
                        @click="prevQuestion"
                        :disabled="currentIndex === 0 || isSaving || isSubmitting"
                        title="Soal sebelumnya"
                        aria-label="Soal sebelumnya"
                    >
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false"><path d="M15 6l-6 6 6 6"></path></svg>
                    </button>
                    <button
                        type="button"
                        class="exam-nav-icon-btn"
                        @click="nextQuestion"
                        :disabled="currentIndex >= questions.length - 1 || isSaving || isSubmitting"
                        title="Soal berikutnya"
                        aria-label="Soal berikutnya"
                    >
                        <svg viewBox="0 0 24 24" role="presentation" focusable="false"><path d="M9 6l6 6-6 6"></path></svg>
                    </button>
                </div>
            </section>

            <aside
                id="exam-question-nav-panel"
                class="exam-sidebar-nav"
                :class="questionNavOpen ? '' : 'is-collapsed'"
                aria-label="Navigasi nomor soal"
                :aria-hidden="String(!questionNavOpen)"
            >
                <div class="question-nav-header">
                    <strong>Navigasi Soal</strong>
                    <div class="legend-wrap">
                        <span class="legend-item"><i class="legend-dot active"></i>Aktif</span>
                        <span class="legend-item"><i class="legend-dot answered"></i>Terisi</span>
                        <span class="legend-item"><i class="legend-dot empty"></i>Belum</span>
                    </div>
                </div>
                <div class="question-nav-tools">
                    <div class="question-nav-pages">
                        <button
                            type="button"
                            class="question-nav-page-btn"
                            @click="prevNavPage"
                            :disabled="isSaving || isSubmitting || navPage <= 0"
                            aria-label="Halaman navigasi sebelumnya"
                        >‹</button>
                        <span class="question-nav-range" x-text="navRangeLabel"></span>
                        <button
                            type="button"
                            class="question-nav-page-btn"
                            @click="nextNavPage"
                            :disabled="isSaving || isSubmitting || navPage >= navTotalPages - 1"
                            aria-label="Halaman navigasi berikutnya"
                        >›</button>
                    </div>
                    <div class="question-nav-tools-row">
                        <label class="question-nav-jump">
                            <span>Loncat ke</span>
                            <input
                                type="number"
                                class="input question-nav-jump-input"
                                min="1"
                                :max="questions.length"
                                x-model.number="jumpQuestionNumber"
                                @keydown.enter.prevent="jumpToQuestionNumber"
                                :disabled="isSaving || isSubmitting"
                            >
                        </label>
                        <button
                            type="button"
                            class="btn btn-ghost question-nav-jump-btn"
                            @click="jumpToQuestionNumber"
                            :disabled="isSaving || isSubmitting"
                        >Buka</button>
                    </div>
                    <label class="question-nav-filter">
                        <input type="checkbox" x-model="showUnansweredOnly" :disabled="isSaving || isSubmitting">
                        <span>Tampilkan yang belum dijawab saja</span>
                    </label>
                    <p class="question-nav-hint" x-text="navHintLabel"></p>
                </div>
                <div class="question-grid">
                    <template x-for="item in navQuestions" :key="item.question.id">
                        <button
                            type="button"
                            class="question-pill"
                            :class="questionStateClass(item.index)"
                            :aria-current="item.index === currentIndex ? 'true' : 'false'"
                            :aria-label="`Buka soal nomor ${item.index + 1}`"
                            @click="goToQuestion(item.index)"
                            :disabled="isSaving || isSubmitting"
                            x-text="item.index + 1"
                        ></button>
                    </template>
                </div>
                <button type="button" class="btn btn-primary sidebar-submit" @click="submitExam()" :disabled="isSubmitting || isSaving" x-text="finishButtonText"></button>
            </aside>

            <button
                type="button"
                class="exam-nav-backdrop"
                :class="questionNavOpen && isMobileViewport ? 'is-visible' : ''"
                @click="closeQuestionNav"
                tabindex="-1"
                aria-hidden="true"
            ></button>
        </div>
    </section>

    <script type="module">
        import { examEngine } from '{{ asset("js/examEngine.js") }}';

        // Buat examEngine tersedia untuk Alpine.js x-data
        window.examEngine = examEngine;
    </script>

    <div id="exam-lockdown-overlay" class="exam-lockdown-overlay" hidden>
        <div class="exam-lockdown-card">
            <p class="exam-lockdown-title">Anda meninggalkan halaman tes</p>
            <p class="exam-lockdown-text">Sistem mendeteksi Anda berpindah tab/aplikasi lain. Kembali ke halaman ini untuk melanjutkan pengerjaan. Aktivitas ini tercatat oleh sistem.</p>
            <button type="button" class="btn btn-primary" id="exam-lockdown-resume">Kembali Mengerjakan</button>
        </div>
    </div>

    <script>
        (() => {
            const overlay = document.getElementById('exam-lockdown-overlay');
            const resumeBtn = document.getElementById('exam-lockdown-resume');
            const examSection = document.querySelector('.exam-shell');
            if (!overlay || !examSection) return;

            const showOverlay = () => {
                if (Date.now() < window.__examLockdownSuppressUntil) {
                    return;
                }
                overlay.hidden = false;
            };
            const hideOverlay = () => { overlay.hidden = true; };

            window.__examLockdownSuppressUntil = 0;

            // Diekspos supaya exam engine bisa keluar dari fullscreen (misalnya
            // saat semua soal sudah terjawab, atau sebelum redirect pasca-submit)
            // tanpa keluarnya programatik ini dianggap sebagai peserta berpindah
            // tab/aplikasi.
            window.__examExitFullscreen = () => {
                window.__examLockdownSuppressUntil = Date.now() + 800;
                if (document.fullscreenElement) {
                    return document.exitFullscreen().catch(() => {});
                }
                return Promise.resolve();
            };

            resumeBtn?.addEventListener('click', () => {
                hideOverlay();
                if (document.documentElement.requestFullscreen && !document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(() => {});
                }
            });

            document.addEventListener('contextmenu', (event) => event.preventDefault());

            document.addEventListener('copy', (event) => event.preventDefault());
            document.addEventListener('cut', (event) => event.preventDefault());

            document.addEventListener('keydown', (event) => {
                const key = event.key;
                const blockedCombo =
                    key === 'F12' ||
                    (event.ctrlKey && event.shiftKey && ['I', 'J', 'C', 'i', 'j', 'c'].includes(key)) ||
                    (event.ctrlKey && ['u', 'U'].includes(key)) ||
                    (event.metaKey && event.altKey && ['I', 'J', 'C', 'i', 'j', 'c'].includes(key));

                if (blockedCombo) {
                    event.preventDefault();
                }
            });

            window.addEventListener('blur', showOverlay);
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    showOverlay();
                }
            });

            window.addEventListener('beforeunload', (event) => {
                // Setelah tes berhasil disubmit, redirect ke hasil/login harus
                // terjadi tanpa prompt native "tinggalkan situs?" — membatalkan
                // prompt itu dulu pernah membuat peserta terjebak di halaman
                // tes yang sudah selesai.
                if (window.__examSubmitted) {
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            });

            if (document.documentElement.requestFullscreen) {
                const tryFullscreen = () => {
                    // Fullscreen seluruh halaman (bukan hanya kartu ujian) agar
                    // dialog/alert yang menempel di <body> — seperti konfirmasi
                    // "Akhiri Tes" — tetap terlihat dan bisa diklik saat
                    // fullscreen aktif, alih-alih tersembunyi sampai Esc ditekan.
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    }
                    document.removeEventListener('click', tryFullscreen);
                };
                document.addEventListener('click', tryFullscreen, { once: true });
            }
        })();
    </script>
</x-layouts.app>
