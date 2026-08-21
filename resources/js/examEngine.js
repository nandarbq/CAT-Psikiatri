/**
 * Exam Engine — Alpine.js state management for participant exam UI
 *
 * Handles:
 * - Question navigation and display
 * - Answer state management
 * - Autosave with optimistic updates
 * - Timer countdown and deadline tracking
 * - Fullscreen and focus monitoring
 * - Geolocation tracking for location-restricted exams
 */

export function examEngine(config) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const serverNowMs = Date.parse(config.serverNow);
    const deadlineMs = Date.parse(config.deadlineAt);
    const deadlineClockOffsetMs = Number.isFinite(serverNowMs) && Number.isFinite(deadlineMs)
        ? Date.now() - serverNowMs
        : 0;
    const adjustedDeadlineAt = new Date(deadlineMs + deadlineClockOffsetMs);
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
        // Gunakan selisih jam pada saat halaman dirender supaya timer tetap
        // menunjukkan durasi yang benar ketika jam perangkat tidak sinkron.
        deadlineAt: adjustedDeadlineAt,
        now: new Date(),
        timerId: null,
        animationTimerId: null,
        autoAdvanceTimerId: null,
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

            if (this.autoAdvanceTimerId !== null) {
                window.clearTimeout(this.autoAdvanceTimerId);
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
                // M6: Cancel any pending auto-advance when participant manually navigates
                if (this.autoAdvanceTimerId !== null) {
                    window.clearTimeout(this.autoAdvanceTimerId);
                    this.autoAdvanceTimerId = null;
                }

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

        isAuthenticationExpired(response) {
            return response.status === 401 || response.status === 419;
        },

        redirectToLogin() {
            this.saveStatus = 'Sesi Anda telah berakhir. Mengarahkan ke halaman login...';
            this.statusType = 'error';
            window.__examSubmitted = true;
            this.exitFullscreenIfActive();

            if (this.timerId !== null) {
                window.clearInterval(this.timerId);
                this.timerId = null;
            }

            window.location.assign(config.loginUrl ?? '/login');
        },

        async selectAnswer(answer) {
            if (!this.currentQuestion || this.isSaving || this.isSubmitting) {
                return;
            }

            const question = this.currentQuestion;
            const answeredIndex = this.currentIndex;
            const previousAnswer = this.answers[question.id];
            const hadPreviousAnswer = typeof previousAnswer !== 'undefined';

            this.isSaving = true;
            this.saveStatus = 'Menyimpan...';
            this.statusType = 'saving';

            // Optimistic update: reflect the pilihan (dan hitungan
            // "terjawab/belum") seketika saat diklik, jangan menunggu
            // respons jaringan selesai dulu — supaya tidak terasa
            // lambat/lag saat koneksi lelet. Kalau simpan gagal,
            // nilainya dikembalikan lagi di blok catch di bawah.
            this.answers[question.id] = answer;

            try {
                const location = this.requiresLocation ? await this.getLocationForFastSave() : null;
                if (this.requiresLocation) {
                    this.refreshLocationInBackground();
                }
                const response = await fetch(config.saveUrl, {
                    method: 'POST',
                    signal: AbortSignal.timeout(8000),
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        ...(location ? this.toLocationHeaders(location) : {}),
                    },
                    body: JSON.stringify({
                        question_id: question.id,
                        answer: answer,
                    }),
                });
                if (this.isAuthenticationExpired(response)) {
                    this.redirectToLogin();
                    return;
                }

                const data = await response.json();

                if (!response.ok || data.success !== true) {
                    throw new Error(data.message ?? 'Gagal menyimpan jawaban.');
                }

                this.saveStatus = 'Tersimpan.';
                this.statusType = 'success';

                if (this.answeredCount >= this.questions.length) {
                    // Every question has an answer now — leave fullscreen
                    // automatically so the finish/submit dialog is visible
                    // without the participant needing to press Esc.
                    this.exitFullscreenIfActive();
                }

                if (this.currentIndex === answeredIndex && this.currentIndex < this.questions.length - 1) {
                    // M6: Cancel any pending auto-advance before scheduling new one
                    if (this.autoAdvanceTimerId !== null) {
                        window.clearTimeout(this.autoAdvanceTimerId);
                    }

                    // M6: Re-check currentIndex in callback to ensure participant
                    // hasn't manually navigated to a different question during the
                    // 350ms delay (which would cause advancing from wrong position).
                    this.autoAdvanceTimerId = window.setTimeout(() => {
                        this.autoAdvanceTimerId = null;
                        // Only advance if we're still on the same question
                        if (this.currentIndex === answeredIndex && this.currentIndex < this.questions.length - 1) {
                            this.nextQuestion();
                        }
                    }, 350);
                }
            } catch (error) {
                // Rollback the optimistic update so counters/nav dots
                // match what's actually saved on the server.
                if (hadPreviousAnswer) {
                    this.answers[question.id] = previousAnswer;
                } else {
                    delete this.answers[question.id];
                }
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
                    signal: AbortSignal.timeout(8000),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        ...(location ? this.toLocationHeaders(location) : {}),
                    },
                });

                if (this.isAuthenticationExpired(response)) {
                    this.redirectToLogin();
                    return;
                }

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

                if (this.timerId !== null) {
                    window.clearInterval(this.timerId);
                }

                window.location.href = data.redirect;
            } catch (error) {
                // M5: Jika force-submit by timer gagal, jangan show dialog.
                // Langsung anggap selesai dan redirect ke login untuk menghindari
                // loop popup error setiap detik.
                if (forcedByTimer === true) {
                    window.__examSubmitted = true;

                    // Stop timer agar tidak retry
                    if (this.timerId !== null) {
                        window.clearInterval(this.timerId);
                    }

                    // Redirect ke login. Error akan dicatat server, participant
                    // bisa login ulang dan melanjutkan jika attempt belum submit.
                    window.location.href = '/participant/login';
                    return;
                }

                // Jika manual submit (user klik tombol), show dialog error
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
    };
}
