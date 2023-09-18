<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Entity\Topic;
use App\Form\TopicType;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/topic", name: "topic_", requirements: ["_locale" => "%locale.supported%"])]
class TopicController extends BaseController
{
    #[Route("/", name: "index")]
    public function index(): Response
    {
        $topics = $this->em->getRepository(Topic::class)->findAll();
        return $this->render('topic/index.html.twig', ['topics' => $topics]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted("ROLE_MODERATOR")]
    public function new(Request $request): Response
    {
        $topic = new Topic();
        $topic->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(TopicType::class, $topic, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($topic);
            foreach ($topic->getTheoreticalSubmodules() as $ts) $ts->setTopic($topic);
            foreach ($topic->getPracticalSubmodules() as $ps) $ps->setTopic($topic);
            $this->em->flush();
            $this->processTranslation($topic, $form);
            $this->addFlash('success', $this->translator->trans('success.topic.new', domain: 'message'));
            return $this->redirectToRoute('topic_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), domain: 'message'));
            }
        }

        return $this->render('topic/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/edit/{topic}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(Topic $topic, Request $request): Response
    {
        $form = $this->createForm(TopicType::class, $topic, ['edit_mode' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($this->em->getRepository(Course::class)->findContainingTopic($topic) as $ts) $ts->setTopic(null);
            foreach ($this->em->getRepository(PracticalSubmodule::class)->findContainingTopic($topic) as $ps) $ps->setTopic(null);
            foreach ($topic->getTheoreticalSubmodules() as $ts) $ts->setTopic($topic);
            foreach ($topic->getPracticalSubmodules() as $ps) $ps->setTopic($topic);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.topic.edit', domain: 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), domain: 'message'));
            }
        }

        return $this->render('topic/edit.html.twig', ['topic' => $topic, 'form' => $form->createView()]);
    }

    private function processTranslation(Topic $topic, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $titleAlt = $form->get('titleAlt')->getData();
        if (null !== $titleAlt && '' !== trim($titleAlt)) {
            $translationRepository->translate($topic, 'title', $localeAlt, $titleAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}