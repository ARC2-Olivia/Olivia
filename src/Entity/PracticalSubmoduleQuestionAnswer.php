<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleQuestionAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleQuestionAnswerRepository::class)]
class PracticalSubmoduleQuestionAnswer extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleQuestionAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleQuestion $practicalSubmoduleQuestion = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.practicalSubmoduleQuestionAnswer.answerText')]
    #[Gedmo\Translatable]
    private ?string $answerText = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $answerValue = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private array $templatedTextFields = [];

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->practicalSubmoduleQuestion->getType() === PracticalSubmoduleQuestion::TYPE_WEIGHTED && $this->answerValue === null) {
            $context->buildViolation('error.practicalSubmoduleQuestionAnswer.answerValue.weighted')->atPath('answerValue')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAnswerText(): ?string
    {
        return $this->answerText;
    }

    public function setAnswerText(string $answerText): self
    {
        $this->answerText = $answerText;

        return $this;
    }

    public function getAnswerValue(): ?string
    {
        return $this->answerValue;
    }

    public function setAnswerValue(?string $answerValue): self
    {
        $this->answerValue = $answerValue;

        return $this;
    }

    public function getTemplatedTextFields(): array
    {
        return $this->templatedTextFields;
    }

    public function setTemplatedTextFields(?array $templatedTextFields): self
    {
        $this->templatedTextFields = $templatedTextFields;

        return $this;
    }
}
