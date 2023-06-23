<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\File;
use App\Entity\Instructor;
use App\Entity\LessonItemFile;
use App\Entity\PracticalSubmodule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/file-fetch", name: "file_fetch_")]
class FileFetchController extends AbstractController
{
    #[Route("/course-image/{course}", name: 'course_image')]
    public function courseImage(Course $course): Response
    {
        $dir = $this->getParameter('dir.course_image');
        $filepath = $dir . '/' . $course->getImage();
        return $this->file($filepath);
    }

    #[Route("/practical-submodule-image/{practicalSubmodule}", name: 'practical_submodule_image')]
    public function practicalSubmoduleImage(PracticalSubmodule $practicalSubmodule): Response
    {
        $dir = $this->getParameter('dir.practical_submodule_image');
        $filepath = $dir . '/' . $practicalSubmodule->getImage();
        return $this->file($filepath);
    }

    #[Route("/instructor-image/{instructor}", name: "instructor_image")]
    public function instructorImage(Instructor $instructor): Response
    {
        $dir = $this->getParameter('dir.instructor_image');
        $filepath = $dir . '/' . $instructor->getImage();
        return $this->file($filepath);
    }

    #[Route("/lesson-file/{lessonItemFile}", name: 'lesson_file')]
    public function lessonFile(LessonItemFile $lessonItemFile): Response
    {
        $dir = $this->getParameter('dir.lesson_file');
        $filepath = $dir . '/' . $lessonItemFile->getFilename();
        return $this->file($filepath);
    }

    #[Route("/uploaded-file/{file}", name: "uploaded_file")]
    public function uploadedFile(File $file): Response
    {
        return $this->file($file->getPath(), $file->getOriginalName());
    }
}