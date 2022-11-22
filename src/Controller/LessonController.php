<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Entity\Lesson;
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

    #[Route("/course/{course}/new/{lessonType}", name: "new")]
    #[IsGranted("ROLE_MODERATOR")]
    public function newText(Course $course, string $lessonType, Request $request, EntityManagerInterface $em): Response
    {
        if (!in_array($lessonType, Lesson::getSupportedLessonTypes())) {
            $lessonType = Lesson::TYPE_TEXT;
        }
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson, ['lesson_type' => $lessonType]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository = $em->getRepository(Lesson::class);
            $lesson->setCourse($course);
            $lesson->setPosition($lessonRepository->nextPositionInCourse($course));
            $lessonRepository->save($lesson, true);

            if ($lesson->getType() === Lesson::TYPE_TEXT) {
                $lessonItemText = new LessonItemText();
                $lessonItemText->setLesson($lesson);
                $lessonItemText->setText($form->get('content')->getData());
                $em->persist($lessonItemText);
                $em->flush();
            }

            $this->addFlash('success', $this->translator->trans('success.lesson.new', ['%course%' => $course->getName()], 'message'));
            return $this->redirectToRoute('lesson_course', ['course' => $course->getId()]);
        }

        return $this->render('lesson/new.html.twig', ['course' => $course, 'form' => $form->createView()]);
    }
}