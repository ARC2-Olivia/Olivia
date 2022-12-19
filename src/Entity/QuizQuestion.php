<?php

namespace App\Entity;

use App\Repository\QuizQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizQuestionRepository::class)]
class QuizQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LessonItemQuiz $quiz = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.quizQuestion.text')]
    private ?string $text = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.quizQuestion.explanation')]
    private ?string $explanation = null;

    #[ORM\Column]
    private ?bool $correctAnswer = null;

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
}
