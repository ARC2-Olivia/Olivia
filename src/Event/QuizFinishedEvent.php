<?php

namespace App\Event;

use App\Entity\Lesson;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class QuizFinishedEvent extends Event
{
    private ?User $user = null;
    private ?Lesson $lesson = null;

    public function __construct(User $user, Lesson $lesson)
    {
        $this->user = $user;
        $this->lesson = $lesson;
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

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;
        return $this;
    }
}