<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorResultCombinerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorResultCombinerRepository::class)]
class PracticalSubmoduleProcessorResultCombiner implements PracticalSubmoduleProcessorImplementationInterface
{
    public const SEPARATE_BY_SPACE          = 0;
    public const SEPARATE_BY_NEWLINE        = 1;
    public const SEPARATE_BY_DOUBLE_NEWLINE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'practicalSubmoduleProcessorResultCombiner', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleProcessor $practicalSubmoduleProcessor = null;

    #[ORM\ManyToMany(targetEntity: PracticalSubmoduleProcessor::class)]
    #[ORM\JoinTable(name: 'practical_submodule_processor_result_combiner_processor')]
    private Collection $practicalSubmoduleProcessors;

    private ?string $resultText = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $separateBy = null;

    #[Assert\Callback] public function validate(ExecutionContextInterface $context, $payload): void
    {
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null, TranslatorInterface $translator = null): void
    {
        $texts = [];

        $processorsIter = $this->getPracticalSubmoduleProcessors()->getIterator();
        $processorsIter->uasort(function (PracticalSubmoduleProcessor $a, PracticalSubmoduleProcessor $b) {
            if ($a->getPosition() === $b->getPosition()) {
                return 0;
            }
            return $a->getPosition() > $b->getPosition() ? 1 : -1;
        });
        $processors = iterator_to_array($processorsIter);

        foreach ($processors as $processor) {
            $processorImpl = $processor->getImplementation();
            $processorImpl->calculateResult($practicalSubmoduleAssessment, $validator, $translator);
            $errors = $validator->validate($processorImpl);
            if (0 === $errors->count() && true === $processorImpl->checkConformity($practicalSubmoduleAssessment, $validator)) {
                if (null === $this->resultText) {
                    $this->resultText = '';
                }
                $currResultText = $processorImpl->getResultText();
                if (null !== $currResultText && '' !== trim($currResultText)) {
                    $texts[] = $currResultText;
                }
            }
        }

        $separator = match ($this->separateBy) {
            self::SEPARATE_BY_NEWLINE => "\n",
            self::SEPARATE_BY_DOUBLE_NEWLINE => "\n\n",
            default => ' '
        };

        $this->setResultText(implode($separator, $texts));
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null, TranslatorInterface $translator = null): bool
    {
        return $this->practicalSubmoduleProcessor->isDependencyConditionPassing($practicalSubmoduleAssessment);
    }

    public function __construct()
    {
        $this->practicalSubmoduleProcessors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPracticalSubmoduleProcessor(): ?PracticalSubmoduleProcessor
    {
        return $this->practicalSubmoduleProcessor;
    }

    public function setPracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        $this->practicalSubmoduleProcessor = $practicalSubmoduleProcessor;

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
        }

        return $this;
    }

    public function removePracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        $this->practicalSubmoduleProcessors->removeElement($practicalSubmoduleProcessor);

        return $this;
    }

    public function getResultText(): ?string
    {
        return $this->resultText;
    }

    public function setResultText(?string $resultText): PracticalSubmoduleProcessorImplementationInterface
    {
        $this->resultText = $resultText;

        return $this;
    }

    public function getSeparateBy(): ?int
    {
        if (null === $this->separateBy) {
            return self::SEPARATE_BY_SPACE;
        }
        return $this->separateBy;
    }

    public function setSeparateBy(?int $separateBy): self
    {
        $this->separateBy = $separateBy;

        return $this;
    }
}
