class EvaluationAssessment {
    #form;
    #parser;
    constructor(querySelector, assessmentData) {
        this.#parser = new DOMParser();
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
        const question = document.createElement("DIV");
        question.classList.add('evaluation-assessment-question');
        question.dataset.id = questionData.id;
        question.dataset.type = questionData.type;

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
            default: finalize = false;
        }

        if (finalize) question.append(questionText, questionAnswers);
        return question;
    }

    #createYesNoAnswers(questionData) {
        const answers = [];
        questionData.answers.forEach((answerData) => {
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="radio" value="${answerData.value}" name="evaluation_assessment[${questionData.id}]" required/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html")
            answers.push(answer.body.firstChild);
        });
        return answers;
    }

    #createWeightedAnswers(questionData) {
        const answers = [];
        questionData.answers.forEach((answerData) => {
            const answer = this.#parser.parseFromString(`
                <label class="evaluation-assessment-question-answer">
                    <input type="radio" value="${answerData.value}" name="evaluation_assessment[${questionData.id}]" required/>
                    <span>${answerData.text}</span>
                </label>
            `, "text/html")
            answers.push(answer.body.firstChild);
        });
        return answers;
    }

    #createNumericalInputAnswer(questionData) {
        const answer = this.#parser.parseFromString(`
            <label class="evaluation-assessment-question-answer">
                <input type="number" class="form-input" name="evaluation_assessment[${questionData.id}]"/>
            </label>
        `, "text/html")
        return answer.body.firstChild;
    }
}

global.EvaluationAssessment = EvaluationAssessment;

