<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorResultCombinerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorResultCombinerRepository::class)]
class PracticalSubmoduleProcessorResultCombiner implements PracticalSubmoduleProcessorImplementationInterface
{
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

    #[Assert\Callback] public function validate(ExecutionContextInterface $context, $payload): void
    {
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): void
    {
        $texts = [];
        foreach ($this->getPracticalSubmoduleProcessors() as $processor) {
            $processorImpl = $processor->getImplementation();
            if ($processor::TYPE_TEMPLATED_TEXT === $processor->getType()) {
                $processorImpl->calculateResult($practicalSubmoduleAssessment);
            }
            $errors = $validator->validate($processorImpl);
            if (0 === $errors->count() && true === $processorImpl->checkConformity($practicalSubmoduleAssessment, $validator)) {
                if (null === $this->resultText) {
                    $this->resultText = '';
                }
                $texts[] = $processorImpl->getResultText();
            }
        }
        $this->setResultText(implode(' ', $texts));
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
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
}
