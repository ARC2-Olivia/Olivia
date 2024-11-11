<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\File;
use App\Entity\PracticalSubmodule;
use App\Entity\Topic;
use App\Form\TopicType;
use App\Traits\BasicFileManagementTrait;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/topic", name: "topic_", requirements: ["_locale" => "%locale.supported%"])]
class TopicController extends BaseController
{
    use BasicFileManagementTrait;

    #[Route("/", name: "index")]
    public function index(Request $request): Response
    {
        $topics = $this->em->getRepository(Topic::class)->findAllSortedByPosition();
        $includeIn = 'en' === $request->getLocale() ? File::INCLUDE_IN_TOPIC_INDEX_DEFAULT : File::INCLUDE_IN_TOPIC_INDEX_ALTERNATE;
        $videos = $this->em->getRepository(File::class)->findByTypeAndInclusion(File::TYPE_VIDEO, $includeIn);
        $files = $this->em->getRepository(File::class)->findByTypeAndInclusion(File::TYPE_FILE, $includeIn);
        return $this->render('topic/index.html.twig', ['topics' => $topics, 'videos' => $videos, 'files' => $files]);
    }

    #[Route("/show/{topic}", name: "show")]
    public function show(Topic $topic): Response
    {
        $theoreticalSubmodules = $this->em->getRepository(Course::class)->findContainingTopicAndOrderedByPosition($topic);
        $practicalSubmodules = $this->em->getRepository(PracticalSubmodule::class)->findContainingTopicAndOrderedByPosition($topic);
        return $this->render('topic/show.html.twig', ['topic' => $topic, 'practicalSubmodules' => $practicalSubmodules, 'theoreticalSubmodules' => $theoreticalSubmodules]);
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
            $image = $form->get('image')->getData();
            $this->storeTopicImage($image, $topic);
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
            $image = $form->get('image')->getData();
            if (null !== $image) $this->removeTopicImage($topic);
            $this->storeTopicImage($image, $topic);
            $this->addFlash('success', $this->translator->trans('success.topic.edit', domain: 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), domain: 'message'));
            }
        }

        return $this->render('topic/edit.html.twig', ['topic' => $topic, 'form' => $form->createView()]);
    }

    #[Route("/delete/{topic}", name: "delete", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(Topic $topic, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if (null !== $csrfToken && $this->isCsrfTokenValid('topic.delete', $csrfToken)) {
            $topicTitle = $topic->getTitle();
            foreach ($this->em->getRepository(Course::class)->findContainingTopic($topic) as $ts) $ts->setTopic(null);
            foreach ($this->em->getRepository(PracticalSubmodule::class)->findContainingTopic($topic) as $ps) $ps->setTopic(null);
            $this->em->remove($topic);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.topic.delete', ['%topic%' => $topicTitle], 'message'));
            return $this->redirectToRoute('topic_index');
        }
        return $this->redirectToRoute('topic_edit', ['topic' => $topic->getId()]);
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

    private function storeTopicImage(?UploadedFile $image, Topic $topic): void
    {
        try {
            if (null !== $image) {
                $uploadDir = $this->getParameter('dir.topic_image');
                $filenamePrefix = sprintf('topic-%d-', $topic->getId());
                $filename = $this->storeFile($image, $uploadDir, $filenamePrefix);
                $topic->setImage($filename);
                $this->em->flush();
            }
        } catch (\Exception $ex) {
            $this->addFlash('warning', $this->translator->trans('warning.topic.image.store', domain: 'message'));
        }
    }

    private function removeTopicImage(Topic $topic): void
    {
        if (null !== $topic->getImage()) {
            $uploadDir = $this->getParameter('dir.topic_image');
            $this->removeFile($uploadDir . '/' . $topic->getImage());
            $topic->setImage(null);
            $this->em->flush();
        }
    }
}