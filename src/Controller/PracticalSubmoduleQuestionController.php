<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleAssessmentAnswer;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Form\PracticalSubmodule\TranslatableTemplatedText;
use App\Form\PracticalSubmodule\TranslatableTemplatedTextType;
use App\Form\PracticalSubmoduleQuestionAnswerMultiChoiceType;
use App\Form\PracticalSubmoduleQuestionAnswerWeightedType;
use App\Form\PracticalSubmoduleQuestionType;
use App\Repository\PracticalSubmoduleQuestionRepository;
use App\Service\NavigationService;
use App\Service\PracticalSubmoduleService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/{_locale}/practical-submodule-question", name: "practical_submodule_question_", requirements: ["_locale" => "%locale.supported%"])]
class PracticalSubmoduleQuestionController extends BaseController
{
    private ?NavigationService $navigationService = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, NavigationService $navigationService)
    {
        parent::__construct($em, $translator);
        $this->navigationService = $navigationService;
    }

    #[Route("/edit/{practicalSubmoduleQuestion}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, Request $request): Response
    {
        $form = $this->createForm(PracticalSubmoduleQuestionType::class, $practicalSubmoduleQuestion, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestion.edit', [], 'message'));
            return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmoduleQuestion->getPracticalSubmodule()->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        $params = [
            'evaluationQuestion' => $practicalSubmoduleQuestion,
            'evaluation' => $practicalSubmoduleQuestion->getPracticalSubmodule(),
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmoduleQuestion->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_EDIT_QUESTION)
        ];

        if ($practicalSubmoduleQuestion->getType() === PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT) {
            $params['tttForm'] = $this->prepareTemplatedTextAnswerForm($practicalSubmoduleQuestion, $request);
        }

        return $this->render('evaluation/evaluation_question/edit.html.twig', $params);
    }

    #[Route("/delete/{practicalSubmoduleQuestion}", name: "delete", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, Request $request): Response
    {
        $evaluation = $practicalSubmoduleQuestion->getPracticalSubmodule();
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleQuestion.delete', $csrfToken)) {
            foreach ($this->em->getRepository(PracticalSubmoduleAssessmentAnswer::class)->findBy(['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion]) as $assessmentAnswer) {
                $this->em->remove($assessmentAnswer);
            }
            foreach ($practicalSubmoduleQuestion->getPracticalSubmoduleQuestionAnswers() as $practicalSubmoduleQuestionAnswer) {
                $this->em->remove($practicalSubmoduleQuestionAnswer);
            }
            $this->em->remove($practicalSubmoduleQuestion);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.practicalSubmoduleQuestion.delete', ['%evaluation%' => $evaluation->getName()], 'message'));
        }

        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $evaluation->getId()]);
    }

    #[Route("/add-weighted-answer/{practicalSubmoduleQuestion}", name: "add_weighted_answer")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addWeightedAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, Request $request): Response
    {
        $practicalSubmoduleQuestionAnswer = (new PracticalSubmoduleQuestionAnswer())
            ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
            ->setLocale($this->getParameter('locale.default'))
        ;
        $form = $this->createForm(PracticalSubmoduleQuestionAnswerWeightedType::class, $practicalSubmoduleQuestionAnswer, ['include_translatable_fields' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($practicalSubmoduleQuestionAnswer);
            $this->em->flush();
            $this->processPracticalSubmoduleQuestionAnswerTranslation($practicalSubmoduleQuestionAnswer, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestionAnswer.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_question_edit', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/new.html.twig', [
            'evaluation' => $practicalSubmoduleQuestion->getPracticalSubmodule(),
            'evaluationQuestion' => $practicalSubmoduleQuestion,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmoduleQuestion->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_NEW_ANSWER)
        ]);
    }

    #[Route("/add-multi-choice-answer/{practicalSubmoduleQuestion}", name: "add_multi_choice_answer")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addMultiChoiceAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, Request $request, PracticalSubmoduleService $practicalSubmoduleService): Response
    {
        $practicalSubmoduleQuestionAnswer = (new PracticalSubmoduleQuestionAnswer())
            ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
            ->setLocale($this->getParameter('locale.default'))
        ;
        $form = $this->createForm(PracticalSubmoduleQuestionAnswerMultiChoiceType::class, $practicalSubmoduleQuestionAnswer, ['include_translatable_fields' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $practicalSubmoduleQuestionAnswer->setAnswerValue($practicalSubmoduleService->getNextAnswerValueForMultiChoiceQuestion($practicalSubmoduleQuestion));
            $this->em->persist($practicalSubmoduleQuestionAnswer);
            $this->em->flush();
            $this->processPracticalSubmoduleQuestionAnswerTranslation($practicalSubmoduleQuestionAnswer, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestionAnswer.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_question_edit', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/new.html.twig', [
            'evaluation' => $practicalSubmoduleQuestion->getPracticalSubmodule(),
            'evaluationQuestion' => $practicalSubmoduleQuestion,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmoduleQuestion->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_NEW_ANSWER)
        ]);
    }

    #[Route("/new-templated-text-answer/{practicalSubmoduleQuestion}", name: "new_templated_text_answer")]
    #[IsGranted("ROLE_MODERATOR")]
    public function newTemplatedTextAnswer(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, Request $request): Response
    {
        $ttt = new TranslatableTemplatedText();
        $form = $this->createForm(TranslatableTemplatedTextType::class, $ttt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $practicalSubmoduleQuestionAnswer = (new PracticalSubmoduleQuestionAnswer())
                ->setPracticalSubmoduleQuestion($practicalSubmoduleQuestion)
                ->setLocale($this->getParameter('locale.default'))
                ->setAnswerText($ttt->getText())
                ->setTemplatedTextFields($ttt->getTextFields())
            ;
            $this->em->persist($practicalSubmoduleQuestionAnswer);
            $this->em->flush();
            $this->processPracticalSubmoduleQuestionAnswerTranslation($practicalSubmoduleQuestionAnswer, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestionAnswer.new', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->redirectToRoute('practical_submodule_question_edit', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion->getId()]);
    }

    #[Route("/reorder", name: "reorder", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function reorder(Request $request, PracticalSubmoduleQuestionRepository $practicalSubmoduleQuestionRepository): Response
    {
        $data = json_decode($request->getContent());
        if ($data !== null && isset($data->reorders) && !empty($data->reorders)) {
            foreach ($data->reorders as $reorder) {
                $practicalSubmoduleQuestion = $practicalSubmoduleQuestionRepository->find($reorder->id);
                $practicalSubmoduleQuestion->setPosition(intval($reorder->position));
            }
            $this->em->flush();
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'fail']);
    }

    private function processPracticalSubmoduleQuestionAnswerTranslation(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $formKey = $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getType() === PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT ? 'translatedText' : 'answerTextAlt';
        $answerTextAlt = $form->get($formKey)->getData();
        if ($answerTextAlt !== null && trim($answerTextAlt) !== '') {
            $translationRepository->translate($practicalSubmoduleQuestionAnswer, 'answerText', $localeAlt, trim($answerTextAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function prepareTemplatedTextAnswerForm(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, Request $request): \Symfony\Component\Form\FormView
    {
        $ttt = new TranslatableTemplatedText();

        if ($practicalSubmoduleQuestion->getPracticalSubmoduleQuestionAnswers()->isEmpty()) {
            return $this->createForm(TranslatableTemplatedTextType::class, $ttt, [
                'method' => 'POST',
                'action' => $this->generateUrl('practical_submodule_question_new_templated_text_answer', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion->getId()])
            ])->createView();
        }

        $psqa = $practicalSubmoduleQuestion->getPracticalSubmoduleQuestionAnswers()->get(0);
        if ($request->getLocale() === $this->getParameter('locale.default')) {
            $ttt->setText($psqa->getAnswerText());
            $psqa = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class)->findByIdForLocale($psqa->getId(), $this->getParameter('locale.alternate'));
            $ttt->setTranslatedText($psqa->getAnswerText());
        } else {
            $ttt->setTranslatedText($psqa->getAnswerText());
            $psqa = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class)->findByIdForLocale($psqa->getId(), $this->getParameter('locale.default'));
            $ttt->setText($psqa->getAnswerText());
        }
        return $this->createForm(TranslatableTemplatedTextType::class, $ttt, [
            'method' => 'POST',
            'action' => $this->generateUrl('practical_submodule_question_answer_edit_templated_text', ['practicalSubmoduleQuestionAnswer' => $psqa->getId()])
        ])->createView();
    }
}