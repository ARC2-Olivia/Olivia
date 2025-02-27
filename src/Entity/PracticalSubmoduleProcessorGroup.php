<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorGroupRepository::class)]
class PracticalSubmoduleProcessorGroup extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleProcessorGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmoduleProcessorGroup', targetEntity: PracticalSubmoduleProcessor::class)]
    private Collection $practicalSubmoduleProcessors;

    #[ORM\Column(type: Types::TEXT)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $position = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        foreach ($this->practicalSubmoduleProcessors as $practicalSubmoduleProcessor) {
            if ($practicalSubmoduleProcessor->getPracticalSubmodule()->getId() !== $this->practicalSubmodule->getId()) {
                $context->buildViolation('error.practicalSubmoduleProcessorGroup.submoduleMismatch')->atPath('practicalSubmoduleProcessors')->addViolation();
                break;
            }
        }
    }

    public function __construct()
    {
        $this->practicalSubmoduleProcessors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function removeItselfFromPracticalSubmoduleProcessors(): void
    {
        /** @var PracticalSubmoduleProcessor $processor */
        foreach ($this->practicalSubmoduleProcessors as $processor) {
            $processor->setPracticalSubmoduleProcessorGroup(null);
        }
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
    public function getPracticalSubmoduleProcessors(): Collection
    {
        return $this->practicalSubmoduleProcessors;
    }

    public function addPracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if (!$this->practicalSubmoduleProcessors->contains($practicalSubmoduleProcessor)) {
            $this->practicalSubmoduleProcessors->add($practicalSubmoduleProcessor);
            $practicalSubmoduleProcessor->setPracticalSubmoduleProcessorGroup($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if ($this->practicalSubmoduleProcessors->removeElement($practicalSubmoduleProcessor)) {
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
