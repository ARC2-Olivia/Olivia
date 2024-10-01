<?php

namespace App\Entity;

use App\Repository\AllCoursesCompletedUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AllCoursesCompletedUserRepository::class)]
class AllCoursesCompletedUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'allCoursesCompletedUser', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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
}
