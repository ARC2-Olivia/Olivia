<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/course", name: "course_")]
class CourseController extends AbstractController
{
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

            try {
                /** @var UploadedFile $image */
                $image = $form->get('image')->getData();
                if ($image !== null) {
                    $uploadDir = $this->getParameter('dir.course_image');
                    $filename = $this->storeFile($image, $uploadDir, 'course-');
                    $course->setImage($filename);
                    $em->flush();
                }
            } catch (\Exception $ex) {
                $this->addFlash('warning', $translator->trans('warning.course.image.store', [], 'message'));
            }

            $this->addFlash('success', $translator->trans('success.course.new', ['%courseName%' => $course->getName()], 'message'));
            return $this->redirectToRoute('course_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('course/new.html.twig', ['form' => $form->createView()]);
    }

    private function storeFile(UploadedFile $file, string $dir, string $filenamePrefix = null): string
    {
        $filename = uniqid() . '.' . $file->guessClientExtension();
        if ($filenamePrefix) $filename = $filenamePrefix . $filename;
        $file->move($dir, $filename);
        return $filename;
    }
}