<?php

namespace App\Entity;

use App\Repository\EvaluationEvaluatorSumAggregateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationEvaluatorSumAggregateRepository::class)]
class EvaluationEvaluatorSumAggregate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'evaluationEvaluatorSumAggregate', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationEvaluator $evaluationEvaluator = null;

    #[ORM\ManyToMany(targetEntity: EvaluationQuestion::class)]
    #[Assert\NotBlank(message: 'error.evaluationEvaluatorSumAggregate.evaluationQuestions')]
    private Collection $evaluationQuestions;

    #[ORM\Column]
    #[Assert\Type(type: 'numeric', message: 'error.evaluationEvaluatorSumAggregate.expectedValueRange.start')]
    private ?int $expectedValueRangeStart = null;

    #[ORM\Column]
    #[Assert\Type(type: 'numeric', message: 'error.evaluationEvaluatorSumAggregate.expectedValueRange.end')]
    private ?int $expectedValueRangeEnd = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.evaluationEvaluatorSumAggregate.resultText')]
    private ?string $resultText = null;

    public function __construct()
    {
        $this->evaluationQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluationEvaluator(): ?EvaluationEvaluator
    {
        return $this->evaluationEvaluator;
    }

    public function setEvaluationEvaluator(EvaluationEvaluator $evaluationEvaluator): self
    {
        $this->evaluationEvaluator = $evaluationEvaluator;

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
        }

        return $this;
    }

    public function removeEvaluationQuestion(EvaluationQuestion $evaluationQuestion): self
    {
        $this->evaluationQuestions->removeElement($evaluationQuestion);

        return $this;
    }

    public function getExpectedValueRangeStart(): ?int
    {
        return $this->expectedValueRangeStart;
    }

    public function setExpectedValueRangeStart(int $expectedValueRangeStart): self
    {
        $this->expectedValueRangeStart = $expectedValueRangeStart;

        return $this;
    }

    public function getExpectedValueRangeEnd(): ?int
    {
        return $this->expectedValueRangeEnd;
    }

    public function setExpectedValueRangeEnd(int $expectedValueRangeEnd): self
    {
        $this->expectedValueRangeEnd = $expectedValueRangeEnd;

        return $this;
    }

    public function getResultText(): ?string
    {
        return $this->resultText;
    }

    public function setResultText(string $resultText): self
    {
        $this->resultText = $resultText;

        return $this;
    }
}
