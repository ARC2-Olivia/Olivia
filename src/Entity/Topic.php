<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\TopicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TopicRepository::class)]
#[ApiResource(normalizationContext: ['groups' => ['api']])]
#[Get]
#[GetCollection]
class Topic extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('api')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "error.topic.title")]
    #[Gedmo\Translatable]
    #[Groups('api')]
    private ?string $title = null;

    #[ORM\OneToMany(mappedBy: 'topic', targetEntity: Course::class)]
    #[Groups('api')]
    private Collection $theoreticalSubmodules;

    #[ORM\OneToMany(mappedBy: 'topic', targetEntity: PracticalSubmodule::class)]
    #[Groups('api')]
    private Collection $practicalSubmodules;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Groups('api')]
    private ?int $position = null;

    public function __construct()
    {
        $this->theoreticalSubmodules = new ArrayCollection();
        $this->practicalSubmodules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getTheoreticalSubmodules(): Collection
    {
        return $this->theoreticalSubmodules;
    }

    public function addTheoreticalSubmodule(Course $theoreticalSubmodule): self
    {
        if (!$this->theoreticalSubmodules->contains($theoreticalSubmodule)) {
            $this->theoreticalSubmodules->add($theoreticalSubmodule);
            $theoreticalSubmodule->setTopic($this);
        }

        return $this;
    }

    public function removeTheoreticalSubmodule(Course $theoreticalSubmodule): self
    {
        if ($this->theoreticalSubmodules->removeElement($theoreticalSubmodule)) {
            // set the owning side to null (unless already changed)
            if ($theoreticalSubmodule->getTopic() === $this) {
                $theoreticalSubmodule->setTopic(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmodule>
     */
    public function getPracticalSubmodules(): Collection
    {
        return $this->practicalSubmodules;
    }

    public function addPracticalSubmodule(PracticalSubmodule $practicalSubmodule): self
    {
        if (!$this->practicalSubmodules->contains($practicalSubmodule)) {
            $this->practicalSubmodules->add($practicalSubmodule);
            $practicalSubmodule->setTopic($this);
        }

        return $this;
    }

    public function removePracticalSubmodule(PracticalSubmodule $practicalSubmodule): self
    {
        if ($this->practicalSubmodules->removeElement($practicalSubmodule)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmodule->getTopic() === $this) {
                $practicalSubmodule->setTopic(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
