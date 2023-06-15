<?php

namespace App\Misc;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmodulePage;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Exception;

class PSImporter
{
    private ?PracticalSubmodule $practicalSubmodule = null;
    private ?array $tasks = null;
    private ?EntityManagerInterface $em = null;

    private int $taskIndex = 1;
    private array $questionMapping = [];

    public function __construct(array $tasks, EntityManagerInterface $em)
    {
        $this->tasks = $tasks;
        $this->em = $em;
    }

    public function import(): ?PracticalSubmodule
    {
        $this->sortTasks();
        foreach ($this->tasks as $task) {
            $isValid = is_array($task) && isset($task['task_op'], $task['task_props']) && is_array($task['task_props']);

            if (false === $isValid) {
                if (1 === $this->taskIndex) {
                    throw new \Exception('First task has to create a practical submodule, and it must not be erroneous.');
                }
                continue;
            }

            if (1 === $this->taskIndex && Tasks::CREATE_SUBMODULE !== $task['task_op']) {
                throw new Exception('First task has to create a practical submodule. Was the exported file tampered in any way?');
            }

            switch ($task['task_op']) {
                case Tasks::CREATE_SUBMODULE: $this->doCreateSubmoduleTask($task['task_props']); break;
                case Tasks::CREATE_QUESTION: $this->doCreateQuestionTask($task['task_props']); break;
                case Tasks::CREATE_QUESTION_DEPENDENCY: $this->doCreateQuestionDependencyTask($task['task_props']); break;
                case Tasks::CREATE_PAGE: $this->doCreatePageTask($task['task_props']); break;
            }
        }

        return $this->practicalSubmodule;
    }

    private function sortTasks()
    {
        usort($this->tasks, function ($t1, $t2) {
            if (false === isset($t1['task_order'], $t2['task_order'])) {
                throw new \Exception('Missing \'task_order\' key.');
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

        foreach ($props['answers'] as $answer) {
            if (false === $this->allKeysExist(['answerText', 'answerValue', 'templatedTextFields'], $answer)) {
                continue;
            }

            $answer = (new PracticalSubmoduleQuestionAnswer())
                ->setPracticalSubmoduleQuestion($question)
                ->setAnswerText($props['answerText'])
                ->setAnswerValue($props['answerValue'])
                ->setTemplatedTextFields($props['templatedTextFields'])
            ;
            $this->em->persist($answer);
        }
    }

    private function doCreateQuestionDependencyTask(array $props): void
    {
        if (false === $this->allKeysExist(['questionId', 'dependentId', 'dependentValue'], $props)) {
            return;
        }

        if (false === $this->allKeysExist([$props['questionId'], $props['dependentId']], $this->questionMapping)) {
            return;
        }

        /** @var PracticalSubmoduleQuestion $question */
        $question = $props['questionId'];
        $dependent = $props['dependentId'];
        $question->setDependentPracticalSubmoduleQuestion($dependent)->setDependentValue($props['dependentValue']);
        $this->em->persist($question);
    }

    private function doCreatePageTask(array $props): void
    {
        if (false === $this->allKeysExist(['title', 'description', 'position'], $props)) {
            return;
        }

        $page = (new PracticalSubmodulePage())
            ->setTitle($props['title'])
            ->setDescription($props['descriptions'])
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