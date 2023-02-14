<?php

namespace App\Controller;

use App\Entity\EvaluationAssessment;
use App\Entity\EvaluationAssessmentAnswer;
use App\Entity\EvaluationQuestion;
use App\Entity\EvaluationQuestionAnswer;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/evaluation-assessment", name: "evaluation_assessment_")]
class EvaluationAssessmentController extends BaseController
{
    #[Route("/start/{evaluationAssessment}", name: "start")]
    #[IsGranted("ROLE_USER")]
    public function start(EvaluationAssessment $evaluationAssessment, Request $request, NavigationService $navigationService): Response
    {
        $session = $request->getSession();
        $allowedToStart = $session->has('evaluationAssessment.start') && $session->get('evaluationAssessment.start') === true;
        if (!$allowedToStart) {
            $this->addFlash('error', $this->translator->trans('error.evaluationAssessment.start', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluationAssessment->getEvaluation()->getId()]);
        }
        $session->remove('evaluationAssessment.start');

        $evaluation = $evaluationAssessment->getEvaluation();

        $assessment = ['id' => $evaluationAssessment->getId(), 'questions' => []];
        $evaluationQuestions = $this->em->getRepository(EvaluationQuestion::class)->findOrderedForEvaluation($evaluation);
        foreach ($evaluationQuestions as $evaluationQuestion) {
            $question = ['id' => $evaluationQuestion->getId(), 'type' => $evaluationQuestion->getType(), 'question' => $evaluationQuestion->getQuestionText(), 'answers' => []];
            if ($evaluationQuestion->getDependentEvaluationQuestion() !== null) {
                $question['dependency'] = ['questionId' => strval($evaluationQuestion->getDependentEvaluationQuestion()->getId()), 'answer' => $evaluationQuestion->getDependentValue()];
            }
            foreach ($evaluationQuestion->getEvaluationQuestionAnswers() as $evaluationQuestionAnswer) {
                $answerText = $evaluationQuestionAnswer->getAnswerText();
                if ($question['type'] === EvaluationQuestion::TYPE_YES_NO) $answerText = $this->translator->trans($answerText, [], 'app');
                $answer = ['id' => $evaluationQuestionAnswer->getId(), 'text' => $answerText, 'value' => $evaluationQuestionAnswer->getAnswerValue()];
                $question['answers'][] = $answer;
            }
            $assessment['questions'][] = $question;
        }

        return $this->render('evaluation/assessment.html.twig', [
            'evaluation' => $evaluation,
            'navigation' => $navigationService->forEvaluation($evaluation, NavigationService::EVALUATION_EVALUATE),
            'assessment' => $assessment
        ]);
    }

    #[Route("/finish/{evaluationAssessment}", name: "finish")]
    #[IsGranted("ROLE_USER")]
    public function finish(EvaluationAssessment $evaluationAssessment, Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationAssessment.finish', $csrfToken)) {
            $assessmentData = $request->request->all('evaluation_assessment');
            $evaluationQuestionRepository = $this->em->getRepository(EvaluationQuestion::class);
            $noQuestionError = false;

            $evaluationAssessment->setCompleted(true);

            foreach ($evaluationAssessment->getEvaluationAssessmentAnswers() as $evaluationAssessmentAnswer) {
                $this->em->remove($evaluationAssessmentAnswer);
            }

            foreach ($assessmentData as $questionId => $givenAnswer) {
                $evaluationQuestion = $evaluationQuestionRepository->find($questionId);
                if ($evaluationQuestion !== null) {
                    $this->storeAnswer($evaluationAssessment, $evaluationQuestion, $givenAnswer);
                } else {
                    $noQuestionError = true;
                }
            }

            if ($noQuestionError) {
                $this->addFlash('warning', $this->translator->trans('warning.evaluationAssessment.noQuestion', [], 'message'));
            }

            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluationAssessment.finish', [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.evaluationAssessment.finish', [], 'message'));
        }

        return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluationAssessment->getEvaluation()->getId()]);
    }

    private function storeAnswer(EvaluationAssessment $evaluationAssessment, EvaluationQuestion $evaluationQuestion, string $givenAnswer): void
    {
        if ($evaluationQuestion->getType() === EvaluationQuestion::TYPE_YES_NO || $evaluationQuestion->getType() === EvaluationQuestion::TYPE_WEIGHTED) {
            $evaluationQuestionAnswer = $this->em->getRepository(EvaluationQuestionAnswer::class)->findOneBy(['evaluationQuestion' => $evaluationQuestion, 'id' => $givenAnswer]);
            $evaluationAssessmentAnswer = (new EvaluationAssessmentAnswer())
                ->setEvaluationAssessment($evaluationAssessment)
                ->setEvaluationQuestion($evaluationQuestion)
                ->setEvaluationQuestionAnswer($evaluationQuestionAnswer)
                ->setAnswerValue($evaluationQuestionAnswer->getAnswerValue());
            $this->em->persist($evaluationAssessmentAnswer);
        } else {
            $evaluationAssessmentAnswer = (new EvaluationAssessmentAnswer())
                ->setEvaluationAssessment($evaluationAssessment)
                ->setEvaluationQuestion($evaluationQuestion)
                ->setAnswerValue($givenAnswer);
            $this->em->persist($evaluationAssessmentAnswer);
        }
    }
}