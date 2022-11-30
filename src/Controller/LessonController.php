<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Entity\Lesson;
use App\Entity\LessonItemEmbeddedVideo;
use App\Entity\LessonItemFile;
use App\Entity\LessonItemText;
use App\Entity\User;
use App\Form\CourseInstructorType;
use App\Form\CourseType;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\InstructorRepository;
use App\Repository\LessonRepository;
use App\Traits\BasicFileManagementTrait;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $form = $this->createForm(LessonType::class, $lesson, ['lesson_type' => $lessonType]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository = $this->em->getRepository(Lesson::class);
            $lesson->setCourse($course);
            $lesson->setPosition($lessonRepository->nextPositionInCourse($course));
            $lessonRepository->save($lesson, true);

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
    public function show(Lesson $lesson, LessonRepository $lessonRepository, EntityManagerInterface $em): Response
    {
        switch ($lesson->getType()) {
            case Lesson::TYPE_TEXT: $lessonItem = $em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]); break;
            case Lesson::TYPE_FILE: $lessonItem = $em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]); break;
            case Lesson::TYPE_VIDEO: $lessonItem = $em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]); break;
            default: $lessonItem = null;
        }

        $previousLesson = $lessonRepository->findPreviousLesson($lesson);
        $nextLesson = $lessonRepository->findNextLesson($lesson);
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'lessonItem' => $lessonItem,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson
        ]);
    }

    private function handleTextLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson): void
    {
        $lessonItemText = new LessonItemText();
        $lessonItemText->setLesson($lesson);
        $lessonItemText->setText($form->get('content')->getData());
        $this->em->persist($lessonItemText);
        $this->em->flush();
    }

    private function handleFileLessonType(\Symfony\Component\Form\FormInterface $form, Course $course, Lesson $lesson): void
    {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();
        $uploadDir = $this->getParameter('dir.lesson_file');
        $filenamePrefix = sprintf('lesson-file-%d-', $course->getId());
        $filename = $this->storeFile($file, $uploadDir, $filenamePrefix);

        $lessonItemFile = new LessonItemFile();
        $lessonItemFile->setLesson($lesson);
        $lessonItemFile->setFilename($filename);
        $this->em->persist($lessonItemFile);
        $this->em->flush();
    }

    private function handleVideoLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson): void
    {
        $lessonItemEmbeddedVideo = new LessonItemEmbeddedVideo();
        $lessonItemEmbeddedVideo->setLesson($lesson);
        $lessonItemEmbeddedVideo->setVideoUrl($form->get('video')->getData());
        $this->em->persist($lessonItemEmbeddedVideo);
        $this->em->flush();
    }
}