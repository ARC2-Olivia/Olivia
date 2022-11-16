<?php

namespace App\Controller;


use App\Entity\Instructor;
use App\Entity\User;
use App\Form\InstructorType;
use App\Repository\InstructorRepository;
use App\Repository\UserRepository;
use App\Traits\BasicFileManagementTrait;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/admin", name: "admin_")]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    use BasicFileManagementTrait;

    #[Route("/", name: "index")]
    public function index()
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route("/users", name: "user_index")]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/user/index.html.twig', ['users' => $users]);
    }

    #[Route("/instructor", name: "instructor_index")]
    public function instructors(InstructorRepository $instructorRepository): Response
    {
        $instructors = $instructorRepository->findAll();
        return $this->render('admin/instructor/index.html.twig', ['instructors' => $instructors]);
    }

    #[Route("/instructor/new", name: "instructor_new")]
    public function newInstructor(Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $instructor = new Instructor();
        $form = $this->createForm(InstructorType::class, $instructor);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($instructor);
            $em->flush();
            $image = $form->get('image')->getData();
            $this->storeInstructorImage($image, $instructor, $em, $translator);
            $this->addFlash('success', $translator->trans('success.instructor.new', [], 'message'));
            return $this->redirectToRoute('admin_instructor_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/instructor/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/instructor/edit/{instructor}", name: "instructor_edit")]
    public function editInstructor(Instructor $instructor, Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(InstructorType::class, $instructor);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($instructor);
            $em->flush();
            $image = $form->get('image')->getData();
            if ($image !== null) $this->removeInstructorImage($instructor, $em);
            $this->storeInstructorImage($image, $instructor, $em, $translator);
            $this->addFlash('success', $translator->trans('success.instructor.new', [], 'message'));
            return $this->redirectToRoute('admin_instructor_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/instructor/edit.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/instructor/delete/{instructor}", name: "instructor_delete")]
    public function deleteInstructor(Instructor $instructor, Request $request, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('instructor.delete', $csrfToken)) {
            $this->removeInstructorImage($instructor, $em);
            $em->remove($instructor);
            $em->flush();
            $this->addFlash('warning', $translator->trans('warning.instructor.delete', [], 'message'));
        }
        return $this->redirectToRoute('admin_instructor_index');
    }

    private function storeInstructorImage(?UploadedFile $image, Instructor $instructor, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        try {
            if ($image !== null) {
                $uploadDir = $this->getParameter('dir.instructor_image');
                $filenamePrefix = sprintf('instructor-%d-', $instructor->getId());
                $filename = $this->storeFile($image, $uploadDir, $filenamePrefix);
                $instructor->setImage($filename);
                $em->flush();
            }
        } catch (\Exception $ex) {
            $this->addFlash('warning', $translator->trans('warning.instructor.image.store', [], 'message'));
        }
    }

    private function removeInstructorImage(Instructor $instructor, EntityManagerInterface $em)
    {
        if ($instructor->getImage() !== null) {
            $uploadDir = $this->getParameter('dir.instructor_image');
            $this->removeFile($uploadDir . '/' . $instructor->getImage());
            $instructor->setImage(null);
            $em->flush();
        }
    }
}