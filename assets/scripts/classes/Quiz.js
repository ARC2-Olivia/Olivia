import axios from "axios";

/**
 * @typedef Question
 * @type {object}
 * @property {number} questionId
 * @property {string} text
 * @property {boolean} userAnswer
 * @property {boolean} correctAnswer
 * @property {string} explanation
 */

/**
 * @typedef UI
 * @type {object}
 * @property {string} yes
 * @property {string} no
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
        const buttonYes = this.#parse(`<button class="quiz-answer yes" type="button">${this.#data.ui.yes}</button>`);
        const buttonNo = this.#parse(`<button class="quiz-answer no" type="button">${this.#data.ui.no}</button>`);
        buttonYes.addEventListener("click", (evt) => {
            evt.target.dispatchEvent(new CustomEvent("quiz.answer.clicked", { detail: true, bubbles: true }));
        });
        buttonNo.addEventListener("click", (evt) => {
            evt.target.dispatchEvent(new CustomEvent("quiz.answer.clicked", { detail: false, bubbles: true }));
        });
        this.#elemAnswers.append(buttonYes, buttonNo);

        this.#elemSubmit = this.#parse(`<button class="quiz-submit hide" type="button">${this.#data.ui.submit}</button>`);
        this.#elemSubmit.addEventListener("click", (evt) => {
            evt.target.dispatchEvent(new CustomEvent("quiz.submit", { bubbles: true }));
        });

        this.#elemLower.append(this.#elemAnswers, this.#elemSubmit);
    }

    #showQuestion() {
        const question = this.#data.questions[this.#index];
        const questionText = this.#parse(`<div class="quiz-question-text">${question.text}</div>`)
        this.#elemUpper.appendChild(questionText);
    }

    #showFinalScreen() {
        const finalText = this.#parse(`<div class="quiz-question-text">${this.#data.ui.finalText}`)
        this.#elemUpper.appendChild(finalText);

    }

    #clearUpper() {
        while (this.#elemUpper.lastChild) this.#elemUpper.removeChild(this.#elemUpper.lastChild);
    }

    #hideAnswers() {
        this.#elemAnswers.classList.add('hide');
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
        this.#clearUpper();
        this.#index++;

        if (this.#index <= this.#lastIndex) {
            this.#clearUpper();
            this.#showQuestion();
        } else {
            this.#hideAnswers();
            this.#showFinalScreen();
            this.#showSubmit();
        }
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