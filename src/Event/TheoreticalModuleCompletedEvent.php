<?php

namespace App\Event;

use App\Entity\Lesson;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class TheoreticalModuleCompletedEvent extends Event
{
    private ?User $user = null;

    public function __construct(User $user)
    {
        $this->user = $user;
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
}