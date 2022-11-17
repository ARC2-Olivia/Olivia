<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Instructor;
use App\Form\CourseInstructorType;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\InstructorRepository;
use App\Traits\BasicFileManagementTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
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
        $form = $this->createForm(CourseType::class, $course);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($course);
            $this->em->flush();
            $image = $form->get('image')->getData();
            $this->storeCourseImage($image, $course);
            $this->addFlash('success', $this->translator->trans('success.course.new', ['%courseName%' => $course->getName()], 'message'));
            return $this->redirectToRoute('course_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->ranslator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/overview/{course}", name: "overview")]
    public function overview(Course $course): Response
    {
        return $this->render('course/overview.html.twig', ['course' => $course, 'activeCard' => 'overview']);
    }

    #[Route("/instructors/{course}", name: "instructors")]
    public function instructors(Course $course, Request $request, InstructorRepository $instructorRepository): Response
    {
        $selectableInstructors = $instructorRepository->findAllExcept($course->getInstructors());
        $form = $this->createForm(CourseInstructorType::class, null, ['instructors' => $selectableInstructors]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $instructor = $form->get('instructor')->getData();
            $course->addInstructor($instructor);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.instructor.add', ['%instructor%' => $instructor, '%course%' => $course->getName()], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/instructors.html.twig', ['course' => $course, 'activeCard' => 'instructors', 'form' => $form->createView()]);
    }

    #[Route("/edit/{course}", name: "edit")]
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
}