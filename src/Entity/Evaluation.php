<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
class Evaluation extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "error.evaluation.name")]
    #[Gedmo\Translatable]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "error.evaluation.description")]
    #[Gedmo\Translatable]
    private ?string $description = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    #[Gedmo\Translatable]
    private array $tags = [];

    #[ORM\OneToMany(mappedBy: 'evaluation', targetEntity: EvaluationQuestion::class, orphanRemoval: true)]
    private Collection $evaluationQuestions;

    #[ORM\OneToMany(mappedBy: 'evaluation', targetEntity: EvaluationEvaluator::class, orphanRemoval: true)]
    private Collection $evaluationEvaluators;

    public function __construct()
    {
        $this->evaluationQuestions = new ArrayCollection();
        $this->evaluationEvaluators = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return Collection<int, EvaluationQuestion>
     */
    public function getEvaluationQuestions(): Collection
    {
        return $this->evaluationQuestions;
    }

    public function addEvaluationQuestion(EvaluationQuestion $evaluationQuestion): self
    {
        if (!$this->evaluationQuestions->contains($evaluationQuestion)) {
            $this->evaluationQuestions->add($evaluationQuestion);
            $evaluationQuestion->setEvaluation($this);
        }

        return $this;
    }

    public function removeEvaluationQuestion(EvaluationQuestion $evaluationQuestion): self
    {
        if ($this->evaluationQuestions->removeElement($evaluationQuestion)) {
            // set the owning side to null (unless already changed)
            if ($evaluationQuestion->getEvaluation() === $this) {
                $evaluationQuestion->setEvaluation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EvaluationEvaluator>
     */
    public function getEvaluationEvaluators(): Collection
    {
        return $this->evaluationEvaluators;
    }

    public function addEvaluationEvaluator(EvaluationEvaluator $evaluationEvaluator): self
    {
        if (!$this->evaluationEvaluators->contains($evaluationEvaluator)) {
            $this->evaluationEvaluators->add($evaluationEvaluator);
            $evaluationEvaluator->setEvaluation($this);
        }

        return $this;
    }

    public function removeEvaluationEvaluator(EvaluationEvaluator $evaluationEvaluator): self
    {
        if ($this->evaluationEvaluators->removeElement($evaluationEvaluator)) {
            // set the owning side to null (unless already changed)
            if ($evaluationEvaluator->getEvaluation() === $this) {
                $evaluationEvaluator->setEvaluation(null);
            }
        }

        return $this;
    }
}
