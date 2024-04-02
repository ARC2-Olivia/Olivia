<?php

namespace App\Controller;

use App\Entity\File;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/file", name: "file_", requirements: ["_locale" => "%locale.supported%"])]
class FileController extends BaseController
{
    #[Route("/", name: "index")]
    public function index()
    {
        $files = $this->em->getRepository(File::class)->findAll();
        return $this->render('file/index.html.twig', ['files' => $files]);
    }
}