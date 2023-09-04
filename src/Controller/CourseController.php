<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Entity\Lesson;
use App\Entity\LessonItemFile;
use App\Entity\LessonItemText;
use App\Entity\PracticalSubmodule;
use App\Entity\User;
use App\Form\CourseInstructorType;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\InstructorRepository;
use App\Service\EnrollmentService;
use App\Service\NavigationService;
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

#[Route("/{_locale}/course", name: "course_", requirements: ["_locale" => "%locale.supported%"])]
class CourseController extends BaseController
{
    use BasicFileManagementTrait;

    private ?NavigationService $navigationService = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, NavigationService $navigationService)
    {
        parent::__construct($em, $translator);
        $this->navigationService = $navigationService;
    }

    #[Route("/", name: "index")]
    public function index(CourseRepository $courseRepository): Response
    {
        $courses = $courseRepository->findAll();
        return $this->render('course/index.html.twig', ['courses' => $courses]);
    }

    #[Route("/new", name: "new")]
    public function new(Request $request): Response
    {
        $course = new Course();
        $course->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(CourseType::class, $course, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($course);
            foreach ($course->getPracticalSubmodules() as $ps) $ps->addCourse($course);
            $this->em->flush();
            $image = $form->get('image')->getData();
            $this->storeCourseImage($image, $course);
            $this->processCourseTranslation($course, $form);
            $this->addFlash('success', $this->translator->trans('success.course.new', ['%courseName%' => $course->getName()], 'message'));
            return $this->redirectToRoute('course_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/overview/{course}", name: "overview")]
    public function overview(Course $course, Request $request): Response
    {
        return $this->render('course/overview.html.twig', [
            'course' => $course,
            'navigation' => $this->navigationService->forCourse($course, NavigationService::COURSE_OVERVIEW)
        ]);
    }

    #[Route("/edit/{course}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(Course $course, Request $request): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($this->em->getRepository(PracticalSubmodule::class)->findContainingCourse($course) as $ps) $ps->removeCourse($course);
            foreach ($course->getPracticalSubmodules() as $ps) $ps->addCourse($course);
            $this->em->flush();
            $image = $form->get('image')->getData();
            if ($image !== null) $this->removeCourseImage($course);
            $this->storeCourseImage($image, $course);
            $this->addFlash('success', $this->translator->trans('success.course.edit', ['%courseName%' => $course->getName()], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
            'navigation' => $this->navigationService->forCourse($course, NavigationService::COURSE_OVERVIEW)
        ]);
    }

    #[Route("/delete/{course}", name: "delete")]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(Course $course, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('course.delete', $csrfToken)) {
            $courseName = $course->getName();
            $this->removeCourseImage($course);

            $lessonRepository = $this->em->getRepository(Lesson::class);
            $lessonItemTextRepository = $this->em->getRepository(LessonItemText::class);
            $lessonItemFileRepository = $this->em->getRepository(LessonItemFile::class);

            foreach ($lessonRepository->findBy(['course' => $course]) as $lesson) {
                if ($lesson->getType() === Lesson::TYPE_TEXT) {
                    $this->em->remove($lessonItemTextRepository->findOneBy(['lesson' => $lesson]));
                } elseif ($lesson->getType() === Lesson::TYPE_FILE || $lesson->getType() === Lesson::TYPE_VIDEO) {
                    $this->em->remove($lessonItemFileRepository->findOneBy(['lesson' => $lesson]));
                }
                $this->em->remove($lesson);
            }

            $this->em->remove($course);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.course.delete', ['%course%' => $courseName], 'message'));
            return $this->redirectToRoute('course_index');
        }

        return $this->redirectToRoute('course_edit', ['course' => $course->getId()]);
    }

    #[Route("/enroll/{course}", name: "enroll")]
    #[IsGranted("enroll", subject: "course")]
    public function enroll(Course $course, EnrollmentService $enrollmentService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $enrollmentService->enroll($course, $user);
        return $this->redirectToRoute('lesson_course', ['course' => $course->getId()]);
    }

    #[Route("/certificate/{course}", name: "certificate")]
    public function certificate(Course $course): Response
    {
        return $this->render('course/certificate.html.twig', [
            'course' => $course,
            'navigation' => $this->navigationService->forCourse($course, NavigationService::COURSE_CERTIFICATE)
        ]);
    }

    private function storeCourseImage(?UploadedFile $image, Course $course): void
    {
        try {
            if ($image !== null) {
                $uploadDir = $this->getParameter('dir.course_image');
                $filenamePrefix = sprintf('course-%d-', $course->getId());
                $filename = $this->storeFile($image, $uploadDir, $filenamePrefix);
                $course->setImage($filename);
                $this->em->flush();
            }
        } catch (\Exception $ex) {
            $this->addFlash('warning', $this->translator->trans('warning.course.image.store', [], 'message'));
        }
    }

    private function removeCourseImage(Course $course): void
    {
        if ($course->getImage() !== null) {
            $uploadDir = $this->getParameter('dir.course_image');
            $this->removeFile($uploadDir . '/' . $course->getImage());
            $course->setImage(null);
            $this->em->flush();;
        }
    }

    private function processCourseTranslation(Course $course, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $nameAlt = $form->get('nameAlt')->getData();
        if ($nameAlt !== null && trim($nameAlt) !== '') {
            $translationRepository->translate($course, 'name', $localeAlt, trim($nameAlt));
            $translated = true;
        }

        $descriptionAlt = $form->get('descriptionAlt')->getData();
        if ($descriptionAlt !== null && trim($descriptionAlt) !== '') {
            $translationRepository->translate($course, 'description', $localeAlt, trim($descriptionAlt));
            $translated = true;
        }

        $tagsAlt = $form->get('tagsAlt')->getData();
        if ($tagsAlt !== null && count($tagsAlt) > 0) {
            $translationRepository->translate($course, 'tags', $localeAlt, $tagsAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}