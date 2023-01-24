<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\EvaluationQuestion;
use App\Entity\EvaluationQuestionAnswer;
use App\Form\EvaluationQuestionType;
use App\Form\EvaluationType;
use App\Repository\EvaluationRepository;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/evaluation", name: "evaluation_")]
class EvaluationController extends BaseController
{
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
        return $this->render('evaluation/overview.html.twig', ['evaluation' => $evaluation, 'activeCard' => 'overview']);
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

        return $this->render('evaluation/edit.html.twig', ['evaluation' => $evaluation, 'activeCard' => 'edit', 'form' => $form->createView()]);
    }

    #[Route("/delete/{evaluation}", name: "delete")]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(Evaluation $evaluation, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluation.delete', $csrfToken)) {
            $evaluationName = $evaluation->getName();
            $this->em->remove($evaluation);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluation.delete', ['%evaluation%' => $evaluationName], 'message'));
            return $this->redirectToRoute('evaluation_index');
        }

        return $this->redirectToRoute('evaluation_edit', ['evaluation' => $evaluation->getId()]);
    }

    #[Route("/evaluate/{evaluation}", name: 'evaluate')]
    public function evaluate(Evaluation $evaluation, Request $request): Response
    {
        return $this->render('evaluation/evaluate.html.twig', ['evaluation' => $evaluation, 'activeCard' => 'evaluate']);
    }

    #[Route("/evaluate/{evaluation}/add-question", name: 'add_question')]
    #[IsGranted("ROLE_MODERATOR")]
    public function addEvaluationQuestion(Evaluation $evaluation, Request $request): Response
    {
        $evaluationQuestion = (new EvaluationQuestion())->setEvaluation($evaluation);
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

        return $this->render('evaluation/evaluation_question/form.html.twig', ['evaluation' => $evaluation, 'form' => $form->createView(), 'activeCard' => 'newQuestion']);
    }

    private function processAutomaticEvaluationQuestionAnswerCreation(EvaluationQuestion $evaluationQuestion)
    {
        if ($evaluationQuestion->getType() === EvaluationQuestion::TYPE_YES_NO || $evaluationQuestion->getType() === EvaluationQuestion::TYPE_NO_EVALUATE) {
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