import axios from "axios";

/**
 * @typedef Choice
 * @type {object}
 * @property {string} text
 * @property {boolean|int} value
 */

/**
 * @typedef Question
 * @type {object}
 * @property {number} questionId
 * @property {string} text
 * @property {boolean|int} userAnswer
 * @property {boolean|int} correctAnswer
 * @property {string} explanation
 * @property {Choice[]} choices
 */

/**
 * @typedef UI
 * @type {object}
 * @property {string} true
 * @property {string} false
 * @property {string} submit
 * @property {string} finalText
 */

/**
 * @typedef QuizData
 * @type {object}
 * @property {string} submitUrl
 * @property {Question[]} questions
 * @property {UI} ui
 */

class Quiz {
    /** @type {HTMLDivElement} */
    #elemQuiz;

    /** @type {HTMLDivElement} */
    #elemUpper;

    /** @type {HTMLDivElement} */
    #elemLower;

    /** @type {HTMLDivElement} */
    #elemAnswers;

    /** @type {HTMLButtonElement} */
    #elemSubmit;

    /** @type {number} */
    #index = 0;

    /** @type {number}*/
    #lastIndex = 0;

    /** @type {DOMParser} */
    #parser = new DOMParser();

    /** @type {QuizData} */
    #data;

    constructor(elem) {
        if (!(elem instanceof HTMLDivElement)) {
            return;
        }

        this.#setup(elem);
        this.#showQuestion();
    }

    #setup(elem) {
        const context = this;
        this.#elemQuiz = elem;

        if ("quizData" in this.#elemQuiz.dataset) {
            this.#data = JSON.parse(this.#elemQuiz.dataset.quizData);
            this.#elemQuiz.removeAttribute("data-quiz-data");
            this.#lastIndex = this.#data.questions.length - 1;
        }
        this.#elemUpper = this.#parse(`<div class="quiz-upper"></div>`);
        this.#elemLower = this.#parse(`<div class="quiz-lower"></div>`);
        this.#elemQuiz.append(this.#elemUpper, this.#elemLower);
        this.#elemQuiz.addEventListener("quiz.answer.clicked", (evt) => context.#onAnswerClicked(evt));
        this.#elemQuiz.addEventListener("quiz.submit", (evt) => context.#onSubmit(evt));

        this.#elemAnswers = this.#parse(`<div class="quiz-answers"></div>`);
        this.#elemSubmit = this.#parse(`<button class="quiz-submit hide" type="button">${this.#data.ui.submit}</button>`);
        this.#elemSubmit.addEventListener("click", (evt) => {
            evt.target.dispatchEvent(new CustomEvent("quiz.submit", { bubbles: true }));
        });

        this.#elemLower.append(this.#elemAnswers, this.#elemSubmit);
    }

    #showQuestion() {
        const question = this.#data.questions[this.#index];
        const questionText = this.#parse(`<div class="quiz-text">${question.text}</div>`)
        this.#elemUpper.appendChild(questionText);

        for (const choice of question.choices) {
            const button = this.#parse(`<button class="quiz-answer ${choice.class}" type="button">${choice.text}</button>`);
            button.addEventListener("click", (evt) => {
                evt.target.dispatchEvent(new CustomEvent("quiz.answer.clicked", { detail: choice.value, bubbles: true }));
            });
            this.#elemAnswers.append(button);
        }
    }

    #showFinalScreen() {
        const finalText = this.#parse(`<div class="quiz-text">${this.#data.ui.finalText}`)
        this.#elemUpper.appendChild(finalText);

    }

    #clearUpper() {
        while (this.#elemUpper.lastChild) this.#elemUpper.removeChild(this.#elemUpper.lastChild);
    }

    #hideAnswers() {
        this.#elemAnswers.classList.add('hide');
    }

    #clearAnswers() {
        while (this.#elemAnswers.firstChild) {
            this.#elemAnswers.removeChild(this.#elemAnswers.firstChild);
        }
    }

    #showAnswers() {
        this.#elemAnswers.classList.remove('hide');
    }

    #hideSubmit() {
        this.#elemSubmit.classList.add('hide');
    }

    #showSubmit() {
        this.#elemSubmit.classList.remove('hide');
    }

    #onAnswerClicked(evt) {
        evt.preventDefault();
        this.#data.questions[this.#index].userAnswer = evt.detail;
        const correct = this.#data.questions[this.#index].userAnswer === this.#data.questions[this.#index].correctAnswer;
        this.#index++;

        const animWhiteout = this.#parse("<div class='quiz-anim-whiteout'></div>")
        let animResult;

        if (correct) {
            animResult = this.#parse(`
                <div class="quiz-anim-correct">
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M12 20C7.59 20 4 16.41 4 12S7.59 4 12 4 20 7.59 20 12 16.41 20 12 20M16.59 7.58L10 14.17L7.41 11.59L6 13L10 17L18 9L16.59 7.58Z"></path>
                    </svg>
                </div>
            `);
        } else {
            animResult = this.#parse(`
                <div class="quiz-anim-wrong">
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M12,2C6.47,2 2,6.47 2,12C2,17.53 6.47,22 12,22C17.53,22 22,17.53 22,12C22,6.47 17.53,2 12,2M14.59,8L12,10.59L9.41,8L8,9.41L10.59,12L8,14.59L9.41,16L12,13.41L14.59,16L16,14.59L13.41,12L16,9.41L14.59,8Z"></path>
                    </svg>
                </div>
            `);
        }

        this.#elemQuiz.append(animWhiteout, animResult)

        setTimeout(() => {
            ctx.#elemQuiz.appendChild(animResult);
        }, 300);

        const ctx = this;
        setTimeout(() => {
            this.#clearUpper();
            this.#clearAnswers();
            if (ctx.#index <= ctx.#lastIndex) {
                ctx.#showQuestion();
            } else {
                ctx.#hideAnswers();
                ctx.#showFinalScreen();
                ctx.#showSubmit();
            }
        }, 1000);

        setTimeout(() => {
            animWhiteout.remove();
            animResult.remove();
        }, 2000)
    }

    #onSubmit(evt) {
        evt.preventDefault();
        const answers = [];
        for (const question of this.#data.questions) {
            answers.push({ questionId: question.questionId, answer: question.userAnswer });
        }

        axios({ url: this.#data.submitUrl, method: "POST", data: { answers: answers } }).then((response) => {
            if (response.data.redirect) {
                window.location = response.data.redirect;
            }
        });
    }

    /** @return {HTMLElement}  */
    #parse(string) {
        return /** @type {HTMLElement} */ (this.#parser.parseFromString(string, 'text/html').body.firstChild);
    }
}

export default Quiz;