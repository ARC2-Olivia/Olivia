<?php

namespace App\Controller;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleAssessmentAnswer;
use App\Entity\PracticalSubmodulePage;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorGroup;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Exception\PSImport\ErroneousFirstTaskException;
use App\Exception\PSImport\MissingTaskOrderKeyException;
use App\Exception\PSImport\WrongFirstTaskTypeException;
use App\Form\BasicFileUploadType;
use App\Form\PracticalSubmodulePageType;
use App\Form\PracticalSubmoduleProcessorGroupType;
use App\Form\PracticalSubmoduleProcessorType;
use App\Form\PracticalSubmoduleQuestionType;
use App\Form\PracticalSubmoduleType;
use App\Repository\PracticalSubmodulePageRepository;
use App\Repository\PracticalSubmoduleProcessorGroupRepository;
use App\Repository\PracticalSubmoduleProcessorRepository;
use App\Repository\PracticalSubmoduleQuestionRepository;
use App\Repository\PracticalSubmoduleRepository;
use App\Service\ExcelService;
use App\Service\PdfService;
use App\Service\PracticalSubmoduleService;
use App\Service\NavigationService;
use App\Service\SanitizerService;
use App\Service\WordService;
use App\Traits\BasicFileManagementTrait;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/{_locale}/practical-submodule", name: "practical_submodule_", requirements: ["_locale" => "%locale.supported%"])]
class PracticalSubmoduleController extends BaseController
{
    use BasicFileManagementTrait;

