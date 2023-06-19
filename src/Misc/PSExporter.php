<?php

namespace App\Misc;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleQuestion;

class PSExporter
{
    private ?PracticalSubmodule $practicalSubmodule = null;
    private array $tasks = [];

    private int $orderIndex = 1;
    private int $questionIndex = 1;
    private int $processorIndex = 1;
    private array $questionMapping = [];
    private array $processorMapping = [];

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
        $this->makeCreateProcessorTasks();
        $this->makeCreateProcessorDependencyOnQuestionTasks();
        $this->makeCreateProcessorDependencyOnProcessorTasks();
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
                'task_op' => Tasks::BIND_QUESTION_DEPENDENCY,
                'task_props' => [
                    'question' => $this->questionMapping[$question->getId()],
                    'dependent' => $this->questionMapping[$dependentQuestionId],
                    'value' => $question->getDependentValue()
                ]
            ];
        }
    }

    private function makeCreateProcessorTasks(): void
    {
        foreach ($this->practicalSubmodule->getPracticalSubmoduleProcessors() as $processor) {
            $id = $this->processorIndex++;
            $this->processorMapping[$processor->getId()] = $id;

            $task = [
                'task_order' => $this->orderIndex++,
                'task_op' => Tasks::CREATE_PROCESSOR,
                'task_props' => [
                    'id' =>  $id,
                    'type' => $processor->getType(),
                    'name' => $processor->getName(),
                    'included' => $processor->isIncluded(),
                    'position' => $processor->getPosition()
                ]
            ];

            $implProps = [];
            switch ($processor->getType()) {
                case $processor::TYPE_SIMPLE: {
                    $impl = $processor->getPracticalSubmoduleProcessorSimple();
                    if (null !== $impl) {
                        $implProps['expectedValue'] = $impl->getExpectedValue();
                        $implProps['resultText'] = $impl->getResultText();
                    }
                } break;
                case $processor::TYPE_SUM_AGGREGATE: {
                    $impl = $processor->getPracticalSubmoduleProcessorSumAggregate();
                    if (null !== $impl) {
                        $implProps['expectedValueRangeStart'] = $impl->getExpectedValueRangeStart();
                        $implProps['expectedValueRangeEnd'] = $impl->getExpectedValueRangeEnd();
                        $implProps['resultText'] = $impl->getResultText();
                    }
                } break;
                case $processor::TYPE_PRODUCT_AGGREGATE: {
                    $impl = $processor->getPracticalSubmoduleProcessorProductAggregate();
                    if (null !== $impl) {
                        $implProps['expectedValueRangeStart'] = $impl->getExpectedValueRangeStart();
                        $implProps['expectedValueRangeEnd'] = $impl->getExpectedValueRangeEnd();
                        $implProps['resultText'] = $impl->getResultText();
                    }
                } break;
                case $processor::TYPE_TEMPLATED_TEXT: {
                    $impl = $processor->getPracticalSubmoduleProcessorTemplatedText();
                    if (null !== $impl) {
                        $implProps['resultText'] = $impl->getResultText();
                    }
                } break;
            }

            $task['task_props']['impl'] = $implProps;

            $this->tasks[] = $task;
        }
    }

    private function makeCreateProcessorDependencyOnQuestionTasks(): void
    {
        foreach ($this->practicalSubmodule->getPracticalSubmoduleProcessors() as $processor) {
            if (false === key_exists($processor->getId(), $this->processorMapping)) {
                continue;
            }

            $dependentIds = [];
            switch ($processor->getType()) {
                case $processor::TYPE_SIMPLE: {
                    $questionId = $processor?->getPracticalSubmoduleProcessorSimple()?->getPracticalSubmoduleQuestion()?->getId();
                    if (null !== $questionId && key_exists($questionId, $this->questionMapping)) {
                        $dependentIds[] = $this->questionMapping[$questionId];
                    }
                } break;
                case $processor::TYPE_SUM_AGGREGATE: {
                    $questionIds = $processor?->getPracticalSubmoduleProcessorSumAggregate()?->getPracticalSubmoduleQuestions()->map(function (PracticalSubmoduleQuestion $psq) { return $psq->getId(); });
                    if (null !== $questionIds) {
                        foreach ($questionIds as $questionId) {
                            if (true === key_exists($questionId, $this->questionMapping)) {
                                $dependentIds[] = $this->questionMapping[$questionId];
                            }
                        }
                    }
                } break;
                case $processor::TYPE_PRODUCT_AGGREGATE: {
                    $questionIds = $processor?->getPracticalSubmoduleProcessorProductAggregate()?->getPracticalSubmoduleQuestions()->map(function (PracticalSubmoduleQuestion $psq) { return $psq->getId(); });
                    if (null !== $questionIds) {
                        foreach ($questionIds as $questionId) {
                            if (key_exists($questionId, $this->questionMapping)) {
                                $dependentIds[] = $this->questionMapping[$questionId];
                            }
                        }
                    }
                } break;
                case $processor::TYPE_TEMPLATED_TEXT: {
                    $questionId = $processor?->getPracticalSubmoduleProcessorTemplatedText()?->getPracticalSubmoduleQuestion()?->getId();
                    if (null !== $questionId && key_exists($questionId, $this->questionMapping)) {
                        $dependentIds[] = $this->questionMapping[$questionId];
                    }
                } break;
            }

            foreach ($dependentIds as $dependentId) {
                $this->tasks[] = [
                    'task_order' => $this->orderIndex++,
                    'task_op' => Tasks::BIND_PROCESSOR_DEPENDENCY_ON_QUESTION,
                    'task_props' => [
                        'processor' => $this->processorMapping[$processor->getId()],
                        'dependent' => $dependentId
                    ]
                ];
            }
        }
    }

    private function makeCreateProcessorDependencyOnProcessorTasks(): void
    {
        foreach ($this->practicalSubmodule->getPracticalSubmoduleProcessors() as $processor) {
            if (false === (key_exists($processor->getId(), $this->processorMapping) && in_array($processor->getType(), $processor::getProcessorProcessingProcessorTypes()))) {
                continue;
            }

            $dependentIds = [];
            switch ($processor->getType()) {
                case $processor::TYPE_SUM_AGGREGATE: {
                    $processorIds = $processor?->getPracticalSubmoduleProcessorSumAggregate()?->getPracticalSubmoduleProcessors()->map(function (PracticalSubmoduleProcessor $psp) { return $psp->getId(); });
                    if (null !== $processorIds) {
                        foreach ($processorIds as $processorId) {
                            if (true === key_exists($processorId, $this->processorMapping)) {
                                $dependentIds[] = $this->processorMapping[$processorId];
                            }
                        }
                    }
                } break;
                case $processor::TYPE_PRODUCT_AGGREGATE: {
                    $processorIds = $processor?->getPracticalSubmoduleProcessorProductAggregate()?->getPracticalSubmoduleProcessors()->map(function (PracticalSubmoduleProcessor $psp) { return $psp->getId(); });
                    if (null !== $processorIds) {
                        foreach ($processorIds as $processorId) {
                            if (true === key_exists($processorId, $this->processorMapping)) {
                                $dependentIds[] = $this->processorMapping[$processorId];
                            }
                        }
                    }
                    break;
                }
            }

            foreach ($dependentIds as $dependentId) {
                $this->tasks[] = [
                    'task_order' => $this->orderIndex++,
                    'task_op' => Tasks::BIND_PROCESSOR_DEPENDENCY_ON_PROCESSOR,
                    'task_props' => [
                        'processor' => $this->processorMapping[$processor->getId()],
                        'dependent' => $dependentId
                    ]
                ];
            }
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