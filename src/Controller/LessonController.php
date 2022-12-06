<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Entity\Lesson;
use App\Entity\LessonItemEmbeddedVideo;
use App\Entity\LessonItemFile;
use App\Entity\LessonItemText;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use App\Traits\BasicFileManagementTrait;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/lesson", name: "lesson_")]
class LessonController extends AbstractController
{
    use BasicFileManagementTrait;

    private ?EntityManagerInterface $em = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    #[Route("/course/{course}", name: "course")]
    #[IsGranted("view", subject: "course")]
    public function course(Course $course, LessonRepository $lessonRepository): Response
    {
        $lessons = $lessonRepository->findAllForCourseSortedByPosition($course);
        return $this->render('lesson/course.html.twig', ['course' => $course, 'lessons' => $lessons]);
    }

    #[Route("/course/{course}/new/{lessonType}", name: "new", defaults: ['lessonType' => Lesson::TYPE_TEXT])]
    #[IsGranted("ROLE_MODERATOR")]
    public function new(Course $course, string $lessonType, Request $request): Response
    {
        if (!in_array($lessonType, Lesson::getSupportedLessonTypes())) {
            $lessonType = Lesson::TYPE_TEXT;
        }
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson, ['lesson_type' => $lessonType, 'include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository = $this->em->getRepository(Lesson::class);
            $lesson->setCourse($course);
            $lesson->setPosition($lessonRepository->nextPositionInCourse($course));
            $lessonRepository->save($lesson, true);
            $this->processLessonTranslation($lesson, $form);

            if ($lesson->getType() === Lesson::TYPE_TEXT) {
                $this->handleTextLessonType($form, $lesson);
            } else if ($lesson->getType() === Lesson::TYPE_FILE) {
                try {
                    $this->handleFileLessonType($form, $course, $lesson);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.file.store', [], 'message'));
                    $this->em->remove($lesson);
                    $this->em->flush();
                    return $this->redirectToRoute('lesson_new', ['course' => $course->getId()]);
                }
            } else if ($lesson->getType() === Lesson::TYPE_VIDEO) {
                try {
                    $this->handleVideoLessonType($form, $lesson);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.video.store', [], 'message'));
                    $this->em->remove($lesson);
                    $this->em->flush();
                    return $this->redirectToRoute('lesson_new', ['course' => $course->getId()]);
                }
            }

            $this->addFlash('success', $this->translator->trans('success.lesson.new', ['%course%' => $course->getName()], 'message'));
            return $this->redirectToRoute('lesson_course', ['course' => $course->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('lesson/new.html.twig', ['course' => $course, 'form' => $form->createView(), 'lessonType' => $lessonType]);
    }

    #[Route("/show/{lesson}", name: "show")]
    #[IsGranted("view", subject: "lesson")]
    public function show(Lesson $lesson, LessonRepository $lessonRepository, EntityManagerInterface $em): Response
    {
        $lessonItem = match ($lesson->getType()) {
            Lesson::TYPE_TEXT => $em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_FILE => $em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_VIDEO => $em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]),
            default => null,
        };

        $previousLesson = $lessonRepository->findPreviousLesson($lesson);
        $nextLesson = $lessonRepository->findNextLesson($lesson);
        $orderedLessons = $lessonRepository->findAllForCourseSortedByPosition($lesson->getCourse());
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'lessonItem' => $lessonItem,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson
        ]);
    }

    #[Route("/delete/{lesson}", name: "delete")]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(Lesson $lesson, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('lesson.delete', $csrfToken)) {
            $course = $lesson->getCourse();
            $lessonName = $lesson->getName();
            switch ($lesson->getType()) {
                case Lesson::TYPE_TEXT: {
                    $lessonItemText = $this->em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]);
                    $this->em->remove($lessonItemText);
                    break;
                }
                case Lesson::TYPE_VIDEO: {
                    $lessonItemEmbeddedVideo = $this->em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]);
                    $this->em->remove($lessonItemEmbeddedVideo);
                    break;
                }
                case Lesson::TYPE_FILE: {
                    $lessonItemFile = $this->em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]);
                    $uploadDir = $this->getParameter('dir.lesson_file');
                    $this->removeFile($uploadDir . '/' . $lessonItemFile->getFilename());
                    $this->em->remove($lessonItemFile);
                    break;
                }
            }
            $this->em->remove($lesson);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.lesson.delete', ['%lesson%' => $lessonName, '%course%' => $course->getName()], 'message'));
            return $this->redirectToRoute('lesson_course', ['course' => $course->getId()]);
        }

        return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
    }

    #[Route("/edit/{lesson}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(Lesson $lesson, Request $request): Response
    {
        $lessonItem = match ($lesson->getType()) {
            Lesson::TYPE_TEXT => $this->em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_FILE => $this->em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_VIDEO => $this->em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]),
            default => null,
        };
        $form = $this->createForm(LessonType::class, $lesson, ['lesson_type' => $lesson->getType(), 'lesson_item' => $lessonItem]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            if ($lesson->getType() === Lesson::TYPE_TEXT) {
                $this->handleTextLessonType($form, $lesson, $lessonItem);
            } else if ($lesson->getType() === Lesson::TYPE_FILE) {
                try {
                    $this->handleFileLessonType($form, $lesson->getCourse(), $lesson, $lessonItem);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.file.store', [], 'message'));
                    return $this->redirectToRoute('lesson_edit', ['lesson' => $lesson->getId()]);
                }
            } else if ($lesson->getType() === Lesson::TYPE_VIDEO) {
                try {
                    $this->handleVideoLessonType($form, $lesson, $lessonItem);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.video.store', [], 'message'));
                    return $this->redirectToRoute('lesson_edit', ['lesson' => $lesson->getId()]);
                }
            }

            $this->addFlash('success', $this->translator->trans('success.lesson.edit', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
            $this->em->refresh($lesson);
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'lessonItem' => $lessonItem,
            'form' => $form->createView()
        ]);
    }

    #[Route("/reorder", name: "reorder", methods: ["POST"])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reorder(Request $request, LessonRepository $lessonRepository): Response
    {
        $data = json_decode($request->getContent());
        if ($data !== null && isset($data->reorders) && !empty($data->reorders)) {
            foreach ($data->reorders as $reorder) {
                $lesson = $lessonRepository->find($reorder->id);
                $lesson->setPosition(intval($reorder->position));
            }
            $this->em->flush();
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'fail']);
    }

    private function handleTextLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson, ?LessonItemText $lessonItemText = null): void
    {
        $persist = false;
        if ($lessonItemText === null) {
            $lessonItemText = new LessonItemText();
            $persist = true;
        }
        $lessonItemText->setLesson($lesson);
        $lessonItemText->setText($form->get('text')->getData());
        if ($persist) $this->em->persist($lessonItemText);
        $this->em->flush();
        if ($persist) $this->processTextLessonItemTranslation($form, $lessonItemText);
    }

    private function handleFileLessonType(\Symfony\Component\Form\FormInterface $form, Course $course, Lesson $lesson, ?LessonItemFile $lessonItemFile = null): void
    {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();
        $uploadDir = $this->getParameter('dir.lesson_file');
        if ($lessonItemFile !== null) $this->removeFile($uploadDir . '/' . $lessonItemFile->getFilename());
        $filenamePrefix = sprintf('lesson-file-%d-', $course->getId());
        $filename = $this->storeFile($file, $uploadDir, $filenamePrefix);

        $persist = false;
        if ($lessonItemFile === null) {
            $lessonItemFile = new LessonItemFile();
            $persist = true;
        }
        $lessonItemFile->setLesson($lesson);
        $lessonItemFile->setFilename($filename);
        if ($persist) $this->em->persist($lessonItemFile);
        $this->em->flush();
    }

    private function handleVideoLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson, ?LessonItemEmbeddedVideo $lessonItemEmbeddedVideo = null): void
    {
        $persist = false;
        if ($lessonItemEmbeddedVideo === null) {
            $lessonItemEmbeddedVideo = new LessonItemEmbeddedVideo();
            $persist = true;
        }
        $lessonItemEmbeddedVideo->setLesson($lesson);
        $lessonItemEmbeddedVideo->setVideoUrl($form->get('video')->getData());
        if ($persist) $this->em->persist($lessonItemEmbeddedVideo);
        $this->em->flush();
    }

    private function processLessonTranslation(Lesson $lesson, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $nameAlt = $form->get('nameAlt')->getData();
        if ($nameAlt !== null && trim($nameAlt) !== '') {
            $translationRepository->translate($lesson, 'name', $localeAlt, trim($nameAlt));
            $translated = true;
        }

        $descriptionAlt = $form->get('descriptionAlt')->getData();
        if ($descriptionAlt !== null && trim($descriptionAlt) !== '') {
            $translationRepository->translate($lesson, 'description', $localeAlt, trim($descriptionAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function processTextLessonItemTranslation(\Symfony\Component\Form\FormInterface $form, ?LessonItemText $lessonItemText): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $textAlt = $form->get('textAlt')->getData();
        if ($textAlt !== null && trim($textAlt) !== '') {
            $translationRepository->translate($lessonItemText, 'text', $localeAlt, $textAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}