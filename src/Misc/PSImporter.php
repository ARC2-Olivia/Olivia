<?php

namespace App\Misc;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmodulePage;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use App\Entity\PracticalSubmoduleProcessorSimple;
use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use App\Entity\PracticalSubmoduleProcessorTemplatedText;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Exception\PSImport\ErroneousFirstTaskException;
use App\Exception\PSImport\MissingTaskOrderKeyException;
use App\Exception\PSImport\WrongFirstTaskTypeException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Exception;

class PSImporter
{
    private ?PracticalSubmodule $practicalSubmodule = null;
    private ?array $tasks = null;
    private ?EntityManagerInterface $em = null;

    private int $taskIndex = 1;
    private array $questionMapping = [];
    private array $processorMapping = [];

    public function __construct(array $tasks, EntityManagerInterface $em)
    {
        $this->tasks = $tasks;
        $this->em = $em;
    }

    /**
     * @throws WrongFirstTaskTypeException
     * @throws ErroneousFirstTaskException
     * @throws MissingTaskOrderKeyException
     */
    public function import(): ?PracticalSubmodule
    {
        $this->sortTasks();
        foreach ($this->tasks as $task) {
            $isValid = is_array($task) && isset($task['task_op'], $task['task_props']) && is_array($task['task_props']);

            if (false === $isValid) {
                if (1 === $this->taskIndex) {
                    throw ErroneousFirstTaskException::withDefaultTranslationKey();
                }
                continue;
            }

            if (1 === $this->taskIndex && Tasks::CREATE_SUBMODULE !== $task['task_op']) {
                throw WrongFirstTaskTypeException::withDefaultTranslationKey();
            }

            switch ($task['task_op']) {
                case Tasks::CREATE_SUBMODULE:                       $this->doCreateSubmoduleTask($task['task_props']);                    break;
                case Tasks::CREATE_QUESTION:                        $this->doCreateQuestionTask($task['task_props']);                     break;
                case Tasks::BIND_QUESTION_DEPENDENCY:               $this->doBindQuestionDependencyTask($task['task_props']);             break;
                case Tasks::CREATE_PROCESSOR:                       $this->doCreateProcessorTask($task['task_props']);                    break;
                case Tasks::BIND_PROCESSOR_DEPENDENCY_ON_QUESTION:  $this->doBindProcessorDependencyOnQuestionTask($task['task_props']);  break;
                case Tasks::BIND_PROCESSOR_DEPENDENCY_ON_PROCESSOR: $this->doBindProcessorDependencyOnProcessorTask($task['task_props']); break;
                case Tasks::CREATE_PAGE:                            $this->doCreatePageTask($task['task_props']);                         break;
            }

            $this->taskIndex++;
        }

        $this->em->flush();
        return $this->practicalSubmodule;
    }

    private function sortTasks(): void
    {
        /**
         * @throws MissingTaskOrderKeyException
         */
        usort( $this->tasks, function ($t1, $t2) {
            if (false === isset($t1['task_order'], $t2['task_order'])) {
                throw MissingTaskOrderKeyException::withDefaultTranslationKey();
            }
            if ($t1['task_order'] === $t2['task_order']) {
                return 0;
            }
            return $t1['task_order'] > $t2['task_order'] ? 1 : -1;
        });
    }

    private function allKeysExist(array $keys, array $array): bool {
        $result = true;
        foreach ($keys as $key) {
            $result = $result && key_exists($key, $array);
            if (false === $result) {
                break;
            }
        }
        return $result;
    }

    private function doCreateSubmoduleTask(array $props): void
    {
        $this->practicalSubmodule = (new PracticalSubmodule())
            ->setName($props['name'] ?? '')
            ->setDescription($props['description'] ?? '')
            ->setPaging($props['paging'] ?? false)
            ->setTags($props['tags'] ?? [])
        ;
        $this->em->persist($this->practicalSubmodule);
    }

