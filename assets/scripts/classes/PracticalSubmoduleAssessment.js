class PracticalSubmoduleAssessment {
    #form;
    #parser;
    #eventBus;
    #paging;
    #pager;
    #translation;
    #handlers = [];
    #backgroundSavingEnabled = true;

    constructor(querySelector, assessmentData, translation) {
        this.#parser = new DOMParser();
        this.#eventBus = new EventBus();
        this.#initializeTranslation(translation);
        this.#initializeForm(querySelector);
        this.#initializeAssessmentFromData(assessmentData);
        for (const handler of this.#handlers) {
            handler();
        }
        this.#initializeBackgroundSavingProcess();
    }

    #initializeTranslation(translation) {
        this.#translation = {
            buttonNext: translation.buttonNext || "Next",
            buttonPrevious: translation.buttonPrevious || "Previous",
            buttonSubmit: translation.buttonSubmit || "Submit",
            buttonAdd: translation.buttonAdd || "Add",
            buttonSaveForLater: translation.buttonSaveForLater || "Save for later",
            errorDefault: translation.errorDefault || "The answer to this question is invalid"
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
                const page = this.#pager.querySelector(`[data-page="${questionData.page}"] > .evaluation-assessment`);
                if (page) page.appendChild(question);
            } else {
                this.#form.appendChild(question);
            }
        });

        if (this.#paging === true) {
            this.#appendPageNavigation();
            this.#initializePagingSuitableFormValidation();
        }
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

            const wrapper = document.createElement("DIV");
            wrapper.classList.add("evaluation-assessment");
            page.appendChild(wrapper);

            pager.appendChild(page);
        });

        this.#form.appendChild(pager);
        this.#pager = pager;
        window.pager = pager;
    }

    #appendPageNavigation() {
        const context = this;
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

            if (navigation !== null) currPage.append(navigation, this.#createSaveForLaterButton());
        }
    }

    #initializePagingSuitableFormValidation() {
        const context = this;
        this.#form.setAttribute("novalidate", "novalidate");
        this.#form.onsubmit = function(evt) {
            if ("sfl" === evt.submitter.value) return;

            const formElements = evt.target.querySelectorAll("[data-answer-required]");
            for (const formElement of formElements) {
                if ("validity" in formElement && !formElement.validity.valid) {
                    evt.preventDefault();
                    const page = formElement.closest("[data-page]");
                    const link = document.createElement("A");
                    link.href = `#${page.id}`;
                    link.click();
                    setTimeout(() => { formElement.reportValidity(); }, 750);
                    break;
                }
            }
        }
    }

    #initializeBackgroundSavingProcess() {
        const context = this;
        const path = context.#form.getAttribute("action");
        setInterval(() => {
            if (false === context.#backgroundSavingEnabled) {
                return;
            }

            const formData = new FormData(context.#form);
            formData.append("_assessement_action", "sfl-bg");
            context.#backgroundSavingEnabled = false;

            fetch(path, {
                method: "POST",
                body: formData
            }).then((response) => {
                context.#backgroundSavingEnabled = true;
            })
        }, 30000);
    }

    #appendSubmitButton() {
        let location = this.#paging === true ? this.#pager.lastChild : this.#form;
        if (location !== null) {
            const submitButton = this.#parser.parseFromString(`
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-theme-white bg-green" name="_assessment_action" value="submit">${this.#translation.buttonSubmit}</button>
                </div>
            `, "text/html").body.firstChild;
            location.appendChild(submitButton);
        }
    }

    #createSaveForLaterButton() {
        return this.#parser.parseFromString(`
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-theme-white bg-dark-blue" name="_assessment_action" value="sfl">${this.#translation.buttonSaveForLater}</button>
            </div>
        `, "text/html").body.firstChild;
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
        questionText.innerHTML = questionData.question;

        let questionAnswers = null;
        if ('static_text' !== questionData.type) {
            questionAnswers = document.createElement("DIV");
            questionAnswers.classList.add('evaluation-assessment-question-answers');
        }

        let finalize = true;
        switch (questionData.type) {
            case 'yes_no': {
                this.#createYesNoAnswers(questionData).forEach((answer) => questionAnswers.appendChild(answer));
                questionText.classList.add('required');
            } break;
            case 'weighted': {
                this.#createWeightedAnswers(questionData).forEach((answer) => questionAnswers.appendChild(answer));
                questionAnswers.classList.add('multichoice');
                questionText.classList.add('required');
            } break;
            case 'numerical_input': {
                questionAnswers.append(this.#createNumericalInputAnswer(questionData));
                questionText.classList.add('required');
            } break;
            case 'text_input': {
                questionAnswers.append(this.#createTextInputAnswer(questionData));
                questionText.classList.add('required');
            } break;
            case 'templated_text_input': {
                questionAnswers.append(this.#createTemplatedTextInputAnswer(questionData));
            } break;
            case 'multi_choice': {
                this.#createMultiChoiceAnswers(questionData).forEach((answer) => questionAnswers.appendChild(answer));
                questionAnswers.classList.add('multichoice');
            } break;
            case 'list_input': {
                questionAnswers.append(this.#createListInputAnswer(questionData));
            } break;
            case 'static_text': {
                question.classList.add(questionData.isHeading ? 'heading' : 'static-text');
                questionText.classList.add('no-bold');
            } break;
            default: finalize = false;
        }

        if (finalize) {
            let shouldDisableQuestion = false;
            if (questionData.dependency) {
                const dependency = questionData.dependency;
                this.#eventBus.attach(question);
                shouldDisableQuestion = true;
                question.on("answerchange", function(sender, { questionId, answer, checkType }) {
                    checkType = checkType || "equals";
                    if (dependency.questionId == questionId) {
                        let enableQuestion = false;
                        switch (checkType) {
                            case "equals": enableQuestion = answer == dependency.answer; break;
                            case "contains": enableQuestion = answer.includes(dependency.answer); break;
                        }
                        if (enableQuestion) {
                            context.#enableQuestion(this);
                        } else {
                            context.#disableQuestion(this);
                        }
                    }
                });
            }
            question.appendChild(questionText);
            if (null !== questionAnswers) question.appendChild(questionAnswers);
            if (shouldDisableQuestion) this.#disableQuestion(question);
        }
        return question;
    }

    #createYesNoAnswers(questionData) {
        const answers = [];
        let userAnswer = null;
        if (questionData.userAnswer) {
            userAnswer = questionData.userAnswer[0] || null;
        }
        questionData.answers.forEach((answerData) => {
            const checked = answerData.id === userAnswer ? ' checked' : '';
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="radio" value="${answerData.id}" name="evaluation_assessment[${questionData.id}]" data-value="${answerData.value}"${checked} data-answer-required required/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html");

            const input = answer.body.firstChild.querySelector("input");
            this.#eventBus.attach(input);
            input.addEventListener("click", function(event) {
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.dataset.value, checkType: 'equals' })
            });

            if (null !== userAnswer && '' !== checked) {
                this.#handlers.push(function () {
                    input.dispatch("answerchange", { questionId: questionData.id, answer: input.dataset.value, checkType: 'equals' });
                });
            }

            answers.push(answer.body.firstChild);
        });
        return answers;
    }

    #createWeightedAnswers(questionData) {
        const answers = [];
        const inputType = true === questionData.multipleWeighted ? 'checkbox' : 'radio';
        const requirement = questionData.multipleWeighted ? '' : ' data-answer-required required';
        const inputData = [];
        const userAnswer = questionData.userAnswer || [];
        let hasPrecheckedValues = false;
        questionData.answers.forEach((answerData) => {
            const checked = userAnswer.includes(answerData.id) ? ' checked' : '';
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="${inputType}" value="${answerData.id}" name="evaluation_assessment[${questionData.id}][]" data-value="${answerData.value}"${checked}${requirement}/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html");

            const input = answer.body.firstChild.querySelector("input");
            this.#eventBus.attach(input);
            inputData.push({ input: input, userAnswered: '' !== checked });
            input.addEventListener("click", function(event) {
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.dataset.value, checkType: 'contains' });
            });

            if ('' !== checked) {
                hasPrecheckedValues = true;
            }

            answers.push(answer.body.firstChild);
        });

        for (const inputItem of inputData) {
            inputItem.input.addEventListener("click", function(event) {
                const checkedValues = inputData.filter(i => i.input.checked === true).map(i => i.input.dataset.value);
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: checkedValues, checkType: 'contains' });
            });
            if (true === inputItem.userAnswered) {
                const checkedValues = inputData.filter(i => i.input.checked === true).map(i => i.input.dataset.value);
                inputItem.input.dispatch("answerchange", { questionId: questionData.id, answer: checkedValues, checkType: 'contains' });
            }
        }

        if (hasPrecheckedValues) {
            this.#handlers.push(function () {
                const checkedValues = inputs.filter(i => i.checked === true).map(i => i.dataset.value);
                context.#eventBus.notifyListeners("answerchange", null, { questionId: questionData.id, answer: checkedValues, checkType: 'contains' })
            })
        }

        return answers;
    }

    #createMultiChoiceAnswers(questionData) {
        const context = this;
        const answers = [];
        const inputs = [];
        const userAnswer = questionData.userAnswer || { selected: [], added: [] };
        let hasPrecheckedValues = false;
        questionData.answers.forEach((answerData) => {
            const checked = userAnswer.selected.includes(answerData.id) ? ' checked' : '';
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="checkbox" value="${answerData.id}" name="evaluation_assessment[${questionData.id}][choices][]" data-value="${answerData.value}"${checked}/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html");

            const input = answer.body.firstChild.querySelector("input");
            inputs.push(input);
            answers.push(answer.body.firstChild);

            if ('' !== checked) {
                hasPrecheckedValues = true;
            }
        });

        inputs.forEach((input) => {
            this.#eventBus.attach(input);
            input.addEventListener("click", function(event) {
                const checkedValues = inputs.filter(i => i.checked === true).map(i => i.dataset.value);
                event.target.dispatch("answerchange", { questionId: questionData.id, answer: checkedValues, checkType: 'contains' });
            });
        });

        if (true === questionData.other) {
            const otherButton = this.#parser.parseFromString(`<button type="button" class="btn btn-theme-white bg-green w-fit">${this.#translation.buttonAdd}</button>`, "text/html").body.firstChild;
            otherButton.addEventListener("click", function() {
                const otherInput = context.#parser.parseFromString(`
                    <label>
                        <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}][other][]"/>
                    </label>
                `, "text/html").body.firstChild;
                this.parentElement.insertBefore(otherInput, this);
            });

            for (const addedAnswer of userAnswer.added) {
                const addedAnswerElement = this.#parser.parseFromString(`
                    <label>
                        <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}][other][]" value="${addedAnswer}"/>
                    </label>`, "text/html").body.firstChild
                ;
                answers.push(addedAnswerElement);
            }
            answers.push(otherButton);
        }

        if (hasPrecheckedValues) {
            this.#handlers.push(function () {
                const checkedValues = inputs.filter(i => i.checked === true).map(i => i.dataset.value);
                context.#eventBus.notifyListeners("answerchange", null, { questionId: questionData.id, answer: checkedValues, checkType: 'contains' })
            })
        }

        return answers;
    }

    #createNumericalInputAnswer(questionData) {
        let userAnswer = questionData.userAnswer || '';
        const answer = this.#parser.parseFromString(`
            <label class="evaluation-assessment-question-answer">
                <input type="number" step="0.01" class="form-input" name="evaluation_assessment[${questionData.id}]" value="${userAnswer}" data-answer-required required/>
            </label>
        `, "text/html");

        const input = answer.body.firstChild.querySelector("input");
        this.#eventBus.attach(input);
        input.addEventListener("input", function(event) {
            event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.value, checkType: 'equals' });
        });

        if ('' !== userAnswer) {
            this.#handlers.push(function () {
                input.dispatch("answerchange", { question: questionData.id, answer: input.value, checkType: 'equals' });
            });
        }

        return answer.body.firstChild;
    }

    #createTextInputAnswer(questionData) {
        let userAnswer = questionData.userAnswer || '';
        let answer, input;
        if (true === questionData.largeText) {
            answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <textarea class="form-textarea" name="evaluation_assessment[${questionData.id}]" data-answer-required required>${userAnswer}</textarea>
                </label>
            `, "text/html");
            input = answer.body.firstChild.querySelector("textarea");
        } else {
            answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}]" value="${userAnswer}" data-answer-required required/>
                </label>
            `, "text/html");
            input = answer.body.firstChild.querySelector("input");
        }
        this.#eventBus.attach(input);
        input.addEventListener("input", function(event) {
            event.target.dispatch("answerchange", { questionId: questionData.id, answer: event.target.value, checkType: 'equals' });
        });

        if ('' !== userAnswer) {
            this.#handlers.push(function () {
                input.dispatch("answerchange", { question: questionData.id, answer: input.value, checkType: 'equals' });
            });
        }

        return answer.body.firstChild;
    }

    #createTemplatedTextInputAnswer(questionData) {
        const answerData = questionData.answers[0];
        let answerText = "", answerFields = [];
        const userAnswer = questionData.userAnswer || {};

        if (answerData) {
            answerText = answerData.text;
            answerFields = answerData.fields;
        }

        let answerRaw = `<div style="white-space: pre-wrap">${answerText}</div>`;
        for (const field of answerFields) {
            let requirementAttributes = '';
            let requirementClass = '';
            if (false === field.properties.some(prop => prop.toLowerCase() === 'optional')) {
                requirementAttributes = ' data-answer-required required';
                requirementClass = ' required';
            }
            const pattern = new RegExp(`{{\\s*${field.name}[\\|\\s\\w]*\\s*}}`);
            const value = field.name in userAnswer ? userAnswer[field.name] : '';
            const inputRaw = `
                <label class="evaluation-assessment-question-answer--inline${requirementClass}" style="white-space: normal">
                    <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}][${field.name}]" value="${value}"${requirementAttributes}/>
                </label>`.trim()
            ;
            answerRaw = answerRaw.replace(pattern, inputRaw);
        }

        const answer = this.#parser.parseFromString(answerRaw, "text/html");
        return answer.body.firstChild;
    }

    #createListInputAnswer(questionData) {
        const answer = this.#parser.parseFromString(`
            <label class="evaluation-assessment-question-answer:column">
                <button type="button" class="btn btn-theme-white btn-sm bg-green">
                    <svg viewBox="0 0 24 24"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"></path></svg>
                    ${this.#translation.buttonAdd}
                </button>
            </label>
        `, "text/html").body.firstChild;

        const userAnswer = questionData.userAnswer || [];
        for (const item of userAnswer) {
            const input = this.#parser.parseFromString(`
                <input type="text" class="form-input" name="evaluation_assessment[${questionData.id}][]" value="${item}"/>
            `, "text/html").body.firstChild;
            answer.appendChild(input);
        }

        answer.querySelector("button").addEventListener("click", () => {
            const input = this.#parser.parseFromString(`
                <div>
                    <input type="hidden" name="evaluation_assessment[${questionData.id}][]"/>
                    <input type="text" class="form-input"/>
                </div>
            `, "text/html").body.firstChild;

            const inputHidden = input.querySelector("input[type='hidden']");
            input.querySelector("input[type='text']").addEventListener("input", (evt) => {
                inputHidden.value = evt.target.value;
            });

            answer.appendChild(input);
        });

        return answer;
    }

    #disableQuestion(questionElement) {
        const context = this;
        if (!questionElement.classList.contains("hide")) questionElement.classList.add("hide");
        questionElement.querySelectorAll("input, textarea").forEach((input) => {
            if ("answerRequired" in input.dataset) input.required = false;
            input.disabled = true;
            this.#sendEmptyAnswerChangeEvent(input, questionElement);
        });
    }

    #enableQuestion(questionElement) {
        if (questionElement.classList.contains("hide")) questionElement.classList.remove("hide");
        questionElement.querySelectorAll("input, textarea").forEach((input) => {
            if ("answerRequired" in input.dataset) input.required = true;
            input.disabled = false;
            this.#resendAnswerChangeEvent(input, questionElement);
        });
    }

    #sendEmptyAnswerChangeEvent(input, question) {
        if (false === (input.hasAttribute("type") && "id" in question.dataset && "type" in question.dataset)) {
            return;
        }
        switch (question.dataset.type) {
            case "yes_no":
            case "text_input":
            case "numerical_input":
            case "weighted": question.dispatch("answerchange", { questionId: question.dataset.id, answer: null, checkType: 'equals' }); break;
            case "multi_choice": question.dispatch("answerchange", { questionId: question.dataset.id, answer: [], checkType: 'contains' }); break;
        }
    }

    #resendAnswerChangeEvent(input, question) {
        if (false === (input.hasAttribute("type") && "id" in question.dataset && "type" in question.dataset)) {
            return;
        }

        if (["yes_no", "weight"].includes(question.dataset.type) && true === input.checked) {
            question.dispatch("answerchange", { questionId: question.dataset.id, answer: input.dataset.value, checkType: 'equals' });
        } else if (["text_input", "numerical_input"].includes(question.dataset.type)) {
            question.dispatch("answerchange", { questionId: question.dataset.id, answer: input.value, checkType: 'equals' })
        } else if ("multi_choice" === question.dataset.type) {
            const inputs = question.querySelectorAll("input");
            const checkedValues = Array.from(inputs).filter(i => i.checked === true).map(i => i.dataset.value);
            question.dispatch("answerchange", { questionId: question.dataset.id, answer: checkedValues, checkType: 'contains' });
        }
    }
}

global.PracticalSubmoduleAssessment = PracticalSubmoduleAssessment;

