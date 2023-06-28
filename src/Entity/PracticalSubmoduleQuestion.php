<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleQuestionRepository::class)]
class PracticalSubmoduleQuestion extends TranslatableEntity
{
    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_WEIGHTED = 'weighted';
    public const TYPE_NUMERICAL_INPUT = 'numerical_input';
    public const TYPE_TEXT_INPUT = 'text_input';
    public const TYPE_TEMPLATED_TEXT_INPUT = 'templated_text_input';
    public const TYPE_MULTI_CHOICE = 'multi_choice';
    public const TYPE_LIST_INPUT = 'list_input';
    public const TYPE_STATIC_TEXT = 'static_text';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\Column(length: 63)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Gedmo\Translatable]
    private ?string $questionText = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmoduleQuestion', targetEntity: PracticalSubmoduleQuestionAnswer::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleQuestionAnswers;

    #[ORM\Column(nullable: true)]
    private ?bool $evaluable = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $dependentPracticalSubmoduleQuestion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dependentValue = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleQuestions')]
    private ?PracticalSubmodulePage $practicalSubmodulePage = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->dependentPracticalSubmoduleQuestion !== null) {
            switch ($this->dependentPracticalSubmoduleQuestion->getType()) {
                case PracticalSubmoduleQuestion::TYPE_YES_NO:
                    if ($this->dependentValue !== '0' && $this->dependentValue !== '1') {
                        $context->buildViolation('error.practicalSubmoduleQuestion.dependentValue.notBool')->atPath('dependentValue')->addViolation();
                    }
                    break;
                case PracticalSubmoduleQuestion::TYPE_WEIGHTED:
                case PracticalSubmoduleQuestion::TYPE_NUMERICAL_INPUT:
                    if (!is_numeric($this->dependentValue)) {
                        $context->buildViolation('error.practicalSubmoduleQuestion.dependentValue.notNumeric')->atPath('dependentValue')->addViolation();
                    }
                    break;
            }
        }
    }

    public static function getNumericTypes(): array
    {
        return [self::TYPE_WEIGHTED, self::TYPE_NUMERICAL_INPUT];
    }

    public static function getSingleChoiceTypes(): array
    {
        return [self::TYPE_YES_NO, self::TYPE_WEIGHTED];
    }

    public static function getMultipleAnswerTypes(): array
    {
        return [self::TYPE_MULTI_CHOICE, self::TYPE_LIST_INPUT];
    }

    public function __construct()
    {
        $this->practicalSubmoduleQuestionAnswers = new ArrayCollection();
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
     * @return Collection<int, PracticalSubmoduleQuestionAnswer>
     */
    public function getPracticalSubmoduleQuestionAnswers(): Collection
    {
        return $this->practicalSubmoduleQuestionAnswers;
    }

    public function addPracticalSubmoduleQuestionAnswer(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer): self
    {
        if (!$this->practicalSubmoduleQuestionAnswers->contains($practicalSubmoduleQuestionAnswer)) {
            $this->practicalSubmoduleQuestionAnswers->add($practicalSubmoduleQuestionAnswer);
            $practicalSubmoduleQuestionAnswer->setPracticalSubmoduleQuestion($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleQuestionAnswer(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer): self
    {
        if ($this->practicalSubmoduleQuestionAnswers->removeElement($practicalSubmoduleQuestionAnswer)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion() === $this) {
                $practicalSubmoduleQuestionAnswer->setPracticalSubmoduleQuestion(null);
            }
        }

        return $this;
    }

    public function isEvaluable(): ?bool
    {
        return $this->evaluable;
    }

    public function setEvaluable(?bool $evaluable): self
    {
        $this->evaluable = $evaluable;

        return $this;
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

    public function getDependentPracticalSubmoduleQuestion(): ?self
    {
        return $this->dependentPracticalSubmoduleQuestion;
    }

    public function setDependentPracticalSubmoduleQuestion(?self $dependentPracticalSubmoduleQuestion): self
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

    public function getPracticalSubmodulePage(): ?PracticalSubmodulePage
    {
        return $this->practicalSubmodulePage;
    }

    public function setPracticalSubmodulePage(?PracticalSubmodulePage $practicalSubmodulePage): self
    {
        $this->practicalSubmodulePage = $practicalSubmodulePage;

        return $this;
    }
}
