<?php

namespace App\Entity;

use App\Repository\PracticalSubmodulePageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PracticalSubmodulePageRepository::class)]
class PracticalSubmodulePage extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmodulePages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmodulePage', targetEntity: PracticalSubmoduleQuestion::class)]
    private Collection $practicalSubmoduleQuestions;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $position = null;

    public function __construct()
    {
        $this->practicalSubmoduleQuestions = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        foreach ($this->practicalSubmoduleQuestions as $practicalSubmoduleQuestion) {
            if ($practicalSubmoduleQuestion->getPracticalSubmodule()->getId() !== $this->practicalSubmodule->getId()) {
                $context->buildViolation('error.practicalSubmodulePage.submoduleMismatch')->atPath('practicalSubmoduleQuestions')->addViolation();
                break;
            }
        }
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
     * @return Collection<int, PracticalSubmoduleQuestion>
     */
    public function getPracticalSubmoduleQuestions(): Collection
    {
        return $this->practicalSubmoduleQuestions;
    }

    public function addPracticalSubmoduleQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        if (!$this->practicalSubmoduleQuestions->contains($practicalSubmoduleQuestion)) {
            $this->practicalSubmoduleQuestions->add($practicalSubmoduleQuestion);
            $practicalSubmoduleQuestion->setPracticalSubmodulePage($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        if ($this->practicalSubmoduleQuestions->removeElement($practicalSubmoduleQuestion)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleQuestion->getPracticalSubmodulePage() === $this) {
                $practicalSubmoduleQuestion->setPracticalSubmodulePage(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function removeItselfFromPracticalSubmoduleQuestions(): void
    {
        /** @var PracticalSubmoduleQuestion $practicalSubmoduleQuestion */
        foreach ($this->practicalSubmoduleQuestions as $practicalSubmoduleQuestion) {
            $practicalSubmoduleQuestion->setPracticalSubmodulePage(null);
        }
    }
}
