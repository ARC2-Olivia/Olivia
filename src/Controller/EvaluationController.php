<?php

namespace App\Controller;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Form\PracticalSubmoduleProcessorType;
use App\Form\PracticalSubmoduleQuestionType;
use App\Form\PracticalSubmoduleType;
use App\Repository\PracticalSubmoduleRepository;
use App\Service\PracticalSubmoduleService;
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
    public function index(PracticalSubmoduleRepository $practicalSubmoduleRepository): Response
    {
        $submodules = $practicalSubmoduleRepository->findAll();
        return $this->render("evaluation/index.html.twig", ['evaluations' => $submodules]);
    }

    #[Route("/new", name: "new")]
    public function new(Request $request): Response
    {
        $practicalSubmodule = new PracticalSubmodule();
        $practicalSubmodule->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(PracticalSubmoduleType::class, $practicalSubmodule, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($practicalSubmodule);
            $this->em->flush();
            $this->processPracticalSubmoduleTranslation($practicalSubmodule, $form);
            $this->addFlash('success', $this->translator->trans('success.evaluation.new', [], 'message'));
            return $this->redirectToRoute('evaluation_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/overview/{practicalSubmodule}", name: "overview")]
    public function overview(PracticalSubmodule $practicalSubmodule, Request $request): Response
    {
        return $this->render('evaluation/overview.html.twig', [
            'evaluation' => $practicalSubmodule,
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_OVERVIEW)
        ]);
    }

    #[Route("/edit/{practicalSubmodule}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(PracticalSubmodule $practicalSubmodule, Request $request): Response
    {
        $form = $this->createForm(PracticalSubmoduleType::class, $practicalSubmodule);
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
            'evaluation' => $practicalSubmodule,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EDIT)
        ]);
    }

    #[Route("/delete/{practicalSubmodule}", name: "delete")]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(PracticalSubmodule $practicalSubmodule, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluation.delete', $csrfToken)) {
            $submoduleName = $practicalSubmodule->getName();
            foreach ($practicalSubmodule->getPracticalSubmoduleQuestions() as $question) {
                foreach ($question->getPracticalSubmoduleQuestionAnswers() as $questionAnswer) {
                    $this->em->remove($questionAnswer);
                }
                $this->em->remove($question);
            }
            $this->em->remove($practicalSubmodule);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluation.delete', ['%evaluation%' => $submoduleName], 'message'));
            return $this->redirectToRoute('evaluation_index');
        }

        return $this->redirectToRoute('evaluation_edit', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/evaluate/{practicalSubmodule}", name: 'evaluate')]
    #[IsGranted("ROLE_USER")]
    public function evaluate(PracticalSubmodule $practicalSubmodule): Response
    {
        $assessmentCompleted = false;
        if ($this->isGranted('ROLE_USER')) {
            $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
            $assessmentCompleted = $assessment !== null && $assessment->isCompleted();
        }

        $questions = $processors = null;
        if ($this->isGranted('ROLE_MODERATOR')) {
            $questions = $this->em->getRepository(PracticalSubmoduleQuestion::class)->findOrderedForSubmodule($practicalSubmodule);
            $processors = $this->em->getRepository(PracticalSubmoduleProcessor::class)->findOrderedForSubmodule($practicalSubmodule);
        }
        return $this->render('evaluation/evaluate.html.twig', [
            'evaluation' => $practicalSubmodule,
            'evaluationQuestions' => $questions,
            'evaluationEvaluators' => $processors,
            'assessmentCompleted' => $assessmentCompleted,
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EVALUATE)
        ]);
    }

    #[Route("/evaluate/{practicalSubmodule}/add-question", name: 'add_question')]
    #[IsGranted("ROLE_MODERATOR")]
    public function addEvaluationQuestion(PracticalSubmodule $practicalSubmodule, Request $request): Response
    {
        $question = (new PracticalSubmoduleQuestion())
            ->setPracticalSubmodule($practicalSubmodule)
            ->setLocale($this->getParameter('locale.default'))
            ->setEvaluable(true);

        $form = $this->createForm(PracticalSubmoduleQuestionType::class, $question, ['include_translatable_fields' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($question);
            $this->em->flush();
            $this->processPracticalSubmoduleQuestionTranslation($question, $form);
            $this->processAutomaticPracticalSubmoduleQuestionAnswerCreation($question);
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestion.new', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/new.html.twig', [
            'evaluation' => $practicalSubmodule,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_NEW_QUESTION)
        ]);
    }

    #[Route("/evaluate/{practicalSubmodule}/add-evaluator", name: "add_evaluator")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addPracticalSubmoduleProcessor(PracticalSubmodule $practicalSubmodule, Request $request): Response
    {
        $processor = (new PracticalSubmoduleProcessor())->setPracticalSubmodule($practicalSubmodule)->setIncluded(true);
        $form = $this->createForm(PracticalSubmoduleProcessorType::class, $processor);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($processor);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluationEvaluator.new', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_evaluator/new.html.twig', [
            'evaluation' => $practicalSubmodule,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_NEW_EVALUATOR)
        ]);
    }

    #[Route("/evaluate/{practicalSubmodule}/assessment", name: "start_assessment")]
    #[IsGranted("ROLE_USER")]
    public function startAssessment(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleService $practicalSubmoduleService): Response
    {
        $csrfToken = $request->get('_csrf_token');

        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationAssessment.start', $csrfToken)) {
            $request->getSession()->set('evaluationAssessment.start', true);
            $practicalSubmoduleAssessment = $practicalSubmoduleService->prepareAssessment($practicalSubmodule, $this->getUser());
            return $this->forward('App\Controller\EvaluationAssessmentController::start', ['practicalSubmoduleAssessment' => $practicalSubmoduleAssessment]);
        }

        $this->addFlash('error', $this->translator->trans('error.evaluationAssessment.start', [], 'message'));
        return $this->redirectToRoute('evaluation_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/evaluate/{practicalSubmodule}/results", name: "results")]
    #[IsGranted("ROLE_USER")]
    public function results(PracticalSubmodule $practicalSubmodule, PracticalSubmoduleService $practicalSubmoduleService): Response
    {
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
        $results = $practicalSubmoduleService->runProcessors($assessment);
        return $this->render('evaluation/results.html.twig', [
            'evaluation' => $practicalSubmodule,
            'evaluationAssessment' => $assessment,
            'results' => $results,
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_RESULTS)
        ]);
    }

    private function processAutomaticPracticalSubmoduleQuestionAnswerCreation(PracticalSubmoduleQuestion $evaluationQuestion)
    {
        if ($evaluationQuestion->getType() === PracticalSubmoduleQuestion::TYPE_YES_NO) {
            $this->em->persist((new PracticalSubmoduleQuestionAnswer())->setPracticalSubmoduleQuestion($evaluationQuestion)->setAnswerText('common.yes')->setAnswerValue('1'));
            $this->em->persist((new PracticalSubmoduleQuestionAnswer())->setPracticalSubmoduleQuestion($evaluationQuestion)->setAnswerText('common.no')->setAnswerValue('0'));
            $this->em->flush();
        }
    }

    private function processPracticalSubmoduleTranslation(PracticalSubmodule $evaluation, \Symfony\Component\Form\FormInterface $form)
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

    private function processPracticalSubmoduleQuestionTranslation(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $questionTextAlt = $form->get('questionTextAlt')->getData();
        if ($questionTextAlt !== null && trim($questionTextAlt) !== '') {
            $translationRepository->translate($practicalSubmoduleQuestion, 'questionText', $localeAlt, trim($questionTextAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}