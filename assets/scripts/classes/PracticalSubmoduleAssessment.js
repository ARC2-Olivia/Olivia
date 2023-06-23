class PracticalSubmoduleAssessment {
    #form;
    #parser;
    #eventBus;
    #paging;
    #pager;
    #translation

    constructor(querySelector, assessmentData, translation) {
        this.#parser = new DOMParser();
        this.#eventBus = new EventBus();
        this.#initializeTranslation(translation);
        this.#initializeForm(querySelector);
        this.#initializeAssessmentFromData(assessmentData);
    }

    #initializeTranslation(translation) {
        this.#translation = {
            buttonNext: translation.buttonNext || "Next",
            buttonPrevious: translation.buttonPrevious || "Previous",
            buttonSubmit: translation.buttonSubmit || "Submit"
        };
    }

    #initializeForm(querySelector) {
        const form = document.querySelector(querySelector);
        if (form === null || form === undefined || form.tagName !== "FORM") {
            throw new Error("Query selector does not return a form element.")
        }
        this.#form = form;
    }

    #initializeAssessmentFromData(assessmentData = {}) {
        this.#paging = assessmentData.paging ?? false;
        if (this.#paging === true) this.#initializePaging(assessmentData);

        assessmentData.questions.forEach((questionData) => {
            const question = this.#createQuestion(questionData);
            if (this.#paging === true) {
                const page= this.#pager.querySelector(`[data-page="${questionData.page}"]`);
                if (page) page.appendChild(question); // DANIJEL PAUSE
            } else {
                this.#form.appendChild(question);
            }
        });

        if (this.#paging === true) this.#appendPageNavigation();
        this.#appendSubmitButton();
    }

    #initializePaging(assessmentData) {
        const pager = document.createElement("DIV");
        pager.classList.add('evaluation-assessment-pager');

        assessmentData.pages.forEach((pageData) => {
            const page = document.createElement("DIV");
            page.id = `assessment-page-${pageData.number}`;
            page.dataset.page = pageData.number;
            page.classList.add('evaluation-assessment-page');

            if (pageData.title !== null) {
                const pageTitle = document.createElement("DIV");
                pageTitle.classList.add("evaluation-assessment-page-title");
                pageTitle.innerText = pageData.title;
                page.appendChild(pageTitle);
            }

            if (pageData.description !== null) {
                const pageDescription = document.createElement("P");
                pageDescription.classList.add("evaluation-assessment-page-description");
                pageDescription.innerText = pageData.description;
                page.appendChild(pageDescription);
            }

            pager.appendChild(page);
        });

        this.#form.appendChild(pager);
        this.#pager = pager;
        window.pager = pager;
    }

    #appendPageNavigation() {
        const pages = this.#pager.querySelectorAll("[data-page]");
        for (let i = 0; i < pages.length; i++) {
            const currPage = pages[i];
            const nextPage = i < pages.length - 1 ? pages[i + 1] : null;
            const prevPage = i > 0 ? pages[i - 1] : null;
            let navigation = null;

            if (prevPage !== null && nextPage !== null) {
                navigation = this.#parser.parseFromString(`
                    <div class="text-center">
                        <a class="btn btn-theme-white bg-blue" href="#${prevPage.id}">${this.#translation.buttonPrevious}</a>
                        <a class="btn btn-theme-white bg-blue ms-3" href="#${nextPage.id}">${this.#translation.buttonNext}</a>
                    </div>
                `, "text/html").body.firstChild;
            } else if (prevPage !== null) {
                navigation = this.#parser.parseFromString(`
                    <div class="text-center">
                        <a class="btn btn-theme-white bg-blue" href="#${prevPage.id}">${this.#translation.buttonPrevious}</a>
                    </div>
                `, "text/html").body.firstChild;
            } else if (nextPage !== null) {
                navigation = this.#parser.parseFromString(`
                    <div class="text-center">
                        <a class="btn btn-theme-white bg-blue" href="#${nextPage.id}">${this.#translation.buttonNext}</a>
                    </div>
                `, "text/html").body.firstChild;
            }


            if (navigation !== null) currPage.append(navigation);
        }
    }

    #appendSubmitButton() {
        let location = this.#paging === true ? this.#pager.lastChild : this.#form;
        if (location !== null) {
            const submitButton = this.#parser.parseFromString(`
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-theme-white bg-green">${this.#translation.buttonSubmit}</button>
                </div>
            `, "text/html").body.firstChild;
            location.appendChild(submitButton);
        }
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
            case 'multi_choice': this.#createMultiChoiceAnswers(questionData).forEach((answer) => questionAnswers.appendChild(answer)); break;
            default: finalize = false;
        }

        if (finalize) {
            if (questionData.dependency) {
                const dependency = questionData.dependency;
                this.#eventBus.attach(question);
                context.#enableQuestion(question);
                question.on("answerchange", function(sender, { questionId, answer, checkType }) {
                    checkType = checkType || "equals";
                    if (dependency.questionId == questionId) {
                        let enableQuestion = false;
                        switch (checkType) {
                            case "equals": enableQuestion = answer == dependency.answer; break;
                            case "contains": enableQuestion = answer.includes(dependency.answer); break;
                        }
                        if (enableQuestion === true) {
                            context.#disableQuestion(this);
                        } else {
                            context.#enableQuestion(this);
                        }
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
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.dataset.value, checkType: 'equals' })
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
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.dataset.value, checkType: 'equals' });
            });

            answers.push(answer.body.firstChild);
        });
        return answers;
    }

    #createMultiChoiceAnswers(questionData) {
        const answers = [];
        const inputs = [];
        questionData.answers.forEach((answerData) => {
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="checkbox" value="${answerData.id}" name="evaluation_assessment[${questionData.id}][]" data-value="${answerData.value}"/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html");

            const input = answer.body.firstChild.querySelector("input");
            inputs.push(input);

            inputs.forEach((input) => {
                this.#eventBus.attach(input);
                input.addEventListener("click", function(event) {
                    const checkedValues = inputs.filter(i => i.checked === true).map(i => i.dataset.value);
                    event.target.dispatch("answerchange", { questionId: questionData.id, answer: checkedValues, checkType: 'contains' });
                });
            });

            answers.push(answer.body.firstChild);
        });

        /*
        const other = this.#parser.parseFromString(`
            <label class="evaluation-assessment-question-answer mt-3">
                <span>Other</span>
                <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}][other]"/>
            </label>
        `, "text/html");
        answers.push(other.body.firstChild);
        */

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
            event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.value, checkType: 'equals' });
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
            event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.value, checkType: 'equals' });
        });

        return answer.body.firstChild;
    }

    #createTemplatedTextInputAnswer(questionData) {
        const answerData = questionData.answers[0];
        let answerText = "", answerFields = [];

        if (answerData) {
            answerText = answerData.text;
            answerFields = answerData.fields;
        }

        let answerRaw = `<div style="white-space: pre-wrap">${answerText}</div>`;
        for (const field of answerFields) {
            const pattern = new RegExp(`{{\\s*${field}\\s*}}`);
            const inputRaw = `<label class="evaluation-assessment-question-answer--inline" style="white-space: normal">
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

global.PracticalSubmoduleAssessment = PracticalSubmoduleAssessment;

