<?php

namespace App\Entity;

use App\Repository\EvaluationQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationQuestionRepository::class)]
class EvaluationQuestion
{
    public const TYPE_NO_EVALUATE = 'no_evaluate';
    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_WEIGHTED = 'weighted';
    public const TYPE_NUMERICAL_INPUT = 'numerical_input';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evaluation $evaluation = null;

    #[ORM\Column(length: 63)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $questionText = null;

    #[ORM\OneToMany(mappedBy: 'evaluationQuestion', targetEntity: EvaluationQuestionAnswer::class, orphanRemoval: true)]
    private Collection $evaluationQuestionAnswers;

    public function __construct()
    {
        $this->evaluationQuestionAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): self
    {
        $this->evaluation = $evaluation;

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

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): self
    {
        $this->questionText = $questionText;

        return $this;
    }

    /**
     * @return Collection<int, EvaluationQuestionAnswer>
     */
    public function getEvaluationQuestionAnswers(): Collection
    {
        return $this->evaluationQuestionAnswers;
    }

    public function addEvaluationQuestionAnswer(EvaluationQuestionAnswer $evaluationQuestionAnswer): self
    {
        if (!$this->evaluationQuestionAnswers->contains($evaluationQuestionAnswer)) {
            $this->evaluationQuestionAnswers->add($evaluationQuestionAnswer);
            $evaluationQuestionAnswer->setEvaluationQuestion($this);
        }

        return $this;
    }

    public function removeEvaluationQuestionAnswer(EvaluationQuestionAnswer $evaluationQuestionAnswer): self
    {
        if ($this->evaluationQuestionAnswers->removeElement($evaluationQuestionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($evaluationQuestionAnswer->getEvaluationQuestion() === $this) {
                $evaluationQuestionAnswer->setEvaluationQuestion(null);
            }
        }

        return $this;
    }
}
