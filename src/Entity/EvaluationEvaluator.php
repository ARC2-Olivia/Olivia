<?php

namespace App\Entity;

use App\Repository\EvaluationEvaluatorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationEvaluatorRepository::class)]
class EvaluationEvaluator
{
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_SUM_AGGREGATE = 'sum_aggregate';
    public const TYPE_PRODUCT_AGGREGATE = 'product_aggregate';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationEvaluators')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evaluation $evaluation = null;

    #[ORM\Column(length: 63)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'error.evaluationEvaluator.name')]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?bool $included = null;

    #[ORM\OneToOne(mappedBy: 'evaluationEvaluator', cascade: ['persist', 'remove'])]
    private ?EvaluationEvaluatorSimple $evaluationEvaluatorSimple = null;

    #[ORM\OneToOne(mappedBy: 'evaluationEvaluator', cascade: ['persist', 'remove'])]
    private ?EvaluationEvaluatorSumAggregate $evaluationEvaluatorSumAggregate = null;

    #[ORM\OneToOne(mappedBy: 'evaluationEvaluator', cascade: ['persist', 'remove'])]
    private ?EvaluationEvaluatorProductAggregate $evaluationEvaluatorProductAggregate = null;

    public static function getSupportedEvaluationEvaluatorTypes(): array
    {
        return [self::TYPE_SIMPLE, self::TYPE_SUM_AGGREGATE];
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isIncluded(): ?bool
    {
        return $this->included;
    }

    public function setIncluded(?bool $included): self
    {
        $this->included = $included;

        return $this;
    }

    public function getEvaluationEvaluatorSimple(): ?EvaluationEvaluatorSimple
    {
        return $this->evaluationEvaluatorSimple;
    }

    public function setEvaluationEvaluatorSimple(EvaluationEvaluatorSimple $evaluationEvaluatorSimple): self
    {
        // set the owning side of the relation if necessary
        if ($evaluationEvaluatorSimple->getEvaluationEvaluator() !== $this) {
            $evaluationEvaluatorSimple->setEvaluationEvaluator($this);
        }

        $this->evaluationEvaluatorSimple = $evaluationEvaluatorSimple;

        return $this;
    }

    public function getEvaluationEvaluatorSumAggregate(): ?EvaluationEvaluatorSumAggregate
    {
        return $this->evaluationEvaluatorSumAggregate;
    }

    public function setEvaluationEvaluatorSumAggregate(EvaluationEvaluatorSumAggregate $evaluationEvaluatorSumAggregate): self
    {
        // set the owning side of the relation if necessary
        if ($evaluationEvaluatorSumAggregate->getEvaluationEvaluator() !== $this) {
            $evaluationEvaluatorSumAggregate->setEvaluationEvaluator($this);
        }

        $this->evaluationEvaluatorSumAggregate = $evaluationEvaluatorSumAggregate;

        return $this;
    }

    public function getEvaluationEvaluatorProductAggregate(): ?EvaluationEvaluatorProductAggregate
    {
        return $this->evaluationEvaluatorProductAggregate;
    }

    public function setEvaluationEvaluatorProductAggregate(EvaluationEvaluatorProductAggregate $evaluationEvaluatorProductAggregate): self
    {
        // set the owning side of the relation if necessary
        if ($evaluationEvaluatorProductAggregate->getEvaluationEvaluator() !== $this) {
            $evaluationEvaluatorProductAggregate->setEvaluationEvaluator($this);
        }

        $this->evaluationEvaluatorProductAggregate = $evaluationEvaluatorProductAggregate;

        return $this;
    }
}
