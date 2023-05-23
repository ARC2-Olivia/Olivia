<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorTemplatedTextRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorTemplatedTextRepository::class)]
class PracticalSubmoduleProcessorTemplatedText extends TranslatableEntity implements PracticalSubmoduleProcessorImplementationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleProcessor $practicalSubmoduleProcessor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleQuestion $practicalSubmoduleQuestion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->practicalSubmoduleQuestion === null) {
            $context->buildViolation('error.practicalSubmoduleProcessorTemplatedText.question')->atPath('practicalSubmoduleQuestion')->addViolation();
        }
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null)
    {
        // Nothing needs to be calculated.
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
    {
        return true;
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

    public function getPracticalSubmoduleQuestion(): ?PracticalSubmoduleQuestion
    {
        return $this->practicalSubmoduleQuestion;
    }

    public function setPracticalSubmoduleQuestion(?PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        $this->practicalSubmoduleQuestion = $practicalSubmoduleQuestion;

        return $this;
    }

    public function getResultText(): ?string
    {
        return $this->resultText;
    }

    public function setResultText(?string $resultText): self
    {
        $this->resultText = $resultText;

        return $this;
    }
}
