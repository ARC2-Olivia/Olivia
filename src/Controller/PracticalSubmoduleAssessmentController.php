<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleAssessmentAnswer;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/practical-submodule-assessment", name: "practical_submodule_assessment_")]
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

        $practicalSubmodule = $practicalSubmoduleAssessment->getPracticalSubmodule();

        /** @var PracticalSubmoduleQuestion[] $practicalSubmoduleQuestions */
        $practicalSubmoduleQuestions = $this->em->getRepository(PracticalSubmoduleQuestion::class)->findOrderedForSubmodule($practicalSubmodule);
        $assessment = ['id' => $practicalSubmoduleAssessment->getId(), 'questions' => []];
        foreach ($practicalSubmoduleQuestions as $practicalSubmoduleQuestion) {
            $question = [
                'id' => $practicalSubmoduleQuestion->getId(),
                'type' => $practicalSubmoduleQuestion->getType(),
                'question' => $practicalSubmoduleQuestion->getQuestionText(),
                'answers' => []
            ];
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
                $question['answers'][] = $answer;
            }
            $assessment['questions'][] = $question;
        }

        return $this->render('evaluation/assessment.html.twig', [
            'evaluation' => $practicalSubmodule,
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EVALUATE),
            'assessment' => $assessment
        ]);
    }

    #[Route("/finish/{practicalSubmoduleAssessment}", name: "finish")]
    #[IsGranted("ROLE_USER")]
    public function finish(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleAssessment.finish', $csrfToken)) {
            $assessmentData = $request->request->all('evaluation_assessment');
            $evaluationQuestionRepository = $this->em->getRepository(PracticalSubmoduleQuestion::class);
            $noQuestionError = false;

            $practicalSubmoduleAssessment->setCompleted(true);

            foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $practicalSubmoduleAssessmentAnswer) {
                $this->em->remove($practicalSubmoduleAssessmentAnswer);
            }

            foreach ($assessmentData as $questionId => $givenAnswer) {
                $evaluationQuestion = $evaluationQuestionRepository->find($questionId);
                if ($evaluationQuestion !== null) {
                    $this->storeAnswer($practicalSubmoduleAssessment, $evaluationQuestion, $givenAnswer);
                } else {
                    $noQuestionError = true;
                }
            }

            if ($noQuestionError) {
                $this->addFlash('warning', $this->translator->trans('warning.practicalSubmoduleAssessment.noQuestion', [], 'message'));
            }

            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleAssessment.finish', [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.practicalSubmoduleAssessment.finish', [], 'message'));
        }

        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmoduleAssessment->getPracticalSubmodule()->getId()]);
    }

    private function storeAnswer(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, PracticalSubmoduleQuestion $practicalSubmoduleQuestion, string $givenAnswer): void
    {
        if ($practicalSubmoduleQuestion->getType() === PracticalSubmoduleQuestion::TYPE_YES_NO || $practicalSubmoduleQuestion->getType() === PracticalSubmoduleQuestion::TYPE_WEIGHTED) {
            $practicalSubmoduleQuestionAnswer = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class)->findOneBy(['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion, 'id' => $givenAnswer]);
            $practicalSubmoduleAssessmentAnswer = (new PracticalSubmoduleAssessmentAnswer())
                ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
                ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                ->setPracticalSubmoduleQuestionAnswer($practicalSubmoduleQuestionAnswer)
                ->setAnswerValue($practicalSubmoduleQuestionAnswer->getAnswerValue());
            $this->em->persist($practicalSubmoduleAssessmentAnswer);
        } else {
            $practicalSubmoduleAssessmentAnswer = (new PracticalSubmoduleAssessmentAnswer())
                ->setPracticalSubmoduleAssessment($practicalSubmoduleAssessment)
                ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                ->setAnswerValue($givenAnswer);
            $this->em->persist($practicalSubmoduleAssessmentAnswer);
        }
    }
}