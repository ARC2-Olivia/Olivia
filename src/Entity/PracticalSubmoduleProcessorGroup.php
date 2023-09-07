<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorGroupRepository::class)]
class PracticalSubmoduleProcessorGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleProcessorGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmoduleProcessorGroup', targetEntity: PracticalSubmoduleProcessor::class)]
    private Collection $practicalSubmoduleProcessor;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $title = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $position = null;

    public function __construct()
    {
        $this->practicalSubmoduleProcessor = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPracticalSubmodule(): ?PracticalSubmodule
    {
        return $this->practicalSubmodule;
    }

    public function setPracticalSubmodule(?PracticalSubmodule $practicalSubmodule): self
    {
        $this->practicalSubmodule = $practicalSubmodule;

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleProcessor>
     */
    public function getPracticalSubmoduleProcessor(): Collection
    {
        return $this->practicalSubmoduleProcessor;
    }

    public function addPracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if (!$this->practicalSubmoduleProcessor->contains($practicalSubmoduleProcessor)) {
            $this->practicalSubmoduleProcessor->add($practicalSubmoduleProcessor);
            $practicalSubmoduleProcessor->setPracticalSubmoduleProcessorGroup($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if ($this->practicalSubmoduleProcessor->removeElement($practicalSubmoduleProcessor)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleProcessor->getPracticalSubmoduleProcessorGroup() === $this) {
                $practicalSubmoduleProcessor->setPracticalSubmoduleProcessorGroup(null);
            }
        }

        return $this;
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
