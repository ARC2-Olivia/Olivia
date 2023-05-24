<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Form\PracticalSubmodule\TranslatableTemplatedText;
use App\Form\PracticalSubmodule\TranslatableTemplatedTextType;
use App\Form\PracticalSubmoduleQuestionAnswerMultiChoiceType;
use App\Form\PracticalSubmoduleQuestionAnswerWeightedType;
use App\Service\NavigationService;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/practical-submodule-question-answer", name: "practical_submodule_question_answer_", requirements: ["_locale" => "%locale.supported%"])]
class PracticalSubmoduleQuestionAnswerController extends BaseController
{
    #[Route("/edit-weighted/{practicalSubmoduleQuestionAnswer}", name: "edit_weighted")]
    #[IsGranted('ROLE_MODERATOR')]
    public function editWeighted(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, Request $request, NavigationService $navigationService): Response
    {
        $form = $this->createForm(PracticalSubmoduleQuestionAnswerWeightedType::class, $practicalSubmoduleQuestionAnswer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestionAnswer.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/edit.html.twig', [
            'evaluation' => $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getPracticalSubmodule(),
            'evaluationQuestionAnswer' => $practicalSubmoduleQuestionAnswer,
            'form' => $form->createView(),
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_EDIT_ANSWER)
        ]);
    }

    #[Route("/edit-multi-choice/{practicalSubmoduleQuestionAnswer}", name: "edit_multi_choice")]
    #[IsGranted('ROLE_MODERATOR')]
    public function editMultiChoice(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, Request $request, NavigationService $navigationService): Response
    {
        $form = $this->createForm(PracticalSubmoduleQuestionAnswerMultiChoiceType::class, $practicalSubmoduleQuestionAnswer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestionAnswer.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/edit.html.twig', [
            'evaluation' => $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getPracticalSubmodule(),
            'evaluationQuestionAnswer' => $practicalSubmoduleQuestionAnswer,
            'form' => $form->createView(),
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_EDIT_ANSWER)
        ]);
    }

    #[Route("/edit-templated-text/{practicalSubmoduleQuestionAnswer}", name: "edit_templated_text")]
    #[IsGranted('ROLE_MODERATOR')]
    public function editTemplatedText(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, Request $request): Response
    {
        $ttt = new TranslatableTemplatedText();
        $form = $this->createForm(TranslatableTemplatedTextType::class, $ttt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $practicalSubmoduleQuestionAnswer->setTemplatedTextFields($ttt->getTextFields());
            $this->em->flush();
            $this->processTemplatedTextTranslations($practicalSubmoduleQuestionAnswer, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestionAnswer.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->redirectToRoute('practical_submodule_question_edit', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getId()]);
    }

    #[Route("/delete/{practicalSubmoduleQuestionAnswer}", name: "delete", methods: ["POST"])]
    public function delete(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, Request $request): Response
    {
        $practicalSubmoduleQuestion = $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion();
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleQuestionAnswer.delete', $csrfToken)) {
            $this->em->remove($practicalSubmoduleQuestionAnswer);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.practicalSubmoduleQuestionAnswer.delete', [], 'message'));
        }

        return $this->redirectToRoute('practical_submodule_question_edit', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion->getId()]);
    }

    private function processTemplatedTextTranslations(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeDefault = $this->getParameter('locale.default');
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $answerText = $form->get('text')->getData();
        if ($answerText !== null && trim($answerText) !== '') {
            $translationRepository->translate($practicalSubmoduleQuestionAnswer, 'answerText', $localeDefault, trim($answerText));
            $translated = true;
        }

        $answerTextAlt = $form->get('translatedText')->getData();
        if ($answerTextAlt !== null && trim($answerTextAlt) !== '') {
            $translationRepository->translate($practicalSubmoduleQuestionAnswer, 'answerText', $localeAlt, trim($answerTextAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}