    private ?NavigationService $navigationService = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, NavigationService $navigationService)
    {
        parent::__construct($em, $translator);
        $this->navigationService = $navigationService;
    }

    #[Route("/", name: "index")]
    public function index(PracticalSubmoduleRepository $practicalSubmoduleRepository): Response
    {
        $submodules = $practicalSubmoduleRepository->findOrderedByPosition();
        return $this->render('evaluation/index.html.twig', ['evaluations' => $submodules]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted("ROLE_MODERATOR")]
    public function new(Request $request, PracticalSubmoduleService $practicalSubmoduleService, SanitizerService $sanitizerService): Response
    {
        $practicalSubmodule = new PracticalSubmodule();
        $practicalSubmodule->setModeOfOperation($practicalSubmodule::MODE_OF_OPERATION_ADVANCED);
        $practicalSubmodule->setLocale($this->getParameter('locale.default'));

        $form = $this->createForm(PracticalSubmoduleType::class, $practicalSubmodule, ['include_translatable_fields' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $practicalSubmodule->setDescription($sanitizerService->sanitizeHtml($practicalSubmodule->getDescription()));
            $this->em->persist($practicalSubmodule);
            $this->em->flush();
            $image = $form->get('image')->getData();
            $this->storePracticalSubmoduleImage($image, $practicalSubmodule);
            $this->processPracticalSubmoduleTranslation($practicalSubmodule, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmodule.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        $importForm = $this->createForm(BasicFileUploadType::class, null, ['mimeTypes' => 'application/json', 'extensions' => '.json']);
        $importForm->handleRequest($request);
        if ($importForm->isSubmitted() && $importForm->isValid()) {
            try {
                /** @var UploadedFile $importFile */
                $importFile = $importForm->get('file')->getData();
                $tasks = file_get_contents($importFile->getPathname());
                $tasks = json_decode($tasks, true);
                $practicalSubmodule = $practicalSubmoduleService->import($tasks);
                if (null !== $practicalSubmodule) {
                    return $this->redirectToRoute('practical_submodule_overview', ['practicalSubmodule' => $practicalSubmodule->getId()]);
                }
            } catch (ErroneousFirstTaskException | WrongFirstTaskTypeException | MissingTaskOrderKeyException $exception) {
                $this->addFlash('error', $this->translator->trans($exception->getMessage(), [], 'message'));
            } catch (\Exception) {
                $this->addFlash('error', $this->translator->trans('error.practicalSubmodule.import.default', [], 'message'));
            }
        } else {
            foreach ($importForm->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/new.html.twig', ['form' => $form->createView(), 'importForm' => $importForm->createView()]);
    }

    #[Route("/overview/{practicalSubmodule}", name: "overview")]
    public function overview(PracticalSubmodule $practicalSubmodule): Response
    {
        return $this->render('evaluation/overview.html.twig', [
            'evaluation' => $practicalSubmodule,
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_OVERVIEW)
        ]);
    }

    #[Route("/edit/{practicalSubmodule}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleService $practicalSubmoduleService, SanitizerService $sanitizerService): Response
    {
        $form = $this->createForm(PracticalSubmoduleType::class, $practicalSubmodule, ['has_advanced_mode_features' => $practicalSubmoduleService->hasAdvancedModeFeatures($practicalSubmodule)]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $practicalSubmodule->setDescription($sanitizerService->sanitizeHtml($practicalSubmodule->getDescription()));
            $this->em->flush();
            $image = $form->get('image')->getData();
            if ($image !== null) $this->removePracticalSubmoduleImage($practicalSubmodule);
            $this->storePracticalSubmoduleImage($image, $practicalSubmodule);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmodule.edit', [], 'message'));
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
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmodule.delete', $csrfToken)) {
            $submoduleName = $practicalSubmodule->getName();

            foreach ($practicalSubmodule->getPracticalSubmoduleQuestions() as $question) {
                $question->setDependentPracticalSubmoduleQuestion(null);
            }
            $this->em->flush();

            foreach ($practicalSubmodule->getPracticalSubmoduleQuestions() as $question) {
                foreach ($this->em->getRepository(PracticalSubmoduleAssessmentAnswer::class)->findBy(['practicalSubmoduleQuestion' => $question]) as $assessmentAnswer) $this->em->remove($assessmentAnswer);
                foreach ($question->getPracticalSubmoduleQuestionAnswers() as $questionAnswer) $this->em->remove($questionAnswer);
                $this->em->remove($question);
            }

            foreach ($this->em->getRepository(PracticalSubmoduleAssessment::class)->findBy(['practicalSubmodule' => $practicalSubmodule]) as $assessment) {
                foreach ($assessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) $this->em->remove($assessmentAnswer);
                $this->em->remove($assessment);
            }

            $this->em->remove($practicalSubmodule);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.practicalSubmodule.delete', ['%evaluation%' => $submoduleName], 'message'));
            return $this->redirectToRoute('practical_submodule_index');
        }

        return $this->redirectToRoute('practical_submodule_edit', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/evaluate/{practicalSubmodule}", name: 'evaluate')]
    #[IsGranted("ROLE_USER")]
    public function evaluate(PracticalSubmodule $practicalSubmodule): Response
    {
        if ($practicalSubmodule->isRevisionMode() && !$this->isGranted('ROLE_TESTER')) {
            return $this->redirectToRoute('practical_submodule_overview', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        }

        $assessmentLastSubmittedAt = null;
        $assessment = null;
        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_MODERATOR')) {
            $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
            if (null !== $assessment) {
                $assessmentLastSubmittedAt = null !== $assessment->getLastSubmittedAt()
                    ? $this->translator->trans('practicalSubmoduleAssessment.message.lastSubmittedAt', ['%datetime%' => $assessment->getLastSubmittedAt()->format('d.m.Y. H:i')],  'app')
                    : null
                ;
            }
        }

        $questions = $processors = $pages = $processorGroups = null;
        if ($this->isGranted('ROLE_MODERATOR')) {
            $questions = $this->em->getRepository(PracticalSubmoduleQuestion::class)->findOrderedForSubmodule($practicalSubmodule);
            $processors = $this->em->getRepository(PracticalSubmoduleProcessor::class)->findOrderedForSubmodule($practicalSubmodule);
            $pages = $this->em->getRepository(PracticalSubmodulePage::class)->findOrderedForSubmodule($practicalSubmodule);
            $processorGroups = $this->em->getRepository(PracticalSubmoduleProcessorGroup::class)->findOrderedForSubmodule($practicalSubmodule);
        }

        return $this->render('evaluation/evaluate.html.twig', [
            'evaluation' => $practicalSubmodule,
            'evaluationQuestions' => $questions,
            'evaluationEvaluators' => $processors,
            'pages' => $pages,
            'procesorGroups' => $processorGroups,
            'assessment' => $assessment,
            'assessmentLastSubmittedAt' => $assessmentLastSubmittedAt,
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EVALUATE),
            'questionCount' => $this->em->getRepository(PracticalSubmoduleQuestion::class)->countActualQuestions($practicalSubmodule)
        ]);
    }

    #[Route("/evaluate/{practicalSubmodule}/add-question", name: 'add_question')]
    #[IsGranted("ROLE_MODERATOR")]
    public function addQuestion(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleQuestionRepository $practicalSubmoduleQuestionRepository): Response
    {
        $question = (new PracticalSubmoduleQuestion())
            ->setPracticalSubmodule($practicalSubmodule)
            ->setLocale($this->getParameter('locale.default'))
            ->setEvaluable(true);

        $form = $this->createForm(PracticalSubmoduleQuestionType::class, $question, ['include_translatable_fields' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $question->setPosition($practicalSubmoduleQuestionRepository->maxPositionForSubmodule($practicalSubmodule) + 1);
            $this->em->persist($question);
            $this->em->flush();
            $this->processPracticalSubmoduleQuestionTranslation($question, $form);
            $this->processAutomaticPracticalSubmoduleQuestionAnswerCreation($question);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleQuestion.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_question_edit', ['practicalSubmoduleQuestion' => $question->getId()]);
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

    #[Route("/evaluate/{practicalSubmodule}/add-processor", name: "add_processor")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addProcessor(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleProcessorRepository $practicalSubmoduleProcessorRepository): Response
    {
        $processor = (new PracticalSubmoduleProcessor())->setPracticalSubmodule($practicalSubmodule)->setIncluded(true);
        $form = $this->createForm(PracticalSubmoduleProcessorType::class, $processor);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $processor->setPosition($practicalSubmoduleProcessorRepository->maxPositionForSubmodule($practicalSubmodule) + 1);
            $this->em->persist($processor);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleProcessor.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_processor_edit', ['practicalSubmoduleProcessor' => $processor->getId()]);
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

    #[Route("/evaluate/{practicalSubmodule}/add-page", name: "add_page")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addPage(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmodulePageRepository $practicalSubmodulePageRepository): Response
    {
        $page = (new PracticalSubmodulePage())->setPracticalSubmodule($practicalSubmodule);
        $form = $this->createForm(PracticalSubmodulePageType::class, $page, ['include_translatable_fields' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $page->setPosition($practicalSubmodulePageRepository->maxPositionForSubmodule($practicalSubmodule) + 1);
            $this->em->persist($page);

            /** @var PracticalSubmoduleQuestion[] $questions */
            $questions = $form->get('questions')->getData();
            if ($questions !== null) {
                foreach ($questions as $question) {
                    $question->setPracticalSubmodulePage($page);
                }
            }

            $this->em->flush();
            $this->processPracticalSubmodulePageTranslation($page, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmodulePage.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/page/new.html.twig', [
            'evaluation' => $practicalSubmodule,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_NEW_PAGE)
        ]);
    }

    #[Route("/evaluate/{practicalSubmodule}/add-processor-group", name: "add_processor_group")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addProcessorGroup(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleProcessorGroupRepository $practicalSubmoduleProcessorGroupRepository): Response
    {
        $processorGroup = (new PracticalSubmoduleProcessorGroup())->setPracticalSubmodule($practicalSubmodule);
        $form = $this->createForm(PracticalSubmoduleProcessorGroupType::class, $processorGroup, ['include_translatable_fields' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $processorGroup->setPosition($practicalSubmoduleProcessorGroupRepository->maxPositionForSubmodule($practicalSubmodule) + 1);
            $this->em->persist($processorGroup);

            /** @var PracticalSubmoduleProcessor[] $processors */
            $processors = $form->get('processors')->getData();
            if (null !== $processors) {
                foreach ($processors as $processor) {
                    $processor->setPracticalSubmoduleProcessorGroup($processorGroup);
                }
            }

            $this->em->flush();
            $this->processPracticalSubmoduleProcessorGroupTranslation($processorGroup, $form);
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleProcessorGroup.new', [], 'message'));
            return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/processorGroup/new.html.twig', [
            'evaluation' => $practicalSubmodule,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_NEW_PROCESSOR_GROUP)
        ]);
    }

    #[Route("/evaluate/{practicalSubmodule}/assessment", name: "start_assessment")]
    #[IsGranted("ROLE_USER")]
    public function startAssessment(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleService $practicalSubmoduleService): Response
    {
        if ($practicalSubmodule->isRevisionMode() && !$this->isGranted('ROLE_TESTER')) {
            return $this->redirectToRoute('practical_submodule_overview', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        }

        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleAssessment.start', $csrfToken)) {
            $request->getSession()->set('practicalSubmoduleAssessment.start', true);
            $assessment = $practicalSubmoduleService->prepareAssessment($practicalSubmodule, $this->getUser());
            return $this->forward('App\Controller\PracticalSubmoduleAssessmentController::start', ['practicalSubmoduleAssessment' => $assessment]);
        }

        $this->addFlash('error', $this->translator->trans('error.practicalSubmoduleAssessment.start', [], 'message'));
        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/evaluate/{practicalSubmodule}/assessment/edit", name: "edit_assessment")]
    #[IsGranted("ROLE_USER")]
    public function editAssessment(PracticalSubmodule $practicalSubmodule, Request $request, PracticalSubmoduleService $practicalSubmoduleService): Response
    {
        if ($practicalSubmodule->isRevisionMode() && !$this->isGranted('ROLE_TESTER')) {
            return $this->redirectToRoute('practical_submodule_overview', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        }

        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleAssessment.edit', $csrfToken)) {
            $request->getSession()->set('practicalSubmoduleAssessment.edit', true);
            $assessment = $practicalSubmoduleService->prepareAssessment($practicalSubmodule, $this->getUser(), true);
            return $this->forward('App\Controller\PracticalSubmoduleAssessmentController::edit', ['practicalSubmoduleAssessment' => $assessment]);
        }

        $this->addFlash('error', $this->translator->trans('error.practicalSubmoduleAssessment.edit', [], 'message'));
        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/evaluate/{practicalSubmodule}/results", name: "results")]
    #[IsGranted("ROLE_USER")]
    public function results(PracticalSubmodule $practicalSubmodule, PracticalSubmoduleService $practicalSubmoduleService): Response
    {
        if ($practicalSubmodule->isRevisionMode() && !$this->isGranted('ROLE_TESTER')) {
            return $this->redirectToRoute('practical_submodule_overview', ['practicalSubmodule' => $practicalSubmodule->getId()]);
        }

        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
        $results = $practicalSubmoduleService->runProcessors($assessment);
        $answerData = [];

        if (PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $practicalSubmodule->getModeOfOperation()) {
            foreach ($results as $result) {
                $answerDatum = ['result' => $result, 'answers' => null];
                if ($result->isQuestionSet()) {
                    foreach ($assessment->getPracticalSubmoduleAssessmentAnswers() as $answer) {
                        if ($answer->getPracticalSubmoduleQuestion()->getId() !== $result->getQuestion()->getId()) continue;
                        if (null === $answerDatum['answers']) $answerDatum['answers'] = [];
                        $answerDatum['answers'][] = $answer->getDisplayableAnswer();
                    }
                }
                $answerData[] = $answerDatum;
            }
        } else {
            foreach ($assessment->getPracticalSubmoduleAssessmentAnswers() as $answer) {
                $questionId = $answer->getPracticalSubmoduleQuestion()->getId();
                if (!key_exists($questionId, $answerData)) {
                    $answerDatum = new \stdClass();
                    $answerDatum->questionId = $questionId;
                    $answerDatum->question = $answer->getPracticalSubmoduleQuestion()->getQuestionText();
                    $answerDatum->answers = [];
                    $answerDatum->dependentQuestionId = $answer->getPracticalSubmoduleQuestion()?->getDependentPracticalSubmoduleQuestion()?->getId();
                    $answerDatum->dependees = [];
                    $answerDatum->unansweredDependees = [];
                    $answerData[$questionId] = $answerDatum;
                }
                $answerData[$questionId]->answers[] = $answer->getDisplayableAnswer();
            }

            $dependeeIds = [];
            $dependencyGrouping = [];
            foreach ($answerData as $questionId => $answerDatum) {
                $dependentQuestionId = $answerDatum->dependentQuestionId;
                if (null !== $dependentQuestionId) {
                    if (!key_exists($dependentQuestionId, $dependencyGrouping)) {
                        $dependencyGrouping[$dependentQuestionId] = [];
                    }
                    $dependencyGrouping[$dependentQuestionId][] = $questionId;
                    $dependeeIds[] = $questionId;
                }
            }

            foreach ($dependencyGrouping as $dependentQuestionId => $questionIds) {
                foreach ($questionIds as $questionId) {
                    $answerData[$dependentQuestionId]->dependees[] = $answerData[$questionId];
                }
            }

            foreach ($dependeeIds as $dependeeId) {
                unset($answerData[$dependeeId]);
            }

            $practicalSubmoduleQuestionRepository = $this->em->getRepository(PracticalSubmoduleQuestion::class);
            foreach (array_keys($answerData) as $questionId) {
                $exclusions = array_map(function ($dependee) { return $dependee->questionId; }, $answerData[$questionId]->dependees);
                $unansweredDependees = $practicalSubmoduleQuestionRepository->findDependingQuestionTexts($questionId, $exclusions);
                $answerData[$questionId]->unansweredDependees = $unansweredDependees;
            }
        }

        $cookieBanner = [];
        if (PracticalSubmodule::EXPORT_TYPE_COOKIE_BANNER === $practicalSubmodule->getExportType()) {
            foreach ($results as $result) {
                $cookieBanner[$result->getExportTag()] = $result->getText();
            }
        }

        $showBanner = key_exists('usage', $cookieBanner)
            && key_exists('link', $cookieBanner)
            && key_exists('first_party', $cookieBanner)
        ;

        return $this->render('evaluation/results.html.twig', [
            'evaluation' => $practicalSubmodule,
            'answerData' => $answerData,
            'results' => $results,
            'cookieBanner' => $cookieBanner,
            'showBanner' => $showBanner,
            'showReportMessage' => true,
            'navigation' => $this->navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_RESULTS)
        ]);
    }

    #[Route("/export/{practicalSubmodule}/results", name: "export_results")]
    #[IsGranted("ROLE_USER")]
    public function exportResults(PracticalSubmodule $practicalSubmodule, WordService $wordService, ExcelService $excelService, PdfService $pdfService, Request $request): Response
    {
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);

        if (PracticalSubmodule::exportsToWord($practicalSubmodule)) {
            $document = $wordService->generateDocumentFromAssessment($assessment, $request->getLocale());
            $filename = $practicalSubmodule->getName().'.docx';
            return $this->file($document, $filename)->deleteFileAfterSend();
        }

        if (PracticalSubmodule::exportsToExcel($practicalSubmodule)) {
            $document = $excelService->generateDocumentFromAssessment($assessment, $request->getLocale());
            $filename = $practicalSubmodule->getName().'.xlsx';
            return $this->file($document, $filename)->deleteFileAfterSend();
        }

        if (PracticalSubmodule::exportsToPdf($practicalSubmodule)) {
            $document = $pdfService->generateDocumentFromAssessment($assessment, $request->getLocale());
            $filename = $practicalSubmodule->getName().'.pdf';
            return $this->file($document, $filename)->deleteFileAfterSend();
        }

        $this->addFlash('error', $this->translator->trans('error.export.results', domain: 'message'));
        return $this->redirectToRoute('practical_submodule_results', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/export/{practicalSubmodule}/submodule", name: "export_submodule")]
    #[IsGranted('ROLE_MODERATOR')]
    public function exportSubmodule(PracticalSubmodule $practicalSubmodule, PracticalSubmoduleService $practicalSubmoduleService, TranslatableListener $translatableListener): Response
    {
        $localeDefault = $this->getParameter('locale.default');
        $localeAlt = $this->getParameter('locale.alternate');
        $activeLocale = $translatableListener->getListenerLocale();
        $isActiveLocaleChanged = false;

        if ($localeAlt === $activeLocale) {
            $translatableListener->setTranslatableLocale($localeDefault);
            $this->em->refresh($practicalSubmodule);
            $isActiveLocaleChanged = true;
        }

        $tasks = $practicalSubmoduleService->export($practicalSubmodule);

        if ($isActiveLocaleChanged) {
            $translatableListener->setTranslatableLocale($activeLocale);
        }

        $fs = new Filesystem();
        $file = $fs->tempnam($this->getParameter('dir.temp'), 'ps-');
        $fs->appendToFile($file, json_encode($tasks));
        $filename = $practicalSubmodule->getName().'.json';
        return $this->file($file, $filename)->deleteFileAfterSend();
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

        $publicNameAlt = $form->get('publicNameAlt')->getData();
        if ($publicNameAlt !== null && trim($publicNameAlt) !== '') {
            $translationRepository->translate($evaluation, 'publicName', $localeAlt, trim($publicNameAlt));
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


        $reportCommentAlt = $form->get('reportCommentAlt')->getData();
        if ($reportCommentAlt !== null && trim($reportCommentAlt) !== '') {
            $translationRepository->translate($evaluation, 'reportComment', $localeAlt, $reportCommentAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function processPracticalSubmoduleQuestionTranslation(PracticalSubmoduleQuestion $practicalSubmoduleQuestion, \Symfony\Component\Form\FormInterface $form): void
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

    private function processPracticalSubmodulePageTranslation(PracticalSubmodulePage $practicalSubmodulePage, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $titleAlt = $form->get('titleAlt')->getData();
        if ($titleAlt !== null && trim($titleAlt) !== '') {
            $translationRepository->translate($practicalSubmodulePage, 'title', $localeAlt, trim($titleAlt));
            $translated = true;
        }

        $descriptionAlt = $form->get('descriptionAlt')->getData();
        if ($descriptionAlt !== null && trim($descriptionAlt) !== '') {
            $translationRepository->translate($practicalSubmodulePage, 'description', $localeAlt, trim($descriptionAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function processPracticalSubmoduleProcessorGroupTranslation(PracticalSubmoduleProcessorGroup $processorGroup, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $titleAlt = $form->get('titleAlt')->getData();
        if ($titleAlt !== null && trim($titleAlt) !== '') {
            $translationRepository->translate($processorGroup, 'title', $localeAlt, trim($titleAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function removePracticalSubmoduleImage(PracticalSubmodule $practicalSubmodule)
    {
        if ($practicalSubmodule->getImage() !== null) {
            $uploadDir = $this->getParameter('dir.course_image');
            $this->removeFile($uploadDir . '/' . $practicalSubmodule->getImage());
            $practicalSubmodule->setImage(null);
            $this->em->flush();
        }
    }

    private function storePracticalSubmoduleImage(?UploadedFile $image, PracticalSubmodule $practicalSubmodule)
    {
        try {
            if ($image !== null) {
                $uploadDir = $this->getParameter('dir.practical_submodule_image');
                $filenamePrefix = sprintf('practical-submodule-%d-', $practicalSubmodule->getId());
                $filename = $this->storeFile($image, $uploadDir, $filenamePrefix);
                $practicalSubmodule->setImage($filename);
                $this->em->flush();
            }
        } catch (\Exception $ex) {
            $this->addFlash('warning', $this->translator->trans('warning.practicalSubmodule.image.store', [], 'message'));
        }
    }
}