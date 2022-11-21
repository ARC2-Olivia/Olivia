<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Entity\User;
use App\Form\CourseInstructorType;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\InstructorRepository;
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

#[Route("/course", name: "course_")]
class CourseController extends AbstractController
{
    use BasicFileManagementTrait;

    private ?EntityManagerInterface $em = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
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
        return $this->render('course/overview.html.twig', ['course' => $course, 'activeCard' => 'overview']);
    }

    #[Route("/instructors/{course}", name: "instructors")]
    public function instructors(Course $course, Request $request, InstructorRepository $instructorRepository): Response
    {
        $selectableInstructors = $instructorRepository->findAllExcept($course->getInstructors());
        $form = $this->createForm(CourseInstructorType::class, null, ['instructors' => $selectableInstructors]);

        $form->handleRequest($request);
        if ($this->isGranted(User::ROLE_MODERATOR) && $form->isSubmitted() && $form->isValid()) {
            $instructor = $form->get('instructor')->getData();
            $course->addInstructor($instructor);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.instructor.add', ['%instructor%' => $instructor, '%course%' => $course->getName()], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/instructors.html.twig', ['course' => $course, 'activeCard' => 'instructors', 'form' => $form->createView(),]);
    }

    #[Route("/edit/{course}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(Course $course, Request $request): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($course);
            $this->em->flush();
            $image = $form->get('image')->getData();
            if ($image !== null) $this->removeCourseImage($course);
            $this->storeCourseImage($image, $course);
            $this->addFlash('success', $this->translator->trans('success.course.new', ['%courseName%' => $course->getName()], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/edit.html.twig', ['course' => $course, 'activeCard' => 'edit', 'form' => $form->createView()]);
    }

    #[Route("/delete/{course}", name: "delete")]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(Course $course, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('course.delete', $csrfToken)) {
            $courseName = $course->getName();
            $this->removeCourseImage($course);
            $this->em->remove($course);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.course.delete', ['%course%' => $courseName], 'message'));
            return $this->redirectToRoute('course_index');
        }

        return $this->redirectToRoute('course_edit', ['course' => $course->getId()]);
    }

    #[Route("/instructors/{course}/remove/{instructor}", name: "instructor_remove")]
    #[IsGranted('ROLE_MODERATOR')]
    public function removeInstructor(Course $course, Instructor $instructor, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('course.instructor.remove', $csrfToken)) {
            if ($course->getInstructors()->contains($instructor)) {
                $course->removeInstructor($instructor);
                $this->em->flush();
                $this->addFlash('warning', $this->translator->trans('warning.instructor.remove', ['%instructor%' => $instructor, '%course%' => $course->getName()], 'message'));
            } else {
                $this->addFlash('warning', $this->translator->trans('error.instructor.remove', ['%instructor%' => $instructor, '%course%' => $course->getName()], 'message'));
            }
        }
        return $this->redirectToRoute('course_instructors', ['course' => $course->getId()]);
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