<?php

namespace App\API\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\API\Entity\PracticalSubmoduleAnswersInfo;
use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleAssessmentAnswer;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Service\PracticalSubmoduleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleAnswersInfoProvider implements ProviderInterface
{
    private const RETURN_TYPE_SINGLE = 0;
    private const RETURN_TYPE_COLLECTION = 1;

    private ?Security $security = null;
    private ?EntityManagerInterface $em = null;
    private ?TranslatorInterface $translator = null;
    private ?RequestStack $requestStack = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?PracticalSubmoduleService $practicalSubmoduleService = null;
    private ?string $locale = null;

    public function __construct(
        Security                  $security,
        EntityManagerInterface    $em,
        TranslatorInterface       $translator,
        RequestStack              $requestStack,
        ParameterBagInterface     $parameterBag,
        PracticalSubmoduleService $practicalSubmoduleService
    )
    {
        $this->security = $security;
        $this->em = $em;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
        $this->practicalSubmoduleService = $practicalSubmoduleService;
    }

    /**
     * @inheritDoc
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $returnType = key_exists('id', $uriVariables) ? self::RETURN_TYPE_SINGLE : self::RETURN_TYPE_COLLECTION;
        return match ($returnType) {
            self::RETURN_TYPE_SINGLE => $this->getSingle($uriVariables['id']),
            self::RETURN_TYPE_COLLECTION => $this->getCollection(),
            default => []
        };
    }

    private function getSingle(mixed $id)
    {
        if (null === $id) {
            return null;
        }

        $practicalSubmodule = $this->em->getRepository(PracticalSubmodule::class)->findOneByIdWithLocale($id, $this->getLocale());
        if (null === $practicalSubmodule) {
            return null;
        }

        /** @var PracticalSubmoduleAssessment $assessment */
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneByUserAndPracticalSubmoduleWithLocale($this->security->getUser(), $practicalSubmodule, $this->getLocale());

        $psai = new PracticalSubmoduleAnswersInfo();
        $psai->id = $practicalSubmodule->getId();
        $psai->practicalSubmodule = $practicalSubmodule;
        $psai->answers = PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $assessment->getPracticalSubmodule()->getModeOfOperation()
            ? $this->getAnswersWithSimpleModeOfOperation($assessment)
            : $this->getAnswersWithAdvancedModeOfOperation($assessment)
        ;
        $psai->locale = $this->getLocale();

        return $psai;
    }

    private function getCollection()
    {
        $data = [];

        /** @var PracticalSubmoduleAssessment[] $assessments */
        $assessments = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findByUserWithLocale($this->security->getUser(), $this->getLocale());
        foreach ($assessments as $assessment) {
            $psai = new PracticalSubmoduleAnswersInfo();
            $psai->id = $assessment->getPracticalSubmodule()->getId();
            $psai->practicalSubmodule = $assessment->getPracticalSubmodule();
            $psai->answers = PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $assessment->getPracticalSubmodule()->getModeOfOperation()
                ? $this->getAnswersWithSimpleModeOfOperation($assessment)
                : $this->getAnswersWithAdvancedModeOfOperation($assessment)
            ;
            $data[] = $psai;
        }

        return $data;
    }

    private function getAnswersWithSimpleModeOfOperation(PracticalSubmoduleAssessment $assessment): array
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $questionRepository = $this->em->getRepository(PracticalSubmoduleQuestion::class);
        $assessmentAnswerRepository = $this->em->getRepository(PracticalSubmoduleAssessmentAnswer::class);
        $questionAnswerRepository = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class);
        $answerData = [];

        foreach ($results as $result) {
            $questionId = $result->getQuestion()->getId();
            $question = $questionRepository->findOneByIdWithLocale($questionId, $this->getLocale());
            $item = ['question' => $question->getQuestionText() ?? null, 'answers' => []];
            if ($result->isQuestionSet()) {
                foreach ($assessmentAnswerRepository->findByAssessmentWithLocale($assessment, $this->getLocale()) as $answer) {
                    if ($answer->getPracticalSubmoduleQuestion()->getId() !== $questionId) {
                        continue;
                    }

                    if ($answer->getPracticalSubmoduleQuestionAnswer() !== null) {
                        $questionAnswer = $questionAnswerRepository->findByIdForLocale($answer->getPracticalSubmoduleQuestionAnswer()->getId(), $this->getLocale());
                        $answerText = $questionAnswer->getAnswerText();
                    } else {
                        $answerText = $answer->getDisplayableAnswer();
                    }

                    $item['answers'][] = $this->translator->trans($answerText, [], 'app', $this->getLocale());
                }
            }
            $answerData[] = $item;
        }

        return $answerData;
    }

    private function getAnswersWithAdvancedModeOfOperation(PracticalSubmoduleAssessment $assessment): array
    {
        $questionRepository = $this->em->getRepository(PracticalSubmoduleQuestion::class);
        $assessmentAnswerRepository = $this->em->getRepository(PracticalSubmoduleAssessmentAnswer::class);
        $questionAnswerRepository = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class);
        $answerData = [];

        foreach ($assessmentAnswerRepository->findByAssessmentWithLocale($assessment, $this->getLocale()) as $answer) {
            $questionId = $answer->getPracticalSubmoduleQuestion()->getId();
            $question = $questionRepository->findOneByIdWithLocale($questionId, $this->getLocale());

            if (!key_exists($questionId, $answerData)) {
                $item = ['question' => $question->getQuestionText(), 'answers' => []];
                $answerData[$questionId] = $item;
            }

            if ($answer->getPracticalSubmoduleQuestionAnswer() !== null) {
                $questionAnswer = $questionAnswerRepository->findByIdForLocale($answer->getPracticalSubmoduleQuestionAnswer()->getId(), $this->getLocale());
                $answerText = $questionAnswer->getAnswerText();
            } else {
                $answerText = $this->translator->trans($answer->getDisplayableAnswer(), [], 'app', $this->getLocale());
            }

            $answerData[$questionId]['answers'][] = $this->translator->trans($answerText, [], 'app', $this->getLocale());
        }

        return array_values($answerData);
    }

    private function getLocale(): string
    {
        if (null !== $this->locale) {
            return $this->locale;
        }
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request->headers->has('Accept-Language') ? $request->headers->get('Accept-Language') : null;
        $defaultLocale = $this->parameterBag->get('locale.default');
        $this->locale = $locale === $defaultLocale || $locale === $this->parameterBag->get('locale.alternate') ? $locale : $defaultLocale;
        return $this->locale;
    }
}