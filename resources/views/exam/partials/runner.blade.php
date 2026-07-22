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

    <script>
        function examEngine(config) {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const isMobileDefault = window.matchMedia('(max-width: 768px)').matches;
            const questionNavOpenDefault = isMobileDefault ? false : (() => {
                try {
                    return window.localStorage.getItem('participant_question_nav_open') !== '0';
                } catch {
                    return true;
                }
            })();

            return {
                questions: config.questions,
                answers: config.answers,
                currentIndex: config.currentIndex,
                saveStatus: 'Pilih jawaban untuk menyimpan otomatis.',
                statusType: 'idle',
                isSaving: false,
                isSubmitting: false,
                deadlineAt: new Date(config.deadlineAt),
                now: new Date(),
                timerId: null,
                animationTimerId: null,
                lastKnownLocation: null,
                locationInFlight: null,
                requiresLocation: Boolean(config.requiresLocation),
                questionNavOpen: questionNavOpenDefault,
                isMobileViewport: isMobileDefault,
                navPageSize: isMobileDefault ? 40 : 80,
                navPage: 0,
                jumpQuestionNumber: config.currentIndex + 1,
                showUnansweredOnly: false,
                questionNavMediaQuery: null,
                questionNavMediaQueryHandler: null,
                finishButtonText: config.finishButtonText ?? 'Akhiri Tes',
                confirmSubmitTitle: config.confirmSubmitTitle ?? 'Akhiri tes sekarang?',
                confirmSubmitConfirmButton: config.confirmSubmitConfirmButton ?? 'Ya, akhiri tes',
                confirmSubmitCancelButton: config.confirmSubmitCancelButton ?? 'Lanjut mengerjakan',
                confirmSubmitTail: config.confirmSubmitTail ?? 'Setelah diakhiri, jawaban tidak bisa diubah lagi.',
                submitFailureStatus: config.submitFailureStatus ?? 'Pengiriman jawaban akhir gagal. Pastikan koneksi stabil lalu ulangi.',
                submitFailureAlertTitle: config.submitFailureAlertTitle ?? 'Pengiriman gagal',
                submitFailureAlertText: config.submitFailureAlertText ?? 'Pastikan koneksi stabil lalu coba lagi.',

                init() {
                    this.setupQuestionNavState();
                    this.ensureNavPageForIndex(this.currentIndex);

                    this.timerId = setInterval(() => {
                        this.now = new Date();

                        if (this.deadlineAt <= this.now) {
                            this.submitExam(true);
                        }
                    }, 1000);

                    if (this.requiresLocation) {
                        this.refreshLocationInBackground();
                    }

                    this.$nextTick(() => this.animateQuestionCard('next'));
                },

                destroy() {
                    if (this.timerId !== null) {
                        window.clearInterval(this.timerId);
                    }

                    if (this.animationTimerId !== null) {
                        window.clearTimeout(this.animationTimerId);
                    }

                    if (this.questionNavMediaQuery && this.questionNavMediaQueryHandler) {
                        if (typeof this.questionNavMediaQuery.removeEventListener === 'function') {
                            this.questionNavMediaQuery.removeEventListener('change', this.questionNavMediaQueryHandler);
                        } else if (typeof this.questionNavMediaQuery.removeListener === 'function') {
                            this.questionNavMediaQuery.removeListener(this.questionNavMediaQueryHandler);
                        }
                    }
                },

                setupQuestionNavState() {
                    this.questionNavMediaQuery = window.matchMedia('(max-width: 768px)');
                    this.syncQuestionNavViewportState(this.questionNavMediaQuery.matches);

                    this.questionNavMediaQueryHandler = (event) => {
                        this.syncQuestionNavViewportState(event.matches);
                    };

                    if (typeof this.questionNavMediaQuery.addEventListener === 'function') {
                        this.questionNavMediaQuery.addEventListener('change', this.questionNavMediaQueryHandler);
                    } else if (typeof this.questionNavMediaQuery.addListener === 'function') {
                        this.questionNavMediaQuery.addListener(this.questionNavMediaQueryHandler);
                    }
                },

                syncQuestionNavViewportState(isMobile) {
                    this.isMobileViewport = isMobile;
                    this.navPageSize = isMobile ? 40 : 80;
                    this.ensureNavPageForIndex(this.currentIndex);

                    if (isMobile) {
                        this.questionNavOpen = false;
                        return;
                    }

                    this.questionNavOpen = this.readQuestionNavPreference();
                },

                toggleQuestionNav() {
                    this.questionNavOpen = !this.questionNavOpen;

                    if (!this.isMobileViewport) {
                        this.persistQuestionNavPreference();
                    }
                },

                closeQuestionNav() {
                    this.questionNavOpen = false;
                },

                readQuestionNavPreference() {
                    try {
                        return window.localStorage.getItem('participant_question_nav_open') !== '0';
                    } catch {
                        return true;
                    }
                },

                persistQuestionNavPreference() {
                    try {
                        window.localStorage.setItem('participant_question_nav_open', this.questionNavOpen ? '1' : '0');
                    } catch {
                        // noop
                    }
                },

                get currentQuestion() {
                    return this.questions[this.currentIndex] ?? null;
                },

                get currentQuestionNumber() {
                    return this.currentIndex + 1;
                },

                get answeredCount() {
                    return Object.keys(this.answers).length;
                },

                get unansweredCount() {
                    return Math.max(0, this.questions.length - this.answeredCount);
                },

                get progressPercent() {
                    if (!this.questions.length) {
                        return 0;
                    }

                    return Math.round((this.answeredCount / this.questions.length) * 100);
                },

                get navTotalPages() {
                    if (!this.questions.length) {
                        return 1;
                    }

                    return Math.max(1, Math.ceil(this.questions.length / this.navPageSize));
                },

                get navStartIndex() {
                    return this.navPage * this.navPageSize;
                },

                get navEndIndex() {
                    return Math.min(this.questions.length, this.navStartIndex + this.navPageSize);
                },

                get navRangeLabel() {
                    if (!this.questions.length) {
                        return 'Tidak ada soal';
                    }

                    return `${this.navStartIndex + 1}-${this.navEndIndex} dari ${this.questions.length}`;
                },

                get navWindowCount() {
                    return Math.max(0, this.navEndIndex - this.navStartIndex);
                },

                get navQuestions() {
                    const start = this.navStartIndex;
                    const end = this.navEndIndex;
                    const windowQuestions = this.questions.slice(start, end).map((question, offset) => ({
                        question,
                        index: start + offset,
                    }));

                    if (!this.showUnansweredOnly) {
                        return windowQuestions;
                    }

                    return windowQuestions.filter((item) => !this.isQuestionAnswered(item.index));
                },

                get navHintLabel() {
                    const visibleCount = this.navQuestions.length;

                    if (!this.showUnansweredOnly) {
                        return `Menampilkan ${visibleCount} soal pada rentang ini.`;
                    }

                    return `Menampilkan ${visibleCount} soal belum dijawab dari ${this.navWindowCount} soal pada rentang ini.`;
                },

                get timeLeftLabel() {
                    const diffMs = this.deadlineAt - this.now;
                    const totalSec = Math.max(0, Math.floor(diffMs / 1000));
                    const hours = Math.floor(totalSec / 3600);
                    const minutes = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
                    const seconds = String(totalSec % 60).padStart(2, '0');

                    if (hours > 0) {
                        return `${String(hours).padStart(2, '0')}:${minutes}:${seconds}`;
                    }

                    return `${minutes}:${seconds}`;
                },

                get timerStateClass() {
                    const diffMs = this.deadlineAt - this.now;
                    const totalSec = Math.max(0, Math.floor(diffMs / 1000));

                    if (totalSec <= 300) {
                        return 'is-critical';
                    }

                    if (totalSec <= 900) {
                        return 'is-warning';
                    }

                    return 'is-normal';
                },

                isAnswered(value) {
                    if (!this.currentQuestion) {
                        return false;
                    }

                    const answer = this.answers[this.currentQuestion.id];
                    return typeof answer !== 'undefined' && Boolean(answer) === value;
                },

                isQuestionAnswered(index) {
                    const question = this.questions[index];

                    if (!question) {
                        return false;
                    }

                    return typeof this.answers[question.id] !== 'undefined';
                },

                questionStateClass(index) {
                    if (index === this.currentIndex) {
                        return 'is-active';
                    }

                    if (this.isQuestionAnswered(index)) {
                        return 'is-answered';
                    }

                    return 'is-empty';
                },

                goToQuestion(index) {
                    if (index >= 0 && index < this.questions.length && index !== this.currentIndex) {
                        const direction = index < this.currentIndex ? 'prev' : 'next';
                        this.currentIndex = index;
                        this.jumpQuestionNumber = index + 1;
                        this.ensureNavPageForIndex(index);
                        this.$nextTick(() => this.animateQuestionCard(direction));

                        if (this.isMobileViewport) {
                            this.closeQuestionNav();
                        }
                    }
                },

                ensureNavPageForIndex(index) {
                    if (!this.questions.length) {
                        this.navPage = 0;
                        return;
                    }

                    const safeIndex = Math.max(0, Math.min(this.questions.length - 1, Number(index) || 0));
                    const nextPage = Math.floor(safeIndex / this.navPageSize);
                    this.navPage = Math.max(0, Math.min(this.navTotalPages - 1, nextPage));
                },

                prevNavPage() {
                    this.navPage = Math.max(0, this.navPage - 1);
                },

                nextNavPage() {
                    this.navPage = Math.min(this.navTotalPages - 1, this.navPage + 1);
                },

                jumpToQuestionNumber() {
                    const target = Number(this.jumpQuestionNumber);
                    if (!Number.isFinite(target)) {
                        return;
                    }

                    const index = Math.max(1, Math.min(this.questions.length, Math.round(target))) - 1;
                    this.jumpQuestionNumber = index + 1;
                    this.goToQuestion(index);
                },

                async selectAnswer(answer) {
                    if (!this.currentQuestion || this.isSaving || this.isSubmitting) {
                        return;
                    }

                    this.isSaving = true;
                    this.saveStatus = 'Menyimpan...';
                    this.statusType = 'saving';

                    try {
                        const location = this.requiresLocation ? await this.getLocationForFastSave() : null;
                        if (this.requiresLocation) {
                            this.refreshLocationInBackground();
                        }
                        const response = await fetch(config.saveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                ...(location ? this.toLocationHeaders(location) : {}),
                            },
                            body: JSON.stringify({
                                question_id: this.currentQuestion.id,
                                answer: answer,
                            }),
                        });
                        const data = await response.json();

                        if (!response.ok || data.success !== true) {
                            throw new Error(data.message ?? 'Gagal menyimpan jawaban.');
                        }

                        this.answers[this.currentQuestion.id] = answer;
                        this.saveStatus = 'Tersimpan.';
                        this.statusType = 'success';

                        if (this.answeredCount >= this.questions.length) {
                            // Every question has an answer now — leave fullscreen
                            // automatically so the finish/submit dialog is visible
                            // without the participant needing to press Esc.
                            this.exitFullscreenIfActive();
                        }

                        if (this.currentIndex < this.questions.length - 1) {
                            window.setTimeout(() => {
                                this.nextQuestion();
                            }, 350);
                        }
                    } catch (error) {
                        this.saveStatus = 'Gagal menyimpan. Coba lagi.';
                        this.statusType = 'error';
                    } finally {
                        this.isSaving = false;
                    }
                },

                exitFullscreenIfActive() {
                    if (typeof window.__examExitFullscreen === 'function') {
                        window.__examExitFullscreen();
                        return;
                    }

                    if (document.fullscreenElement) {
                        document.exitFullscreen().catch(() => {});
                    }
                },

                prevQuestion() {
                    if (this.currentIndex > 0) {
                        this.currentIndex -= 1;
                        this.jumpQuestionNumber = this.currentIndex + 1;
                        this.ensureNavPageForIndex(this.currentIndex);
                        this.$nextTick(() => this.animateQuestionCard('prev'));
                    }
                },

                nextQuestion() {
                    if (this.currentIndex < this.questions.length - 1) {
                        this.currentIndex += 1;
                        this.jumpQuestionNumber = this.currentIndex + 1;
                        this.ensureNavPageForIndex(this.currentIndex);
                        this.$nextTick(() => this.animateQuestionCard('next'));
                    }
                },

                animateQuestionCard(direction) {
                    const card = this.$refs.questionCard;

                    if (!card) {
                        return;
                    }

                    card.classList.remove('is-animating-prev', 'is-animating-next');

                    // Force reflow so repeated navigation retriggers the animation class.
                    void card.offsetWidth;

                    card.classList.add(direction === 'prev' ? 'is-animating-prev' : 'is-animating-next');

                    if (this.animationTimerId !== null) {
                        window.clearTimeout(this.animationTimerId);
                    }

                    this.animationTimerId = window.setTimeout(() => {
                        card.classList.remove('is-animating-prev', 'is-animating-next');
                        this.animationTimerId = null;
                    }, 240);
                },

                async submitExam(forcedByTimer = false) {
                    if (this.isSubmitting || this.isSaving) {
                        return;
                    }

                    if (forcedByTimer !== true) {
                        const confirmationMessage = this.unansweredCount > 0
                            ? `Masih ada ${this.unansweredCount} soal belum dijawab.`
                            : 'Semua soal sudah terjawab.';

                        const approved = await window.AppDialog.confirm({
                            title: this.confirmSubmitTitle,
                            text: `${confirmationMessage} ${this.confirmSubmitTail}`,
                            icon: 'warning',
                            confirmButtonText: this.confirmSubmitConfirmButton,
                            cancelButtonText: this.confirmSubmitCancelButton,
                        });

                        if (!approved) {
                            return;
                        }
                    }

                    this.isSubmitting = true;

                    try {
                        let location = null;
                        if (this.requiresLocation) {
                            location = await this.getCurrentLocation({
                                timeout: 3000,
                                maximumAge: 20000,
                                highAccuracy: false,
                            });
                            if (location) {
                                this.lastKnownLocation = location;
                            }
                        }
                        const response = await fetch(config.submitUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                ...(location ? this.toLocationHeaders(location) : {}),
                            },
                        });

                        const data = await response.json();

                        if (!response.ok || data.success !== true) {
                            throw new Error(data.message ?? 'Pengiriman jawaban akhir gagal.');
                        }

                        // Tes sudah berhasil dikirim ke server: matikan proteksi
                        // "tinggalkan halaman" dan keluar dari fullscreen sebelum
                        // redirect, supaya browser tidak menampilkan dialog
                        // konfirmasi native yang bisa membatalkan kepindahan halaman.
                        window.__examSubmitted = true;
                        this.exitFullscreenIfActive();
                        window.location.href = data.redirect;
                    } catch {
                        this.saveStatus = this.submitFailureStatus;
                        this.statusType = 'error';
                        await window.AppDialog.alert({
                            title: this.submitFailureAlertTitle,
                            text: this.submitFailureAlertText,
                            icon: 'error',
                        });
                        this.isSubmitting = false;
                    }
                },

                async getLocationForFastSave() {
                    if (this.lastKnownLocation) {
                        return this.lastKnownLocation;
                    }

                    const quickLocation = await this.getCurrentLocation({
                        timeout: 1200,
                        maximumAge: 60000,
                        highAccuracy: false,
                    });

                    if (quickLocation) {
                        this.lastKnownLocation = quickLocation;
                    }

                    return quickLocation;
                },

                refreshLocationInBackground() {
                    if (this.locationInFlight) {
                        return;
                    }

                    this.locationInFlight = this.getCurrentLocation({
                        timeout: 4000,
                        maximumAge: 10000,
                        highAccuracy: true,
                    }).then((location) => {
                        if (location) {
                            this.lastKnownLocation = location;
                        }
                    }).finally(() => {
                        this.locationInFlight = null;
                    });
                },

                async getCurrentLocation(options = {}) {
                    if (!navigator.geolocation) {
                        return null;
                    }

                    const timeout = Number.isFinite(options.timeout) ? Number(options.timeout) : 8000;
                    const maximumAge = Number.isFinite(options.maximumAge) ? Number(options.maximumAge) : 15000;
                    const highAccuracy = options.highAccuracy === true;

                    return await new Promise((resolve) => {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                resolve({
                                    latitude: position.coords.latitude,
                                    longitude: position.coords.longitude,
                                    accuracy: position.coords.accuracy ?? null,
                                });
                            },
                            () => resolve(null),
                            {
                                enableHighAccuracy: highAccuracy,
                                timeout,
                                maximumAge,
                            }
                        );
                    });
                },

                toLocationHeaders(location) {
                    return {
                        'X-Exam-Latitude': String(location.latitude),
                        'X-Exam-Longitude': String(location.longitude),
                        'X-Exam-Accuracy': location.accuracy == null ? '' : String(location.accuracy),
                    };
                },
            }
        }
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

            // Exposed so the exam engine can leave fullscreen (e.g. once every
            // question is answered, or right before the post-submit redirect)
            // without that programmatic exit being mistaken for the participant
            // switching tabs/apps.
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

            // Best-effort deterrents. Note: a website cannot block OS/browser-level
            // shortcuts like Ctrl+T, Ctrl+N, or opening a new window from the
            // browser's own UI — these run outside what page JavaScript can reach.
            // What we *can* do: detect when the tab loses focus/visibility, disable
            // the right-click menu and text copying, discourage devtools shortcuts,
            // and nudge fullscreen mode back on.

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
            window.addEventListener('focus', () => {
                // Keep the overlay up until the participant explicitly resumes,
                // so a quick alt-tab away still gets flagged and acknowledged.
            });

            window.addEventListener('beforeunload', (event) => {
                // Once the exam has been submitted successfully we want the
                // redirect to results/login to happen without the native
                // "leave site?" prompt — cancelling that prompt used to leave
                // the participant stuck on a finished exam page.
                if (window.__examSubmitted) {
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            });

            if (document.documentElement.requestFullscreen) {
                const tryFullscreen = () => {
                    // Fullscreen the whole page (not just the exam card) so that
                    // dialogs/alerts appended to <body> — like the "Akhiri Tes"
                    // confirmation — stay visible and clickable while fullscreen
                    // is active, instead of being hidden until Esc is pressed.
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
