<?php

namespace App\Misc;

use App\Entity\PracticalSubmodule;

class PSExporter
{
    private ?PracticalSubmodule $practicalSubmodule = null;
    private array $tasks = [];

    private int $orderIndex = 1;
    private int $questionIndex = 1;
    private array $questionMapping = [];

    public function __construct(PracticalSubmodule $practicalSubmodule)
    {
        $this->practicalSubmodule = $practicalSubmodule;
    }

    public function export(): array
    {
        if (!empty($this->tasks)) return $this->tasks;

        $this->makeCreateSubmoduleTask();
        $this->makeCreateQuestionTasks();
        $this->makeCreateQuestionDependencyTasks();
        $this->makeCreatePageTasks();

        return $this->tasks;
    }

    private function makeCreateSubmoduleTask(): void
    {
        $this->tasks[] = [
            'task_order' => $this->orderIndex++,
            'task_op' => Tasks::CREATE_SUBMODULE,
            'task_props' => [
                'name' => $this->practicalSubmodule->getName(),
                'description' => $this->practicalSubmodule->getDescription(),
                'paging' => $this->practicalSubmodule->isPaging(),
                'tags' => $this->practicalSubmodule->getTags()
            ]
        ];
    }

    private function makeCreateQuestionTasks(): void
    {
        foreach ($this->practicalSubmodule->getPracticalSubmoduleQuestions() as $question) {
            $id = $this->questionIndex++;
            $this->questionMapping[$question->getId()] = $id;

            $task = [
                'task_order' => $this->orderIndex++,
                'task_op' => Tasks::CREATE_QUESTION,
                'task_props' => [
                    'id' => $id,
                    'type' => $question->getType(),
                    'questionText' => $question->getQuestionText(),
                    'evaluable' => $question->isEvaluable(),
                    'position' => $question->getPosition(),
                    'answers' => []
                ]
            ];

            foreach ($question->getPracticalSubmoduleQuestionAnswers() as $answer) {
                $task['task_props']['answers'][] = [
                    'answerText' => $answer->getAnswerText(),
                    'answerValue' => $answer->getAnswerValue(),
                    'templatedTextFields' => $answer->getTemplatedTextFields()
                ];
            }

            $this->tasks[] = $task;
        }
    }

    private function makeCreateQuestionDependencyTasks(): void
    {
        foreach ($this->practicalSubmodule->getPracticalSubmoduleQuestions() as $question) {
            $dependentQuestionId = $question->getDependentPracticalSubmoduleQuestion()?->getId();
            if (null === $dependentQuestionId || false === key_exists($dependentQuestionId, $this->questionMapping)) {
                continue;
            }

            $this->tasks[] = [
                'task_order' => $this->orderIndex++,
                'task_op' => Tasks::CREATE_QUESTION_DEPENDENCY,
                'task_props' => [
                    'questionId' => $this->questionMapping[$question->getId()],
                    'dependentId' => $this->questionMapping[$dependentQuestionId],
                    'dependentValue' => $question->getDependentValue()
                ]
            ];
        }
    }

    private function makeCreatePageTasks(): void
    {
        foreach ($this->practicalSubmodule->getPracticalSubmodulePages() as $page) {
            $task = [
                'task_order' => $this->orderIndex++,
                'task_op' => Tasks::CREATE_PAGE,
                'task_props' => [
                    'title' => $page->getTitle(),
                    'description' => $page->getDescription(),
                    'position' => $page->getPosition(),
                    'questions' => []
                ]
            ];

            foreach ($page->getPracticalSubmoduleQuestions() as $question) {
                if (true === key_exists($question->getId(), $this->questionMapping)) {
                    $task['task_props']['questions'][] = $this->questionMapping[$question->getId()];
                }
            }

            $this->tasks[] = $task;
        }
    }
}