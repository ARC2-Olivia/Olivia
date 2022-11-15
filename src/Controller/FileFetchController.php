<?php

namespace App\Controller;

use App\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/file-fetch", name: "file_fetch_")]
class FileFetchController extends AbstractController
{
    #[Route("/course-image/{course}", name: 'course_image')]
    public function courseImage(Course $course)
    {
        $dir = $this->getParameter('dir.course_image');
        $filepath = $dir . '/' . $course->getImage();
        return $this->file($filepath);
    }
}