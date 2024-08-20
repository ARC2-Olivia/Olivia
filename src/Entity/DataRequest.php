<?php

namespace App\Entity;

use App\Repository\DataRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DataRequestRepository::class)]
class DataRequest
{
    const TYPE_ACCESS = 'access';
    const TYPE_DELETE = 'delete';
    const TYPE_DELETE_SPECIFIC = 'delete-specific';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 16)]
    private ?string $type = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $requestedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $deletedUserEmail = null;

    #[ORM\Column(nullable: true)]
    private array $specifics = [];

    public static function createAccessRequest(User $user): self
    {
        return (new DataRequest())->setUser($user)->setType(self::TYPE_ACCESS)->setRequestedAt(new \DateTimeImmutable());
    }

    public static function createDeleteRequest(User $user): self
    {
        return (new DataRequest())->setUser($user)->setType(self::TYPE_DELETE)->setRequestedAt(new \DateTimeImmutable());
    }

    public static function createDeleteSpecificRequest(User $user, array $specifics): self
    {
        return (new DataRequest())->setUser($user)->setType(self::TYPE_DELETE_SPECIFIC)->setRequestedAt(new \DateTimeImmutable())->setSpecifics($specifics);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRequestedAt(): ?\DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getDeletedUserEmail(): ?string
    {
        return $this->deletedUserEmail;
    }

    public function setDeletedUserEmail(?string $deletedUserEmail): self
    {
        $this->deletedUserEmail = $deletedUserEmail;

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        if (null !== $this->user) {
            return $this->user->getNameOrEmail();
        }
        return $this->deletedUserEmail;
    }

    public function getSpecifics(): ?array
    {
        return $this->specifics;
    }

    public function setSpecifics(?array $specifics): self
    {
        $this->specifics = $specifics;

        return $this;
    }
}
