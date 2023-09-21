<?php

namespace App\Entity;

use App\Repository\QuizQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: QuizQuestionRepository::class)]
class QuizQuestion extends TranslatableEntity
{
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SINGLE_CHOICE = 'single_choice';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LessonItemQuiz $quiz = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.quizQuestion.text')]
    #[Gedmo\Translatable]
    private ?string $text = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.quizQuestion.explanation')]
    #[Gedmo\Translatable]
    private ?string $explanation = null;

    #[ORM\Column(nullable: true)]
    private ?bool $correctAnswer = null;

    #[ORM\Column(length: 32)]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'quizQuestion', targetEntity: QuizQuestionChoice::class, orphanRemoval: true)]
    private Collection $quizQuestionChoices;

    public function __construct()
    {
        $this->quizQuestionChoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?LessonItemQuiz
    {
        return $this->quiz;
    }

    public function setQuiz(?LessonItemQuiz $quiz): self
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): self
    {
        $this->explanation = $explanation;

        return $this;
    }

    public function getCorrectAnswer(): ?bool
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(?bool $correctAnswer): self
    {
        $this->correctAnswer = $correctAnswer;

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

    /**
     * @return Collection<int, QuizQuestionChoice>
     */
    public function getQuizQuestionChoices(): Collection
    {
        return $this->quizQuestionChoices;
    }

    public function addQuizQuestionChoice(QuizQuestionChoice $quizQuestionChoice): self
    {
        if (!$this->quizQuestionChoices->contains($quizQuestionChoice)) {
            $this->quizQuestionChoices->add($quizQuestionChoice);
            $quizQuestionChoice->setQuizQuestion($this);
        }

        return $this;
    }

    public function removeQuizQuestionChoice(QuizQuestionChoice $quizQuestionChoice): self
    {
        if ($this->quizQuestionChoices->removeElement($quizQuestionChoice)) {
            // set the owning side to null (unless already changed)
            if ($quizQuestionChoice->getQuizQuestion() === $this) {
                $quizQuestionChoice->setQuizQuestion(null);
            }
        }

        return $this;
    }
}
