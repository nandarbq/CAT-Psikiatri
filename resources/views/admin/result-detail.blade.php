<x-layouts.admin
    title="Detail Hasil Ujian"
    heading="Detail Jawaban Peserta"
    :subtitle="$attempt->participant->name.' • '.$attempt->session->title"
>
    <x-slot:actions>
        <a href="{{ route('admin.results') }}" class="btn btn-ghost">Kembali</a>
        <a href="{{ route('admin.results.export.attempt.csv', $attempt) }}" class="btn btn-primary">Ekspor Hasil CSV</a>
    </x-slot:actions>

    <section
        x-data="resultReviewEngine({
            questions: @js($reviewQuestions),
            answers: @js($reviewAnswers),
            answeredAt: @js($reviewAnsweredAt),
        })"
        class="exam-shell exam-modern exam-review-shell"
    >
        <header class="exam-header">
            <div class="exam-header-main">
                <p class="exam-kicker">Tinjau Jawaban Peserta</p>
                <h2 class="exam-session-title">{{ $attempt->session->title }}</h2>
                <div class="exam-meta-row">
                    <span class="exam-chip">Peserta: {{ $attempt->participant->name }}</span>
                    <span class="exam-chip">Pengerjaan #{{ $attempt->id }}</span>
                    <span class="exam-chip">{{ $attempt->is_submitted ? 'Sudah Dikirim' : 'Belum Dikirim' }}</span>
                </div>
            </div>
            <div class="exam-timer-card">
                <p class="time-caption">Ringkasan Jawaban</p>
                <strong class="time-value" x-text="`${answeredCount}/${questions.length}`"></strong>
                <p class="time-subcaption" x-text="`${unansweredCount} soal belum dijawab`"></p>
                <p class="review-summary-breakdown" x-text="`YA: ${yesCount} • TIDAK: ${noCount}`"></p>
            </div>
        </header>

        <div class="exam-workspace">
            <section class="exam-main">
                <div class="progress-wrap">
                    <div class="progress-label">
                        <span x-text="`Soal ${currentQuestionNumber} dari ${questions.length}`"></span>
                        <span x-text="`${answeredCount} terjawab • ${unansweredCount} belum`"></span>
                    </div>
                    <div class="progress-bar" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100">
                        <span :style="`width:${progressPercent}%`"></span>
                    </div>
                </div>

                <article class="question-card review-question-card" x-show="currentQuestion" x-ref="questionCard">
                    <div class="question-head">
                        <span class="question-number-badge" x-text="`No. ${currentQuestionNumber}`"></span>
                        <p class="question-category" x-text="currentQuestion?.category || 'Umum'"></p>
                    </div>
                    <h3 class="question-text" x-text="currentQuestion?.question"></h3>

                    <div class="answer-grid">
                        <button
                            type="button"
                            class="btn-answer"
                            :class="isAnswered(true) ? 'active-yes' : ''"
                            disabled
                        >YA</button>
                        <button
                            type="button"
                            class="btn-answer"
                            :class="isAnswered(false) ? 'active-no' : ''"
                            disabled
                        >TIDAK</button>
                    </div>

                    <p class="save-status" :class="answerStatusType" x-text="answerStatusLabel"></p>
                    <p class="review-answer-time" x-show="currentAnsweredAt">
                        Dijawab pada: <strong x-text="currentAnsweredAt"></strong>
                    </p>
                </article>

                <div class="exam-actions">
                    <button type="button" class="btn btn-ghost" @click="prevQuestion" :disabled="currentIndex === 0">Sebelumnya</button>
                    <button type="button" class="btn btn-ghost" @click="nextQuestion" :disabled="currentIndex >= questions.length - 1">Berikutnya</button>
                </div>
            </section>

            <aside class="exam-sidebar-nav" aria-label="Navigasi nomor soal">
                <div class="question-nav-header">
                    <strong>Navigasi Soal</strong>
                    <div class="legend-wrap">
                        <span class="legend-item"><i class="legend-dot active"></i>Aktif</span>
                        <span class="legend-item"><i class="legend-dot yes"></i>YA</span>
                        <span class="legend-item"><i class="legend-dot no"></i>TIDAK</span>
                        <span class="legend-item"><i class="legend-dot empty"></i>Belum</span>
                    </div>
                </div>
                <div class="question-grid">
                    <template x-for="(question, index) in questions" :key="question.id">
                        <button
                            type="button"
                            class="question-pill"
                            :class="questionStateClass(index)"
                            :aria-current="index === currentIndex ? 'true' : 'false'"
                            :aria-label="`Buka soal nomor ${index + 1}`"
                            @click="goToQuestion(index)"
                            x-text="index + 1"
                        ></button>
                    </template>
                </div>
            </aside>
        </div>
    </section>

    <script>
        function resultReviewEngine(config) {
            return {
                questions: config.questions ?? [],
                answers: config.answers ?? {},
                answeredAt: config.answeredAt ?? {},
                currentIndex: 0,
                animationTimerId: null,

                init() {
                    this.$nextTick(() => this.animateQuestionCard('next'));
                },

                get currentQuestion() {
                    return this.questions[this.currentIndex] ?? null;
                },

                get currentQuestionNumber() {
                    return this.questions.length ? this.currentIndex + 1 : 0;
                },

                get answeredCount() {
                    return Object.keys(this.answers).length;
                },

                get unansweredCount() {
                    return Math.max(0, this.questions.length - this.answeredCount);
                },

                get yesCount() {
                    return Object.values(this.answers).filter((answer) => Boolean(answer) === true).length;
                },

                get noCount() {
                    return Object.values(this.answers).filter((answer) => Boolean(answer) === false).length;
                },

                get progressPercent() {
                    if (!this.questions.length) {
                        return 0;
                    }

                    return Math.round((this.answeredCount / this.questions.length) * 100);
                },

                get currentAnsweredAt() {
                    if (!this.currentQuestion) {
                        return null;
                    }

                    return this.answeredAt[this.currentQuestion.id] ?? null;
                },

                get answerStatusType() {
                    if (!this.currentQuestion) {
                        return 'idle';
                    }

                    const answer = this.answers[this.currentQuestion.id];

                    if (typeof answer === 'undefined') {
                        return 'idle';
                    }

                    return 'success';
                },

                get answerStatusLabel() {
                    if (!this.currentQuestion) {
                        return 'Tidak ada soal untuk ditampilkan.';
                    }

                    const answer = this.answers[this.currentQuestion.id];

                    if (typeof answer === 'undefined') {
                        return 'Peserta belum menjawab soal ini.';
                    }

                    return answer ? 'Jawaban peserta: YA' : 'Jawaban peserta: TIDAK';
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

                questionAnswer(index) {
                    const question = this.questions[index];

                    if (!question) {
                        return null;
                    }

                    const answer = this.answers[question.id];
                    return typeof answer === 'undefined' ? null : Boolean(answer);
                },

                questionStateClass(index) {
                    const classes = [];

                    if (index === this.currentIndex) {
                        classes.push('is-active');
                    }

                    const answer = this.questionAnswer(index);

                    if (answer === true) {
                        classes.push('is-yes');
                    } else if (answer === false) {
                        classes.push('is-no');
                    } else {
                        classes.push('is-empty');
                    }

                    return classes.join(' ');
                },

                goToQuestion(index) {
                    if (index >= 0 && index < this.questions.length && index !== this.currentIndex) {
                        const direction = index < this.currentIndex ? 'prev' : 'next';
                        this.currentIndex = index;
                        this.$nextTick(() => this.animateQuestionCard(direction));
                    }
                },

                prevQuestion() {
                    if (this.currentIndex > 0) {
                        this.currentIndex -= 1;
                        this.$nextTick(() => this.animateQuestionCard('prev'));
                    }
                },

                nextQuestion() {
                    if (this.currentIndex < this.questions.length - 1) {
                        this.currentIndex += 1;
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
            };
        }
    </script>
</x-layouts.admin>
