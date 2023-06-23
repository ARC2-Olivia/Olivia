<?php

namespace App\Controller;


use App\Entity\DataRequest;
use App\Entity\File;
use App\Entity\Instructor;
use App\Entity\Note;
use App\Entity\User;
use App\Form\BasicFileUploadType;
use App\Form\InstructorType;
use App\Form\UserType;
use App\Repository\DataRequestRepository;
use App\Repository\FileRepository;
use App\Repository\InstructorRepository;
use App\Repository\UserRepository;
use App\Traits\BasicFileManagementTrait;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/admin", name: "admin_", requirements: ["_locale" => "%locale.supported%"])]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends BaseController
{
    use BasicFileManagementTrait;

    #[Route("/", name: "index")]
    public function index()
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route("/user", name: "user_index")]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/user/index.html.twig', ['users' => $users]);
    }

    #[Route("/user/edit/{user}", name: "user_edit")]
    public function editUser(User $user, Request $request): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.user.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/user/edit.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/instructor", name: "instructor_index")]
    public function instructors(InstructorRepository $instructorRepository): Response
    {
        $instructors = $instructorRepository->findAll();
        return $this->render('admin/instructor/index.html.twig', ['instructors' => $instructors]);
    }

    #[Route("/instructor/new", name: "instructor_new")]
    public function newInstructor(Request $request): Response
    {
        $instructor = new Instructor();
        $instructor->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(InstructorType::class, $instructor, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($instructor);
            $this->em->flush();
            $image = $form->get('image')->getData();
            $this->storeInstructorImage($image, $instructor);
            $this->processInstructorTranslation($instructor, $form);
            $this->addFlash('success', $this->translator->trans('success.instructor.new', [], 'message'));
            return $this->redirectToRoute('admin_instructor_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/instructor/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/instructor/edit/{instructor}", name: "instructor_edit")]
    public function editInstructor(Instructor $instructor, Request $request): Response
    {
        $form = $this->createForm(InstructorType::class, $instructor);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($instructor);
            $this->em->flush();
            $image = $form->get('image')->getData();
            if ($image !== null) $this->removeInstructorImage($instructor);
            $this->storeInstructorImage($image, $instructor);
            $this->addFlash('success', $this->translator->trans('success.instructor.new', [], 'message'));
            return $this->redirectToRoute('admin_instructor_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/instructor/edit.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/instructor/delete/{instructor}", name: "instructor_delete")]
    public function deleteInstructor(Instructor $instructor, Request $request): Response
    {
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('admin.instructor.delete', $csrfToken)) {
            $this->removeInstructorImage($instructor);
            $this->em->remove($instructor);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.instructor.delete', [], 'message'));
        }
        return $this->redirectToRoute('admin_instructor_index');
    }

    #[Route("/data-request", name: "data_request_index")]
    public function dataRequests(DataRequestRepository $dataRequestRepository): Response
    {
        /** @var DataRequest $dataAccessRequests */ $dataAccessRequests = $dataRequestRepository->findUnresolvedByType(DataRequest::TYPE_ACCESS);
        /** @var DataRequest $dataDeletionRequests */ $dataDeletionRequests = $dataRequestRepository->findUnresolvedByType(DataRequest::TYPE_DELETE);
        /** @var DataRequest $resolvedDataRequests */ $resolvedDataRequests = $dataRequestRepository->findResolved();

        return $this->render('admin/dataRequest/index.html.twig', [
            'dataAccessRequests' => $dataAccessRequests,
            'dataDeletionRequests' => $dataDeletionRequests,
            'resolvedDataRequests' => $resolvedDataRequests
        ]);
    }

    #[Route("/file", name: "file_index")]
    public function files(FileRepository $fileRepository): Response
    {
        $files = $fileRepository->findAll();
        return $this->render('admin/file/index.html.twig', ['files' => $files]);
    }

    #[Route("/file/new", name: "file_new")]
    public function newFile(Request $request): Response
    {
        $form = $this->createForm(BasicFileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $upload */
            $upload = $form->get('file')->getData();

            if (null !== $upload) {
                $file = (new File())->setCreatedAt(new \DateTimeImmutable())->setOriginalName($upload->getClientOriginalName());
                $directory = $this->getParameter('dir.file_repository');
                $filename = uniqid('file-', true);
                $upload->move($directory, $filename);
                $file->setPath($directory.'/'.$filename);

                $this->em->persist($file);
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('success.file.new', [], 'message'));
                return $this->redirectToRoute('admin_file_index');
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/file/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/file/delete/{file}", name: "file_delete")]
    public function deleteFile(File $file, Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (null !== $csrfToken && $this->isCsrfTokenValid('file.delete', $csrfToken)) {
            $this->removeFile($file->getPath());
            $this->em->remove($file);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('warning.file.delete', [], 'message'));
        }

        return $this->redirectToRoute('admin_file_index');
    }

    #[Route("/file/replace/{file}", name: "file_replace")]
    public function replaceFile(File $file, Request $request): Response
    {
        $form = $this->createForm(BasicFileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $upload */
            $upload = $form->get('file')->getData();

            if (null !== $upload) {
                $originalName = $upload->getClientOriginalName();
                $directory = $this->getParameter('dir.file_repository');
                $filename = uniqid('file-', true);
                $upload->move($directory, $filename);

                $this->removeFile($file->getPath());
                $file->setOriginalName($originalName)->setPath($directory.'/'.$filename)->setModifiedAt(new \DateTimeImmutable());
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('success.file.edit', [], 'message'));
                return $this->redirectToRoute('admin_file_index');
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('admin/file/replace.html.twig', ['file' => $file, 'form' => $form->createView()]);
    }

    private function storeInstructorImage(?UploadedFile $image, Instructor $instructor)
    {
        try {
            if ($image !== null) {
                $uploadDir = $this->getParameter('dir.instructor_image');
                $filenamePrefix = sprintf('instructor-%d-', $instructor->getId());
                $filename = $this->storeFile($image, $uploadDir, $filenamePrefix);
                $instructor->setImage($filename);
                $this->em->flush();
            }
        } catch (\Exception $ex) {
            $this->addFlash('warning', $this->translator->trans('warning.instructor.image.store', [], 'message'));
        }
    }

    private function removeInstructorImage(Instructor $instructor)
    {
        if ($instructor->getImage() !== null) {
            $uploadDir = $this->getParameter('dir.instructor_image');
            $this->removeFile($uploadDir . '/' . $instructor->getImage());
            $instructor->setImage(null);
            $this->em->flush();
        }
    }

    private function processInstructorTranslation(Instructor $instructor, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $institutionAlt = $form->get('institutionAlt')->getData();
        if ($institutionAlt !== null && trim($institutionAlt) !== '') {
            $translationRepository->translate($instructor, 'institution', $localeAlt, $institutionAlt);
            $translated = true;
        }

        $biographyAlt = $form->get('biographyAlt')->getData();
        if ($biographyAlt !== null && trim($biographyAlt) !== '') {
            $translationRepository->translate($instructor, 'biography', $localeAlt, $biographyAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}