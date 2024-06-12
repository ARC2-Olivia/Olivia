<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileRepository::class)]
class File
{
    public const TYPE_FILE = 'file';
    public const TYPE_VIDEO = 'video';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $path = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $originalName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $displayText = null;

    #[ORM\Column(nullable: true)]
    private ?bool $seminar = null;

    #[ORM\Column(nullable: true)]
    private ?bool $presentation = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    public function __toString(): string
    {
        if (null !== $this->displayText) {
            return $this->displayText;
        }
        return $this->originalName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        if (null === $this->type) {
            return self::TYPE_FILE;
        }
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getDisplayText(): ?string
    {
        return $this->displayText;
    }

    public function setDisplayText(?string $displayText): self
    {
        $this->displayText = $displayText;

        return $this;
    }

    public function isSeminar(): ?bool
    {
        if (null === $this->seminar) {
            return false;
        }
        return $this->seminar;
    }

    public function setSeminar(?bool $seminar): self
    {
        $this->seminar = $seminar;

        return $this;
    }

    public function isPresentation(): ?bool
    {
        if (null === $this->presentation) {
            return false;
        }
        return $this->presentation;
    }

    public function setPresentation(?bool $presentation): self
    {
        $this->presentation = $presentation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }
}
