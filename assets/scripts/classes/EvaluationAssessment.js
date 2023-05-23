class EvaluationAssessment {
    #form;
    #parser;
    #eventBus;

    constructor(querySelector, assessmentData) {
        this.#parser = new DOMParser();
        this.#eventBus = new EventBus();
        this.#initializeForm(querySelector);
        this.#initializeAssessmentFromData(assessmentData);
    }

    #initializeForm(querySelector) {
        const form = document.querySelector(querySelector);
        if (form === null || form === undefined || form.tagName !== "FORM") {
            throw new Error("Query selector does not return a form element.")
        }
        this.#form = form;
    }

    #initializeAssessmentFromData(assessmentData = []) {
        assessmentData.questions.forEach((questionData) => {
            const question = this.#createQuestion(questionData);
            this.#form.appendChild(question);
        });
    }

    #createQuestion(questionData) {
        const context = this;

        const question = document.createElement("DIV");
        question.classList.add('evaluation-assessment-question');
        question.dataset.id = questionData.id;
        question.dataset.type = questionData.type;
        question.dataset.question = "";

        const questionText = document.createElement("DIV");
        questionText.classList.add('evaluation-assessment-question-text');
        questionText.innerText = questionData.question;

        const questionAnswers = document.createElement("DIV");
        questionAnswers.classList.add('evaluation-assessment-question-answers');

        let finalize = true;
        switch (questionData.type) {
            case 'yes_no': this.#createYesNoAnswers(questionData).forEach((answer) => questionAnswers.appendChild(answer)); break;
            case 'weighted': this.#createWeightedAnswers(questionData).forEach((answer) => questionAnswers.appendChild(answer)); break;
            case 'numerical_input': questionAnswers.append(this.#createNumericalInputAnswer(questionData)); break;
            case 'text_input': questionAnswers.append(this.#createTextInputAnswer(questionData)); break;
            case 'templated_text_input': questionAnswers.append(this.#createTemplatedTextInputAnswer(questionData)); break;
            default: finalize = false;
        }

        if (finalize) {
            if (questionData.dependency) {
                const dependency = questionData.dependency;
                this.#eventBus.attach(question);
                context.#enableQuestion(question);
                question.on("answerchange", function(sender, { questionId, answer }) {
                    if (dependency.questionId == questionId) {
                        if (dependency.answer == answer)
                            context.#disableQuestion(this);
                        else
                            context.#enableQuestion(this);
                    }
                });
            }
            question.append(questionText, questionAnswers);
        }
        return question;
    }

    #createYesNoAnswers(questionData) {
        const answers = [];
        questionData.answers.forEach((answerData) => {
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="radio" value="${answerData.id}" name="evaluation_assessment[${questionData.id}]" data-value="${answerData.value}" required/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html");

            const input = answer.body.firstChild.querySelector("input");
            this.#eventBus.attach(input);
            input.addEventListener("click", function(event) {
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.dataset.value })
            });

            answers.push(answer.body.firstChild);
        });
        return answers;
    }

    #createWeightedAnswers(questionData) {
        const answers = [];
        questionData.answers.forEach((answerData) => {
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="radio" value="${answerData.id}" name="evaluation_assessment[${questionData.id}]" data-value="${answerData.value}" required/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html");

            const input = answer.body.firstChild.querySelector("input");
            this.#eventBus.attach(input);
            input.addEventListener("click", function(event) {
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.dataset.value });
            });

            answers.push(answer.body.firstChild);
        });
        return answers;
    }

    #createNumericalInputAnswer(questionData) {
        const answer = this.#parser.parseFromString(`
            <label class="evaluation-assessment-question-answer">
                <input type="number" step="0.01" class="form-input" name="evaluation_assessment[${questionData.id}]" required/>
            </label>
        `, "text/html");

        const input = answer.body.firstChild.querySelector("input");
        this.#eventBus.attach(input);
        input.addEventListener("input", function(event) {
            event.target.dispatch("answerchange", { questionId: questionData.id, answer: element.target.value });
        });

        return answer.body.firstChild;
    }

    #createTextInputAnswer(questionData) {
        const answer = this.#parser.parseFromString(`
            <label class="evaluation-assessment-question-answer">
                <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}]" required/>
            </label>
        `, "text/html");

        const input = answer.body.firstChild.querySelector("input");
        this.#eventBus.attach(input);
        input.addEventListener("input", function(event) {
            event.target.dispatch("answerchange", { questionId: questionData.id, answer: element.target.value });
        });

        return answer.body.firstChild;
    }

    #createTemplatedTextInputAnswer(questionData) {
        const answerData = questionData.answers[0];
        let answerRaw = `<div>${answerData.text}</div>`;

        for (const field of answerData.fields) {
            const pattern = new RegExp(`{{\\s*${field}\\s*}}`);
            const inputRaw = `<label class="evaluation-assessment-question-answer--inline">
                <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}][${field}]" required/>
            </label>`;
            answerRaw = answerRaw.replace(pattern, inputRaw);
        }

        const answer = this.#parser.parseFromString(answerRaw, "text/html");
        return answer.body.firstChild;
    }

    #enableQuestion(questionElement) {
        if (!questionElement.classList.contains("hide")) questionElement.classList.add("hide");
        questionElement.querySelectorAll("input").forEach((input) => {
            input.required = false;
            input.disabled = true;
        });
    }

    #disableQuestion(questionElement) {
        if (questionElement.classList.contains("hide")) questionElement.classList.remove("hide");
        questionElement.querySelectorAll("input").forEach((input) => {
            input.required = true;
            input.disabled = false;
        });
    }
}

global.EvaluationAssessment = EvaluationAssessment;

