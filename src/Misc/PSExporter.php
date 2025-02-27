<?php

namespace App\Misc;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\Entity\Translation;

class PSExporter
{
    private ?PracticalSubmodule $practicalSubmodule = null;
    private ?EntityManagerInterface $em = null;
    private ?string $localeAlternate = null;
    private array $tasks = [];
    private ?TranslationRepository $translationRepository = null;

    private int $orderIndex = 1;
    private int $questionIndex = 1;
    private int $processorIndex = 1;
    private array $questionMapping = [];
    private array $processorMapping = [];

    public function __construct(PracticalSubmodule $practicalSubmodule, EntityManagerInterface $em, string $localeAlternate)
    {
        $this->practicalSubmodule = $practicalSubmodule;
        $this->em = $em;
        $this->localeAlternate = $localeAlternate;
        $this->translationRepository = $this->em->getRepository(Translation::class);
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
        $this->makeCreateProcessorGroupTasks();
        return $this->tasks;
    }

    private function makeCreateSubmoduleTask(): void
    {
        $task = [
            'task_order' => $this->orderIndex++,
            'task_op' => Tasks::CREATE_SUBMODULE,
            'task_props' => [
                'name' => $this->practicalSubmodule->getName(),
                'public_name' => $this->practicalSubmodule->getPublicName(),
                'description' => $this->practicalSubmodule->getDescription(),
                'report_comment' => $this->practicalSubmodule->getReportComment(),
                'paging' => $this->practicalSubmodule->isPaging(),
                'op_mode' => $this->practicalSubmodule->getModeOfOperation(),
                'export_type' => $this->practicalSubmodule->getExportType()
            ]
        ];

        $trans = $this->translationRepository->findTranslations($this->practicalSubmodule);
        if (true === key_exists($this->localeAlternate, $trans)) {
            $transName = $trans[$this->localeAlternate]['name'] ?? null;
            $transPublicName = $trans[$this->localeAlternate]['publicName'] ?? null;
            $transDescription = $trans[$this->localeAlternate]['description'] ?? null;
            $transReportComment = $trans[$this->localeAlternate]['reportComment'] ?? null;
            $task['task_props']['trans'] = [];
            if (null !== $transName) $task['task_props']['trans']['name'] = $transName;
            if (null !== $transPublicName) $task['task_props']['trans']['public_name'] = $transPublicName;
            if (null !== $transDescription) $task['task_props']['trans']['description'] = $transDescription;
            if (null !== $transReportComment) $task['task_props']['trans']['report_comment'] = $transReportComment;
        }

        $this->tasks[] = $task;
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
                    'answers' => [],
                    'other' => $question->isOtherEnabled(),
                    'heading' => $question->getIsHeading(),
                    'disabled' => $question->isDisabled(),
                    'multipleWeighted' => $question->isMultipleWeighted(),
                    'largeText' => $question->isLargeText(),
                    'listWithSublist' => $question->isListWithSublist(),
                    'template' => $question->getTemplate(),
                    'templateVariables' => $question->getTemplateVariables(),
                    'modal' => $question->isModal(),
                ]
            ];

            $trans = $this->translationRepository->findTranslations($question);
            if (true === key_exists($this->localeAlternate, $trans)) {
                $transQuestionText = $trans[$this->localeAlternate]['questionText'] ?? null;
                if (null !== $transQuestionText) $task['task_props']['trans'] = ['questionText' => $transQuestionText];
            }

            foreach ($question->getPracticalSubmoduleQuestionAnswers() as $answer) {
                $answerProps = [
                    'answerText' => $answer->getAnswerText(),
                    'answerValue' => $answer->getAnswerValue(),
                    'templatedTextFields' => $answer->getTemplatedTextFields()
                ];

                $trans = $this->translationRepository->findTranslations($answer);
                if (true === key_exists($this->localeAlternate, $trans)) {
                    $transAnswerText = $trans[$this->localeAlternate]['answerText'] ?? null;
                    if (null !== $transAnswerText) $answerProps['trans'] = ['answerText' => $transAnswerText];
                }

                $task['task_props']['answers'][] = $answerProps;
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
                    'position' => $processor->getPosition(),
                    'disabled' => $processor->isDisabled(),
                    'exportTag' => $processor->getExportTag()
                ]
            ];

            $trans = $this->translationRepository->findTranslations($processor);
            if (true === key_exists($this->localeAlternate, $trans)) {
                $transName = $trans[$this->localeAlternate]['name'] ?? null;
                if (null !== $transName) $task['task_props']['trans'] = ['name' => $transName];
            }

            $implProps = [];
            switch ($processor->getType()) {
                case $processor::TYPE_SIMPLE: {
                    $impl = $processor->getPracticalSubmoduleProcessorSimple();
                    if (null !== $impl) {
                        $implProps['expectedValue'] = $impl->getExpectedValue();
                        $implProps['resultText'] = $impl->getResultText();

                        $trans = $this->translationRepository->findTranslations($impl);
                        if (true === key_exists($this->localeAlternate, $trans)) {
                            $transResultText = $trans[$this->localeAlternate]['resultText'] ?? null;
                            if (null !== $transResultText) $implProps['trans'] = ['resultText' => $transResultText];
                        }
                    }
                } break;
                case $processor::TYPE_HTML: {
                    $impl = $processor->getPracticalSubmoduleProcessorHtml();
                    if (null !== $impl) {
                        $implProps['expectedValue'] = $impl->getExpectedValue();
                        $implProps['resultText'] = $impl->getResultText();

                        $trans = $this->translationRepository->findTranslations($impl);
                        if (true === key_exists($this->localeAlternate, $trans)) {
                            $transResultText = $trans[$this->localeAlternate]['resultText'] ?? null;
                            if (null !== $transResultText) $implProps['trans'] = ['resultText' => $transResultText];
                        }
                    }
                } break;
                case $processor::TYPE_SUM_AGGREGATE: {
                    $impl = $processor->getPracticalSubmoduleProcessorSumAggregate();
                    if (null !== $impl) {
                        $implProps['expectedValueRangeStart'] = $impl->getExpectedValueRangeStart();
                        $implProps['expectedValueRangeEnd'] = $impl->getExpectedValueRangeEnd();
                        $implProps['resultText'] = $impl->getResultText();

                        $trans = $this->translationRepository->findTranslations($impl);
                        if (true === key_exists($this->localeAlternate, $trans)) {
                            $transResultText = $trans[$this->localeAlternate]['resultText'] ?? null;
                            if (null !== $transResultText) $implProps['trans'] = ['resultText' => $transResultText];
                        }
                    }
                } break;
                case $processor::TYPE_PRODUCT_AGGREGATE: {
                    $impl = $processor->getPracticalSubmoduleProcessorProductAggregate();
                    if (null !== $impl) {
                        $implProps['expectedValueRangeStart'] = $impl->getExpectedValueRangeStart();
                        $implProps['expectedValueRangeEnd'] = $impl->getExpectedValueRangeEnd();
                        $implProps['resultText'] = $impl->getResultText();

                        $trans = $this->translationRepository->findTranslations($impl);
                        if (true === key_exists($this->localeAlternate, $trans)) {
                            $transResultText = $trans[$this->localeAlternate]['resultText'] ?? null;
                            if (null !== $transResultText) $implProps['trans'] = ['resultText' => $transResultText];
                        }
                    }
                } break;
                case $processor::TYPE_TEMPLATED_TEXT: {
                    $impl = $processor->getPracticalSubmoduleProcessorTemplatedText();
                    if (null !== $impl) {
                        $implProps['resultText'] = $impl->getResultText();

                        $trans = $this->translationRepository->findTranslations($impl);
                        if (true === key_exists($this->localeAlternate, $trans)) {
                            $transResultText = $trans[$this->localeAlternate]['resultText'] ?? null;
                            if (null !== $transResultText) $implProps['trans'] = ['resultText' => $transResultText];
                        }
                    }
                } break;
                case $processor::TYPE_RESULT_COMBINER: {
                    $impl = $processor->getPracticalSubmoduleProcessorResultCombiner();
                    if (null !== $impl) {
                        $implProps['resultText'] = $impl->getResultText();
                        $implProps['separateBy'] = $impl->getSeparateBy();

                        $trans = $this->translationRepository->findTranslations($impl);
                        if (true === key_exists($this->localeAlternate, $trans)) {
                            $transResultText = $trans[$this->localeAlternate]['resultText'] ?? null;
                            if (null !== $transResultText) $implProps['trans'] = ['resultText' => $transResultText];
                        }
                    }
                }
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
                case $processor::TYPE_HTML: {
                    $questionId = $processor?->getPracticalSubmoduleProcessorHtml()?->getPracticalSubmoduleQuestion()?->getId();
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
                case $processor::TYPE_RESULT_COMBINER: {
                    $processorIds = $processor?->getPracticalSubmoduleProcessorResultCombiner()?->getPracticalSubmoduleProcessors()->map(function (PracticalSubmoduleProcessor $psp) { return $psp->getId(); });
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

            $trans = $this->translationRepository->findTranslations($page);
            if (true === key_exists($this->localeAlternate, $trans)) {
                $task['task_props']['trans'] = [];
                $transTitle = $trans[$this->localeAlternate]['title'] ?? null;
                $transDescription = $trans[$this->localeAlternate]['description'] ?? null;
                if (null !== $transTitle) $task['task_props']['trans']['title'] = $transTitle;
                if (null !== $transDescription) $task['task_props']['trans']['description'] = $transDescription;
            }

            $this->tasks[] = $task;
        }
    }

    private function makeCreateProcessorGroupTasks()
    {
        foreach ($this->practicalSubmodule->getPracticalSubmoduleProcessorGroups() as $pg) {
            $task = [
                'task_order' => $this->orderIndex++,
                'task_op' => Tasks::CREATE_PROCESSOR_GROUP,
                'task_props' => [
                    'title' => $pg->getTitle(),
                    'position' => $pg->getPosition(),
                    'processors' => []
                ]
            ];

            foreach ($pg->getPracticalSubmoduleProcessors() as $processor) {
                if (key_exists($processor->getId(), $this->processorMapping)) {
                    $task['task_props']['processors'][] = $this->processorMapping[$processor->getId()];
                }
            }

            $trans = $this->translationRepository->findTranslations($pg);
            if (key_exists($this->localeAlternate, $trans)) {
                $task['task_props']['trans'] = [];
                $transTitle = $trans[$this->localeAlternate]['title'] ?? null;
                if (null !== $transTitle) $task['task_props']['trans']['title'] = $transTitle;
            }

            $this->tasks[] = $task;
        }
    }
}