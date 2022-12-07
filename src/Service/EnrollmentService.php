<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Repository\EnrollmentRepository;

class EnrollmentService
{
    private ?EnrollmentRepository $enrollmentRepository = null;

    public function __construct(EnrollmentRepository $enrollmentRepository)
    {
        $this->enrollmentRepository = $enrollmentRepository;
    }

    public function enroll(Course $course, User $user): void
    {
        $enrollment = new Enrollment();
        $enrollment->setCourse($course);
        $enrollment->setUser($user);
        $enrollment->setEnrolledAt(new \DateTimeImmutable());
        $this->enrollmentRepository->save($enrollment, true);
    }

    public function isEnrolled(Course $course, User $user): bool
    {
        return $this->enrollmentRepository->findOneBy(['course' => $course, 'user' => $user]) !== null;
    }
}