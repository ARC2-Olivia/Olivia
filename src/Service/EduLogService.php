<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\EduLog;
use App\Entity\Lesson;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EduLogService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function logCourseEnrollment(Course $course, User $user, string $ipAddress): void
    {
        $eduLog = $this->prepareEduLog($user, $ipAddress)
            ->setCourse($course)
            ->setAction(EduLog::ACTION_COURSE_ENROLLMENT)
        ;
        $this->save($eduLog);
    }

    public function logCourseLessonsView(Course $course, User $user, string $ipAddress): void
    {
        $eduLog = $this->prepareEduLog($user, $ipAddress)
            ->setCourse($course)
            ->setAction(EduLog::ACTION_COURSE_LESSONS_VIEW)
        ;
        $this->save($eduLog);
    }

    public function logLessonView(Lesson $lesson, User $user, string $ipAddress): void
    {
        $eduLog = $this->prepareEduLog($user, $ipAddress)
            ->setCourse($lesson->getCourse())
            ->setLesson($lesson)
            ->setAction(EduLog::ACTION_LESSON_VIEW)
        ;
        $this->save($eduLog);
    }

    public function logLessonCompletionUpdate(Lesson $lesson, User $user, string $ipAddress): void
    {
        $eduLog = $this->prepareEduLog($user, $ipAddress)
            ->setCourse($lesson->getCourse())
            ->setLesson($lesson)
            ->setAction(EduLog::ACTION_LESSON_COMPLETION_UPDATE)
        ;
        $this->save($eduLog);
    }

    private function prepareEduLog(User $user, string $ipAddress): EduLog
    {
        return (new EduLog())
            ->setAt(new \DateTimeImmutable())
            ->setUser($user)
            ->setIpAddress($ipAddress)
        ;
    }

    private function save(EduLog $eduLog) {
        $this->em->persist($eduLog);
        $this->em->flush();
    }
}