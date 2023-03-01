<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorRepository::class)]
class PracticalSubmoduleProcessor
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
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\Column(length: 63)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'error.evaluationEvaluator.name')]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?bool $included = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorSimple $practicalSubmoduleProcessorSimple = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorSumAggregate $practicalSubmoduleProcessorSumAggregate = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorProductAggregate $practicalSubmoduleProcessorProductAggregate = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $position = null;

    public static function getSupportedEvaluationEvaluatorTypes(): array
    {
        return [self::TYPE_SIMPLE, self::TYPE_SUM_AGGREGATE];
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

    public function getPracticalSubmoduleProcessorSimple(): ?PracticalSubmoduleProcessorSimple
    {
        return $this->practicalSubmoduleProcessorSimple;
    }

    public function setPracticalSubmoduleProcessorSimple(PracticalSubmoduleProcessorSimple $practicalSubmoduleProcessorSimple): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorSimple->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorSimple->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorSimple = $practicalSubmoduleProcessorSimple;

        return $this;
    }

    public function getPracticalSubmoduleProcessorSumAggregate(): ?PracticalSubmoduleProcessorSumAggregate
    {
        return $this->practicalSubmoduleProcessorSumAggregate;
    }

    public function setPracticalSubmoduleProcessorSumAggregate(PracticalSubmoduleProcessorSumAggregate $practicalSubmoduleProcessorSumAggregate): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorSumAggregate->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorSumAggregate->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorSumAggregate = $practicalSubmoduleProcessorSumAggregate;

        return $this;
    }

    public function getPracticalSubmoduleProcessorProductAggregate(): ?PracticalSubmoduleProcessorProductAggregate
    {
        return $this->practicalSubmoduleProcessorProductAggregate;
    }

    public function setPracticalSubmoduleProcessorProductAggregate(PracticalSubmoduleProcessorProductAggregate $practicalSubmoduleProcessorProductAggregate): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorProductAggregate->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorProductAggregate->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorProductAggregate = $practicalSubmoduleProcessorProductAggregate;

        return $this;
    }

    public function getEvaluationEvaluatorImplementation(): ?PracticalSubmoduleProcessorImplementationInterface
    {
        return match ($this->type) {
            self::TYPE_SIMPLE => $this->getPracticalSubmoduleProcessorSimple(),
            self::TYPE_SUM_AGGREGATE => $this->getPracticalSubmoduleProcessorSumAggregate(),
            self::TYPE_PRODUCT_AGGREGATE => $this->getPracticalSubmoduleProcessorProductAggregate(),
            default => null
        };
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
