<x-layouts.admin
    title="Detail Hasil Tes"
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
        class="exam-shell review-v2-shell"
    >
        <header class="review-v2-header">
            <p class="exam-kicker">Tinjau Jawaban Peserta</p>
            <h2 class="exam-session-title">{{ $attempt->session->title }}</h2>
            <div class="exam-meta-row">
                <span class="exam-chip">Peserta: {{ $attempt->participant->name }}</span>
                <span class="exam-chip">Pengerjaan #{{ $attempt->id }}</span>
                <span class="exam-chip">{{ $attempt->is_submitted ? 'Sudah Dikirim' : 'Belum Dikirim' }}</span>
            </div>
        </header>

        <div class="review-v2-grid">
            <div class="review-v2-main">
                <div class="review-v2-progress">
                    <div class="review-v2-progress-track" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100">
                        <span :style="`width:${progressPercent}%`"></span>
                    </div>
                    <p class="review-v2-progress-label" x-text="`${answeredCount} Terjawab - ${unansweredCount} Belum`"></p>
                </div>

                <div class="review-v2-pillrow" aria-label="Navigasi nomor soal">
                    <template x-for="(question, index) in questions" :key="question.id">
                        <button
                            type="button"
                            class="review-v2-pill"
                            :class="questionStateClass(index)"
                            :aria-current="index === currentIndex ? 'true' : 'false'"
                            :aria-label="`Buka soal nomor ${index + 1}`"
                            @click="goToQuestion(index)"
                            x-text="index + 1"
                        ></button>
                    </template>
                </div>

                <article class="review-v2-card" x-show="currentQuestion" x-ref="questionCard">
                    <p class="review-v2-card-label" x-text="`Soal Pertanyaan No.${currentQuestionNumber}`"></p>
                    <p class="review-v2-card-question" x-text="currentQuestion?.question"></p>
                </article>

                <div class="review-v2-answers">
                    <button
                        type="button"
                        class="review-v2-answer-btn"
                        :class="isAnswered(true) ? 'is-yes-active' : ''"
                        disabled
                    >Ya</button>
                    <button
                        type="button"
                        class="review-v2-answer-btn"
                        :class="isAnswered(false) ? 'is-no-active' : ''"
                        disabled
                    >Tidak</button>
                </div>

                <div class="review-v2-footer">
                    <p class="review-v2-time" x-text="currentAnsweredAt ? `Terjawab pada pukul ${currentAnsweredAt}` : 'Peserta belum menjawab soal ini'"></p>
                    <div class="review-v2-nav-btns">
                        <button type="button" class="btn btn-ghost" @click="prevQuestion" :disabled="currentIndex === 0">Sebelumnya</button>
                        <button type="button" class="review-v2-next-btn" @click="nextQuestion" :disabled="currentIndex >= questions.length - 1">Selanjutnya</button>
                    </div>
                </div>
            </div>

            <aside class="review-v2-summary" aria-label="Ringkasan jawaban">
                <h3 class="review-v2-summary-title">Ringkasan Jawaban</h3>

                <div class="review-v2-donut-wrap">
                    <div class="review-v2-donut" :style="donutStyle">
                        <div class="review-v2-donut-inner">
                            <strong x-text="`${answeredCount}/${questions.length}`"></strong>
                            <span>Terjawab</span>
                        </div>
                    </div>
                </div>

                <ul class="review-v2-legend">
                    <li>
                        <span class="review-v2-dot is-yes"></span>
                        <span class="review-v2-legend-label">Jawaban Ya</span>
                        <b x-text="yesCount"></b>
                    </li>
                    <li>
                        <span class="review-v2-dot is-no"></span>
                        <span class="review-v2-legend-label">Jawaban Tidak</span>
                        <b x-text="noCount"></b>
                    </li>
                    <li>
                        <span class="review-v2-dot is-empty"></span>
                        <span class="review-v2-legend-label">Belum jawab</span>
                        <b x-text="unansweredCount"></b>
                    </li>
                </ul>
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

                get donutStyle() {
                    const total = this.questions.length || 1;
                    const yesEnd = (this.yesCount / total) * 100;
                    const noEnd = yesEnd + (this.noCount / total) * 100;

                    return `background: conic-gradient(#3E8A5D 0% ${yesEnd}%, #C76A60 ${yesEnd}% ${noEnd}%, #E1E9E8 ${noEnd}% 100%)`;
                },

                get currentAnsweredAt() {
                    if (!this.currentQuestion) {
                        return null;
                    }

                    return this.answeredAt[this.currentQuestion.id] ?? null;
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
