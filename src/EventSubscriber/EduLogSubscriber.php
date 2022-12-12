<?php

namespace App\EventSubscriber;

use App\Controller\CourseController;
use App\Controller\LessonController;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\User;
use App\Service\EduLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Security;

class EduLogSubscriber implements EventSubscriberInterface
{
    private ?Security $security = null;
    private ?EntityManagerInterface $em = null;
    private ?EduLogService $eduLogService = null;

    public function __construct(Security $security, EntityManagerInterface $em, EduLogService $eduLogService)
    {
        $this->security = $security;
        $this->em = $em;
        $this->eduLogService = $eduLogService;
    }

    public static function getSubscribedEvents()
    {
        return [ControllerEvent::class => 'handleEduLogging'];
    }

    public function handleEduLogging(ControllerEvent $event)
    {
        if ($this->security->getUser() === null) return;

        $isUser = $this->security->isGranted('ROLE_USER')
            && !$this->security->isGranted('ROLE_MODERATOR')
            && !$this->security->isGranted('ROLE_ADMIN');

        if ($isUser) {
            $this->handleUserEduLogging($event->getRequest());
        } else {
            $this->handleModeratorEduLogging($event->getRequest());
        }
    }

    private function handleUserEduLogging(Request $request)
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $route = $request->attributes->get('_route');
        $routeParams = $request->attributes->get('_route_params');
        $ipAddress = $request->getClientIp();

        if ($route === 'course_enroll' && !empty($routeParams) && key_exists('course', $routeParams)) {
            $course = $this->em->getRepository(Course::class)->find($routeParams['course']);
            $this->eduLogService->logCourseEnrollment($course, $user, $ipAddress);
        }
        else if ($route === 'lesson_course' && !empty($routeParams) && key_exists('course', $routeParams)) {
            $course = $this->em->getRepository(Course::class)->find($routeParams['course']);
            $this->eduLogService->logCourseLessonsView($course, $user, $ipAddress);
        }
        else if ($route === 'lesson_show' && !empty($routeParams) && key_exists('lesson', $routeParams)) {
            $lesson = $this->em->getRepository(Lesson::class)->find($routeParams['lesson']);
            $this->eduLogService->logLessonView($lesson, $user, $ipAddress);
        }
        else if ($route === 'lesson_toggle_completed' && !empty($routeParams) && key_exists('lesson', $routeParams)) {
            $lesson = $this->em->getRepository(Lesson::class)->find($routeParams['lesson']);
            $this->eduLogService->logLessonCompletionUpdate($lesson, $user, $ipAddress);
        }
    }

    private function handleModeratorEduLogging(mixed $route)
    {
        // At this moment in time, there are no plans in logging moderator's or admin's activities in the practice module.
    }
}