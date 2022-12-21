<?php

namespace App\Entity;

use App\Repository\EduLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EduLogRepository::class)]
class EduLog
{
    public const ACTION_COURSE_ENROLLMENT = 'course_enrollment';
    public const ACTION_COURSE_LESSONS_VIEW = 'course_lessons_view';
    public const ACTION_LESSON_VIEW = 'lesson_view';
    public const ACTION_LESSON_COMPLETION_UPDATE = 'lesson_completion_update';
    public const ACTION_LESSON_QUIZ_START = 'lesson_quiz_start';
    public const ACTION_LESSON_QUIZ_FINISH = 'lesson_quiz_finish';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(length: 255)]
    private ?string $ipAddress = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAt(): ?\DateTimeImmutable
    {
        return $this->at;
    }

    public function setAt(\DateTimeImmutable $at): self
    {
        $this->at = $at;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }
}
