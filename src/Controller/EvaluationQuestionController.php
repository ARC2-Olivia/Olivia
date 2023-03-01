<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Form\PracticalSubmoduleQuestionAnswerWeightedType;
use App\Form\PracticalSubmoduleQuestionType;
use App\Repository\PracticalSubmoduleQuestionRepository;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/evaluation-question", name: "evaluation_question_")]
class EvaluationQuestionController extends BaseController
{
    private ?NavigationService $navigationService = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, NavigationService $navigationService)
    {
        parent::__construct($em, $translator);
        $this->navigationService = $navigationService;
    }

    #[Route("/edit/{evaluationQuestion}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(PracticalSubmoduleQuestion $evaluationQuestion, Request $request): Response
    {
        $form = $this->createForm(PracticalSubmoduleQuestionType::class, $evaluationQuestion, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestion.edit', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluationQuestion->getPracticalSubmodule()->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/edit.html.twig', [
            'evaluationQuestion' => $evaluationQuestion,
            'evaluation' => $evaluationQuestion->getPracticalSubmodule(),
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($evaluationQuestion->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_EDIT_QUESTION)
        ]);
    }

    #[Route("/delete/{evaluationQuestion}", name: "delete", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(PracticalSubmoduleQuestion $evaluationQuestion, Request $request): Response
    {
        $evaluation = $evaluationQuestion->getPracticalSubmodule();
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationQuestion.delete', $csrfToken)) {
            foreach ($evaluationQuestion->getPracticalSubmoduleQuestionAnswers() as $evaluationQuestionAnswer) {
                $this->em->remove($evaluationQuestionAnswer);
            }
            $this->em->remove($evaluationQuestion);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluationQuestion.delete', ['%evaluation%' => $evaluation->getName()], 'message'));
        }

        return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluation->getId()]);
    }

    #[Route("/add-weighted-answer/{evaluationQuestion}", name: "add_weighted_answer")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addWeightedAnswer(PracticalSubmoduleQuestion $evaluationQuestion, Request $request): Response
    {
        $evaluationQuestionAnswer = (new PracticalSubmoduleQuestionAnswer())->setPracticalSubmoduleQuestion($evaluationQuestion)->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(PracticalSubmoduleQuestionAnswerWeightedType::class, $evaluationQuestionAnswer, ['include_translatable_fields' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($evaluationQuestionAnswer);
            $this->em->flush();
            $this->processEvaluationQuestionAnswerTranslation($evaluationQuestionAnswer, $form);
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestionAnswer.new', [], 'message'));
            return $this->redirectToRoute('evaluation_question_edit', ['evaluationQuestion' => $evaluationQuestion->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/new.html.twig', [
            'evaluation' => $evaluationQuestion->getPracticalSubmodule(),
            'evaluationQuestion' => $evaluationQuestion,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($evaluationQuestion->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_NEW_ANSWER)
        ]);
    }

    #[Route("/reorder", name: "reorder", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function reorder(Request $request, PracticalSubmoduleQuestionRepository $evaluationQuestionRepository): Response
    {
        $data = json_decode($request->getContent());
        if ($data !== null && isset($data->reorders) && !empty($data->reorders)) {
            foreach ($data->reorders as $reorder) {
                $evaluationQuestion = $evaluationQuestionRepository->find($reorder->id);
                $evaluationQuestion->setPosition(intval($reorder->position));
            }
            $this->em->flush();
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'fail']);
    }

    private function processEvaluationQuestionAnswerTranslation(PracticalSubmoduleQuestionAnswer $evaluationQuestionAnswer, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $answerTextAlt = $form->get('answerTextAlt')->getData();
        if ($answerTextAlt !== null && trim($answerTextAlt) !== '') {
            $translationRepository->translate($evaluationQuestionAnswer, 'answerText', $localeAlt, trim($answerTextAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}