<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\EvaluationAssessment;
use App\Entity\EvaluationEvaluator;
use App\Entity\EvaluationQuestion;
use App\Entity\EvaluationQuestionAnswer;
use App\Form\EvaluationEvaluatorType;
use App\Form\EvaluationQuestionType;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use App\Service\EvaluationService;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/evaluation", name: "evaluation_")]
class EvaluationController extends BaseController
{
    private ?NavigationService $navigationService = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, NavigationService $navigationService)
    {
        parent::__construct($em, $translator);
        $this->navigationService = $navigationService;
    }

    #[Route("/", name: "index")]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        $evaluations = $evaluationRepository->findAll();
        return $this->render("evaluation/index.html.twig", ['evaluations' => $evaluations]);
    }

    #[Route("/new", name: "new")]
    public function new(Request $request): Response
    {
        $evaluation = new Evaluation();
        $evaluation->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(EvaluationType::class, $evaluation, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($evaluation);
            $this->em->flush();
            $this->processEvaluationTranslation($evaluation, $form);
            $this->addFlash('success', $this->translator->trans('success.evaluation.new', [], 'message'));
            return $this->redirectToRoute('evaluation_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/overview/{evaluation}", name: "overview")]
    public function overview(Evaluation $evaluation, Request $request): Response
    {
        return $this->render('evaluation/overview.html.twig', [
            'evaluation' => $evaluation,
            'navigation' => $this->navigationService->forEvaluation($evaluation, NavigationService::EVALUATION_OVERVIEW)
        ]);
    }

    #[Route("/edit/{evaluation}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(Evaluation $evaluation, Request $request): Response
    {
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isSubmitted()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluation.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forEvaluation($evaluation, NavigationService::EVALUATION_EDIT)
        ]);
    }

    #[Route("/delete/{evaluation}", name: "delete")]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(Evaluation $evaluation, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluation.delete', $csrfToken)) {
            $evaluationName = $evaluation->getName();
            foreach ($evaluation->getEvaluationQuestions() as $evaluationQuestion) {
                foreach ($evaluationQuestion->getEvaluationQuestionAnswers() as $evaluationQuestionAnswer) {
                    $this->em->remove($evaluationQuestionAnswer);
                }
                $this->em->remove($evaluationQuestion);
            }
            $this->em->remove($evaluation);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluation.delete', ['%evaluation%' => $evaluationName], 'message'));
            return $this->redirectToRoute('evaluation_index');
        }

        return $this->redirectToRoute('evaluation_edit', ['evaluation' => $evaluation->getId()]);
    }

    #[Route("/evaluate/{evaluation}", name: 'evaluate')]
    #[IsGranted("ROLE_USER")]
    public function evaluate(Evaluation $evaluation, Request $request): Response
    {
        $assessmentExists = false;
        if ($this->isGranted('ROLE_USER')) {
            $evaluationAssessment = $this->em->getRepository(EvaluationAssessment::class)->findOneBy(['evaluation' => $evaluation, 'user' => $this->getUser()]);
            $assessmentExists = $evaluationAssessment !== null;
        }

        return $this->render('evaluation/evaluate.html.twig', [
            'evaluation' => $evaluation,
            'assessmentExists' => $assessmentExists,
            'navigation' => $this->navigationService->forEvaluation($evaluation, NavigationService::EVALUATION_EVALUATE)
        ]);
    }

    #[Route("/evaluate/{evaluation}/add-question", name: 'add_question')]
    #[IsGranted("ROLE_MODERATOR")]
    public function addEvaluationQuestion(Evaluation $evaluation, Request $request): Response
    {
        $evaluationQuestion = (new EvaluationQuestion())
            ->setEvaluation($evaluation)
            ->setLocale($this->getParameter('locale.default'))
            ->setEvaluable(true);

        $form = $this->createForm(EvaluationQuestionType::class, $evaluationQuestion, ['include_translatable_fields' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($evaluationQuestion);
            $this->em->flush();
            $this->processEvaluationQuestionTranslation($evaluationQuestion, $form);
            $this->processAutomaticEvaluationQuestionAnswerCreation($evaluationQuestion);
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestion.new', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluation->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forEvaluation($evaluation, NavigationService::EVALUATION_EXTRA_NEW_QUESTION)
        ]);
    }

    #[Route("/evaluate/{evaluation}/add-evaluator", name: "add_evaluator")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addEvaluationEvaluator(Evaluation $evaluation, Request $request): Response
    {
        $evaluationEvaluator = (new EvaluationEvaluator())->setEvaluation($evaluation);
        $form = $this->createForm(EvaluationEvaluatorType::class, $evaluationEvaluator);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($evaluationEvaluator);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluationEvaluator.new', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluation->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_evaluator/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forEvaluation($evaluation, NavigationService::EVALUATION_EXTRA_NEW_EVALUATOR)
        ]);
    }

    #[Route("/evaluate/{evaluation}/assessment", name: "start_assessment")]
    #[IsGranted("ROLE_USER")]
    public function startAssessment(Evaluation $evaluation, Request $request, EvaluationService $evaluationService): Response
    {
        $evaluationAssessment = $evaluationService->prepareEvaluationAssessment($evaluation, $this->getUser());
        return $this->redirectToRoute('evaluation_assessment_start', ['evaluationAssessment' => $evaluationAssessment->getId()]);
    }

    private function processAutomaticEvaluationQuestionAnswerCreation(EvaluationQuestion $evaluationQuestion)
    {
        if ($evaluationQuestion->getType() === EvaluationQuestion::TYPE_YES_NO) {
            $this->em->persist((new EvaluationQuestionAnswer())->setEvaluationQuestion($evaluationQuestion)->setAnswerText('common.yes')->setAnswerValue('1'));
            $this->em->persist((new EvaluationQuestionAnswer())->setEvaluationQuestion($evaluationQuestion)->setAnswerText('common.no')->setAnswerValue('0'));
            $this->em->flush();
        }
    }

    private function processEvaluationTranslation(Evaluation $evaluation, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $nameAlt = $form->get('nameAlt')->getData();
        if ($nameAlt !== null && trim($nameAlt) !== '') {
            $translationRepository->translate($evaluation, 'name', $localeAlt, trim($nameAlt));
            $translated = true;
        }

        $descriptionAlt = $form->get('descriptionAlt')->getData();
        if ($descriptionAlt !== null && trim($descriptionAlt) !== '') {
            $translationRepository->translate($evaluation, 'description', $localeAlt, trim($descriptionAlt));
            $translated = true;
        }


        $tagsAlt = $form->get('tagsAlt')->getData();
        if ($tagsAlt !== null && count($tagsAlt) > 0) {
            $translationRepository->translate($evaluation, 'tags', $localeAlt, $tagsAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function processEvaluationQuestionTranslation(EvaluationQuestion $evaluationQuestion, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $questionTextAlt = $form->get('questionTextAlt')->getData();
        if ($questionTextAlt !== null && trim($questionTextAlt) !== '') {
            $translationRepository->translate($evaluationQuestion, 'questionText', $localeAlt, trim($questionTextAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}