<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorRepository::class)]
class PracticalSubmoduleProcessor
{
    public const TYPE_HTML              = 'html';
    public const TYPE_SIMPLE            = 'simple';
    public const TYPE_MAX_VALUE         = 'max_value';
    public const TYPE_SUM_AGGREGATE     = 'sum_aggregate';
    public const TYPE_TEMPLATED_TEXT    = 'templated_text';
    public const TYPE_RESULT_COMBINER   = 'result_combiner';
    public const TYPE_PRODUCT_AGGREGATE = 'product_aggregate';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleProcessors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\Column(length: 63)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'error.practicalSubmoduleProcessor.name')]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?bool $included = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dependentValue = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToMany(targetEntity: File::class)]
    private Collection $resultFiles;

    #[ORM\Column(nullable: true)]
    private ?bool $disabled = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorSimple $practicalSubmoduleProcessorSimple = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorSumAggregate $practicalSubmoduleProcessorSumAggregate = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorProductAggregate $practicalSubmoduleProcessorProductAggregate = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorTemplatedText $practicalSubmoduleProcessorTemplatedText = null;

    #[ORM\ManyToOne]
    private ?PracticalSubmoduleQuestion $dependentPracticalSubmoduleQuestion = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorHtml $practicalSubmoduleProcessorHtml = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorResultCombiner $practicalSubmoduleProcessorResultCombiner = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleProcessor')]
    private ?PracticalSubmoduleProcessorGroup $practicalSubmoduleProcessorGroup = null;

    #[ORM\OneToOne(mappedBy: 'practicalSubmoduleProcessor', cascade: ['persist', 'remove'])]
    private ?PracticalSubmoduleProcessorMaxValue $practicalSubmoduleProcessorMaxValue = null;

    #[ORM\Column(length: 127, nullable: true)]
    private ?string $exportTag = null;

    public function __construct()
    {
        $this->resultFiles = new ArrayCollection();
    }

    public static function getSupportedProcessorTypes(): array
    {
        return [self::TYPE_SIMPLE, self::TYPE_SUM_AGGREGATE, self::TYPE_PRODUCT_AGGREGATE, self::TYPE_TEMPLATED_TEXT, self::TYPE_HTML, self::TYPE_RESULT_COMBINER, self::TYPE_MAX_VALUE];
    }

    public static function getProcessorProcessingProcessorTypes(): array
    {
        return [self::TYPE_SUM_AGGREGATE, self::TYPE_PRODUCT_AGGREGATE, self::TYPE_RESULT_COMBINER, self::TYPE_MAX_VALUE];
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

    public function getPracticalSubmoduleProcessorTemplatedText(): ?PracticalSubmoduleProcessorTemplatedText
    {
        return $this->practicalSubmoduleProcessorTemplatedText;
    }

    public function setPracticalSubmoduleProcessorTemplatedText(PracticalSubmoduleProcessorTemplatedText $practicalSubmoduleProcessorTemplatedText): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorTemplatedText->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorTemplatedText->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorTemplatedText = $practicalSubmoduleProcessorTemplatedText;

        return $this;
    }

    public function getPracticalSubmoduleProcessorHtml(): ?PracticalSubmoduleProcessorHtml
    {
        return $this->practicalSubmoduleProcessorHtml;
    }

    public function setPracticalSubmoduleProcessorHtml(PracticalSubmoduleProcessorHtml $practicalSubmoduleProcessorHtml): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorHtml->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorHtml->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorHtml = $practicalSubmoduleProcessorHtml;

        return $this;
    }

    public function getPracticalSubmoduleProcessorResultCombiner(): ?PracticalSubmoduleProcessorResultCombiner
    {
        return $this->practicalSubmoduleProcessorResultCombiner;
    }

    public function setPracticalSubmoduleProcessorResultCombiner(PracticalSubmoduleProcessorResultCombiner $practicalSubmoduleProcessorResultCombiner): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorResultCombiner->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorResultCombiner->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorResultCombiner = $practicalSubmoduleProcessorResultCombiner;

        return $this;
    }

    public function getImplementation(): ?PracticalSubmoduleProcessorImplementationInterface
    {
        return match ($this->type) {
            self::TYPE_HTML              => $this->getPracticalSubmoduleProcessorHtml(),
            self::TYPE_SIMPLE            => $this->getPracticalSubmoduleProcessorSimple(),
            self::TYPE_MAX_VALUE         => $this->getPracticalSubmoduleProcessorMaxValue(),
            self::TYPE_SUM_AGGREGATE     => $this->getPracticalSubmoduleProcessorSumAggregate(),
            self::TYPE_TEMPLATED_TEXT    => $this->getPracticalSubmoduleProcessorTemplatedText(),
            self::TYPE_RESULT_COMBINER   => $this->getPracticalSubmoduleProcessorResultCombiner(),
            self::TYPE_PRODUCT_AGGREGATE => $this->getPracticalSubmoduleProcessorProductAggregate(),
            default => null
        };
    }

    public function getDependentPracticalSubmoduleQuestion(): ?PracticalSubmoduleQuestion
    {
        return $this->dependentPracticalSubmoduleQuestion;
    }

    public function setDependentPracticalSubmoduleQuestion(?PracticalSubmoduleQuestion $dependentPracticalSubmoduleQuestion): self
    {
        $this->dependentPracticalSubmoduleQuestion = $dependentPracticalSubmoduleQuestion;

        return $this;
    }

    public function getDependentValue(): ?string
    {
        return $this->dependentValue;
    }

    public function setDependentValue(?string $dependentValue): self
    {
        $this->dependentValue = $dependentValue;

        return $this;
    }

    public function isDependencyConditionSet(): bool
    {
        return null !== $this->dependentPracticalSubmoduleQuestion && null !== $this->dependentValue;
    }

    public function isDependencyConditionPassing(PracticalSubmoduleAssessment $practicalSubmoduleAssessment): bool
    {
        if (false === $this->isDependencyConditionSet()) {
            return true;
        }

        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() !== $this->dependentPracticalSubmoduleQuestion->getId()) {
                continue;
            }
            return $assessmentAnswer->getAnswerValue() === $this->getDependentValue();
        }

        return false;
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

    /**
     * @return Collection<int, File>
     */
    public function getResultFiles(): Collection
    {
        return $this->resultFiles;
    }

    public function addResultFile(File $resultFile): self
    {
        if (!$this->resultFiles->contains($resultFile)) {
            $this->resultFiles->add($resultFile);
        }

        return $this;
    }

    public function removeResultFile(File $resultFile): self
    {
        $this->resultFiles->removeElement($resultFile);

        return $this;
    }

    public function clearResultFiles(): self
    {
        $this->resultFiles->clear();

        return $this;
    }

    public function isDisabled(): ?bool
    {
        if (null === $this->disabled) {
            return false;
        }
        return $this->disabled;
    }

    public function setDisabled(?bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function getPracticalSubmoduleProcessorGroup(): ?PracticalSubmoduleProcessorGroup
    {
        return $this->practicalSubmoduleProcessorGroup;
    }

    public function setPracticalSubmoduleProcessorGroup(?PracticalSubmoduleProcessorGroup $practicalSubmoduleProcessorGroup): self
    {
        $this->practicalSubmoduleProcessorGroup = $practicalSubmoduleProcessorGroup;

        return $this;
    }

    public function getPracticalSubmoduleProcessorMaxValue(): ?PracticalSubmoduleProcessorMaxValue
    {
        return $this->practicalSubmoduleProcessorMaxValue;
    }

    public function setPracticalSubmoduleProcessorMaxValue(PracticalSubmoduleProcessorMaxValue $practicalSubmoduleProcessorMaxValue): self
    {
        // set the owning side of the relation if necessary
        if ($practicalSubmoduleProcessorMaxValue->getPracticalSubmoduleProcessor() !== $this) {
            $practicalSubmoduleProcessorMaxValue->setPracticalSubmoduleProcessor($this);
        }

        $this->practicalSubmoduleProcessorMaxValue = $practicalSubmoduleProcessorMaxValue;

        return $this;
    }

    public function getExportTag(): ?string
    {
        return $this->exportTag;
    }

    public function setExportTag(?string $exportTag): self
    {
        $this->exportTag = $exportTag;

        return $this;
    }
}
