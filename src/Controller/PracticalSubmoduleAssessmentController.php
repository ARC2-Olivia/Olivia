<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleAssessmentAnswer;
use App\Entity\PracticalSubmodulePage;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Misc\TemplatedTextField;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/practical-submodule-assessment", name: "practical_submodule_assessment_", requirements: ["_locale" => "%locale.supported%"])]
class PracticalSubmoduleAssessmentController extends BaseController
{
    #[Route("/start/{practicalSubmoduleAssessment}", name: "start")]
    #[IsGranted("ROLE_USER")]
    public function start(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, Request $request, NavigationService $navigationService): Response
    {
        $session = $request->getSession();
        $allowedToStart = $session->has('practicalSubmoduleAssessment.start') && $session->get('practicalSubmoduleAssessment.start') === true;
        if (!$allowedToStart) {
            $this->addFlash('error', $this->translator->trans('error.practicalSubmoduleAssessment.start', [], 'message'));
            return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmoduleAssessment->getPracticalSubmodule()->getId()]);
        }
        $session->remove('practicalSubmoduleAssessment.start');

        $questionnaire = $this->buildQuestionnaire($practicalSubmoduleAssessment);
        $practicalSubmodule = $practicalSubmoduleAssessment->getPracticalSubmodule();
        return $this->render('evaluation/assessment.html.twig', [
            'evaluation' => $practicalSubmodule,
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EVALUATE),
            'assessment' => $questionnaire
        ]);
    }

    #[Route("/edit/{practicalSubmoduleAssessment}", name: "edit")]
    #[IsGranted("ROLE_USER")]
    public function edit(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, Request $request, NavigationService $navigationService): Response
    {
        $session = $request->getSession();
        $allowedToEdit = $session->has('practicalSubmoduleAssessment.edit') && $session->get('practicalSubmoduleAssessment.edit') === true;
        if (!$allowedToEdit) {
            $this->addFlash('error', $this->translator->trans('error.practicalSubmoduleAssessment.edit', [], 'message'));
            return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmoduleAssessment->getPracticalSubmodule()->getId()]);
        }
        $session->remove('practicalSubmoduleAssessment.edit');

        $questionnaire = $this->buildQuestionnaire($practicalSubmoduleAssessment, true);
        $practicalSubmodule = $practicalSubmoduleAssessment->getPracticalSubmodule();
        return $this->render('evaluation/assessment.html.twig', [
            'evaluation' => $practicalSubmodule,
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EVALUATE),
            'assessment' => $questionnaire
        ]);
    }

    #[Route("/finish/{practicalSubmoduleAssessment}", name: "finish")]
    #[IsGranted("ROLE_USER")]
    public function finish(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleAssessment.finish', $csrfToken)) {
            $assessmentData = $request->request->all('evaluation_assessment');
            $assessmentAction = $request->request->get('_assessment_action');
            $practicalSubmoduleQuestionRepository = $this->em->getRepository(PracticalSubmoduleQuestion::class);
            $noQuestionError = false;

            $practicalSubmoduleAssessment->setCompleted('submit' === $assessmentAction);
            $practicalSubmoduleAssessment->setLastSubmittedAt(new \DateTimeImmutable());

            foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $practicalSubmoduleAssessmentAnswer) {
                $this->em->remove($practicalSubmoduleAssessmentAnswer);
            }

            foreach ($assessmentData as $questionId => $givenAnswer) {
                $practicalSubmoduleQuestion = $practicalSubmoduleQuestionRepository->find($questionId);
                if ($practicalSubmoduleQuestion !== null) {
                    $this->storeAnswer($practicalSubmoduleAssessment, $practicalSubmoduleQuestion, $givenAnswer);
                } else {
                    $noQuestionError = true;
                }
            }

            $this->em->flush();
            if ('sfl-bg' === $assessmentAction) {
                return new Response('OK');
            }

            if ($noQuestionError) {
                $this->addFlash('warning', $this->translator->trans('warning.practicalSubmoduleAssessment.noQuestion', [], 'message'));
            }

            $stringKey = $practicalSubmoduleAssessment->isCompleted() ? 'success.practicalSubmoduleAssessment.finish' : 'success.practicalSubmoduleAssessment.save';
            $this->addFlash('success', $this->translator->trans($stringKey, [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.practicalSubmoduleAssessment.finish', [], 'message'));
        }

        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmoduleAssessment->getPracticalSubmodule()->getId()]);
    }

    private function storeAnswer(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, PracticalSubmoduleQuestion $practicalSubmoduleQuestion, mixed $givenAnswer): void
    {
        switch ($practicalSubmoduleQuestion->getType()) {
            case PracticalSubmoduleQuestion::TYPE_YES_NO:
                $this->storeSingleChoiceAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer); break;
            case PracticalSubmoduleQuestion::TYPE_WEIGHTED:
                $this->storeWeightedAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer); break;
            case PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT:
                $this->storeTemplatedTextInputAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer); break;
            case PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE:
                $this->storeMultiChoiceAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer); break;
            case PracticalSubmoduleQuestion::TYPE_LIST_INPUT:
                $this->storeListInputAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer); break;
            case PracticalSubmoduleQuestion::TYPE_TEMPLATED_LIST_INPUT:
                $this->storeTemplatedListInputAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer); break;
            default:
                $this->storeSimpleAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, $givenAnswer);
        }
    }

    private function storeSingleChoiceAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        $practicalSubmoduleQuestionAnswer = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class)->findOneBy([
            'practicalSubmoduleQuestion' => $practicalSubmoduleQuestion,
            'id' => $givenAnswer
        ]);
        $practicalSubmoduleAssessmentAnswer = (new PracticalSubmoduleAssessmentAnswer())
            ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
            ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
            ->setPracticalSubmoduleQuestionAnswer($practicalSubmoduleQuestionAnswer)
            ->setAnswerValue($practicalSubmoduleQuestionAnswer->getAnswerValue());
        $this->em->persist($practicalSubmoduleAssessmentAnswer);
    }

    private function storeWeightedAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        $practicalSubmoduleQuestionAnswerRepository = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class);
        foreach ($givenAnswer as $qaId) {
            if (null !== ($qa = $practicalSubmoduleQuestionAnswerRepository->find($qaId))) {
                $weightAnswer = (new PracticalSubmoduleAssessmentAnswer())
                    ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
                    ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                    ->setPracticalSubmoduleQuestionAnswer($qa)
                    ->setAnswerValue($qa->getAnswerValue())
                ;
                $this->em->persist($weightAnswer);
            }
        }
    }

    private function storeTemplatedTextInputAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        $this->storeSimpleAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, json_encode($givenAnswer));
    }

    private function storeTemplatedListInputAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        if (is_array($givenAnswer)) {
            $removalList = [];
            foreach ($givenAnswer as $i => $item) {
                $allEmpty = true;
                foreach ($item as $variable => $value) {
                    if (trim($value) !== '') {
                        $allEmpty = false;
                        break;
                    }
                }
                if ($allEmpty)
                    $removalList[] = $i;
            }
            foreach ($removalList as $i) {
                unset($givenAnswer[$i]);
            }
            $this->storeSimpleAnswer($practicalSubmoduleQuestion, $practicalSubmoduleAssessment, json_encode(array_values($givenAnswer)));
        }
    }

    private function storeMultiChoiceAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        if (key_exists('choices', $givenAnswer)) {
            $practicalSubmoduleQuestionAnswerRepository = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class);
            foreach ($givenAnswer['choices'] as $choice) {
                if (null !== ($qa = $practicalSubmoduleQuestionAnswerRepository->find($choice))) {
                    $choiceAnswer = (new PracticalSubmoduleAssessmentAnswer())
                        ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
                        ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                        ->setPracticalSubmoduleQuestionAnswer($qa);
                    $this->em->persist($choiceAnswer);
                }
            }
        }

        if (key_exists('other', $givenAnswer)) {
            foreach ($givenAnswer['other'] as $other) {
                $other = trim($other);
                if (strlen($other) > 0) {
                    $choiceAnswer = (new PracticalSubmoduleAssessmentAnswer())
                        ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
                        ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                        ->setAnswerValue($other);
                    $this->em->persist($choiceAnswer);
                }
            }
        }
    }

    private function storeListInputAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        foreach ($givenAnswer as $item) {
            $item = trim($item);
            if ('' === $item || '###' === $item) {
                continue;
            }

            $answer = (new PracticalSubmoduleAssessmentAnswer())
                ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
                ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                ->setAnswerValue($item);
            $this->em->persist($answer);
        }
    }

    private function storeSimpleAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, PracticalSubmoduleAssessment $practicalSubmoduleAssessment, mixed $givenAnswer): void
    {
        $practicalSubmoduleAssessmentAnswer = (new PracticalSubmoduleAssessmentAnswer())
            ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
            ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
            ->setAnswerValue($givenAnswer);
        $this->em->persist($practicalSubmoduleAssessmentAnswer);
    }

    private function buildQuestionnaire(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, bool $editing = false): array
    {
        $practicalSubmodule = $practicalSubmoduleAssessment->getPracticalSubmodule();

        /** @var PracticalSubmoduleQuestion[] $practicalSubmoduleQuestions */
        $practicalSubmoduleQuestions = $this->em->getRepository(PracticalSubmoduleQuestion::class)->findOrderedForSubmodule($practicalSubmodule, true);
        $assessment = ['id' => $practicalSubmoduleAssessment->getId(), 'questions' => [], 'paging' => $practicalSubmodule->isPaging(), 'pages' => []];

        if ($practicalSubmodule->isPaging()) {
            foreach ($this->em->getRepository(PracticalSubmodulePage::class)->findOrderedForSubmodule($practicalSubmodule) as $practicalSubmodulePage) {
                $assessment['pages'][] = [
                    'title' => $practicalSubmodulePage->getTitle(),
                    'description' => $practicalSubmodulePage->getDescription(),
                    'number' => $practicalSubmodulePage->getPosition()
                ];
            }
        }

        $templatedTextFieldsMapper = function (TemplatedTextField $ttf): array { return $ttf->asArray(); };
        if ($editing) $assessmentAnswerRepository = $this->em->getRepository(PracticalSubmoduleAssessmentAnswer::class);
        foreach ($practicalSubmoduleQuestions as $practicalSubmoduleQuestion) {
            $question = [
                'id' => $practicalSubmoduleQuestion->getId(),
                'type' => $practicalSubmoduleQuestion->getType(),
                'question' => $practicalSubmoduleQuestion->getQuestionText(),
                'answers' => [],
                'page' => $practicalSubmoduleQuestion->getPracticalSubmodulePage()?->getPosition(),
                'other' => $practicalSubmoduleQuestion->isOtherEnabled(),
                'isHeading' => $practicalSubmoduleQuestion->getIsHeading(),
                'multipleWeighted' => $practicalSubmoduleQuestion->isMultipleWeighted(),
                'largeText' => $practicalSubmoduleQuestion->isLargeText(),
                'listWithSublist' => $practicalSubmoduleQuestion->isListWithSublist(),
                'template' => $practicalSubmoduleQuestion->getTemplate() ?? '',
                'templateVariables' => $practicalSubmoduleQuestion->getTemplateVariables(),
                'isModal' => $practicalSubmoduleQuestion->isModal()
            ];

            if ($editing) {
                # Učitavanje korisničkih odgovora
                $assessmentAnswers = $assessmentAnswerRepository->findBy(['practicalSubmoduleAssessment' => $practicalSubmoduleAssessment, 'practicalSubmoduleQuestion' => $practicalSubmoduleQuestion]);
                if (count($assessmentAnswers) > 0) {
                    switch ($practicalSubmoduleQuestion->getType()) {
                        case PracticalSubmoduleQuestion::TYPE_YES_NO:
                        case PracticalSubmoduleQuestion::TYPE_WEIGHTED: {
                            $question['userAnswer'] = [];
                            foreach ($assessmentAnswers as $aa)
                                $question['userAnswer'][] = $aa->getPracticalSubmoduleQuestionAnswer()->getId();
                        } break;
                        case PracticalSubmoduleQuestion::TYPE_TEXT_INPUT:
                        case PracticalSubmoduleQuestion::TYPE_NUMERICAL_INPUT: {
                            $question['userAnswer'] = $assessmentAnswers[0]->getAnswerValue();
                        } break;
                        case PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT:
                        case PracticalSubmoduleQuestion::TYPE_TEMPLATED_LIST_INPUT: {
                            $question['userAnswer'] = json_decode($assessmentAnswers[0]->getAnswerValue(), true);
                        } break;
                        case PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE: {
                            $question['userAnswer'] = ['selected' => [], 'added' => []];
                            foreach ($assessmentAnswers as $aa) {
                                if (null !== $aa->getPracticalSubmoduleQuestionAnswer())
                                    $question['userAnswer']['selected'][] = $aa->getPracticalSubmoduleQuestionAnswer()->getId();
                                else
                                    $question['userAnswer']['added'][] = $aa->getAnswerValue();
                            }
                        } break;
                        case PracticalSubmoduleQuestion::TYPE_LIST_INPUT: {
                            $question['userAnswer'] = [];
                            foreach ($assessmentAnswers as $aa)
                                $question['userAnswer'][] = $aa->getAnswerValue();
                        } break;
                    }
                }
            }

            if ($practicalSubmoduleQuestion->getDependentPracticalSubmoduleQuestion() !== null) {
                $question['dependency'] = [
                    'questionId' => strval($practicalSubmoduleQuestion->getDependentPracticalSubmoduleQuestion()->getId()),
                    'answer' => $practicalSubmoduleQuestion->getDependentValue()
                ];
            }

            foreach ($practicalSubmoduleQuestion->getPracticalSubmoduleQuestionAnswers() as $practicalSubmoduleQuestionAnswer) {
                $answerText = $practicalSubmoduleQuestionAnswer->getAnswerText();
                if ($question['type'] === PracticalSubmoduleQuestion::TYPE_YES_NO) $answerText = $this->translator->trans($answerText, [], 'app');
                $answer = [
                    'id' => $practicalSubmoduleQuestionAnswer->getId(),
                    'text' => $answerText,
                    'value' => $practicalSubmoduleQuestionAnswer->getAnswerValue()
                ];
                if ($question['type'] === PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT) {
                    $answer['fields'] = array_map($templatedTextFieldsMapper, $practicalSubmoduleQuestionAnswer->getDesimplifiedTemplatedTextFields());
                }
                $question['answers'][] = $answer;
            }
            $assessment['questions'][] = $question;
        }

        return $assessment;
    }
}