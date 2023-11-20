<?php

namespace App\Entity;

use App\Repository\TextsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: TextsRepository::class)]
class Texts extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $aboutUs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $aboutProject = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAboutUs(): ?string
    {
        return $this->aboutUs;
    }

    public function setAboutUs(?string $aboutUs): self
    {
        $this->aboutUs = $aboutUs;

        return $this;
    }

    public function getAboutProject(): ?string
    {
        return $this->aboutProject;
    }

    public function setAboutProject(?string $aboutProject): self
    {
        $this->aboutProject = $aboutProject;

        return $this;
    }
}
