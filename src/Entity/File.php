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

    public const INCLUDE_IN_TOPIC_INDEX_DEFAULT = 'topic_index_default';
    public const INCLUDE_IN_TOPIC_INDEX_ALTERNATE = 'topic_index_alternate';

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

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $webinarOrder = null;

    #[ORM\Column(nullable: true)]
    private ?int $presentationOrder = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $includeIn = [];

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

    public function getWebinarOrder(): ?int
    {
        if (null === $this->webinarOrder) {
            return PHP_INT_MAX;
        }
        return $this->webinarOrder;
    }

    public function setWebinarOrder(?int $webinarOrder): self
    {
        $this->webinarOrder = $webinarOrder;

        return $this;
    }

    public function getPresentationOrder(): ?int
    {
        if (null === $this->presentationOrder) {
            return PHP_INT_MAX;
        }
        return $this->presentationOrder;
    }

    public function setPresentationOrder(?int $presentationOrder): self
    {
        $this->presentationOrder = $presentationOrder;

        return $this;
    }

    public function getIncludeIn(): array
    {
        if (null === $this->includeIn) {
            return [];
        }
        return $this->includeIn;
    }

    public function setIncludeIn(?array $includeIn): self
    {
        $this->includeIn = $includeIn ?? [];

        return $this;
    }
}