    private function doCreateQuestionTask(array $props): void
    {
        if (false === $this->allKeysExist(['id', 'type', 'questionText', 'evaluable', 'position'], $props)) {
            return;
        }

        $question = (new PracticalSubmoduleQuestion())
            ->setPracticalSubmodule($this->practicalSubmodule)
            ->setType($props['type'])
            ->setQuestionText($props['questionText'])
            ->setEvaluable($props['evaluable'])
            ->setPosition($props['position'])
        ;
        $this->em->persist($question);
        $this->questionMapping[$props['id']] = $question;

        if (false === key_exists('answers', $props)) {
            return;
        }

        foreach ($props['answers'] as $answerProps) {
            if (false === $this->allKeysExist(['answerText', 'answerValue', 'templatedTextFields'], $answerProps)) {
                continue;
            }

            $answer = (new PracticalSubmoduleQuestionAnswer())
                ->setPracticalSubmoduleQuestion($question)
                ->setAnswerText($answerProps['answerText'])
                ->setAnswerValue($answerProps['answerValue'])
                ->setTemplatedTextFields($answerProps['templatedTextFields'])
            ;
            $this->em->persist($answer);
        }
    }

    private function doBindQuestionDependencyTask(array $props): void
    {
        if (false === $this->allKeysExist(['question', 'dependent', 'value'], $props)) {
            return;
        }

        if (false === $this->allKeysExist([$props['question'], $props['dependent']], $this->questionMapping)) {
            return;
        }

        /** @var PracticalSubmoduleQuestion $question */
        $question = $this->questionMapping[$props['question']];
        $dependent = $this->questionMapping[$props['dependent']];
        $question->setDependentPracticalSubmoduleQuestion($dependent)->setDependentValue($props['value']);
        $this->em->persist($question);
    }

    private function doCreateProcessorTask(array $props): void
    {
        if (false === $this->allKeysExist(['id', 'type', 'name', 'included', 'position', 'impl'], $props)) {
            return;
        }

        $processor = (new PracticalSubmoduleProcessor())
            ->setType($props['type'])
            ->setName($props['name'])
            ->setIncluded($props['included'])
            ->setPosition($props['position'])
        ;

        $this->em->persist($processor);
        $this->processorMapping[$props['id']] = $processor;

        if (true === empty($props['impl'])) {
            return;
        }

        $implProps = $props['impl'];
        switch ($processor->getType()) {
            case $processor::TYPE_SIMPLE: {
                if (false === $this->allKeysExist(['expectedValue', 'resultText'], $implProps)) return;
                $pspSimple = (new PracticalSubmoduleProcessorSimple())
                    ->setExpectedValue($implProps['expectedValue'])
                    ->setResultText($implProps['resultText'])
                ;
                $processor->setPracticalSubmoduleProcessorSimple($pspSimple);
                $this->em->persist($pspSimple);
            } break;
            case $processor::TYPE_SUM_AGGREGATE: {
                if (false === $this->allKeysExist(['expectedValueRangeStart', 'expectedValueRangeEnd', 'resultText'], $implProps)) return;
                $pspSumAggregate = (new PracticalSubmoduleProcessorSumAggregate())
                    ->setExpectedValueRangeStart($implProps['expectedValueRangeStart'])
                    ->setExpectedValueRangeEnd($implProps['expectedValueRangeEnd'])
                    ->setResultText($implProps['resultText'])
                ;
                $processor->setPracticalSubmoduleProcessorSumAggregate($pspSumAggregate);
                $this->em->persist($pspSumAggregate);
            } break;
            case $processor::TYPE_PRODUCT_AGGREGATE: {
                if (false === $this->allKeysExist(['expectedValueRangeStart', 'expectedValueRangeEnd', 'resultText'], $implProps)) return;
                $pspProductAggregate = (new PracticalSubmoduleProcessorProductAggregate())
                    ->setExpectedValueRangeStart($implProps['expectedValueRangeStart'])
                    ->setExpectedValueRangeEnd($implProps['expectedValueRangeEnd'])
                    ->setResultText($implProps['resultText'])
                ;
                $processor->setPracticalSubmoduleProcessorProductAggregate($pspProductAggregate);
                $this->em->persist($pspProductAggregate);
            } break;
            case $processor::TYPE_TEMPLATED_TEXT: {
                if (false === key_exists('resultText', $implProps)) return;
                $pspTemplatedText = (new PracticalSubmoduleProcessorTemplatedText())->setResultText($implProps['resultText']);
                $processor->setPracticalSubmoduleProcessorTemplatedText($pspTemplatedText);
                $this->em->persist($pspTemplatedText);
            } break;
        }
    }

