<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonCompletion;
use App\Entity\User;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class EnrollmentService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function enroll(Course $course, User $user): void
    {
        $enrollment = new Enrollment();
        $enrollment->setCourse($course);
        $enrollment->setUser($user);
        $enrollment->setEnrolledAt(new \DateTimeImmutable());
        $this->em->persist($enrollment);
        $this->em->flush();
    }

    public function isEnrolled(Course $course, User $user): bool
    {
        return null !== $this->em->getRepository(Enrollment::class)->findOneBy(['course' => $course, 'user' => $user]);
    }

    public function isPassed(Course $course, User $user): bool
    {
        return null !== $this->em->getRepository(Enrollment::class)->findOneBy(['course' => $course, 'user' => $user, 'passed' => true]);
    }

    public function checkCoursePassingCondition(Course $course, User $user): bool
    {
        if (!$this->isEnrolled($course, $user)) {
            return false;
        }

        $lessons = $this->em->getRepository(Lesson::class)->findQuizLessonsForCourse($course);
        $lessonCompletionRepository = $this->em->getRepository(LessonCompletion::class);
        foreach ($lessons as $lesson) {
            $lessonCompletion = $lessonCompletionRepository->findOneBy(['lesson' => $lesson, 'user' => $user]);
            if (null !== $lessonCompletion && !$lessonCompletion->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    public function markAsPassed(Course $course, User $user): void
    {
        $enrollment = $this->em->getRepository(Enrollment::class)->findOneBy(['course' => $course, 'user' => $user]);
        if (null !== $enrollment) {
            $enrollment->setPassed(true);
            $this->em->flush();
        }
    }

    public function markAsNotPassed(Course $course, User $user): void
    {
        $enrollment = $this->em->getRepository(Enrollment::class)->findOneBy(['course' => $course, 'user' => $user]);
        if (null !== $enrollment) {
            $enrollment->setPassed(false);
            $this->em->flush();
        }
    }
}