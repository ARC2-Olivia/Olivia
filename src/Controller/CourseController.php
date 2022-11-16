<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
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

    #[Route("/", name: "index")]
    public function index(CourseRepository $courseRepository): Response
    {
        /** @var Course[] $courses */
        $courses = $courseRepository->findAll();
        return $this->render('course/index.html.twig', ['courses' => $courses]);
    }

    #[Route("/new", name: "new")]
    public function new(Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($course);
            $em->flush();
            $image = $form->get('image')->getData();
            $this->storeCourseImage($image, $course, $em, $translator);
            $this->addFlash('success', $translator->trans('success.course.new', ['%courseName%' => $course->getName()], 'message'));
            return $this->redirectToRoute('course_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $translator->trans($error->getMessage(), [], 'message'));
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
    public function instructors(Course $course): Response
    {
        return $this->render('course/instructors.html.twig', ['course' => $course, 'activeCard' => 'instructors']);
    }

    #[Route("/edit/{course}", name: "edit")]
    public function edit(Course $course, Request $request, TranslatorInterface $translator, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($course);
            $em->flush();
            $image = $form->get('image')->getData();
            if ($image !== null) $this->removeCourseImage($course, $em);
            $this->storeCourseImage($image, $course, $em, $translator);
            $this->addFlash('success', $translator->trans('success.course.new', ['%courseName%' => $course->getName()], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/edit.html.twig', ['course' => $course, 'activeCard' => 'edit', 'form' => $form->createView()]);
    }

    private function storeCourseImage(?UploadedFile $image, Course $course, EntityManagerInterface $em, TranslatorInterface $translator): void
    {
        try {
            if ($image !== null) {
                $uploadDir = $this->getParameter('dir.course_image');
                $filenamePrefix = sprintf('course-%d-', $course->getId());
                $filename = $this->storeFile($image, $uploadDir, $filenamePrefix);
                $course->setImage($filename);
                $em->flush();
            }
        } catch (\Exception $ex) {
            $this->addFlash('warning', $translator->trans('warning.course.image.store', [], 'message'));
        }
    }

    private function removeCourseImage(Course $course, EntityManagerInterface $em): void
    {
        if ($course->getImage() !== null) {
            $uploadDir = $this->getParameter('dir.course_image');
            $this->removeFile($uploadDir . '/' . $course->getImage());
            $course->setImage(null);
            $em->flush();;
        }
    }
}