    private function doBindProcessorDependencyOnQuestionTask(array $props): void
    {
        if (false === $this->allKeysExist(['processor', 'dependent'], $props)) {
            return;
        }

        $processorId = $props['processor'];
        $questionId = $props['dependent'];

        if (false === (key_exists($processorId, $this->processorMapping) && key_exists($questionId, $this->questionMapping))) {
            return;
        }

        /** @var PracticalSubmoduleProcessor $processor */
        $processor = $this->processorMapping[$processorId];
        $question = $this->questionMapping[$questionId];

        switch ($processor->getType()) {
            case $processor::TYPE_SIMPLE: {
                $pspSimple = $processor->getPracticalSubmoduleProcessorSimple();
                $pspSimple->setPracticalSubmoduleQuestion($question);
                $this->em->persist($pspSimple);
            } break;
            case $processor::TYPE_SUM_AGGREGATE: {
                $pspSumAggregate = $processor->getPracticalSubmoduleProcessorSumAggregate();
                $pspSumAggregate->addPracticalSubmoduleQuestion($question);
                $this->em->persist($pspSumAggregate);
            } break;
            case $processor::TYPE_PRODUCT_AGGREGATE: {
                $pspProductAggregate = $processor->getPracticalSubmoduleProcessorProductAggregate();
                $pspProductAggregate->addPracticalSubmoduleQuestion($question);
                $this->em->persist($pspProductAggregate);
            } break;
            case $processor::TYPE_TEMPLATED_TEXT: {
                $pspTemplatedText = $processor->getPracticalSubmoduleProcessorTemplatedText();
                $pspTemplatedText->setPracticalSubmoduleQuestion($question);
                $this->em->persist($pspTemplatedText);
            } break;
        }
    }

    private function doBindProcessorDependencyOnProcessorTask(array $props): void
    {
        if (false === $this->allKeysExist(['processor', 'dependent'], $props)) {
            return;
        }

        $processorId = $props['processor'];
        $otherProcessorId = $props['dependent'];

        if (false === $this->allKeysExist([$processorId, $otherProcessorId], $this->processorMapping)) {
            return;
        }

        /** @var PracticalSubmoduleProcessor $processor */
        $processor = $this->processorMapping[$processorId];
        if (false === in_array($processor->getType(), $processor::getProcessorProcessingProcessorTypes())) {
            return;
        }

        $otherProcessor = $this->processorMapping[$otherProcessorId];
        switch ($processor->getType()) {
            case $processor::TYPE_SUM_AGGREGATE: {
                $pspSumAggregate = $processor->getPracticalSubmoduleProcessorSumAggregate();
                $pspSumAggregate->addPracticalSubmoduleProcessor($otherProcessor);
                $this->em->persist($pspSumAggregate);
            } break;
            case $processor::TYPE_PRODUCT_AGGREGATE: {
                $pspProductAggregate = $processor->getPracticalSubmoduleProcessorProductAggregate();
                $pspProductAggregate->addPracticalSubmoduleProcessor($otherProcessor);
                $this->em->persist($pspProductAggregate);
            } break;
        }
    }

    private function doCreatePageTask(array $props): void
    {
        if (false === $this->allKeysExist(['title', 'description', 'position'], $props)) {
            return;
        }

        $page = (new PracticalSubmodulePage())
            ->setPracticalSubmodule($this->practicalSubmodule)
            ->setTitle($props['title'])
            ->setDescription($props['description'])
            ->setPosition($props['position']);
        ;
        $this->em->persist($page);

        if (false === (key_exists('questions', $props) && is_array($props['questions']))) {
            return;
        }

        foreach ($props['questions'] as $questionId) {
            /** @var PracticalSubmoduleQuestion $question */
            $question = $this->questionMapping[$questionId] ?? null;
            if (null !== $question) {
                $question->setPracticalSubmodulePage($page);
                $this->em->persist($question);
            }
        }
    }
}